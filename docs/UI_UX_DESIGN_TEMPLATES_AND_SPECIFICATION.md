# Mind Unite — UI/UX Design Templates & API Response Specification

This document provides visual wireframe templates and field-by-field UI/UX mappings for every endpoint in the Boosting & Advertising system. Use this as a direct specification for your UI/UX designer.

---

## 🎨 Global Design Tokens

| Token Name | Value | Purpose |
| :--- | :--- | :--- |
| **Primary Brand (Deep Teal)** | `#043940` | Card headers, main button gradient, deep containers |
| **Accent Highlight (Electric Cyan)** | `#00c2cb` | Pill badges, active rings, primary CTA buttons, sliders |
| **Canvas Background (Dark)** | `#0B131A` / `#0F172A` | Page and modal backdrops |
| **Card Surface (Dark)** | `#131F2A` / `#1E293B` | Card containers with `border border-slate-800` |
| **Active / Success** | `#10B981` (Emerald) | Active status pills, pulsing dots |
| **Paused / Pending** | `#F59E0B` (Amber) | Paused status badges |
| **Typography** | `Plus Jakarta Sans` or `Inter` | Crystal clear legibility |
| **Border Radius** | `16px` (`rounded-2xl`) for cards, `12px` (`rounded-xl`) for buttons, `9999px` (`rounded-full`) for badges | Modern soft curves |

---

## 📱 SCREEN 1: In-Feed Sponsored Product Card
* **Primary API:** `GET /api/newsfeed`
* **Impression Tracking API:** `POST /api/advertisements/impressions`
* **Click Tracking API:** `POST /api/advertisements/{id}/click`
* **Placement:** Injected seamlessly at **Slot 4** (5th post) and **Slot 13** in the social newsfeed.

### Visual ASCII Wireframe:
```text
┌────────────────────────────────────────────────────────────────────────┐
│ [Company Logo]  Company Name (item.company.name)          ⚡ Most Popular│
│ (40x40 circle)  Category • Sponsor (item.company.tagline) (item.badge)  │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│                       [ Product Image Showcase ]                       │
│                           (item.image)                                 │
│                                                                        │
│  [$89.00]                                                              │
│  (item.price)                                                          │
├────────────────────────────────────────────────────────────────────────┤
│ Product Title (item.title)                                             │
│ Product description 2-line truncated text... (item.description)        │
├────────────────────────────────────────────────────────────────────────┤
│ 👁️ Viewable Impression Tracked           [ View Product ↗ ]            │
│ (POST /advertisements/impressions)        (item.cta_text)              │
│                                           (POST .../{id}/click)        │
└────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `item.is_sponsored`: If `true`, applies deep teal background (`#043940`), cyan border (`#00c2cb/40`), and glow effect.
2. `item.company.name`: Rendered in the top left user header as the advertiser name.
3. `item.company.logo`: 40x40 circular profile picture.
4. `item.badge`: Rendered in the top right pill badge (e.g. `⚡ Most Popular` in cyan `#00c2cb`).
5. `item.image`: Full-width 16:9 media banner with rounded corners.
6. `item.price`: Frosted glass floating pill in the bottom-left of the image container (`bg-black/75 backdrop-blur-md`).
7. `item.title`: Bold 16px title text below media.
8. `item.description`: Regular 13px text, truncated at 2 lines.
9. `item.cta_text`: Text inside the primary button (e.g. "View Product" or "Shop Now").
10. `item.destination_url`: The web link opened when the user clicks the CTA button or card.

---

## ⚡ SCREEN 2: Product Boost Selection & Paywall Modal
* **Primary API:** `GET /api/industry/boost-packages`
* **Purchase API:** `POST /api/industry/products/{id}/boost/purchase`
* **Trigger:** Click "⚡ Boost Product" on any seller's product row.

