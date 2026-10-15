# Mind Unite — Web Developer Advertising & Boosting Integration Guide
## Complete Frontend Integration Manual for React / Next.js / Vue & AI Coding Agents

---

### 1. Architectural Overview & Authentication

Mind Unite features a polymorphic advertising engine allowing industry users to boost their products. On the **Web Platform**, advertisers can choose between **Preset Tier Packages (7, 14, 30 Days)** OR an advertiser-controlled **Custom Duration Slider (3 to 60 Days)**.

#### Base URLs & HTTP Headers
All endpoints require standard JSON headers and authenticated JWT tokens for advertiser actions:
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <JWT_USER_ACCESS_TOKEN>
```
*(Public endpoints like feed browsing and impression/click tracking do not require authorization, but will automatically attach the user ID if the header is provided).*

---

### 2. TypeScript Data Interfaces

Copy and paste these type definitions into your frontend types file (e.g. `src/types/boosting.ts`):

```typescript
export interface BoostPackage {
  id: number;
  name: string;
  target_entity: 'product' | 'all';
  duration_days: number;
  price: number;
  estimated_impressions: string | null;
  badge: string | null;
  description: string | null;
  features: string[];
  status: 'active' | 'inactive';
}

export interface CustomSliderConfig {
  enabled: boolean;
  channel: 'web_only';
  daily_rate: number;
  currency: string;
  min_days: number;
  max_days: number;
  daily_estimated_impressions_min: number;
  daily_estimated_impressions_max: number;
}

export interface PackagesResponse {
  success: boolean;
  data: {
    stripe_public_key: string;
    preset_packages: BoostPackage[];
    custom_slider: CustomSliderConfig;
  };
}

export interface AdCampaign {
  id: number;
  boostable_type: string;
  boostable_id: number;
  boost_package_id: number | null;
  ad_type: 'product';
  network_type: 'psychology' | 'neuroscience' | 'both';
  placements: string[];
  amount_paid: number;
  currency: string;
  duration_days: number;
  starts_at: string;
  ends_at: string;
  paused_at: string | null;
  status: 'pending' | 'active' | 'paused' | 'completed' | 'cancelled';
  total_impressions: number;
  total_clicks: number;
}

export interface SponsoredFeedCard {
  id: string; // e.g. "sponsored_ad_14"
  is_sponsored: true;
  ad_campaign_id: number;
  ad_type: 'product';
  badge: string; // e.g. "Sponsored" | "Popular" | "Top Reach"
  title: string;
  description: string | null;
  image: string | null;
  price: number | null;
  destination_url: string | null;
  cta_text: string; // "View Product"
  company: {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    tagline: string | null;
  };
  product_details: {
    id: number;
    slug: string | null;
    section_id: number | null;
    category_id: number | null;
  };
  created_at: string;
}

export interface RegularNewsfeedPost {
  id: number;
  is_sponsored?: false;
  user: any;
  description: string;
  visibility: string;
  total_like: number;
  total_comment: number;
  liked_by_me: boolean;
  media: any[];
  industry: any | null;
  created_at: string;
}

export type FeedItem = RegularNewsfeedPost | SponsoredFeedCard;
```

---

### 3. Step-by-Step Advertiser Checkout Flow (Web)

```
┌────────────────────────────────────────────────────────┐
│ 1. Open Modal -> Fetch /api/industry/boost-packages    │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 2. Select Plan: Preset Package OR Custom Days Slider   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 3. Click "Send Verification Code" -> POST /send-otp    │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 4. Enter 4-digit OTP & Credit Card (Stripe Elements)   │
└───────────────────────────┬────────────────────────────┘
                            │
                            ▼
┌────────────────────────────────────────────────────────┐
│ 5. POST /boost/purchase -> Creates PaymentIntent       │
└───────────────────────────┬────────────────────────────┘
                            │
             ┌──────────────┴──────────────┐
             ▼                             ▼
   [requires_action = true]      [status = succeeded]
             │                             │
   Stripe 3DS Challenge Modal              ▼
   POST /boost/confirm              Instant Live! 🎉
