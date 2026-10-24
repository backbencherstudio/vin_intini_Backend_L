# Admin Boosting System API & Frontend Integration Documentation

> **Target Audience:** Frontend Developers (Admin Dashboard Web & Mobile Apps), QA Engineers, and Product Managers.  
> **Base URL:** `https://your-domain.com/api` (or local environment: `http://localhost:8000/api`)  
> **Live UI Reference:** `http://localhost:8000/admin/boosting-showcase`  
> **Authentication:** Required — JWT Bearer token with `role: admin`.  
> **Headers Required:**
> - `Accept: application/json`
> - `Content-Type: application/json`
> - `Authorization: Bearer <admin_access_token>`

---

## 1. Overview & Architecture

The **Admin Boosting System** enables platform administrators to configure self-serve paid promotion packages for marketplace products, job posts, research publications, and community posts.

### Core Architectural Decisions & Rules
1. **Equal Platform Feature Standard (Static Features):**
   Unlike user subscription tiers where permissions vary (e.g. 5 jobs vs 50 jobs), **every single product boost package receives equal, top-tier promotional capabilities**. Therefore, admin does **NOT** manually input or check feature checkboxes during plan creation.
   The 4 core features guaranteed for every boosted campaign are:
   - `Dual Placement: Marketplace Feed + Main Newsfeed` (Dual Channel Exposure)
   - `Featured "Sponsored" Badge Ribbon` (Cyan highlight on cards for maximum CTR)
   - `Fair-Play Exposure Rotation (Never Buried)` (Impression-balancing round-robin rotation)
   - `Real-Time Verified Views & Click Analytics` (Fraud-free impressions & engagement tracking)
2. **Admin-Configurable Plan Parameters:**
   - **Boosting Plan Name** (e.g. `Starter Boost`, `Growth Boost`, `Best Value Boost`)
   - **Duration (Days)** (e.g. `7`, `14`, `30`)
   - **Price ($ USD)** (e.g. `19.99`, `34.99`, `69.99`)
   - **Target Entity** (`product`, `job`, `publication`, `post`, `all`)
   - **Package Description** (Clear summary for the checkout paywall)
   - **Badge Ribbon (Optional)** (e.g. `Most Popular Plan`, `Best Value (Save 18%)`, `Starter Tier`)
   - **Promotion Duration Highlight (Badge)** (e.g. `14 Days Extended Multi-Channel Reach`)
   - **Estimated Impressions** (e.g. `15,000 – 25,000 Impressions`)
   - **Status** (`active` / `inactive`)
   - **Priority Weight** (Integer `1` to `3` for visual sorting order on the paywall)
   - **Mobile In-App Purchase Credentials (RevenueCat):**
     - `revenuecat_store_identifier_ios` (Apple App Store Product ID)
     - `revenuecat_store_identifier_android` (Google Play Store Product ID)
     - `revenuecat_entitlement_identifier` (RevenueCat Entitlement ID)

---

