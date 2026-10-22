<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindUnite - {{ $industries[$currentIndustry]['name'] ?? 'Industry Products' }} | {{ $networks[$currentNetwork] ?? 'Network' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --text-secondary: #475569;
            --primary-teal: #0284c7;
            --primary-teal-light: #e0f2fe;
            --primary-teal-border: #7dd3fc;
            --primary-dark: #0369a1;
            --boost-indigo: #6366f1;
            --boost-indigo-light: #e0e7ff;
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
            color: var(--text-main);
            line-height: 1.5;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* ========================================================
           1. TOP NAVBAR (AUTHENTIC MINDUNITE BRANDING)
           ======================================================== */
        /* ========================================================
           1. TOP NAVBAR (MATCHING MINDUNITE LIVE UI / NEWSFEED)
           ======================================================== */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .header-inner {
            max-width: 1380px;
            margin: 0 auto;
            padding: 0 24px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        /* Logo */
        .logo-area {
            display: flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: #e0f2fe;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon svg {
            width: 22px;
            height: 22px;
            fill: #0ea5e9;
        }

        .logo-text {
            font-size: 21px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
        }

        .logo-text span {
            color: #0284c7;
        }

        /* Center Nav Items */
        .center-nav {
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 1;
            justify-content: center;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            color: var(--text-muted);
            font-size: 11.5px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.15s ease;
            position: relative;
            white-space: nowrap;
            gap: 3px;
            text-decoration: none;
        }

        .nav-item:hover {
            color: #0284c7;
            background: #f8fafc;
        }

        .nav-item.active {
            color: #0ea5e9;
            font-weight: 700;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 12px;
            right: 12px;
            height: 2.5px;
            background: #0ea5e9;
            border-radius: 4px 4px 0 0;
        }

        .nav-icon {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        .nav-item .label-with-chevron {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .nav-chevron {
            font-size: 10px;
            line-height: 1;
            opacity: 0.7;
        }

        /* Right User Actions */
        .right-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .action-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .action-icon-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .action-icon-btn svg {
            width: 20px;
            height: 20px;
            fill: currentColor;
        }

        .user-profile-menu {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 3px 6px;
            border-radius: 9999px;
            transition: background 0.15s ease;
        }

        .user-profile-menu:hover {
            background: #f1f5f9;
        }

        .user-avatar-ring {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #f59e0b;
            overflow: hidden;
            background: #f8fafc;
        }

        .user-avatar-ring img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ========================================================
           2. MIND UNITE PARTNERS LOGOS BAR
           ======================================================== */
        .partners-bar {
            max-width: 1380px;
            margin: 20px auto 0;
            padding: 0 24px;
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .partners-label {
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
            white-space: nowrap;
        }

        .partners-list {
            display: flex;
            align-items: center;
            gap: 16px;
            overflow-x: auto;
            padding: 4px 0;
        }

        .partner-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-secondary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            flex-shrink: 0;
        }

        /* ========================================================
           3. MAIN 2-COLUMN LAYOUT (SIDEBAR + FEED CONTENT)
           ======================================================== */
        .page-content-wrapper {
            max-width: 1380px;
            margin: 20px auto 80px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 240px minmax(0, 1fr);
            gap: 36px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .page-content-wrapper {
                grid-template-columns: 1fr;
            }
        }

        /* Left Sidebar (Industries) */
        .industries-sidebar {
            position: sticky;
            top: 90px;
        }

        .sidebar-title {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 12px;
            letter-spacing: 0.02em;
        }

        .industry-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .industry-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-secondary);
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .industry-nav-item:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .industry-nav-item.active {
            background: #ecfeff;
            border-color: #a5f3fc;
            color: #0891b2;
            font-weight: 700;
        }

        .industry-nav-item svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            stroke-width: 1.8;
            fill: none;
            flex-shrink: 0;
        }

        /* Right Main Content */
        .main-feed-area {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 28px 32px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        }

        .industry-header {
            margin-bottom: 24px;
        }

        .industry-header-info {
            margin-bottom: 16px;
        }

        .industry-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .industry-header-controls-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        /* Feed Search Bar */
        .feed-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 280px;
            max-width: 380px;
            flex: 1 1 280px;
        }

        .feed-search-icon {
            position: absolute;
            left: 14px;
            width: 17px;
            height: 17px;
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .feed-search-wrap:focus-within .feed-search-icon {
            color: #0284c7;
        }

        .feed-search-input {
            width: 100%;
            height: 42px;
            padding: 0 36px 0 40px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 500;
            color: #0f172a;
            outline: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .feed-search-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .feed-search-input:hover {
            border-color: #cbd5e1;
        }

        .feed-search-input:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15), 0 2px 4px rgba(0, 0, 0, 0.04);
        }

        .feed-search-clear {
            position: absolute;
            right: 8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #f1f5f9;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            transition: all 0.15s ease;
        }

        .feed-search-clear:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .feed-search-clear svg {
            width: 13px;
            height: 13px;
            stroke-width: 2.2;
        }

        .industry-main-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .industry-main-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* View Mode Segmented Controls */
        .view-mode-toggle-group {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            gap: 4px;
        }

        .view-mode-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .view-mode-btn svg {
            width: 16px;
            height: 16px;
            stroke-width: 2.2;
        }

        .view-mode-btn:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.5);
        }

        .view-mode-btn.active {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.04);
            font-weight: 700;
        }

        .view-mode-btn .view-badge-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 9999px;
            line-height: 1.2;
        }

        .view-mode-btn.active .view-badge-count {
            background: #e0f2fe;
            color: #0284c7;
        }

        /* All Products Summary Sub-bar */
        .all-products-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 22px;
            font-size: 13px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 10px;
        }

        .all-products-header-bar .pill-stats {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 12px;
        }

        /* Section Block */
        .section-block {
            margin-bottom: 36px;
            padding-bottom: 36px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-block:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 14px;
        }

        /* Categories Sub-nav Tabs */
        .categories-tabs-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 20px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .categories-tabs-bar::-webkit-scrollbar {
            display: none;
        }

        .cat-tab-btn {
            background: transparent;
            border: none;
            padding: 8px 4px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            white-space: nowrap;
            position: relative;
            transition: color 0.15s ease;
        }

        .cat-tab-btn:hover {
            color: var(--text-main);
        }

        .cat-tab-btn.active {
            color: #0284c7;
            font-weight: 700;
        }

        .cat-tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #0284c7;
            border-radius: 2px;
        }

        /* Products Grid (3 Columns) */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        @media (max-width: 1200px) {
            .products-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 680px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Product Card Matching Live Screenshots */
        .product-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: var(--border-hover);
        }

        /* Boosted Ad Card Enhancement */
        .product-card.boosted {
            border: 1.5px solid #818cf8;
            box-shadow: 0 4px 16px -2px rgba(99, 102, 241, 0.2);
        }

        .product-card.boosted::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #a855f7, #ec4899);
            z-index: 2;
        }

        .boosted-ribbon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .product-card.new-release {
            border: 1.5px solid #f59e0b;
            box-shadow: 0 4px 16px -2px rgba(245, 158, 11, 0.2);
        }

        .product-card.new-release::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #ea580c, #ef4444);
            z-index: 2;
        }

        .new-release-ribbon {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #ffffff;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.4);
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Card Image Container (Authentic Cyan Brain Graphic or Uploaded Image) */
        .card-image-wrap {
            width: 100%;
            height: 160px;
            background: #091220;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .brain-cyan-illustration {
            width: 80%;
            height: 80%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brain-cyan-illustration svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 0 10px rgba(14, 165, 233, 0.7));
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }

        .card-title {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.35;
        }

        .card-tag-badge {
            background: #f1f5f9;
            color: #475569;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            white-space: nowrap;
            border: 1px solid #e2e8f0;
        }

        .card-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .card-desc {
            font-size: 12.5px;
            color: var(--text-secondary);
            line-height: 1.5;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }

        .learn-more-link {
            font-size: 12.5px;
            font-weight: 700;
            color: #0284c7;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: gap 0.15s ease;
        }

        .learn-more-link:hover {
            gap: 7px;
            color: #0369a1;
        }

        /* Card Live Metrics Bar (For Boosted Product Ads) */
        .card-metrics-pill-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-family: monospace;
            color: #64748b;
        }

        .card-footer {
            padding: 10px 16px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            background: #ffffff;
        }

        .card-action-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.15s ease;
            padding: 4px 8px;
            border-radius: 6px;
        }

        .card-action-btn:hover {
            color: #0284c7;
            background: #f0f9ff;
        }

        .card-action-btn.liked {
            color: #0284c7;
            font-weight: 700;
            background: #e0f2fe;
        }

        /* Empty State */
        .empty-section-message {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
            font-size: 14px;
            font-style: italic;
            background: #fafafa;
            border-radius: 10px;
            border: 1px dashed #e2e8f0;
        }

        /* See More Products Button */
        .see-more-wrap {
            display: flex;
            justify-content: center;
            margin-top: 24px;
        }

        /* See More Button & Link */
        .see-more-wrap {
            display: flex;
            justify-content: center;
            margin-top: 24px;
        }

        .see-more-btn {
            background: #ffffff;
            border: 1.5px solid #0284c7;
            color: #0284c7;
            font-size: 13.5px;
            font-weight: 700;
            padding: 9px 26px;
            border-radius: 9999px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.08);
            transition: all 0.2s ease;
            user-select: none;
            text-decoration: none;
        }

        .see-more-btn:hover {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transform: translateY(-1px);
        }

        .see-more-btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2.2;
            fill: none;
            transition: transform 0.2s ease;
        }

        .see-more-btn:hover svg {
            transform: translateX(3px);
        }

        /* Dedicated Section View Styles */
        .dedicated-section-header {
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .section-breadcrumb-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .back-to-sections-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0284c7;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            padding: 7px 16px;
            background: rgba(2, 132, 199, 0.08);
            border-radius: 9999px;
            transition: all 0.15s ease;
        }

        .back-to-sections-btn:hover {
            background: rgba(2, 132, 199, 0.18);
            transform: translateX(-3px);
        }

        .section-tag-badge {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .dedicated-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        .dedicated-section-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .dedicated-section-desc {
            font-size: 13.5px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Cursor Pagination Container */
        .cursor-pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 22px;
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            margin-top: 32px;
            gap: 16px;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .pagination-meta {
            font-size: 13.5px;
            color: var(--text-muted);
        }

        .pagination-meta strong {
            color: var(--text-dark);
        }

        .pagination-nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cursor-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1.5px solid #0284c7;
            color: #0284c7;
            background: #ffffff;
            user-select: none;
        }

        .cursor-nav-btn:hover:not(.disabled) {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            transform: translateY(-1px);
        }

        .cursor-nav-btn.disabled {
            border-color: #e2e8f0;
            color: #94a3b8;
            background: #f8fafc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .cursor-nav-indicator {
            font-size: 12px;
            font-weight: 600;
            color: #94a3b8;
            padding: 0 4px;
        }

        /* ========================================================
           CURSOR INFINITE SCROLL LOADER & END BADGES
           ======================================================== */
        .infinite-scroll-container {
            width: 100%;
            margin-top: 16px;
            padding: 8px 0 24px;
            position: relative;
        }

        .infinite-scroll-sentinel {
            width: 100%;
            height: 20px;
            pointer-events: none;
            opacity: 0;
        }

        .infinite-scroll-loader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 24px 0;
            color: #0284c7;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .scroll-spinner-ring {
            width: 26px;
            height: 26px;
            border: 3px solid rgba(2, 132, 199, 0.18);
            border-top-color: #0284c7;
            border-radius: 50%;
            animation: scrollSpinRing 0.75s linear infinite;
        }

        @keyframes scrollSpinRing {
            to { transform: rotate(360deg); }
        }

        .infinite-scroll-end {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 18px;
            padding: 32px 0 16px;
        }

        .scroll-end-line {
            flex: 1;
            max-width: 160px;
            height: 1px;
            background: linear-gradient(90deg, transparent, #cbd5e1, transparent);
        }

        .scroll-end-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 7px 18px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            color: #64748b;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .scroll-end-badge svg {
            color: #10b981;
        }

        .infinite-scroll-error {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px 0;
            color: #ef4444;
            font-size: 13.5px;
            font-weight: 600;
        }

        .scroll-retry-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            background: #ffffff;
            border: 1.5px solid #ef4444;
            color: #ef4444;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .scroll-retry-btn:hover {
            background: #ef4444;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(239, 68, 68, 0.25);
        }

        @keyframes cardFadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-card {
            animation: cardFadeInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* ========================================================
           4. STICKY REAL-TIME MONITOR & TOAST NOTIFICATIONS
           ======================================================== */
        .sticky-monitor {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: rgba(15, 23, 42, 0.96);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(99, 102, 241, 0.4);
            border-radius: 9999px;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 999;
            font-size: 13px;
            color: #f8fafc;
        }

        .pulse-indicator {
            width: 9px;
            height: 9px;
            background-color: var(--success);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: livePulse 2s infinite;
        }

        @keyframes livePulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10000;
            max-width: 420px;
            pointer-events: none;
        }

        .toast-card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 12px;
            padding: 14px 18px;
            box-shadow: 0 16px 36px -8px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            pointer-events: auto;
            animation: slideInRight 0.3s ease;
            font-size: 13px;
            line-height: 1.45;
            color: #fff;
        }

        .toast-card.impression {
            border: 1px solid rgba(129, 140, 248, 0.6);
        }

        .toast-card.click {
            border: 1px solid rgba(244, 114, 182, 0.6);
        }

        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes fadeInDown {
            from { transform: translateY(-8px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ========================================================
           5. PRODUCT DETAILS MODAL - EXECUTIVE REDESIGN
           ======================================================== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
            opacity: 0;
            transition: opacity 0.22s ease;
        }

        .modal-overlay.active {
            opacity: 1;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 20px;
            max-width: 740px;
            width: 100%;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(226, 232, 240, 0.85);
            transform: scale(0.96) translateY(10px);
            transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-overlay.active .modal-card {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 16px 22px;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
        }

        .modal-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .modal-header-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
        }

        .modal-header-icon svg {
            width: 18px;
            height: 18px;
        }

        .modal-title-wrap {
            display: flex;
            flex-direction: column;
        }

        .modal-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.01em;
            line-height: 1.25;
        }

        .modal-subtitle {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 500;
        }

        .modal-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* Product ID Pill - Replacing Status: Active */
        .modal-product-id-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 9999px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .modal-product-id-pill:hover {
            background: #f0fdf4;
            border-color: #0284c7;
            color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(2, 132, 199, 0.15);
        }

        .modal-product-id-pill .id-tag {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .modal-product-id-pill .id-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .modal-product-id-pill .copy-icon {
            width: 13px;
            height: 13px;
            color: #94a3b8;
            transition: color 0.15s ease;
        }

        .modal-product-id-pill:hover .copy-icon {
            color: #0284c7;
        }

        .modal-close-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
            padding: 0;
        }

        .modal-close-btn:hover {
            background: #e2e8f0;
            color: #0f172a;
            transform: scale(1.05);
        }

        .modal-close-btn svg {
            width: 16px;
            height: 16px;
            stroke-width: 2.2;
        }

        /* Modal Body */
        .modal-body {
            padding: 22px 24px;
            overflow-y: auto;
            max-height: calc(90vh - 145px);
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f8fafc;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        /* Modal Hero Box */
        .modal-hero-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 18px;
            display: flex;
            gap: 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .modal-image-col {
            position: relative;
            width: 160px;
            height: 160px;
            border-radius: 12px;
            overflow: hidden;
            background: #0b1120;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-image-col img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal-image-col .brain-cyan-illustration {
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, #0f2744 0%, #060d1a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-image-col .brain-cyan-illustration svg {
            width: 100px;
            height: 80px;
            filter: drop-shadow(0 0 10px rgba(14, 165, 233, 0.5));
        }

        .modal-image-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
            z-index: 2;
        }

        .modal-image-badge.sponsored {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #ffffff;
        }

        .modal-image-badge.new-release {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #ffffff;
        }

        .modal-info-col {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .modal-product-title {
            font-size: 19px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.3;
            margin: 0 0 6px 0;
            letter-spacing: -0.015em;
        }

        .modal-partner-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 12px;
        }

        .partner-verified-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 10.5px;
            font-weight: 700;
        }

        .modal-meta-chips-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 10px;
            margin-bottom: 12px;
        }

        .modal-meta-chip {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .modal-meta-chip .chip-label {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
        }

        .modal-meta-chip .chip-value {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .modal-stats-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
            font-size: 12px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 8px;
        }

        .modal-stats-group {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 600;
            color: #475569;
        }

        .modal-stat-pill strong {
            color: #0f172a;
            font-weight: 800;
        }

        /* Sponsored Campaign Analytics Box */
        .modal-boost-analytics {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 18px;
            color: #ffffff;
            box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .modal-boost-analytics-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-boost-analytics-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #38bdf8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .modal-boost-analytics-badge {
            font-size: 11px;
            background: rgba(14, 165, 233, 0.15);
            color: #7dd3fc;
            border: 1px solid rgba(14, 165, 233, 0.3);
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
        }

        .modal-boost-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .modal-boost-stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 10px 12px;
            text-align: center;
        }

        .modal-boost-stat-label {
            font-size: 10.5px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }

        .modal-boost-stat-num {
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
        }

        /* Sections */
        .modal-section-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }

        .modal-section-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
            letter-spacing: -0.01em;
        }

        .modal-description-content {
            color: #475569;
            font-size: 13px;
            line-height: 1.65;
        }

        .modal-tags-wrap {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modal-tag-chip {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .modal-tag-chip:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Person of Contact Executive Card */
        .modal-poc-executive-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            gap: 14px;
            flex-wrap: wrap;
        }

        .modal-poc-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-poc-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
            flex-shrink: 0;
        }

        .modal-poc-details .poc-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .modal-poc-details .poc-role {
            font-size: 11.5px;
            color: #64748b;
        }

        .modal-poc-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-poc-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 11.5px;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .modal-poc-btn:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #0f172a;
        }

        .modal-poc-btn svg {
            width: 13px;
            height: 13px;
        }

        /* Modal Sticky Footer */
        .modal-footer {
            padding: 14px 22px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-shrink: 0;
            border-radius: 0 0 20px 20px;
        }

        .modal-footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-footer-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-btn-secondary {
            padding: 9px 18px;
            border-radius: 10px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .modal-btn-secondary:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        .modal-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7, #0369a1);
            border: none;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(2, 132, 199, 0.35);
            transition: all 0.2s ease;
        }

        .modal-btn-primary:hover {
            background: linear-gradient(135deg, #0369a1, #075985);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.45);
        }

        .modal-btn-primary svg {
            width: 15px;
            height: 15px;
            stroke-width: 2.2;
        }

        .modal-btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 13px;
            border-radius: 8px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            color: #475569;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .modal-btn-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .modal-btn-action.liked {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .modal-btn-action svg {
            width: 14px;
            height: 14px;
        }
    </style>
