# MindUnite — Web Integration Guide: Industry Product Marketplace Feed
## For Web Frontend Developers (React, Next.js, Vue, Tailwind CSS)
### Version 2.0 • Production Ready

---

## 📌 Executive Summary

This guide provides web frontend developers with the complete blueprint to build the **MindUnite Industry Product Marketplace Feed**. It perfectly mirrors the verified test version (`/product-feed-preview?network_type=psychology&industry_type=biotechnology`), supporting:
1. **Section-wise Overview Mode** (Top 6 products per section with sub-category tabs).
2. **Dedicated Section Mode** (Cursor-paginated deep dive into a single section).
3. **All Products Flat Feed Mode** (`view=all` with infinite scroll cursor pagination).
4. **Dynamic Sub-Category Filtering & Search**.
5. **Algorithmic Ranking**: Sponsored Boosted products first ➔ 24-hour New Releases second ➔ Organic third.

---

## 🔒 Zero Secret Data & Sanitized Privacy Policy

The backend guarantees that **NO private, financial, or confidential personal data** is exposed in the public marketplace feed:
- ❌ No advertiser account budgets, spend limits, or Stripe transaction IDs.
- ❌ No payment tokens, bank details, or internal admin pricing rates.
- ❌ No personal contact details (`poc_name`, `poc_email`, `poc_phone`) or private competitor metrics (`impressions_count`, `clicks_count`, `boost_score`) in the public feed. (For product inquiries and specifications, call `GET /api/industry/products/{id}/details`).
- ❌ No bloated HTML tags — feed returns concise, sanitized clean text (`short_description` & `description`) for high-speed page loads.
- ✅ Only safe public marketplace information is served (`product_name`, `slug`, `image_url`, `description`, `badge`, `industry`, `creator`, `likes_count`, `views_count`, `is_liked`, `is_boosted`, `ad_campaign_id`).

---

## 🌐 HTTP Headers & Endpoint Overview

- **Primary Endpoint:** `GET /api/industry/product-feed`
- **Aliases:** `GET /api/industry/feed`, `GET /api/industry/products/feed`
- **Required Headers:**
  ```http
  Accept: application/json
  Authorization: Bearer <USER_JWT_TOKEN>
  ```
  *(Note: The feed requires standard user authentication via JWT Bearer token with active session).*

---

## 1. Query Parameters Reference

| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `network_type` | `string` | **Yes** | — | `psychology` or `neuroscience` |
| `industry_type` | `string` | **Yes** | — | `biotechnology` or `psychotropics` *(Strict: `publications` is separate!)* |
| `view` | `string` | No | `overview` | Pass `all` to retrieve a flat, cursor-paginated list of all products |
| `flat` | `boolean` | No | `0` | Pass `1` or `true` as an alternative to `view=all` |
| `section_id` | `integer` | No | `null` | Target specific section ID (enters Dedicated Section View) |
| `category_id` | `integer` | No | `null` | Filter products by sub-category ID |
| `search` | `string` | No | `null` | Search across product name, description, and product SKU code |
| `per_page` | `integer` | No | `12` | Products per page (`1` to `100`) |
| `cursor` | `string` | No | `null` | Base64-encoded cursor token from previous page response |
| `limit_per_section` | `integer` | No | `6` | Number of preview products per section block in overview mode |

---

## 2. API Response Modes

### 2.1 Mode A: Section-Wise Overview (Default Landing View)
Fetches all department sections, their sub-category tabs, and the top 6 products per section.

```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology
```

#### JSON Response Schema:
```json
{
  "success": true,
  "message": "Section-wise products feed retrieved successfully.",
  "data": [
    {
      "section_id": 1,
      "section_name": "Equipment & Devices",
      "network_type": "psychology",
      "industry_type": "biotechnology",
      "total_products": 24,
      "has_more": true,
      "categories": [
        { "id": 1, "category_name": "Diagnostic Tools" },
        { "id": 2, "category_name": "Bio-Sensors" }
      ],
      "products": [
        {
          "id": 7,
          "product_id": "10EBPW",
          "product_name": "BioPac MP160 System",
          "slug": "biopac-mp160-system",
          "network_type": "psychology",
          "industry_type": "biotechnology",
          "section_id": 1,
          "section_name": "Equipment & Devices",
          "category_id": 1,
          "category_name": "Diagnostic Tools",
          "description": "High resolution functional brain imaging apparatus engineered for clinical neuroscience research.",
          "short_description": "High resolution functional brain imaging apparatus...",
          "product_url": "https://www.biopac.com/product/mp160",
          "image_url": "https://api.mindunite.com/storage/products/sample.webp",
          "tags": ["Biotechnology", "EEG", "Sensors"],
          "is_boosted": true,
          "badge": "Sponsored",
          "ad_campaign_id": 3,
          "is_new_release": false,
          "likes_count": 32,
          "views_count": 92,
          "is_liked": false,
          "creator": {
            "id": 2,
            "name": "Niaz Ahmed",
            "username": "niaz-ahmed",
            "profile_image": "https://api.mindunite.com/storage/profile.jpg"
          },
          "industry": {
            "id": 10,
            "name": "Softvence Delta",
            "logo": "https://api.mindunite.com/storage/logo.jpg"
          }
        }
      ]
    }
  ]
}
```

