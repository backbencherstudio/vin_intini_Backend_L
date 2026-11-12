<?php

namespace App\Services\Industry;

use App\Enums\AdCampaignStatus;
use App\Models\Industry;
use App\Models\IndustryProduct;
use App\Models\User;
use App\Notifications\ProductRejectedNotification;
use App\Notifications\ProductStatusUpdatedNotification;
use App\Services\Advertising\BoostSettingService;
use App\Services\Media\OptimizedImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndustryProductService
{
    public function __construct(
        protected OptimizedImageUploadService $imageUploadService,
        protected BoostSettingService $boostSettingService
    ) {}

    /**
     * Create a new industry product, properly staging and protecting files.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProduct(User $user, Industry $industry, array $data, ?UploadedFile $imageFile = null): IndustryProduct
    {
        $imagePath = null;
        if ($imageFile instanceof UploadedFile) {
            $imagePath = $this->imageUploadService->store($imageFile, 'products');
        }

        $baseSlug = Str::slug($data['product_name']);
        $uniqueSlug = $baseSlug.'-'.Str::lower(Str::random(6));

        $newReleaseHours = $this->boostSettingService->getNewReleaseHours();

        try {
            $product = IndustryProduct::create([
                'creator_id' => $user->id,
                'industry_id' => $industry->id,
                'network_type' => $data['network_type'],
                'industry_type' => $data['industry_type'],
                'section_id' => $data['section_id'],
                'category_id' => $data['category_id'],
                'product_name' => $data['product_name'],
                'slug' => $uniqueSlug,
                'description' => $data['description'],
                'product_url' => $data['product_url'] ?? null,
                'image' => $imagePath,
                'tags' => $this->formatTags($data['tags'] ?? []),
                'poc_name' => $data['poc_name'] ?? null,
                'poc_email' => $data['poc_email'] ?? null,
                'poc_phone' => $data['poc_phone'] ?? null,
                'information_confirmed' => (bool) ($data['information_confirmed'] ?? true),
                'status' => $data['status'] ?? 'active',
                'is_new_release' => $newReleaseHours > 0,
                'new_release_expires_at' => $newReleaseHours > 0 ? now()->addHours($newReleaseHours) : null,
                'views_count' => 0,
                'likes_count' => 0,
            ]);
        } catch (\Throwable $e) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }

        return $product->load(['creator', 'industry', 'section', 'category']);
    }

    /**
     * Update an industry product with safe two-phase file replacement.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProduct(
        IndustryProduct $product,
        array $data,
        ?UploadedFile $newImageFile = null,
        ?User $admin = null
    ): IndustryProduct {
        $previousStatus = (string) $product->status;
        $newImagePath = null;
        $oldImagePath = $product->image;

        if ($newImageFile instanceof UploadedFile) {
            $newImagePath = $this->imageUploadService->store($newImageFile, 'products');
        }

        try {
            if ($newImagePath) {
                $product->image = $newImagePath;
            }

            if (isset($data['product_name']) && $data['product_name'] !== $product->product_name) {
                $product->product_name = $data['product_name'];
                $product->slug = Str::slug($data['product_name']).'-'.Str::lower(Str::random(6));
            }

            if (isset($data['industry_id'])) {
                $product->industry_id = $data['industry_id'];
            }
            if (isset($data['network_type'])) {
                $product->network_type = $data['network_type'];
            }
            if (isset($data['industry_type'])) {
                $product->industry_type = $data['industry_type'];
            }
            if (isset($data['section_id'])) {
                $product->section_id = $data['section_id'];
            }
            if (isset($data['category_id'])) {
                $product->category_id = $data['category_id'];
            }
            if (isset($data['description'])) {
                $product->description = $data['description'];
            }
            if (array_key_exists('product_url', $data)) {
                $product->product_url = $data['product_url'];
            }
            if (isset($data['tags'])) {
                $product->tags = $this->formatTags($data['tags']);
            }
            if (array_key_exists('poc_name', $data)) {
                $product->poc_name = $data['poc_name'];
            }
            if (array_key_exists('poc_email', $data)) {
                $product->poc_email = $data['poc_email'];
            }
            if (array_key_exists('poc_phone', $data)) {
                $product->poc_phone = $data['poc_phone'];
            }
            if (isset($data['information_confirmed'])) {
                $product->information_confirmed = (bool) $data['information_confirmed'];
            }
            if (isset($data['status'])) {
                $product->status = $data['status'];
                if ($data['status'] !== 'rejected') {
                    $this->clearRejectionMetadata($product);
                }
            }

            $product->save();

            // Commit succeeded: clean up previous image if replaced
            if ($newImagePath && $oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }
        } catch (\Throwable $e) {
            // Rollback: clean up the newly created image to avoid orphans
            if ($newImagePath && Storage::disk('public')->exists($newImagePath)) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $e;
        }

        $product->load(['creator', 'industry.creator', 'section', 'category']);

        $this->notifyStatusRestoredIfNeeded($product, $previousStatus, (string) $product->status, $admin);

        return $product;
    }

    /**
     * Update product status as Admin and notify the industry owner when restoring from rejected.
     */
    public function updateProductStatus(IndustryProduct $product, string $status, ?User $admin = null): IndustryProduct
    {
        $previousStatus = (string) $product->status;

        $product->status = $status;
        if ($status !== 'rejected') {
            $this->clearRejectionMetadata($product);
        }
        $product->save();

        $product->load(['creator', 'industry.creator', 'section', 'category', 'activeCampaign', 'latestCampaign']);

        $this->notifyStatusRestoredIfNeeded($product, $previousStatus, (string) $product->status, $admin);

        return $product;
    }

    /**
     * Reject an illegal or non-compliant product, hide it platform-wide,
     * cancel any running boost campaigns, and notify the industry owner with the reason.
     */
    public function rejectProduct(IndustryProduct $product, string $reason, ?User $admin = null): IndustryProduct
    {
        $cleanReason = trim($reason);

        $product = DB::transaction(function () use ($product, $cleanReason, $admin): IndustryProduct {
            $product->status = 'rejected';
            $product->rejection_reason = $cleanReason;
            $product->rejected_at = now();
            $product->rejected_by = $admin?->id;
            $product->is_boosted = false;
            $product->boost_expires_at = null;
            $product->is_new_release = false;
            $product->new_release_expires_at = null;
            $product->save();

            $product->campaigns()
                ->whereIn('status', [
                    AdCampaignStatus::ACTIVE->value,
                    AdCampaignStatus::PAUSED->value,
                    AdCampaignStatus::PENDING->value,
                ])
                ->update(['status' => AdCampaignStatus::CANCELLED->value]);

            return $product->load(['creator', 'industry.creator', 'section', 'category', 'activeCampaign', 'latestCampaign']);
        });

        $this->notifyProductRecipients($product, new ProductRejectedNotification($product, $cleanReason, $admin));

        return $product;
    }

    protected function notifyStatusRestoredIfNeeded(
        IndustryProduct $product,
        string $previousStatus,
        string $newStatus,
        ?User $admin = null
    ): void {
        if ($previousStatus !== 'rejected' || $newStatus === 'rejected') {
            return;
        }

        $this->notifyProductRecipients(
            $product,
            new ProductStatusUpdatedNotification($product, $previousStatus, $newStatus, $admin)
        );
    }

    protected function notifyProductRecipients(IndustryProduct $product, Notification $notification): void
    {
        $product->loadMissing(['creator', 'industry.creator']);

        $recipients = collect([$product->industry?->creator, $product->creator])
            ->filter(fn ($recipient): bool => $recipient instanceof User)
            ->unique('id')
            ->values();

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    public function clearRejectionMetadata(IndustryProduct $product): void
    {
        $product->rejection_reason = null;
        $product->rejected_at = null;
        $product->rejected_by = null;
    }

    /**
     * Format tags helper.
     *
     * @return array<int, string>
     */
    public function formatTags(mixed $tags): array
    {
        if (empty($tags)) {
            return [];
        }

        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $tags = $decoded;
            } else {
                $tags = explode(',', $tags);
            }
        }

        if (! is_array($tags)) {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), $tags)));
    }
}
