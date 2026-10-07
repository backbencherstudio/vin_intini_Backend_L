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
        $logo = null;
        if ($this->industry) {
            $rawLogo = $this->industry->logo ?? null;
            if ($rawLogo) {
                $logo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/'.ltrim($rawLogo, '/'));
            }
        }

        $isLiked = false;
        if (! empty($this->likedPublicationIds)) {
            $isLiked = isset($this->likedPublicationIds[$this->id]);
        } elseif (auth('api')->check()) {
            $isLiked = $this->likes()->where('user_id', auth('api')->id())->exists();
        }

        return [
            'id' => $this->id,
            'publication_id' => $this->publication_id,
            'creator_id' => $this->creator_id,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => trim(($this->creator->first_name ?? '').' '.($this->creator->last_name ?? '')),
                'username' => $this->creator->username,
                'profile_image' => $this->creator->profile_image_url ?? $this->creator->profile_image,
            ] : null,
            'industry_id' => $this->industry_id,
            'industry' => $this->industry ? [
                'id' => $this->industry->id,
                'name' => $this->industry->name,
                'logo' => $logo,
            ] : null,
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