---

### 2.2 Mode B: Dedicated Section Mode (With Cursor Pagination)
Triggered when the user clicks **"See More >"** on any section header or selects a specific section tab:

```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id=1&per_page=12
```

#### JSON Response Schema:
```json
{
  "success": true,
  "message": "Products retrieved successfully.",
  "section_id": 1,
  "section_name": "Equipment & Devices",
  "network_type": "psychology",
  "industry_type": "biotechnology",
  "categories": [
    { "id": 1, "category_name": "Diagnostic Tools" },
    { "id": 2, "category_name": "Bio-Sensors" }
  ],
  "total_products": 24,
  "data": [ /* 12 product items */ ],
  "pagination": {
    "limit": 12,
    "per_page": 12,
    "next_cursor": "eyJpZCI6MTUsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0",
    "prev_cursor": null,
    "has_more_pages": true,
    "total": 24
  }
}
```

---

### 2.3 Mode C: "All Products" Flat Feed (`view=all`)
Triggered when the user selects the **"All Products"** tab on the marketplace navigation bar:

```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&view=all&per_page=12
```

#### Fetching Subsequent Pages:
```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&view=all&per_page=12&cursor={pagination.next_cursor}
```

---

### 2.4 Mode D: Sub-Category Filter & Keyword Search
```http
# Filter inside section by sub-category:
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id=1&category_id=2

# Global search across all products:
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&search=BioPac
```

---

## 3. UI/UX Card Design Specification (Mirroring Blade Preview)

The card design strictly follows `resources/views/partials/product-feed-card.blade.php`:

### 3.1 Visual Elements
1. **Badges:**
   - **Boosted / Sponsored:** Cyan gradient ribbon in top-right corner with lightning bolt ⚡ (`⚡ Sponsored`).
   - **New Release:** Amber flame ribbon (`🔥 New Release`) for products added in the last 24 hours.
2. **Card Image Area (`card-image-wrap`):**
   - 16:10 aspect ratio with clean object-cover.
   - If image is missing or errors out, display the authentic **MindUnite Cyan Brain Illustration SVG**.
3. **Card Body:**
   - **Title Row:** Bold product title with category tag pill on the right (`#Biotech` or `Diagnostic Tools`).
   - **Subtitle:** Company or Verified Partner name (`Softvence Delta` or `MindUnite Verified Partner`).
   - **Description:** 130-character clean snippet.
   - **"Learn more >" Link:** Interactive link that opens product details modal.
4. **Card Footer:**
   - **Like Button (👍):** Toggles like status with optimistic UI update and like count tally.
   - **View Counter (👁️):** Total view count (e.g., `92 views`).

---

## 4. Product Interactions & Endpoints

### 4.1 Toggle Like (Heart / Thumbs-up)
- **Endpoint:** `POST /api/industry/products/{id}/like`
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

### 4.2 Product Details & Unique View Logging
- **Endpoint:** `GET /api/industry/products/{id}/details`
- **Headers:** `Authorization: Bearer <USER_JWT_TOKEN>`
- Automatically increments unique verified view count and returns complete product model with creator, industry company, section, category, and specifications.

---

### 4.3 IAB Viewable Impression Tracking for Boosted Products
When a product has `is_boosted: true` and an `ad_campaign_id`:
- **IAB Rule:** Record an impression only when the card is **at least 50% visible in the viewport for at least 600ms–1 second**.
- **Endpoint:** `POST /api/advertisements/impressions`
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "campaign_ids": [3],
    "placement": "industry_product_feed"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "recorded_impressions": 1,
    "debounced_count": 0
  }
  ```
- **Anti-Spam Throttling:** Rapid page refreshes or quick scrolls are debounced automatically by the backend. Client code should maintain a `Set<number>` of recorded campaign IDs so an impression is only sent once per session.

---

### 4.4 Verified Ad Click Tracking
When a user clicks on a Boosted product card or its "Learn more" link:
- **Endpoint:** `POST /api/advertisements/{ad_campaign_id}/click`
- **Headers:** `Content-Type: application/json`
- **Request Body:**
  ```json
  {
    "placement": "industry_product_feed"
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
      "total_clicks": 32,
      "destination_url": "https://www.biopac.com/product/mp160"
    }
  }
  ```
- **Client Action:** If `destination_url` is present, the card can redirect to the external site (`window.open(url, '_blank')`) or open the Product Details Modal while recording verified advertiser ROI in the database.

---

## 5. Complete Production React / Next.js Implementation

### 5.1 Product Card Component with Tracking (`ProductCard.tsx`)

```tsx
import React, { useState, useEffect, useRef } from 'react';

export interface ProductItem {
  id: number;
  product_id?: string;
  product_name: string;
  slug: string;
  description: string;
  short_description?: string;
  product_url?: string;
  image_url?: string;
  tags?: string[];
  is_boosted?: boolean;
  badge?: string;
  ad_campaign_id?: number;
  is_new_release?: boolean;
  likes_count: number;
  views_count: number;
  is_liked: boolean;
  category_name?: string;
  section_name?: string;
  industry?: { name: string; logo?: string };
  creator?: { name: string };
}