## 2. API Endpoints Summary

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/admin/boost-packages` | List all boosting tiers with overview KPIs and query filters |
| `POST` | `/api/admin/boost-packages/store` | Create a new boosting plan |
| `GET` | `/api/admin/boost-packages/{id}/show` | Get single plan details for edit modal / preview |
| `PATCH` | `/api/admin/boost-packages/{id}/update` | Update plan parameters |
| `PATCH` | `/api/admin/boost-packages/{id}/status` | Quick toggle between `active` and `inactive` |
| `DELETE` | `/api/admin/boost-packages/{id}/delete` | Delete plan (protected if active campaigns exist) |
| `GET` | `/api/admin/boost-settings` | Get global boost settings (custom slider, pause policy, newsfeed ad rules) |
| `PATCH` | `/api/admin/boost-settings` | Update global boost settings |

---

## 3. Endpoints Specification

### 3.1. List Boost Packages & KPI Overview (`GET /api/admin/boost-packages`)

Fetches all packages sorted by `priority_weight` DESC with real-time KPI overview metrics for top stat counters.

- **Method:** `GET`
- **URL:** `/api/admin/boost-packages`
- **Auth:** Required (`Bearer <admin_token>`)

#### Query Parameters (Optional Filters):
| Parameter | Type | Default | Description |
|---|---|---|---|
| `target_entity` | `string` | `null` | Filter by entity: `'product'`, `'job'`, `'publication'`, `'post'`, or `'all'`. |
| `status` | `string` | `null` | Filter by status: `'active'` or `'inactive'`. |
| `search` | `string` | `null` | Search query matching plan name or description. |

#### Example Request:
```http
GET /api/admin/boost-packages?target_entity=product HTTP/1.1
Host: your-domain.com
Authorization: Bearer 1|abcdef123456...
Accept: application/json
```

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "overview": {
    "total_packages": 3,
    "active_packages": 3,
    "total_ad_revenue": 45890.50,
    "live_campaigns_count": 41058,
    "total_impressions_delivered": 1420500,
    "total_clicks_recorded": 98450,
    "custom_packages": {
      "total_purchased_count": 8420,
      "live_campaigns_count": 420,
      "total_revenue": 14250.00
    },
    "preset_packages": {
      "total_purchased_count": 32638,
      "live_campaigns_count": 40638,
      "total_revenue": 31640.50
    }
  },
  "data": [
    {
      "id": 3,
      "name": "Best Value Boost",
      "target_entity": "product",
      "duration_days": 30,
      "price": 69.99,
      "daily_price": 2.33,
      "estimated_impressions": "40,000 – 60,000+ Impressions",
      "badge": "Best Value (Save 18%)",
      "promotion_highlight": "30 Days Full-Month Non-Stop",
      "description": "Full-month omnipresent promotion for top vendors & brands.",
      "features": [
        "Dual Placement: Marketplace Feed + Main Newsfeed",
        "Featured \"Sponsored\" Badge Ribbon",
        "Fair-Play Exposure Rotation (Never Buried)",
        "Real-Time Verified Views & Click Analytics"
      ],
      "priority_weight": 3,
      "status": "active",
      "is_active": true,
      "checkout_type": "stripe",
      "revenuecat_store_identifier_ios": "boost_product_30d",
      "revenuecat_store_identifier_android": "boost_product_30d_gp",
      "revenuecat_entitlement_identifier": "product_boost_30d",
      "active_campaigns_count": 457,
      "total_campaigns_count": 1820,
      "created_at": "2026-10-15T05:01:05.000000Z",
      "updated_at": "2026-10-22T10:45:00.000000Z"
    },
    {
      "id": 2,
      "name": "Growth Boost",
      "target_entity": "product",
      "duration_days": 14,
      "price": 34.99,
      "daily_price": 2.50,
      "estimated_impressions": "15,000 – 25,000 Impressions",
      "badge": "Most Popular Plan",
      "promotion_highlight": "14 Days Extended Multi-Channel Reach",
      "description": "Multi-channel promotion across marketplace & main newsfeed.",
      "features": [
        "Dual Placement: Marketplace Feed + Main Newsfeed",
        "Featured \"Sponsored\" Badge Ribbon",
        "Fair-Play Exposure Rotation (Never Buried)",
        "Real-Time Verified Views & Click Analytics"
      ],
      "priority_weight": 2,
      "status": "active",
      "is_active": true,
      "checkout_type": "stripe",
      "revenuecat_store_identifier_ios": "boost_product_14d",
      "revenuecat_store_identifier_android": "boost_product_14d_gp",
      "revenuecat_entitlement_identifier": "product_boost_14d",
      "active_campaigns_count": 4923,
      "total_campaigns_count": 12840,
      "created_at": "2026-10-15T05:01:05.000000Z",
      "updated_at": "2026-10-22T10:45:00.000000Z"
    },
    {
      "id": 1,
      "name": "Starter Boost",
      "target_entity": "product",
      "duration_days": 7,
      "price": 19.99,
      "daily_price": 2.85,
      "estimated_impressions": "5,000 – 10,000 Impressions",
      "badge": "Starter Tier",
      "promotion_highlight": "7 Days Active Promotion",
      "description": "Essential front-row visibility to jumpstart product discovery.",
      "features": [
        "Dual Placement: Marketplace Feed + Main Newsfeed",
        "Featured \"Sponsored\" Badge Ribbon",
        "Fair-Play Exposure Rotation (Never Buried)",
        "Real-Time Verified Views & Click Analytics"
      ],
      "priority_weight": 1,
      "status": "active",
      "is_active": true,
      "checkout_type": "stripe",
      "revenuecat_store_identifier_ios": "boost_product_7d",
      "revenuecat_store_identifier_android": "boost_product_7d_gp",
      "revenuecat_entitlement_identifier": "product_boost_7d",
      "active_campaigns_count": 35678,
      "total_campaigns_count": 68420,
      "created_at": "2026-10-15T05:01:05.000000Z",
      "updated_at": "2026-10-22T10:45:00.000000Z"
    }
  ]
}
```

