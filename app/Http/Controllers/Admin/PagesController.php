<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\OptimizedImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        abort(404);
    }

    public function update(Request $request, $id, OptimizedImageUploadService $imageUploadService)
    {
        $page = Page::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'founder_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'what_we_do_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'team.*.photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*.file' => 'nullable|mimes:mp4,mov,avi,wmv|max:102400',
            'videos.*.thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'leading_institutions.*.logo' => 'nullable|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ], [
            'founder_photo.max' => 'The founder photo must not be larger than 5MB.',
            'team.*.photo.max' => 'The image must not be larger than 5MB.',
            'what_we_do_image.max' => 'The diagram image must not be larger than 5MB.',
            'videos.*.thumbnail.max' => 'Video thumbnail must not be larger than 5MB.',
            'leading_institutions.*.logo.max' => 'Institution logo must not be larger than 2MB.',
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
            $founder['photo'] = $imageUploadService->store($request->file('founder_photo'), 'pages/founder');
        }

        $page->founder_info = $founder;

        // if ($request->hasFile('what_we_do_image')) {
        //     if ($page->what_we_do_image) {
        //         Storage::disk('public')->delete($page->what_we_do_image);
        //     }
        //     $page->what_we_do_image = $request->file('what_we_do_image')->store('pages/about', 'public');
        // }

        $newTeamData = [];
        if ($request->has('team')) {
            foreach ($request->team as $key => $member) {
                $photoPath = $member['old_photo'] ?? null;
                if ($request->hasFile("team.$key.photo")) {
                    if ($photoPath) {
                        Storage::disk('public')->delete($photoPath);
                    }
                    $photoPath = $imageUploadService->store($request->file("team.$key.photo"), 'pages/team');
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
            if (isset($oMember['photo']) && ! collect($newTeamData)->contains('photo', $oMember['photo'])) {
                Storage::disk('public')->delete($oMember['photo']);
            }
        }
        $page->team_members = $newTeamData;

        $newVideoData = [];
        if ($request->has('videos')) {
            foreach ($request->videos as $vKey => $video) {
                $item = [
                    'title' => $video['title'],
                    'source' => $video['source'],
                    'type' => ($video['source'] == 'url') ? 'youtube_video' : 'local_video',
                ];

                $thumbPath = $video['old_thumbnail'] ?? null;
                if ($request->hasFile("videos.$vKey.thumbnail")) {
                    if ($thumbPath) {
                        Storage::disk('public')->delete($thumbPath);
                    }
                    $thumbPath = $imageUploadService->store($request->file("videos.$vKey.thumbnail"), 'pages/videos/thumbnails');
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
                        $vPath = $this->processAboutVideo($request->file("videos.$vKey.file"));
                    }
                    $item['path'] = $vPath;
                    $item['url'] = null;
                }
                $newVideoData[] = $item;
            }
        }
        foreach ($oldVideos as $oVideo) {
            if (isset($oVideo['path']) && ! collect($newVideoData)->contains('path', $oVideo['path'])) {
                Storage::disk('public')->delete($oVideo['path']);
            }
            if (isset($oVideo['thumbnail']) && ! collect($newVideoData)->contains('thumbnail', $oVideo['thumbnail'])) {
                Storage::disk('public')->delete($oVideo['thumbnail']);
            }
        }
        $page->features_videos = $newVideoData;

        // $newVideoData = [];
        // if ($request->has('videos')) {
        //     foreach ($request->videos as $vKey => $video) {
        //         $item = [
        //             'title' => $video['title'],
        //             'source' => $video['source'],
        //             'type' => ($video['source'] == 'url') ? 'youtube_video' : 'local_video',
        //         ];

        //         $thumbPath = $video['old_thumbnail'] ?? null;
        //         if ($request->hasFile("videos.$vKey.thumbnail")) {
        //             if ($thumbPath) {
        //                 Storage::disk('public')->delete($thumbPath);
        //             }
        //             $thumbPath = $request->file("videos.$vKey.thumbnail")->store('pages/videos/thumbnails', 'public');
        //         }
        //         $item['thumbnail'] = $thumbPath;

        //         if ($video['source'] == 'url') {
        //             $item['url'] = $video['url'];
        //             $item['path'] = null;
        //         } else {
        //             $vPath = $video['path'] ?? null;
        //             if ($request->hasFile("videos.$vKey.file")) {
        //                 if ($vPath) {
        //                     Storage::disk('public')->delete($vPath);
        //                 }
        //                 $vPath = $request->file("videos.$vKey.file")->store('pages/videos', 'public');
        //             }
        //             $item['path'] = $vPath;
        //             $item['url'] = null;
        //         }
        //         $newVideoData[] = $item;
        //     }
        // }

        // foreach ($oldVideos as $oVideo) {
        //     if (isset($oVideo['path']) && $oVideo['path']) {
        //         if (! collect($newVideoData)->contains('path', $oVideo['path'])) {
        //             Storage::disk('public')->delete($oVideo['path']);
        //         }
        //     }
        //     if (isset($oVideo['thumbnail']) && ! collect($newVideoData)->contains('thumbnail', $oVideo['thumbnail'])) {
        //         Storage::disk('public')->delete($oVideo['thumbnail']);
        //     }
        // }
        // $page->features_videos = $newVideoData;

        // FAQ Management
        $newFaqData = [];
        if ($request->has('faqs')) {
            foreach ($request->faqs as $faq) {
                if (! empty($faq['question']) && ! empty($faq['answer'])) {
                    $newFaqData[] = [
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ];
                }
            }
        }
        $page->faqs = $newFaqData;

        // Leading Institutions Management
        $oldInstitutions = $page->leading_institutions ?? [];
        $newInstitutionsData = [];

        if ($request->has('leading_institutions')) {
            foreach ($request->leading_institutions as $key => $item) {
                $logoPath = $item['old_logo'] ?? null;

                if ($request->hasFile("leading_institutions.$key.logo")) {
                    if ($logoPath) {
                        Storage::disk('public')->delete($logoPath);
                    }
                    $logoPath = $imageUploadService->store($request->file("leading_institutions.$key.logo"), 'pages/institutions');
                }

                $newInstitutionsData[] = [
                    'name' => $item['name'] ?? '',
                    'logo' => $logoPath,
                    'is_active' => isset($item['is_active']) ? true : false,
                ];
            }
        }

        foreach ($oldInstitutions as $oldItem) {
            if (isset($oldItem['logo']) && ! collect($newInstitutionsData)->contains('logo', $oldItem['logo'])) {
                Storage::disk('public')->delete($oldItem['logo']);
            }
        }

        $page->leading_institutions = collect($newInstitutionsData)->sortBy('order')->values()->all();

        $page->save();

        return back()->with('success', 'Page content updated successfully!');
    }

    private function processAboutVideo($file)
    {
        $filename = 'pages/videos/'.Str::uuid().'.mp4';
        $inputPath = $file->getRealPath();
        $outputPath = storage_path('app/public/'.$filename);

        if (! Storage::disk('public')->exists('pages/videos')) {
            Storage::disk('public')->makeDirectory('pages/videos');
        }

        $ffmpegPath = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
                      ? 'C:/ffmpeg/bin/ffmpeg.exe'
                      : '/usr/bin/ffmpeg';

        $command = sprintf(
            "\"%s\" -i %s -vf \"scale='min(1280,iw)':-2\" -c:v libx264 -preset superfast -crf 28 -movflags +faststart -c:a aac -b:a 128k %s -y 2>&1",
            $ffmpegPath,
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $status);

        if ($status !== 0) {
            \Log::error('FFmpeg Error: '.implode("\n", $output));

            return $file->store('pages/videos', 'public');
        }

        return $filename;
    }

    // get page data for API
    public function getPageData(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)->where('is_active', true)->first();

        if (! $page) {
            return response()->json(['success' => false, 'message' => 'Page not found'], 404);
        }

        if (in_array($slug, ['privacy-policy', 'terms-and-conditions'])) {
            return response()->json([
                'success' => true,
                'data' => ['title' => $page->title, 'content' => $page->content],
            ], 200);
        }

        $requestedSections = $request->query('section');
        $sections = $requestedSections ? explode(',', $requestedSections) : [];

        $responseData = ['title' => $page->title];

        if (empty($sections) || in_array('content', $sections)) {
            $responseData['content'] = $page->content;
        }

        // 1. Core Values (Vision, Mission, Strategy)
        if (empty($sections) || in_array('core_values', $sections)) {
            $responseData['core_values'] = [
                'vision' => $page->vision,
                'mission' => $page->mission,
                'strategy' => $page->strategy,
            ];
        }

        // 2. Founder Info
        if (empty($sections) || in_array('founder', $sections)) {
            $founder = $page->founder_info ?? [];
            if (isset($founder['photo'])) {
                $founder['photo_url'] = asset('storage/'.$founder['photo']);
            }
            $responseData['founder'] = $founder;
        }

        // 3. What We Do (Diagram)
        if (empty($sections) || in_array('what_we_do', $sections)) {
            $responseData['what_we_do'] = [
                'diagram_url' => $page->what_we_do_image ? asset('storage/'.$page->what_we_do_image) : null,
            ];
        }

        // 4. Team Members
        if (empty($sections) || in_array('team', $sections)) {
            $responseData['team'] = collect($page->team_members ?? [])->map(function ($member) {
                return [
                    'name' => $member['name'] ?? '',
                    'title' => $member['title'] ?? '',
                    'bio' => $member['bio'] ?? '',
                    'photo_url' => ! empty($member['photo']) ? asset('storage/'.$member['photo']) : null,
                ];
            });
        }

        // 5. Videos
        if (empty($sections) || in_array('videos', $sections)) {
            $responseData['videos'] = collect($page->features_videos ?? [])->map(function ($video) {
                return [
                    'title' => $video['title'] ?? '',
                    'source' => $video['source'] ?? '',
                    'type' => $video['type'] ?? '',
                    'url' => $video['url'] ?? null,
                    'file_url' => isset($video['path']) ? asset('storage/'.$video['path']) : null,
                    'thumbnail_url' => isset($video['thumbnail']) ? asset('storage/'.$video['thumbnail']) : null,
                ];
            });
        }

        // 6. FAQs
        if (empty($sections) || in_array('faqs', $sections)) {
            $responseData['faqs'] = $page->faqs ?? [];
        }

        // 7. Leading Institutions
        if (empty($sections) || in_array('leading_institutions', $sections)) {
            $responseData['leading_institutions'] = collect($page->leading_institutions ?? [])
                ->where('is_active', true)
                ->map(function ($item) {
                    return [
                        'name' => $item['name'],
                        'logo_url' => ! empty($item['logo']) ? asset('storage/'.$item['logo']) : null,
                    ];
                })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ], 200);
    }
}
