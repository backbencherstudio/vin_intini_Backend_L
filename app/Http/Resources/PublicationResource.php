<?php

namespace App\Http\Resources;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Publication
 */
class PublicationResource extends JsonResource
{
    /**
     * @param  Publication  $resource
     * @param  array<int, bool>|int  $likedPublicationIds
     */
    public function __construct(
        $resource,
        protected array|int $likedPublicationIds = []
    ) {
        if (is_int($likedPublicationIds)) {
            $this->likedPublicationIds = [];
        }
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isLiked = false;
        if (! empty($this->likedPublicationIds)) {
            $isLiked = isset($this->likedPublicationIds[$this->id]);
        } elseif (auth('api')->check()) {
            $isLiked = $this->likes()->where('user_id', auth('api')->id())->exists();
        }

        $user = null;
        $industry = null;

        if ($this->industry) {
            $rawLogo = $this->industry->logo ?? null;
            $logo = $rawLogo ? (str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/' . ltrim($rawLogo, '/'))) : null;
            $industry = [
                'name' => $this->industry->name,
                'image' => $logo,
            ];
        } elseif ($this->creator) {
            $userName = trim(($this->creator->first_name ?? '') . ' ' . ($this->creator->last_name ?? ''));
            $userImage = $this->creator->profile_image_url
                ?? ($this->creator->profile_image ? asset('storage/' . ltrim($this->creator->profile_image, '/')) : null);
            $user = [
                'name' => $userName ?: ($this->creator->username ?? ''),
                'image' => $userImage,
            ];
        }

        return [
            'id' => $this->id,
            'publication_id' => $this->publication_id,
            'creator_id' => $this->creator_id,
            'user' => $user,
            'industry_id' => $this->industry_id,
            'industry' => $industry,
            'network_type' => $this->network_type,
            'publication_type' => $this->publication_type,
            'title' => $this->title,
            'slug' => $this->slug,
            'authors' => $this->authors,
            'abstract' => $this->abstract,
            'short_abstract' => Str::limit(strip_tags((string) $this->abstract), 150),
            'website_url' => $this->website_url,
            'attachment' => $this->attachment,
            'attachment_url' => $this->attachment_url,
            'file_extension' => $this->file_extension,
            'information_confirmed' => (bool) $this->information_confirmed,
            'status' => $this->status,
            'views_count' => (int) ($this->views_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'is_liked' => $isLiked,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