### Visual ASCII Wireframe:
```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ 🚀 Boost Your Product Visibility                                          ✕  │
│ Reach up to 50,000+ targeted shoppers on Mind Unite feed                     │
├──────────────────────────────────────────────────────────────────────────────┤
│ [Thumb] BioPac MP170 System                                [ Selected Prod ] │
│         Current Views: 88 views                                              │
├──────────────────────────────────────────────────────────────────────────────┤
│ [ Preset Packages (Active) ]        [ Custom Duration Slider (Web Only) ]    │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌───────────────────┐  ┌───────────────────┐  ┌───────────────────┐          │
│ │ Starter           │  │ Growth [Popular]  │  │ Pro [Best Value]  │          │
│ │ $19.99            │  │ $34.99            │  │ $69.99            │          │
│ │ 7 Days            │  │ 14 Days           │  │ 30 Days           │          │
│ │ Est. 5k-10k Views │  │ Est. 15k-25k Views│  │ Est. 40k-60k Views│          │
│ │ ───────────────── │  │ ───────────────── │  │ ───────────────── │          │
│ │ ✓ Top Placement   │  │ ✓ High In-Feed    │  │ ✓ Full Dominance  │          │
│ │ ✓ In-Feed Cards   │  │ ✓ Pause/Resume    │  │ ✓ Full Analytics  │          │
│ └───────────────────┘  └───────────────────┘  └───────────────────┘          │
├──────────────────────────────────────────────────────────────────────────────┤
│ Custom Duration Slider (When Tab 2 Active):                                  │
│ 3 Days [══════════════════════●══════════════════════════] 60 Days           │
│ Daily Rate: $3.00/day  •  Total: 20 Days × $3.00 = $60.00                    │
├──────────────────────────────────────────────────────────────────────────────┤
│ Card Details (Stripe Elements)                                               │
│ [ •••• •••• •••• 4242        |  MM / YY  |  CVC ]                            │
├──────────────────────────────────────────────────────────────────────────────┤
│ [ Pay $34.99 & Proceed to Security Verification → ]                         │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `preset_packages[i].name`: Card tier header (e.g. "Growth Boost").
2. `preset_packages[i].badge`: Pill tag on card corner (e.g. "Most Popular" or "Best Value").
3. `preset_packages[i].price`: Large font price (e.g. `$34.99`).
4. `preset_packages[i].duration_days`: Duration subtitle (e.g. `14 Days`).
5. `preset_packages[i].estimated_impressions`: Projected reach text (e.g. `15,000 – 25,000 Views`).
6. `preset_packages[i].features[]`: Array mapped directly to checkmark bullet list (`✓ Feature`).
7. `custom_slider.daily_rate`: Multiplied in real-time by slider value to calculate total price.
8. `custom_slider.min_days` & `max_days`: Slider minimum (3) and maximum (60) limits.
9. `stripe_publishable_key`: Initialized in Stripe.js SDK for rendering CardElement.

---

## 🔐 SCREEN 3: 4-Digit Email OTP Challenge Modal
* **Send OTP API:** `POST /api/industry/products/{id}/boost/send-otp`
* **Verify API:** `POST /api/industry/products/{id}/boost/purchase` (with `otp: '4821'`)
* **Trigger:** Click "Pay" in Web checkout flow.

### Visual ASCII Wireframe:
```text
┌─────────────────────────────────────────────────────────┐
│                           [✕]                           │
│                          [🛡️]                           │
│                 Verify Payment Security                 │
│       We sent a 4-digit code to your registered email:  │
│                   [ u***@mindunite.com ]                │
│                                                         │
│             ┌───┐   ┌───┐   ┌───┐   ┌───┐               │
│             │ 4 │   │ 8 │   │ 2 │   │ 1 │               │
│             └───┘   └───┘   └───┘   └───┘               │
│                                                         │
│            Didn't receive code? Resend in 00:45s        │
│                                                         │
│          [ Verify & Launch Campaign ]                   │
└─────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `data.email`: Masked email address displayed to confirm where code was sent.
2. `data.requires_verification`: Triggers opening of this OTP modal.
3. 4 Input Boxes: Auto-focus first input, auto-jump forward on keystroke, jump backward on backspace.
4. Resend Timer: 60-second countdown; converts to clickable link upon expiration.

---

## 📊 SCREEN 4: Industry Advertiser Portal — "Advertisements" Management & Analytics Hub

* **Location:** Industry Merchant Portal Navigation: `Marketing & Ads > Advertisements`
* **Purpose:** A unified self-service portal where industry merchants manage active ad campaigns, pause/resume ads without losing days (Clock-Freeze), monitor campaign history, and review audience engagement analytics.

---

