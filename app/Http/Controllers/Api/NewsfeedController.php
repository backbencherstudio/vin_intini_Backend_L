<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\IndustryFollow;
use App\Models\Post;
use App\Models\UserFollow;
use App\Services\Advertising\NewsfeedAdInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsfeedController extends Controller
{
    public function __construct(
        protected NewsfeedAdInjector $adInjector
    ) {}

    public function newsFeed(Request $request)
    {
        $user = auth('api')->user();

        $groupIds = $user->groups()->pluck('groups.id')->toArray();
        $adminGroupIds = $user->groups()
            ->wherePivot('role', 'admin')
            ->pluck('groups.id')
            ->toArray();

        $relationships = Connection::where(function ($q) use ($user): void {
            $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->whereHas('sender', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('receiver', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        $relationshipMap = [];
        foreach ($relationships as $connection) {
            $otherUserId = $connection->sender_id == $user->id
                ? $connection->receiver_id
                : $connection->sender_id;
            $relationshipMap[$otherUserId] = $connection->status;
        }

        $connectionIds = collect($relationshipMap)
            ->filter(fn ($status): bool => $status === Connection::STATUS_ACCEPTED)
            ->keys();

        $followingIds = UserFollow::where('follower_id', $user->id)
            ->whereHas('following', fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('following_id');

        $unfollowedConnectionIds = $connectionIds->diff($followingIds);

        $followedIndustryIds = IndustryFollow::where('user_id', $user->id)
            ->pluck('industry_id')
            ->flip();

        $perPage = (int) ($request->input('per_page') ?? $request->input('limit') ?? 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $posts = Post::query()
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->with([
                'user:id,username,first_name,last_name,profile_image,title',
                'user.profile:user_id,privacy_profile_activity',
                'media',
                'groups:id,name,logo',
                'industryLink:id,post_id,industry_id', 'industryLink.industry:id,name,slug,tagline,logo',
                'likes' => function ($q) use ($user): void {
                    $q->where('user_id', $user->id);
                },
            ])
            ->where(function ($query) use ($user, $groupIds, $connectionIds, $followingIds, $unfollowedConnectionIds): void {

                $query->where('user_id', $user->id)

                    ->orWhere(function ($q) use ($connectionIds, $unfollowedConnectionIds): void {
                        $q->where('visibility', 'public')
                            ->whereNotIn('user_id', $unfollowedConnectionIds)
                            ->whereHas('user.profile', function ($qProfile) use ($connectionIds): void {
                                $qProfile->where(function ($qp) use ($connectionIds): void {
                                    $qp->where('privacy_profile_activity', 'everyone')
                                        ->orWhere(function ($qp2) use ($connectionIds): void {
                                            $qp2->where('privacy_profile_activity', 'only_connected')
                                                ->whereIn('user_profiles.user_id', $connectionIds);
                                        });
                                });
                            });
                    })

                    ->orWhere(function ($q) use ($connectionIds, $followingIds): void {
                        $q->where('visibility', 'connections')
                            ->whereIn('user_id', $connectionIds)
                            ->whereIn('user_id', $followingIds)
                            ->whereHas('user.profile', function ($qProfile): void {
                                $qProfile->where('privacy_profile_activity', '!=', 'nobody');
                            });
                    })

                    ->orWhere(function ($q) use ($groupIds, $user): void {
                        $q->where('visibility', 'groups')
                            ->whereHas('groups', function ($q2) use ($groupIds, $user): void {
                                $q2->whereIn('groups.id', $groupIds)
                                    ->whereHas('users', function ($q3) use ($user): void {
                                        $q3->where('group_users.user_id', $user->id)
                                            ->where('group_users.status', '!=', 'banned');
                                    });
                            });
                    })

                    ->orWhere(function ($q) use ($user): void {
                        $q->whereExists(function ($sub): void {
                            $sub->select(DB::raw(1))
                                ->from('post_industry')
                                ->whereColumn('post_industry.post_id', 'posts.id');
                        })
                            ->where(function ($q2) use ($user): void {
                                $q2->where('visibility', 'public')

                                    ->orWhere(function ($q3) use ($user): void {
                                        $q3->where('visibility', 'followers')
                                            ->whereExists(function ($sub) use ($user): void {
                                                $sub->select(DB::raw(1))
                                                    ->from('industry_follows')
                                                    ->join(
                                                        'post_industry',
                                                        'post_industry.industry_id',
                                                        '=',
                                                        'industry_follows.industry_id'
                                                    )
                                                    ->whereColumn('post_industry.post_id', 'posts.id')
                                                    ->where('industry_follows.user_id', $user->id);
                                            });
                                    });
                            });
                    });
            })
            ->orderByDesc('id')
            ->cursorPaginate(perPage: $perPage, cursor: $request->input('cursor'));

        $feedItems = collect($posts->items())->map(function ($post) use ($user, $relationshipMap, $adminGroupIds, $followedIndustryIds): ?array {
            if (! $post->user) {
                return null;
            }
            $canEdit = ($post->user_id === $user->id);
            $canDelete = ($post->user_id === $user->id);

            if (! $canDelete) {
                foreach ($post->groups as $group) {
                    if (in_array($group->id, $adminGroupIds)) {
                        $canDelete = true;
                        break;
                    }
                }
            }

            if ($post->user_id === $user->id) {
                $relationshipStatus = 'connected';
            } else {
                $status = $relationshipMap[$post->user_id] ?? null;
                $relationshipStatus = match ($status) {
                    Connection::STATUS_ACCEPTED => 'connected',
                    Connection::STATUS_PENDING => 'pending',
                    default => 'not_connected',
                };
            }

            $industry = $post->industryLink?->industry;
            $industryData = null;
            if ($industry) {
                $industryData = [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'tagline' => $industry->tagline,
                    'logo' => $industry->logo,
                    'is_following' => isset($followedIndustryIds[$industry->id]),
                ];
            }

            return [
                'id' => $post->id,
                'user' => $post->user,
                'description' => $post->description,
                'visibility' => $post->visibility,
                'who_can_comment' => $post->who_can_comment,
                'total_like' => $post->total_like ?? 0,
                'total_comment' => $post->total_comment ?? 0,
                'liked_by_me' => $post->likes->isNotEmpty(),
                'relationship_status' => $relationshipStatus,
                'media' => $post->media,
                'group' => $post->groups->first(),
                'industry' => $industryData,
                'created_at' => $post->created_at,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
            ];
        })->filter()->values();

        $networkType = $request->input('network_type');
        if ($networkType) {
            $rawNet = strtolower(str_replace(['_', '-'], '', (string) $networkType));
            if (str_contains($rawNet, 'neuro')) {
                $networkType = 'neuroscience';
            } elseif (str_contains($rawNet, 'psych')) {
                $networkType = 'psychology';
            }
        }

        $seenCampaignIds = array_filter(array_map(intval(...), explode(',', (string) $request->input('seen_campaign_ids', ''))));

        $finalFeed = $this->adInjector->injectAdsIntoFeed(
            $feedItems,
            $user,
            $networkType,
            excludeCampaignIds: $seenCampaignIds
        );

        $injectedCampaignIds = $finalFeed->where('is_sponsored', true)->pluck('ad_campaign_id')->filter()->values()->all();
        $mergedSeen = array_merge($seenCampaignIds, $injectedCampaignIds);
        $allSeenIds = array_values(array_reverse(array_unique(array_reverse($mergedSeen))));
        $allSeenIds = array_slice($allSeenIds, -30);

        return response()->json([
            'success' => true,
            'message' => 'Feed fetched successfully',
            'data' => $finalFeed,
            'pagination' => [
                'per_page' => $posts->perPage(),
                'next_cursor' => $posts->nextCursor()?->encode(),
                'prev_cursor' => $posts->previousCursor()?->encode(),
                'has_more_pages' => $posts->hasMorePages(),
                'seen_campaign_ids' => implode(',', $allSeenIds),
            ],
        ]);
    }

    public function singlePost($id)
    {
        $user = auth('api')->user();

        $groupIds = $user->groups()->pluck('groups.id');

        $relationships = Connection::where(function ($q) use ($user): void {
            $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->whereHas('sender', fn ($q) => $q->whereNull('deleted_at'))
            ->whereHas('receiver', fn ($q) => $q->whereNull('deleted_at'))
            ->get();

        $relationshipMap = [];

        foreach ($relationships as $connection) {

            $otherUserId = $connection->sender_id == $user->id
                ? $connection->receiver_id
                : $connection->sender_id;

            $relationshipMap[$otherUserId] = $connection->status;
        }

        $connectionIds = collect($relationshipMap)
            ->filter(fn ($status): bool => $status === Connection::STATUS_ACCEPTED)
            ->keys();

        $followingIds = UserFollow::where('follower_id', $user->id)
            ->whereHas('following', fn ($q) => $q->whereNull('deleted_at'))
            ->pluck('following_id');

        $allowedConnectionIds = $connectionIds->intersect($followingIds);

        $post = Post::with([
            'user:id,username,first_name,last_name,profile_image,title',
            'media',
            'groups:id,name,logo',
            'industryLink:id,post_id,industry_id', 'industryLink.industry:id,name,slug,tagline,logo',
            'likes' => function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            },
        ])
            ->where('id', $id)
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->where(function ($query) use (
                $user,
                $groupIds,
                $allowedConnectionIds
            ): void {

                $query->where('user_id', $user->id)

                    ->orWhere(function ($query) use (
                        $groupIds,
                        $allowedConnectionIds,
                        $user
                    ): void {
                        $query->where('visibility', 'public')

                            ->orWhere(function ($q) use ($allowedConnectionIds): void {
                                $q->where('visibility', 'connections')
                                    ->whereIn('user_id', $allowedConnectionIds);
                            })

                            ->orWhere(function ($q) use ($groupIds, $user): void {
                                $q->where('visibility', 'groups')
                                    ->whereHas('groups', function ($q2) use ($groupIds, $user): void {
                                        $q2->whereIn('groups.id', $groupIds)
                                            ->whereHas('users', function ($q3) use ($user): void {
                                                $q3->where('group_users.user_id', $user->id)
                                                    ->where('group_users.status', '!=', 'banned');
                                            });
                                    });
                            })

                            ->orWhere(function ($q) use ($user): void {
                                $q->whereExists(function ($sub): void {
                                    $sub->select(DB::raw(1))
                                        ->from('post_industry')
                                        ->whereColumn('post_industry.post_id', 'posts.id');
                                })
                                    ->where(function ($q2) use ($user): void {
                                        $q2->where('visibility', 'public')

                                            ->orWhere(function ($q3) use ($user): void {
                                                $q3->where('visibility', 'followers')
                                                    ->whereExists(function ($sub) use ($user): void {
                                                        $sub->select(DB::raw(1))
                                                            ->from('industry_follows')
                                                            ->join(
                                                                'post_industry',
                                                                'post_industry.industry_id',
                                                                '=',
                                                                'industry_follows.industry_id'
                                                            )
                                                            ->whereColumn('post_industry.post_id', 'posts.id')
                                                            ->where('industry_follows.user_id', $user->id);
                                                    });
                                            });
                                    });
                            });
                    });
            })
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or access denied',
            ], 404);
        }

        $canEdit = $post->user_id === $user->id;
        $canDelete = $post->user_id === $user->id;

        foreach ($post->groups as $group) {

            $isGroupAdmin = $group->users()
                ->wherePivot('role', 'admin')
                ->wherePivot('user_id', $user->id)
                ->exists();

            if ($isGroupAdmin && $post->user_id !== $user->id) {
                $canDelete = true;
                break;
            }
        }

        if ($post->user_id === $user->id) {

            $relationshipStatus = 'connected';
        } else {

            $status = $relationshipMap[$post->user_id] ?? null;

            $relationshipStatus = match ($status) {
                Connection::STATUS_ACCEPTED => 'connected',
                Connection::STATUS_PENDING => 'pending',
                default => 'not_connected',
            };
        }

        $industry = $post->industryLink?->industry;
        $industryData = null;
        if ($industry) {
            $industryData = [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'tagline' => $industry->tagline,
                'logo' => $industry->logo,
                'is_following' => IndustryFollow::where('industry_id', $industry->id)
                    ->where('user_id', $user->id)
                    ->exists(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'id' => $post->id,
                'user' => $post->user,
                'description' => $post->description,
                'visibility' => $post->visibility,
                'who_can_comment' => $post->who_can_comment,
                'total_like' => $post->total_like ?? 0,
                'total_comment' => $post->total_comment ?? 0,
                'liked_by_me' => $post->likes->isNotEmpty(),
                'relationship_status' => $relationshipStatus,
                'media' => $post->media,
                'group' => $post->groups->first(),
                'industry' => $industryData,
                'created_at' => $post->created_at,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
            ],
        ]);
    }
}