---

### 3.2. Create Boost Package (`POST /api/admin/boost-packages/store`)

Allows Admin to create a new boosting package.

- **Method:** `POST`
- **URL:** `/api/admin/boost-packages/store`
- **Auth:** Required (`Bearer <admin_token>`)

#### Request Body Parameters:
| Field | Type | Required | Description |
|---|---|---|---|
| `name` | `string` | **Yes** | Boosting plan name (max: 255). |
| `target_entity` | `string` | **Yes** | One of: `'product'`, `'job'`, `'publication'`, `'post'`, `'all'`. |
| `duration_days` | `integer` | **Yes** | Total campaign days (min: 1, max: 365). |
| `price` | `numeric` | **Yes** | Price in USD (min: 0, max: 999999.99). |
| `description` | `string` | No | Marketing summary shown on paywall cards. |
| `badge` | `string` | No | Optional badge ribbon above card (e.g. `'Most Popular Plan'`). |
| `promotion_highlight` | `string` | No | Highlighted pill inside card (e.g. `'14 Days Extended Multi-Channel Reach'`). |
| `estimated_impressions` | `string` | No | Impression highlight (e.g. `'15,000 – 25,000 Impressions'`). |
| `priority_weight` | `integer` | No | Display sorting priority: `1` (Standard), `2` (Growth), `3` (VIP). Default: `0`. |
| `status` | `string` | No | `'active'` or `'inactive'`. Default: `'active'`. |
| `revenuecat_store_identifier_ios` | `string` | No | iOS In-App Purchase product ID. |
| `revenuecat_store_identifier_android` | `string` | No | Google Play In-App Purchase product ID. |
| `revenuecat_entitlement_identifier` | `string` | No | RevenueCat entitlement identifier. |

#### Example Request:
```json
{
  "name": "Weekend Blitz",
  "target_entity": "product",
  "duration_days": 3,
  "price": 9.99,
  "description": "Quick 3-day weekend discovery burst for product flash sales.",
  "badge": "Flash Promo",
  "promotion_highlight": "3 Days Weekend Discovery Burst",
  "estimated_impressions": "3,000 – 5,000 Impressions",
  "priority_weight": 1,
  "status": "active",
  "revenuecat_store_identifier_ios": "boost_product_3d",
  "revenuecat_store_identifier_android": "boost_product_3d_gp",
  "revenuecat_entitlement_identifier": "product_boost_3d"
}
```

