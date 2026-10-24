# MindUnite — Mobile App Integration Guide: Industry Product Marketplace Feed
## For Mobile App Developers (React Native, Flutter, iOS / Android)
### Version 2.0 • Production Ready

---

## 📌 Executive Summary

This guide provides mobile application developers (React Native, Flutter, Native iOS/Android) with everything required to implement the **MindUnite Industry Product Marketplace Feed**. It matches the verified web preview (`/product-feed-preview`), with touch-optimized mobile UX, smooth 2-column card grids, horizontal section carousels, sub-category chip filtering, real-time like toggling, and infinite scroll cursor pagination.

---

## 🔒 Zero Secret Data & Sanitized Privacy Policy

The API strictly guarantees that **NO private, financial, or confidential personal data** is sent to the mobile client in the public feed:
- ❌ **No advertiser billing accounts, Stripe IDs, or transaction tokens.**
- ❌ **No campaign CPM/CPC pricing rates, internal budgets, or wallet balances.**
- ❌ **No personal contact details (`poc_name`, `poc_email`, `poc_phone`) or private competitor metrics (`impressions_count`, `clicks_count`, `boost_score`) in the public feed.** (Product inquiries and full specifications are fetched via `GET /api/industry/products/{id}/details`).
- ❌ **No bloated HTML markup** — clean, lightweight sanitized strings (`short_description` & `description`) are sent for maximum mobile performance and smooth 60fps scrolling.
- ✅ **Only safe public product listing fields are served** (`product_name`, `slug`, `image_url`, `description`, `badge`, `industry`, `creator`, `likes_count`, `views_count`, `is_liked`, `is_boosted`, `ad_campaign_id`).

---

## 🌐 API Endpoint & Authentication

- **Endpoint:** `GET /api/industry/product-feed`
- **Aliases:** `GET /api/industry/feed`, `GET /api/industry/products/feed`
- **Headers:**
  ```http
  Accept: application/json
  Authorization: Bearer <USER_JWT_TOKEN>
  ```
  *(Standard user JWT token required with active session).*

---

## 1. Query Parameters Reference

| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `network_type` | `string` | **Yes** | — | `psychology` or `neuroscience` |
| `industry_type` | `string` | **Yes** | — | `biotechnology` or `psychotropics` *(Strict: `publications` is separate!)* |
| `view` | `string` | No | `overview` | Pass `all` to retrieve a flat, cursor-paginated list of all products |
| `flat` | `boolean` | No | `0` | Pass `1` or `true` as an alternative to `view=all` |
| `section_id` | `integer` | No | `null` | Target specific section ID (Dedicated Section Screen) |
| `category_id` | `integer` | No | `null` | Filter products by sub-category ID chip |
| `search` | `string` | No | `null` | Search across product name, description, and SKU code |
| `per_page` | `integer` | No | `12` | Products per page (`1` to `100`) |
| `cursor` | `string` | No | `null` | Cursor token for infinite scroll (`pagination.next_cursor`) |

---

## 2. Mobile Feed Modes & UX Patterns

### 2.1 Mode A: Section Overview (Landing Screen)
```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology
```
- Returns all department sections.
- **Mobile UX:** Render each section with a title header, a "See More >" button, and a horizontal snap-scroll `FlatList` showing the top 6 product cards.

### 2.2 Mode B: Dedicated Section (Deep Dive Screen)
```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id=1&per_page=12
```
- Displays only products belonging to that section.
- **Mobile UX:** 2-column vertical grid (`numColumns={2}`) with sub-category horizontal chip filter at the top and infinite scroll on `onEndReached`.

### 2.3 Mode C: All Products Flat Feed (`view=all`)
```http
GET /api/industry/product-feed?network_type=psychology&industry_type=biotechnology&view=all&per_page=12
```
- Returns a flat list of all active products ranked:
  1. **Boosted Sponsored Ads** first (`is_boosted: true`).
  2. **24h New Releases** second (`is_new_release: true`).
  3. **Organic Products** third.
- **Mobile UX:** 2-column vertical grid with cursor pagination.

---

## 3. Product Interactions (Likes & Details)

### 3.1 Toggle Product Like
- **Endpoint:** `POST /api/industry/products/{id}/like`
- **Headers:** `Authorization: Bearer <USER_JWT_TOKEN>`
- **Optimistic UI:** Immediately flip `is_liked` and update `likes_count` on the client, then trigger the API call in the background. If the request fails, revert state.
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

