<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndustryCategory;
use App\Models\IndustryPartner;
use App\Models\IndustrySections;

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
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
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
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
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
                ->with(['IndustryCategory' => function ($query) {
                    $query->with(['IndustryItem' => function ($q) {
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
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
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
                ->with(['IndustryCategory' => function ($query) {
                    $query->where('category_name', '!=', 'All')
                        ->with(['IndustryItem' => function ($q) {
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
                ->with(['IndustryCategory' => function ($query) {
                    $query->with(['IndustryItem' => function ($q) {
                        $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
                    }]);
                }])->get();

            return response()->json(['success' => true, 'data' => ['industry_title' => 'Neuroscience Research Publications', 'sections' => $sections]], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ============================================================
       SECTION 2: PARTNERS DATA (Separate APIs)
       ============================================================ */

    // --- Psychology Network Partners ---
    public function getPsychologyBiotechPartners()
    {
        return $this->getPartners('psychology', 'biotechnology');
    }

    public function getPsychologyPharmaPartners()
    {
        return $this->getPartners('psychology', 'psychotropics');
    }

    public function getPsychologyPubPartners()
    {
        return $this->getPartners('psychology', 'publications');
    }

    // --- Neuroscience Network Partners ---
    public function getNeuroscienceBiotechPartners()
    {
        return $this->getPartners('neuroscience', 'biotechnology');
    }

    public function getNeurosciencePharmaPartners()
    {
        return $this->getPartners('neuroscience', 'psychotropics');
    }

    public function getNeurosciencePubPartners()
    {
        return $this->getPartners('neuroscience', 'publications');
    }

    // Common Partner Fetcher
    private function getPartners($network, $industry)
    {
        try {
            $partners = IndustryPartner::where('network_type', $network)
                ->where('industry_type', $industry)
                ->select('id', 'partner_name', 'partner_tag', 'partner_desc', 'partner_link', 'partner_logo')
                ->get()
                ->map(function ($partner) {
                    $partner->partner_logo = $partner->partner_logo
                        ? asset('storage/'.$partner->partner_logo)
                        : null;

                    return $partner;
                });

            return response()->json(['success' => true, 'partners' => $partners], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // public function getPsychologyBiotech()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'psychology')
    //             ->where('industry_type', 'biotechnology')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 // 'All' remove "all" category
    //                 $query->where('category_name', '!=', 'All')
    //                     ->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'psychology')
    //             ->where('industry_type', 'biotechnology')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Biotechnology Industry',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Psychology - psychotropics
    //  */
    // public function getPsychologyPharma()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'psychology')
    //             ->where('industry_type', 'psychotropics')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 $query->where('category_name', '!=', 'All') // 'All' remove "all" category
    //                     ->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'psychology')
    //             ->where('industry_type', 'psychotropics')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Psychotropics',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Psychology - Publications
    //  */
    // public function getPsychologyPublications()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'psychology')
    //             ->where('industry_type', 'publications')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 $query->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'psychology')
    //             ->where('industry_type', 'publications')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Newly Published Articles',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Neuroscience - Biotechnology
    //  */
    // public function getNeuroscienceBiotech()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'biotechnology')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 $query->where('category_name', '!=', 'All')
    //                     ->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'description', 'image', 'link');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'biotechnology')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Neuroscience Biotechnology',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Neuroscience - psychotropics
    //  */
    // public function getNeurosciencePharma()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'psychotropics')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 $query->where('category_name', '!=', 'All')
    //                     ->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'sub_title', 'indication', 'moa', 'description', 'link', 'image');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'psychotropics')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Neuroscience Pharma & Psychotropics',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }

    // /**
    //  * Neuroscience - Publications
    //  */
    // public function getNeurosciencePublications()
    // {
    //     try {
    //         $sections = IndustrySections::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'publications')
    //             ->with(['IndustryCategory' => function ($query) {
    //                 $query->with(['IndustryItem' => function ($q) {
    //                         $q->select('id', 'category_id', 'title', 'tag', 'pub_date', 'extra_tag', 'description', 'link');
    //                     }]);
    //             }])
    //             ->get();

    //         $partners = IndustryPartner::where('network_type', 'neuroscience')
    //             ->where('industry_type', 'publications')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'industry_title' => 'Neuroscience Research Publications',
    //                 'sections' => $sections,
    //                 'partners' => $partners
    //             ]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    //     }
    // }
}
