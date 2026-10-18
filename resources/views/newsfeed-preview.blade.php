<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsfeed Ads Preview - Live Database Simulation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0b0f19;
            --bg-card: #111827;
            --bg-card-hover: #162032;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-ad: rgba(99, 102, 241, 0.4);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent-primary: #6366f1;
            --accent-primary-hover: #4f46e5;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --ad-bg-gradient: linear-gradient(180deg, rgba(30, 27, 75, 0.35) 0%, rgba(17, 24, 39, 0.95) 100%);
            --badge-bg: rgba(99, 102, 241, 0.15);
            --badge-text: #a5b4fc;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 24px 16px 80px;
        }

        .container {
            max-width: 1240px;
            margin: 0 auto;
        }

        /* Top Header Banner */
        .preview-banner {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.7) 0%, rgba(17, 24, 39, 0.9) 100%);
            border: 1px solid var(--border-ad);
            border-radius: 16px;
            padding: 24px 28px;
            margin-bottom: 28px;
            box-shadow: 0 10px 30px -10px rgba(99, 102, 241, 0.25);
            position: relative;
            overflow: hidden;
        }

        .preview-banner::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 70%);
            pointer-events: none;
        }

        .banner-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .banner-title-area h1 {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pulse-indicator {
            width: 10px;
            height: 10px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .banner-desc {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 6px;
            max-width: 800px;
        }

        .stats-badges {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .stat-badge {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .stat-badge strong {
            color: #fff;
        }

        .stat-badge.highlight {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.4);
            color: #c7d2fe;
        }

        /* Two Column Layout */
        .feed-layout {
            display: grid;
            grid-template-columns: minmax(0, 720px) 380px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .feed-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Feed List */
        .feed-stream {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Common Card Style */
        .feed-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 24px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .feed-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .slot-marker {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #1f2937;
            border: 1px solid var(--border-color);
            padding: 2px 10px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
        }

        /* Regular Post Card */
        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4b5563, #374151);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .post-author-info {
            flex: 1;
        }

        .author-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
        }

        .author-meta {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .post-content {
            font-size: 14.5px;
            color: #e5e7eb;
            white-space: pre-line;
            line-height: 1.65;
            margin-bottom: 16px;
        }

        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 13px;
            color: var(--text-secondary);
        }

        .post-actions {
            display: flex;
            gap: 16px;
        }

        .action-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        /* ========================================================
           SPONSORED BOOSTED AD CARD (PREMIUM & EYE-CATCHING)
           ======================================================== */
        .feed-card.sponsored-ad-card {
            background: var(--ad-bg-gradient);
            border: 1.5px solid rgba(99, 102, 241, 0.5);
            box-shadow: 0 12px 35px -8px rgba(99, 102, 241, 0.3);
            position: relative;
            overflow: hidden;
        }

        .feed-card.sponsored-ad-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient);
        }

        .sponsored-ad-card .slot-marker {
            background: rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.6);
            color: #c7d2fe;
        }

        .sponsored-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .sponsored-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--badge-bg);
            color: var(--badge-text);
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .sponsored-chip svg {
            width: 13px;
            height: 13px;
            fill: currentColor;
        }

        .company-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .company-logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #a5b4fc;
            font-size: 18px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .company-info .company-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .company-info .company-tagline {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Product Showcase Box inside Ad */
        .ad-product-showcase {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }

        .ad-product-showcase:hover {
            border-color: rgba(99, 102, 241, 0.4);
        }

        .ad-product-image {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .ad-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .ad-product-showcase:hover .ad-product-image img {
            transform: scale(1.02);
        }

        .ad-image-placeholder {
            text-align: center;
            color: #c7d2fe;
            padding: 20px;
        }

        .ad-image-placeholder svg {
            width: 48px;
            height: 48px;
            stroke: currentColor;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .ad-product-body {
            padding: 16px 18px;
        }

        .ad-product-title {
            font-size: 17px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
            line-height: 1.35;
        }

        .ad-product-desc {
            font-size: 13.5px;
            color: var(--text-secondary);
            line-height: 1.55;
            margin-bottom: 14px;
        }

        .ad-cta-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }

        .ad-destination {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .ad-cta-btn {
            background: var(--accent-gradient);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
            transition: all 0.2s ease;
        }

        .ad-cta-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
            filter: brightness(1.1);
        }

        /* Live Tracking Debug Bar */
        .ad-tracking-bar {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            margin-top: 12px;
            font-size: 11.5px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-family: monospace;
        }

        .tracking-status {
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Right Sidebar Explanations */
        .sidebar {
            position: sticky;
            top: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .info-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 22px;
        }

        .info-card h3 {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rules-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 13.5px;
            color: var(--text-secondary);
        }

        .rules-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            line-height: 1.5;
        }

        .rules-list .icon {
            color: var(--accent-primary);
            font-weight: 800;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .rules-list strong {
            color: #fff;
        }

        /* Live Toast Notification */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1e1b4b;
            border: 1px solid var(--accent-primary);
            color: #fff;
            padding: 14px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Banner -->
    <header class="preview-banner">
        <div class="banner-header">
            <div class="banner-title-area">
                <h1>
                    <span class="pulse-indicator"></span>
                    Newsfeed Live Ads Simulation
                </h1>
                <p class="banner-desc">
                    এটি আপনার লোকাল ডাটাবেজের বাস্তব পোস্ট (Real Database Posts) এবং <code>NewsfeedAdInjector</code>-এর মাধ্যমে স্বয়ংক্রিয়ভাবে ইনজেক্ট করা বুস্টেড স্পন্সরড প্রোডাক্ট অ্যাডের লাইভ প্রিভিউ।
                </p>
            </div>
        </div>

        <div class="stats-badges">
            <span class="stat-badge">📊 Total Items in Feed: <strong>{{ count($feed) }}</strong></span>
            <span class="stat-badge">📝 Original DB Posts: <strong>{{ $postsCount }}</strong></span>
            <span class="stat-badge highlight">🎯 Injected Ads: <strong>{{ count($feed) - $postsCount }}</strong></span>
            <span class="stat-badge">📌 1st Ad Position: <strong>Slot 4</strong> (after 4th post)</span>
            <span class="stat-badge">🔁 Interval: <strong>Every 8 posts</strong></span>
        </div>
    </header>

    <!-- Main Two-Column Layout -->
    <div class="feed-layout">
        <!-- Feed Stream -->
        <main class="feed-stream">
            @forelse($feed as $index => $item)
                @php
                    $slotNum = $index + 1;
                    $isSponsored = !empty($item['is_sponsored']) && $item['is_sponsored'];
                @endphp

                @if($isSponsored)
                    <!-- ==============================================
                         SPONSORED AD CARD (SLOT {{ $slotNum }})
                         ============================================== -->
                    <article class="feed-card sponsored-ad-card" id="ad-{{ $item['ad_campaign_id'] ?? $loop->index }}">
                        <span class="slot-marker">Slot {{ $slotNum }} • Sponsored Ad</span>

                        <div class="sponsored-top-bar">
                            <span class="sponsored-chip">
                                <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                {{ $item['badge'] ?? 'Sponsored' }}
                            </span>
                            <span style="font-size: 12px; color: #a5b4fc; font-weight: 600;">Promoted Product</span>
                        </div>

                        <!-- Advertiser / Company Info -->
                        <div class="company-header">
                            <div class="company-logo">
                                @php
                                    $compLogo = $item['company']['logo'] ?? null;
                                    if ($compLogo && !str_starts_with($compLogo, 'http')) {
                                        $compLogo = asset('storage/' . ltrim($compLogo, '/'));
                                    }
                                @endphp
                                @if(!empty($compLogo))
                                    <img src="{{ $compLogo }}" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <span style="display:none;">{{ substr($item['company']['name'] ?? 'AD', 0, 2) }}</span>
                                @else
                                    <span>{{ substr($item['company']['name'] ?? 'AD', 0, 2) }}</span>
                                @endif
                            </div>
                            <div class="company-info">
                                <div class="company-name">
                                    {{ $item['company']['name'] ?? 'Featured Advertiser' }}
                                    <svg style="width:14px;height:14px;fill:#6366f1" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div class="company-tagline">{{ $item['company']['tagline'] ?? 'Industry Leader in Innovation' }}</div>
                            </div>
                        </div>

                        <!-- Product Showcase -->
                        <div class="ad-product-showcase">
                            <div class="ad-product-image">
                                @php
                                    $adImg = $item['image'] ?? null;
                                    if ($adImg && !str_starts_with($adImg, 'http')) {
                                        $adImg = asset('storage/' . ltrim($adImg, '/'));
                                    }
                                @endphp
                                @if(!empty($adImg))
                                    <img src="{{ $adImg }}" alt="{{ $item['title'] }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="ad-image-placeholder" style="display:none;">
                                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <p style="font-weight:700;">{{ $item['title'] }}</p>
                                    </div>
                                @else
                                    <div class="ad-image-placeholder">
                                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <p style="font-weight:700;">{{ $item['title'] }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="ad-product-body">
                                <h2 class="ad-product-title">{{ $item['title'] }}</h2>
                                <p class="ad-product-desc">{{ $item['description'] ?? 'Discover advanced technological solutions engineered for modern researchers and professionals.' }}</p>

                                <div class="ad-cta-row">
                                    <span class="ad-destination">
                                        🌐 {{ parse_url($item['destination_url'] ?? 'vinintini.com', PHP_URL_HOST) ?? 'vinintini.com' }}
                                    </span>
                                    <button type="button" class="ad-cta-btn" onclick="trackAdClick({{ $item['ad_campaign_id'] }}, '{{ $item['destination_url'] ?? '#' }}')">
                                        <span>{{ $item['cta_text'] ?? 'View Product' }}</span>
                                        <svg style="width:16px;height:16px;fill:none;stroke:currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Debug & Tracking Status -->
                        <div class="ad-tracking-bar">
                            <span class="tracking-status">
                                <span>●</span> IAB Impression Tracked
                            </span>
                            <span>Campaign #{{ $item['ad_campaign_id'] }}</span>
                            <span>Placement: newsfeed</span>
                        </div>
                    </article>

                @else
                    <!-- ==============================================
                         REGULAR POST CARD (SLOT {{ $slotNum }})
                         ============================================== -->
                    <article class="feed-card" id="post-{{ $item['id'] }}">
                        <span class="slot-marker">Slot {{ $slotNum }} • Organic Post</span>

                        <div class="post-header">
                            @php
                                $firstName = $item['user']->first_name ?? 'User';
                                $lastName = $item['user']->last_name ?? '';
                                $fullName = trim("$firstName $lastName") ?: ($item['user']->username ?? 'Member');
                                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName ?: $firstName, 0, 1));
                            @endphp

                            <div class="avatar">
                                @if(!empty($item['user']->profile_image))
                                    <img src="{{ asset('storage/' . $item['user']->profile_image) }}" alt="{{ $fullName }}" class="avatar" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <span style="display:none;">{{ $initials }}</span>
                                @else
                                    <span>{{ $initials }}</span>
                                @endif
                            </div>

                            <div class="post-author-info">
                                <div class="author-name">{{ $fullName }}</div>
                                <div class="author-meta">
                                    <span>{{ $item['user']->title ?? '@' . ($item['user']->username ?? 'member') }}</span>
                                    <span>•</span>
                                    <span>{{ isset($item['created_at']) ? \Carbon\Carbon::parse($item['created_at'])->diffForHumans() : 'Recently' }}</span>
                                    <span>•</span>
                                    <span>🌍 {{ ucfirst($item['visibility'] ?? 'Public') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="post-content">
                            {{ $item['description'] }}
                        </div>

                        @if(!empty($item['media']) && count($item['media']) > 0)
                            <div style="border-radius: 12px; overflow: hidden; margin-bottom: 14px;">
                                @foreach($item['media'] as $media)
                                    <img src="{{ asset('storage/' . $media->file_path) }}" alt="Post media" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 10px;">
                                @endforeach
                            </div>
                        @endif

                        <div class="post-footer">
                            <div class="post-actions">
                                <button class="action-btn">
                                    👍 Like ({{ $item['total_like'] ?? 0 }})
                                </button>
                                <button class="action-btn">
                                    💬 Comment ({{ $item['total_comment'] ?? 0 }})
                                </button>
                                <button class="action-btn">
                                    🔗 Share
                                </button>
                            </div>
                            <span>Post #{{ $item['id'] }}</span>
                        </div>
                    </article>
                @endif

            @empty
                <div class="feed-card" style="text-align: center; padding: 40px;">
                    <p style="color: var(--text-muted); font-size: 16px;">ডাটাবেজে কোনো পোস্ট পাওয়া যায়নি।</p>
                </div>
            @endforelse
        </main>

        <!-- Sidebar Information -->
        <aside class="sidebar">
            <div class="info-card">
                <h3>⚙️ নিউজফিড অ্যাড লজিক</h3>
                <ul class="rules-list">
                    <li>
                        <span class="icon">১.</span>
                        <div><strong>স্লট নির্ধারণ:</strong> প্রথম বুস্টেড অ্যাডটি ঠিক <strong>৪র্থ পোস্টের পর (Slot 4)</strong> প্রবেশ করানো হয়।</div>
                    </li>
                    <li>
                        <span class="icon">২.</span>
                        <div><strong>পরবর্তী ব্যবধান:</strong> এর পরবর্তী প্রতিটি অ্যাড প্রতি <strong>৮টি পোস্ট পর পর</strong> ফিডে আসবে।</div>
                    </li>
                    <li>
                        <span class="icon">৩.</span>
                        <div><strong>Fair-Play রোটেশন:</strong> যে সকল অ্যাক্টিভ ক্যাম্পেইনের ভিউ কম হয়েছে, সিস্টেম স্বয়ংক্রিয়ভাবে তাদের আগে সুযোগ দেয়।</div>
                    </li>
                    <li>
                        <span class="icon">৪.</span>
                        <div><strong>Anti-Fatigue Capping:</strong> একই অ্যাড কোনো সিঙ্গেল ইউজারকে দিনে ৫ বারের বেশি দেখানো হয় না।</div>
                    </li>
                </ul>
            </div>

            <div class="info-card" style="border-color: rgba(16, 185, 129, 0.3);">
                <h3 style="color: #6ee7b7;">🎯 লাইভ ট্র্যাকিং টেস্ট</h3>
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 14px;">
                    স্পন্সরড কার্ডের <strong>"View Product"</strong> বাটনে ক্লিক করলে ব্যাকএন্ডের ট্র্যাকিং এপিআই (<code>POST /api/advertisements/{id}/click</code>) কল হয় এবং ক্লিক কাউন্ট বেড়ে যায়।
                </p>
                <div style="background: rgba(0,0,0,0.3); padding: 10px; border-radius: 8px; font-size: 12px; color: #a7f3d0; font-family: monospace;">
                    API: /api/advertisements/{id}/click
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast" id="clickToast">
    <span>✅ Click recorded on backend!</span>
    <span id="toastMsg" style="color: #c7d2fe;">Redirecting...</span>
</div>

<script>
    function trackAdClick(campaignId, destinationUrl) {
        const toast = document.getElementById('clickToast');
        const toastMsg = document.getElementById('toastMsg');
        
        toast.style.display = 'flex';
        toastMsg.textContent = 'Calling API for Campaign #' + campaignId + '...';

        fetch('/api/advertisements/' + campaignId + '/click', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ placement: 'newsfeed' })
        })
        .then(res => res.json())
        .then(data => {
            toastMsg.textContent = 'Recorded! Destination: ' + (data.data?.destination_url || destinationUrl);
            setTimeout(() => {
                window.open(data.data?.destination_url || destinationUrl, '_blank');
            }, 1000);
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        })
        .catch(err => {
            toastMsg.textContent = 'Simulated click recorded!';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 2500);
        });
    }
</script>

</body>
</html>
