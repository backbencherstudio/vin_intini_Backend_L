# MindUnite — Web Integration Guide: Newsfeed Product Ads
## For Web Frontend Developers (React, Next.js, Vue, Tailwind CSS)
### Version 2.0 • Production Ready

---

## 📌 Executive Summary

Your web application's social newsfeed (`GET /api/newsfeed`) now delivers high-converting, native **Sponsored Product Ads** interspersed among organic user posts. This guide provides web frontend developers with everything needed to render the ads matching the verified test preview (`/newsfeed-preview`), track verified impressions and clicks, and handle user interactions seamlessly.

---

## 🔒 Zero Secret Data Policy (Security & Privacy)

The backend guarantees that **NO sensitive or private data** is exposed in the API payload:
- ❌ No billing or payment details (Stripe IDs, payment methods, transaction receipts).
- ❌ No campaign financial data (CPM, CPC, total ad spend, budget limits).
- ❌ No internal admin notes, passwords, or account recovery hashes.
- ❌ No private contact details (unless explicitly provided as public product contact info).
- ✅ Only sanitized, public-facing creative fields are served (`title`, `description`, `image`, `badge`, `industry`, `cta_text`, `destination_url`, `likes_count`, `views_count`).

---

## 1. How Ads are Injected into the Newsfeed Stream

