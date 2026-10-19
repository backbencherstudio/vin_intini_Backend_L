<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industry Product Feed - Live Database Simulation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #0a0d14;
            --bg-card: #111622;
            --bg-card-hover: #172033;
            --border-color: rgba(255, 255, 255, 0.08);
            --border-boosted: rgba(129, 140, 248, 0.5);
            --border-new: rgba(16, 185, 129, 0.5);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-primary: #6366f1;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%);
            --boosted-card-bg: linear-gradient(180deg, rgba(30, 27, 75, 0.45) 0%, rgba(17, 22, 34, 0.95) 100%);
            --new-card-bg: linear-gradient(180deg, rgba(6, 78, 59, 0.25) 0%, rgba(17, 22, 34, 0.95) 100%);
            --badge-boosted-bg: rgba(99, 102, 241, 0.2);
            --badge-boosted-text: #c7d2fe;
            --badge-new-bg: rgba(16, 185, 129, 0.2);
            --badge-new-text: #6ee7b7;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 24px 20px 80px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Top Header Area */
        .feed-header-banner {
            background: linear-gradient(135deg, rgba(30, 27, 75, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid var(--border-boosted);
            border-radius: 20px;
            padding: 28px 32px;
            margin-bottom: 28px;
            box-shadow: 0 16px 36px -12px rgba(99, 102, 241, 0.25);
            position: relative;
            overflow: hidden;
        }

        .feed-header-banner::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 400px;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.2), transparent 70%);
            pointer-events: none;
        }

        .banner-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .title-block h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .live-dot {
            width: 11px;
            height: 11px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: livePulse 2s infinite;
        }

        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .endpoint-badge {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #a5b4fc;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 13px;
        }

        .banner-desc {
            color: var(--text-secondary);
            font-size: 14.5px;
            margin-top: 8px;
            max-width: 900px;
        }

        .stats-ribbon {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .stat-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-chip strong {
            color: #fff;
        }

        .stat-chip.boosted {
            background: rgba(99, 102, 241, 0.2);
            border-color: rgba(99, 102, 241, 0.5);
            color: #c7d2fe;
        }

        .stat-chip.new {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.4);
            color: #a7f3d0;
        }

        /* 3-Tier Hierarchy Explanation Legend */
        .tier-legend {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }

        .tier-legend-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tier-legend-items {
            display: flex;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 13px;
        }

        .tier-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-secondary);
        }

        .tier-indicator {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        .tier-indicator.boosted { background: #818cf8; box-shadow: 0 0 8px #818cf8; }
        .tier-indicator.new { background: #34d399; box-shadow: 0 0 8px #34d399; }
        .tier-indicator.organic { background: #64748b; }

        /* Filter Controls Bar */
        .filters-toolbar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 14px 20px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }

        .filter-groups {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .filter-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .filter-btn.active {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: #fff;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        .search-box {
            position: relative;
            min-width: 260px;
        }

        .search-box input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 8px 14px 8px 36px;
            color: #fff;
            font-size: 13.5px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-box input:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            fill: none;
            stroke: var(--text-muted);
        }

        /* Section Container */
        .section-block {
            margin-bottom: 44px;
        }

        .section-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            flex-wrap: wrap;
            gap: 12px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-badge {
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 6px;
        }

        .category-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cat-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .cat-pill:hover, .cat-pill.active {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            color: #c7d2fe;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 22px;
        }

        /* Product Card Base */
        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 14px 30px -10px rgba(0, 0, 0, 0.6);
        }

        /* Boosted Product Card (Sponsored) */
        .product-card.boosted {
            background: var(--boosted-card-bg);
            border: 1.5px solid var(--border-boosted);
            box-shadow: 0 10px 28px -8px rgba(99, 102, 241, 0.3);
        }

        .product-card.boosted::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-gradient);
            z-index: 2;
        }

        /* New Release Product Card */
        .product-card.new-release {
            background: var(--new-card-bg);
            border: 1.5px solid var(--border-new);
            box-shadow: 0 10px 24px -8px rgba(16, 185, 129, 0.25);
        }

        .product-card.new-release::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #10b981, #06b6d4);
            z-index: 2;
        }

        /* Product Card Image */
        .card-media {
            width: 100%;
            height: 200px;
            background: #0f172a;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .card-media img {
            transform: scale(1.04);
        }

        .card-media-placeholder {
            color: #64748b;
            text-align: center;
            padding: 20px;
        }

        /* Badges over image */
        .card-floating-badges {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            z-index: 3;
            pointer-events: none;
        }

        .badge-pill {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(8px);
        }

        .badge-pill.boosted {
            background: rgba(30, 27, 75, 0.9);
            color: #c7d2fe;
            border: 1px solid rgba(129, 140, 248, 0.5);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        .badge-pill.new {
            background: rgba(6, 78, 59, 0.9);
            color: #a7f3d0;
            border: 1px solid rgba(52, 211, 153, 0.5);
        }

        .badge-time-left {
            background: rgba(0, 0, 0, 0.75);
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Card Body */
        .card-body {
            padding: 18px 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .company-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .company-mini-logo {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            color: #a5b4fc;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .company-mini-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .company-title-text {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .product-title-link {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            line-height: 1.4;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.15s ease;
        }

        .product-title-link:hover {
            color: var(--accent-primary);
        }

        .product-description-snippet {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .card-meta-tags {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .meta-tag {
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .card-bottom-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin-top: auto;
        }

        .views-stats {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-details-btn {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .view-details-btn:hover {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .product-card.boosted .view-details-btn {
            background: var(--accent-gradient);
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        /* Empty State */
        .empty-section {
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        /* Modal Simulation */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal-content {
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            max-width: 640px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            animation: modalIn 0.25s ease;
        }

        @keyframes modalIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 800;
            color: #fff;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
        }

        /* Real-time Toast Notifications */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10000;
            max-width: 440px;
            pointer-events: none;
        }

        .toast-card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 16px 36px -8px rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            pointer-events: auto;
            animation: slideInRight 0.3s ease;
            font-size: 13px;
            line-height: 1.45;
        }

        .toast-card.impression {
            border: 1px solid rgba(129, 140, 248, 0.6);
            box-shadow: 0 0 24px -4px rgba(99, 102, 241, 0.4);
        }

        .toast-card.click {
            border: 1px solid rgba(244, 114, 182, 0.6);
            box-shadow: 0 0 24px -4px rgba(236, 72, 153, 0.4);
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* Live Activity Panel */
        .live-activity-panel {
            background: rgba(17, 24, 39, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 18px 22px;
            margin-top: 36px;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .live-activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .log-terminal {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 14px 16px;
            max-height: 170px;
            overflow-y: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .log-entry {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
        }

        .log-entry.imp {
            color: #c7d2fe;
        }

        .log-entry.clk {
            color: #fbcfe8;
        }

        /* Sticky Floating Tracking Monitor Bar */
        .sticky-tracking-bar {
            position: fixed;
            bottom: 20px;
            left: 24px;
            background: rgba(15, 23, 42, 0.94);
            border: 1px solid rgba(129, 140, 248, 0.4);
            backdrop-filter: blur(14px);
            border-radius: 9999px;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.75);
            z-index: 9998;
            font-size: 13px;
        }

        .sticky-tracking-bar .toggle-log-btn {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(129, 140, 248, 0.5);
            color: #c7d2fe;
            border-radius: 9999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .sticky-tracking-bar .toggle-log-btn:hover {
            background: #6366f1;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header Banner -->
    <header class="feed-header-banner">
        <div class="banner-top-row">
            <div class="title-block">
                <h1>
                    <span class="live-dot"></span>
                    Industry Product Feed Simulation
                </h1>
                <p class="banner-desc">
                    এটি আপনার লোকাল ডাটাবেজের বাস্তব প্রোডাক্ট (Real Database Products) এবং <code>IndustryProductController::productFeed</code>-এর মাধ্যমে ৩-স্তরের অগ্রাধিকার (3-Tier Hierarchy) র‍্যাঙ্কিংয়ের লাইভ প্রিভিউ।
                </p>
            </div>
            <div class="endpoint-badge">
                GET /api/industry/product-feed
            </div>
        </div>

        <div class="stats-ribbon">
            <span class="stat-chip">📦 Total Active: <strong>{{ $stats['total_active'] }}</strong></span>
            <span class="stat-chip boosted">⚡ Boosted (Sponsored): <strong>{{ $stats['boosted_count'] }}</strong></span>
            <span class="stat-chip new">🔥 New Releases: <strong>{{ $stats['new_release_count'] }}</strong></span>
            <span class="stat-chip">🏢 Organic Standard: <strong>{{ $stats['organic_count'] }}</strong></span>
            <span class="stat-chip" style="background: rgba(99, 102, 241, 0.25); border-color: rgba(129, 140, 248, 0.4); color: #c7d2fe;">
                👁️ Boost Impressions: <strong id="top-total-impressions">{{ number_format($stats['total_impressions']) }}</strong>
            </span>
            <span class="stat-chip" style="background: rgba(236, 72, 153, 0.25); border-color: rgba(244, 114, 182, 0.4); color: #fbcfe8;">
                🖱️ Boost Clicks: <strong id="top-total-clicks">{{ number_format($stats['total_clicks']) }}</strong>
            </span>
            <span class="stat-chip" style="background: rgba(14, 165, 233, 0.25); border-color: rgba(56, 189, 248, 0.4); color: #bae6fd;">
                📈 Feed CTR: <strong id="top-total-ctr">{{ $stats['avg_ctr'] }}%</strong>
            </span>
        </div>
    </header>

    <!-- 3-Tier Hierarchy Legend -->
    <div class="tier-legend">
        <div class="tier-legend-title">
            <span>🏆 র‍্যাঙ্কিং প্রায়োরিটি (Three-Tier Feed Order):</span>
        </div>
        <div class="tier-legend-items">
            <div class="tier-item">
                <span class="tier-indicator boosted"></span>
                <span><strong>Tier 1: Boosted Ads (Sponsored)</strong> — Priority Weight (3 > 2 > 1) ক্রমানুসারে সবার উপরে</span>
            </div>
            <div class="tier-item">
                <span class="tier-indicator new"></span>
                <span><strong>Tier 2: New Releases</strong> — নতুন আপলোড হওয়া প্রোডাক্ট প্রথম ২৪ ঘণ্টা বুস্ট ছাড়াই স্পেশাল অগ্রাধিকার পায়</span>
            </div>
            <div class="tier-item">
                <span class="tier-indicator organic"></span>
                <span><strong>Tier 3: Organic Standard</strong> — সাধারণ আন-বুস্টেড প্রোডাক্ট স্বাভাবিক ক্রমে প্রদর্শিত হয়</span>
            </div>
        </div>
    </div>

    <!-- Filters & View Switcher -->
    <div class="filters-toolbar">
        <div class="filter-groups">
            <span style="font-size: 13px; font-weight: 700; color: var(--text-muted); margin-right: 4px;">Network:</span>
            <a href="?network_type=psychology&industry_type={{ $currentIndustry }}&view={{ $viewMode }}" class="filter-btn {{ $currentNetwork === 'psychology' ? 'active' : '' }}">
                🧠 Psychology
            </a>
            <a href="?network_type=neuroscience&industry_type={{ $currentIndustry }}&view={{ $viewMode }}" class="filter-btn {{ $currentNetwork === 'neuroscience' ? 'active' : '' }}">
                🔬 Neuroscience
            </a>

            <span style="font-size: 13px; font-weight: 700; color: var(--text-muted); margin: 0 4px 0 12px;">View:</span>
            <a href="?network_type={{ $currentNetwork }}&industry_type={{ $currentIndustry }}&view=grouped" class="filter-btn {{ $viewMode === 'grouped' ? 'active' : '' }}">
                📑 Section-wise Grouped
            </a>
            <a href="?network_type={{ $currentNetwork }}&industry_type={{ $currentIndustry }}&view=flat" class="filter-btn {{ $viewMode === 'flat' ? 'active' : '' }}">
                📊 All Products Grid
            </a>
        </div>

        <form method="GET" action="" class="search-box">
            <input type="hidden" name="network_type" value="{{ $currentNetwork }}">
            <input type="hidden" name="industry_type" value="{{ $currentIndustry }}">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <svg class="search-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="search" placeholder="Search products..." value="{{ $search ?? '' }}">
        </form>
    </div>

    @if($viewMode === 'grouped')
        <!-- ==============================================
             SECTION-WISE GROUPED VIEW (DEFAULT)
             ============================================== -->
        @foreach($groupedBySection as $secGroup)
            @php
                $section = $secGroup['section'];
                $products = $secGroup['products'];
            @endphp

            <section class="section-block" id="section-{{ $section->id }}">
                <div class="section-header-row">
                    <div class="section-title">
                        <span>{{ $section->name }}</span>
                        <span class="section-badge">{{ $products->count() }} items</span>
                    </div>

                    @if($section->IndustryCategory && $section->IndustryCategory->isNotEmpty())
                        <div class="category-pills">
                            @foreach($section->IndustryCategory as $cat)
                                <a href="?network_type={{ $currentNetwork }}&industry_type={{ $currentIndustry }}&section_id={{ $section->id }}&category_id={{ $cat->id }}&view=flat" class="cat-pill {{ $currentCategory == $cat->id ? 'active' : '' }}">
                                    {{ $cat->category_name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($products->isNotEmpty())
                    <div class="products-grid">
                        @foreach($products as $product)
                            @include('partials.product-card-preview', ['product' => $product])
                        @endforeach
                    </div>
                @else
                    <div class="empty-section">
                        এই সেকশনে বর্তমানে কোনো সক্রিয় প্রোডাক্ট পাওয়া যায়নি।
                    </div>
                @endif
            </section>
        @endforeach

    @else
        <!-- ==============================================
             FLAT ALL PRODUCTS GRID VIEW
             ============================================== -->
        <div class="products-grid">
            @forelse($flatProducts as $product)
                @include('partials.product-card-preview', ['product' => $product])
            @empty
                <div class="empty-section" style="grid-column: 1 / -1;">
                    কোনো প্রোডাক্ট পাওয়া যায়নি।
                </div>
            @endforelse
        </div>
    @endif

    <!-- Real-time Ad Tracking Terminal / Activity Stream -->
    <div class="live-activity-panel">
        <div class="live-activity-header">
            <span style="display: flex; align-items: center; gap: 8px; font-weight: 800; color: #fff;">
                <span class="live-dot" style="width: 8px; height: 8px;"></span>
                📡 Live API Ad Tracking Activity Stream (MySQL Real-time Logging)
            </span>
            <span style="font-size: 12px; color: var(--text-muted);">
                Endpoints: <code>POST /api/advertisements/impressions</code> &bull; <code>POST /api/advertisements/{id}/click</code>
            </span>
        </div>
        <div class="log-terminal" id="logTerminal">
            <div class="log-entry" style="color: #64748b;">
                <span>[Ready]</span> Ad tracking client initialized. Scroll down to trigger IAB viewable impressions (50% visibility for 600ms) or click product cards to record clicks.
            </div>
        </div>
    </div>

</div>

<!-- Real-time Toast Notifications Container -->
<div id="toastContainer" class="toast-container"></div>

<!-- Floating Sticky Tracking Monitor Bar -->
<div class="sticky-tracking-bar" id="stickyTrackingBar">
    <span style="display: flex; align-items: center; gap: 8px; font-weight: 800; color: #fff;">
        <span class="live-dot" style="width: 8px; height: 8px;"></span>
        API Tracking Monitor:
    </span>
    <span style="color: #c7d2fe; font-weight: 700;">👁️ <span id="sticky-imp-count">{{ number_format($stats['total_impressions']) }}</span> imps</span>
    <span style="color: #fbcfe8; font-weight: 700;">🖱️ <span id="sticky-click-count">{{ number_format($stats['total_clicks']) }}</span> clicks</span>
    <span style="color: #38bdf8; font-weight: 700;">📈 <span id="sticky-ctr-count">{{ $stats['avg_ctr'] }}%</span> CTR</span>
    <button type="button" class="toggle-log-btn" onclick="scrollToTerminal()">
        📡 API Logs (<span id="eventCountBadge">0</span>)
    </button>
</div>

<!-- Product Quick View Modal -->
<div class="modal-overlay" id="productModal" onclick="closeModal(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3 id="modalTitle">Product Details</h3>
            <button type="button" class="modal-close" onclick="closeModal(event)">✕</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Dynamic Content -->
        </div>
    </div>
</div>

<script>
    // Global Ad Tracking State
    let totalImpressions = {{ $stats['total_impressions'] }};
    let totalClicks = {{ $stats['total_clicks'] }};
    let eventCount = 0;
    const trackedImpressions = new Set();

    function showToast(type, title, message) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast-card ${type}`;
        const icon = type === 'impression' ? '⚡' : (type === 'click' ? '🖱️' : 'ℹ️');
        toast.innerHTML = `
            <div style="font-size: 20px;">${icon}</div>
            <div style="flex: 1;">
                <div style="font-weight: 800; color: #fff; margin-bottom: 2px;">${title}</div>
                <div style="color: #cbd5e1; font-size: 12px; line-height: 1.4;">${message}</div>
            </div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    function appendLog(type, htmlContent) {
        const terminal = document.getElementById('logTerminal');
        if (!terminal) return;
        eventCount++;
        const badge = document.getElementById('eventCountBadge');
        if (badge) badge.textContent = eventCount;

        const time = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.className = `log-entry ${type}`;
        entry.innerHTML = `<span style="color: #64748b; font-weight: 700;">[${time}]</span> <span>${htmlContent}</span>`;
        terminal.insertBefore(entry, terminal.firstChild);
    }

    function updateTopCtr() {
        const ctr = totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(1) : 0.0;
        const el = document.getElementById('top-total-ctr');
        if (el) el.textContent = ctr + '%';
        const stickyCtr = document.getElementById('sticky-ctr-count');
        if (stickyCtr) stickyCtr.textContent = ctr + '%';

        const stickyImp = document.getElementById('sticky-imp-count');
        if (stickyImp) stickyImp.textContent = totalImpressions.toLocaleString();

        const stickyClk = document.getElementById('sticky-click-count');
        if (stickyClk) stickyClk.textContent = totalClicks.toLocaleString();
    }

    function scrollToTerminal() {
        const term = document.querySelector('.live-activity-panel');
        if (term) {
            term.scrollIntoView({ behavior: 'smooth', block: 'center' });
            term.style.outline = '2px solid #818cf8';
            setTimeout(() => { term.style.outline = 'none'; }, 2000);
        }
    }

    // 1. IAB Viewable Impression Tracking via IntersectionObserver
    function initViewableImpressionTracker() {
        const boostedCards = document.querySelectorAll('.product-card.boosted[data-campaign-id]');
        if (!boostedCards.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                    const card = entry.target;
                    const campaignId = card.getAttribute('data-campaign-id');
                    const productName = card.getAttribute('data-product-name');

                    if (campaignId && !trackedImpressions.has(campaignId)) {
                        trackedImpressions.add(campaignId);

                        // Call actual API endpoint: POST /api/advertisements/impressions
                        fetch('/api/advertisements/impressions', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                campaign_ids: [parseInt(campaignId)],
                                placement: 'industry_product_feed'
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                // Increment card impressions count
                                const impSpan = document.getElementById(`card-imp-${campaignId}`);
                                if (impSpan) {
                                    const current = parseInt(impSpan.textContent.replace(/,/g, '')) || 0;
                                    const nextCount = current + 1;
                                    impSpan.textContent = nextCount.toLocaleString();

                                    // Recalculate card CTR
                                    const clickSpan = document.getElementById(`card-click-${campaignId}`);
                                    const cardCtr = document.getElementById(`card-ctr-${campaignId}`);
                                    if (clickSpan && cardCtr) {
                                        const clks = parseInt(clickSpan.textContent.replace(/,/g, '')) || 0;
                                        cardCtr.textContent = nextCount > 0 ? ((clks / nextCount) * 100).toFixed(1) + '%' : '0%';
                                    }
                                }

                                // Increment top ribbon impressions count
                                totalImpressions++;
                                const topImp = document.getElementById('top-total-impressions');
                                if (topImp) topImp.textContent = totalImpressions.toLocaleString();
                                updateTopCtr();

                                showToast('impression', '⚡ IAB Impression Recorded!', `Campaign #${campaignId} ("${productName}") → DB count incremented.`);
                                appendLog('imp', `⚡ <strong>POST /api/advertisements/impressions</strong> &rarr; Campaign #${campaignId} (<em>${productName}</em>) &mdash; <span style="color:#a7f3d0;">+1 impression logged in MySQL (ad_impressions & industry_products)</span>`);
                            }
                        })
                        .catch(err => console.error('Impression tracking error:', err));
                    }
                }
            });
        }, {
            threshold: 0.5
        });

        boostedCards.forEach(card => observer.observe(card));
    }

    // 2. Verified Ad Click & Interaction Tracking
    function handleProductClick(product) {
        const isBoosted = !!product.is_boosted;
        const campaignId = product.ad_campaign_id;

        if (isBoosted && campaignId) {
            // Call actual API endpoint: POST /api/advertisements/{id}/click
            fetch(`/api/advertisements/${campaignId}/click`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    placement: 'industry_product_feed'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const totalAdClicks = data.data && data.data.total_clicks ? data.data.total_clicks : null;
                    
                    // Increment card click counter
                    const clickSpan = document.getElementById(`card-click-${campaignId}`);
                    if (clickSpan) {
                        const current = parseInt(clickSpan.textContent.replace(/,/g, '')) || 0;
                        const nextVal = totalAdClicks !== null ? totalAdClicks : current + 1;
                        clickSpan.textContent = nextVal.toLocaleString();

                        // Recalculate card CTR
                        const impSpan = document.getElementById(`card-imp-${campaignId}`);
                        const cardCtr = document.getElementById(`card-ctr-${campaignId}`);
                        if (impSpan && cardCtr) {
                            const imps = parseInt(impSpan.textContent.replace(/,/g, '')) || 0;
                            cardCtr.textContent = imps > 0 ? ((nextVal / imps) * 100).toFixed(1) + '%' : '0%';
                        }
                    }

                    // Increment top ribbon clicks count
                    totalClicks++;
                    const topClk = document.getElementById('top-total-clicks');
                    if (topClk) topClk.textContent = totalClicks.toLocaleString();
                    updateTopCtr();

                    showToast('click', '🖱️ Verified Ad Click Recorded!', `Campaign #${campaignId} ("${product.product_name}") &rarr; Total Clicks: ${totalAdClicks || totalClicks}`);
                    appendLog('clk', `🖱️ <strong>POST /api/advertisements/${campaignId}/click</strong> &rarr; Campaign #${campaignId} (<em>${product.product_name}</em>) &mdash; <span style="color:#fbcfe8;">total_clicks updated to ${totalAdClicks || totalClicks} in DB!</span>`);
                }
            })
            .catch(err => console.error('Click tracking error:', err));
        }

        // Open Product Details Modal
        openProductModal(product);
    }

    function openProductModal(product) {
        document.getElementById('modalTitle').textContent = product.product_name;
        document.getElementById('modalBody').innerHTML = `
            <div style="margin-bottom: 16px;">
                ${product.image_url ? `<img src="${product.image_url}" style="width:100%; max-height:260px; object-fit:cover; border-radius:12px; margin-bottom:14px;">` : ''}
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
                    <span style="font-size:12px; font-weight:700; color:#818cf8; background:rgba(99,102,241,0.2); padding:3px 10px; border-radius:6px;">
                        ${product.is_boosted ? '⚡ BOOSTED (' + (product.boost_remaining_text || 'Active') + ')' : (product.is_new_release ? '🔥 NEW RELEASE' : 'ORGANIC')}
                    </span>
                    ${product.ad_campaign_id ? `<span style="font-size:11.5px; font-weight:700; color:#c7d2fe; background:rgba(129,140,248,0.2); padding:3px 8px; border-radius:6px;">Ad Campaign #${product.ad_campaign_id}</span>` : ''}
                    <span style="color:#94a3b8; font-size:13px;">ID: ${product.product_id}</span>
                </div>
                <p style="color:#cbd5e1; font-size:14px; line-height:1.6; margin-bottom:16px;">${product.description || 'No description provided.'}</p>
                
                ${product.is_boosted ? `
                    <div style="background: rgba(99,102,241,0.1); border: 1px solid rgba(129,140,248,0.3); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Total Impressions</div>
                            <div style="font-size: 18px; font-weight: 800; color: #fff;">${product.impressions_count || 0}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Total Clicks</div>
                            <div style="font-size: 18px; font-weight: 800; color: #ec4899;">${product.clicks_count || 0}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;">CTR</div>
                            <div style="font-size: 18px; font-weight: 800; color: #38bdf8;">${product.impressions_count > 0 ? ((product.clicks_count / product.impressions_count) * 100).toFixed(1) : 0}%</div>
                        </div>
                    </div>
                ` : ''}

                <div style="background:rgba(0,0,0,0.3); padding:14px; border-radius:10px; font-size:13px; color:#94a3b8; line-height:1.7;">
                    <div><strong>Company:</strong> ${product.industry ? product.industry.name : 'N/A'}</div>
                    <div><strong>Section:</strong> ${product.section_name || 'N/A'}</div>
                    <div><strong>Category:</strong> ${product.category_name || 'N/A'}</div>
                    <div><strong>Contact Person:</strong> ${product.poc_name || 'N/A'} (${product.poc_email || 'N/A'})</div>
                </div>
                <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                    <a href="${product.product_url || '#'}" target="_blank" style="background:linear-gradient(135deg, #6366f1, #8b5cf6); color:#fff; text-decoration:none; padding:10px 20px; border-radius:9999px; font-size:13.5px; font-weight:700;">
                        Visit Official Website →
                    </a>
                </div>
            </div>
        `;
        document.getElementById('productModal').style.display = 'flex';
    }

    function closeModal(e) {
        document.getElementById('productModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        initViewableImpressionTracker();
    });
</script>

</body>
</html>
