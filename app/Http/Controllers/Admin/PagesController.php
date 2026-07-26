<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PagesController extends Controller
{
    public function edit($slug)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        if ($slug == 'about-us') {
            return view('admin.pages.about_us', compact('page'));
        }

        if ($slug == 'privacy-policy') {
            return view('admin.pages.privacy_policy', compact('page'));
        }

        if ($slug == 'terms-and-conditions') {
            return view('admin.pages.terms_condition', compact('page'));
        }

        if ($slug == 'how-to-use') {
            return view('admin.pages.about_us', compact('page'));
        }

        abort(404);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'founder_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'what_we_do_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'team.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*.file' => 'nullable|mimes:mp4,mov,avi,wmv|max:102400',
            'videos.*.thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'founder_photo.max' => 'The founder photo must not be larger than 5MB.',
            'team.*.photo.max' => 'The image must not be larger than 5MB.',
            'what_we_do_image.max' => 'The diagram image must not be larger than 5MB.',
            'videos.*.thumbnail.max' => 'Video thumbnail must not be larger than 2MB.',
        ]);

        $oldVideos = $page->features_videos ?? [];
        $oldTeam = $page->team_members ?? [];

        $page->title = $request->title;
        $page->content = $request->content;
        $page->vision = $request->vision;
        $page->mission = $request->mission;
        $page->strategy = $request->strategy;

        $founder = $page->founder_info ?? [];
        $founder['name'] = $request->founder_name;
        $founder['designation'] = $request->founder_designation;
        $founder['bio'] = $request->founder_bio;
        $founder['signature'] = $request->founder_signature;

        if ($request->hasFile('founder_photo')) {
            if (isset($founder['photo'])) {
                Storage::disk('public')->delete($founder['photo']);
            }
            $founder['photo'] = $request->file('founder_photo')->store('pages/founder', 'public');
        }

        $page->founder_info = $founder;
        if ($request->hasFile('what_we_do_image')) {
            if ($page->what_we_do_image) {
                Storage::disk('public')->delete($page->what_we_do_image);
            }
            $page->what_we_do_image = $request->file('what_we_do_image')->store('pages/about', 'public');
        }

        $newTeamData = [];
        if ($request->has('team')) {
            foreach ($request->team as $key => $member) {
                $photoPath = $member['old_photo'] ?? null;
                if ($request->hasFile("team.$key.photo")) {
                    if ($photoPath) {
                        Storage::disk('public')->delete($photoPath);
                    }
                    $photoPath = $request->file("team.$key.photo")->store('pages/team', 'public');
                }
                $newTeamData[] = [
                    'name' => $member['name'],
                    'title' => $member['title'],
                    'bio' => $member['bio'],
                    'photo' => $photoPath,
                ];
            }
        }
        foreach ($oldTeam as $oMember) {
            if (isset($oMember['photo']) && !collect($newTeamData)->contains('photo', $oMember['photo'])) {
                Storage::disk('public')->delete($oMember['photo']);
            }
        }
        $page->team_members = $newTeamData;

        $newVideoData = [];
        if ($request->has('videos')) {
            foreach ($request->videos as $vKey => $video) {
                $item = ['title' => $video['title'], 'source' => $video['source']];

                $thumbPath = $video['old_thumbnail'] ?? null;
                if ($request->hasFile("videos.$vKey.thumbnail")) {
                    if ($thumbPath) {
                        Storage::disk('public')->delete($thumbPath);
                    }
                    $thumbPath = $request->file("videos.$vKey.thumbnail")->store('pages/videos/thumbnails', 'public');
                }
                $item['thumbnail'] = $thumbPath;

                if ($video['source'] == 'url') {
                    $item['url'] = $video['url'];
                    $item['path'] = null;
                } else {
                    $vPath = $video['path'] ?? null;
                    if ($request->hasFile("videos.$vKey.file")) {
                        if ($vPath) {
                            Storage::disk('public')->delete($vPath);
                        }
                        $vPath = $request->file("videos.$vKey.file")->store('pages/videos', 'public');
                    }
                    $item['path'] = $vPath;
                    $item['url'] = null;
                }
                $newVideoData[] = $item;
            }
        }

        foreach ($oldVideos as $oVideo) {
            if (isset($oVideo['path']) && $oVideo['path']) {
                if (!collect($newVideoData)->contains('path', $oVideo['path'])) {
                    Storage::disk('public')->delete($oVideo['path']);
                }
            }
            if (isset($oVideo['thumbnail']) && !collect($newVideoData)->contains('thumbnail', $oVideo['thumbnail'])) {
                Storage::disk('public')->delete($oVideo['thumbnail']);
            }
        }
        $page->features_videos = $newVideoData;

        // FAQ Management
        $newFaqData = [];
        if ($request->has('faqs')) {
            foreach ($request->faqs as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $newFaqData[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ];
                }
            }
        }
        $page->faqs = $newFaqData;

        $page->save();

        return back()->with('success', 'About Us updated successfully and unused files deleted!');
    }
}