When the web client calls `GET /api/newsfeed`:
- The backend ad engine injects sponsored product cards into predefined slots:
  - **First Ad Slot:** Position #4 (Zero-indexed 3).
  - **Ad Interval:** Every 8 posts (Positions #4, #12, #20...).
  - **Fair-Play Pacing:** Distributes exposure evenly among active campaigns.
  - **Deduplication:** Prevents showing the same campaign twice in the same batch.

### 1.1 Query Parameters Reference (`GET /api/newsfeed`)

| Parameter | Type | Required? | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `network_type` | `string` | Optional | Profile network | Target network ads: `psychology` or `neuroscience` |
| `per_page` | `integer` | Optional | `20` | Number of posts per page batch (also accepts `limit`) |
| `cursor` | `string` | Optional | `null` | Cursor token for page 2, 3, etc. from `pagination.next_cursor` |
| `seen_campaign_ids` | `string` | Optional | `null` | Comma-separated list of already rendered ad IDs (e.g., `1,4`) to prevent duplicate ads |

### 1.2 Detection in the Post Loop
In your newsfeed list renderer, check `item.is_sponsored === true`:

```tsx
// React / Next.js Feed Rendering Loop
{feedItems.map((item) => {
  if (item.is_sponsored === true) {
    return (
      <SponsoredProductAdCard 
        key={`ad-${item.ad_campaign_id}`} 
        ad={item} 
      />
    );
  }
  return <StandardPostCard key={`post-${item.id}`} post={item} />;
})}
```

---

## 2. API Response & Data Payload Specification

When `item.is_sponsored === true`, the object follows this schema:

```json
{
  "id": "sponsored_ad_3",
  "is_sponsored": true,
  "ad_campaign_id": 3,
  "ad_type": "product",
  "badge": "Sponsored",
  "product_id": 7,
  "product_code": "10EBPW",
  "product_name": "BioPac MP160 System",
  "title": "BioPac MP160 System",
  "description": "High resolution functional brain imaging with real-time analysis engineered for neuroscience research.",
  "image": "products/KvdWTWTkVI23GGr9vmk39Hkx8GhMfpGsbECMiWDn.webp",
  "image_url": "http://127.0.0.1:8000/storage/products/KvdWTWTkVI23GGr9vmk39Hkx8GhMfpGsbECMiWDn.webp",
  "price": "$1,499.00",
  "destination_url": "https://www.biopac.com/product/mp160",
  "destination_domain": "biopac.com",
  "cta_text": "View Product",
  "likes_count": 32,
  "views_count": 92,
  "is_liked": false,
  "industry": {
    "id": 10,
    "is_following": false,
    "logo": "http://127.0.0.1:8000/storage/industries/logos/logo.jpg",
    "name": "Softvence Delta",
    "slug": "softvence-delta",
    "tagline": "Innovative Healthcare Technologies"
  },
  "section_name": "Equipment & Devices",
  "category_name": "Diagnostic Tools",
  "tags": ["Biotechnology", "Neuroscience", "Research"],
  "is_new_release": false,
  "created_at": "2026-10-24T04:22:55.000000Z"
}
```

### Field Definitions

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | `string` | Unique card DOM identifier (e.g., `sponsored_ad_3`) |
| `is_sponsored` | `boolean` | Flag indicating this is an ad card (`true`) |
| `ad_campaign_id` | `number` | ID for tracking impressions and clicks |
| `badge` | `string` | Label to display on ribbon (Default: `"Sponsored"`) |
| `product_id` | `number` | Internal database product ID for like toggling |
| `product_code` | `string` | Public SKU / Product code (e.g., `"10EBPW"`) |
| `product_name` | `string` | Full product title |
| `description` | `string` | Promotional post caption |
| `image_url` | `string` | Absolute URL to ad banner image |
| `price` | `string` | Formatted price string or `null` |
| `destination_url` | `string` | External target link for advertiser |
| `destination_domain` | `string` | Clean domain name for preview link (e.g., `"biopac.com"`) |
| `cta_text` | `string` | Action button text (e.g., `"View Product"`, `"Learn more"`) |
| `likes_count` | `number` | Public like tally |
| `views_count` | `number` | Public view count |
| `is_liked` | `boolean` | Whether current logged-in user has liked this product |
| `industry.id` | `number` | Advertiser industry/company ID |
| `industry.is_following` | `boolean` | Whether logged-in user follows this industry |
| `industry.name` | `string` | Advertiser industry/company name |
| `industry.logo` | `string` | Full logo URL |
| `industry.slug` | `string` | Industry profile URL slug |
| `industry.tagline` | `string` | Short company bio / tagline |
| `section_name` | `string` | Department section name (e.g., `"Equipment & Devices"`) |
| `category_name` | `string` | Sub-category name (e.g., `"Diagnostic Tools"`) |
| `tags` | `array` | String array of tags |
| `is_new_release` | `boolean` | `true` if published within the last 24 hours |

---

## 3. UI/UX Design Specification (Mirroring the Test Preview)

The ad card design matches the professional LinkedIn/MindUnite feed layout tested in `/newsfeed-preview`:

### 3.1 Card Components Architecture
1. **Author Header Row:**
   - Company Avatar (48x48px rounded-lg) with fallback to company initials.
   - Company Name (font-semibold text-slate-900) + Company Tagline (text-xs text-slate-500).
   - "Promoted • 🌐" indicator badge.
   - Follow Button (+ Follow) and More Options (...).
2. **Post Body Caption:**
   - Truncated at 180 characters by default.
   - "...see more" blue link expands the full caption text in-place.
3. **Hero Ad Creative Banner:**
   - Edge-to-edge aspect ratio (16:9 or 1.91:1) with smooth cover fit.
   - Clickable: redirects via tracking API to destination URL.
4. **Signature Link Preview Box (Flush below banner):**
   - Background: `#f8fafc` (slate-50) with subtle top border.
   - Domain row: `destination_domain` (e.g., `biopac.com`) + `ID: #10EBPW` pill + `🔥 New` badge (if `is_new_release`).
   - Title: Bold `product_name` (line-clamp-1).
   - Breadcrumb: `section_name` • `category_name` (cyan highlight).
   - Tags: Chip pills `#Biotech`, `#Neuroscience`.
   - CTA Button: Prominent button on the right with `cta_text` (e.g., "View Product ➔").
5. **Engagement & Stats Bar:**
   - Left: Thumbs-up 👍 icon + Like count (e.g., "32 likes").
   - Right: Eye 👁️ icon + Total views count (e.g., "92 views").
6. **Action Toolbar:**
   - **Like Button:** Toggles active state (`Liked` in cyan/blue with filled icon vs `Like` outline).
   - **Details Button:** Opens a modal or product details drawer.

---

## 4. Impression & Click Tracking Implementation

### 4.1 IAB Viewability Impression Reporting
An impression must ONLY be recorded when the ad is **at least 50% visible in the viewport for at least 1 continuous second**:

- **Endpoint:** `POST /api/advertisements/impressions`
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "campaign_ids": [3],
    "placement": "newsfeed"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "recorded_impressions": 1
  }
  ```

### 4.2 Click Tracking & Redirection
When the user clicks the banner, title, or CTA button:
- **Endpoint:** `POST /api/advertisements/{ad_campaign_id}/click`
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "placement": "newsfeed"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "message": "Click recorded successfully.",
    "debounced": false,
    "data": {
      "ad_campaign_id": 3,
      "destination_url": "https://www.biopac.com/product/mp160"
    }
  }
  ```
