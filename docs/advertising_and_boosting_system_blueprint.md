# Mind Unite — Universal Advertising & Boosting Engine (Enterprise Architecture)
## Unified Polymorphic Ad Platform Specification & Future-Proof Implementation Guide

---

## 1. Executive Architectural Vision: The Universal Ad Engine

### 1.1 The Core Problem with Single-Purpose Designs
Many platforms build ad features in silos (e.g., creating a `product_boosts` table today, only to realize six months later that they need a `job_boosts` table, a `publication_promotions` table, and a `post_sponsorships` table). This leads to:
* Fragmented database schemas and redundant tables.
* Duplicate billing, checkout, and webhook logic.
* Incompatible analytics and fragmented reporting dashboards.
* Massive technical debt and risky database refactoring on live production data.

### 1.2 The Senior Solution: Polymorphic Entity Architecture (`Boostable`)
To ensure Mind Unite scales seamlessly without ever requiring database restructuring, we adopt an **Enterprise Polymorphic Ad Architecture** (modeled after Twitter Ads, LinkedIn Campaign Manager, and Meta Ads Manager).

The entire advertising platform operates on an abstract, reusable contract: **`BoostableInterface`** and a universal **`ad_campaigns`** engine.

```
                         MIND UNITE UNIVERSAL ADS PLATFORM
                                 
                               ┌───────────────────┐
                               │  System Admin     │
                               │  Package & Rates  │
                               └─────────┬─────────┘
                                         │
                                         ▼
                               ┌───────────────────┐
                               │   boost_packages  │
                               │ (Target Entity:   │
                               │  product/job/pub) │
                               └─────────┬─────────┘
                                         │
                                         ▼
                     ┌───────────────────────────────────────┐
                     │          ad_campaigns (Core)          │
                     │  boostable_type  |  boostable_id      │
                     │  advertiser_id   |  industry_id       │
                     │  placement       |  network_type      │
                     └───────────────────┬───────────────────┘
                                         │
        ┌───────────────────┬────────────┴───────┬───────────────────┐
        ▼                   ▼                    ▼                   ▼
┌───────────────┐   ┌───────────────┐   ┌─────────────────┐   ┌───────────────┐
│IndustryProduct│   │IndustryJobPost│   │   Publication   │   │     Post      │
│ (Phase 1 MVP) │   │ (Phase 2 Job) │   │(Phase 3 Article)│   │(Phase 4 Social)│
└───────┬───────┘   └───────┬───────┘   └────────┬────────┘   └───────┬───────┘
        │                   │                    │                    │
        └───────────────────┼────────────────────┴────────────────────┘
                            ▼
        ┌────────────────────────────────────────────────────────┐
        │       UNIVERSAL FAIR-PLAY & MULTI-FEED ENGINE          │
        │                                                        │
        │  • Main Newsfeed Ingestion (/api/newsfeed)             │
        │  • Marketplace Placement (/api/industry/product-feed)  │
        │  • Jobs Feed Placement (/api/jobsfeed)                 │
        │  • Publication Feed Placement (/api/publication/feed)  │
        └────────────────────────────────────────────────────────┘
```

### 1.3 Key Benefits of this Architecture
1. **Zero Schema Changes for Future Features:** Adding job boosts, publication boosts, or timeline post boosts in the future requires **zero database migrations** to the billing, campaign, impression, or click tables.
2. **Entity-Specific Pricing:** Admin can set separate pricing for different targets (e.g., $19.99 for Product Boost, $49.99 for Urgent Job Boost, $9.99 for Publication Boost).
3. **Cross-Feed Omnichannel Placements:** A boosted product can appear in both the Marketplace AND the Social Newsfeed; a boosted job can appear in the Jobs Feed AND the Social Newsfeed.
4. **100% Platform Revenue:** Zero payouts, zero seller commissions. 100% of all ad payments land directly in Mind Unite's Stripe account.
5. **Dual Selection Flexibility (Preset Packages & Custom Duration Slider):** Advertisers can pick quick preset packages (7, 14, 30 days) OR choose a custom duration (e.g. 5, 9, 21 days) with dynamic price calculation ($X/day) and minimum duration safeguards.

