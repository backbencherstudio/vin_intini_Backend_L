# MindUnite — Mobile App Integration Guide: Newsfeed Product Ads
## For Mobile App Developers (React Native, Flutter, iOS / Android)
### Version 2.0 • Production Ready

---

## 📌 Executive Summary

Your mobile application's social newsfeed stream (`GET /api/newsfeed`) delivers native **Sponsored Product Ads** seamlessly between regular posts. This guide specifies how mobile developers should detect sponsored items, render responsive touch-friendly cards matching the live design, track IAB-standard viewable impressions in mobile scroll views, handle ad clicks with deep links or in-app webviews, and manage user interactions.

---

## 🔒 Zero Secret Data Policy (Mobile Security & Privacy)

The mobile client receives **only public-safe, sanitized display data**:
- ❌ **No payment or billing data** (Stripe customer IDs, card tokens, transaction receipts).
- ❌ **No campaign financial metrics** (CPM, CPC rates, total ad budgets, advertiser wallet balances).
- ❌ **No private credentials or tokens** (passwords, 2FA recovery hashes, admin internal notes).
- ✅ **Only public creative fields** (`title`, `description`, `image_url`, `badge`, `industry`, `cta_text`, `destination_url`, `likes_count`, `views_count`).

---

## 1. Newsfeed Stream Ad Injection

When calling your existing authenticated newsfeed endpoint:
```http
GET /api/newsfeed
Authorization: Bearer <USER_JWT_TOKEN>
```

