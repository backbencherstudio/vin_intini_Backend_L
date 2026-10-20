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

        .industry-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
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

        .see-more-btn.is-expanded svg {
            transform: rotate(180deg);
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

        /* Modal Simulation */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 20px;
        }

        .modal-card {
            background: #ffffff;
            border-radius: 16px;
            max-width: 680px;
            max-height: 90vh;
            width: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: fadeInDown 0.2s ease;
        }

        .modal-body {
            padding: 20px 24px;
            overflow-y: auto;
            max-height: calc(90vh - 75px);
        }

        .modal-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-main);
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .modal-body {
            padding: 24px;
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
            <!-- Header for Selected Industry -->
            <div class="industry-header">
                <div class="industry-header-row">
                    <div>
                        <h1 class="industry-main-title">{{ $industries[$currentIndustry]['name'] ?? 'Industry Products' }}</h1>
                        <p class="industry-main-desc">{{ $industries[$currentIndustry]['desc'] ?? 'Explore the latest releases advancing brain health research and treatment.' }}</p>
                    </div>

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
                            <span class="view-badge-count">{{ $flatProducts->count() }}</span>
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
                            <span class="view-badge-count">{{ count($groupedBySection) }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: All Type Products Unified Grid (Default) -->
            <div id="viewModeAllContainer" style="{{ $viewMode !== 'section' ? 'display: block;' : 'display: none;' }}">
                <div class="all-products-header-bar">
                    <div>
                        <strong>All Available Products</strong> &bull; Showing {{ $flatProducts->count() }} items sorted by priority
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
                    @endphp

                    <div class="section-block" id="section-block-{{ $section->id }}">
                        <!-- Section Title -->
                        <h2 class="section-title">{{ $section->name }}</h2>

                        <!-- Category Tabs Sub-navigation -->
                        <div class="categories-tabs-bar">
                            <button type="button" 
                                    class="cat-tab-btn active" 
                                    onclick="filterSectionCategory({{ $section->id }}, 'all', this)">
                                All
                            </button>

                            @foreach($categories as $cat)
                                <button type="button" 
                                        class="cat-tab-btn" 
                                        onclick="filterSectionCategory({{ $section->id }}, {{ $cat->id }}, this)">
                                    {{ $cat->category_name }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Products Grid -->
                        @if($secProducts->isNotEmpty())
                            <div class="products-grid" id="grid-sec-{{ $section->id }}">
                                @foreach($secProducts as $prodIndex => $prod)
                                    @include('partials.product-feed-card', [
                                        'prod' => $prod,
                                        'isExtra' => $prodIndex >= 6,
                                        'sectionId' => $section->id
                                    ])
                                @endforeach
                            </div>

                            <!-- See More Products Button (per section, default shows 6 products) -->
                            @if($secProducts->count() > 6)
                                <div class="see-more-wrap" id="see-more-wrap-{{ $section->id }}">
                                    <button type="button" 
                                            class="see-more-btn" 
                                            id="see-more-btn-{{ $section->id }}"
                                            onclick="toggleSectionExpand({{ $section->id }})">
                                        <span class="btn-text">See more products ({{ $secProducts->count() - 6 }} more)</span>
                                        <svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                                    </button>
                                </div>
                            @endif
                        @else
                            <!-- Empty State matching Screenshot 2 -->
                            <div class="empty-section-message">
                                @if($currentIndustry === 'psychotropics')
                                    No medications found in this category
                                @else
                                    No products found in this category
                                @endif
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
         5. PRODUCT DETAILS MODAL
         ======================================================== -->
    <div class="modal-overlay" id="productModal" onclick="closeModalOnOverlay(event)">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Product Details</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Dynamically populated -->
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

            // Re-bind IAB impression observer for visible cards in the active view
            setTimeout(() => {
                initViewableImpressionTracker();
            }, 100);
        }

        // 2. Client-Side Section Expansion & Sub-Category Tab Filtering
        const sectionState = {};

        function applySectionDisplay(sectionId) {
            if (!sectionState[sectionId]) {
                sectionState[sectionId] = { expanded: false, activeCategory: 'all' };
            }
            const state = sectionState[sectionId];
            const grid = document.getElementById(`grid-sec-${sectionId}`);
            if (!grid) return;

            const cards = Array.from(grid.querySelectorAll(`.product-card[data-section-id="${sectionId}"]`));
            const matchingCards = [];

            cards.forEach(card => {
                const cardCatId = card.getAttribute('data-category-id');
                const matches = (state.activeCategory === 'all' || cardCatId === String(state.activeCategory));
                if (matches) {
                    matchingCards.push(card);
                } else {
                    card.style.display = 'none';
                }
            });

            // Determine visibility based on expanded state
            if (state.expanded) {
                matchingCards.forEach(card => {
                    card.style.display = 'flex';
                });
            } else {
                matchingCards.forEach((card, idx) => {
                    if (idx < 6) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Update "See more products" button per section
            const btnWrap = document.getElementById(`see-more-wrap-${sectionId}`);
            const btn = document.getElementById(`see-more-btn-${sectionId}`);
            if (btnWrap && btn) {
                const btnText = btn.querySelector('.btn-text');
                const remaining = matchingCards.length - 6;

                if (matchingCards.length > 6) {
                    btnWrap.style.display = 'flex';
                    if (state.expanded) {
                        btn.classList.add('is-expanded');
                        if (btnText) btnText.textContent = 'Show less';
                    } else {
                        btn.classList.remove('is-expanded');
                        if (btnText) btnText.textContent = `See more products (${remaining} more)`;
                    }
                } else {
                    btnWrap.style.display = 'none';
                }
            }

            // Handle empty state inside section if filter results in 0
            let emptyMsg = grid.parentElement.querySelector('.empty-filter-msg');
            if (matchingCards.length === 0) {
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-section-message empty-filter-msg';
                    emptyMsg.textContent = 'No products found in this category';
                    grid.parentElement.appendChild(emptyMsg);
                }
                emptyMsg.style.display = 'block';
            } else if (emptyMsg) {
                emptyMsg.style.display = 'none';
            }
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
            applySectionDisplay(sectionId);
        }

        // 3. IAB Viewable Impression Observer (50% visibility for 600ms)
        function initViewableImpressionTracker() {
            if (globalImpressionObserver) {
                globalImpressionObserver.disconnect();
            }

            const activeContainer = currentViewMode === 'section'
                ? document.getElementById('viewModeSectionContainer')
                : document.getElementById('viewModeAllContainer');

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
            const productId = product.product_id || 'N/A';
            const productDbId = product.id || product.product_id;
            const description = product.description || 'No description provided for this product.';
            const sectionName = product.section_name || (product.section ? product.section.name : 'N/A');
            const categoryName = product.category_name || (product.category ? product.category.category_name : 'N/A');
            const networkType = product.network_type ? (product.network_type.charAt(0).toUpperCase() + product.network_type.slice(1)) : 'Psychology';
            const industryType = product.industry_type ? (product.industry_type.charAt(0).toUpperCase() + product.industry_type.slice(1)) : ((product.industry && product.industry.name) ? product.industry.name : 'Biotechnology');
            const status = product.status ? (product.status.charAt(0).toUpperCase() + product.status.slice(1)) : 'Active';
            const publishedAt = product.created_at ? new Date(product.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Recently';
            const rawTags = (product.tags && Array.isArray(product.tags) && product.tags.length) ? product.tags : ['Diagnostic', 'Research', 'Apparatus'];
            const pocName = product.poc_name || 'Niaz Ahmed Nayeem';
            const pocEmail = product.poc_email || 'niaz.softvence@gmail.com';
            const pocPhone = product.poc_phone || '+8801800000000';
            const destinationUrl = product.product_url || 'https://mindunite.pixelstack.cloud/';
            const productImage = product.image_url || product.image || 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=80';

            // Sync live counts with feed card if available
            const viewsSpan = document.querySelector(`.card-views-count-${product.id}`);
            const likesSpan = document.querySelector(`.card-likes-count-${product.id}`);
            const viewsCount = viewsSpan ? (parseInt(viewsSpan.textContent.replace(/[^0-9]/g, '')) || 0) : (product.views_count || 1);
            const likesCount = likesSpan ? (parseInt(likesSpan.textContent.replace(/[^0-9]/g, '')) || 0) : (product.likes_count || 0);

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

            let tagsHtml = '';
            rawTags.forEach(t => {
                tagsHtml += `<span style="background: #ffffff; border: 1px solid #e2e8f0; color: #475569; padding: 4px 14px; border-radius: 16px; font-size: 12px; font-weight: 500;">${t}</span>`;
            });

            document.getElementById('modalTitle').textContent = 'View Product';
            document.getElementById('modalBody').innerHTML = `
                <div>
                    <!-- 1. Top Card: Image + Overview Grid -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; background: #ffffff; margin-bottom: 16px;">
                        <div style="display: flex; gap: 16px; align-items: flex-start;">
                            <img src="${productImage}" alt="${productName}" 
                                 onerror="this.src='https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=400&q=80'"
                                 style="width: 140px; height: 140px; object-fit: cover; border-radius: 8px; flex-shrink: 0; border: 1px solid #f1f5f9;">
                            
                            <div style="flex: 1; min-width: 0; display: flex; flex-direction: column; justify-content: space-between; min-height: 140px;">
                                <div>
                                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                        <h3 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3;">
                                            ${productName}
                                        </h3>
                                        <span style="background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; border-radius: 12px; font-size: 11px; font-weight: 600; padding: 2px 10px; white-space: nowrap;">
                                            ${status}
                                        </span>
                                    </div>

                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; font-size: 12.5px;">
                                        <div>
                                            <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">Network Type</div>
                                            <div style="font-weight: 600; color: #1e293b;">${networkType}</div>
                                        </div>
                                        <div>
                                            <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">Industry Type</div>
                                            <div style="font-weight: 600; color: #1e293b;">${industryType}</div>
                                        </div>
                                        <div>
                                            <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">Section</div>
                                            <div style="font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${sectionName}</div>
                                        </div>
                                        <div>
                                            <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">Category</div>
                                            <div style="font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${categoryName}</div>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px; margin-top: 8px; border-top: 1px solid #f1f5f9; font-size: 11.5px; color: #64748b;">
                                    <div style="display: flex; gap: 14px;">
                                        <span>Total Views <strong id="modalViewsCount" style="color: #0f172a; font-weight: 700;">${viewsCount.toLocaleString()}</strong></span>
                                        <span>Likes <strong style="color: #0f172a; font-weight: 700;">${likesCount.toLocaleString()}</strong></span>
                                    </div>
                                    <div style="color: #64748b; font-size: 11px;">
                                        Published ${publishedAt}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${product.is_boosted ? `
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-around;">
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Impressions</div>
                                <div style="font-size: 18px; font-weight: 800; color: #0284c7;">${(product.impressions_count || 0).toLocaleString()}</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Clicks</div>
                                <div style="font-size: 18px; font-weight: 800; color: #ec4899;">${(product.clicks_count || 0).toLocaleString()}</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">CTR</div>
                                <div style="font-size: 18px; font-weight: 800; color: #10b981;">${product.impressions_count > 0 ? ((product.clicks_count / product.impressions_count) * 100).toFixed(1) : 0}%</div>
                            </div>
                        </div>
                    ` : ''}

                    <!-- 2. Description Box -->
                    <div style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; background: #ffffff;">
                        <h4 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Description</h4>
                        <div style="color: #475569; font-size: 13px; line-height: 1.6; margin: 0; word-break: break-word;">
                            ${cleanModalDescription(description)}
                        </div>
                    </div>

                    <!-- 3. Product Link -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">Product Link</h4>
                        <a href="${destinationUrl}" target="_blank" 
                           style="color: #0284c7; font-size: 13px; text-decoration: none; word-break: break-all; display: inline-block; font-weight: 600;">
                            ${destinationUrl} &rarr;
                        </a>
                    </div>

                    <!-- 4. Product Tags -->
                    <div style="margin-bottom: 16px;">
                        <h4 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0;">Product Tags</h4>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            ${tagsHtml}
                        </div>
                    </div>

                    <!-- 5. Person of Contact -->
                    <div>
                        <h4 style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin: 0 0 10px 0;">Person of Contact</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; background: #ffffff;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Name</div>
                                <div style="font-size: 12.5px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    ${pocName}
                                </div>
                            </div>
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; background: #ffffff;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Email</div>
                                <div style="font-size: 12.5px; font-weight: 600; color: #0f172a; word-break: break-all;">
                                    ${pocEmail}
                                </div>
                            </div>
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; background: #ffffff;">
                                <div style="font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 4px;">Phone Number</div>
                                <div style="font-size: 12.5px; font-weight: 600; color: #0f172a;">
                                    ${pocPhone}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('productModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function closeModalOnOverlay(e) {
            if (e.target.id === 'productModal') {
                closeModal();
            }
        }

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

        document.addEventListener('DOMContentLoaded', () => {
            initViewableImpressionTracker();
        });
    </script>
</body>
</html>