---

## 2. Universal Database Schema & Additive Migrations

All migrations are **100% non-destructive and backward compatible**.

### 2.1 Migration 1: Universal `boost_packages` Table
Allows Admin to configure pricing and durations for any entity type on Mind Unite.

```php
// database/migrations/2026_10_14_000001_create_universal_boost_packages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('boost_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                     // e.g. '7-Day Product Boost', 'Featured Job Urgent'
            $table->enum('target_entity', [                             // The entity this package applies to
                'all', 
                'product', 
                'job', 
                'publication', 
                'post'
            ])->default('product')->index();

            $table->integer('duration_days');                           // 7, 14, 30
            $table->decimal('price', 8, 2);                             // 19.99, 49.99
            $table->string('estimated_impressions')->nullable();        // '5,000 – 10,000 Views'
            $table->string('badge')->nullable();                        // 'Popular', 'Urgent', 'Top Reach'
            $table->text('description')->nullable();
            $table->json('features')->nullable();                       // Highlights list
            $table->unsignedInteger('priority_weight')->default(1);     // Tier priority for Fair-Play algorithm
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->index(['target_entity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boost_packages');
    }
};
```

### 2.2 Migration 2: Polymorphic `ad_campaigns` Table
The single source of truth for every ad and boost on the platform.

```php
// database/migrations/2026_10_14_000002_create_polymorphic_ad_campaigns_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();

            // Polymorphic target (Product, Job, Publication, or Post)
            $table->string('boostable_type')->index();                  // App\Models\IndustryProduct, etc.
            $table->unsignedBigInteger('boostable_id')->index();

            // Ownership (Preserved under industry even if creator user account is deleted)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained('industries')->nullOnDelete();
            $table->foreignId('boost_package_id')->nullable()->constrained('boost_packages')->nullOnDelete();

            // Targeting & Placements
            $table->enum('ad_type', ['product', 'job', 'publication', 'post'])->default('product')->index();
            $table->string('network_type', 50)->default('both')->index(); // 'psychology', 'neuroscience', 'both'
            $table->json('placements')->nullable();                      // ["marketplace", "newsfeed", "search"]

            // Financial & Payment Audit
            $table->decimal('amount_paid', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_checkout_session_id')->nullable()->index();

            // Lifecycle
            $table->integer('duration_days');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('paused_at')->nullable()->index();
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled'])->default('pending')->index();

            // Aggregated Metrics
            $table->unsignedBigInteger('total_impressions')->default(0);
            $table->unsignedBigInteger('total_clicks')->default(0);

            $table->timestamps();

            // Composite indexes for sub-5ms lookup
            $table->index(['boostable_type', 'boostable_id', 'status'], 'idx_campaign_boostable_status');
            $table->index(['ad_type', 'network_type', 'status', 'ends_at'], 'idx_active_ad_feed_lookup');
            $table->index(['industry_id', 'status'], 'idx_industry_campaigns');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
```

### 2.3 Migration 3: Additive Boosting Fields on Target Entity Tables
To guarantee high-speed feed rendering without expensive polymorphic JOINs during pagination, each boostable table receives lightweight denormalized flags.

