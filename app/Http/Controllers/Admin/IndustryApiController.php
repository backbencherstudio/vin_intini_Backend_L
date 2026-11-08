<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\IndustrySections;
use Illuminate\Http\JsonResponse;

class IndustryApiController extends Controller
{
    /* ============================================================
       SECTION 1: INDUSTRY DATA (Sections, Categories & Items)
       ============================================================ */

    public function getPsychologyBiotech()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q): void {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
                        }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Biotechnology Industry', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getPsychologyPharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'psychotropics')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q): void {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                        }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Psychotropics', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getPsychologyPublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->with(['IndustryItem' => function ($q): void {
                        $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
                    }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Newly Published Articles', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getNeuroscienceBiotech()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q): void {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
                        }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Neuroscience Biotechnology', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getNeurosciencePharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychotropics')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q): void {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                        }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Neuroscience Pharma & Psychotropics', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function getNeurosciencePublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory' => function ($query): void {
                    $query->with(['IndustryItem' => function ($q): void {
                        $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
                    }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Neuroscience Research Publications', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ============================================================
       SECTION 2: PARTNERS DATA (Pro Industry Partners)
       ============================================================ */

    /**
     * Unified partners endpoint returning all active Pro Industries.
     */
    public function getAllPartners(): JsonResponse
    {
        try {
            $partners = Industry::where(function ($query): void {
                $query->where('status', 'active')->orWhereNull('status');
            })
                ->whereNotNull('logo')
                ->where('logo', '!=', '')
                ->latest('id')
                ->get()
                ->map(function ($industry): array {
                    $logoUrl = $industry->logo;
                    $website = $industry->website;
                    if ($website && ! str_starts_with($website, 'http://') && ! str_starts_with($website, 'https://')) {
                        $website = 'https://'.$website;
                    }

                    return [
                        'id' => $industry->id,
                        'name' => $industry->name,
                        'title' => $industry->name,
                        'partner_name' => $industry->name,
                        'slug' => $industry->slug,
                        'logo' => $logoUrl,
                        'partner_logo' => $logoUrl,
                        'image' => $logoUrl,
                        'website' => $website,
                        'website_url' => $website,
                        'tagline' => $industry->tagline,
                        'partner_tag' => $industry->tagline,
                        'description' => $industry->description,
                        'partner_desc' => $industry->description,
                        'industry' => $industry->industry,
                        'status' => $industry->status ?? 'active',
                    ];
                });

            return response()->json(['success' => true, 'partners' => $partners], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // --- Psychology Network Partners ---
    public function getPsychologyBiotechPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }

    public function getPsychologyPharmaPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }

    public function getPsychologyPubPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }

    // --- Neuroscience Network Partners ---
    public function getNeuroscienceBiotechPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }

    public function getNeurosciencePharmaPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }

    public function getNeurosciencePubPartners(): JsonResponse
    {
        return $this->getAllPartners();
    }
}