### 3.2 Product Details & Verification Count
- **Endpoint:** `GET /api/industry/products/{id}/details`
- **Headers:** `Authorization: Bearer <USER_JWT_TOKEN>`
- Automatically increments unique verified views on the server and returns complete creator, company, specifications, and contact info.

---

### 3.3 Mobile IAB Impression Tracking for Boosted Products
When displaying products with `is_boosted: true` and `ad_campaign_id`:
- **Trigger:** When card is at least 50% visible on screen for >= 600ms–1 second in the `FlatList`.
- **Endpoint:** `POST /api/advertisements/impressions`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "campaign_ids": [3],
    "placement": "industry_product_feed"
  }
  ```
- **Mobile Client Cache:** Maintain a `Set<number>` of recorded campaign IDs so duplicate calls are avoided while scrolling.

---

### 3.4 Verified Mobile Click Tracking
When a user taps a Boosted product card or "Learn more":
- **Endpoint:** `POST /api/advertisements/{ad_campaign_id}/click`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "placement": "industry_product_feed"
  }
  ```
- **Mobile Action:** Increment client-side click count if displayed, and navigate the user to the Product Details screen or open external destination in browser.

---

## 4. Complete React Native Implementation

### 4.1 Product Card Component (`MobileProductCard.tsx`)