#### Successful Response (`201 Created`):
```json
{
  "success": true,
  "message": "Boost package created successfully.",
  "data": {
    "id": 4,
    "name": "Weekend Blitz",
    "target_entity": "product",
    "duration_days": 3,
    "price": 9.99,
    "daily_price": 3.33,
    "estimated_impressions": "3,000 – 5,000 Impressions",
    "badge": "Flash Promo",
    "promotion_highlight": "3 Days Weekend Discovery Burst",
    "description": "Quick 3-day weekend discovery burst for product flash sales.",
    "features": [
      "Dual Placement: Marketplace Feed + Main Newsfeed",
      "Featured \"Sponsored\" Badge Ribbon",
      "Fair-Play Exposure Rotation (Never Buried)",
      "Real-Time Verified Views & Click Analytics"
    ],
    "priority_weight": 1,
    "status": "active",
    "is_active": true,
    "checkout_type": "stripe",
    "revenuecat_store_identifier_ios": "boost_product_3d",
    "revenuecat_store_identifier_android": "boost_product_3d_gp",
    "revenuecat_entitlement_identifier": "product_boost_3d",
    "created_at": "2026-10-22T11:00:00.000000Z",
    "updated_at": "2026-10-22T11:00:00.000000Z"
  }
}
```

---

### 3.3. View Single Package (`GET /api/admin/boost-packages/{id}/show`)

- **Method:** `GET`
- **URL:** `/api/admin/boost-packages/{id}/show`
- **Auth:** Required (`Bearer <admin_token>`)

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 2,
    "name": "Growth Boost",
    "target_entity": "product",
    "duration_days": 14,
    "price": 34.99,
    "daily_price": 2.50,
    "estimated_impressions": "15,000 – 25,000 Impressions",
    "badge": "Most Popular Plan",
    "promotion_highlight": "14 Days Extended Multi-Channel Reach",
    "description": "Multi-channel promotion across marketplace & main newsfeed.",
    "features": [
      "Dual Placement: Marketplace Feed + Main Newsfeed",
      "Featured \"Sponsored\" Badge Ribbon",
      "Fair-Play Exposure Rotation (Never Buried)",
      "Real-Time Verified Views & Click Analytics"
    ],
    "priority_weight": 2,
    "status": "active",
    "is_active": true,
    "checkout_type": "stripe",
    "revenuecat_store_identifier_ios": "boost_product_14d",
    "revenuecat_store_identifier_android": "boost_product_14d_gp",
    "revenuecat_entitlement_identifier": "product_boost_14d",
    "active_campaigns_count": 4923,
    "total_campaigns_count": 12840,
    "created_at": "2026-10-15T05:01:05.000000Z",
    "updated_at": "2026-10-22T10:45:00.000000Z"
  }
}
```

---

### 3.4. Update Boost Package (`PATCH /api/admin/boost-packages/{id}/update`)

- **Method:** `PATCH`
- **URL:** `/api/admin/boost-packages/{id}/update`
- **Auth:** Required (`Bearer <admin_token>`)

#### Request Body (All fields optional / sometimes required):
```json
{
  "name": "Growth Boost (Updated)",
  "price": 36.99,
  "duration_days": 14,
  "description": "Updated reach summary.",
  "badge": "Top Seller",
  "promotion_highlight": "14 Days VIP Extended Reach",
  "estimated_impressions": "18,000 – 28,000 Impressions",
  "priority_weight": 2,
  "status": "active"
}
```

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "message": "Boost package updated successfully.",
  "data": {
    "id": 2,
    "name": "Growth Boost (Updated)",
    "target_entity": "product",
    "duration_days": 14,
    "price": 36.99,
    "daily_price": 2.64,
    "estimated_impressions": "18,000 – 28,000 Impressions",
    "badge": "Top Seller",
    "promotion_highlight": "14 Days VIP Extended Reach",
    "description": "Updated reach summary.",
    "features": [
      "Dual Placement: Marketplace Feed + Main Newsfeed",
      "Featured \"Sponsored\" Badge Ribbon",
      "Fair-Play Exposure Rotation (Never Buried)",
      "Real-Time Verified Views & Click Analytics"
    ],
    "priority_weight": 2,
    "status": "active",
    "is_active": true,
    "updated_at": "2026-10-22T11:05:00.000000Z"
  }
}
```