```

#### Step 3.1: Fetch Packages & Slider Config
When the user clicks "Boost Product" on their product card or dashboard:
```http
GET /api/industry/boost-packages?target_entity=product
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "stripe_public_key": "pk_test_51P...",
    "preset_packages": [
      {
        "id": 1,
        "name": "7-Day Starter Boost",
        "target_entity": "product",
        "duration_days": 7,
        "price": 19.99,
        "estimated_impressions": "5,000 – 10,000 Views",
        "badge": "Popular",
        "description": "Essential visibility for new products.",
        "features": [
          "Top ranking in Marketplace Feed",
          "Sponsored card placement in Social Newsfeed",
          "Real-time impression & click analytics"
        ],
        "status": "active"
      },
      {
        "id": 2,
        "name": "14-Day Growth Boost",
        "target_entity": "product",
        "duration_days": 14,
        "price": 34.99,
        "estimated_impressions": "15,000 – 25,000 Views",
        "badge": "Best Value",
        "features": [...],
        "status": "active"
      },
      {
        "id": 3,
        "name": "30-Day Pro Boost",
        "target_entity": "product",
        "duration_days": 30,
        "price": 69.99,
        "estimated_impressions": "40,000 – 60,000 Views",
        "badge": "Top Reach",
        "features": [...],
        "status": "active"
      }
    ],
    "custom_slider": {
      "enabled": true,
      "channel": "web_only",
      "daily_rate": 3.00,
      "currency": "USD",
      "min_days": 3,
      "max_days": 60,
      "daily_estimated_impressions_min": 700,
      "daily_estimated_impressions_max": 1400
    }
  }
}
```

#### Step 3.2: Custom Duration Slider Calculation
On the Web UI, advertisers can toggle to **"Custom Duration"**:
- **Slider Range:** `<input type="range" min={custom_slider.min_days} max={custom_slider.max_days} value={days} />`
- **Dynamic Price Display:** `TotalPrice = days * custom_slider.daily_rate`
- **Estimated Impressions Display:** `${days * 700} – ${days * 1400} Views`

#### Step 3.3: Request Email OTP Code
Before charging the card, the user clicks "Send Verification Code". A 4-digit code is dispatched to their email:

```http
POST /api/industry/products/{productId}/boost/send-otp
```
**Payload for Preset Package:**
```json
{
  "checkout_type": "stripe",
  "boost_package_id": 1
}
```
**Payload for Custom Duration Slider:**
```json
{
  "checkout_type": "stripe",
  "custom_days": 15
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "OTP verification code sent to your registered email.",
  "data": {
    "email": "user@example.com",
    "expires_in_minutes": 3
  }
}
```
*(Rate limiting: If requested again within 3 minutes, returns `429 Too Many Requests`).*

#### Step 3.4: Charge Payment via Stripe Elements & Verify OTP
Use `@stripe/react-stripe-js` to tokenize the card:
```typescript
const { paymentMethod, error } = await stripe.createPaymentMethod({
  type: 'card',
  card: elements.getElement(CardElement)!,
});
```
Then send the `payment_method.id` and the user-entered 4-digit `otp`:

```http
POST /api/industry/products/{productId}/boost/purchase
```
**Payload for Preset Package:**
```json
{
  "checkout_type": "stripe",
  "boost_package_id": 1,
  "payment_method": "pm_1Pxxxxxx",
  "otp": "1234",
  "network_type": "both",
  "placements": ["marketplace", "newsfeed"]
}
```
**Payload for Custom Duration Slider:**
```json
{
  "checkout_type": "stripe",
  "custom_days": 15,
  "payment_method": "pm_1Pxxxxxx",
  "otp": "1234",
  "network_type": "both",
  "placements": ["marketplace", "newsfeed"]
}
```

#### Step 3.5: Handling Responses (Immediate Success vs 3D Secure Challenge)

**Case A: Immediate Success (200 OK):**
```json
{
  "success": true,
  "message": "Product boosted successfully.",
  "data": {
    "campaign": {
      "id": 14,
      "boostable_id": 42,
      "ad_type": "product",
      "status": "active",
      "amount_paid": "19.99",
      "duration_days": 7,
      "starts_at": "2026-10-15T15:00:00.000000Z",
      "ends_at": "2026-10-22T15:00:00.000000Z",
      "total_impressions": 0,
      "total_clicks": 0
    }
  }
}
```
*(Close modal, show celebration toast, update product card with "Sponsored" badge).*

**Case B: 3D Secure Bank Challenge (`requires_action: true`):**
```json
{
  "success": true,
  "requires_action": true,
  "client_secret": "pi_3Pxxxx_secret_yyyy",
  "payment_status": "requires_action"
}
```
**Frontend 3DS Handling:**
```typescript
if (response.requires_action && response.client_secret) {
  // Trigger Stripe bank modal popup
  const result = await stripe.handleCardAction(response.client_secret);
  
  if (result.error) {
    alert(result.error.message);
  } else {
    // Confirm on backend
    const confirmRes = await api.post(`/api/industry/products/${productId}/boost/confirm`, {
      payment_intent_id: result.paymentIntent.id
    });
    // Campaign is now active!
  }
}
```

---

### 4. Advertiser Campaign Controls (Pause & Resume with Clock Freeze)

Advertisers can pause active boosts (e.g. out of inventory) and resume them later with **zero lost time**.

#### 4.1 Get Campaign History & Metrics
```http
GET /api/industry/products/{productId}/boost/campaigns
```
**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "product_id": 42,
    "product_name": "BCI Wireless Headset Pro",
    "is_boosted": true,
    "boost_expires_at": "2026-10-22T15:00:00.000000Z",
    "active_campaign": {
      "id": 14,
      "status": "active",
      "duration_days": 7,
      "ends_at": "2026-10-22T15:00:00.000000Z",
      "total_impressions": 342,
      "total_clicks": 28
    },
    "campaigns": [ ... ]
  }
}
```