#### Table 1: `industry_products` (Phase 1)
```php
// database/migrations/2026_10_14_000003_add_boost_fields_to_industry_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('industry_products', function (Blueprint $table) {
            $table->boolean('is_new_release')->default(false)->after('status')->index();
            $table->timestamp('new_release_expires_at')->nullable()->after('is_new_release')->index();

            $table->boolean('is_boosted')->default(false)->after('new_release_expires_at')->index();
            $table->timestamp('boosted_at')->nullable()->after('is_boosted');
            $table->timestamp('boost_expires_at')->nullable()->after('boosted_at')->index();
            $table->unsignedInteger('boost_score')->default(0)->after('boost_expires_at');

            $table->unsignedBigInteger('impressions_count')->default(0)->after('views_count');
            $table->unsignedBigInteger('clicks_count')->default(0)->after('impressions_count');

            $table->index(['network_type', 'status', 'is_boosted', 'boost_score', 'id'], 'idx_products_boost_sort');
        });
    }

    public function down(): void
    {
        Schema::table('industry_products', function (Blueprint $table) {
            $table->dropIndex('idx_products_boost_sort');
            $table->dropColumn([
                'is_new_release', 'new_release_expires_at',
                'is_boosted', 'boosted_at', 'boost_expires_at', 'boost_score',
                'impressions_count', 'clicks_count'
            ]);
        });
    }
};
```

*(Note: For future phases, identical lightweight additive migrations will be applied to `industry_job_posts`, `publications`, and `posts`).*

### 2.4 Migration 4: Universal `ad_impressions` Table
IAB-compliant (50% area / 1 second) impression verification with daily session debouncing.

```php
// database/migrations/2026_10_14_000004_create_universal_ad_impressions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('boostable_type', 100);
            $table->unsignedBigInteger('boostable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('placement', 50); // 'marketplace', 'newsfeed', 'jobs_feed', 'search'
            $table->string('ip_address', 45)->nullable();
            $table->date('viewed_date')->index();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ad_campaign_id', 'user_id', 'viewed_date'], 'idx_impression_unique_check');
            $table->index(['boostable_type', 'boostable_id', 'created_at'], 'idx_entity_impressions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_impressions');
    }
};
```

### 2.5 Migration 5: Universal `ad_clicks` Table
Fraud-filtered tracking for clicks redirecting to destination URLs or details modals.

```php
// database/migrations/2026_10_14_000005_create_universal_ad_clicks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ad_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_campaign_id')->constrained('ad_campaigns')->cascadeOnDelete();
            $table->string('boostable_type', 100);
            $table->unsignedBigInteger('boostable_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('placement', 50);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ad_campaign_id', 'created_at']);
            $table->index(['boostable_type', 'boostable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_clicks');
    }
};
```

---

## 3. Polymorphic PHP Domain Architecture (SOLID & Octane-Safe)

### 3.1 The Contract: `BoostableInterface`
Every model that can be advertised implements this contract:

```php
namespace App\Contracts;

use App\Models\AdCampaign;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface BoostableInterface
{
    public function campaigns(): MorphMany;
    public function activeCampaign(): MorphOne;
    public function isBoosted(): bool;
    public function getAdTitle(): string;
    public function getAdDescription(): ?string;
    public function getAdImageUrl(): ?string;
    public function getAdDestinationUrl(): ?string;
    public function getAdNetworkType(): string;
    public function getAdType(): string; // 'product', 'job', 'publication', 'post'
}
```

### 3.2 The Reusable Trait: `HasBoostableCampaigns`
Drop-in trait for `IndustryProduct`, `Publication`, `IndustryJobPost`, and `Post`:

```php
namespace App\Traits;

use App\Models\AdCampaign;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasBoostableCampaigns
{
    public function campaigns(): MorphMany
    {
        return $this->morphMany(AdCampaign::class, 'boostable');
    }

    public function activeCampaign(): MorphOne
    {
        return $this->morphOne(AdCampaign::class, 'boostable')
            ->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    public function isBoosted(): bool
    {
        return (bool) $this->is_boosted && ($this->boost_expires_at === null || $this->boost_expires_at->isFuture());
    }

    public function scopeBoosted(Builder $query): Builder
    {
        return $query->where('is_boosted', true)
            ->where('boost_expires_at', '>', now());
    }
}
```

### 3.3 Dual Selection Engine: Preset Tiers vs. Custom Duration Slider
To cater to both quick-decision buyers and precise campaign managers (like Facebook/Instagram Boost), the platform supports **Dual Package Selection**:

#### Mode A: Preset Packages (Admin Defined)
* Quick 1-click selection: **7-Day Starter ($19.99)**, **14-Day Growth ($34.99)**, **30-Day Pro ($69.99)**.
* Stored in `ad_campaigns`: `boost_package_id = {package_id}`, `duration_days = {package->duration_days}`.

#### Mode B: Custom Duration Slider (Advertiser Controlled)
* Industry user toggles to *"Custom Duration"* and selects any duration using a slider or input (e.g., 5 days, 10 days, 21 days).
* **Constraints:** Minimum duration = `3 days`, Maximum duration = `60 days`.
* **Dynamic Pricing Formula:**
  $$\text{Total Price} = \text{Custom Days} \times \text{Admin Daily Base Rate (\$}3.00\text{/day)}$$
  *(Example: 5 days = $15.00, 10 days = $30.00)*.
* **Estimated Impressions Calculation:** Dynamically projected as $\text{Days} \times (700 \text{ to } 1,400 \text{ impressions/day})$.
* **Database Integration:** Seamless zero-schema-change storage in `ad_campaigns`:
  - `boost_package_id = null`
  - `duration_days = {custom_days}`
  - `amount_paid = {custom_days * daily_rate}`
  - `ends_at = now()->addDays(custom_days)`

### 3.4 Campaign Pause & Resume Engine (The Clock-Freeze Mechanism)
Advertisers frequently need to temporarily halt ads (e.g. out of inventory, website maintenance, or seasonal breaks) without forfeiting their paid advertising days.

```
[ Active Boost: 5 Days Remaining ]
                │
                │ User clicks "Pause"
                ▼
[ Status: PAUSED, paused_at = NOW() ]
[ Product hidden from Feeds ] ── (Clock Freezes ❄️)
                │
                │ 4 Days Pass
                ▼
[ User clicks "Resume" ]
                │
                ▼
[ Calculate Paused Time: 4 Days ]
[ Auto-Extend ends_at by +4 Days ]
[ Status: ACTIVE, Product restored to Feeds ] ── (Full 5 Days Remaining Restored! 🎉)
```

#### Technical Operation:
1. **Pause Action (`POST /api/industry/advertisements/campaigns/{id}/pause`):**
   * Verifies campaign is currently `active`.
   * Sets `ad_campaigns.status = 'paused'`, `ad_campaigns.paused_at = now()`.
   * Immediately updates target product: `industry_products.is_boosted = false` (ad immediately disappears from feed rotations).
2. **Resume Action (`POST /api/industry/advertisements/campaigns/{id}/resume`):**
   * Verifies campaign is currently `paused`.
   * Computes paused duration:
     ```php
     $pausedDurationInSeconds = now()->diffInSeconds($campaign->paused_at);
     $campaign->ends_at = $campaign->ends_at->addSeconds($pausedDurationInSeconds);
     $campaign->update([
         'status' => 'active',
         'paused_at' => null,
     ]);
     $campaign->boostable->update(['is_boosted' => true]);
     ```
   * Result: **Zero lost time for the advertiser.** The exact remaining hours/days are completely preserved.
3. **Anti-Abuse Safeguard:**
   * A campaign may remain paused for a maximum of **30 consecutive days**.
   * An hourly scheduled worker (`ExpireAdCampaignsCommand`) flags campaigns paused for >30 days and notifies the owner before auto-completing the campaign.

---

## 4. Multi-Feed Placement Matrix & Fair-Play Engine

### 4.1 Omnichannel Ad Placement Matrix

| Entity Type | Marketplace Feed (`/api/industry/product-feed`) | Social Newsfeed (`/api/newsfeed`) | Jobs Feed (`/api/jobsfeed`) | Publications Feed (`/api/publication/feed`) |
| :--- | :---: | :---: | :---: | :---: |
| **Product** (Phase 1) | ⭐ **Pinned Top Section** | ⭐ **Sponsored Card (Every 5-7 posts)** | — | — |
| **Publication** (Phase 2) | — | ⭐ **Sponsored Article Card** | — | ⭐ **Pinned Top Section** |
| **Job Post** (Phase 3) | — | ⭐ **Sponsored Hiring Card** | ⭐ **Pinned Featured Top** | — |
| **Post** (Phase 4) | — | ⭐ **Promoted Post** | — | — |

