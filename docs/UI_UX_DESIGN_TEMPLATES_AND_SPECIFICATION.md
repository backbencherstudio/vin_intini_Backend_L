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

## 📊 SCREEN 4: Product Boost History & Pause/Resume Analytics Card
* **List API:** `GET /api/industry/products/{id}/boost/campaigns`
* **Pause API:** `POST /api/industry/boost/campaigns/{id}/pause`
* **Resume API:** `POST /api/industry/boost/campaigns/{id}/resume`
* **Trigger:** Accessible on the Merchant's Product Dashboard.

### Visual ASCII Wireframe:
```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ Active Boost Campaign                                        🟢 Active       │
│ Campaign ID #1 • Growth 14-Day Package                       (item.status)   │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────┐ ┌────────────────────────┐ ┌────────────────────┐ │
│ │ Total Impressions      │ │ Total Clicks           │ │ Click-Through Rate │ │
│ │ 1,420                  │ │ 88                     │ │ 6.2%               │ │
│ │ (total_impressions)    │ │ (total_clicks)         │ │ (clicks / imp)     │ │
│ └────────────────────────┘ └────────────────────────┘ └────────────────────┘ │
├──────────────────────────────────────────────────────────────────────────────┤
│ Timeline Progress:                                                           │
│ Starts: Oct 17, 2026           12 Days Remaining          Ends: Oct 31, 2026 │
│ [████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░] │
│ (item.starts_at)                                            (item.ends_at)   │
├──────────────────────────────────────────────────────────────────────────────┤
│ ⏸️ Campaign Clock-Freeze Policy                                              │
│ Pause your ad anytime up to 30 days without losing time.     [ ⏸️ Pause Ad ] │
│                                                              (POST .../pause)│
└──────────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `item.status`: Status badge (`Active` green, `Paused` amber, `Completed` slate).
2. `item.total_impressions`: Formatted view count.
3. `item.total_clicks`: Formatted click count.
4. `item.starts_at` & `item.ends_at`: Progress bar start/end dates and remaining days.
5. `is_paused`:
   - If `false`: Renders amber `[ ⏸️ Pause Ad ]` button.
   - If `true`: Renders emerald `[ ▶️ Resume Ad ]` button.

---

## 🛡️ SCREEN 5: Unified Admin Boost Packages, Revenue KPI & Settings Center
* **List Packages & Overview API:** `GET /api/admin/boost-packages`
* **Toggle Status API:** `PATCH /api/admin/boost-packages/{id}/status`
* **Get Settings API:** `GET /api/admin/boost-settings`
* **Update Settings API:** `PATCH /api/admin/boost-settings`
* **Trigger:** Admin Navigation: `Monetization & Ads > Boost Packages & Settings`.

### Visual ASCII Wireframe:
```text
┌────────────────────────────────────────────────────────────────────────────────────────────────┐
│ 💰 Total Ad Revenue    │ 🟢 Live Active Ads     │ 📦 Boost Packages     │ 👁️ Impressions        │
│ $104.98                │ 2 Running              │ 3 Active / 3 Total    │ 1,420 Delivered        │
│ (overview.total_ad_rev)│ (overview.live_count)  │ (overview.active_pkg) │ (overview.total_imp)   │
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Package Management Table                                               [ + Create New Package ]│
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
├────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ⚙️ Global Boost & Ad Algorithm Settings (GET /api/admin/boost-settings)  [ 💾 Save Config ]    │
│ ┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐     │
│ │ 📢 Newsfeed Ad Insertion           [ON]  │  │ 🎚️ Web Custom Slider Rate          [ON]  │     │
│ │ First Slot: [4]    Interval: [8] posts   │  │ Daily Rate: [ $3.00/day ]                │     │
│ │ Anti-Fatigue Cap: [5] views/day          │  │ Min: [3] days    Max: [60] days          │     │
│ └──────────────────────────────────────────┘  └──────────────────────────────────────────┘     │
│ ┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐     │
│ │ 🎁 Free New Release Window               │  │ ⏸️ Enterprise Pause Policy              │     │
│ │ Duration: [24] hours free boost          │  │ Max Pause: [30] days   Reminder: Day 27  │     │
│ └──────────────────────────────────────────┘  └──────────────────────────────────────────┘     │
└────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Exact Response Field to UI Component Mapping:
1. `overview.total_ad_revenue`: Large dollar amount in Top Card 1 (Total platform boost earnings).
2. `overview.live_campaigns_count`: Top Card 2 running campaigns in circulation.
3. `overview.active_packages`: Top Card 3 count of published tiers available to sellers.
4. `overview.total_impressions_delivered`: Top Card 4 cumulative view impressions tracked.
5. `data[i].name` & `badge`: Data table title and cyan badge pill.
6. `data[i].duration_days`, `price`, `estimated_impressions`: Table metrics columns.
7. `data[i].is_active`: Instant interactive toggle switch (`PATCH .../status`).
8. `newsfeed_ads.*`, `custom_slider.*`, `pause_policy.*`: Editable configuration inputs saved via `PATCH /api/admin/boost-settings`.

---

## 📝 SCREEN 6: Admin Create & Edit Package Modal Form
* **Create API:** `POST /api/admin/boost-packages`
* **Update API:** `PATCH /api/admin/boost-packages/{id}`
* **Trigger:** Click `+ Create New Package` or `✏️ Edit` icon.

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

---

## ⚙️ SCREEN 7: Admin Global Advertising & Boosting Settings
* **Get Settings API:** `GET /api/admin/boost-settings`
* **Update Settings API:** `PATCH /api/admin/boost-settings`
* **Trigger:** Admin Navigation: `Monetization & Ads > Ad & Boost Settings`.

### Visual ASCII Wireframe (4-Card Grid):
```text
┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐
│ 📢 Newsfeed Ad Injection           [ON]  │  │ 🎚️ Web Custom Slider Rate          [ON]  │
├──────────────────────────────────────────┤  ├──────────────────────────────────────────┤
│ First Ad Slot: [ 4 ]                     │  │ Daily Rate ($ USD): [ 3.00 ]             │
│ Ad Interval (posts): [ 8 ]               │  │ Min Days: [ 3 ]     Max Days: [ 60 ]     │
│ Anti-Fatigue Cap (views/day): [ 5 ]      │  │ Daily Est. Reach: [ 700 - 1400 ]         │
└──────────────────────────────────────────┘  └──────────────────────────────────────────┘

┌──────────────────────────────────────────┐  ┌──────────────────────────────────────────┐
│ 🎁 Free New Release Promotion            │  │ ⏸️ Enterprise Pause Policy              │
├──────────────────────────────────────────┤  ├──────────────────────────────────────────┤
│ Free Boost Duration (Hours): [ 24 ]      │  │ Max Pause Duration (Days): [ 30 ]        │
│                                          │  │ 1st Reminder Day: [ 27 ]                 │
│ (New products auto-get 24h boost free)   │  │ Final Warning Day: [ 29 ]                │
└──────────────────────────────────────────┘  └──────────────────────────────────────────┘

                                             [ 💾 Save Configurations ]
```
