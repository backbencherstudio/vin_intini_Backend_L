@php
    $isBoosted = !empty($prod['is_boosted']);
    $isNewRelease = !empty($prod['is_new_release']);
    $campaignId = $prod['ad_campaign_id'] ?? null;
    $isExtra = $isExtra ?? false;
    $sectionId = $sectionId ?? ($prod['section_id'] ?? 0);
    $searchBlob = strtolower(($prod['product_name'] ?? '').' '.($prod['product_id'] ?? '').' '.($prod['category_name'] ?? '').' '.implode(' ', (array) ($prod['tags'] ?? [])));
@endphp

<div class="product-card {{ $isBoosted ? 'boosted' : '' }} {{ !$isBoosted && $isNewRelease ? 'new-release' : '' }} {{ $isExtra ? 'extra-card' : '' }}" 
     data-section-id="{{ $sectionId }}"
     data-category-id="{{ $prod['category_id'] ?? '' }}"
     data-product-name="{{ strtolower($prod['product_name'] ?? '') }}"
     data-product-id="{{ strtolower($prod['product_id'] ?? '') }}"
     data-search-content="{{ $searchBlob }}"
     @if($isExtra) style="display: none;" @endif
     @if($isBoosted && $campaignId)
         data-campaign-id="{{ $campaignId }}"
     @endif>

    <!-- Boosted / New Release Badge Ribbon -->
    @if($isBoosted)
        <div class="boosted-ribbon">
            <span>⚡</span>
            <span>Sponsored</span>
        </div>
    @elseif($isNewRelease)
        <div class="new-release-ribbon">
            <span>🔥</span>
            <span>New Release</span>
        </div>
    @endif

    <!-- Image Area: Authentic Cyan Brain Graphic or Uploaded Product Image -->
    <div class="card-image-wrap" onclick="handleCardClick({{ json_encode($prod) }})">
        @if(!empty($prod['image_url']) && !str_contains($prod['image_url'], 'placeholder'))
            <img src="{{ $prod['image_url'] }}" alt="{{ $prod['product_name'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="brain-cyan-illustration" style="display:none;">
                <svg viewBox="0 0 100 80">
                    <circle cx="50" cy="40" r="30" fill="none" stroke="#0ea5e9" stroke-width="4" stroke-dasharray="10 6"/>
                    <path d="M30 40 Q40 20 50 40 T70 40" fill="none" stroke="#38bdf8" stroke-width="4.5" stroke-linecap="round"/>
                    <path d="M35 50 Q50 35 65 50" fill="none" stroke="#0284c7" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="30" cy="40" r="4.5" fill="#38bdf8"/>
                    <circle cx="50" cy="40" r="5.5" fill="#38bdf8"/>
                    <circle cx="70" cy="40" r="4.5" fill="#38bdf8"/>
                    <circle cx="50" cy="22" r="4" fill="#0284c7"/>
                    <circle cx="65" cy="50" r="4" fill="#0284c7"/>
                </svg>
            </div>
        @else
            <div class="brain-cyan-illustration">
                <svg viewBox="0 0 100 80">
                    <circle cx="50" cy="40" r="30" fill="none" stroke="#0ea5e9" stroke-width="4" stroke-dasharray="10 6"/>
                    <path d="M30 40 Q40 20 50 40 T70 40" fill="none" stroke="#38bdf8" stroke-width="4.5" stroke-linecap="round"/>
                    <path d="M35 50 Q50 35 65 50" fill="none" stroke="#0284c7" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="30" cy="40" r="4.5" fill="#38bdf8"/>
                    <circle cx="50" cy="40" r="5.5" fill="#38bdf8"/>
                    <circle cx="70" cy="40" r="4.5" fill="#38bdf8"/>
                    <circle cx="50" cy="22" r="4" fill="#0284c7"/>
                    <circle cx="65" cy="50" r="4" fill="#0284c7"/>
                </svg>
            </div>
        @endif
    </div>

    <!-- Card Content Body -->
    <div class="card-body">
        <div class="card-title-row">
            <h3 class="card-title">{{ $prod['product_name'] }}</h3>
            @if(!empty($prod['tags']) && is_array($prod['tags']) && count($prod['tags']) > 0)
                <span class="card-tag-badge">{{ $prod['tags'][0] }}</span>
            @elseif(!empty($prod['category_name']))
                <span class="card-tag-badge">{{ Str::limit($prod['category_name'], 12) }}</span>
            @endif
        </div>

        <div class="card-subtitle">
            {{ $prod['industry']['name'] ?? $prod['creator']['name'] ?? 'MindUnite Verified Partner' }}
        </div>

        <p class="card-desc">
            {{ Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode($prod['description'] ?? 'High resolution diagnostic and research apparatus engineered for brain health.', ENT_QUOTES | ENT_HTML5, 'UTF-8')))), 130) }}
        </p>

        <div style="margin-top: auto; display: flex; align-items: center; justify-content: space-between;">
            <a href="javascript:void(0)" class="learn-more-link" onclick="handleCardClick({{ json_encode($prod) }})">
                <span>Learn more</span>
                <span>&gt;</span>
            </a>
        </div>

        <!-- Real-time metrics for Boosted products -->
        @if($isBoosted && $campaignId)
            <div class="card-metrics-pill-bar">
                <span>👁️ Imp: <strong class="card-imp-val card-imp-{{ $campaignId }}" id="card-imp-{{ $campaignId }}" style="color:#0284c7;">{{ number_format($prod['impressions_count'] ?? 0) }}</strong></span>
                <span>🖱️ Clicks: <strong class="card-click-val card-click-{{ $campaignId }}" id="card-click-{{ $campaignId }}" style="color:#ec4899;">{{ number_format($prod['clicks_count'] ?? 0) }}</strong></span>
            </div>
        @endif
    </div>

    <!-- Card Footer (Like & Views) -->
    <div class="card-footer">
        <button type="button" 
                class="card-action-btn like-btn {{ !empty($prod['is_liked']) ? 'liked' : '' }} card-like-btn-{{ $prod['id'] }}" 
                id="card-like-btn-{{ $prod['id'] }}"
                onclick="toggleProductLike({{ $prod['id'] }}, '{{ addslashes($prod['product_name']) }}', this, event)"
                title="Like this product">
            <span>👍</span>
            <span class="like-label card-like-label-{{ $prod['id'] }}" id="card-like-label-{{ $prod['id'] }}">{{ !empty($prod['is_liked']) ? 'Liked' : 'Like' }}</span>
            <span class="like-count card-likes-count-{{ $prod['id'] }}" id="card-likes-count-{{ $prod['id'] }}" style="margin-left: 2px; font-weight: 700;">({{ number_format($prod['likes_count'] ?? 0) }})</span>
        </button>
        <div style="display:flex; align-items:center; gap:5px; font-size:12px; color:#64748b; font-weight:600;">
            <span>👁️</span>
            <span class="card-views-count-{{ $prod['id'] }}" id="card-views-count-{{ $prod['id'] }}">{{ number_format($prod['views_count'] ?? 0) }}</span>
            <span>views</span>
        </div>
    </div>
</div>