```tsx
import React, { useState } from 'react';
import {
  View,
  Text,
  Image,
  TouchableOpacity,
  StyleSheet,
  Dimensions,
} from 'react-native';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 36) / 2; // 2-column grid layout

export interface MobileProductItem {
  id: number;
  product_id?: string;
  product_name: string;
  slug: string;
  description: string;
  short_description?: string;
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
  industry?: { name: string; logo?: string };
}

interface Props {
  product: MobileProductItem;
  userToken?: string;
  onPress: (product: MobileProductItem) => void;
}

export const MobileProductCard: React.FC<Props> = ({ product, userToken, onPress }) => {
  const [isLiked, setIsLiked] = useState(product.is_liked);
  const [likesCount, setLikesCount] = useState(product.likes_count);
  const [imageError, setImageError] = useState(false);

  const handleLike = async () => {
    if (!userToken) return;

    // Optimistic UI update
    const prevLiked = isLiked;
    const prevCount = likesCount;
    setIsLiked(!prevLiked);
    setLikesCount(prevLiked ? prevCount - 1 : prevCount + 1);

    try {
      const res = await fetch(`https://api.mindunite.com/api/industry/products/${product.id}/like`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
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
    }
  };

  const handleCardPress = () => {
    if (product.is_boosted && product.ad_campaign_id) {
      fetch(`https://api.mindunite.com/api/advertisements/${product.ad_campaign_id}/click`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
        },
        body: JSON.stringify({ placement: 'industry_product_feed' }),
      }).catch(console.error);
    }
    onPress(product);
  };

  return (
    <TouchableOpacity
      activeOpacity={0.9}
      onPress={handleCardPress}
      style={[
        styles.card,
        product.is_boosted && styles.cardBoosted,
      ]}
    >
      {/* Ribbon Badge */}
      {product.is_boosted ? (
        <View style={styles.sponsoredRibbon}>
          <Text style={styles.ribbonText}>⚡ {product.badge || 'Sponsored'}</Text>
        </View>
      ) : product.is_new_release ? (
        <View style={styles.newReleaseRibbon}>
          <Text style={styles.ribbonText}>🔥 New</Text>
        </View>
      ) : null}

      {/* Image Area */}
      <View style={styles.imageWrap}>
        {product.image_url && !imageError ? (
          <Image
            source={{ uri: product.image_url }}
            style={styles.image}
            resizeMode="cover"
            onError={() => setImageError(true)}
          />
        ) : (
          <View style={styles.fallbackWrap}>
            <Text style={styles.fallbackIcon}>🧠</Text>
          </View>
        )}
      </View>

      {/* Content */}
      <View style={styles.content}>
        <Text style={styles.title} numberOfLines={1}>
          {product.product_name}
        </Text>

        <Text style={styles.company} numberOfLines={1}>
          {product.industry?.name || 'MindUnite Partner'}
        </Text>

        <Text style={styles.description} numberOfLines={2}>
          {product.short_description || product.description}
        </Text>

        <Text style={styles.learnMore}>Learn more &gt;</Text>
      </View>

      {/* Footer */}
      <View style={styles.footer}>
        <TouchableOpacity style={styles.likeBtn} onPress={handleLike} activeOpacity={0.7}>
          <Text style={[styles.likeText, isLiked && styles.likeTextActive]}>
            👍 {likesCount}
          </Text>
        </TouchableOpacity>

        <Text style={styles.viewsText}>👁️ {product.views_count}</Text>
      </View>
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  card: {
    width: CARD_WIDTH,
    backgroundColor: '#ffffff',
    borderRadius: 16,
    marginHorizontal: 4,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 5,
    elevation: 2,
  },
  cardBoosted: {
    borderColor: '#7dd3fc',
    shadowColor: '#0284c7',
    shadowOpacity: 0.12,
    shadowRadius: 8,
  },
  sponsoredRibbon: {
    position: 'absolute',
    top: 8,
    left: 8,
    zIndex: 10,
    backgroundColor: '#0284c7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 12,
  },
  newReleaseRibbon: {
    position: 'absolute',
    top: 8,
    left: 8,
    zIndex: 10,
    backgroundColor: '#f59e0b',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 12,
  },
  ribbonText: {
    color: '#ffffff',
    fontSize: 9,
    fontWeight: '800',
  },
  imageWrap: {
    width: '100%',
    height: 120,
    backgroundColor: '#0f172a',
  },
  image: {
    width: '100%',
    height: '100%',
  },
  fallbackWrap: {
    width: '100%',
    height: '100%',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#0f172a',
  },
  fallbackIcon: {
    fontSize: 32,
  },
  content: {
    padding: 10,
    flex: 1,
  },
  title: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0f172a',
  },
  company: {
    fontSize: 10,
    color: '#64748b',
    marginTop: 2,
    fontWeight: '500',
  },
  description: {
    fontSize: 11,
    color: '#475569',
    marginTop: 4,
    lineHeight: 15,
  },
  learnMore: {
    fontSize: 11,
    fontWeight: '700',
    color: '#0284c7',
    marginTop: 8,
  },
  footer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderTopWidth: 1,
    borderColor: '#f1f5f9',
    backgroundColor: '#f8fafc',
  },
  likeBtn: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  likeText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748b',
  },
  likeTextActive: {
    color: '#0284c7',
  },
  viewsText: {
    fontSize: 10,
    color: '#94a3b8',
    fontWeight: '500',
  },
});
```

---

### 4.2 Main Marketplace Feed Screen (`MobileProductMarketplaceScreen.tsx`)

```tsx
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  FlatList,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  StyleSheet,
  SafeAreaView,
} from 'react-native';
import { MobileProductCard, MobileProductItem } from './MobileProductCard';