</head>
<body>

    <!-- ========================================================
         1. TOP NAVBAR
         ======================================================== -->
    <!-- ========================================================
         1. TOP NAVBAR (MATCHING MINDUNITE LIVE UI / NEWSFEED)
         ======================================================== -->
    <header class="app-header">
        <div class="header-inner">
            <!-- Brand Logo -->
            <a href="/newsfeed-preview" class="logo-area">
                <div class="logo-icon">
                    <svg viewBox="0 0 24 24"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/></svg>
                </div>
                <div class="logo-text">Mind<span>Unite.</span></div>
            </a>

            <!-- Center Navigation Icons & Links -->
            <nav class="center-nav">
                <a href="/newsfeed-preview" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    <span>Home</span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M11 17h2v-6h-2v6zm1-15C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zM11 9h2V7h-2v2z"/></svg>
                    <span>About Us</span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                    <span>Academia</span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    <span>My Network</span>
                </a>

                <a href="?network_type=psychology&industry_type={{ $currentIndustry }}" class="nav-item {{ $currentNetwork === 'psychology' ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 2a9 9 0 0 0-9 9c0 3.87 2.33 7.2 5.75 8.48.38.14.75.25 1.15.33v2.19h4.2v-2.19c.4-.08.77-.19 1.15-.33A9.006 9.006 0 0 0 21 11a9 9 0 0 0-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z"/></svg>
                    <span class="label-with-chevron">
                        Psychology Network <span class="nav-chevron">▾</span>
                    </span>
                </a>

                <a href="?network_type=neuroscience&industry_type={{ $currentIndustry }}" class="nav-item {{ $currentNetwork === 'neuroscience' ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span class="label-with-chevron">
                        Neuroscience Network <span class="nav-chevron">▾</span>
                    </span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.1-.89-2-2-2h-4c-1.1 0-2 .89-2 2v2H4c-1.1 0-1.99.89-1.99 2L2 19c0 1.1.89 2 2 2h16c1.1 0 2-.89 2-2V8c0-1.1-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
                    <span>Jobs</span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 0 0-1.01.24l-1.57 1.97c-2.83-1.35-5.43-3.9-6.63-6.82l1.9-1.61c.29-.26.39-.65.29-1.03-.36-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/></svg>
                    <span>Contact Us</span>
                </a>
            </nav>

            <!-- Right Actions: Notifications, Messages, User Profile Avatar -->
            <div class="right-actions">
                <button type="button" class="action-icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/></svg>
                </button>
                <button type="button" class="action-icon-btn" title="Messages">
                    <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/></svg>
                </button>
                <div class="user-profile-menu">
                    <div class="user-avatar-ring">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&h=120&q=80" alt="Niaz Nayeem" onerror="this.src='https://ui-avatars.com/api/?name=Niaz+Nayeem&background=0284c7&color=fff'">
                    </div>
                    <span style="font-size: 11px; color: #64748b;">▾</span>
                </div>
            </div>
        </div>
    </header>

    <!-- ========================================================
         2. PARTNERS BAR
         ======================================================== -->
    <div class="partners-bar">
        <span class="partners-label">Mind Unite Partners</span>
        <div class="partners-list">
            <div class="partner-badge" title="BioPac Systems">BP</div>
            <div class="partner-badge" title="Harvard Lab">HL</div>
            <div class="partner-badge" title="Softvence">SV</div>
            <div class="partner-badge" title="Max Planck">MP</div>
            <div class="partner-badge" title="Oxford">OX</div>
            <div class="partner-badge" title="NeuroTech">NT</div>
            <div class="partner-badge" title="Python Science">PY</div>
        </div>
    </div>

    <!-- ========================================================
         3. MAIN 2-COLUMN LAYOUT (SIDEBAR + CONTENT)
         ======================================================== -->
    <main class="page-content-wrapper">
        <!-- Left Sidebar: Industries -->
        <aside class="industries-sidebar">
            <div class="sidebar-title">Industries</div>
            <ul class="industry-nav-list">
                @foreach($industries as $indKey => $indData)
                    <li>
                        <a href="?network_type={{ $currentNetwork }}&industry_type={{ $indKey }}" 
                           class="industry-nav-item {{ $currentIndustry === $indKey ? 'active' : '' }}">
                            @if($indKey === 'biotechnology')
                                <svg viewBox="0 0 24 24"><path d="M4.5 3h15M6 3v16a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V3M6 14h12"/></svg>
                            @elseif($indKey === 'psychotropics')
                                <svg viewBox="0 0 24 24"><path d="M10.5 20.5l10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                            @endif
                            <span>{{ $indData['name'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <!-- Right Main Feed Area -->
        <section class="main-feed-area">
            @if(isset($activeSection) && $activeSection)
                <!-- ========================================================
                     DEDICATED SECTION VIEW (Triggered via "See More" Button)
                     ======================================================== -->
                <div class="dedicated-section-header">
                    <div class="section-breadcrumb-bar">
                        <a href="{{ route('product-feed.preview', ['network_type' => $currentNetwork, 'industry_type' => $currentIndustry, 'view' => 'section']) }}" 
                           class="back-to-sections-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                            <span>Back to All {{ $industries[$currentIndustry]['name'] ?? 'Industry' }} Sections</span>
                        </a>
                        <span class="section-tag-badge">Section Directory</span>
                    </div>

                    <div class="dedicated-title-row">
                        <div>
                            <h2 class="dedicated-section-title">{{ $activeSection->name }}</h2>
                            <p class="dedicated-section-desc">
                                Showing products in this section &bull; <strong>{{ $totalSectionProducts }}</strong> total products
                            </p>
                        </div>

                        <!-- Search in this section -->
                        <div class="feed-search-wrap">
                            <svg class="feed-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" 
                                   id="feedSearchInput" 
                                   class="feed-search-input" 
                                   placeholder="search in this section..." 
                                   value="{{ $search ?? '' }}"
                                   oninput="handleFeedSearch(this.value)"
                                   onkeydown="handleSearchKeydown(event)"
                                   autocomplete="off"
                                   aria-label="Search products in this section">
                            <button type="button" 
                                    id="clearSearchBtn" 
                                    class="feed-search-clear" 
                                    onclick="clearFeedSearch()" 
                                    title="Clear search"
                                    style="{{ !empty($search) ? 'display: flex;' : 'display: none;' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Category Tabs for this Section -->
                    <div class="categories-tabs-bar" style="margin-top: 18px; margin-bottom: 0;">
                        <a href="{{ route('product-feed.preview', ['network_type' => $currentNetwork, 'industry_type' => $currentIndustry, 'section_id' => $activeSection->id]) }}" 
                           class="cat-tab-btn {{ empty($currentCategory) || $currentCategory === 'all' ? 'active' : '' }}"
                           style="text-decoration: none;">
                            All
                        </a>
                        @foreach(($sectionCategories ?? $activeSection->IndustryCategory ?? collect([])) as $cat)
                            <a href="{{ route('product-feed.preview', ['network_type' => $currentNetwork, 'industry_type' => $currentIndustry, 'section_id' => $activeSection->id, 'category_id' => $cat->id]) }}" 
                               class="cat-tab-btn {{ (string)$currentCategory === (string)$cat->id ? 'active' : '' }}"
                               style="text-decoration: none;">
                                {{ $cat->category_name }}
                            </a>
                        @endforeach
                    </div>
                </div>

                @if($sectionPaginatedProducts && $sectionPaginatedProducts->isNotEmpty())
                    <div class="products-grid" id="grid-dedicated-section" style="margin-top: 20px;">
                        @foreach($sectionPaginatedProducts as $prod)
                            @include('partials.product-feed-card', ['prod' => $prod, 'isExtra' => false, 'sectionId' => $activeSection->id])
                        @endforeach
                    </div>

                    <!-- Infinite Scroll for Dedicated Section (Auto loads data on scroll) -->
                    @include('partials.infinite-scroll', [
                        'paginator' => $sectionPaginatedProducts,
                        'targetGridId' => 'grid-dedicated-section',
                        'feedMode' => 'section'
                    ])
                @else
                    <div class="empty-section-message" style="padding: 60px 20px; margin-top: 20px;">
                        <p style="font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No products found in this section.</p>
                        <p style="font-size: 13px; color: #94a3b8;">Try choosing another category or clearing your search.</p>
                    </div>
                @endif

            @else
                <!-- ========================================================
                     MAIN FEED (All Products View & Section-wise View)
                     ======================================================== -->
                <div class="industry-header">
                    <div class="industry-header-info">
                        <h1 class="industry-main-title">{{ $industries[$currentIndustry]['name'] ?? 'Industry Products' }}</h1>
                        <p class="industry-main-desc">{{ $industries[$currentIndustry]['desc'] ?? 'Explore the latest releases advancing brain health research and treatment.' }}</p>
                    </div>

                    <!-- Controls Row: View Mode Switcher on Left, Search Bar on Right -->
                    <div class="industry-header-controls-row">
                        <!-- View Mode Switcher -->
                        <div class="view-mode-toggle-group">
                            <button type="button" 
                                    class="view-mode-btn {{ $viewMode !== 'section' ? 'active' : '' }}" 
                                    id="viewBtnAll"
                                    onclick="switchViewMode('all')"
                                    title="View all products together in a single feed">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                                </svg>
                                <span>All Products View</span>
                                <span class="view-badge-count" id="viewCountAll">{{ $totalAllCount ?? $flatProducts->count() }}</span>
                            </button>

                            <button type="button" 
                                    class="view-mode-btn {{ $viewMode === 'section' ? 'active' : '' }}" 
                                    id="viewBtnSection"
                                    onclick="switchViewMode('section')"
                                    title="View products grouped section-wise">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <line x1="3" y1="6" x2="21" y2="6"></line>
                                    <line x1="3" y1="12" x2="21" y2="12"></line>
                                    <line x1="3" y1="18" x2="21" y2="18"></line>
                                </svg>
                                <span>Section-wise View</span>
                                <span class="view-badge-count" id="viewCountSection">{{ count($groupedBySection) }}</span>
                            </button>
                        </div>

                        <!-- Search Bar -->
                        <div class="feed-search-wrap">
                            <svg class="feed-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            <input type="text" 
                                   id="feedSearchInput" 
                                   class="feed-search-input" 
                                   placeholder="search by product name or id" 
                                   value="{{ $search ?? '' }}"
                                   oninput="handleFeedSearch(this.value)"
                                   onkeydown="handleSearchKeydown(event)"
                                   autocomplete="off"
                                   aria-label="Search by product name or id">
                            <button type="button" 
                                    id="clearSearchBtn" 
                                    class="feed-search-clear" 
                                    onclick="clearFeedSearch()" 
                                    title="Clear search"
                                    style="{{ !empty($search) ? 'display: flex;' : 'display: none;' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- VIEW 1: All Type Products Unified Grid (Default) -->
                <div id="viewModeAllContainer" style="{{ $viewMode !== 'section' ? 'display: block;' : 'display: none;' }}">
                    <div class="all-products-header-bar">
                        <div id="allProductsSummaryText">
                            @if(!empty($search))
                                <strong>Search Results</strong> &bull; Showing matching items for "{{ $search }}"
                            @else
                                <strong>All Available Products</strong> &bull; Showing {{ $totalAllCount ?? $flatProducts->count() }} items sorted by priority
                            @endif
                        </div>
                        <div class="pill-stats">
                            @if(!empty($stats['boosted_count']))
                                <span style="color:#0284c7; font-weight: 700;">⚡ {{ $stats['boosted_count'] }} Sponsored</span>
                            @endif
                            @if(!empty($stats['new_release_count']))
                                <span style="color:#d97706; font-weight: 700;">🔥 {{ $stats['new_release_count'] }} New Release</span>
                            @endif
                            <span style="color:#64748b; font-weight: 600;">📦 {{ $stats['organic_count'] ?? 0 }} Regular</span>
                        </div>
                    </div>

                    @if($flatProducts->isNotEmpty())
                        <div class="products-grid" id="grid-all-products">
                            @foreach($flatProducts as $prod)
                                @include('partials.product-feed-card', ['prod' => $prod, 'isExtra' => false])
                            @endforeach
                        </div>

                        <!-- Infinite Scroll for All Products View (Auto loads data on scroll) -->
                        @if(isset($allPaginated))
                            @include('partials.infinite-scroll', [
                                'paginator' => $allPaginated,
                                'targetGridId' => 'grid-all-products',
                                'feedMode' => 'all'
                            ])
                        @endif
                    @else
                        <div class="empty-section-message" style="padding: 60px 20px;">
                            <p style="font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No products found in this industry.</p>
                            <p style="font-size: 13px; color: #94a3b8;">Try switching networks or choosing another industry from the sidebar.</p>
                        </div>
                    @endif
                </div>

                <!-- VIEW 2: Section-wise Grouped Blocks -->
                <div id="viewModeSectionContainer" style="{{ $viewMode === 'section' ? 'display: block;' : 'display: none;' }}">
                    @forelse($groupedBySection as $group)
                        @php
                            $section = $group['section'];
                            $secProducts = $group['products'];
                            $categories = $group['categories'];
                            $secTotal = $group['total'] ?? $secProducts->count();
                        @endphp

                        <div class="section-block" id="section-block-{{ $section->id }}">
                            <!-- Section Title -->
                            <h2 class="section-title">{{ $section->name }}</h2>

                            <!-- Category Tabs Sub-navigation -->
                            <div class="categories-tabs-bar" id="cat-tabs-bar-{{ $section->id }}">
                                <button type="button" 
                                        class="cat-tab-btn active" 
                                        data-category-id="all"
                                        onclick="filterSectionCategory({{ $section->id }}, 'all', this)">
                                    All
                                </button>

                                @foreach($categories as $cat)
                                    <button type="button" 
                                            class="cat-tab-btn" 
                                            data-category-id="{{ $cat->id }}"
                                            onclick="filterSectionCategory({{ $section->id }}, {{ $cat->id }}, this)">
                                        {{ $cat->category_name }}
                                    </button>
                                @endforeach
                            </div>

                            <!-- Products Grid (Top 6 per section) -->
                            @if($secProducts->isNotEmpty())
                                <div class="products-grid" id="grid-sec-{{ $section->id }}">
                                    @foreach($secProducts as $prodIndex => $prod)
                                        @include('partials.product-feed-card', [
                                            'prod' => $prod,
                                            'isExtra' => false,
                                            'sectionId' => $section->id
                                        ])
                                    @endforeach
                                </div>

                                <!-- See More Button: Redirects to Dedicated Section Page with Cursor Pagination! -->
                                @if($secTotal > 6)
                                    <div class="see-more-wrap" id="see-more-wrap-{{ $section->id }}">
                                        <a href="{{ route('product-feed.preview', ['network_type' => $currentNetwork, 'industry_type' => $currentIndustry, 'section_id' => $section->id, 'view' => 'section']) }}" 
                                           class="see-more-btn see-more-link" 
                                           id="see-more-btn-{{ $section->id }}"
                                           title="View all {{ $secTotal }} products in {{ $section->name }}">
                                            <span class="btn-text">See all products in this section ({{ $secTotal - 6 }} more)</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="empty-section-message">
                                    No products found in this category
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="empty-section-message" style="padding: 60px 20px;">
                            <p style="font-size: 16px; font-weight: 600; color: #64748b; margin-bottom: 8px;">No sections found for this industry.</p>
                            <p style="font-size: 13px; color: #94a3b8;">Try switching networks or choosing another industry from the sidebar.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </main>

    <!-- ========================================================
         4. STICKY REAL-TIME MONITOR & TOASTS
         ======================================================== -->
    <div class="sticky-monitor">
        <div style="font-weight: 800; color: #a5b4fc; display: flex; align-items: center; gap: 6px;">
            <span class="pulse-indicator"></span> MindUnite Ads Monitor:
        </div>
        @if(!empty($stats['new_release_count']))
            <div>
                <span style="color: #94a3b8;">🔥 New:</span>
                <strong style="color: #fbbf24;">{{ number_format($stats['new_release_count']) }}</strong>
            </div>
        @endif
        <div>
            <span style="color: #94a3b8;">Total Imp:</span>
            <strong id="sticky-imp-count" style="color: #38bdf8;">{{ number_format($stats['total_impressions'] ?? 0) }}</strong>
        </div>
        <div>
            <span style="color: #94a3b8;">Clicks:</span>
            <strong id="sticky-click-count" style="color: #f472b6;">{{ number_format($stats['total_clicks'] ?? 0) }}</strong>
        </div>
        <div>
            <span style="color: #94a3b8;">CTR:</span>
            <strong id="sticky-ctr" style="color: #6ee7b7;">{{ $stats['avg_ctr'] ?? 0 }}%</strong>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ========================================================
         5. PRODUCT DETAILS MODAL (EXECUTIVE REDESIGN)
         ======================================================== -->
    <div class="modal-overlay" id="productModal" onclick="closeModalOnOverlay(event)">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-header-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div class="modal-title-wrap">
                        <div class="modal-title" id="modalTitle">Product Details</div>
                        <div class="modal-subtitle" id="modalSubtitle">MindUnite Partner Directory</div>
                    </div>
                </div>
                <div class="modal-header-right" id="modalHeaderRight">
                    <!-- Dynamic Product ID Pill (replaces status active) + Optional Badges + Close Button -->
                </div>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <!-- Dynamically populated action buttons -->
            </div>
        </div>
    </div>

    <!-- ========================================================
         6. JAVASCRIPT: VIEW SWITCHER, FILTERING & IAB TRACKING
         ======================================================== -->
    <script>
        const trackedImpressions = new Set();
        const dwellTimers = new Map();
        let totalImpressions = {{ $stats['total_impressions'] ?? 0 }};
        let totalClicks = {{ $stats['total_clicks'] ?? 0 }};
        let currentViewMode = '{{ $viewMode ?? 'all' }}';
        let globalImpressionObserver = null;

        // 1. View Mode Switcher (All Products View vs Section-wise View)
        function switchViewMode(mode) {
            currentViewMode = mode;
            const allContainer = document.getElementById('viewModeAllContainer');
            const sectionContainer = document.getElementById('viewModeSectionContainer');
            const btnAll = document.getElementById('viewBtnAll');
            const btnSection = document.getElementById('viewBtnSection');

            if (mode === 'all') {
                if (allContainer) allContainer.style.display = 'block';
                if (sectionContainer) sectionContainer.style.display = 'none';
                if (btnAll) btnAll.classList.add('active');
                if (btnSection) btnSection.classList.remove('active');
            } else {
                if (allContainer) allContainer.style.display = 'none';
                if (sectionContainer) sectionContainer.style.display = 'block';
                if (btnAll) btnAll.classList.remove('active');
                if (btnSection) btnSection.classList.add('active');
            }

            // Sync URL query param without page reload so refreshing maintains view state
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('view', mode);
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            // Re-apply active search in newly active view
            const searchInput = document.getElementById('feedSearchInput');
            if (searchInput && searchInput.value) {
                executeFeedSearch(searchInput.value);
            }

            // Re-bind IAB impression observer for visible cards in the active view
            setTimeout(() => {
                initViewableImpressionTracker();
            }, 100);
        }

        // 1.5. Real-Time Client-Side Search (Product Name or Product ID)
        let searchDebounceTimer = null;

        function handleFeedSearch(rawQuery) {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                executeFeedSearch(rawQuery);
            }, 90);
        }

        function handleSearchKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(searchDebounceTimer);
                const val = event.target.value.trim();
                const url = new URL(window.location.href);
                if (val) {
                    url.searchParams.set('search', val);
                } else {
                    url.searchParams.delete('search');
                }
                url.searchParams.delete('cursor');
                window.location.href = url.toString();
            }
        }

        function clearFeedSearch() {
            const input = document.getElementById('feedSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('search') || url.searchParams.has('cursor')) {
                    url.searchParams.delete('search');
                    url.searchParams.delete('cursor');
                    window.location.href = url.toString();
                    return;
                }
            } catch (e) {}

            Object.keys(sectionState).forEach(secId => {
                sectionState[secId].activeCategory = 'all';
            });
            executeFeedSearch('');
        }

        function executeFeedSearch(rawQuery) {
            const query = (rawQuery || '').trim().toLowerCase();
            const cleanIdQuery = query.replace(/^#/, '');
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'flex' : 'none';
            }

            // Sync URL parameter without page reload
            try {
                const url = new URL(window.location.href);
                if (query) {
                    url.searchParams.set('search', query);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.replaceState({}, '', url.toString());
            } catch (e) {}

            // A. Filter All Products Grid (#grid-all-products)
            const allGrid = document.getElementById('grid-all-products');
            let totalAllMatches = 0;
            if (allGrid) {
                const allCards = allGrid.querySelectorAll('.product-card');
                allCards.forEach(card => {
                    if (!query) {
                        card.style.display = 'flex';
                        totalAllMatches++;
                        return;
                    }

                    const name = (card.getAttribute('data-product-name') || '').toLowerCase();
                    const pId = (card.getAttribute('data-product-id') || '').toLowerCase();
                    const searchBlob = (card.getAttribute('data-search-content') || '').toLowerCase();

                    const isMatch = name.includes(query) || 
                                    pId.includes(query) || 
                                    pId.includes(cleanIdQuery) || 
                                    searchBlob.includes(query);

                    if (isMatch) {
                        card.style.display = 'flex';
                        totalAllMatches++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                // Update All Products badge count
                const badgeAll = document.getElementById('viewCountAll');
                if (badgeAll) {
                    badgeAll.textContent = totalAllMatches;
                }

                // Update summary text
                const summaryTextEl = document.getElementById('allProductsSummaryText');
                if (summaryTextEl) {
                    if (query) {
                        summaryTextEl.innerHTML = `<strong>Search Results</strong> &bull; Showing ${totalAllMatches} matching items for "${escapeHtml(query)}"`;
                    } else {
                        summaryTextEl.innerHTML = `<strong>All Available Products</strong> &bull; Showing ${allCards.length} items sorted by priority`;
                    }
                }

                // Empty state for All Products view
                let emptyAllMsg = document.getElementById('emptyAllSearchMsg');
                if (totalAllMatches === 0 && query) {
                    if (!emptyAllMsg) {
                        emptyAllMsg = document.createElement('div');
                        emptyAllMsg.id = 'emptyAllSearchMsg';
                        emptyAllMsg.className = 'empty-section-message';
                        emptyAllMsg.style.gridColumn = '1 / -1';
                        emptyAllMsg.style.padding = '40px 20px';
                        emptyAllMsg.innerHTML = `
                            <p style="font-size: 15px; font-weight: 700; color: #475569; margin-bottom: 6px;">No products match "${escapeHtml(query)}"</p>
                            <p style="font-size: 13px; color: #94a3b8; margin: 0;">Try searching with a different product name, ID, or category keyword.</p>
                        `;
                        allGrid.appendChild(emptyAllMsg);
                    }
                    emptyAllMsg.style.display = 'block';
                } else if (emptyAllMsg) {
                    emptyAllMsg.style.display = 'none';
                }
            }

            // B. Filter Section-wise View (#viewModeSectionContainer)
            const sectionBlocks = document.querySelectorAll('#viewModeSectionContainer .section-block');
            let visibleSectionCount = 0;

            if (!query) {
                Object.keys(sectionState).forEach(secId => {
                    sectionState[secId].activeCategory = 'all';
                });
            }

            sectionBlocks.forEach(secBlock => {
                const secId = secBlock.id.replace('section-block-', '');
                const matchingCount = applySectionDisplay(secId);

                if (query) {
                    if (matchingCount > 0) {
                        secBlock.style.display = 'block';
                        visibleSectionCount++;
                    } else {
                        secBlock.style.display = 'none';
                    }
                } else {
                    secBlock.style.display = 'block';
                    visibleSectionCount++;
                }
            });

            // Update Section-wise badge count
            const badgeSec = document.getElementById('viewCountSection');
            if (badgeSec) {
                badgeSec.textContent = visibleSectionCount;
            }

            // Section-wise container global empty message
            const secContainer = document.getElementById('viewModeSectionContainer');
            if (secContainer) {
                let emptySecMsg = document.getElementById('emptySecSearchMsg');
                if (visibleSectionCount === 0 && query) {
                    if (!emptySecMsg) {
                        emptySecMsg = document.createElement('div');
                        emptySecMsg.id = 'emptySecSearchMsg';
                        emptySecMsg.className = 'empty-section-message';
                        emptySecMsg.style.padding = '50px 20px';
                        emptySecMsg.innerHTML = `
                            <p style="font-size: 15px; font-weight: 700; color: #475569; margin-bottom: 6px;">No sections found matching "${escapeHtml(query)}"</p>
                            <p style="font-size: 13px; color: #94a3b8; margin: 0;">Try searching for another product name or ID.</p>
                        `;
                        secContainer.appendChild(emptySecMsg);
                    }
                    emptySecMsg.style.display = 'block';
                } else if (emptySecMsg) {
                    emptySecMsg.style.display = 'none';
                }
            }

            // C. Re-bind IAB impression observer for newly visible cards
            setTimeout(() => {
                initViewableImpressionTracker();
            }, 60);
        }

        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // 2. Client-Side Section Expansion & Sub-Category Tab Filtering
        const sectionState = {};

        function applySectionDisplay(sectionId) {
            if (!sectionState[sectionId]) {
                sectionState[sectionId] = { expanded: false, activeCategory: 'all' };
            }
            const state = sectionState[sectionId];
            const grid = document.getElementById(`grid-sec-${sectionId}`);
            if (!grid) return 0;

            const searchInput = document.getElementById('feedSearchInput');
            const rawQuery = searchInput ? searchInput.value : '';
            const query = (rawQuery || '').trim().toLowerCase();
            const cleanIdQuery = query.replace(/^#/, '');

            const cards = Array.from(grid.querySelectorAll(`.product-card[data-section-id="${sectionId}"]`));

            // 1. Identify which cards and categories in this section match the search query
            const searchMatchingCatIds = new Set();
            cards.forEach(card => {
                let searchMatches = true;
                if (query) {
                    const name = (card.getAttribute('data-product-name') || '').toLowerCase();
                    const pId = (card.getAttribute('data-product-id') || '').toLowerCase();
                    const searchBlob = (card.getAttribute('data-search-content') || '').toLowerCase();

                    searchMatches = name.includes(query) || 
                                    pId.includes(query) || 
                                    pId.includes(cleanIdQuery) || 
                                    searchBlob.includes(query);
                }
                if (searchMatches) {
                    const cardCatId = card.getAttribute('data-category-id');
                    if (cardCatId) {
                        searchMatchingCatIds.add(String(cardCatId));
                    }
                }
            });

            // 2. Adjust Category Tabs based on search results
            const tabsBar = document.getElementById(`cat-tabs-bar-${sectionId}`);
            if (tabsBar) {
                const allBtn = tabsBar.querySelector('.cat-tab-btn[data-category-id="all"]');
                const catBtns = tabsBar.querySelectorAll('.cat-tab-btn:not([data-category-id="all"])');

                if (query) {
                    if (searchMatchingCatIds.size === 1) {
                        // Exactly 1 category matches in this section:
                        // Show ONLY that category and hide "All" + all other empty categories!
                        const singleCatId = Array.from(searchMatchingCatIds)[0];

                        if (allBtn) {
                            allBtn.style.display = 'none';
                            allBtn.classList.remove('active');
                        }

                        catBtns.forEach(btn => {
                            if (btn.getAttribute('data-category-id') === String(singleCatId)) {
                                btn.style.display = '';
                                btn.classList.add('active');
                            } else {
                                btn.style.display = 'none';
                                btn.classList.remove('active');
                            }
                        });

                        state.activeCategory = singleCatId;
                    } else if (searchMatchingCatIds.size > 1) {
                        // Multiple categories have matches: show "All" and only the matching categories
                        if (allBtn) {
                            allBtn.style.display = '';
                        }
                        let hasActiveVisible = false;
                        catBtns.forEach(btn => {
                            const btnCatId = btn.getAttribute('data-category-id');
                            if (searchMatchingCatIds.has(btnCatId)) {
                                btn.style.display = '';
                                if (String(state.activeCategory) === btnCatId) {
                                    hasActiveVisible = true;
                                    btn.classList.add('active');
                                } else {
                                    btn.classList.remove('active');
                                }
                            } else {
                                btn.style.display = 'none';
                                btn.classList.remove('active');
                            }
                        });

                        if (state.activeCategory === 'all' || !hasActiveVisible) {
                            state.activeCategory = 'all';
                            if (allBtn) allBtn.classList.add('active');
                        } else if (allBtn) {
                            allBtn.classList.remove('active');
                        }
                    } else {
                        // 0 matches in this section
                    }
                } else {
                    // No active search: restore all category tabs
                    if (allBtn) {
                        allBtn.style.display = '';
                        if (state.activeCategory === 'all') {
                            allBtn.classList.add('active');
                        } else {
                            allBtn.classList.remove('active');
                        }
                    }
                    catBtns.forEach(btn => {
                        btn.style.display = '';
                        if (String(state.activeCategory) === btn.getAttribute('data-category-id')) {
                            btn.classList.add('active');
                        } else {
                            btn.classList.remove('active');
                        }
                    });
                }
            }

            // 3. Filter cards according to activeCategory & search query
            const matchingCards = [];
            cards.forEach(card => {
                const cardCatId = card.getAttribute('data-category-id');
                const catMatches = (state.activeCategory === 'all' || cardCatId === String(state.activeCategory));

                let searchMatches = true;
                if (query) {
                    const name = (card.getAttribute('data-product-name') || '').toLowerCase();
                    const pId = (card.getAttribute('data-product-id') || '').toLowerCase();
                    const searchBlob = (card.getAttribute('data-search-content') || '').toLowerCase();

                    searchMatches = name.includes(query) || 
                                    pId.includes(query) || 
                                    pId.includes(cleanIdQuery) || 
                                    searchBlob.includes(query);
                }

                if (catMatches && searchMatches) {
                    matchingCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            const btnWrap = document.getElementById(`see-more-wrap-${sectionId}`);

            matchingCards.forEach(card => {
                card.style.display = 'flex';
            });

            if (btnWrap) {
                if (query) {
                    btnWrap.style.display = 'none';
                } else {
                    btnWrap.style.display = matchingCards.length > 0 ? 'flex' : 'none';
                }
            }

            // Handle empty state inside section if filter results in 0
            let emptyMsg = grid.parentElement.querySelector('.empty-filter-msg');
            if (matchingCards.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-section-message empty-filter-msg';
                    grid.parentElement.appendChild(emptyMsg);
                }
                if (query) {
                    emptyMsg.textContent = `No products found matching "${rawQuery}" in this category`;
                } else {
                    emptyMsg.textContent = 'No products found in this category';
                }
                emptyMsg.style.display = 'block';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }

            return matchingCards.length;
        }

        function toggleSectionExpand(sectionId) {
            if (!sectionState[sectionId]) {
                sectionState[sectionId] = { expanded: false, activeCategory: 'all' };
            }
            sectionState[sectionId].expanded = !sectionState[sectionId].expanded;
            applySectionDisplay(sectionId);
        }

        function filterSectionCategory(sectionId, categoryId, tabBtn) {
            const tabsBar = tabBtn.closest('.categories-tabs-bar');
            if (tabsBar) {
                tabsBar.querySelectorAll('.cat-tab-btn').forEach(btn => btn.classList.remove('active'));
                tabBtn.classList.add('active');
            }

            if (!sectionState[sectionId]) {
                sectionState[sectionId] = { expanded: false, activeCategory: 'all' };
            }
            sectionState[sectionId].activeCategory = categoryId;
            const matchingCount = applySectionDisplay(sectionId);

            // Recompute section visibility if search is active
            const searchInput = document.getElementById('feedSearchInput');
            const query = (searchInput ? searchInput.value : '').trim();
            if (query) {
                const secBlock = document.getElementById(`section-block-${sectionId}`);
                if (secBlock) {
                    secBlock.style.display = matchingCount > 0 ? 'block' : 'none';
                }

                // Update section count badge
                let visibleCount = 0;
                document.querySelectorAll('#viewModeSectionContainer .section-block').forEach(sb => {
                    if (sb.style.display !== 'none') visibleCount++;
                });
                const badgeSec = document.getElementById('viewCountSection');
                if (badgeSec) badgeSec.textContent = visibleCount;
            }

            setTimeout(() => {
                initViewableImpressionTracker();
            }, 60);
        }

        // 3. IAB Viewable Impression Observer (50% visibility for 600ms)
        function initViewableImpressionTracker() {
            if (globalImpressionObserver) {
                globalImpressionObserver.disconnect();
            }

            const activeContainer = document.getElementById('grid-dedicated-section')
                || (currentViewMode === 'section'
                    ? document.getElementById('viewModeSectionContainer')
                    : document.getElementById('viewModeAllContainer'));

            if (!activeContainer) return;

            const boostedCards = activeContainer.querySelectorAll('.product-card.boosted[data-campaign-id]');
            if (!boostedCards.length) return;

            globalImpressionObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const card = entry.target;
                    const campaignId = card.getAttribute('data-campaign-id');
                    const productName = card.getAttribute('data-product-name') || 'Sponsored Product';

                    if (!campaignId || trackedImpressions.has(campaignId)) return;

                    if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                        // Dwell 600ms check
                        if (!dwellTimers.has(campaignId)) {
                            const timer = setTimeout(() => {
                                dwellTimers.delete(campaignId);
                                if (trackedImpressions.has(campaignId)) return;
                                trackedImpressions.add(campaignId);

                                // Fire API call: POST /api/advertisements/impressions
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
                                        if (data.debounced_count > 0 && data.recorded_count === 0) {
                                            showToast('impression', 'ℹ️ Impression Debounced (Anti-Spam)', `Campaign #${campaignId} already counted recently. Rapid reloads are throttled.`);
                                            return;
                                        }

                                        // Update card count across all instances of this campaign card in DOM
                                        document.querySelectorAll(`.card-imp-${campaignId}`).forEach(impSpan => {
                                            const current = parseInt(impSpan.textContent.replace(/,/g, '')) || 0;
                                            impSpan.textContent = (current + 1).toLocaleString();
                                        });

                                        totalImpressions++;
                                        updateStickyMetrics();

                                        showToast('impression', '⚡ IAB Impression Recorded!', `Campaign #${campaignId} ("${productName}") &rarr; DB count incremented.`);
                                    }
                                })
                                .catch(err => console.error('Impression error:', err));
                            }, 600);

                            dwellTimers.set(campaignId, timer);
                        }
                    } else {
                        // User scrolled away before 600ms dwell completed
                        if (dwellTimers.has(campaignId)) {
                            clearTimeout(dwellTimers.get(campaignId));
                            dwellTimers.delete(campaignId);
                        }
                    }
                });
            }, {
                threshold: 0.5
            });

            boostedCards.forEach(card => globalImpressionObserver.observe(card));
        }

        // 4. Handle Card & CTA Click
        function handleCardClick(product) {
            const isBoosted = !!product.is_boosted;
            const campaignId = product.ad_campaign_id;

            if (isBoosted && campaignId) {
                // Call API: POST /api/advertisements/{id}/click
                fetch(`/api/advertisements/${campaignId}/click`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ placement: 'industry_product_feed' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (data.debounced) {
                            showToast('info', 'ℹ️ Click Already Counted (Anti-Spam)', `Campaign #${campaignId} click was already recorded recently. Rapid repeated clicks are debounced.`);
                            return;
                        }

                        const totalAdClicks = data.data && data.data.total_clicks ? data.data.total_clicks : null;

                        document.querySelectorAll(`.card-click-${campaignId}`).forEach(clickSpan => {
                            const current = parseInt(clickSpan.textContent.replace(/,/g, '')) || 0;
                            clickSpan.textContent = (totalAdClicks || (current + 1)).toLocaleString();
                        });

                        totalClicks++;
                        updateStickyMetrics();

                        showToast('click', '🖱️ Verified Ad Click Recorded!', `Campaign #${campaignId} ("${product.product_name}") &rarr; DB updated.`);
                    }
                })
                .catch(err => console.error('Click error:', err));
            }

            // Open Detail Modal
            openProductModal(product);
        }

        function toggleProductLike(productId, productName, btnEl, event) {
            if (event) event.stopPropagation();

            const isCurrentlyLiked = btnEl.classList.contains('liked');
            const countEl = document.querySelector(`.card-likes-count-${productId}`);

            let currentCount = countEl ? (parseInt(countEl.textContent.replace(/[^0-9]/g, '')) || 0) : 0;
            let newLiked = !isCurrentlyLiked;
            let newCount = newLiked ? currentCount + 1 : Math.max(0, currentCount - 1);

            // Optimistic UI update across all instances in DOM
            document.querySelectorAll(`.card-like-btn-${productId}`).forEach(b => {
                if (newLiked) b.classList.add('liked'); else b.classList.remove('liked');
            });
            document.querySelectorAll(`.card-like-label-${productId}`).forEach(l => {
                l.textContent = newLiked ? 'Liked' : 'Like';
            });
            document.querySelectorAll(`.card-likes-count-${productId}`).forEach(c => {
                c.textContent = `(${newCount.toLocaleString()})`;
            });

            fetch(`/preview/products/${productId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Preview-Mode': 'true'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    const serverLikes = data.data.likes_count;
                    document.querySelectorAll(`.card-likes-count-${productId}`).forEach(c => {
                        c.textContent = `(${serverLikes.toLocaleString()})`;
                    });
                    showToast('click', data.data.is_liked ? 'Product Liked 👍' : 'Product Unliked', `${productName}`);
                }
            })
            .catch(err => {
                console.error('Like error:', err);
                document.querySelectorAll(`.card-like-btn-${productId}`).forEach(b => {
                    if (isCurrentlyLiked) b.classList.add('liked'); else b.classList.remove('liked');
                });
                document.querySelectorAll(`.card-like-label-${productId}`).forEach(l => {
                    l.textContent = isCurrentlyLiked ? 'Liked' : 'Like';
                });
                document.querySelectorAll(`.card-likes-count-${productId}`).forEach(c => {
                    c.textContent = `(${currentCount.toLocaleString()})`;
                });
            });
        }

        function openProductModal(product) {
            const campaignId = product.ad_campaign_id;
            const productName = product.product_name || 'Product Details';
            const rawId = (product.product_id || product.id || 'N/A').toString();
            const displayId = rawId.startsWith('#') ? rawId : `#${rawId}`;
            const productDbId = product.id || product.product_id;
            const description = product.description || 'No description provided for this product.';
            const sectionName = product.section_name || (product.section ? product.section.name : 'N/A');
            const categoryName = product.category_name || (product.category ? product.category.category_name : 'N/A');
            const networkType = product.network_type ? (product.network_type.charAt(0).toUpperCase() + product.network_type.slice(1)) : 'Psychology';
            const industryType = product.industry_type ? (product.industry_type.charAt(0).toUpperCase() + product.industry_type.slice(1)) : ((product.industry && product.industry.name) ? product.industry.name : 'Biotechnology');
            const publishedAt = product.created_at ? new Date(product.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Recently';
            const rawTags = (product.tags && Array.isArray(product.tags) && product.tags.length) ? product.tags : ['Diagnostic', 'Research', 'Apparatus'];
            const pocName = product.poc_name || 'Niaz Ahmed Nayeem';
            const pocEmail = product.poc_email || 'niaz.softvence@gmail.com';
            const pocPhone = product.poc_phone || '+8801800000000';
            const destinationUrl = product.product_url || 'https://mindunite.pixelstack.cloud/';
            const productImage = product.image_url || product.image || '';
            const hasValidImage = productImage && !productImage.includes('placeholder');

            const creatorName = (product.creator && product.creator.name) 
                ? product.creator.name 
                : ((product.industry && product.industry.name) ? product.industry.name : 'MindUnite Partner');

            const pocInitials = (pocName || 'MU')
                .split(' ')
                .filter(Boolean)
                .slice(0, 2)
                .map(w => w[0].toUpperCase())
                .join('') || 'MU';

            // Sync live counts with feed card if available
            const viewsSpan = document.querySelector(`.card-views-count-${product.id}`);
            const likesSpan = document.querySelector(`.card-likes-count-${product.id}`);
            const viewsCount = viewsSpan ? (parseInt(viewsSpan.textContent.replace(/[^0-9]/g, '')) || 0) : (product.views_count || 1);
            const likesCount = likesSpan ? (parseInt(likesSpan.textContent.replace(/[^0-9]/g, '')) || 0) : (product.likes_count || 0);

            const existingLikeBtn = document.querySelector(`.card-like-btn-${productDbId}`);
            const isCurrentlyLiked = existingLikeBtn ? existingLikeBtn.classList.contains('liked') : !!product.is_liked;

            // Record view in backend DB when opened
            if (productDbId) {
                fetch(`/preview/products/${productDbId}/details`, {
                    headers: { 'Accept': 'application/json', 'X-Preview-Mode': 'true' }
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.data && typeof d.data.views_count !== 'undefined') {
                        document.querySelectorAll(`.card-views-count-${product.id}`).forEach(vSpan => {
                            vSpan.textContent = d.data.views_count.toLocaleString();
                        });
                        const modalViews = document.getElementById('modalViewsCount');
                        if (modalViews) modalViews.textContent = d.data.views_count.toLocaleString();
                    }
                })
                .catch(() => {});
            }

            // 1. Populate Modal Header
            document.getElementById('modalTitle').textContent = productName;
            document.getElementById('modalSubtitle').textContent = `${networkType} Network \u2022 ${industryType}`;

            let headerRightHtml = '';
            if (product.is_boosted) {
                headerRightHtml += `<span class="modal-image-badge sponsored" style="position:static; margin-right:4px;">\u26A1 Sponsored</span>`;
            } else if (product.is_new_release) {
                headerRightHtml += `<span class="modal-image-badge new-release" style="position:static; margin-right:4px;">\uD83D\uDD25 New Release</span>`;
            }

            // Product ID Pill directly replaces Status: Active
            headerRightHtml += `
                <div class="modal-product-id-pill" onclick="copyToClipboard('${displayId}', 'Product ID copied to clipboard!')" title="Click to copy Product ID">
                    <span class="id-tag">ID:</span>
                    <span class="id-code">${displayId}</span>
                    <svg class="copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                    </svg>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeModal()" title="Close dialog">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            `;
            document.getElementById('modalHeaderRight').innerHTML = headerRightHtml;

            // 2. Build Tags HTML
            let tagsHtml = '';
            rawTags.forEach(t => {
                tagsHtml += `<span class="modal-tag-chip">#${t}</span>`;
            });

            // 3. Populate Modal Body
            const imps = product.impressions_count || 0;
            const clks = product.clicks_count || 0;
            const ctr = imps > 0 ? ((clks / imps) * 100).toFixed(1) + '%' : '0.0%';

            document.getElementById('modalBody').innerHTML = `
                <div>
                    <!-- Hero Showcase Box -->
                    <div class="modal-hero-box">
                        <div class="modal-image-col">
                            ${product.is_boosted ? '<div class="modal-image-badge sponsored">\u26A1 Sponsored</div>' : (product.is_new_release ? '<div class="modal-image-badge new-release">\uD83D\uDD25 New Release</div>' : '')}
                            ${hasValidImage ? `
                                <img src="${productImage}" alt="${escapeHtml(productName)}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
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
                            ` : `
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
                            `}
                        </div>

                        <div class="modal-info-col">
                            <div>
                                <h3 class="modal-product-title">${escapeHtml(productName)}</h3>
                                <div class="modal-partner-row">
                                    <span>By <strong style="color: #0f172a;">${escapeHtml(creatorName)}</strong></span>
                                    <span class="partner-verified-tag">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="currentColor">
                                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                        </svg>
                                        Verified Partner
                                    </span>
                                </div>

                                <div class="modal-meta-chips-grid">
                                    <div class="modal-meta-chip">
                                        <div class="chip-label">Network \u2022 Industry</div>
                                        <div class="chip-value">${networkType} \u2022 ${industryType}</div>
                                    </div>
                                    <div class="modal-meta-chip">
                                        <div class="chip-label">Section</div>
                                        <div class="chip-value">${escapeHtml(sectionName)}</div>
                                    </div>
                                    <div class="modal-meta-chip">
                                        <div class="chip-label">Category</div>
                                        <div class="chip-value">${escapeHtml(categoryName)}</div>
                                    </div>
                                    <div class="modal-meta-chip">
                                        <div class="chip-label">Product Identifier</div>
                                        <div class="chip-value" style="font-family: monospace; color: #0284c7;">${displayId}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-stats-strip">
                                <div class="modal-stats-group">
                                    <span class="modal-stat-pill">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        <strong id="modalViewsCount">${viewsCount.toLocaleString()}</strong> Views
                                    </span>
                                    <span class="modal-stat-pill">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="currentColor"><path d="M2 10.5a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5h-3A1.5 1.5 0 012 19.5v-9zM8 19.5a1.5 1.5 0 001.5 1.5h7.24a2.5 2.5 0 002.43-1.92l1.62-6.5A2.5 2.5 0 0018.36 9.5H14V5.5A2.5 2.5 0 0011.5 3c-.83 0-1.5.67-1.5 1.5v3.83L8 10.42v9.08z"/></svg>
                                        <strong class="card-likes-count-${productDbId}">(${likesCount.toLocaleString()})</strong> Likes
                                    </span>
                                </div>
                                <div style="color: #64748b; font-size: 11.5px;">
                                    Published on ${publishedAt}
                                </div>
                            </div>
                        </div>
                    </div>

                    ${product.is_boosted ? `
                        <!-- Sponsored Performance Box -->
                        <div class="modal-boost-analytics">
                            <div class="modal-boost-analytics-header">
                                <div class="modal-boost-analytics-title">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                    Sponsored Campaign Performance
                                </div>
                                <div class="modal-boost-analytics-badge">
                                    \u26A1 Active Feed Boost
                                </div>
                            </div>
                            <div class="modal-boost-stats-row">
                                <div class="modal-boost-stat-card">
                                    <div class="modal-boost-stat-label">Total Impressions</div>
                                    <div class="modal-boost-stat-num" style="color: #38bdf8;">${imps.toLocaleString()}</div>
                                </div>
                                <div class="modal-boost-stat-card">
                                    <div class="modal-boost-stat-label">Verified Clicks</div>
                                    <div class="modal-boost-stat-num" style="color: #f472b6;">${clks.toLocaleString()}</div>
                                </div>
                                <div class="modal-boost-stat-card">
                                    <div class="modal-boost-stat-label">Click-Through Rate</div>
                                    <div class="modal-boost-stat-num" style="color: #34d399;">${ctr}</div>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    <!-- Description Card -->
                    <div class="modal-section-card">
                        <div class="modal-section-heading">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            Product Overview & Technical Description
                        </div>
                        <div class="modal-description-content">
                            ${cleanModalDescription(description)}
                        </div>
                    </div>

                    <!-- Product Tags Card -->
                    <div class="modal-section-card">
                        <div class="modal-section-heading">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                            Classifications & Tags
                        </div>
                        <div class="modal-tags-wrap">
                            ${tagsHtml}
                        </div>
                    </div>

                    <!-- Person of Contact (POC) Executive Card -->
                    <div class="modal-section-card" style="margin-bottom: 4px;">
                        <div class="modal-section-heading">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                            Authorized Person of Contact
                        </div>
                        <div class="modal-poc-executive-card">
                            <div class="modal-poc-profile">
                                <div class="modal-poc-avatar">${pocInitials}</div>
                                <div class="modal-poc-details">
                                    <div class="poc-name">${escapeHtml(pocName)}</div>
                                    <div class="poc-role">Authorized Partner Representative</div>
                                </div>
                            </div>
                            <div class="modal-poc-actions">
                                <a href="mailto:${pocEmail}" class="modal-poc-btn" title="Send email to ${pocEmail}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                                    Email POC
                                </a>
                                <a href="tel:${pocPhone}" class="modal-poc-btn" title="Call ${pocPhone}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                                    Call POC
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // 4. Populate Modal Footer Actions
            document.getElementById('modalFooter').innerHTML = `
                <div class="modal-footer-left">
                    <button type="button" 
                            class="modal-btn-action card-like-btn-${productDbId} ${isCurrentlyLiked ? 'liked' : ''}" 
                            onclick="toggleProductLike(${productDbId}, '${escapeHtml(productName)}', this, event)"
                            title="Like this product">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 10.5a1.5 1.5 0 011.5-1.5h3a1.5 1.5 0 011.5 1.5v9a1.5 1.5 0 01-1.5 1.5h-3A1.5 1.5 0 012 19.5v-9zM8 19.5a1.5 1.5 0 001.5 1.5h7.24a2.5 2.5 0 002.43-1.92l1.62-6.5A2.5 2.5 0 0018.36 9.5H14V5.5A2.5 2.5 0 0011.5 3c-.83 0-1.5.67-1.5 1.5v3.83L8 10.42v9.08z"/>
                        </svg>
                        <span class="card-like-label-${productDbId}">${isCurrentlyLiked ? 'Liked' : 'Like'}</span>
                        <span class="card-likes-count-${productDbId}">(${likesCount.toLocaleString()})</span>
                    </button>

                    <button type="button" class="modal-btn-action" onclick="copyToClipboard('${destinationUrl}', 'Product link copied to clipboard!')" title="Copy product URL">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        <span>Copy Link</span>
                    </button>
                </div>

                <div class="modal-footer-right">
                    <button type="button" class="modal-btn-secondary" onclick="closeModal()">Close</button>
                    <a href="${destinationUrl}" target="_blank" class="modal-btn-primary" onclick="if(window.recordClickFromModal) window.recordClickFromModal(${productDbId}, ${campaignId || 'null'})">
                        <span>Visit Official Website</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <line x1="7" y1="17" x2="17" y2="7"></line>
                            <polyline points="7 7 17 7 17 17"></polyline>
                        </svg>
                    </a>
                </div>
            `;

            // 5. Reveal modal with smooth animation
            const modal = document.getElementById('productModal');
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                modal.classList.add('active');
            });
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('productModal');
            if (!modal) return;
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }, 180);
        }

        function closeModalOnOverlay(e) {
            if (e.target.id === 'productModal') {
                closeModal();
            }
        }

        function recordClickFromModal(productDbId, campaignId) {
            if (campaignId) {
                fetch(`/api/advertisements/${campaignId}/click`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ placement: 'industry_product_feed_modal' })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && !data.debounced) {
                        totalClicks++;
                        updateStickyMetrics();
                        document.querySelectorAll(`.card-click-${campaignId}`).forEach(clickSpan => {
                            const current = parseInt(clickSpan.textContent.replace(/,/g, '')) || 0;
                            clickSpan.textContent = (current + 1).toLocaleString();
                        });
                    }
                })
                .catch(() => {});
            }
        }

        function copyToClipboard(text, message) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('click', '\uD83D\uDCCB Copied!', message || text);
                }).catch(() => {
                    promptCopy(text);
                });
            } else {
                promptCopy(text);
            }
        }

        function promptCopy(text) {
            window.prompt('Copy to clipboard: Ctrl+C, Enter', text);
        }

        // Close modal on Escape key
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('productModal');
                if (modal && modal.style.display === 'flex') {
                    closeModal();
                }
            }
        });

        function updateStickyMetrics() {
            const stickyImp = document.getElementById('sticky-imp-count');
            const stickyClk = document.getElementById('sticky-click-count');
            const stickyCtr = document.getElementById('sticky-ctr');

            if (stickyImp) stickyImp.textContent = totalImpressions.toLocaleString();
            if (stickyClk) stickyClk.textContent = totalClicks.toLocaleString();
            if (stickyCtr) stickyCtr.textContent = totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(1) + '%' : '0%';
        }

        function showToast(type, title, message) {
            const container = document.getElementById('toastContainer');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast-card ${type}`;
            toast.innerHTML = `
                <div style="font-size: 20px; line-height: 1;">${type === 'impression' ? '⚡' : '🖱️'}</div>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 2px;">${title}</div>
                    <div style="color: #cbd5e1; font-size: 12.5px;">${message}</div>
                </div>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        function cleanModalDescription(html) {
            if (!html) return 'No description provided for this product.';
            if (!/<[a-z][\s\S]*>/i.test(html)) {
                return `<p style="margin:0 0 8px 0; line-height:1.6;">${html.replace(/\n/g, '<br>')}</p>`;
            }
            return html
                .replace(/id="docs-internal-guid-[^"]*"/gi, '')
                .replace(/style="[^"]*"/gi, function(match) {
                    return match
                        .replace(/(font-size|font-family|line-height|white-space|margin-top|margin-bottom|padding-inline-start)\s*:[^;"]*;?/gi, '')
                        .replace(/color\s*:\s*#000000;?/gi, '');
                });
        }

        // ========================================================
        // 7. CURSOR-BASED AUTO-LOAD INFINITE SCROLL SYSTEM
        // ========================================================
        let infiniteScrollObserver = null;
        let isFetchingNextBatch = false;
        let currentNextCursor = null;
        let hasMoreItemsToLoad = false;
        let currentTargetGridId = 'grid-dedicated-section';

        function initInfiniteScroll() {
            const wrap = document.getElementById('infiniteScrollWrap');
            if (!wrap) return;

            hasMoreItemsToLoad = wrap.getAttribute('data-has-more') === 'true';
            currentNextCursor = wrap.getAttribute('data-next-cursor') || null;
            currentTargetGridId = wrap.getAttribute('data-target-grid') || 'grid-dedicated-section';

            const sentinel = document.getElementById('infiniteScrollSentinel');
            if (!sentinel) return;

            if (infiniteScrollObserver) {
                infiniteScrollObserver.disconnect();
            }

            if ('IntersectionObserver' in window) {
                infiniteScrollObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && hasMoreItemsToLoad && !isFetchingNextBatch && currentNextCursor) {
                            loadNextProductBatch();
                        }
                    });
                }, {
                    root: null,
                    rootMargin: '1200px', // Pre-fetch 1200px before user reaches bottom for zero perceived lag
                    threshold: 0.01
                });

                infiniteScrollObserver.observe(sentinel);
            }

            window.addEventListener('scroll', handleWindowScrollInfiniteFallback, { passive: true });
        }

        function handleWindowScrollInfiniteFallback() {
            if (isFetchingNextBatch || !hasMoreItemsToLoad || !currentNextCursor) return;
            const scrollHeight = document.documentElement.scrollHeight;
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const clientHeight = window.innerHeight;

            if (scrollTop + clientHeight >= scrollHeight - 1000) {
                loadNextProductBatch();
            }
        }

        async function loadNextProductBatch() {
            if (isFetchingNextBatch || !hasMoreItemsToLoad || !currentNextCursor) return;
            isFetchingNextBatch = true;

            const loader = document.getElementById('infiniteScrollLoader');
            const errorEl = document.getElementById('infiniteScrollError');
            const endEl = document.getElementById('infiniteScrollEnd');
            const sentinel = document.getElementById('infiniteScrollSentinel');

            if (loader) loader.style.display = 'flex';
            if (errorEl) errorEl.style.display = 'none';

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('cursor', currentNextCursor);
                url.searchParams.set('ajax', '1');

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error(`Server returned HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success && data.html) {
                    const targetGrid = document.getElementById(currentTargetGridId) 
                        || document.getElementById('grid-dedicated-section')
                        || document.getElementById('grid-all-products');

                    if (targetGrid) {
                        const tempContainer = document.createElement('div');
                        tempContainer.innerHTML = data.html;
                        const newCards = Array.from(tempContainer.children);

                        const existingIds = new Set(
                            Array.from(targetGrid.querySelectorAll('.product-card'))
                                .map(c => c.getAttribute('data-product-id'))
                                .filter(Boolean)
                        );

                        newCards.forEach(card => {
                            const pId = card.getAttribute('data-product-id');
                            if (pId && existingIds.has(pId)) {
                                return;
                            }
                            if (pId) existingIds.add(pId);

                            card.classList.add('fade-in-card');
                            targetGrid.appendChild(card);
                        });
                    }

                    currentNextCursor = data.next_cursor || null;
                    hasMoreItemsToLoad = Boolean(data.has_more);

                    // Re-sync IAB viewable impression observer for newly added cards
                    setTimeout(() => {
                        initViewableImpressionTracker();
                    }, 100);
                } else {
                    hasMoreItemsToLoad = false;
                    currentNextCursor = null;
                }
            } catch (err) {
                console.error('Infinite scroll loading error:', err);
                if (errorEl) errorEl.style.display = 'flex';
            } finally {
                isFetchingNextBatch = false;
                if (loader) loader.style.display = 'none';

                if (!hasMoreItemsToLoad) {
                    if (endEl) endEl.style.display = 'flex';
                    if (infiniteScrollObserver) {
                        infiniteScrollObserver.disconnect();
                    }
                    window.removeEventListener('scroll', handleWindowScrollInfiniteFallback);
                } else if (currentNextCursor && sentinel) {
                    // If user is already near bottom on high-res displays, pre-fetch subsequent batch
                    const rect = sentinel.getBoundingClientRect();
                    if (rect.top <= window.innerHeight + 1200) {
                        setTimeout(() => {
                            if (!isFetchingNextBatch && hasMoreItemsToLoad && currentNextCursor) {
                                loadNextProductBatch();
                            }
                        }, 50);
                    }
                }
            }
        }

        function retryInfiniteScroll() {
            const errorEl = document.getElementById('infiniteScrollError');
            if (errorEl) errorEl.style.display = 'none';
            loadNextProductBatch();
        }

        document.addEventListener('DOMContentLoaded', () => {
            initViewableImpressionTracker();
            initInfiniteScroll();
            const searchInput = document.getElementById('feedSearchInput');
            if (searchInput && searchInput.value) {
                executeFeedSearch(searchInput.value);
            }
        });
    </script>
</body>
</html>
