<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\OptimizedImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function getPageData(Request $request, $slug)
    {
        $page = Page::where('slug', $slug)->first();

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

    public function update(Request $request, $slug, OptimizedImageUploadService $imageUploadService)
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'founder_photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'team.*.photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'videos.*.file' => 'sometimes|nullable|mimes:mp4,mov,avi,wmv|max:102400',
            'videos.*.thumbnail' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'leading_institutions.*.logo' => 'sometimes|nullable|file|mimes:jpeg,png,jpg,webp,svg|max:2048',
        ]);

        $page->fill(
            $request->only([
                'title',
                'content',
                'vision',
                'mission',
                'strategy',
            ])
        );

        // Basic fields update

        if (
            $request->hasAny([
                'founder_name',
                'founder_designation',
                'founder_bio',
                'founder_signature',
                'founder_photo',
            ])
        ) {

            $founder = $page->founder_info ?? [];

            if ($request->has('founder_name')) {
                $founder['name'] = $request->founder_name;
            }

            if ($request->has('founder_designation')) {
                $founder['designation'] = $request->founder_designation;
            }

            if ($request->has('founder_bio')) {
                $founder['bio'] = $request->founder_bio;
            }

            if ($request->has('founder_signature')) {
                $founder['signature'] = $request->founder_signature;
            }
            if ($request->hasFile('founder_photo')) {

                if (! empty($founder['photo'])) {
                    Storage::disk('public')
                        ->delete($founder['photo']);
                }

                $founder['photo'] =
                    $imageUploadService->store($request->file('founder_photo'), 'pages/founder');
            }

            $page->founder_info = $founder;
        }

        // Team Members

        if ($request->has('team')) {
            $team = $page->team_members ?? [];

            foreach ($request->team as $index => $member) {

                $oldMember = $team[$index] ?? [];

                $oldMember['name'] =
                    $member['name'] ?? ($oldMember['name'] ?? null);

                $oldMember['title'] =
                    $member['title'] ?? ($oldMember['title'] ?? null);

                $oldMember['bio'] =
                    $member['bio'] ?? ($oldMember['bio'] ?? null);

                if ($request->hasFile("team.$index.photo")) {

                    if (! empty($oldMember['photo'])) {
                        Storage::disk('public')
                            ->delete($oldMember['photo']);
                    }

                    $oldMember['photo'] =
                        $imageUploadService->store($request->file("team.$index.photo"), 'pages/team');
                }

                $team[$index] = $oldMember;
            }

            $page->team_members =
                array_values($team);
        }

        // Videos

        if ($request->has('videos')) {

            $videos =
                $page->features_videos ?? [];

            foreach ($request->videos as $index => $video) {

                $oldVideo =
                    $videos[$index] ?? [];

                $oldVideo['title'] =
                    $video['title'] ??
                    ($oldVideo['title'] ?? null);

                $oldVideo['source'] =
                    $video['source'] ??
                    ($oldVideo['source'] ?? null);

                if (
                    isset($video['source'])
                    &&
                    $video['source'] == 'url'
                ) {

                    if (! empty($oldVideo['path'])) {
                        Storage::disk('public')
                            ->delete($oldVideo['path']);
                    }

                    $oldVideo['type'] = 'youtube_video';
                    $oldVideo['url'] = $video['url'] ?? ($oldVideo['url'] ?? null);
                    $oldVideo['path'] = null;
                } else {

                    $oldVideo['type'] = 'local_video';

                    if (
                        $request->hasFile("videos.$index.file")
                    ) {

                        if (! empty($oldVideo['path'])) {
                            Storage::disk('public')
                                ->delete($oldVideo['path']);
                        }

                        $oldVideo['path'] = null;

                        $oldVideo['path'] =
                            $this->processAboutVideo(
                                $request->file("videos.$index.file")
                            );
                    }
                }

                if (
                    $request->hasFile("videos.$index.thumbnail")
                ) {

                    if (! empty($oldVideo['thumbnail'])) {

                        Storage::disk('public')
                            ->delete($oldVideo['thumbnail']);
                    }

                    $oldVideo['thumbnail'] =
                        $imageUploadService->store(
                            $request->file("videos.$index.thumbnail"),
                            'pages/videos/thumbnails'
                        );
                }

                $videos[$index] = $oldVideo;
            }

            $page->features_videos =
                array_values($videos);
        }

        // FAQ

        if ($request->has('faqs')) {

            $page->faqs =
                $request->faqs;
        }

        // Leading Institutions JSON

        if ($request->has('leading_institutions')) {

            $institutions =
                $page->leading_institutions ?? [];

            foreach (
                $request->leading_institutions as $index => $item
            ) {

                $old =
                    $institutions[$index] ?? [];

                $old['name'] =
                    $item['name'] ??
                    ($old['name'] ?? null);

                if (
                    $request->hasFile(
                        "leading_institutions.$index.logo"
                    )
                ) {

                    if (! empty($old['logo'])) {

                        Storage::disk('public')
                            ->delete($old['logo']);
                    }

                    $old['logo'] =
                        $imageUploadService->store(
                            $request->file("leading_institutions.$index.logo"),
                            'pages/institutions'
                        );
                }

                if (isset($item['is_active'])) {
                    $old['is_active'] = (bool) $item['is_active'];
                }

                $institutions[$index] =
                    $old;
            }

            $page->leading_institutions =
                array_values($institutions);
        }

        $page->save();

        return response()->json([
            'status' => true,
            'message' => 'Page updated successfully',
            'data' => $page->fresh(),
        ]);
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
            '"%s" -i %s -vf "scale=\'min(1280,iw)\':-2" -c:v libx264 -preset superfast -crf 28 -movflags +faststart -c:a aac -b:a 128k %s -y 2>&1',
            $ffmpegPath,
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $status);

        if ($status !== 0) {
            Log::error('FFmpeg Error: '.implode("\n", $output));

            return $file->store('pages/videos', 'public');
        }

        return $filename;
    }
}