---

### 4.2 The Fair-Play Rotation Algorithm (`FairPlayAdRotationService`)

When 50+ ads are simultaneously active, this service prevents any single advertiser from monopolizing the top position:

```php
namespace App\Services\Advertising;

use App\Models\AdCampaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FairPlayAdRotationService
{
    /**
     * Pick optimal active ad campaign using Impression-Balancing Round-Robin.
     */
    public function resolveAdForFeed(
        string $adType, 
        string $networkType, 
        string $placement, 
        ?int $excludeCampaignId = null
    ): ?AdCampaign {
        $cacheKey = "active_campaigns_{$adType}_{$networkType}_{$placement}";

        // Retrieve active campaigns (cached for 60s in Octane memory)
        $campaigns = Cache::remember($cacheKey, 60, function () use ($adType, $networkType, $placement) {
            return AdCampaign::query()
                ->where('status', 'active')
                ->where('ad_type', $adType)
                ->where('ends_at', '>', now())
                ->where(function ($q) use ($networkType) {
                    $q->where('network_type', $networkType)
                      ->orWhere('network_type', 'both');
                })
                ->with(['boostable', 'boostPackage'])
                ->get();
        });

        if ($campaigns->isEmpty()) {
            return null;
        }

        // Weighted Scoring: prioritize campaigns that have delivered fewer impressions today
        return $campaigns
            ->filter(fn ($c) => $c->id !== $excludeCampaignId)
            ->sortBy(function (AdCampaign $campaign) {
                $targetDailyQuota = max(($campaign->boostPackage?->priority_weight ?? 1) * 1000, 1);
                $deliveredRatio = $campaign->total_impressions / $targetDailyQuota;
                
                // Lower delivered ratio = higher priority, plus slight randomness (jitter)
                return $deliveredRatio + (random_int(0, 100) / 10000);
            })
            ->first();
    }
}
```

---

## 5. Universal Newsfeed Injection Pipeline