export const MobileProductMarketplaceScreen: React.FC<{
  userToken: string;
  onSelectProduct: (p: MobileProductItem) => void;
}> = ({ userToken, onSelectProduct }) => {
  const [networkType, setNetworkType] = useState<'psychology' | 'neuroscience'>('psychology');
  const [industryType, setIndustryType] = useState<'biotechnology' | 'psychotropics'>('biotechnology');
  const [search, setSearch] = useState('');
  const [products, setProducts] = useState<MobileProductItem[]>([]);
  const [cursor, setCursor] = useState<string | null>(null);
  const [hasMore, setHasMore] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);

  const fetchFeed = async (nextCursor: string | null = null) => {
    if (nextCursor) {
      setLoadingMore(true);
    } else {
      setLoading(true);
    }

    try {
      const params = new URLSearchParams({
        network_type: networkType,
        industry_type: industryType,
        view: 'all',
        per_page: '12',
      });

      if (search.trim()) params.append('search', search.trim());
      if (nextCursor) params.append('cursor', nextCursor);

      const res = await fetch(
        `https://api.mindunite.com/api/industry/product-feed?${params.toString()}`,
        {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${userToken}`,
          },
        }
      );
      const json = await res.json();

      if (nextCursor) {
        setProducts((prev) => [...prev, ...(json.data || [])]);
      } else {
        setProducts(json.data || []);
      }

      setCursor(json.pagination?.next_cursor || null);
      setHasMore(json.pagination?.has_more_pages || false);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
      setLoadingMore(false);
    }
  };

  useEffect(() => {
    fetchFeed();
  }, [networkType, industryType]);

  const handleEndReached = () => {
    if (hasMore && !loadingMore && cursor) {
      fetchFeed(cursor);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      {/* Network & Industry Selectors */}
      <View style={styles.filterBar}>
        <View style={styles.toggleRow}>
          <TouchableOpacity
            style={[styles.toggleBtn, networkType === 'psychology' && styles.toggleBtnActive]}
            onPress={() => setNetworkType('psychology')}
          >
            <Text style={[styles.toggleText, networkType === 'psychology' && styles.toggleTextActive]}>
              Psychology
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.toggleBtn, networkType === 'neuroscience' && styles.toggleBtnActive]}
            onPress={() => setNetworkType('neuroscience')}
          >
            <Text style={[styles.toggleText, networkType === 'neuroscience' && styles.toggleTextActive]}>
              Neuroscience
            </Text>
          </TouchableOpacity>
        </View>

        <View style={styles.toggleRow}>
          <TouchableOpacity
            style={[styles.chipBtn, industryType === 'biotechnology' && styles.chipBtnActive]}
            onPress={() => setIndustryType('biotechnology')}
          >
            <Text style={[styles.chipText, industryType === 'biotechnology' && styles.chipTextActive]}>
              Biotech
            </Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.chipBtn, industryType === 'psychotropics' && styles.chipBtnActive]}
            onPress={() => setIndustryType('psychotropics')}
          >
            <Text style={[styles.chipText, industryType === 'psychotropics' && styles.chipTextActive]}>
              Psychotropics
            </Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Search Bar */}
      <View style={styles.searchWrap}>
        <TextInput
          value={search}
          onChangeText={setSearch}
          onSubmitEditing={() => fetchFeed()}
          placeholder="Search apparatus, EEGs, kits..."
          placeholderTextColor="#94a3b8"
          style={styles.searchInput}
          returnKeyType="search"
        />
      </View>

      {/* 2-Column Product Grid */}
      {loading ? (
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#0284c7" />
        </View>
      ) : (
        <FlatList
          data={products}
          numColumns={2}
          keyExtractor={(item) => item.id.toString()}
          renderItem={({ item }) => (
            <MobileProductCard product={item} userToken={userToken} onPress={onSelectProduct} />
          )}
          contentContainerStyle={styles.listContent}
          onEndReached={handleEndReached}
          onEndReachedThreshold={0.5}
          ListFooterComponent={
            loadingMore ? (
              <View style={styles.footerLoading}>
                <ActivityIndicator size="small" color="#0284c7" />
              </View>
            ) : null
          }
        />
      )}
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f8fafc',
  },
  filterBar: {
    paddingHorizontal: 12,
    paddingTop: 8,
  },
  toggleRow: {
    flexDirection: 'row',
    backgroundColor: '#e2e8f0',
    borderRadius: 14,
    padding: 3,
    marginBottom: 8,
  },
  toggleBtn: {
    flex: 1,
    paddingVertical: 7,
    alignItems: 'center',
    borderRadius: 12,
  },
  toggleBtnActive: {
    backgroundColor: '#ffffff',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.08,
    shadowRadius: 2,
    elevation: 2,
  },
  toggleText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748b',
  },
  toggleTextActive: {
    color: '#0284c7',
  },
  chipBtn: {
    flex: 1,
    paddingVertical: 6,
    alignItems: 'center',
    borderRadius: 12,
  },
  chipBtnActive: {
    backgroundColor: '#0284c7',
  },
  chipText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#475569',
  },
  chipTextActive: {
    color: '#ffffff',
  },
  searchWrap: {
    paddingHorizontal: 12,
    marginBottom: 8,
  },
  searchInput: {
    backgroundColor: '#ffffff',
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 14,
    paddingHorizontal: 14,
    paddingVertical: 9,
    fontSize: 13,
    color: '#0f172a',
  },
  listContent: {
    paddingHorizontal: 8,
    paddingBottom: 24,
  },
  centerLoading: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  footerLoading: {
    paddingVertical: 16,
    alignItems: 'center',
  },
});
```
