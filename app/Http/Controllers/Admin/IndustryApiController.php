<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryPartner;
use App\Models\IndustrySections;
use Illuminate\Http\Request;

class IndustryApiController extends Controller
{
    public function getPsychologyBiotech()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory' => function ($query) {
                    // 'All' remove "all" category
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'psychology')
                ->where('industry_type', 'biotechnology')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Biotechnology Industry',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Psychology - psychotropics
     */
    public function getPsychologyPharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'psychotropics')
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All') // 'All' remove "all" category
                        ->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'psychology')
                ->where('industry_type', 'psychotropics')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Psychotropics',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Psychology - Publications
     */
    public function getPsychologyPublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory' => function ($query) {
                    $query->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'psychology')
                ->where('industry_type', 'publications')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Newly Published Articles',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Neuroscience - Biotechnology
     */
    public function getNeuroscienceBiotech()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'neuroscience')
                ->where('industry_type', 'biotechnology')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Neuroscience Biotechnology',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Neuroscience - psychotropics
     */
    public function getNeurosciencePharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychotropics')
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychotropics')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Neuroscience Pharma & Psychotropics',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Neuroscience - Publications
     */
    public function getNeurosciencePublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory' => function ($query) {
                    $query->with(['IndustryItem' => function ($q) {
                            $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
                        }]);
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'neuroscience')
                ->where('industry_type', 'publications')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Neuroscience Research Publications',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