---

### 3.5. Quick Status Toggle (`PATCH /api/admin/boost-packages/{id}/status`)

Enables a 1-click toggle switch in the admin table without opening the full edit form.

- **Method:** `PATCH`
- **URL:** `/api/admin/boost-packages/{id}/status`
- **Auth:** Required (`Bearer <admin_token>`)

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "message": "Boost package status updated to inactive.",
  "data": {
    "id": 2,
    "name": "Growth Boost",
    "status": "inactive",
    "is_active": false
  }
}
```

---

### 3.6. Delete Boost Package (`DELETE /api/admin/boost-packages/{id}/delete`)

- **Method:** `DELETE`
- **URL:** `/api/admin/boost-packages/{id}/delete`
- **Auth:** Required (`Bearer <admin_token>`)

#### Safe Success Response (`200 OK`):
*(When no active campaigns are currently running on this plan)*
```json
{
  "success": true,
  "message": "Boost package deleted successfully."
}
```

#### Protected Error Response (`422 Unprocessable Content`):
*(When advertisers currently have live campaigns using this package)*
```json
{
  "success": false,
  "message": "Cannot delete boost package with active campaigns. Please deactivate it instead."
}
```

---

### 3.7. Global Boosting Settings (`GET /api/admin/boost-settings`)

Retrieves global configuration values for the Custom Duration Slider, Newsfeed Ad Injection, and Campaign Pause Policy.

- **Method:** `GET`
- **URL:** `/api/admin/boost-settings`
- **Auth:** Required (`Bearer <admin_token>`)

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "message": "Boost system settings retrieved successfully.",
  "data": {
    "custom_slider": {
      "enabled": true,
      "channel": "web_only",
      "daily_rate": 3.00,
      "currency": "USD",
      "min_days": 3,
      "max_days": 60,
      "daily_estimated_impressions_min": 1000,
      "daily_estimated_impressions_max": 2000
    },
    "newsfeed_ads": {
      "enabled": true,
      "first_ad_slot": 4,
      "ad_interval": 8,
      "max_daily_impressions_per_user": 5
    },
    "pause_policy": {
      "max_days": 30,
      "first_reminder_days": 27,
      "final_reminder_days": 29
    },
    "new_release": {
      "hours": 24
    }
  }
}
```

---

### 3.8. Update Global Boosting Settings (`PATCH /api/admin/boost-settings`)

- **Method:** `PATCH` (or `PUT`)
- **URL:** `/api/admin/boost-settings`
- **Auth:** Required (`Bearer <admin_token>`)

#### Request Body Parameters:
```json
{
  "boost_custom_slider_enabled": true,
  "boost_custom_daily_rate": 3.50,
  "boost_custom_min_days": 3,
  "boost_custom_max_days": 60,
  "boost_newsfeed_ads_enabled": true,
  "boost_newsfeed_first_ad_slot": 4,
  "boost_newsfeed_ad_interval": 8,
  "boost_newsfeed_max_daily_impressions_per_user": 5,
  "boost_pause_policy_max_days": 30,
  "boost_pause_first_reminder_days": 27,
  "boost_pause_final_reminder_days": 29,
  "boost_new_release_hours": 24
}
```

#### Successful Response (`200 OK`):
```json
{
  "success": true,
  "message": "Boost system settings updated successfully.",
  "data": {
    "custom_slider": {
      "enabled": true,
      "daily_rate": 3.50,
      "min_days": 3,
      "max_days": 60
    },
    "newsfeed_ads": {
      "enabled": true,
      "first_ad_slot": 4,
      "ad_interval": 8,
      "max_daily_impressions_per_user": 5
    },
    "pause_policy": {
      "max_days": 30,
      "first_reminder_days": 27,
      "final_reminder_days": 29
    },
    "new_release": {
      "hours": 24
    }
  }
}
```

