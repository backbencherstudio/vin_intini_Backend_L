@php
    $isBoosted = (bool) data_get($product, 'is_boosted', false);
    $isNewRelease = (bool) data_get($product, 'is_new_release', false);
    $productName = data_get($product, 'product_name', 'Untitled Product');
    $imageUrl = data_get($product, 'image_url');
    $industryName = data_get($product, 'industry.name') ?? 'Biotech / Pharma Industry';
    $industryLogo = data_get($product, 'industry.logo');
    $sectionName = data_get($product, 'section_name');
    $categoryName = data_get($product, 'category_name');
    $boostRemaining = data_get($product, 'boost_remaining_text');
    $shortDesc = data_get($product, 'short_description') ?? Str::limit(strip_tags((string) data_get($product, 'description')), 110);
    $viewsCount = (int) data_get($product, 'views_count', 0);
    $likesCount = (int) data_get($product, 'likes_count', 0);
    $productId = data_get($product, 'product_id', data_get($product, 'id'));
    $dbId = data_get($product, 'id');
    $campaignId = data_get($product, 'ad_campaign_id');
    $impressionsCount = (int) data_get($product, 'impressions_count', 0);
    $clicksCount = (int) data_get($product, 'clicks_count', 0);
    $ctr = $impressionsCount > 0 ? round(($clicksCount / $impressionsCount) * 100, 1) : 0.0;

    $tags = data_get($product, 'tags', []);
    if (is_string($tags)) {
        $tags = json_decode($tags, true) ?? array_filter(array_map('trim', explode(',', $tags)));
    }
@endphp

<div class="product-card {{ $isBoosted ? 'boosted' : '' }} {{ !$isBoosted && $isNewRelease ? 'new-release' : '' }}" 
     id="product-card-{{ $dbId }}"
     data-product-id="{{ $dbId }}"
     data-is-boosted="{{ $isBoosted ? '1' : '0' }}"
     data-campaign-id="{{ $campaignId ?? '' }}"
     data-product-name="{{ $productName }}">

    <!-- Floating Tier Badges -->
    <div class="card-floating-badges">
        @if($isBoosted)
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span class="badge-pill boosted">
                    ⚡ SPONSORED
                </span>
                @if($campaignId)
                    <span style="font-size: 10px; font-weight: 800; color: #a5b4fc; background: rgba(99,102,241,0.3); padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(129,140,248,0.4);" title="Ad Campaign ID">
                        Camp #{{ $campaignId }}
                    </span>
                @endif
                @if($boostRemaining)
                    <span class="badge-time-left">⏳ {{ $boostRemaining }}</span>
                @endif
            </div>
        @elseif($isNewRelease)
            <span class="badge-pill new">
                🔥 NEW RELEASE
            </span>
        @else
            <span style="font-size: 10px; font-weight: 700; color: #64748b; background: rgba(0,0,0,0.5); padding: 2px 7px; border-radius: 4px;">
                ORGANIC
            </span>
        @endif

        <span style="font-size: 10.5px; font-weight: 700; color: #94a3b8; background: rgba(0,0,0,0.65); padding: 3px 8px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.08);">
            #{{ $productId }}
        </span>
    </div>

    <!-- Product Image Media -->
    <div class="card-media" onclick='handleProductClick(@json($product))' style="cursor: pointer;">
        @if(!empty($imageUrl))
            <img src="{{ $imageUrl }}" alt="{{ $productName }}" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/600x400/1e293b/a5b4fc?text=Product+Image';">
        @else
            <div class="card-media-placeholder">
                <svg style="width: 42px; height: 42px; stroke: #475569; fill: none; margin-bottom: 6px;" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <div style="font-size: 12px; font-weight: 600;">{{ $productName }}</div>
            </div>
        @endif
    </div>

    <!-- Card Body Content -->
    <div class="card-body">
        <!-- Industry / Company Row -->
        <div class="company-row">
            <div class="company-mini-logo">
                @if(!empty($industryLogo))
                    <img src="{{ $industryLogo }}" alt="{{ $industryName }}" onerror="this.onerror=null; this.parentNode.innerHTML='{{ strtoupper(substr($industryName, 0, 1)) }}';">
                @else
                    {{ strtoupper(substr($industryName, 0, 1)) }}
                @endif
            </div>
            <div class="company-title-text" title="{{ $industryName }}">
                {{ Str::limit($industryName, 22) }}
            </div>
            @if($categoryName)
                <span style="margin-left: auto; font-size: 11px; font-weight: 700; color: #818cf8; background: rgba(99,102,241,0.12); padding: 2px 7px; border-radius: 4px;">
                    {{ $categoryName }}
                </span>
            @endif
        </div>

        <!-- Product Title -->
        <a href="javascript:void(0)" onclick='handleProductClick(@json($product))' class="product-title-link" title="{{ $productName }}">
            {{ $productName }}
        </a>

        <!-- Description Snippet -->
        <p class="product-description-snippet">
            {{ $shortDesc ?: 'High quality biotechnology and laboratory equipment designed for modern healthcare and research facilities.' }}
        </p>

        <!-- Live Ad Metric Ribbon for Boosted Products -->
        @if($isBoosted && $campaignId)
            <div class="ad-metric-ribbon" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(129, 140, 248, 0.3); border-radius: 8px; padding: 6px 10px; margin-bottom: 12px; font-size: 11.5px; display: flex; align-items: center; justify-content: space-between;">
                <span title="Real-time Verified IAB Impressions" style="color: #c7d2fe;">
                    👁️ <strong id="card-imp-{{ $campaignId }}">{{ number_format($impressionsCount) }}</strong> imps
                </span>
                <span title="Verified Ad Clicks" style="color: #c7d2fe;">
                    🖱️ <strong id="card-click-{{ $campaignId }}">{{ number_format($clicksCount) }}</strong> clicks
                </span>
                <span title="Click-Through Rate" style="color: #38bdf8; font-weight: 800;">
                    📈 <strong id="card-ctr-{{ $campaignId }}">{{ $ctr }}%</strong> CTR
                </span>
            </div>
        @endif

        <!-- Tags / Metadata -->
        <div class="card-meta-tags">
            @if($sectionName)
                <span class="meta-tag">📁 {{ $sectionName }}</span>
            @endif
            @if(is_array($tags) && count($tags) > 0)
                @foreach(array_slice($tags, 0, 2) as $t)
                    <span class="meta-tag">🏷️ {{ $t }}</span>
                @endforeach
            @endif
        </div>

        <!-- Card Bottom Action & Stats -->
        <div class="card-bottom-actions">
            <div class="views-stats">
                <span title="Product Details Unique Views">👁️ <span id="card-view-{{ $dbId }}">{{ number_format($viewsCount) }}</span> views</span>
                <span title="Product Likes">❤️ {{ number_format($likesCount) }}</span>
            </div>

            <button type="button" class="view-details-btn" onclick='handleProductClick(@json($product))'>
                View Details
                <svg style="width: 13px; height: 13px; stroke: currentColor; fill: none;" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </div>
    </div>
</div>