In [NewsfeedController.php](file:///c:/Users/niazs/Desktop/Projects/vin_intini_Backend_L/app/Http/Controllers/Api/NewsfeedController.php#L136), the feed stream seamlessly weaves sponsored cards into the cursor-paginated timeline:

```php
// App\Services\Advertising\NewsfeedAdInjector.php

namespace App\Services\Advertising;

use App\Models\User;
use Illuminate\Support\Collection;

class NewsfeedAdInjector
{
    public function __construct(
        protected FairPlayAdRotationService $rotationService
    ) {}

    public function injectAdsIntoFeed(Collection $posts, User $user, string $networkType): Collection
    {
        if ($posts->count() < 4) {
            return $posts;
        }

        // 1. Fetch best product ad
        $productAd = $this->rotationService->resolveAdForFeed('product', $networkType, 'newsfeed');

        // 2. Insert into slot 4
        if ($productAd && $productAd->boostable) {
            $adItem = [
                'id' => 'ad_' . $productAd->id,
                'is_sponsored' => true,
                'ad_campaign_id' => $productAd->id,
                'ad_type' => 'product',
                'title' => $productAd->boostable->product_name,
                'description' => $productAd->boostable->description,
                'image' => $productAd->boostable->image,
                'price' => $productAd->boostable->price ?? null,
                'destination_url' => $productAd->boostable->product_url,
                'company' => [
                    'id' => $productAd->industry_id,
                    'name' => $productAd->industry?->name,
                    'logo' => $productAd->industry?->logo,
                ],
                'cta_text' => 'View Product',
            ];

            $posts->splice(3, 0, [$adItem]);
        }

        return $posts;
    }
}
```

---

## 6. Complete API Specifications

### 6.1 Admin Management Endpoints
* `GET /api/admin/boost-packages` — List all packages (filterable by `target_entity: product|job|publication|post`).
* `POST /api/admin/boost-packages/create` — Create a package with pricing, entity type, and estimated impressions.
* `PATCH /api/admin/boost-packages/{id}` — Edit package properties.
* `PATCH /api/admin/boost-packages/{id}/status` — Toggle status (`active`/`inactive`).
* `GET /api/admin/advertisements/overview` — Platform-wide ad revenue metrics and performance graph.

### 6.2 Industry Advertiser Endpoints
* `GET /api/boost-packages?target_entity=product` — Active packages available for selection in the boost modal.
* `POST /api/industry/advertisements/boost-checkout` — Initiates Stripe Checkout session for either a Preset Package OR a Custom Duration:
  ```json
  // Option A: Preset Package
  {
    "boostable_type": "product",
    "boostable_id": 42,
    "boost_package_id": 2
  }

  // Option B: Custom Duration Slider
  {
    "boostable_type": "product",
    "boostable_id": 42,
    "custom_days": 10
  }
  ```
* `GET /api/industry/advertisements/campaigns` — All running and past campaigns with live impressions, clicks, CTR, and time remaining.
* `POST /api/industry/advertisements/campaigns/{id}/pause` — Temporarily pauses an active campaign (freezes clock, hides from feeds).
* `POST /api/industry/advertisements/campaigns/{id}/resume` — Resumes a paused campaign (restores full remaining duration, restores to feeds).

### 6.3 Universal Tracking Endpoints
* `POST /api/advertisements/impressions` — Batch ingestion for verified IAB 50%/1s viewable impressions:
  ```json
  {
    "campaign_ids": [12, 18],
    "placement": "newsfeed"
  }
  ```
* `POST /api/advertisements/{campaign_id}/click` — Records valid outbound click and returns destination URL.

---

## 7. Phased Implementation Roadmap

```
PHASE 1: Core Foundation & Product Boost (Current Scope)
├── 1. Run 5 Universal Migrations (boost_packages, ad_campaigns, ad_impressions, ad_clicks, products alter)
├── 2. Implement BoostableInterface & HasBoostableCampaigns Trait on IndustryProduct
├── 3. Seed initial 7-Day ($19.99), 14-Day ($34.99), 30-Day ($69.99) packages
├── 4. 24h Free New Release initialization in IndustryProductController@store
├── 5. Stripe Boost Checkout & Webhook Handler
├── 6. Fair-Play Rotation in Product Marketplace Feed & Newsfeed Injection
└── 7. Batch IAB Impression & Click Tracking

PHASE 2: Publication Boosting (Zero DB Changes Required)
├── 1. Add HasBoostableCampaigns to Publication model
├── 2. Admin seeds "7-Day Publication Spotlight ($9.99)" package
└── 3. Pinned placement in /api/publication/feed & Newsfeed Injection

PHASE 3: Job Post Boosting ("Featured Urgent Job")
├── 1. Add HasBoostableCampaigns to IndustryJobPost model
├── 2. Admin seeds "14-Day Urgent Hiring Boost ($49.99)" package
└── 3. Pinned placement in /api/jobsfeed & Newsfeed Hiring Card

PHASE 4: Social Post Boosting ("Promoted Community Post")
└── Reusable infrastructure applies instantly to Post model
```

---

## 8. Live Project Safety & Performance Checklist
1. **Zero Downtime:** Every migration uses `nullable()` or safe defaults. No existing columns renamed or dropped.
2. **Octane-Safe:** All rotation services and trackers are stateless; no request leaks across persistent PHP workers.
3. **Database Indexing:** All composite indexes (`idx_active_ad_feed_lookup`, `idx_products_boost_sort`) guarantee query execution times under 5 milliseconds.
4. **Code Quality:** Formatted strictly with `vendor/bin/pint --format agent` and covered by comprehensive Pest feature tests.