The server automatically injects product ad cards into the stream:
- **First Ad Position:** Index #3 (Post #4).
- **Subsequent Ads:** Every 8 posts (Post #4, Post #12, Post #20...).
- **Max Ads Per Feed Load:** 1 to 2 ads per pagination batch.
- **Fair-Play Exposure:** Algorithmic round-robin prevents ad monopolization.

### 1.1 Query Parameters Reference (`GET /api/newsfeed`)

| Parameter | Type | Required? | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `network_type` | `string` | Optional | Profile network | Target network ads: `psychology` or `neuroscience` |
| `per_page` | `integer` | Optional | `20` | Number of feed posts per page batch (also accepts `limit`) |
| `cursor` | `string` | Optional | `null` | Cursor token for page 2, 3, etc. from `pagination.next_cursor` |
| `seen_campaign_ids` | `string` | Optional | `null` | Comma-separated list of already rendered ad IDs (e.g., `1,4`) to prevent duplicate ads |

### 1.2 Item Detection in `FlatList` / `ListView`
Check `item.is_sponsored === true`:

```tsx
// React Native FlatList renderItem
const renderFeedItem = ({ item }: { item: any }) => {
  if (item.is_sponsored === true) {
    return (
      <MobileSponsoredAdCard 
        ad={item} 
        userToken={authToken} 
      />
    );
  }
  return <StandardPostCard post={item} />;
};
```

---

## 2. API Response & Data Payload Specification

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
  "image": "products/sample.webp",
  "image_url": "https://api.mindunite.com/storage/products/sample.webp",
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
    "logo": "https://api.mindunite.com/storage/industries/logos/logo.jpg",
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

---

## 3. Mobile Viewability & Click Tracking

### 3.1 Mobile IAB Viewability Tracking
An ad impression must ONLY be recorded once when the ad card is **at least 50% visible on the device screen for at least 1 continuous second**.

#### In React Native (`FlatList` Viewability Config):
```typescript
const viewabilityConfig = {
  itemVisiblePercentThreshold: 50,
  minimumViewTime: 1000, // 1 second
};

const onViewableItemsChanged = useRef(({ viewableItems }: any) => {
  const newImpressionCampaignIds: number[] = [];

  viewableItems.forEach(({ item }: any) => {
    if (item.is_sponsored && !recordedCampaignIds.has(item.ad_campaign_id)) {
      recordedCampaignIds.add(item.ad_campaign_id);
      newImpressionCampaignIds.push(item.ad_campaign_id);
    }
  });

  if (newImpressionCampaignIds.length > 0) {
    fetch('https://api.mindunite.com/api/advertisements/impressions', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authToken}`,
      },
      body: JSON.stringify({
        campaign_ids: newImpressionCampaignIds,
        placement: 'newsfeed',
      }),
    }).catch(console.error);
  }
}).current;
```

#### API Endpoint:
- **POST** `/api/advertisements/impressions`
- **Body:** `{ "campaign_ids": [3], "placement": "newsfeed" }`

---

### 3.2 Click Tracking & External Linking
When a user taps anywhere on the creative image, headline, or CTA button:
1. Call the click tracking endpoint:
   - **POST** `/api/advertisements/{ad_campaign_id}/click`
   - **Body:** `{ "placement": "newsfeed" }`
2. Open the returned `destination_url` via `Linking.openURL()` or in-app browser.

```typescript
const handleAdPress = async (ad: any) => {
  try {
    const res = await fetch(`https://api.mindunite.com/api/advertisements/${ad.ad_campaign_id}/click`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authToken}`,
      },
      body: JSON.stringify({ placement: 'newsfeed' }),
    });
    const json = await res.json();
    const targetUrl = json.data?.destination_url || ad.destination_url;
    if (targetUrl) {
      Linking.openURL(targetUrl);
    }
  } catch {
    if (ad.destination_url) {
      Linking.openURL(ad.destination_url);
    }
  }
};
```

---

### 3.3 Infinite Scroll Deduplication
When requesting subsequent pages in the newsfeed:
- Accumulate the `ad_campaign_id` of all ads loaded so far.
- Append `seen_campaign_ids=3,5` to the newsfeed pagination URL:
  ```http
  GET /api/newsfeed?cursor={cursor}&seen_campaign_ids=3,5
  ```
- The backend excludes those campaigns from upcoming pages so users do not see repeating ads.

---

## 4. Complete React Native Implementation

```tsx
import React, { useState } from 'react';
import {
  View,
  Text,
  Image,
  TouchableOpacity,
  Linking,
  StyleSheet,
  ActivityIndicator,
} from 'react-native';

export interface MobileSponsoredAdProps {
  ad: {
    id: string;
    ad_campaign_id: number;
    badge?: string;
    product_id: number;
    product_code?: string;
    product_name: string;
    description: string;
    image_url: string;
    destination_url: string;
    destination_domain?: string;
    cta_text?: string;
    likes_count: number;
    views_count: number;
    is_liked: boolean;
    industry?: {
      id?: number;
      is_following?: boolean;
      name?: string;
      logo?: string;
      slug?: string;
      tagline?: string;
    };
    section_name?: string;
    category_name?: string;
    tags?: string[];
    is_new_release?: boolean;
  };
  userToken?: string;
  onOpenDetails?: (product: any) => void;
}

export const MobileSponsoredAdCard: React.FC<MobileSponsoredAdProps> = ({
  ad,
  userToken,
  onOpenDetails,
}) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const [isLiked, setIsLiked] = useState(ad.is_liked);
  const [likesCount, setLikesCount] = useState(ad.likes_count);
  const [loadingLike, setLoadingLike] = useState(false);

  const handlePress = async () => {
    try {
      const res = await fetch(
        `https://api.mindunite.com/api/advertisements/${ad.ad_campaign_id}/click`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...(userToken ? { Authorization: `Bearer ${userToken}` } : {}),
          },
          body: JSON.stringify({ placement: 'newsfeed' }),
        }
      );
      const data = await res.json();
      const targetUrl = data.data?.destination_url || ad.destination_url;
      if (targetUrl) {
        Linking.openURL(targetUrl);
      }
    } catch {
      if (ad.destination_url) {
        Linking.openURL(ad.destination_url);
      }
    }
  };

  const handleToggleLike = async () => {
    if (loadingLike || !userToken) return;

    const previousLiked = isLiked;
    const previousCount = likesCount;
    setIsLiked(!previousLiked);
    setLikesCount(previousLiked ? previousCount - 1 : previousCount + 1);
    setLoadingLike(true);

    try {
      const res = await fetch(
        `https://api.mindunite.com/api/industry/products/${ad.product_id}/like`,
        {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${userToken}`,
          },
        }
      );
      const data = await res.json();
      if (data.success && data.data) {
        setIsLiked(data.data.is_liked);
        setLikesCount(data.data.likes_count);
      }
    } catch {
      setIsLiked(previousLiked);
      setLikesCount(previousCount);
    } finally {
      setLoadingLike(false);
    }
  };

  const industry = ad.industry;
  const captionText = ad.description || ad.product_name;
  const isLong = captionText.length > 150;

  return (
    <View style={styles.cardContainer}>
      {/* Header */}
      <View style={styles.headerRow}>
        <TouchableOpacity style={styles.companyLeft} onPress={handlePress} activeOpacity={0.8}>
          {industry?.logo ? (
            <Image source={{ uri: industry.logo }} style={styles.companyLogo} />
          ) : (
            <View style={styles.avatarFallback}>
              <Text style={styles.avatarFallbackText}>
                {(industry?.name || 'MU').substring(0, 2).toUpperCase()}
              </Text>
            </View>
          )}
          <View style={styles.companyMeta}>
            <Text style={styles.companyName} numberOfLines={1}>
              {industry?.name || 'MindUnite Partner'}
            </Text>
            <Text style={styles.companyTagline} numberOfLines={1}>
              {industry?.tagline || 'Innovative Technologies'}
            </Text>
            <View style={styles.promotedRow}>
              <Text style={styles.badgeText}>{ad.badge || 'Sponsored'}</Text>
              <Text style={styles.dotSeparator}>•</Text>
              <Text style={styles.promotedText}>Promoted</Text>
            </View>
          </View>
        </TouchableOpacity>

        <TouchableOpacity style={styles.followButton} activeOpacity={0.7}>
          <Text style={styles.followButtonText}>+ Follow</Text>
        </TouchableOpacity>
      </View>

      {/* Caption */}
      <View style={styles.captionContainer}>
        <Text style={styles.captionText}>
          {isLong && !isExpanded ? `${captionText.substring(0, 150)}...` : captionText}
          {isLong && (
            <Text style={styles.seeMoreText} onPress={() => setIsExpanded(!isExpanded)}>
              {isExpanded ? ' see less' : ' see more'}
            </Text>
          )}
        </Text>
      </View>

      {/* Hero Banner */}
      <TouchableOpacity activeOpacity={0.9} onPress={handlePress}>
        <Image source={{ uri: ad.image_url }} style={styles.bannerImage} resizeMode="cover" />
      </TouchableOpacity>

      {/* Link Preview Box */}
      <TouchableOpacity style={styles.previewBox} activeOpacity={0.85} onPress={handlePress}>
        <View style={styles.previewInfo}>
          <View style={styles.domainRow}>
            <Text style={styles.domainText}>{ad.destination_domain || 'mindunite.com'}</Text>
            {ad.product_code && (
              <View style={styles.skuBadge}>
                <Text style={styles.skuText}>#{ad.product_code}</Text>
              </View>
            )}
            {ad.is_new_release && (
              <View style={styles.newReleaseBadge}>
                <Text style={styles.newReleaseText}>🔥 New</Text>
              </View>
            )}
          </View>

          <Text style={styles.productTitle} numberOfLines={1}>
            {ad.product_name}
          </Text>

          <Text style={styles.categoryBreadcrumb} numberOfLines={1}>
            {ad.section_name}
            {ad.category_name ? ` • ${ad.category_name}` : ''}
          </Text>
        </View>

        <TouchableOpacity style={styles.ctaButton} onPress={handlePress}>
          <Text style={styles.ctaButtonText}>{ad.cta_text || 'View Product'} ➔</Text>
        </TouchableOpacity>
      </TouchableOpacity>

      {/* Stats Bar */}
      <View style={styles.statsBar}>
        <Text style={styles.statsText}>👍 {likesCount} likes</Text>
        <Text style={styles.statsText}>{ad.views_count} views</Text>
      </View>

      {/* Action Buttons */}
      <View style={styles.actionsRow}>
        <TouchableOpacity
          style={[styles.actionBtn, isLiked && styles.actionBtnActive]}
          onPress={handleToggleLike}
          activeOpacity={0.7}
        >
          <Text style={[styles.actionBtnText, isLiked && styles.actionBtnTextActive]}>
            👍 {isLiked ? 'Liked' : 'Like'}
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.actionBtn}
          onPress={() => onOpenDetails?.(ad)}
          activeOpacity={0.7}
        >
          <Text style={styles.actionBtnText}>📄 Details</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  cardContainer: {
    backgroundColor: '#ffffff',
    borderRadius: 18,
    marginVertical: 10,
    marginHorizontal: 12,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 6,
    elevation: 2,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 14,
  },
  companyLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
    marginRight: 10,
  },
  companyLogo: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#f1f5f9',
  },
  avatarFallback: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#e0f2fe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarFallbackText: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0284c7',
  },
  companyMeta: {
    marginLeft: 10,
    flex: 1,
  },
  companyName: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0f172a',
  },
  companyTagline: {
    fontSize: 11,
    color: '#64748b',
    marginTop: 1,
  },
  promotedRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#0284c7',
  },
  dotSeparator: {
    fontSize: 10,
    color: '#94a3b8',
    marginHorizontal: 4,
  },
  promotedText: {
    fontSize: 10,
    color: '#64748b',
  },
  followButton: {
    backgroundColor: '#f0f9ff',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#bae6fd',
  },
  followButtonText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#0369a1',
  },
  captionContainer: {
    paddingHorizontal: 14,
    paddingBottom: 10,
  },
  captionText: {
    fontSize: 13,
    color: '#334155',
    lineHeight: 18,
  },
  seeMoreText: {
    color: '#0284c7',
    fontWeight: '700',
  },
  bannerImage: {
    width: '100%',
    height: 220,
    backgroundColor: '#0f172a',
  },
  previewBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: 12,
    backgroundColor: '#f8fafc',
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: '#e2e8f0',
  },
  previewInfo: {
    flex: 1,
    marginRight: 10,
  },
  domainRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 3,
  },
  domainText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#64748b',
  },
  skuBadge: {
    backgroundColor: '#e2e8f0',
    paddingHorizontal: 5,
    paddingVertical: 1,
    borderRadius: 4,
    marginLeft: 6,
  },
  skuText: {
    fontSize: 9,
    fontFamily: 'monospace',
    color: '#475569',
  },
  newReleaseBadge: {
    backgroundColor: '#fef3c7',
    paddingHorizontal: 5,
    paddingVertical: 1,
    borderRadius: 4,
    marginLeft: 6,
  },
  newReleaseText: {
    fontSize: 9,
    fontWeight: '700',
    color: '#b45309',
  },
  productTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#0f172a',
  },
  categoryBreadcrumb: {
    fontSize: 11,
    color: '#0284c7',
    marginTop: 2,
  },
  ctaButton: {
    backgroundColor: '#0284c7',
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 12,
  },
  ctaButtonText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  statsBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderColor: '#f1f5f9',
  },
  statsText: {
    fontSize: 11,
    color: '#64748b',
    fontWeight: '500',
  },
  actionsRow: {
    flexDirection: 'row',
    padding: 6,
  },
  actionBtn: {
    flex: 1,
    paddingVertical: 8,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 10,
  },
  actionBtnActive: {
    backgroundColor: '#f0f9ff',
  },
  actionBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#475569',
  },
  actionBtnTextActive: {
    color: '#0284c7',
  },
});
```
