<?php

namespace App\Http\Resources;

use App\Models\IndustryProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin IndustryProduct
 */
class IndustryProductResource extends JsonResource
{
    /**
     * @param  IndustryProduct  $resource
     * @param  array<int, bool>|int  $likedProductIds
     */
    public function __construct(
        $resource,
        protected array|int $likedProductIds = []
    ) {
        if (is_int($likedProductIds)) {
            $this->likedProductIds = [];
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
        if (! empty($this->likedProductIds)) {
            $isLiked = isset($this->likedProductIds[$this->id]);
        } elseif (auth('api')->check()) {
            $isLiked = $this->likes()->where('user_id', auth('api')->id())->exists();
        }

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
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
            'product_name' => $this->product_name,
            'slug' => $this->slug,
            'network_type' => $this->network_type,
            'industry_type' => $this->industry_type,
            'section_id' => $this->section_id,
            'section_name' => $this->section?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->category_name,
            'description' => $this->description,
            'short_description' => Str::limit(strip_tags((string) $this->description), 120),
            'product_url' => $this->product_url,
            'image' => $this->image,
            'image_url' => $this->image_url,
            'tags' => $this->tags,
            'poc_name' => $this->poc_name,
            'poc_email' => $this->poc_email,
            'poc_phone' => $this->poc_phone,
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
