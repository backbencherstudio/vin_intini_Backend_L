<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

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
                $founder['photo_url'] = asset('storage/' . $founder['photo']);
            }
            $responseData['founder'] = $founder;
        }

        // 3. What We Do (Diagram)
        if (empty($sections) || in_array('what_we_do', $sections)) {
            $responseData['what_we_do'] = [
                'diagram_url' => $page->what_we_do_image ? asset('storage/' . $page->what_we_do_image) : null,
            ];
        }

        // 4. Team Members
        if (empty($sections) || in_array('team', $sections)) {
            $responseData['team'] = collect($page->team_members ?? [])->map(function ($member) {
                return [
                    'name' => $member['name'] ?? '',
                    'title' => $member['title'] ?? '',
                    'bio' => $member['bio'] ?? '',
                    'photo_url' => ! empty($member['photo']) ? asset('storage/' . $member['photo']) : null,
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
                    'file_url' => isset($video['path']) ? asset('storage/' . $video['path']) : null,
                    'thumbnail_url' => isset($video['thumbnail']) ? asset('storage/' . $video['thumbnail']) : null,
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
                        'logo_url' => ! empty($item['logo']) ? asset('storage/' . $item['logo']) : null,
                    ];
                })->values();
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ], 200);
    }

    public function update()
    {
        
    }
}