#### 4.2 Pause Campaign (Clock Freeze)
```http
POST /api/industry/boost/campaigns/{campaignId}/pause
```
**Response (200 OK):**
```json
{
  "success": true,
  "message": "Campaign paused successfully. Remaining time is frozen.",
  "data": {
    "campaign": {
      "id": 14,
      "status": "paused",
      "paused_at": "2026-10-15T15:30:00.000000Z"
    }
  }
}
```
*(Status changes to `paused`, remaining time is frozen, product disappears from boosted feed rotations).*

#### 4.3 Resume Campaign (Clock Extension)
```http
POST /api/industry/boost/campaigns/{campaignId}/resume
```
**Response (200 OK):**
```json
{
  "success": true,
  "message": "Campaign resumed successfully. Expiration extended by 2 days, 4 hours.",
  "data": {
    "campaign": {
      "id": 14,
      "status": "active",
      "paused_at": null,
      "ends_at": "2026-10-24T19:30:00.000000Z"
    }
  }
}
```

---

### 5. Newsfeed Timeline & Sponsored Card Integration

In the main social timeline (`GET /api/newsfeed`), sponsored product cards are automatically woven into the feed at slot 4 (and slot 13 for long feeds).

#### 5.1 React / Next.js Feed Item Renderer
```tsx
import React, { useEffect, useRef } from 'react';

export const NewsfeedList: React.FC<{ items: FeedItem[] }> = ({ items }) => {
  return (
    <div className="flex flex-col gap-4">
      {items.map((item) => {
        if (item.is_sponsored) {
          return <SponsoredProductCard key={item.id} ad={item} />;
        }
        return <RegularPostCard key={item.id} post={item} />;
      })}
    </div>
  );
};

export const SponsoredProductCard: React.FC<{ ad: SponsoredFeedCard }> = ({ ad }) => {
  const cardRef = useRef<HTMLDivElement>(null);

  // IAB Verified Viewable Impression Tracker (50% area visible for >= 1 second)
  useEffect(() => {
    let timer: NodeJS.Timeout;
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            timer = setTimeout(() => {
              // Fire verified impression batch
              fetch('/api/advertisements/impressions', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  campaign_ids: [ad.ad_campaign_id],
                  placement: 'newsfeed',
                }),
              });
            }, 1000);
          } else {
            clearTimeout(timer);
          }
        });
      },
      { threshold: 0.5 } // 50% visibility
    );

    if (cardRef.current) observer.observe(cardRef.current);
    return () => {
      observer.disconnect();
      clearTimeout(timer);
    };
  }, [ad.ad_campaign_id]);

  const handleCardClick = async () => {
    try {
      const res = await fetch(`/api/advertisements/${ad.ad_campaign_id}/click`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ placement: 'newsfeed' }),
      });
      const json = await res.json();
      if (json.data?.destination_url) {
        window.open(json.data.destination_url, '_blank', 'noopener,noreferrer');
      }
    } catch (e) {
      if (ad.destination_url) window.open(ad.destination_url, '_blank');
    }
  };

  return (
    <div
      ref={cardRef}
      className="p-5 rounded-2xl border border-teal-100 bg-white shadow-sm hover:shadow-md transition-shadow relative"
    >
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-3">
          {ad.company.logo && (
            <img src={ad.company.logo} alt={ad.company.name} className="w-10 h-10 rounded-full object-cover" />
          )}
          <div>
            <h4 className="font-semibold text-slate-800 text-sm">{ad.company.name}</h4>
            <p className="text-xs text-slate-500">{ad.company.tagline || 'Promoted'}</p>
          </div>
        </div>
        <span className="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-teal-50 text-teal-700 border border-teal-200">
          {ad.badge || 'Sponsored'}
        </span>
      </div>

      {ad.image && (
        <img
          src={ad.image}
          alt={ad.title}
          className="w-full h-48 object-cover rounded-xl mb-3 cursor-pointer"
          onClick={handleCardClick}
        />
      )}

      <h3 className="font-bold text-slate-900 text-base mb-1">{ad.title}</h3>
      {ad.description && <p className="text-sm text-slate-600 line-clamp-2 mb-3">{ad.description}</p>}

      <div className="flex items-center justify-between pt-2 border-t border-slate-100">
        {ad.price ? (
          <span className="text-lg font-bold text-teal-800">${Number(ad.price).toFixed(2)}</span>
        ) : (
          <span />
        )}
        <button
          onClick={handleCardClick}
          className="px-4 py-2 bg-[#043940] hover:bg-[#008b8b] text-white text-xs font-bold rounded-lg transition-colors flex items-center gap-1.5"
        >
          {ad.cta_text || 'View Product'} &rarr;
        </button>
      </div>
    </div>
  );
};
```

---

### 6. Universal Tracking Endpoints

| Action | Method & Endpoint | Payload | Response Data |
| :--- | :--- | :--- | :--- |
| **Batch Impression** | `POST /api/advertisements/impressions` | `{ "campaign_ids": [14], "placement": "newsfeed" }` | `{ "success": true, "recorded_count": 1 }` |
| **Ad Click** | `POST /api/advertisements/{campaignId}/click` | `{ "placement": "newsfeed" }` | `{ "data": { "destination_url": "https://...", "total_clicks": 29 } }` |

---

### 7. Admin Dashboard Settings API (For Admin Portal Developers)

Admins can adjust the daily boost price and slider bounds without touching code:
- **Fetch Settings:** `GET /api/admin/boost-settings`
- **Update Settings:** `PATCH /api/admin/boost-settings`
  ```json
  {
    "daily_rate": 4.00,
    "min_days": 3,
    "max_days": 60,
    "new_release_hours": 24,
    "pause_max_days": 30
  }
  ```
- **Manage Packages:** `GET`, `POST`, `PATCH`, `DELETE` at `/api/admin/boost-packages`.