---

## 4. Frontend UI Components Mapping

For the frontend team implementing the Admin Boosting Dashboard:

### 4.1. Pricing Tier Cards (Top Showcase Section)
```typescript
interface BoostingPackage {
  id: number;
  name: string;
  duration_days: number;
  price: number;
  daily_price: number; // e.g. 2.50
  badge?: string; // e.g. "Most Popular Plan"
  promotion_highlight?: string; // e.g. "14 Days Extended Multi-Channel Reach"
  estimated_impressions?: string; // e.g. "15,000 – 25,000 Impressions"
  description?: string;
  features: string[]; // ["Dual Placement: Marketplace Feed + Main Newsfeed", "Featured \"Sponsored\" Badge Ribbon", "Fair-Play Exposure Rotation (Never Buried)", "Real-Time Verified Views & Click Analytics"]
  status: 'active' | 'inactive';
  priority_weight: number;
}
```

#### Calculated UI Savings Badge Formula:
```typescript
// If comparing against the Starter 7-day base rate:
const baseDailyRate = packages.find(p => p.duration_days === 7)?.daily_price || 2.85;

function getSavingsPercentage(packageItem: BoostingPackage): number | null {
  if (packageItem.duration_days <= 7) return null;
  const savings = ((baseDailyRate - packageItem.daily_price) / baseDailyRate) * 100;
  return savings > 0 ? Math.round(savings) : null;
}
// Growth Boost (14d): returns 12 -> displays "(Save 12%)"
// Best Value Boost (30d): returns 18 -> displays "(Save 18%)"
```

### 4.2. Create / Edit Form UI Fields Checklist
When building the Create/Edit form modal or page:
1. `name`: Text input (`Boosting Plan Name *`)
2. `target_entity`: Select dropdown (`Product`, `Job`, `Publication`, `Post`, `Universal`)
3. `duration_days`: Number input (`Duration (Days) *`)
4. `price`: Number input (`Price ($ USD) *`)
5. `description`: Textarea (`Package Description *`)
6. `badge`: Text input (`Badge Ribbon (Optional)` - e.g. "Most Popular Plan")
7. `promotion_highlight`: Text input (`Promotion Duration Highlight (Badge)` - e.g. "14 Days Extended Multi-Channel Reach")
8. `estimated_impressions`: Text input (`Estimated Impression`)
9. `status`: Select dropdown (`Active` / `Inactive`)
10. **Platform Guaranteed Features (Informational Only):** Render a read-only badge component showing the 4 features (No user input required).
11. **RevenueCat In-App Purchase Accordion:**
    - `revenuecat_store_identifier_ios` (iOS Store ID)
    - `revenuecat_store_identifier_android` (Android Store ID)
    - `revenuecat_entitlement_identifier` (Entitlement ID)

---

### 4.3. Guaranteed Platform Features Breakdown & UI Copy Guide
(ইউজার ও অ্যাডভার্টাইজারদের জন্য বিস্তারিত অর্থ ও ফ্রন্টএন্ড UI তে উপস্থাপনের নিয়ম)

প্রতিটি বুস্টিং প্যাকেজের সাথে প্ল্যাটফর্ম গ্যারান্টিড ৪টি ফিচার অন্তর্ভুক্ত থাকে। ইউজার যাতে প্যাকেজ কেনার সময় সুস্পষ্টভাবে বুঝতে পারে সে কী পাচ্ছে, ফ্রন্টএন্ড টিম নিচের শিরোনাম ও সাবটাইটেল ফরম্যাটটি ব্যবহার করবে:

| # | Feature Name | UI Title + Subtitle | সাধারণ ইউজারদের বোঝার জন্য বিস্তারিত অর্থ |
|---|--------------|---------------------|------------------------------------------|
| 1 | **Dual Placement** | **Dual Placement:** Marketplace Feed + Main Newsfeed | **মার্কেটপ্লেস ও মেইন নিউজফিড উভয় জায়গায় শো করবে:** পণ্যটি কেবল মার্কেটপ্লেসের ভেতর সীমাবদ্ধ থাকবে না, সাধারণ কমিউনিটি হোম নিউজফিডেও (সোশ্যাল টাইমলাইনে ৪ নম্বর পোস্ট ও প্রতি ৮ পোস্ট পরপর) প্রমোশনাল স্পন্সরড কার্ড হিসেবে ইউজারদের সামনে ভেসে উঠবে। |
| 2 | **Sponsored Badge** | **Featured Badge:** Eye-catching 'Sponsored' Ribbon | **আকর্ষণীয় 'Sponsored' ব্যাজ রিবন:** পণ্যের উপর উজ্জ্বল প্রিমিয়াম সায়ান রঙের স্পন্সরড রিবন থাকে। এতে সাধারণ পোস্ট থেকে আলাদা দেখায় এবং ক্রেতাদের বিশ্বাসযোগ্যতা ও ক্লিকে রূপান্তরের হার (CTR) বৃদ্ধি পায়। |
| 3 | **Fair-Play Rotation** | **Fair-Play Rotation:** Equal exposure, never buried | **সবার সমান সুযোগ – কখনো নিচে হারিয়ে যাবে না:** স্বয়ংক্রিয় রাউন্ড-রবিন অ্যালগরিদম নিশ্চিত করে যে যেকোনো প্যাকেজ নেওয়া বিক্রেতার পণ্যই রোটেশনের মাধ্যমে সমানভাবে সবার উপরে পৌঁছাবে। কোনো বড় ব্র্যান্ডের বিজ্ঞাপনের নিচে আপনার বিজ্ঞাপন চাপা পড়বে না। |
| 4 | **Live Analytics** | **Live Analytics:** Real-time verified views & clicks | **রিয়েল-টাইম ভেরিফায়েড ভিউ ও ক্লিক রিপোর্ট:** ড্যাশবোর্ডে একদম লাইভ দেখতে পাবেন কতজন প্রকৃত ইউজার আপনার পণ্যটি দেখেছেন, কতজন ক্লিক করেছেন এবং কতজন সরাসরি চ্যাটে বা লিডে পরিণত হয়েছেন। কোনো ফেক বা বট ক্লিক গণনা হয় না। |

#### Frontend UI Rendering Recommendation (Mobile App & Web Paywall):
```jsx
// কার্ডে দেখানোর সময় Title বোল্ড এবং Subtitle রেগুলার টেক্সটে দেখানো সবচেয়ে আকর্ষণীয় ও স্পষ্ট:
<ul className="space-y-2">
  <li>
    <span className="text-emerald-500 font-bold">✓</span>
    <span className="font-semibold text-slate-800"> Dual Placement:</span>
    <span className="text-slate-600"> Marketplace Feed + Main Newsfeed</span>
  </li>
  <li>
    <span className="text-emerald-500 font-bold">✓</span>
    <span className="font-semibold text-slate-800"> Featured Badge:</span>
    <span className="text-slate-600"> Eye-catching 'Sponsored' Ribbon</span>
  </li>
  <li>
    <span className="text-emerald-500 font-bold">✓</span>
    <span className="font-semibold text-slate-800"> Fair-Play Rotation:</span>
    <span className="text-slate-600"> Equal exposure, never buried</span>
  </li>
  <li>
    <span className="text-emerald-500 font-bold">✓</span>
    <span className="font-semibold text-slate-800"> Live Analytics:</span>
    <span className="text-slate-600"> Real-time verified views & clicks</span>
  </li>
</ul>
```