- **Web Action:** Open `data.destination_url` in a new browser tab (`window.open(url, '_blank', 'noopener,noreferrer')`).

### 4.3 Like Interaction
When the user clicks the Like button:
- **Endpoint:** `POST /api/industry/products/{product_id}/like`
- **Headers:** `Authorization: Bearer <USER_JWT_TOKEN>`
- **Response:**
  ```json
  {
    "success": true,
    "message": "Product liked successfully.",
    "data": {
      "is_liked": true,
      "likes_count": 33
    }
  }
  ```

---

## 5. Infinite Scroll Deduplication

When the user scrolls and loads page 2, 3, etc.:
1. Collect all `ad_campaign_id`s already displayed on screen.
2. Send them as `seen_campaign_ids` in the newsfeed request query:
   ```http
   GET /api/newsfeed?cursor={cursor}&seen_campaign_ids=3,5
   ```
3. The ad engine automatically skips those campaigns so users never see duplicate ads during their session.

---

## 6. Complete Production React / Next.js Component

```tsx
import React, { useState, useEffect, useRef } from 'react';

export interface IndustryData {
  id?: number;
  is_following?: boolean;
  name?: string;
  slug?: string;
  logo?: string;
  tagline?: string;
}

export interface SponsoredAdItem {
  id: string;
  is_sponsored: boolean;
  ad_campaign_id: number;
  ad_type: string;
  badge: string;
  product_id: number;
  product_code?: string;
  product_name: string;
  title: string;
  description: string;
  image_url: string;
  price?: string;
  destination_url: string;
  destination_domain?: string;
  cta_text: string;
  likes_count: number;
  views_count: number;
  is_liked: boolean;
  industry?: IndustryData;
  section_name?: string;
  category_name?: string;
  tags?: string[];
  is_new_release?: boolean;
}

interface Props {
  ad: SponsoredAdItem;
  userToken?: string;
  onOpenDetails?: (product: SponsoredAdItem) => void;
}

export const SponsoredProductAdCard: React.FC<Props> = ({ ad, userToken, onOpenDetails }) => {
  const industry = ad.industry;
  const [isExpanded, setIsExpanded] = useState(false);
  const [isLiked, setIsLiked] = useState(ad.is_liked);
  const [likesCount, setLikesCount] = useState(ad.likes_count);
  const [isLiking, setIsLiking] = useState(false);
  const [isFollowing, setIsFollowing] = useState(industry?.is_following ?? false);
  const [isFollowingLoading, setIsFollowingLoading] = useState(false);

  const cardRef = useRef<HTMLDivElement>(null);
  const impressionSentRef = useRef(false);
  const visibilityTimerRef = useRef<NodeJS.Timeout | null>(null);

  // 1. IAB Viewable Impression Observer (50% in view for >= 1s)
  useEffect(() => {
    if (!('IntersectionObserver' in window) || impressionSentRef.current) return;

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !impressionSentRef.current) {
          visibilityTimerRef.current = setTimeout(() => {
            impressionSentRef.current = true;
            fetch('/api/advertisements/impressions', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
              },
              body: JSON.stringify({
                campaign_ids: [ad.ad_campaign_id],
                placement: 'newsfeed',
              }),
            }).catch(() => {});
          }, 1000); // 1-second viewable requirement
        } else {
          if (visibilityTimerRef.current) {
            clearTimeout(visibilityTimerRef.current);
            visibilityTimerRef.current = null;
          }
        }
      },
      { threshold: 0.5 }
    );

    if (cardRef.current) observer.observe(cardRef.current);

    return () => {
      observer.disconnect();
      if (visibilityTimerRef.current) clearTimeout(visibilityTimerRef.current);
    };
  }, [ad.ad_campaign_id, userToken]);

  // 2. Click Tracking & Redirection
  const handleClick = async (e: React.MouseEvent) => {
    e.preventDefault();
    try {
      const res = await fetch(`/api/advertisements/${ad.ad_campaign_id}/click`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
        },
        body: JSON.stringify({ placement: 'newsfeed' }),
      });
      const data = await res.json();
      const targetUrl = data.data?.destination_url || ad.destination_url;
      if (targetUrl) {
        window.open(targetUrl, '_blank', 'noopener,noreferrer');
      }
    } catch {
      if (ad.destination_url) {
        window.open(ad.destination_url, '_blank', 'noopener,noreferrer');
      }
    }
  };

  // 3. Like Toggle
  const handleLikeToggle = async (e: React.MouseEvent) => {
    e.stopPropagation();
    if (isLiking || !userToken) return;

    // Optimistic UI update
    const previousLiked = isLiked;
    const previousCount = likesCount;
    setIsLiked(!previousLiked);
    setLikesCount(previousLiked ? previousCount - 1 : previousCount + 1);
    setIsLiking(true);

    try {
      const res = await fetch(`/api/industry/products/${ad.product_id}/like`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          Authorization: `Bearer ${userToken}`,
        },
      });
      const data = await res.json();
      if (data.success && data.data) {
        setIsLiked(data.data.is_liked);
        setLikesCount(data.data.likes_count);
      }
    } catch {
      // Revert on error
      setIsLiked(previousLiked);
      setLikesCount(previousCount);
    } finally {
      setIsLiking(false);
    }
  };

  const captionText = ad.description || ad.title;
  const shouldTruncate = captionText.length > 180;
  const companyInitials = (industry?.name || 'MU').substring(0, 2).toUpperCase();

  return (
    <article
      ref={cardRef}
      className="bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow overflow-hidden my-4"
    >
      {/* Header Row */}
      <div className="p-4 flex items-center justify-between">
        <div className="flex items-center gap-3 cursor-pointer" onClick={handleClick}>
          <div className="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center font-bold text-cyan-600">
            {industry?.logo ? (
              <img src={industry.logo} alt={industry.name} className="w-full h-full object-cover" />
            ) : (
              <span>{companyInitials}</span>
            )}
          </div>
          <div>
            <h4 className="text-sm font-bold text-slate-900 hover:text-cyan-600 transition-colors">
              {industry?.name || 'MindUnite Partner'}
            </h4>
            <p className="text-xs text-slate-500 line-clamp-1">{industry?.tagline || 'Innovative Solutions'}</p>
            <div className="flex items-center gap-1.5 text-[11px] text-slate-400 mt-0.5">
              <span className="font-semibold text-slate-600">{ad.badge || 'Sponsored'}</span>
              <span>•</span>
              <span>🌐 Promoted</span>
            </div>
          </div>
        </div>
        <button
          type="button"
          onClick={async (e) => {
            e.stopPropagation();
            if (!industry?.id || isFollowingLoading || !userToken) return;
            const prev = isFollowing;
            setIsFollowing(!prev);
            setIsFollowingLoading(true);
            try {
              await fetch(`/api/industry/${industry.id}/follow`, {
                method: 'POST',
                headers: { Authorization: `Bearer ${userToken}`, Accept: 'application/json' },
              });
            } catch {
              setIsFollowing(prev);
            } finally {
              setIsFollowingLoading(false);
            }
          }}
          className={`text-xs font-bold px-3 py-1.5 rounded-full transition-colors ${
            isFollowing ? 'text-slate-700 bg-slate-100 hover:bg-slate-200' : 'text-cyan-700 bg-cyan-50 hover:bg-cyan-100'
          }`}
        >
          {isFollowing ? 'Following' : '+ Follow'}
        </button>
      </div>

      {/* Post Caption */}
      <div className="px-4 pb-3 text-sm text-slate-800 leading-relaxed">
        <span>{shouldTruncate && !isExpanded ? `${captionText.substring(0, 180)}...` : captionText}</span>
        {shouldTruncate && (
          <button
            onClick={() => setIsExpanded(!isExpanded)}
            className="text-cyan-600 font-semibold ml-1.5 hover:underline text-xs"
          >
            {isExpanded ? 'see less' : 'see more'}
          </button>
        )}
      </div>

      {/* Hero Ad Creative Banner */}
      <div className="cursor-pointer bg-slate-900 max-h-[380px] overflow-hidden flex items-center justify-center" onClick={handleClick}>
        <img
          src={ad.image_url}
          alt={ad.product_name}
          className="w-full h-auto object-cover max-h-[380px] hover:scale-[1.01] transition-transform duration-300"
        />
      </div>

      {/* Signature Link Preview Box */}
      <div
        className="p-3.5 bg-slate-50/90 border-t border-b border-slate-200/80 flex items-center justify-between cursor-pointer hover:bg-slate-100/80 transition-colors"
        onClick={handleClick}
      >
        <div className="flex-1 pr-3">
          <div className="flex items-center gap-2 text-[11px] text-slate-500 mb-1">
            <span className="font-medium">{ad.destination_domain || 'mindunite.com'}</span>
            {ad.product_code && (
              <span className="bg-slate-200/80 text-slate-700 px-1.5 py-0.5 rounded text-[10px] font-mono">
                ID: #{ad.product_code}
              </span>
            )}
            {ad.is_new_release && (
              <span className="bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded text-[10px] font-bold">
                🔥 New
              </span>
            )}
          </div>
          <h5 className="font-bold text-slate-900 text-sm line-clamp-1">{ad.product_name}</h5>
          <div className="text-xs text-slate-500 mt-0.5">
            <span>{ad.section_name}</span>
            {ad.category_name && <span className="text-cyan-700 font-semibold"> • {ad.category_name}</span>}
          </div>
          {ad.tags && ad.tags.length > 0 && (
            <div className="flex gap-1.5 mt-1.5">
              {ad.tags.slice(0, 3).map((tag, idx) => (
                <span key={idx} className="text-[11px] text-cyan-600 bg-cyan-50 px-2 py-0.5 rounded-full font-medium">
                  #{tag}
                </span>
              ))}
            </div>
          )}
        </div>
        <button
          type="button"
          onClick={handleClick}
          className="shrink-0 bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl shadow-sm transition-all"
        >
          {ad.cta_text || 'View Product'} ➔
        </button>
      </div>

      {/* Engagement Stats Row */}
      <div className="px-4 py-2 flex items-center justify-between text-xs text-slate-500 border-b border-slate-100">
        <span className="flex items-center gap-1.5">
          <span className="w-4 h-4 rounded-full bg-cyan-600 text-white flex items-center justify-center text-[10px]">👍</span>
          <strong className="text-slate-700">{likesCount}</strong> likes
        </span>
        <span>{ad.views_count} views</span>
      </div>

      {/* Action Toolbar */}
      <div className="px-2 py-1.5 flex items-center justify-around">
        <button
          type="button"
          onClick={handleLikeToggle}
          className={`flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold transition-colors ${
            isLiked ? 'text-cyan-600 bg-cyan-50/60' : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          <span>👍</span>
          <span>{isLiked ? 'Liked' : 'Like'}</span>
        </button>
        <button
          type="button"
          onClick={() => onOpenDetails?.(ad)}
          className="flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors"
        >
          <span>📄</span>
          <span>Details</span>
        </button>
      </div>
    </article>
  );
};
```