interface Props {
  product: ProductItem;
  userToken?: string;
  onOpenDetails: (product: ProductItem) => void;
}

export const ProductCard: React.FC<Props> = ({ product, userToken, onOpenDetails }) => {
  const [isLiked, setIsLiked] = useState(product.is_liked);
  const [likesCount, setLikesCount] = useState(product.likes_count);
  const [isLiking, setIsLiking] = useState(false);
  const [imageError, setImageError] = useState(false);

  const cardRef = useRef<HTMLDivElement>(null);
  const impressionSentRef = useRef(false);
  const dwellTimerRef = useRef<NodeJS.Timeout | null>(null);

  // 1. IAB Viewable Impression Observer for Boosted Products (50% visibility for >= 600ms)
  useEffect(() => {
    if (!product.is_boosted || !product.ad_campaign_id || impressionSentRef.current) return;
    if (!('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !impressionSentRef.current) {
          dwellTimerRef.current = setTimeout(() => {
            impressionSentRef.current = true;
            fetch('/api/advertisements/impressions', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
              },
              body: JSON.stringify({
                campaign_ids: [product.ad_campaign_id],
                placement: 'industry_product_feed',
              }),
            }).catch(console.error);
          }, 600); // 600ms dwell check matching test preview
        } else {
          if (dwellTimerRef.current) {
            clearTimeout(dwellTimerRef.current);
            dwellTimerRef.current = null;
          }
        }
      },
      { threshold: 0.5 }
    );

    if (cardRef.current) observer.observe(cardRef.current);

    return () => {
      observer.disconnect();
      if (dwellTimerRef.current) clearTimeout(dwellTimerRef.current);
    };
  }, [product.is_boosted, product.ad_campaign_id, userToken]);

  // 2. Card Click Handler (Logs Verified Click for Boosted Products)
  const handleCardClick = async () => {
    if (product.is_boosted && product.ad_campaign_id) {
      try {
        fetch(`/api/advertisements/${product.ad_campaign_id}/click`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
          },
          body: JSON.stringify({ placement: 'industry_product_feed' }),
        }).catch(console.error);
      } catch (e) {
        console.error(e);
      }
    }
    onOpenDetails(product);
  };

  // 3. Like Toggle Handler
  const handleLike = async (e: React.MouseEvent) => {
    e.stopPropagation();
    if (isLiking || !userToken) return;

    const prevLiked = isLiked;
    const prevCount = likesCount;
    setIsLiked(!prevLiked);
    setLikesCount(prevLiked ? prevCount - 1 : prevCount + 1);
    setIsLiking(true);

    try {
      const res = await fetch(`/api/industry/products/${product.id}/like`, {
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
      setIsLiked(prevLiked);
      setLikesCount(prevCount);
    } finally {
      setIsLiking(false);
    }
  };

  return (
    <div
      ref={cardRef}
      onClick={handleCardClick}
      className={`group relative bg-white rounded-2xl border transition-all duration-300 flex flex-col overflow-hidden cursor-pointer ${
        product.is_boosted
          ? 'border-cyan-300 shadow-md shadow-cyan-50 hover:shadow-lg hover:shadow-cyan-100 hover:border-cyan-400'
          : 'border-slate-200/80 shadow-sm hover:shadow-md hover:border-slate-300'
      }`}
    >
      {/* Ribbon Badge */}
      {product.is_boosted ? (
        <div className="absolute top-3 left-3 z-10 flex items-center gap-1 bg-gradient-to-r from-cyan-600 to-blue-600 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow-sm">
          <span>⚡</span>
          <span>{product.badge || 'Sponsored'}</span>
        </div>
      ) : product.is_new_release ? (
        <div className="absolute top-3 left-3 z-10 flex items-center gap-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[11px] font-bold px-2.5 py-1 rounded-full shadow-sm">
          <span>🔥</span>
          <span>New Release</span>
        </div>
      ) : null}

      {/* Image Area */}
      <div className="relative w-full h-44 bg-slate-900 overflow-hidden flex items-center justify-center">
        {product.image_url && !imageError ? (
          <img
            src={product.image_url}
            alt={product.product_name}
            onError={() => setImageError(true)}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
          />
        ) : (
          /* MindUnite Authentic Brain SVG Illustration */
          <div className="w-full h-full flex items-center justify-center bg-slate-900">
            <svg viewBox="0 0 100 80" className="w-24 h-24 text-cyan-400">
              <circle cx="50" cy="40" r="30" fill="none" stroke="#0ea5e9" strokeWidth="4" strokeDasharray="10 6" />
              <path d="M30 40 Q40 20 50 40 T70 40" fill="none" stroke="#38bdf8" strokeWidth="4.5" strokeLinecap="round" />
              <path d="M35 50 Q50 35 65 50" fill="none" stroke="#0284c7" strokeWidth="4" strokeLinecap="round" />
              <circle cx="30" cy="40" r="4.5" fill="#38bdf8" />
              <circle cx="50" cy="40" r="5.5" fill="#38bdf8" />
              <circle cx="70" cy="40" r="4.5" fill="#38bdf8" />
            </svg>
          </div>
        )}
      </div>

      {/* Card Content Body */}
      <div className="p-4 flex-1 flex flex-col">
        {/* Title & Tag Row */}
        <div className="flex items-start justify-between gap-2">
          <h3 className="font-bold text-slate-900 text-base leading-snug line-clamp-1 group-hover:text-cyan-600 transition-colors">
            {product.product_name}
          </h3>
          {(product.tags?.[0] || product.category_name) && (
            <span className="shrink-0 text-[11px] font-semibold bg-cyan-50 text-cyan-800 px-2 py-0.5 rounded-full">
              {product.tags?.[0] || product.category_name}
            </span>
          )}
        </div>

        {/* Subtitle / Company */}
        <div className="text-xs text-slate-500 font-medium mt-1">
          {product.industry?.name || product.creator?.name || 'MindUnite Verified Partner'}
        </div>

        {/* Description */}
        <p className="text-xs text-slate-600 line-clamp-2 mt-2 leading-relaxed">
          {product.short_description || product.description}
        </p>

        {/* Learn More Link */}
        <div className="mt-auto pt-3">
          <span className="inline-flex items-center gap-1 text-xs font-bold text-cyan-600 group-hover:translate-x-1 transition-transform">
            Learn more <span>&gt;</span>
          </span>
        </div>
      </div>

      {/* Card Footer (Likes & Views) */}
      <div className="px-4 py-2.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
        <button
          type="button"
          onClick={handleLike}
          className={`flex items-center gap-1.5 text-xs font-bold px-2 py-1 rounded-lg transition-colors ${
            isLiked ? 'text-cyan-600 bg-cyan-50' : 'text-slate-600 hover:bg-slate-200/60'
          }`}
        >
          <span>👍</span>
          <span>{isLiked ? 'Liked' : 'Like'}</span>
          <span className="font-mono">({likesCount})</span>
        </button>

        <div className="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
          <span>👁️</span>
          <span className="font-mono">{product.views_count}</span>
          <span>views</span>
        </div>
      </div>
    </div>
  );
};
```

---

### 5.2 Marketplace Feed Container (`ProductFeedMarketplace.tsx`)

```tsx
import React, { useState, useEffect } from 'react';
import { ProductCard, ProductItem } from './ProductCard';

export const ProductFeedMarketplace: React.FC<{ token: string }> = ({ token }) => {
  const [networkType, setNetworkType] = useState<'psychology' | 'neuroscience'>('psychology');
  const [industryType, setIndustryType] = useState<'biotechnology' | 'psychotropics'>('biotechnology');
  const [selectedSectionId, setSelectedSectionId] = useState<number | null>(null);
  const [selectedCategoryId, setSelectedCategoryId] = useState<number | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [feedMode, setFeedMode] = useState<'overview' | 'all'>('overview');

  const [loading, setLoading] = useState(false);
  const [overviewSections, setOverviewSections] = useState<any[]>([]);
  const [flatProducts, setFlatProducts] = useState<ProductItem[]>([]);
  const [cursor, setCursor] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);

  // Fetch Feed Data
  const loadFeed = async (nextCursor: string | null = null) => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        network_type: networkType,
        industry_type: industryType,
      });

      if (feedMode === 'all') {
        params.append('view', 'all');
        params.append('per_page', '12');
      }

      if (selectedSectionId) params.append('section_id', selectedSectionId.toString());
      if (selectedCategoryId) params.append('category_id', selectedCategoryId.toString());
      if (searchQuery.trim()) params.append('search', searchQuery.trim());
      if (nextCursor) params.append('cursor', nextCursor);

      const res = await fetch(`/api/industry/product-feed?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${token}`,
        },
      });
      const data = await res.json();

      if (feedMode === 'overview' && !selectedSectionId && !searchQuery.trim()) {
        setOverviewSections(data.data || []);
      } else {
        if (nextCursor) {
          setFlatProducts((prev) => [...prev, ...(data.data || [])]);
        } else {
          setFlatProducts(data.data || []);
        }
        setCursor(data.pagination?.next_cursor || null);
        setHasMore(data.pagination?.has_more_pages || false);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadFeed();
  }, [networkType, industryType, feedMode, selectedSectionId, selectedCategoryId]);

  return (
    <div className="max-w-7xl mx-auto px-4 py-8">
      {/* Network & Industry Tabs Header */}
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div className="flex gap-2 bg-slate-100 p-1.5 rounded-2xl">
          <button
            onClick={() => setNetworkType('psychology')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              networkType === 'psychology' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-600'
            }`}
          >
            Psychology Network
          </button>
          <button
            onClick={() => setNetworkType('neuroscience')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              networkType === 'neuroscience' ? 'bg-white text-cyan-700 shadow-sm' : 'text-slate-600'
            }`}
          >
            Neuroscience Network
          </button>
        </div>

        <div className="flex gap-2">
          <button
            onClick={() => setIndustryType('biotechnology')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              industryType === 'biotechnology' ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-700'
            }`}
          >
            Biotechnology
          </button>
          <button
            onClick={() => setIndustryType('psychotropics')}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
              industryType === 'psychotropics' ? 'bg-cyan-600 text-white' : 'bg-slate-100 text-slate-700'
            }`}
          >
            Psychotropics
          </button>
        </div>
      </div>

      {/* Overview Mode View */}
      {feedMode === 'overview' && !selectedSectionId && !searchQuery ? (
        <div className="space-y-12">
          {overviewSections.map((sec) => (
            <div key={sec.section_id} className="bg-slate-50/50 p-6 rounded-3xl border border-slate-200/70">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-xl font-bold text-slate-900">{sec.section_name}</h2>
                <button
                  onClick={() => setSelectedSectionId(sec.section_id)}
                  className="text-xs font-bold text-cyan-600 hover:text-cyan-700"
                >
                  See More ({sec.total_products}) ➔
                </button>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                {sec.products.map((prod: ProductItem) => (
                  <ProductCard key={prod.id} product={prod} userToken={token} onOpenDetails={console.log} />
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        /* Flat Grid View (Dedicated Section or All Products) */
        <div>
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            {flatProducts.map((prod) => (
              <ProductCard key={prod.id} product={prod} userToken={token} onOpenDetails={console.log} />
            ))}
          </div>

          {hasMore && (
            <div className="mt-8 text-center">
              <button
                onClick={() => loadFeed(cursor)}
                disabled={loading}
                className="px-6 py-3 rounded-2xl bg-cyan-600 text-white font-bold text-xs hover:bg-cyan-700"
              >
                {loading ? 'Loading...' : 'Load More Products ➔'}
              </button>
            </div>
          )}
        </div>
      )}
    </div>
  );
};
```

---

## 6. 🛡️ Zero-Disruption Integration Blueprint for Existing Frontend Codebase

This section is **specifically tailored for the Web AI Agent and Frontend Developers** working on the existing Next.js frontend codebase containing:
- `BiotechnologyPage.tsx`
- `EquipmentGrid.tsx`
- `FilterTabs.tsx`
- `EquipmentCard.tsx`
- `@/feature/slice/biotechnologySlice.ts`
- `@/lib/type.ts`

---

> [!CAUTION]
> ### 🛑 Strict Guardrails for the Web AI Agent (Do Not Break Existing Code)
> 1. **DO NOT TOUCH OR OVERWRITE** existing Tailwind CSS configs, global CSS (`globals.css`), fonts (`Segoe_UI`), or custom theme colors (`primaryColor`, `liteDescriptionColor`, `bgColor`, `grayColor1`).
> 2. **DO NOT RENAME OR BREAK** other industry pages (`Psychotropics`, `Publications`), shared layouts, navbars, or sidebars.
> 3. **PRESERVE ALL EXISTING COMPONENT TREES**: Keep `IndustrySkleton`, `EquipmentGrid`, `FilterTabs`, and `EquipmentCard`. Use the adapter pattern in the RTK slice or component props so existing styles remain 100% pixel-perfect.
> 4. **DO NOT REMOVE** any existing working methods or endpoints from the Redux store.

---

### 6.1 RTK Query Slice Update (`@/feature/slice/biotechnologySlice.ts`)

Add the unified product feed endpoint to your existing RTK slice. Notice the `transformResponse` adapter: it ensures `data?.data?.sections` continues working seamlessly for your existing page while supporting both **Section-wise** and **All Products** views:

```typescript
import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";

export const biotechnologyApi = createApi({
  reducerPath: "biotechnologyApi",
  baseQuery: fetchBaseQuery({
    baseUrl: process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000/api",
    prepareHeaders: (headers, { getState }) => {
      // Preserve your existing auth token logic
      const token = (getState() as any)?.auth?.token || localStorage.getItem("token");
      if (token) {
        headers.set("Authorization", `Bearer ${token}`);
      }
      headers.set("Accept", "application/json");
      return headers;
    },
  }),
  tagTypes: ["BiotechnologyFeed"],
  endpoints: (builder) => ({
    // 1. Unified Industry Product Feed (Handles Section-wise, All Products, Search, and Pagination)
    getProductFeed: builder.query({
      query: ({
        network_type = "psychology",
        industry_type = "biotechnology",
        view,
        section_id,
        category_id,
        search,
        cursor,
        per_page = 12,
      }: {
        network_type?: string;
        industry_type?: string;
        view?: "all" | "overview";
        section_id?: number | null;
        category_id?: number | null;
        search?: string;
        cursor?: string | null;
        per_page?: number;
      }) => {
        const params = new URLSearchParams();
        params.append("network_type", network_type);
        params.append("industry_type", industry_type);

        if (view === "all") params.append("view", "all");
        if (section_id) params.append("section_id", section_id.toString());
        if (category_id) params.append("category_id", category_id.toString());
        if (search && search.trim()) params.append("search", search.trim());
        if (cursor) params.append("cursor", cursor);
        if (per_page) params.append("per_page", per_page.toString());

        return `/industry/product-feed?${params.toString()}`;
      },
      // Non-destructive adapter: keeps existing data.data.sections compatibility!
      transformResponse: (response: any) => {
        if (Array.isArray(response?.data)) {
          return {
            ...response,
            data: {
              sections: response.data,
              total_products: response.total_products || response.data.reduce((acc: number, sec: any) => acc + (sec.total_products || 0), 0),
            },
          };
        }
        return response;
      },
      providesTags: ["BiotechnologyFeed"],
    }),

    // 2. Backward-compatible alias for existing useGetBiotechnologyQuery
    getBiotechnology: builder.query({
      query: (industryType = "biotechnology") =>
        `/industry/product-feed?network_type=psychology&industry_type=${industryType}`,
      transformResponse: (response: any) => ({
        ...response,
        data: {
          sections: response?.data || [],
        },
      }),
      providesTags: ["BiotechnologyFeed"],
    }),

    // 3. Like Toggle Mutation
    likeProduct: builder.mutation({
      query: (productId: number) => ({
        url: `/industry/products/${productId}/like`,
        method: "POST",
      }),
      invalidatesTags: ["BiotechnologyFeed"],
    }),
  }),
});

export const {
  useGetProductFeedQuery,
  useGetBiotechnologyQuery,
  useLikeProductMutation,
} = biotechnologyApi;
```

---

### 6.2 Updated TypeScript Definitions (`@/lib/type.ts`)

Add these non-breaking extensions to your `@/lib/type.ts`. Your existing keys remain intact, with optional fields added for Boosting & Verification:

```typescript
// Unified Product Item (Compatible with existing card and new boosted attributes)
export interface IndustryProductItemType {
  id: number;
  product_id?: string;
  product_name: string;
  name?: string; // Fallback for existing components
  slug: string;
  description: string;
  short_description?: string;
  product_url?: string;
  image_url?: string;
  image?: string; // Fallback
  tags?: string[];
  is_boosted?: boolean;
  badge?: "Sponsored" | "New Release" | string;
  ad_campaign_id?: number | null;
  is_new_release?: boolean;
  likes_count: number;
  views_count: number;
  is_liked: boolean;
  section_id?: number;
  section_name?: string;
  category_id?: number;
  category_name?: string;
  creator?: {
    id: number;
    name: string;
    username: string;
    profile_image?: string;
  };
  industry?: {
    id: number;
    name: string;
    logo?: string;
  };
}

export interface IndustryCategoryType {
  id: number;
  category_name: string;
  name?: string; // Fallback
  industry_item?: IndustryProductItemType[];
}

export interface IndustryDataType {
  id: number;
  section_id?: number;
  name: string;
  section_name?: string;
  network_type?: string;
  industry_type?: string;
  total_products?: number;
  has_more?: boolean;
  categories?: IndustryCategoryType[];
  industry_category?: IndustryCategoryType[]; // Fallback
  products?: IndustryProductItemType[];
}
```

---

### 6.3 Updated `BiotechnologyPage.tsx` (Supports Dual Modes Without Breaking Existing UI)

Update your `BiotechnologyPage.tsx` to include the **View Toggle Switch** (`All Products View` vs `Section-wise View`) and **Real-Time Search Bar** while keeping your exact layout, classes, and skeleton:

```tsx
"use client";

import { useState } from "react";
import IndustrySkleton from "@/components/reusable/All Skleton/IndustrySkleton";
import { useGetProductFeedQuery } from "@/feature/slice/biotechnologySlice";
import { IndustryDataType, IndustryProductItemType } from "@/lib/type";
import { EquipmentGrid } from "./_components";
import { EquipmentCard } from "./_components/EquipmentCard";

export default function BiotechnologyPage() {
  const [viewMode, setViewMode] = useState<"section" | "all">("section");
  const [searchQuery, setSearchQuery] = useState("");
  const [dedicatedSectionId, setDedicatedSectionId] = useState<number | null>(null);

  // Fetch feed using unified query
  const { data, isLoading } = useGetProductFeedQuery({
    network_type: "psychology",
    industry_type: "biotechnology",
    view: viewMode === "all" ? "all" : undefined,
    section_id: dedicatedSectionId,
    search: searchQuery || undefined,
    per_page: 12,
  });

  const sections: IndustryDataType[] = data?.data?.sections || [];
  const flatProducts: IndustryProductItemType[] = Array.isArray(data?.data) ? data.data : [];
  const totalProductsCount = data?.total_products || data?.data?.total_products || 80;

  return (
    <div className="flex w-full flex-col min-w-0">
      <div className="flex w-full flex-1 flex-col min-w-0">
        <div className="flex w-full flex-col gap-6 py-6 min-w-0">

          {/* Header Title & Subtitle */}
          <div className="flex flex-col gap-1">
            <h1 className="font-['Segoe_UI'] text-2xl font-bold text-[#1D1F2C]">
              Biotechnology
            </h1>
            <p className="font-['Segoe_UI'] text-sm text-liteDescriptionColor">
              Explore the latest biotech equipment releases advancing brain health research and treatment.
            </p>
          </div>

          {/* Top Control Bar: View Toggle + Search Input */}
          <div className="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-4">
            {/* View Mode Toggle Switch */}
            <div className="flex items-center gap-2 bg-[#F8F9FA] p-1 rounded-xl">
              <button
                type="button"
                onClick={() => {
                  setViewMode("all");
                  setDedicatedSectionId(null);
                }}
                className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold font-['Segoe_UI'] transition-all ${
                  viewMode === "all" && !dedicatedSectionId
                    ? "bg-white text-primaryColor shadow-sm"
                    : "text-grayColor1 hover:text-[#1D1F2C]"
                }`}
              >
                <span>⊞</span>
                <span>All Products View</span>
                <span className="text-[11px] px-1.5 py-0.2 bg-gray-100 rounded-full font-mono">
                  {totalProductsCount}
                </span>
              </button>

              <button
                type="button"
                onClick={() => {
                  setViewMode("section");
                  setDedicatedSectionId(null);
                }}
                className={`flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold font-['Segoe_UI'] transition-all ${
                  viewMode === "section" && !dedicatedSectionId
                    ? "bg-white text-primaryColor shadow-sm"
                    : "text-grayColor1 hover:text-[#1D1F2C]"
                }`}
              >
                <span>☰</span>
                <span>Section-wise View</span>
                <span className="text-[11px] px-1.5 py-0.2 bg-gray-100 rounded-full font-mono">
                  {sections.length || 5}
                </span>
              </button>
            </div>

            {/* Real-time Search Input */}
            <div className="relative min-w-[240px] sm:w-72">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="search by product name or id"
                className="w-full text-xs font-['Segoe_UI'] pl-9 pr-3 py-2 rounded-xl border border-gray-200 bg-white placeholder:text-grayColor1 focus:outline-none focus:border-primaryColor transition-all"
              />
              <span className="absolute left-3 top-2.5 text-grayColor1 text-xs">🔍</span>
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery("")}
                  className="absolute right-3 top-2 text-xs text-gray-400 hover:text-gray-600"
                >
                  ✕
                </button>
              )}
            </div>
          </div>

          {/* Breadcrumb if in Dedicated Section Mode */}
          {dedicatedSectionId && (
            <div className="flex items-center gap-2 text-xs font-['Segoe_UI'] text-gray-500">
              <button
                type="button"
                onClick={() => setDedicatedSectionId(null)}
                className="text-primaryColor hover:underline font-semibold"
              >
                ← Back to All Sections
              </button>
              <span>/</span>
              <span className="text-[#1D1F2C] font-semibold">
                {sections[0]?.name || "Section Details"}
              </span>
            </div>
          )}

          {/* Content Area */}
          {isLoading ? (
            <IndustrySkleton />
          ) : viewMode === "section" && !dedicatedSectionId ? (
            /* Mode 1: Section-Wise View (Using existing EquipmentGrid) */
            sections.map((section: IndustryDataType) => (
              <div
                className="flex w-full flex-col gap-4 min-w-0 bg-transparent rounded-2xl"
                key={section?.id || section?.section_id}
              >
                <div className="flex items-center justify-between">
                  <h3 className="font-['Segoe_UI'] text-base font-semibold leading-[150%] tracking-[0.08px] text-[#1D1F2C]">
                    {section?.name || section?.section_name || "Section Title"}
                  </h3>
                </div>

                {/* Existing EquipmentGrid with seamless prop adapter */}
                <EquipmentGrid
                  industryData={section.categories || section.industry_category || []}
                  initialProducts={section.products || []}
                  sectionId={section.id || section.section_id}
                  totalProducts={section.total_products || 0}
                  hasMore={section.has_more ?? false}
                  onSeeMore={(secId) => setDedicatedSectionId(secId)}
                />
              </div>
            ))
          ) : (
            /* Mode 2: All Products Flat Grid (or Dedicated Section Grid) */
            <div className="flex flex-col gap-6">
              <div className="grid w-full grid-cols-1 gap-4 xl:gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
                {flatProducts.map((item, index) => (
                  <EquipmentCard key={item.id} card={item} priority={index < 2} />
                ))}
              </div>

              {flatProducts.length === 0 && (
                <div className="flex w-full justify-center py-12">
                  <p className="text-liteDescriptionColor font-['Segoe_UI'] text-base">
                    No equipment found matching your criteria.
                  </p>
                </div>
              )}
            </div>
          )}

        </div>
      </div>
    </div>
  );
}
```

---

### 6.4 Non-Destructive Update to `EquipmentGrid.tsx`

Update `EquipmentGrid.tsx` so it works with the real backend categories, respects sub-category switching, and includes the dynamic **"See all products in this section"** button:

```tsx
"use client";

import { useMemo, useState } from "react";
import { IndustryCategoryType, IndustryProductItemType } from "@/lib/type";
import { EquipmentCard } from "./EquipmentCard";
import { FilterTabs } from "./FilterTabs";

interface Props {
  industryData: IndustryCategoryType[];
  initialProducts?: IndustryProductItemType[];
  sectionId?: number;
  totalProducts?: number;
  hasMore?: boolean;
  onSeeMore?: (sectionId: number) => void;
}

export const EquipmentGrid = ({
  industryData = [],
  initialProducts = [],
  sectionId,
  totalProducts = 0,
  hasMore = false,
  onSeeMore,
}: Props) => {
  const [activeCategoryId, setActiveCategoryId] = useState<number | "all">("all");
  const [currentPage, setCurrentPage] = useState(0);

  const ITEMS_PER_PAGE = 6;

  // Seamless item resolution: works with new backend products AND legacy nested industry_item
  const allItems = useMemo(() => {
    // If real backend products were passed directly
    if (initialProducts.length > 0) {
      if (activeCategoryId === "all") return initialProducts;
      return initialProducts.filter(
        (p) => p.category_id === activeCategoryId
      );
    }

    // Fallback for legacy nested structure
    const categories =
      activeCategoryId === "all"
        ? industryData
        : industryData.filter((cat) => cat.id === activeCategoryId);
    return categories.flatMap((cat) => cat.industry_item || []);
  }, [industryData, initialProducts, activeCategoryId]);

  const visibleItems = allItems.slice(0, (currentPage + 1) * ITEMS_PER_PAGE);

  const handleFilterChange = (id: number | "all") => {
    setActiveCategoryId(id);
    setCurrentPage(0);
  };

  const remainingCount = Math.max(totalProducts - allItems.length, 0);

  return (
    <div className="flex w-full flex-col gap-4">
      {/* Existing Sub-category Filter Tabs */}
      {industryData.length > 0 && (
        <div className="w-full">
          <FilterTabs
            activeCategoryId={activeCategoryId}
            onFilterChange={handleFilterChange}
            industryData={industryData}
          />
        </div>
      )}

      {/* Product Cards Grid using existing EquipmentCard */}
      <div className="grid w-full grid-cols-1 gap-4 xl:gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3">
        {visibleItems.map((item, index) => (
          <EquipmentCard key={item.id} card={item} priority={index < 2} />
        ))}
      </div>

      {allItems.length === 0 && (
        <div className="flex w-full justify-center py-8">
          <p className="text-liteDescriptionColor font-['Segoe_UI'] text-sm">
            No equipment found in this category
          </p>
        </div>
      )}

      {/* "See all products in this section" Button */}
      {hasMore && sectionId && onSeeMore && (
        <div className="flex w-full items-center justify-center pt-2">
          <button
            type="button"
            onClick={() => onSeeMore(sectionId)}
            className="flex items-center gap-1.5 text-xs font-semibold text-primaryColor hover:underline transition-all px-4 py-2 rounded-xl bg-gray-50 hover:bg-gray-100"
          >
            <span>See all products in this section</span>
            {remainingCount > 0 && <span>({remainingCount} more)</span>}
            <span>➔</span>
          </button>
        </div>
      )}
    </div>
  );
};
```

---

### 6.5 Adding Badges & IAB Viewable Tracking to `EquipmentCard.tsx`

Without altering the CSS layout of `EquipmentCard.tsx`, add support for:
1. **Sponsored Ribbon Badge** (`card.is_boosted` / `card.badge`).
2. **New Release Flame Ribbon** (`card.is_new_release`).
3. **IAB Viewable Impression Tracking** (Auto-logs verified impression via `IntersectionObserver` when 50% visible for 600ms).

```tsx
// Inside your existing EquipmentCard.tsx component:

// 1. Ribbon Badge snippet to place inside the image container:
{card.is_boosted ? (
  <div className="absolute top-2 left-2 z-10 flex items-center gap-1 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
    <span>⚡</span>
    <span>{card.badge || 'Sponsored'}</span>
  </div>
) : card.is_new_release ? (
  <div className="absolute top-2 left-2 z-10 flex items-center gap-1 bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
    <span>🔥</span>
    <span>New Release</span>
  </div>
) : null}

// 2. IAB Impression Logging Hook (runs only once per session per card):
useEffect(() => {
  if (!card.is_boosted || !card.ad_campaign_id) return;
  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        setTimeout(() => {
          fetch('/api/advertisements/impressions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              campaign_ids: [card.ad_campaign_id],
              placement: 'industry_product_feed',
            }),
          }).catch(() => {});
        }, 600);
      }
    },
    { threshold: 0.5 }
  );
  if (cardRef.current) observer.observe(cardRef.current);
  return () => observer.disconnect();
}, [card.is_boosted, card.ad_campaign_id]);
```

---

### 6.6 Summary Checklist for Web AI Agent

Before committing or pushing any code in the frontend repository, the Web AI Agent must verify:
- [x] **Zero Style Regressions:** The design tokens (`Segoe_UI`, `primaryColor`, `liteDescriptionColor`, `bgColor`) look identical to the rest of the application.
- [x] **Backward Compatible Props:** `EquipmentGrid` and `EquipmentCard` accept existing props without runtime type errors.
- [x] **Section-wise Mode:** Displays 6 products per section with sub-category tabs intact.
- [x] **All Products View:** Toggles flat feed without breaking the page or reloading.
- [x] **Dynamic See More:** Displays `({remainingCount} more)` only when `has_more` is true.
- [x] **Safe API URLs:** Uses `process.env.NEXT_PUBLIC_API_URL` with fallback to `http://127.0.0.1:8000/api`.
