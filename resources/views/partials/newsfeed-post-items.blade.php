@forelse($feed as $index => $item)
    @php
        $isSponsored = !empty($item['is_sponsored']) && $item['is_sponsored'];
    @endphp

    @if($isSponsored)
        <!-- ==============================================
             SPONSORED AD CARD (Matching Food Panda / Softvence Delta style)
             ============================================== -->
        @php
            $campaignId = $item['ad_campaign_id'] ?? null;
            $industryData = $item['industry'] ?? $item['company'] ?? [];
            $companyName = $industryData['name'] ?? 'MindUnite Partner';
            $companyTagline = $industryData['tagline'] ?? 'Make life delicious and innovative!';
            $compLogo = $industryData['logo'] ?? null;
            if ($compLogo && !str_starts_with($compLogo, 'http')) {
                $compLogo = asset('storage/' . ltrim($compLogo, '/'));
            }

            $adImage = $item['image'] ?? null;
            if ($adImage && !str_starts_with($adImage, 'http')) {
                $adImage = asset('storage/' . ltrim($adImage, '/'));
            }

            $productDetails = $item['product_details'] ?? [];
            $productName = $item['product_name'] ?? $item['title'] ?? 'Sponsored Product';
            $productId = $item['product_id'] ?? ($productDetails['product_id'] ?? null);
            $productDbId = $item['product_db_id'] ?? ($productDetails['id'] ?? $productId);
            $likesCount = (int) ($item['likes_count'] ?? ($productDetails['likes_count'] ?? 0));
            $viewsCount = (int) ($item['views_count'] ?? ($productDetails['views_count'] ?? 0));
            $isLiked = !empty($item['is_liked']) || !empty($productDetails['is_liked']);
            $sectionName = $productDetails['section_name'] ?? null;
            $categoryName = $productDetails['category_name'] ?? null;
            $tags = $productDetails['tags'] ?? [];
            $domain = $item['destination_domain'] ?? null;
            $isNewRelease = !empty($productDetails['is_new_release']);
            $pocName = $productDetails['poc_name'] ?? null;
            $pocEmail = $productDetails['poc_email'] ?? null;
            $pocPhone = $productDetails['poc_phone'] ?? null;
        @endphp

        <article class="post-card sponsored-ad-card linkedin-ad-card" 
                 id="ad-campaign-{{ $campaignId }}" 
                 data-campaign-id="{{ $campaignId }}" 
                 data-ad-title="{{ $productName }}">

            <!-- LinkedIn Sponsored Header -->
            <div class="linkedin-ad-header">
                <div class="linkedin-ad-author-col">
                    <div class="linkedin-ad-logo" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                        @if(!empty($compLogo))
                            <img src="{{ $compLogo }}" alt="{{ $companyName }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#f3f2ef; color:#0a66c2; font-weight:800; font-size:16px;">
                                {{ substr($companyName, 0, 2) }}
                            </div>
                        @else
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f3f2ef; color:#0a66c2; font-weight:800; font-size:16px;">
                                {{ substr($companyName, 0, 2) }}
                            </div>
                        @endif
                    </div>
                    <div class="linkedin-ad-meta">
                        <div class="linkedin-author-name" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                            {{ $companyName }}
                        </div>
                        <div class="linkedin-author-tagline">{{ $companyTagline }}</div>
                        <div class="linkedin-promoted-meta">
                            <span>Promoted</span>
                            <span>&bull;</span>
                            <svg viewBox="0 0 16 16"><path d="M8 1a7 7 0 107 7 7 7 0 00-7-7zM2.5 8a5.46 5.46 0 01.35-1.92L6 9.2V10a1 1 0 001 1v1.93A5.5 5.5 0 012.5 8zm8.9 4.39A5.5 5.5 0 018 13.5v-1.5a1 1 0 00-1-1h-.5v-2H8a1 1 0 001-1V7h1a1 1 0 001-1V5.79A5.47 5.47 0 0113.5 8a5.45 5.45 0 01-2.1 4.39z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="linkedin-header-actions">
                    <button type="button" class="linkedin-follow-btn">
                        <svg viewBox="0 0 16 16" width="13" height="13" fill="#0a66c2"><path d="M14 9H9v5H7V9H2V7h5V2h2v5h5z"/></svg>
                        <span>Follow</span>
                    </button>
                    <button type="button" class="icon-more-btn" title="More options">
                        <svg viewBox="0 0 24 24"><path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                    </button>
                </div>
            </div>

            <!-- LinkedIn Post Body Caption -->
            <div class="post-body-text" id="ad-caption-{{ $campaignId }}">
                @php
                    $caption = $item['description'] ?? 'Discover advanced technological solutions engineered for brain health and scientific research.';
                    $isLong = mb_strlen($caption) > 180;
                    $shortCaption = $isLong ? mb_substr($caption, 0, 180) . '...' : $caption;
                @endphp
                <span id="ad-caption-trunc-{{ $campaignId }}">{{ $shortCaption }}</span>
                @if($isLong)
                    <span id="ad-caption-full-{{ $campaignId }}" style="display: none;">{{ $caption }}</span>
                    <a href="javascript:void(0)" class="see-more-link" onclick="toggleAdCaption({{ $campaignId }}, this)">see more</a>
                @endif
            </div>

            <!-- LinkedIn Ad Creative Banner (Edge-to-Edge) -->
            <div class="post-media-container" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                @if(!empty($adImage))
                    <img src="{{ $adImage }}" alt="{{ $productName }}" class="post-media-img" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80';">
                @else
                    <img src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80" alt="{{ $productName }}" class="post-media-img">
                @endif
            </div>

            <!-- LinkedIn Signature Link Preview Box (Flush Below Banner) -->
            <div class="linkedin-preview-box" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                <div class="linkedin-preview-left">
                    <div class="linkedin-domain-row">
                        <span>{{ $domain ?: 'mindunite.com' }}</span>
                        @if($productId)
                            <span class="linkedin-sku-tag">ID: #{{ $productId }}</span>
                        @endif
                        @if($isNewRelease)
                            <span class="linkedin-new-release-tag">🔥 New</span>
                        @endif
                    </div>
                    <h4 class="linkedin-product-title" title="{{ $productName }}">
                        {{ $productName }}
                    </h4>
                    <div class="linkedin-meta-desc">
                        @if($sectionName)
                            <span>{{ $sectionName }}</span>
                        @endif
                        @if($sectionName && $categoryName)
                            <span>&bull;</span>
                        @endif
                        @if($categoryName)
                            <span style="color:#0a66c2; font-weight:600;">{{ $categoryName }}</span>
                        @endif
                        @if($pocName || $pocEmail)
                            <span>&bull;</span>
                            <span>POC: {{ $pocName ?: $pocEmail }}</span>
                        @endif
                    </div>
                    @if(!empty($tags) && count($tags) > 0)
                        <div style="display:flex; align-items:center; gap:5px; margin-top:3px;">
                            @foreach(array_slice($tags, 0, 3) as $t)
                                 <span class="linkedin-tag-chip">#{{ $t }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="linkedin-preview-right">
                    <button type="button" class="linkedin-cta-btn" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                        {{ $item['cta_text'] ?? 'Learn more' }}
                    </button>
                </div>
            </div>

            <!-- Real-time Live Analytics & Simulation Bar -->
            <div class="linkedin-metric-bar">
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <span>👁️ Impressions: <strong id="card-imp-{{ $campaignId }}" style="color:#0a66c2;">{{ number_format($item['impressions_count'] ?? 0) }}</strong></span>
                    <span>🖱️ Clicks: <strong id="card-click-{{ $campaignId }}" style="color:#059669;">{{ number_format($item['clicks_count'] ?? 0) }}</strong></span>
                    <span style="color:#cbd5e1;">|</span>
                    <span style="color:#64748b;">CTR: <strong id="card-ctr-{{ $campaignId }}">{{ $item['impressions_count'] > 0 ? number_format(($item['clicks_count'] / $item['impressions_count']) * 100, 1) : 0 }}%</strong></span>
                </div>
                <button type="button" class="linkedin-specs-modal-btn" onclick="event.stopPropagation(); openProductQuickView({{ json_encode($item) }})">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                    <span>Quick View Modal</span>
                </button>
            </div>

            <!-- LinkedIn Engagement Stats Row (Likes + Total Views Count, No Comments) -->
            <div class="post-stats-row">
                <span style="display:flex; align-items:center; gap:5px;">
                    <span style="background:#0a66c2; color:#fff; border-radius:50%; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center; font-size:10px;">👍</span>
                    <span id="card-likes-count-{{ $campaignId }}">{{ number_format($likesCount) }} {{ $likesCount === 1 ? 'like' : 'likes' }}</span>
                </span>
                <span style="display:flex; align-items:center; gap:4px; color:#64748b; font-size:12px; font-weight:500;">
                    <span id="card-views-count-{{ $campaignId }}">{{ number_format($viewsCount) }} {{ $viewsCount === 1 ? 'view' : 'views' }}</span>
                </span>
            </div>

            <!-- LinkedIn Action Buttons Row (Like + Product Details Modal) -->
            <div class="post-actions-row">
                <button type="button" 
                        class="post-action-btn like-btn {{ $isLiked ? 'liked' : '' }}" 
                        id="card-like-btn-{{ $campaignId }}" 
                        onclick="toggleProductLike('{{ $productDbId }}', {{ $campaignId }}, '{{ addslashes($productName) }}', this)">
                    <svg viewBox="0 0 24 24"><path d="M19.46 11l-3.91-3.91a7 7 0 0 1-1.69-2.74l-.49-1.47A2.76 2.76 0 0 0 10.76 1 2.75 2.75 0 0 0 8 3.74v1.12a9.19 9.19 0 0 0 .46 2.85L8.89 9H4.12A2.74 2.74 0 0 0 1.5 11.45a2.58 2.58 0 0 0 .54 1.63L5 18.52a3 3 0 0 0 2.45 1.48h8.86a3 3 0 0 0 2.87-2.12l1.9-6.41A1.75 1.75 0 0 0 21 11a1.76 1.76 0 0 0-1.54 0z"/></svg>
                    <span class="like-btn-text">{{ $isLiked ? 'Liked' : 'Like' }}</span>
                </button>
                <button type="button" 
                        class="post-action-btn details-btn" 
                        id="card-details-btn-{{ $campaignId }}" 
                        onclick="openProductQuickView({{ json_encode($item) }})">
                    <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    <span>Details</span>
                </button>
            </div>
        </article>

    @else
        <!-- ==============================================
             ORGANIC USER POST CARD (Matching screenshot style)
             ============================================== -->
        @php
            $user = $item['user'] ?? null;
            $firstName = $user->first_name ?? 'Member';
            $lastName = $user->last_name ?? '';
            $fullName = trim("$firstName $lastName") ?: ($user->username ?? 'Member');
            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1));
            $title = $user->title ?? 'Limitless Together';
            $profileImg = $user->profile_image ?? null;
            if ($profileImg && !str_starts_with($profileImg, 'http')) {
                $profileImg = asset('storage/' . ltrim($profileImg, '/'));
            }
        @endphp

        <article class="post-card" id="post-{{ $item['id'] }}">
            <div class="post-card-header">
                <div class="post-header-left">
                    <div class="post-avatar-wrap circle">
                        @if(!empty($profileImg))
                            <img src="{{ $profileImg }}" alt="{{ $fullName }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display:none; width:100%; height:100%; align-items:center; justify-content:center; background:#e0f2fe; color:#0284c7; font-weight:700;">
                                {{ $initials }}
                            </div>
                        @else
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#e0f2fe; color:#0284c7; font-weight:700;">
                                {{ $initials }}
                            </div>
                        @endif
                    </div>
                    <div class="post-meta-col">
                        <div class="post-author-name">{{ $fullName }}</div>
                        <div class="post-author-subtitle">{{ $title }}</div>
                        <div class="post-time-meta">{{ isset($item['created_at']) ? \Carbon\Carbon::parse($item['created_at'])->diffForHumans() : 'a day ago' }}</div>
                    </div>
                </div>
                <div class="post-header-right">
                    <button type="button" class="icon-more-btn" title="Options">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
                    </button>
                </div>
            </div>

            <div class="post-body-text" id="post-body-{{ $item['id'] }}">
                @php
                    $rawDesc = $item['description'] ?? '';
                    $hasHtml = strip_tags($rawDesc) !== $rawDesc;
                    $cleanText = trim(strip_tags(html_entity_decode($rawDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                    $isLong = mb_strlen($cleanText) > 220;
                @endphp

                @if($hasHtml)
                    @php
                        $safeHtml = strip_tags($rawDesc, '<p><br><strong><b><em><i><u><a><span><ul><ol><li>');
                        $safeHtml = preg_replace('/\s*(style|id|dir|class)="[^"]*"/i', '', $safeHtml);
                    @endphp
                    <div class="post-content-html {{ $isLong ? 'post-content-clamped' : '' }}" id="post-content-{{ $item['id'] }}">
                        {!! $safeHtml !!}
                    </div>
                    @if($isLong)
                        <a href="javascript:void(0)" class="see-more-link" onclick="togglePostContent({{ $item['id'] }}, this)">...see more</a>
                    @endif
                @else
                    @php
                        $shortText = $isLong ? mb_substr($cleanText, 0, 220) . '...' : $cleanText;
                    @endphp
                    <span id="post-text-trunc-{{ $item['id'] }}">{!! nl2br(e($shortText)) !!}</span>
                    @if($isLong)
                        <span id="post-text-full-{{ $item['id'] }}" style="display: none;">{!! nl2br(e($cleanText)) !!}</span>
                        <a href="javascript:void(0)" class="see-more-link" onclick="togglePostText({{ $item['id'] }}, this)">see more</a>
                    @endif
                @endif
            </div>

            <!-- Post Media Images (with live file support and default fallback) -->
            @php
                $defaultPostImages = [
                    'https://images.unsplash.com/photo-1507668077129-56e32842fceb?auto=format&fit=crop&w=800&q=80', // Neuroscience / Brain tech
                    'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=800&q=80', // Laboratory / Clinical research
                    'https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=800&q=80', // Tech & Neural Network
                    'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=800&q=80', // Microchips & BioTech
                    'https://images.unsplash.com/photo-1559757175-5700dde675bc?auto=format&fit=crop&w=800&q=80', // Medical Research
                    'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80', // Psychology & Connectivity
                ];
                $fallbackDefaultImg = $defaultPostImages[abs((int)($item['id'] ?? 0)) % count($defaultPostImages)];
                $hasMedia = !empty($item['media']) && count($item['media']) > 0;
            @endphp

            <div class="post-media-container">
                @if($hasMedia)
                    @foreach($item['media'] as $media)
                        @php
                            $mediaPath = ltrim($media->file_path ?? '', '/');
                            $mediaUrl = str_starts_with($mediaPath, 'http') ? $mediaPath : asset('storage/' . $mediaPath);
                        @endphp
                        @if(($media->type ?? '') === 'video')
                            <video controls class="post-media-img" style="max-height: 480px; width: 100%; background: #000;" poster="{{ $fallbackDefaultImg }}">
                                <source src="{{ $mediaUrl }}" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        @else
                            <img src="{{ $mediaUrl }}" 
                                 alt="Post image" 
                                 class="post-media-img" 
                                 onerror="this.onerror=null; this.src='{{ $fallbackDefaultImg }}';">
                        @endif
                    @endforeach
                @else
                    <img src="{{ $fallbackDefaultImg }}" 
                         alt="Post image" 
                         class="post-media-img">
                @endif
            </div>

            <div class="post-stats-row">
                <span>{{ $item['total_like'] ?? 0 }} likes</span>
                <span>{{ $item['total_comment'] ?? 0 }} comments</span>
            </div>

            <div class="post-actions-row">
                <button type="button" class="post-action-btn">
                    <svg viewBox="0 0 24 24"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>
                    <span>Like</span>
                </button>
                <button type="button" class="post-action-btn">
                    <svg viewBox="0 0 24 24"><path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                    <span>Comments</span>
                </button>
            </div>
        </article>
    @endif

@empty
    <div class="post-card" style="padding: 48px 20px; text-align: center; color: #94a3b8;">
        <p style="font-size: 15px; font-weight: 600;">No posts in feed right now.</p>
    </div>
@endforelse
