<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindUnite - Home Newsfeed & Ads Simulation</title>
    
    <!-- Google Fonts: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-page: #f8fafc;
            --bg-surface: #ffffff;
            --border-light: #e2e8f0;
            --border-subtle: #f1f5f9;
            --text-main: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --text-sub: #94a3b8;
            --brand-primary: #0284c7;
            --brand-primary-hover: #0369a1;
            --brand-cyan: #0ea5e9;
            --brand-indigo: #6366f1;
            --brand-amber: #f59e0b;
            --success: #10b981;
            --card-radius: 10px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button {
            font-family: inherit;
        }

        /* ========================================================
           1. TOP NAVBAR (MATCHING MINDUNITE LIVE UI)
           ======================================================== */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid var(--border-light);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
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
        }

        .nav-item:hover {
            color: var(--brand-primary);
            background: #f8fafc;
        }

        .nav-item.active {
            color: var(--brand-cyan);
            font-weight: 700;
        }

        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 12px;
            right: 12px;
            height: 2.5px;
            background: var(--brand-cyan);
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
            color: var(--text-main);
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
           2. THREE COLUMN LAYOUT CONTAINER
           ======================================================== */
        .layout-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 20px 80px;
            display: grid;
            grid-template-columns: 240px minmax(0, 620px) 300px;
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1120px) {
            .layout-container {
                grid-template-columns: 220px minmax(0, 1fr);
            }
            .right-sidebar {
                display: none;
            }
        }

        @media (max-width: 860px) {
            .layout-container {
                grid-template-columns: 1fr;
            }
            .left-sidebar {
                display: none;
            }
        }

        /* ========================================================
           3. LEFT SIDEBAR (MANAGE NETWORK & PINNED WIDGET)
           ======================================================== */
        .left-sidebar {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .manage-network-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 16px 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .sidebar-heading {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-main);
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-subtle);
            margin-bottom: 12px;
        }

        .sidebar-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .sidebar-menu-item:hover {
            background: #f8fafc;
            color: var(--brand-primary);
        }

        .sidebar-menu-item svg {
            width: 17px;
            height: 17px;
            fill: currentColor;
            opacity: 0.75;
            flex-shrink: 0;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--border-subtle);
            margin: 12px 0;
        }

        /* Pinned Company Widget (Softvence Delta style from screenshot) */
        .pinned-company-widget {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .company-widget-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .company-widget-avatar {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .company-widget-avatar svg {
            width: 22px;
            height: 22px;
        }

        .company-widget-info h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.25;
        }

        .company-widget-info p {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .company-widget-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            padding-top: 8px;
            border-top: 1px solid var(--border-subtle);
            color: var(--text-muted);
        }

        .company-widget-bottom .dashboard-link {
            color: #ca8a04;
            font-weight: 700;
            font-size: 11.5px;
            transition: color 0.15s ease;
        }

        .company-widget-bottom .dashboard-link:hover {
            color: #a16207;
            text-decoration: underline;
        }

        /* ========================================================
           4. MIDDLE COLUMN: CREATE POST & NEWSFEED STREAM
           ======================================================== */
        .feed-stream {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Create Post Card */
        .create-post-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 14px 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .create-post-top {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .create-post-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            background: #e2e8f0;
            flex-shrink: 0;
        }

        .create-post-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .create-post-input {
            flex: 1;
            border: none;
            outline: none;
            font-size: 13.5px;
            color: var(--text-main);
            background: transparent;
        }

        .create-post-input::placeholder {
            color: var(--text-sub);
        }

        .create-post-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 10px;
            border-top: 1px solid var(--border-subtle);
        }

        .create-post-tools {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tool-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .tool-btn:hover {
            background: #f1f5f9;
            color: var(--brand-primary);
        }

        .tool-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        .send-post-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .send-post-btn:hover {
            color: var(--brand-primary);
            transform: translateX(2px);
        }

        .send-post-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Post Cards */
        .post-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .post-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Header of Post Card */
        .post-card-header {
            padding: 16px 16px 10px 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .post-header-left {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
        }

        .post-avatar-wrap {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            overflow: hidden;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 15px;
            font-weight: 800;
            color: var(--brand-primary);
        }

        .post-avatar-wrap.circle {
            border-radius: 50%;
        }

        .post-avatar-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-meta-col {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .post-author-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .post-author-name {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
        }

        .sponsored-badge {
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #bae6fd;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1px 6px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .post-author-subtitle {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.35;
        }

        .post-time-meta {
            font-size: 11.5px;
            color: var(--text-sub);
            margin-top: 1px;
        }

        .post-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .follow-outline-btn {
            background: transparent;
            border: 1px solid #0f172a;
            color: #0f172a;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .follow-outline-btn:hover {
            background: #0f172a;
            color: #ffffff;
        }

        .icon-more-btn {
            background: transparent;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .icon-more-btn:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .icon-more-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Post Body Text */
        .post-body-text {
            padding: 4px 16px 12px 16px;
            font-size: 13.5px;
            color: #334155;
            line-height: 1.6;
            word-break: break-word;
        }

        .see-more-link {
            color: var(--brand-primary);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            margin-left: 4px;
        }

        .see-more-link:hover {
            text-decoration: underline;
        }

        /* Post Media Image Container */
        .post-media-container {
            padding: 0 16px 12px 16px;
            cursor: pointer;
        }

        .post-media-img {
            width: 100%;
            max-height: 480px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #f1f5f9;
            display: block;
        }

        /* Sponsored Ad Live Metrics & CTA Bar */
        /* ========================================================
           LINKEDIN-STYLE SPONSORED AD CARD
           ======================================================== */
        .sponsored-ad-card.linkedin-ad-card {
            border: 1px solid #e0dfdc;
            border-radius: 8px;
            background: #ffffff;
            margin-bottom: 12px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s ease;
        }

        .sponsored-ad-card.linkedin-ad-card:hover {
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* LinkedIn Card Header */
        .linkedin-ad-header {
            padding: 12px 16px 8px 16px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .linkedin-ad-author-col {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .linkedin-ad-logo {
            width: 48px;
            height: 48px;
            border-radius: 4px;
            overflow: hidden;
            background: #f3f2ef;
            border: 1px solid #e0dfdc;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .linkedin-ad-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .linkedin-ad-meta {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .linkedin-author-name {
            font-size: 14.5px;
            font-weight: 700;
            color: #191919;
            line-height: 1.25;
            cursor: pointer;
        }

        .linkedin-author-name:hover {
            color: #0a66c2;
            text-decoration: underline;
        }

        .linkedin-author-tagline {
            font-size: 12px;
            color: #666666;
            line-height: 1.35;
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .linkedin-promoted-meta {
            font-size: 12px;
            color: #666666;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 2px;
        }

        .linkedin-promoted-meta svg {
            width: 12px;
            height: 12px;
            fill: #666666;
        }

        .linkedin-header-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .linkedin-follow-btn {
            color: #0a66c2;
            background: transparent;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 5px 12px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.15s ease;
        }

        .linkedin-follow-btn:hover {
            background: rgba(10, 102, 194, 0.08);
            color: #004182;
        }

        /* Post Body Caption */
        .sponsored-ad-card.linkedin-ad-card .post-body-text {
            padding: 4px 16px 12px 16px;
            font-size: 14px;
            color: #191919;
            line-height: 1.5;
            word-break: break-word;
        }

        /* Edge-to-Edge Media Container */
        .sponsored-ad-card.linkedin-ad-card .post-media-container {
            padding: 0;
            margin: 0;
            cursor: pointer;
            position: relative;
            background: #000000;
            overflow: hidden;
            display: block;
        }

        .sponsored-ad-card.linkedin-ad-card .post-media-img {
            width: 100%;
            max-height: 460px;
            object-fit: cover;
            border-radius: 0;
            border: none;
            display: block;
            margin: 0;
        }

        /* LinkedIn Signature Link Preview Box (Attached directly flush below creative) */
        .linkedin-preview-box {
            background: #f3f6f8;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .linkedin-preview-box:hover {
            background: #e9eef2;
        }

        .linkedin-preview-left {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .linkedin-domain-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .linkedin-sku-tag {
            background: #e2e8f0;
            color: #475569;
            font-family: monospace;
            font-size: 10.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
        }

        .linkedin-new-release-tag {
            background: #ffedd5;
            color: #ea580c;
            font-size: 10.5px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
        }

        .linkedin-product-title {
            font-size: 14.5px;
            font-weight: 600;
            color: #191919;
            line-height: 1.35;
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: color 0.15s ease;
        }

        .linkedin-preview-box:hover .linkedin-product-title {
            color: #0a66c2;
            text-decoration: underline;
        }

        .linkedin-meta-desc {
            font-size: 12px;
            color: #666666;
            line-height: 1.4;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 5px;
        }

        .linkedin-tag-chip {
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: 600;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .linkedin-preview-right {
            flex-shrink: 0;
        }

        .linkedin-cta-btn {
            background: transparent;
            color: #0a66c2;
            border: 1px solid #0a66c2;
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .linkedin-cta-btn:hover {
            background: rgba(10, 102, 194, 0.1);
            border-color: #004182;
            color: #004182;
        }

        /* Real-time Analytics & Testing Bar */
        .linkedin-metric-bar {
            padding: 8px 16px;
            background: #fafbfc;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            color: #64748b;
            flex-wrap: wrap;
            gap: 8px;
        }

        .linkedin-metric-bar strong {
            font-family: monospace;
            font-size: 12px;
        }

        .linkedin-specs-modal-btn {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }

        .linkedin-specs-modal-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #94a3b8;
        }

        /* Product Quick-View Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 12px;
            max-width: 620px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.05);
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.96);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            font-size: 17px;
            font-weight: 700;
            color: #191919;
            margin: 0;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 24px;
            color: #666666;
            cursor: pointer;
            line-height: 1;
            padding: 4px;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .modal-close-btn:hover {
            color: #191919;
            background: #f1f5f9;
        }

        .modal-body {
            padding: 20px;
        }

        /* Engagement Stats Row (likes / comments) */
        .post-stats-row {
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-subtle);
        }

        /* Action Buttons Row */
        .post-actions-row {
            padding: 6px 16px;
            display: flex;
            align-items: center;
            justify-content: space-around;
        }

        .post-action-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
            padding: 6px 18px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 7px;
            transition: all 0.15s ease;
        }

        .post-action-btn:hover {
            background: #f8fafc;
            color: var(--brand-primary);
        }

        .post-action-btn svg {
            width: 17px;
            height: 17px;
            fill: currentColor;
        }

        /* ========================================================
           5. RIGHT SIDEBAR (GROUPS YOU MIGHT BE INTERESTED)
           ======================================================== */
        .right-sidebar {
            position: sticky;
            top: 84px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .groups-widget-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-light);
            border-radius: var(--card-radius);
            padding: 18px 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .widget-heading {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
        }

        .group-list {
            display: flex;
            flex-direction: column;
        }

        .group-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 6px 0;
        }

        .group-left {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .group-avatar {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-weight: 800;
            color: var(--brand-primary);
            font-size: 14px;
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .group-info {
            min-width: 0;
        }

        .group-name {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .group-members {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .join-outline-btn {
            background: transparent;
            border: 1px solid #0f172a;
            color: #0f172a;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 16px;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .join-outline-btn:hover {
            background: #0f172a;
            color: #ffffff;
        }

        .group-divider {
            height: 1px;
            background: var(--border-subtle);
            margin: 12px 0;
        }

        /* ========================================================
           6. STICKY REAL-TIME MONITOR & TOASTS
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
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 1000;
            max-width: 420px;
            pointer-events: none;
        }

        .toast-card {
            background: rgba(15, 23, 42, 0.98);
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            color: #fff;
            pointer-events: auto;
            animation: toastSlide 0.25s ease;
        }

        .toast-card.impression {
            border: 1.5px solid #0284c7;
        }

        .toast-card.click {
            border: 1.5px solid #ec4899;
        }

        @keyframes toastSlide {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>

    <!-- ========================================================
         1. TOP NAVIGATION BAR (MATCHING MINDUNITE LIVE UI)
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
                <a href="/newsfeed-preview" class="nav-item active">
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

                <a href="/product-feed-preview?network_type=psychology&industry_type=biotechnology" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 2a9 9 0 0 0-9 9c0 3.87 2.33 7.2 5.75 8.48.38.14.75.25 1.15.33v2.19h4.2v-2.19c.4-.08.77-.19 1.15-.33A9.006 9.006 0 0 0 21 11a9 9 0 0 0-9-9zm0 16c-3.86 0-7-3.14-7-7s3.14-7 7-7 7 3.14 7 7-3.14 7-7 7z"/></svg>
                    <span class="label-with-chevron">
                        Psychology Network <span class="nav-chevron">▾</span>
                    </span>
                </a>

                <a href="/product-feed-preview?network_type=neuroscience&industry_type=biotechnology" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    <span class="label-with-chevron">
                        Neuroscience Network <span class="nav-chevron">▾</span>
                    </span>
                </a>

                <a href="javascript:void(0)" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24"><path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/></svg>
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
         2. THREE-COLUMN FEED CONTAINER
         ======================================================== -->
    <div class="layout-container">

        <!-- ====================================================
             LEFT SIDEBAR: MANAGE NETWORK & PINNED WIDGET
             ==================================================== -->
        <aside class="left-sidebar">
            <div class="manage-network-card">
                <h3 class="sidebar-heading">Manage Network</h3>
                <ul class="sidebar-menu">
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            <span>My Connections</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span>Connection Requests</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <span>Following & Followers</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                            <span>Groups</span>
                        </a>
                    </li>
                </ul>

                <div class="sidebar-divider"></div>

                <ul class="sidebar-menu">
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 0 0 .12-.61l-1.92-3.32a.488.488 0 0 0-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 0 0-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 0 0-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                            <span>General Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/></svg>
                            <span>Security Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="sidebar-menu-item">
                            <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                            <span>Billing History</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Pinned Company Widget (Softvence Delta style from screenshot) -->
            <div class="pinned-company-widget">
                <div class="company-widget-top">
                    <div class="company-widget-avatar">
                        <svg viewBox="0 0 40 40"><circle cx="20" cy="20" r="18" fill="#ecfdf5" stroke="#10b981" stroke-width="2"/><path d="M14 24 C14 17, 26 17, 26 24 C26 28, 14 28, 14 24" fill="#10b981"/></svg>
                    </div>
                    <div class="company-widget-info">
                        <h4>Softvence Delta</h4>
                        <p>Limitless Together</p>
                    </div>
                </div>
                <div class="company-widget-bottom">
                    <span>👥 0 followers</span>
                    <a href="javascript:void(0)" class="dashboard-link">Dashboard</a>
                </div>
            </div>
        </aside>

        <!-- ====================================================
             MIDDLE COLUMN: CREATE POST & NEWSFEED STREAM
             ==================================================== -->
        <main class="feed-stream">

            <!-- 1. Create Post Box (Exact match with screenshot) -->
            <div class="create-post-card">
                <div class="create-post-top">
                    <div class="create-post-avatar">
                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=120&h=120&q=80" alt="You" onerror="this.src='https://ui-avatars.com/api/?name=User&background=0284c7&color=fff'">
                    </div>
                    <input type="text" class="create-post-input" placeholder="Type your post...">
                </div>
                <div class="create-post-bottom">
                    <div class="create-post-tools">
                        <button type="button" class="tool-btn" title="Add Image">
                            <svg viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                        </button>
                        <button type="button" class="tool-btn" title="Add Video">
                            <svg viewBox="0 0 24 24"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                        </button>
                        <button type="button" class="tool-btn" title="Add Emoji">
                            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
                        </button>
                    </div>
                    <button type="button" class="send-post-btn" title="Send Post">
                        <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                    </button>
                </div>
            </div>

            <!-- 2. Posts Stream (Mixed Organic Posts & Injected Sponsored Ads) -->
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
                        $companyName = $item['company']['name'] ?? 'MindUnite Partner';
                        $companyTagline = $item['company']['tagline'] ?? 'Make life delicious and innovative!';
                        $compLogo = $item['company']['logo'] ?? null;
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
                        <div class="post-body-text">
                            {{ $item['description'] ?? 'Discover advanced technological solutions engineered for brain health and scientific research.' }}
                            <a href="javascript:void(0)" class="see-more-link">See more</a>
                        </div>

                        <!-- LinkedIn Ad Creative Banner (Edge-to-Edge) -->
                        <div class="post-media-container" onclick="trackAdClick({{ $campaignId }}, '{{ addslashes($productName) }}', '{{ $item['destination_url'] ?? '#' }}', event)">
                            @if(!empty($adImage))
                                <img src="{{ $adImage }}" alt="{{ $productName }}" class="post-media-img" onerror="this.src='https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80'">
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

                        <!-- LinkedIn Engagement Stats Row -->
                        <div class="post-stats-row">
                            <span style="display:flex; align-items:center; gap:4px;">
                                <span style="background:#0a66c2; color:#fff; border-radius:50%; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center; font-size:10px;">👍</span>
                                <span>1 like</span>
                            </span>
                            <span>0 comments</span>
                        </div>

                        <!-- LinkedIn Action Buttons Row -->
                        <div class="post-actions-row">
                            <button type="button" class="post-action-btn">
                                <svg viewBox="0 0 24 24"><path d="M19.46 11l-3.91-3.91a7 7 0 0 1-1.69-2.74l-.49-1.47A2.76 2.76 0 0 0 10.76 1 2.75 2.75 0 0 0 8 3.74v1.12a9.19 9.19 0 0 0 .46 2.85L8.89 9H4.12A2.74 2.74 0 0 0 1.5 11.45a2.58 2.58 0 0 0 .54 1.63L5 18.52a3 3 0 0 0 2.45 1.48h8.86a3 3 0 0 0 2.87-2.12l1.9-6.41A1.75 1.75 0 0 0 21 11a1.76 1.76 0 0 0-1.54 0z"/></svg>
                                <span>Like</span>
                            </button>
                            <button type="button" class="post-action-btn">
                                <svg viewBox="0 0 24 24"><path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                                <span>Comment</span>
                            </button>
                            <button type="button" class="post-action-btn">
                                <svg viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>
                                <span>Repost</span>
                            </button>
                            <button type="button" class="post-action-btn">
                                <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                                <span>Send</span>
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

                        <div class="post-body-text">
                            {{ $item['description'] }}
                            @if(strlen($item['description']) > 160)
                                <a href="javascript:void(0)" class="see-more-link">See more</a>
                            @endif
                        </div>

                        <!-- Post Media Images (if available) -->
                        @if(!empty($item['media']) && count($item['media']) > 0)
                            <div class="post-media-container">
                                @foreach($item['media'] as $media)
                                    <img src="{{ asset('storage/' . $media->file_path) }}" alt="Post image" class="post-media-img" onerror="this.style.display='none';">
                                @endforeach
                            </div>
                        @endif

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

        </main>

        <!-- ====================================================
             RIGHT SIDEBAR: GROUPS YOU MIGHT BE INTERESTED
             ==================================================== -->
        <aside class="right-sidebar">
            <div class="groups-widget-card">
                <h3 class="widget-heading">Groups you might be Interested</h3>

                <div class="group-list">
                    <!-- Group 1: Vin Group (from screenshot) -->
                    <div class="group-item">
                        <div class="group-left">
                            <div class="group-avatar" style="background:#fee2e2; border-color:#fecaca;">
                                <span style="font-size: 18px;">🎨</span>
                            </div>
                            <div class="group-info">
                                <h4 class="group-name">Vin Group</h4>
                                <p class="group-members">👥 1 Group members</p>
                            </div>
                        </div>
                        <button type="button" class="join-outline-btn">Join</button>
                    </div>

                    <div class="group-divider"></div>

                    <!-- Group 2: CartForGood (from screenshot) -->
                    <div class="group-item">
                        <div class="group-left">
                            <div class="group-avatar" style="background:#1e3a8a; border-color:#1e40af;">
                                <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:#60a5fa;"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                            </div>
                            <div class="group-info">
                                <h4 class="group-name">CartForGood</h4>
                                <p class="group-members">👥 1 Group members</p>
                            </div>
                        </div>
                        <button type="button" class="join-outline-btn">Join</button>
                    </div>

                    <!-- Dynamic Suggested Groups from DB -->
                    @foreach($suggestedGroups ?? [] as $sGroup)
                        <div class="group-divider"></div>
                        <div class="group-item">
                            <div class="group-left">
                                <div class="group-avatar">
                                    @if(!empty($sGroup->logo))
                                        <img src="{{ asset('storage/' . $sGroup->logo) }}" alt="{{ $sGroup->name }}" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($sGroup->name) }}&background=0284c7&color=fff'">
                                    @else
                                        {{ substr($sGroup->name, 0, 2) }}
                                    @endif
                                </div>
                                <div class="group-info">
                                    <h4 class="group-name">{{ $sGroup->name }}</h4>
                                    <p class="group-members">👥 {{ rand(2, 18) }} Group members</p>
                                </div>
                            </div>
                            <button type="button" class="join-outline-btn">Join</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

    </div>

    <!-- ========================================================
         7. STICKY REAL-TIME MONITOR & TOASTS
         ======================================================== -->
    <div class="sticky-monitor">
        <div style="font-weight: 800; color: #a5b4fc; display: flex; align-items: center; gap: 6px;">
            <span class="pulse-indicator"></span> MindUnite Ads Monitor:
        </div>
        <div>
            <span style="color: #94a3b8;">Total Imp:</span>
            <strong id="sticky-imp-count" style="color: #38bdf8;">{{ number_format($totalImpressions ?? 0) }}</strong>
        </div>
        <div>
            <span style="color: #94a3b8;">Clicks:</span>
            <strong id="sticky-click-count" style="color: #f472b6;">{{ number_format($totalClicks ?? 0) }}</strong>
        </div>
        <div>
            <span style="color: #94a3b8;">CTR:</span>
            <strong id="sticky-ctr" style="color: #6ee7b7;">{{ ($totalImpressions ?? 0) > 0 ? number_format((($totalClicks ?? 0) / $totalImpressions) * 100, 1) : 0 }}%</strong>
        </div>
    </div>

    <!-- Product Quick-View Modal -->
    <div class="modal-overlay" id="productModal" onclick="closeModalOnOverlay(event)">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Product Details</h3>
                <button type="button" class="modal-close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Injected via openProductQuickView() -->
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- ========================================================
         8. REAL-TIME TRACKING SCRIPT (IAB VIEWABLE IMPRESSIONS & CLICKS)
         ======================================================== -->
    <script>
        let totalImpressions = {{ (int) ($totalImpressions ?? 0) }};
        let totalClicks = {{ (int) ($totalClicks ?? 0) }};
        const trackedImpressions = new Set();
        const dwellTimers = new Map();

        // 1. IAB Viewable Impression Observer (50% visibility for 600ms dwell)
        function initViewableImpressionTracker() {
            const sponsoredCards = document.querySelectorAll('.post-card.sponsored-ad-card[data-campaign-id]');
            if (!sponsoredCards.length) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const card = entry.target;
                    const campaignId = card.getAttribute('data-campaign-id');
                    const adTitle = card.getAttribute('data-ad-title') || 'Sponsored Ad';

                    if (!campaignId || trackedImpressions.has(campaignId)) return;

                    if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                        // Start 600ms dwell timer
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
                                        placement: 'newsfeed'
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        // Update card counter
                                        const impSpan = document.getElementById(`card-imp-${campaignId}`);
                                        if (impSpan) {
                                            const current = parseInt(impSpan.textContent.replace(/,/g, '')) || 0;
                                            impSpan.textContent = (current + 1).toLocaleString();
                                        }

                                        totalImpressions++;
                                        updateStickyMetrics();

                                        showToast('impression', '⚡ IAB Impression Recorded!', `Campaign #${campaignId} ("${adTitle}") &rarr; DB count incremented.`);
                                    }
                                })
                                .catch(err => console.error('Impression error:', err));
                            }, 600);

                            dwellTimers.set(campaignId, timer);
                        }
                    } else {
                        // Scrolled away before dwell completed
                        if (dwellTimers.has(campaignId)) {
                            clearTimeout(dwellTimers.get(campaignId));
                            dwellTimers.delete(campaignId);
                        }
                    }
                });
            }, {
                threshold: 0.5
            });

            sponsoredCards.forEach(card => observer.observe(card));
        }

        // 2. Track Verified Ad Click
        function trackAdClick(campaignId, title, destinationUrl, e) {
            if (e) {
                e.stopPropagation();
            }

            // Immediately open destination in new tab (synchronous user gesture avoids browser popup blocker)
            if (destinationUrl && destinationUrl !== '#' && destinationUrl !== 'javascript:void(0)') {
                window.open(destinationUrl, '_blank');
            }

            if (!campaignId) return;

            fetch(`/api/advertisements/${campaignId}/click`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ placement: 'newsfeed' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const totalAdClicks = data.data && typeof data.data.total_clicks !== 'undefined' ? data.data.total_clicks : null;

                    const clickSpan = document.getElementById(`card-click-${campaignId}`);
                    if (clickSpan) {
                        const current = parseInt(clickSpan.textContent.replace(/,/g, '')) || 0;
                        const newClicks = totalAdClicks !== null ? totalAdClicks : (current + 1);
                        clickSpan.textContent = newClicks.toLocaleString();

                        // Recalculate CTR in real-time
                        const impSpan = document.getElementById(`card-imp-${campaignId}`);
                        const ctrSpan = document.getElementById(`card-ctr-${campaignId}`);
                        if (impSpan && ctrSpan) {
                            const imps = parseInt(impSpan.textContent.replace(/,/g, '')) || 0;
                            ctrSpan.textContent = imps > 0 ? ((newClicks / imps) * 100).toFixed(1) + '%' : '0%';
                        }
                    }

                    totalClicks++;
                    updateStickyMetrics();

                    showToast('click', '🖱️ Verified Ad Click Recorded!', `Campaign #${campaignId} ("${title}") &rarr; DB count updated.`);
                }
            })
            .catch(err => {
                console.error('Click error:', err);
            });
        }

        // 3. Update Sticky Metrics
        function updateStickyMetrics() {
            const stickyImp = document.getElementById('sticky-imp-count');
            const stickyClk = document.getElementById('sticky-click-count');
            const stickyCtr = document.getElementById('sticky-ctr');

            if (stickyImp) stickyImp.textContent = totalImpressions.toLocaleString();
            if (stickyClk) stickyClk.textContent = totalClicks.toLocaleString();
            if (stickyCtr) stickyCtr.textContent = totalImpressions > 0 ? ((totalClicks / totalImpressions) * 100).toFixed(1) + '%' : '0%';
        }

        // 4. Toast Notifications
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

        // 5. Product Quick-View Modal
        function openProductQuickView(item) {
            const details = item.product_details || {};
            const campaignId = item.ad_campaign_id;
            const productName = item.product_name || item.title || 'Sponsored Product';
            const productId = item.product_id || details.product_id || 'N/A';
            const description = item.description || 'No description provided.';
            const sectionName = details.section_name || 'N/A';
            const categoryName = details.category_name || 'N/A';
            const networkType = details.network_type ? details.network_type.toUpperCase() : 'N/A';
            const tags = details.tags || [];
            const pocName = details.poc_name || null;
            const pocEmail = details.poc_email || null;
            const pocPhone = details.poc_phone || null;
            const companyName = (item.company && item.company.name) ? item.company.name : 'MindUnite Partner';
            const destinationUrl = item.destination_url || '#';
            const impSpan = document.getElementById(`card-imp-${campaignId}`);
            const clickSpan = document.getElementById(`card-click-${campaignId}`);
            const impCount = impSpan ? impSpan.textContent : (item.impressions_count || 0);
            const clickCount = clickSpan ? clickSpan.textContent : (item.clicks_count || 0);

            let tagHtml = '';
            if (tags.length) {
                tagHtml = `<div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:10px;">` + 
                    tags.map(t => `<span style="background:#e0f2fe; color:#0369a1; font-size:11px; font-weight:600; padding:2px 8px; border-radius:12px; border:1px solid #bae6fd;">#${t}</span>`).join('') + 
                    `</div>`;
            }

            document.getElementById('modalTitle').textContent = productName;
            document.getElementById('modalBody').innerHTML = `
                <div style="margin-bottom: 16px;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
                        <span style="font-size:12px; font-weight:700; color:#0284c7; background:#e0f2fe; padding:3px 10px; border-radius:6px;">
                            ⚡ SPONSORED PRODUCT
                        </span>
                        ${details.is_new_release ? `<span style="font-size:11.5px; font-weight:700; color:#ea580c; background:#ffedd5; padding:3px 8px; border-radius:6px;">🔥 New Release</span>` : ''}
                        ${campaignId ? `<span style="font-size:11.5px; font-weight:700; color:#475569; background:#f1f5f9; padding:3px 8px; border-radius:6px;">Campaign #${campaignId}</span>` : ''}
                        <span style="color:#64748b; font-size:13px; font-family:monospace; font-weight:600;">ID: ${productId}</span>
                    </div>

                    <p style="color:#334155; font-size:14px; line-height:1.6; margin-bottom:16px;">${description}</p>

                    ${tagHtml}

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin: 16px 0; display: flex; align-items: center; justify-content: space-around;">
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Impressions</div>
                            <div style="font-size: 18px; font-weight: 800; color: #0284c7;">${impCount}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Clicks</div>
                            <div style="font-size: 18px; font-weight: 800; color: #ec4899;">${clickCount}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Placement</div>
                            <div style="font-size: 15px; font-weight: 800; color: #10b981;">Newsfeed</div>
                        </div>
                    </div>

                    <div style="background:#f8fafc; padding:14px; border-radius:10px; font-size:13px; color:#475569; line-height:1.7; border:1px solid #e2e8f0;">
                        <div><strong>Manufacturer / Partner:</strong> ${companyName}</div>
                        <div><strong>Section:</strong> ${sectionName}</div>
                        <div><strong>Category:</strong> ${categoryName}</div>
                        <div><strong>Network:</strong> ${networkType}</div>
                        ${(pocName || pocEmail || pocPhone) ? `
                            <div style="margin-top:6px; padding-top:6px; border-top:1px dashed #cbd5e1;">
                                <strong>Point of Contact (POC):</strong> ${pocName || 'N/A'} ${pocEmail ? `(&bull; ${pocEmail})` : ''} ${pocPhone ? `(&bull; ${pocPhone})` : ''}
                            </div>
                        ` : ''}
                    </div>

                    <div style="margin-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" onclick="closeModal()" style="background:#f1f5f9; color:#475569; border:none; padding:10px 18px; border-radius:8px; font-size:13.5px; font-weight:600; cursor:pointer;">
                            Close
                        </button>
                        <a href="javascript:void(0)" onclick="trackAdClick(${campaignId}, '${productName.replace(/'/g, "\\'")}', '${destinationUrl}')" style="background:#0284c7; color:#fff; text-decoration:none; padding:10px 20px; border-radius:8px; font-size:13.5px; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                            Visit Official Product Page &rarr;
                        </a>
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

        document.addEventListener('DOMContentLoaded', () => {
            initViewableImpressionTracker();
        });
    </script>
</body>
</html>
