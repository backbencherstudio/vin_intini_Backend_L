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
            // ১. সেকশন অনুযায়ী ক্যাটাগরি এবং ক্যাটাগরি অনুযায়ী আইটেম নিয়ে আসা
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
                }])
                ->get();

            // ২. পার্টনার লিস্ট
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
     * Psychology - Psychopharmacology Data
     */
    public function getPsychologyPharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'psychopharmacology')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'psychology')
                ->where('industry_type', 'psychopharmacology')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'industry_title' => 'Psychopharmacology & Psychotropics',
                    'sections' => $sections,
                    'partners' => $partners
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Psychology - Publications Data
     */
    public function getPsychologyPublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'psychology')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
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


    public function getNeuroscienceBiotech()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'biotechnology')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
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
     * Neuroscience - Psychopharmacology Data
     */
    public function getNeurosciencePharma()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychopharmacology')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
                }])
                ->get();

            $partners = IndustryPartner::where('network_type', 'neuroscience')
                ->where('industry_type', 'psychopharmacology')
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
     * Neuroscience - Publications Data
     */
    public function getNeurosciencePublications()
    {
        try {
            $sections = IndustrySections::where('network_type', 'neuroscience')
                ->where('industry_type', 'publications')
                ->with(['IndustryCategory.IndustryItem' => function ($query) {
                    $query->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
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