### 🕒 SCREEN 4A: Ad Campaigns History & Clock-Freeze Controls
* **List Campaigns API:** `GET /api/industry/boost-campaigns` (QueryParams: `status=all|active|paused|completed`, `search={term}`, `page=1`)
* **Single Product History API:** `GET /api/industry/products/{id}/boost/campaigns`
* **Pause Ad API:** `POST /api/industry/boost/campaigns/{id}/pause`
* **Resume Ad API:** `POST /api/industry/boost/campaigns/{id}/resume`

#### Visual ASCII Wireframe:
```text
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ [ 🕒 Ad Campaigns History & Controls (8) ]       [ 📊 Ad Performance Analytics ]                      [ + Boost New Product 🚀 ]│
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ┌────────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐ │
│ │ Total Invested │ │ Campaigns    │ │ Active Ads   │ │ Paused (Frz) │ │ Completed    │ │ Impressions  │ │ Avg CTR          │ │
│ │ $420.00 💰     │ │ 8            │ │ 3 🟢         │ │ 1 ⏱️         │ │ 4            │ │ 28,450       │ │ 6.40%            │ │
│ │ (total_spend)  │ │ (total_camp) │ │ (active_camp)│ │ (paused_camp)│ │ (compl_camp) │ │ (total_imp)  │ │ (average_ctr)    │ │
│ └────────────────┘ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────────┘ │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Filter: [ All (8) ] [ Active (3) ] [ Paused (1) ] [ Completed (4) ]                   🔍 [ Search product name...            ] │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ [🧠] BioPac MP170 Smart EEG Monitor (boostable.product_name)                                $112.00 Paid      🟢 Active Ad │ │
│ │      Campaign #108 • Growth Surge (14 Days) • Priority: 5 (boost_package.name)                 (amount_paid)  (item.status)│ │
│ │ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐                        │ │
│ │ │ Impressions: 14,250  │ │ Clicks: 920          │ │ CTR: 6.46%           │ │ Cost/Click: $0.12    │                        │ │
│ │ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘                        │ │
│ │ Timeline: Started: Oct 10, 2026      ⚡ 7 Days Remaining (50% Complete)        Ends: Oct 24, 2026                           │ │
│ │ [████████████████████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]                   │ │
│ │ ❄️ Clock-Freeze Policy: Pause anytime without losing your 7 days.                      [ 👁️ In Feed ]  [ ⏸️ Pause Ad ]   │ │
│ │                                                                                                         (POST .../pause) │ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│ ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│ │ [📡] NeuroEcho Wireless EMG Sensor                                                          $56.00 Paid ⏱️ Paused (Frozen) │ │
│ │      Campaign #95 • Starter Spark (7 Days) • Priority: 3                                                      (item.status)│ │
│ │ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐                        │ │
│ │ │ Impressions: 6,480   │ │ Clicks: 415          │ │ CTR: 6.40%           │ │ Cost/Click: $0.13    │                        │ │
│ │ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘                        │ │
│ │ Timeline: Started: Oct 11, 2026      ⏱️ 4 Days 12h Remaining (Frozen)          Ends: Frozen Expiration                      │ │
│ │ [████████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]                   │ │
│ │ ⚠️ Auto-Expiry: 26 days left before paused campaign auto-expires.                      [ ✏️ Edit ]     [ ▶️ Resume Ad ]  │ │
│ │                                                                                                         (POST .../resume)│ │
│ └────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

#### Exact Response Field to UI Component Mapping (Screen 4A):
1. `data.metrics.total_spend`: Cumulative budget invested in boosting across all campaigns (e.g. `420.00`).
2. `data.metrics.total_spend_formatted`: Formatted currency string for direct display (e.g. `"$420.00"`).
3. `data.metrics.total_campaigns`: Total number of campaigns ever created by this industry.
4. `data.metrics.active_campaigns`: Number of currently running ad campaigns (with pulsing emerald indicator).
5. `data.metrics.paused_campaigns`: Number of paused campaigns with clock frozen.
6. `data.metrics.completed_campaigns`: Number of campaigns that reached scheduled end date.
7. `data.metrics.total_impressions`: Sum of all viewable IAB impressions delivered.
8. `data.metrics.total_clicks`: Sum of all verified clicks recorded.
9. `data.metrics.average_ctr`: Aggregate CTR percentage: `(total_clicks / total_impressions) * 100`.
10. `item.boostable.product_name`: Title of the boosted product.
11. `item.boost_package.name`: Title of boost package applied (or "Custom Duration").
12. `item.status`:
    - `active`: Emerald pill with pulsing dot + `[ ⏸️ Pause Ad ]` button.
    - `paused`: Amber pill with clock icon + `[ ▶️ Resume Ad ]` button.
    - `completed`: Slate gray pill + `[ 🚀 Boost Again ]` button.
13. `item.starts_at` & `item.ends_at`: Formatted dates flanking the timeline progress bar.
14. `item.total_impressions` & `item.total_clicks`: Real-time KPI tiles for each individual campaign.

---

### 📊 SCREEN 4B: Industry Ad Performance Analytics & Insights
* **Analytics API:** `GET /api/industry/analytics/advertisements` (QueryParams: `period=weekly|monthly`)
* **Purpose:** High-level executive performance summary showing total investment, growth comparisons, top-performing product rankings, and a views-versus-engagements trend chart.

#### Visual ASCII Wireframe:
```text
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Timeframe: [ Weekly (Last 7 Days) ] [ Monthly (2026) ]                                                  [ 📥 Export CSV Report ] │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────────┐ │
│ │ 💰 Total Invested    │ │ 📦 Total Products    │ │ ⚡ Active Boosted Ads │ │ 👁️ Product Ad Views │ │ ❤️ Likes & Engagements   │ │
│ │ $420.00              │ │ 24                   │ │ 8                    │ │ 18,450               │ │ 1,280                    │ │
│ │ ↗ +14.5% from last m │ │ ↗ +12% from last mon │ │ ↗ +33.3% from last m │ │ ↗ +28.5% from last m │ │ ↗ +18.2% from last month │ │
│ │ (cards.total_spend)  │ │ (cards.total_products│ │ (active_products)    │ │ (cards.product_views)│ │ (cards.total_likes)      │ │
│ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘ └──────────────────────┘ └──────────────────────────┘ │
├────────────────────────────────────────────────────────────┬─────────────────────────────────────────────────────────────────────┤
│ 🏆 Top Performing Products (top_performing_products)      │ 📈 Views vs. Engagements Chart (data.graph)                         │
│ ┌──────────────────────────────────┬───────┬─────────────┐ │ Legend: [■ Cyan = Views]  [● Amber = Likes/Clicks]                 │
│ │ Rank & Product                   │ Views │ Likes/Clicks│ │                                                                     │
│ ├──────────────────────────────────┼───────┼─────────────┤ │  3.5k ──┐                     ┌─┐ (Peak: Thu)                       │
│ │ 🥇 1. BioPac MP170 EEG Monitor   │ 7,420 │      512    │ │         │         ┌─┐         │█│                                   │
│ │ 🥈 2. OptiScan Portable MRI      │ 5,310 │      384    │ │  2.5k ──┤ ┌─┐     │█│ ┌─┐     │█│ ┌─┐ ┌─┐ ┌─┐                       │
│ │ 🥉 3. NeuroEcho EMG Sensor       │ 3,240 │      215    │ │         │ │█│ ●   │█│ │█│ ●   │█│ │█│ │█│ │█│                       │
│ │    4. SynapsePro Interface       │ 1,680 │      108    │ │  1.0k ──┤ │█│ │   │█│ │█│ │   │█│ │█│ │█│ │█│                       │
│ │    5. CorticalStrap Headset V2   │   800 │       61    │ │         │ │█│ │   │█│ │█│ │   │█│ │█│ │█│ │█│                       │
│ └──────────────────────────────────┴───────┴─────────────┘ │         └─┴─┴─┴───┴─┴─┴─┴─┴───┴─┴─┴─┴─┴─┴─┴─┴───────────────────────┘ │
│                                                            │           Mon   Tue   Wed   Thu   Fri   Sat   Sun                       │
└────────────────────────────────────────────────────────────┴─────────────────────────────────────────────────────────────────────┘
```

#### Exact Response Field to UI Component Mapping (Screen 4B):
1. `data.cards.total_spend`: Total money invested in ad campaigns (`total`, `formatted` as `"$420.00"`, `growth`, `is_positive`).
2. `data.cards.total_products`: Total product count (`total`, `growth` formatted as `+12% from last month`, `is_positive`).
3. `data.cards.active_products`: Active promoted products count with growth metric.
4. `data.cards.product_views`: Cumulative ad views count across current month with percentage change.
5. `data.cards.total_likes`: Total like/save interactions with percentage growth.
5. `data.top_performing_products[]`: Left ranking table:
   - `id` & `product_id`: Unique identifier badge.
   - `product_name`: Truncated product label.
   - `views_count`: Formatted view count.
   - `likes_count`: Formatted engagement count.
6. `data.graph[]`: Right performance trend chart:
   - `label`: X-axis time label (`Mon` through `Sun` for weekly; `Jan` through `Dec` for monthly).
   - `views`: Vertical bar height rendered in electric cyan gradient (`#00c2cb`).
   - `likes`: Highlight dot / trend line point rendered in amber (`#f59e0b`).
7. Period selector toggle:
   - When switching to `period=weekly`, queries `GET /api/industry/analytics/advertisements?period=weekly`.
   - When switching to `period=monthly`, queries `GET /api/industry/analytics/advertisements?period=monthly`.

---

## 🛡️ SCREEN 5: Global Boost & Ad Algorithm Settings Center
* **Get Settings API:** `GET /api/admin/boost-settings`
* **Get Revenue & Overview API:** `GET /api/admin/boost-packages/overview`
* **Update Settings API:** `PATCH /api/admin/boost-settings`
* **Trigger:** Admin Navigation: `Monetization & Ads > Boost Settings & Algorithms`.

### Visual ASCII Wireframe (Overview KPIs + 4 Display Cards + Edit Modal):
```text
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ 💰 Total Ad Revenue    │ 🟢 Live Active Ads     │ 📦 Boost Packages     │ 👁️ Impressions        │
│ $104.98                │ 2 Running              │ 3 Active / 3 Total    │ 1,420 Delivered        │
│ (overview.total_ad_rev)│ (overview.live_count)  │ (overview.active_pkg) │ (overview.total_imp)   │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ⚙️ Global Boost & Ad Algorithm Settings (GET /api/admin/boost-settings)  [ ✏️ Edit All Settings ]│
│                                                                                                │
│ ┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐     │
│ │ 📢 Newsfeed Ad Insertion     [ ✏️ Edit ] │  │ 🎚️ Web Custom Slider Rate  [ ✏️ Edit ] │     │
│ ├──────────────────────────────────────────┤  ├──────────────────────────────────────────┤     │
│ │ First Slot: Slot 4                       │  │ Daily Rate: $3.00 / Day                  │     │
│ │ Ad Interval: Every 8 Posts               │  │ Min Duration: 3 Days                     │     │
│ │ Anti-Fatigue: Max 5 / Day                │  │ Max Duration: 60 Days                    │     │
│ └──────────────────────────────────────────┘  └──────────────────────────────────────────┘     │
│ ┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐     │
│ │ 🎁 Free New Release Window   [ ✏️ Edit ] │  │ ⏸️ Enterprise Pause Policy   [ ✏️ Edit ] │     │
│ ├──────────────────────────────────────────┤  ├──────────────────────────────────────────┤     │
│ │ Free Boost Window: 24 Hours              │  │ Max Freeze: 30 Days                      │     │
│ │ Top Placement + New Release Badge        │  │ 1st Reminder: Day 27                     │     │
│ │                                          │  │ Final Warning: Day 29                    │     │
│ └──────────────────────────────────────────┘  └──────────────────────────────────────────┘     │
└────────────────────────────────────────────────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ⚙️ Edit Global Advertising & Boost Settings (Modal)                      [ ✕ Close ]           │
│                                                                                                │
│ 1. Newsfeed Ad Injection:                                                                      │
│    First Ad Slot: [ 4 ]      Ad Interval: [ 8 ]      Max Daily Views/User: [ 5 ]               │
│                                                                                                │
│ 2. Web Custom Duration Slider Rate:                                                            │
│    Daily Rate ($ USD): [ 3.00 ]     Min Days: [ 3 ]     Max Days: [ 60 ]                       │
│                                                                                                │
│ 3. Free New Release Promotion Window:                                                          │
│    Free Boost Window (Hours): [ 24 ]                                                           │
│                                                                                                │
│ 4. Enterprise Pause Policy:                                                                    │
│    Max Pause Days: [ 30 ]    1st Reminder Day: [ 27 ]    Final Warning Day: [ 29 ]             │
│                                                                                                │
│                                           [ Cancel ]  [ 💾 Save & Apply Settings (PATCH) ]    │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `overview.total_ad_revenue`: Top Card 1 (Total platform boost earnings).
2. `overview.live_campaigns_count`: Top Card 2 (Active campaigns currently in circulation).
3. `overview.active_packages`: Top Card 3 (Count of published tiers available to sellers).
4. `overview.total_impressions_delivered`: Top Card 4 (Cumulative view impressions tracked).
5. `newsfeed_ads.first_ad_slot`, `ad_interval`, `max_daily_impressions_per_user`: Display Card 1 values and Modal Section 1 inputs.
6. `custom_slider.daily_rate`, `min_days`, `max_days`: Display Card 2 values and Modal Section 2 inputs.
7. `new_release.hours`: Display Card 3 value and Modal Section 3 input.
8. `pause_policy.max_days`, `first_reminder_days`, `final_reminder_days`: Display Card 4 values and Modal Section 4 inputs.
9. Modal Save button triggers `PATCH /api/admin/boost-settings` and dynamically updates all card values in real-time.

---

## 📦 SCREEN 6: Admin Boost Packages Management Table
* **List Packages API:** `GET /api/admin/boost-packages`
* **Toggle Status API:** `PATCH /api/admin/boost-packages/{id}/status`
* **Delete Package API:** `DELETE /api/admin/boost-packages/{id}`
* **Trigger:** Admin Navigation: `Monetization & Ads > Boost Packages Table`.

### Visual ASCII Wireframe:
```text
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ Screen 6: Admin Boost Packages Management Table                        [ + Create New Package ]│
│ Sellers purchase these boost tiers on mobile and web. Toggle package status or edit tier.      │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Package Name & Badge  Duration  Price   Est. Reach       Priority  Active Ads  Status  Actions │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 7-Day Starter Boost   7 Days    $19.99  5,000 – 10,000   1         0 Live      [ON]    ✏️   🗑️  │
│ [Starter]                                                                                      │
│                                                                                                │
│ 14-Day Growth Boost   14 Days   $34.99  15,000 – 25,000  2         1 Live      [ON]    ✏️   🗑️  │
│ [Most Popular]                                                                                 │
│                                                                                                │
│ 30-Day Pro Boost      30 Days   $69.99  40,000 – 60,000  3         1 Live      [ON]    ✏️   🗑️  │
│ [Best Value]                                                                                   │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `data[i].name` & `badge`: Data table title and cyan badge pill.
2. `data[i].duration_days`, `price`, `estimated_impressions`: Table metrics columns.
3. `data[i].priority_weight`: Priority sorting column.
4. `data[i].is_active`: Instant interactive toggle switch (`PATCH .../status`).
5. Action Buttons: `✏️` switches to Screen 7 (Edit Package), `🗑️` triggers delete confirmation (`DELETE .../{id}`).

---

## 📝 SCREEN 7: Admin Create & Edit Package Modal Form
* **Create API:** `POST /api/admin/boost-packages`
* **Update API:** `PATCH /api/admin/boost-packages/{id}`
* **Trigger:** Click `+ Create New Package` or `✏️ Edit` icon from Screen 6.

### Visual ASCII Wireframe (2-Column Architecture):
```text
┌──────────────────────────────────────────────────────────┬────────────────────────┐
│ Create Boost Package                                     │ Live Paywall Preview   │
│                                                          │                        │
│ Package Name:                                            │ ┌────────────────────┐ │
│ [ 14-Day Growth Boost                                  ] │ │ Most Popular       │ │
│                                                          │ │ $34.99 / 14 Days   │ │
│ Duration (Days):                  Price (USD):           │ │ Est. 15k-25k Views │ │
│ [ 14                            ] [ 34.99              ] │ │ ────────────────── │ │
│                                                          │ │ ✓ Top Placement    │ │
│ Highlight Badge:                  Estimated Reach:       │ │ ✓ In-Feed Cards    │ │
│ [ Most Popular                  ] [ 15,000 – 25,000    ] │ │ ✓ Analytics        │ │
│                                                          │ └────────────────────┘ │
│ Features Checklist:                                      │                        │
│ [ ✓ Top Placement ✕ ] [ ✓ In-Feed Cards ✕ ] [ + Add ]   │ Preview updates        │
│                                                          │ dynamically as admin   │
│ RevenueCat Product ID (Mobile IAP):                      │ types on the left      │
│ [ boost_product_14d_3499                               ] │                        │
│                                                          │                        │
│                    [ Cancel ]  [ Save & Publish Package ]│                        │
└──────────────────────────────────────────────────────────┴────────────────────────┘
```

