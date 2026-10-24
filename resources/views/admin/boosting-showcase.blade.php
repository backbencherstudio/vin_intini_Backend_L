<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindUnite — Admin Boosting System Design Showcase & UI Reference</title>
    <!-- Google Fonts & Tailwind CSS CDN -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }
        .mono-font { font-family: 'JetBrains Mono', monospace; }
        .sidebar-brand-gradient {
            background: linear-gradient(135deg, #043940 0%, #008f95 100%);
        }
        .btn-cyan {
            background-color: #00c2cb;
            color: #043940;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-cyan:hover {
            background-color: #00a8b0;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(0, 194, 203, 0.35);
        }
        .badge-cyan {
            background-color: rgba(0, 194, 203, 0.12);
            color: #008f95;
            border: 1px solid rgba(0, 194, 203, 0.3);
        }
        .badge-active {
            background-color: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .badge-inactive {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .glow-hover:hover {
            box-shadow: 0 14px 30px -5px rgba(0, 194, 203, 0.18), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }
        .bento-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .bento-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
            transform: translateY(-2px);
        }
        .hero-plan-card {
            background: linear-gradient(180deg, #ffffff 0%, #f0fdfa 100%);
            border: 2px solid #00c2cb;
            box-shadow: 0 20px 35px -10px rgba(0, 194, 203, 0.22);
        }
        .vip-plan-card {
            background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
            border: 2px solid #fde68a;
            box-shadow: 0 20px 35px -10px rgba(245, 158, 11, 0.16);
        }
        .starter-plan-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04);
        }
        .starter-plan-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 14px 30px -5px rgba(15, 23, 42, 0.08);
            transform: translateY(-2px);
        }
        @keyframes pulse-beacon {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.35); opacity: 0.6; }
        }
        .beacon-dot {
            animation: pulse-beacon 2s infinite ease-in-out;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col custom-scrollbar">

    <!-- Top Floating Designer & Developer Navigation Bar -->
    <nav class="sticky top-0 z-50 bg-[#043940] text-white border-b border-cyan-800/50 shadow-md px-6 py-2.5">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#00c2cb] to-emerald-400 flex items-center justify-center font-extrabold text-[#043940] text-sm shadow">
                    MU
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight flex items-center gap-2">
                        MindUnite <span class="text-xs px-2 py-0.5 rounded-full bg-cyan-950/80 text-[#00c2cb] border border-[#00c2cb]/40 font-mono">Admin Boosting System Showcase</span>
                    </h1>
                    <p class="text-[11px] text-cyan-200/80">Interactive Production Reference for UI/UX Designers & Engineers</p>
                </div>
            </div>

            <!-- View Mode Switcher -->
            <div class="flex items-center gap-1.5 bg-slate-900/60 p-1 rounded-xl border border-cyan-700/40 text-xs font-semibold">
                <button type="button" onclick="switchView('overview')" id="btn-view-overview" class="view-btn px-3 py-1.5 rounded-lg bg-[#00c2cb] text-[#043940] font-bold shadow-sm transition-all">
                    📊 1. Dashboard Overview
                </button>
                <button type="button" onclick="switchView('create')" id="btn-view-create" class="view-btn px-3 py-1.5 rounded-lg text-cyan-100 hover:text-white hover:bg-slate-800 transition-all">
                    ➕ 2. Create / Edit Plan Form
                </button>
                <button type="button" onclick="openSettingsModal()" id="btn-view-modal" class="view-btn px-3 py-1.5 rounded-lg text-cyan-100 hover:text-white hover:bg-slate-800 transition-all">
                    ⚙️ 3. Plan Settings Modal
                </button>
                <button type="button" onclick="openDeleteModal('Growth Boost')" id="btn-view-delete" class="view-btn px-3 py-1.5 rounded-lg text-cyan-100 hover:text-white hover:bg-slate-800 transition-all">
                    🗑️ 4. Delete Dialog
                </button>
                <button type="button" onclick="switchView('audit')" id="btn-view-audit" class="view-btn px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-500/30 transition-all">
                    🔍 5. Figma Audit & Fixes (8 Points)
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Container With Authentic MindUnite Sidebar & Top Header -->
    <div class="flex-1 flex overflow-hidden">

        <!-- ==============================================================
             LEFT SIDEBAR (Authentic MindUnite Admin Shell)
             ============================================================== -->
        <aside class="w-64 bg-[#091e23] border-r border-slate-800/80 flex-shrink-0 flex flex-col justify-between hidden lg:flex">
            <div>
                <!-- Brand Header -->
                <div class="p-6 border-b border-slate-800/80 flex items-center gap-3">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#00c2cb" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                        <path d="M2 12h20"></path>
                    </svg>
                    <span class="text-lg font-extrabold tracking-tight text-white">Mind<span class="text-[#00c2cb]">Unite</span></span>
                </div>

                <!-- Nav Menu -->
                <nav class="p-4 space-y-1 text-xs font-semibold text-slate-400">
                    <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:text-white hover:bg-slate-800/60 transition-colors">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>
                        Dashboard
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:text-white hover:bg-slate-800/60 transition-colors">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        User Management
                    </a>

                    <!-- Active Section: Subscription / Advertising -->
                    <div class="pt-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-1.5">Monetization & Ads</div>
                        <div class="space-y-0.5 mt-1">
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                Overview
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                Plan and Pricing
                            </a>
                            <!-- ACTIVE ITEM -->
                            <a href="javascript:void(0)" onclick="switchView('overview')" class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-[#00c2cb]/15 text-[#00c2cb] border border-[#00c2cb]/30 font-bold shadow-sm">
                                <div class="flex items-center gap-3">
                                    <svg width="16" height="16" fill="none" stroke="#00c2cb" stroke-width="2.2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                                    <span>Boosting Plans</span>
                                </div>
                                <span class="w-2 h-2 rounded-full bg-[#00c2cb]"></span>
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                Transactions
                            </a>
                        </div>
                    </div>

                    <div class="pt-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 py-1.5">Platform Modules</div>
                        <div class="space-y-0.5 mt-1">
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:text-white hover:bg-slate-800/60">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line></svg>
                                Payment Management
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:text-white hover:bg-slate-800/60">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m15.5 17 5 5-5-5Z"></path><path d="m18 14-3.5 3.5"></path><circle cx="9" cy="9" r="7"></circle></svg>
                                Advertise Management
                            </a>
                            <a href="#" class="flex items-center gap-3 px-3 py-2 rounded-xl hover:text-white hover:bg-slate-800/60">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"></path></svg>
                                Academia & Research
                            </a>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Sidebar Footer User Card -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name=Admin+Niaz&background=00c2cb&color=043940&bold=true" class="w-9 h-9 rounded-xl border border-cyan-400/30" alt="Avatar">
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-white truncate">Super Admin</div>
                        <div class="text-[11px] text-slate-400 truncate">admin@mindunite.com</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- ==============================================================
             RIGHT MAIN CONTENT AREA
             ============================================================== -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-6 lg:p-8 bg-slate-50">

            <!-- Breadcrumbs Header -->
            <div class="flex flex-wrap items-center justify-between pb-6 border-b border-slate-200/80 mb-6 gap-3">
                <div>
                    <!-- CORRECTED BREADCRUMB (Fixed Figma Bug: was incorrectly showing "User Management > Basic Users") -->
                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-1">
                        <a href="#" class="hover:text-slate-600">Dashboard</a>
                        <span>/</span>
                        <a href="#" class="hover:text-slate-600">Monetization</a>
                        <span>/</span>
                        <span class="text-[#008f95] font-bold" id="currentBreadcrumbText">Boosting Plans</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight" id="pageTitleText">Boosting Analysis & Management</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-cyan-100 text-[#008f95] border border-cyan-200">Production Ready</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5" id="pageSubtitleText">Monitor live campaign revenue, configure multi-channel boosting tiers, and manage global pacing policies.</p>
                </div>

                <!-- Top Right Global Action Button -->
                <div class="flex items-center gap-3">
                    <button type="button" onclick="openSettingsModal()" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 hover:border-slate-300 text-xs font-bold shadow-sm transition-all flex items-center gap-1.5">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Global Settings</span>
                    </button>
                    <button type="button" onclick="switchView('create')" class="btn-cyan px-4 py-2 rounded-xl text-xs font-bold shadow-md flex items-center gap-2">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>+ Add New Boosting Plan</span>
                    </button>
                </div>
            </div>

            <!-- ==============================================================
                 VIEW 1: DASHBOARD OVERVIEW (Main Screen)
                 ============================================================== -->
            <div id="view-overview" class="space-y-8">

                <!-- 1. KPI Stats Cards Row (Financial & Channel Intelligence) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total Packages & Channels -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Catalog & Channels</span>
                            <div class="w-10 h-10 rounded-xl bg-cyan-50 border border-cyan-100 flex items-center justify-center text-[#008f95]">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight">3 Tiers</span>
                                <span class="text-xs font-bold text-slate-400">+ 1 Custom</span>
                            </div>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 font-medium">Channel Health:</span>
                                <span class="font-bold text-emerald-600 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    100% Operational
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Campaigns Executed -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Campaigns</span>
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight">1,457</span>
                                <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-0.5">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
                                    +14.8% MoM
                                </span>
                            </div>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 font-medium">Preset: <strong class="text-slate-800">1,034</strong></span>
                                <span class="text-slate-400 font-bold">•</span>
                                <span class="text-slate-500 font-medium">Custom: <strong class="text-indigo-600 font-bold">423</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Total Ads Revenue -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Ads Revenue</span>
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight">$80,640.00</span>
                                <span class="text-[11px] font-bold text-emerald-600 flex items-center gap-0.5">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="18 15 12 9 6 15"></polyline></svg>
                                    +28.5%
                                </span>
                            </div>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 font-medium">Presets: <strong class="text-slate-800">$66.4k</strong></span>
                                <span class="text-slate-400 font-bold">•</span>
                                <span class="text-slate-500 font-medium">Slider: <strong class="text-emerald-600 font-bold">$14.2k</strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Pacing & Fair-Play Health -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Delivery Algorithm</span>
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                                <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-extrabold text-slate-900 tracking-tight">Fair-Play</span>
                                <span class="text-xs font-bold text-emerald-600">Active</span>
                            </div>
                            <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-500 font-medium">Pacing Health:</span>
                                <span class="font-bold text-slate-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 beacon-dot"></span>
                                    Zero Feed Stacking
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Global Delivery Rules & Ad Engine Pacing (Bento Grid) -->
                <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/90 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between pb-4 border-b border-slate-100 mb-6 gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-[#043940] text-[#00c2cb] flex items-center justify-center font-bold text-sm shadow-sm">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Global Delivery Rules & Ad Engine Pacing</h3>
                                <p class="text-xs text-slate-500">Autonomous platform constraints controlling insertion frequency, duration range, and vendor grace periods.</p>
                            </div>
                        </div>
                        <button type="button" onclick="openSettingsModal()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition-all flex items-center gap-2 shadow-sm">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            <span>Configure Engine Rules</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Bento 1: Custom Slider Engine -->
                        <div class="bento-card p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <span class="text-xs font-bold text-slate-800">Custom Slider Engine</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Web Checkout</span>
                                </div>

                                <!-- Slider Visual Simulation -->
                                <div class="p-3 rounded-xl bg-slate-50 border border-slate-100 mb-3.5">
                                    <div class="flex justify-between items-center text-xs mb-1.5">
                                        <span class="text-slate-400 font-medium">Daily Rate:</span>
                                        <span class="text-sm font-extrabold text-emerald-600">$3.00 <span class="text-[10px] text-slate-400 font-normal">/ Day</span></span>
                                    </div>
                                    <!-- Interactive Range Visual Track -->
                                    <div class="relative py-2">
                                        <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-emerald-400 to-[#00c2cb] w-3/5 rounded-full"></div>
                                        </div>
                                        <div class="absolute top-1/2 left-[58%] -translate-y-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-white border-2 border-[#008f95] shadow-sm"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 mt-1">
                                        <span>Min: 3 Days</span>
                                        <span class="text-[#008f95]">Selected: 18 Days</span>
                                        <span>Max: 60 Days</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="p-2 rounded-lg bg-indigo-50/70 border border-indigo-100">
                                        <span class="text-[10px] text-indigo-600 font-medium block">Purchases</span>
                                        <span class="font-extrabold text-indigo-900 text-xs">423 Campaigns</span>
                                    </div>
                                    <div class="p-2 rounded-lg bg-emerald-50/70 border border-emerald-100">
                                        <span class="text-[10px] text-emerald-600 font-medium block">Revenue</span>
                                        <span class="font-extrabold text-emerald-900 text-xs">$14,250.00</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100">
                                Autonomous dynamic pricing for arbitrary durations.
                            </p>
                        </div>

                        <!-- Bento 2: Newsfeed Ad Injector -->
                        <div class="bento-card p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-[#00c2cb]"></span>
                                        <span class="text-xs font-bold text-slate-800">Newsfeed Ad Injector</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-100 text-[#008f95] border border-cyan-200">Paced</span>
                                </div>

                                <!-- Stream Sequence Visual Strip -->
                                <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 mb-3.5">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Stream Sequence Pattern</span>
                                    <div class="flex items-center gap-1 text-[10px] font-bold">
                                        <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-500 shadow-2xs">#1-#3</span>
                                        <span class="text-slate-300">➔</span>
                                        <span class="px-1.5 py-0.5 rounded bg-cyan-500 text-white shadow-xs">Slot #4</span>
                                        <span class="text-slate-300">➔</span>
                                        <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-slate-500 shadow-2xs">+8 Posts</span>
                                        <span class="text-slate-300">➔</span>
                                        <span class="px-1.5 py-0.5 rounded bg-cyan-500 text-white shadow-xs">Slot #12</span>
                                    </div>
                                </div>

                                <div class="space-y-1.5 text-xs">
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-400">Stream Density:</span>
                                        <span class="font-bold text-slate-800">Max 2 Ads / Stream</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-slate-400">User Fatigue Limit:</span>
                                        <span class="font-bold text-[#008f95] bg-cyan-50 px-2 py-0.5 rounded-md">5 Impr / Day / User</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100">
                                Fair-play rotation keeps newsfeed organic & spam-free.
                            </p>
                        </div>

                        <!-- Bento 3: Campaign Pause Protection Policy -->
                        <div class="bento-card p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                        <span class="text-xs font-bold text-slate-800">Pause Protection Policy</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200">Freeze Safe</span>
                                </div>

                                <!-- Stepper Progress Lifecycle -->
                                <div class="space-y-1.5 p-2.5 rounded-xl bg-slate-50 border border-slate-100 mb-3.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded-full bg-slate-200 text-slate-700 font-bold text-[9px] flex items-center justify-center">0d</span>
                                            <span class="text-slate-700 font-semibold">Vendor Freezes</span>
                                        </div>
                                        <span class="text-[10px] text-slate-400">Pacing paused</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded-full bg-amber-100 text-amber-800 font-bold text-[9px] flex items-center justify-center">27d</span>
                                            <span class="text-amber-800 font-bold">Email Notice</span>
                                        </div>
                                        <span class="text-[10px] text-amber-600 font-medium">Auto Notice</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded-full bg-rose-100 text-rose-800 font-bold text-[9px] flex items-center justify-center">29d</span>
                                            <span class="text-rose-800 font-bold">Urgent Alert</span>
                                        </div>
                                        <span class="text-[10px] text-rose-600 font-medium">Push & Email</span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/80">
                                        <div class="flex items-center gap-2">
                                            <span class="w-4 h-4 rounded-full bg-slate-800 text-white font-bold text-[9px] flex items-center justify-center">30d</span>
                                            <span class="text-slate-900 font-bold">Auto-Expire</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-slate-600">Slot Recycled</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2 pt-2.5 border-t border-slate-100">
                                Max 30-day freeze prevents hoarding ad inventory.
                            </p>
                        </div>

                        <!-- Bento 4: Free New Release Launchpad -->
                        <div class="bento-card p-5 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                                        <span class="text-xs font-bold text-slate-800">New Release Launchpad</span>
                                    </div>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 border border-purple-200">Zero-Cost</span>
                                </div>

                                <div class="p-3.5 rounded-xl bg-purple-50/60 border border-purple-100/80 text-center mb-3.5">
                                    <div class="text-3xl font-extrabold text-purple-950 tracking-tight flex items-center justify-center gap-1.5">
                                        <span>24</span>
                                        <span class="text-sm font-bold text-purple-700">Hours</span>
                                    </div>
                                    <div class="inline-flex items-center gap-1 mt-1 px-2.5 py-0.5 rounded-full bg-purple-200/60 text-purple-900 text-[10px] font-bold">
                                        🚀 Instant Top-Rank Visibility
                                    </div>
                                </div>

                                <div class="space-y-1.5 text-xs text-slate-600">
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-400">Trigger:</span>
                                        <span class="font-bold text-slate-800">New Product Submission</span>
                                    </div>
                                    <div class="flex justify-between items-center py-1">
                                        <span class="text-slate-400">Cost to Vendor:</span>
                                        <span class="font-bold text-emerald-600">$0.00 (Platform Free)</span>
                                    </div>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-3 pt-2.5 border-t border-slate-100">
                                Ensures every vendor receives discovery velocity on launch.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Boosting Plans Pricing Cards (3 Tiers - World-Class Paywall Design) -->
                <div>
                    <div class="flex flex-wrap items-center justify-between mb-5 gap-3">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 tracking-tight">Active Boosting Plan Tiers</h3>
                            <p class="text-xs text-slate-500">Visual cards presented to vendors during web checkout paywall and native mobile in-app purchases.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px] flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Equal Priority Algorithm (Fair-Play Rotation)
                            </span>
                            <span class="text-xs text-slate-400">Sorted by Priority Weight</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">

                        <!-- CARD 1: Starter Boost (7 Days) -->
                        <div class="starter-plan-card rounded-3xl p-6 flex flex-col justify-between relative transition-all">
                            <!-- Ribbon Badge -->
                            <div class="absolute -top-3 left-6 bg-slate-900 text-slate-100 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm flex items-center gap-1.5">
                                <span>⏱️</span>
                                <span>Starter Tier</span>
                            </div>

                            <div>
                                <!-- Header & Actions -->
                                <div class="flex justify-between items-start mb-3 pt-2">
                                    <div>
                                        <h4 class="text-lg font-extrabold text-slate-900 tracking-tight">Starter Boost</h4>
                                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Essential front-row visibility to jumpstart product discovery.</p>
                                    </div>
                                    <button type="button" onclick="editPlanDemo('Starter Boost', 19.99, 7, 'Starter Tier', '7 Days Active Promotion', '5,000 – 10,000 Impressions', 'active', 'Essential front-row visibility to jumpstart product discovery.')" class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="Edit Plan">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </button>
                                </div>

                                <!-- Price Display & Daily Breakdown -->
                                <div class="flex items-baseline justify-between my-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <div>
                                        <span class="text-3xl font-black text-slate-900">$19.99</span>
                                        <span class="text-xs text-slate-400 font-bold">/ 7 Days</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-extrabold text-slate-700 bg-white px-2.5 py-1 rounded-lg border border-slate-200/80 shadow-2xs block">
                                            $2.85 / day
                                        </span>
                                        <span class="text-[10px] text-slate-400 mt-0.5 block">Standard Daily Rate</span>
                                    </div>
                                </div>

                                <!-- Promotion Highlight Badge & Estimated Impressions -->
                                <div class="space-y-2 mb-4 text-xs">
                                    <div class="py-2.5 px-3 rounded-xl bg-purple-50/80 border border-purple-200/70 text-purple-900 font-bold flex items-center justify-between">
                                        <span class="text-[11px] text-purple-700 flex items-center gap-1.5 font-medium">
                                            <span>⏱️</span> Promotion Highlight:
                                        </span>
                                        <span class="font-extrabold text-purple-900 text-xs">7 Days Active Promotion</span>
                                    </div>
                                    <div class="py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-700 flex items-center justify-between text-[11px]">
                                        <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                            <span>👁️</span> Estimated Reach:
                                        </span>
                                        <span class="font-extrabold text-slate-900 text-xs">5,000 – 10,000 Impressions</span>
                                    </div>
                                </div>

                                <!-- Static Guaranteed Package Features -->
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Guaranteed Features</span>
                                        <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Platform Standard</span>
                                    </div>
                                    <ul class="space-y-3 text-xs text-slate-700">
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Dual-Placement Feed</strong>
                                                <span class="text-slate-500 text-[11px]">Marketplace Feed + Main Social Newsfeed</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Sponsored Featured Badge</strong>
                                                <span class="text-slate-500 text-[11px]">High-contrast official promotion badge</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Fair-Play Rotation</strong>
                                                <span class="text-slate-500 text-[11px]">Equal exposure priority, never algorithmically buried</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Live Real-Time Analytics</strong>
                                                <span class="text-slate-500 text-[11px]">Verified impressions, views, clicks & CTR</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="pt-4 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                <span>Target: <strong class="text-slate-700">Product</strong></span>
                                <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[10px]">Active</span>
                            </div>
                        </div>

                        <!-- CARD 2: Growth Boost (14 Days) — HERO PLAN CARD -->
                        <div class="hero-plan-card rounded-3xl p-6 flex flex-col justify-between relative transition-all transform lg:-translate-y-2">
                            <!-- Hero Ribbon Badge -->
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#043940] text-[#00c2cb] border-2 border-[#00c2cb] text-[10px] font-black px-4 py-1 rounded-full uppercase tracking-wider shadow-md flex items-center gap-1.5">
                                <span>⭐</span>
                                <span>Most Popular Choice</span>
                            </div>

                            <div>
                                <!-- Header & Actions -->
                                <div class="flex justify-between items-start mb-3 pt-2">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-lg font-extrabold text-slate-900 tracking-tight">Growth Boost</h4>
                                            <span class="px-2 py-0.5 rounded-md bg-[#00c2cb]/20 text-[#008f95] text-[10px] font-black">Top Pick</span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Multi-channel promotion across marketplace & main newsfeed.</p>
                                    </div>
                                    <button type="button" onclick="editPlanDemo('Growth Boost', 34.99, 14, 'Most Popular Plan', '14 Days Extended Multi-Channel Reach', '15,000 – 25,000 Impressions', 'active', 'Multi-channel promotion across marketplace & main newsfeed.')" class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="Edit Plan">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </button>
                                </div>

                                <!-- Price Display & Daily Breakdown -->
                                <div class="flex items-baseline justify-between my-4 p-4 rounded-2xl bg-cyan-50/70 border border-cyan-200">
                                    <div>
                                        <span class="text-3xl font-black text-[#008f95]">$34.99</span>
                                        <span class="text-xs text-slate-400 font-bold">/ 14 Days</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-black text-[#008f95] bg-white px-2.5 py-1 rounded-lg border border-cyan-200 shadow-2xs block">
                                            $2.50 / day
                                        </span>
                                        <span class="text-[10px] font-extrabold text-emerald-600 mt-0.5 block">Save 12% vs Starter</span>
                                    </div>
                                </div>

                                <!-- Promotion Highlight Badge & Estimated Impressions -->
                                <div class="space-y-2 mb-4 text-xs">
                                    <div class="py-2.5 px-3 rounded-xl bg-cyan-100/70 border border-cyan-300 text-[#008f95] font-bold flex items-center justify-between">
                                        <span class="text-[11px] text-[#008f95] flex items-center gap-1.5 font-medium">
                                            <span>🚀</span> Promotion Highlight:
                                        </span>
                                        <span class="font-extrabold text-slate-900 text-xs">14 Days Extended Multi-Channel Reach</span>
                                    </div>
                                    <div class="py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-700 flex items-center justify-between text-[11px]">
                                        <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                            <span>👁️</span> Estimated Reach:
                                        </span>
                                        <span class="font-extrabold text-[#008f95] text-xs">15,000 – 25,000 Impressions</span>
                                    </div>
                                </div>

                                <!-- Static Guaranteed Package Features -->
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Guaranteed Features</span>
                                        <span class="text-[10px] font-bold text-[#008f95] bg-cyan-50 px-2 py-0.5 rounded-full border border-cyan-200">Platform Standard</span>
                                    </div>
                                    <ul class="space-y-3 text-xs text-slate-700">
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Dual-Placement Feed</strong>
                                                <span class="text-slate-500 text-[11px]">Marketplace Feed + Main Social Newsfeed</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Sponsored Featured Badge</strong>
                                                <span class="text-slate-500 text-[11px]">High-contrast official promotion badge</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Fair-Play Rotation</strong>
                                                <span class="text-slate-500 text-[11px]">Equal exposure priority, never algorithmically buried</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Live Real-Time Analytics</strong>
                                                <span class="text-slate-500 text-[11px]">Verified impressions, views, clicks & CTR</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="pt-4 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                <span>Target: <strong class="text-slate-700">Product</strong></span>
                                <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[10px]">Active</span>
                            </div>
                        </div>

                        <!-- CARD 3: Best Value Boost (30 Days) — VIP PLAN CARD -->
                        <div class="vip-plan-card rounded-3xl p-6 flex flex-col justify-between relative transition-all">
                            <!-- Ribbon Badge -->
                            <div class="absolute -top-3 left-6 bg-gradient-to-r from-amber-600 to-amber-700 text-white text-[10px] font-extrabold px-3.5 py-1 rounded-full uppercase tracking-wider shadow-sm flex items-center gap-1.5">
                                <span>👑</span>
                                <span>Best Value (Save 18%)</span>
                            </div>

                            <div>
                                <!-- Header & Actions -->
                                <div class="flex justify-between items-start mb-3 pt-2">
                                    <div>
                                        <h4 class="text-lg font-extrabold text-slate-900 tracking-tight">Best Value Boost</h4>
                                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Full-month omnipresent promotion for top vendors & brands.</p>
                                    </div>
                                    <button type="button" onclick="editPlanDemo('Best Value Boost', 69.99, 30, 'Best Value (Save 18%)', '30 Days Full-Month Non-Stop Placement', '40,000 – 60,000+ Impressions', 'active', 'Full-month omnipresent promotion for top vendors & brands.')" class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="Edit Plan">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                    </button>
                                </div>

                                <!-- Price Display & Daily Breakdown -->
                                <div class="flex items-baseline justify-between my-4 p-4 rounded-2xl bg-amber-50/70 border border-amber-200">
                                    <div>
                                        <span class="text-3xl font-black text-slate-900">$69.99</span>
                                        <span class="text-xs text-slate-400 font-bold">/ 30 Days</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-black text-amber-900 bg-white px-2.5 py-1 rounded-lg border border-amber-200 shadow-2xs block">
                                            $2.33 / day
                                        </span>
                                        <span class="text-[10px] font-extrabold text-emerald-600 mt-0.5 block">Save 18% (Best Daily ROI)</span>
                                    </div>
                                </div>

                                <!-- Promotion Highlight Badge & Estimated Impressions -->
                                <div class="space-y-2 mb-4 text-xs">
                                    <div class="py-2.5 px-3 rounded-xl bg-amber-100/70 border border-amber-300 text-amber-950 font-bold flex items-center justify-between">
                                        <span class="text-[11px] text-amber-900 flex items-center gap-1.5 font-medium">
                                            <span>👑</span> Promotion Highlight:
                                        </span>
                                        <span class="font-extrabold text-slate-900 text-xs">30 Days Full-Month Non-Stop</span>
                                    </div>
                                    <div class="py-2.5 px-3 rounded-xl bg-slate-50 border border-slate-200/80 text-slate-700 flex items-center justify-between text-[11px]">
                                        <span class="text-slate-500 font-medium flex items-center gap-1.5">
                                            <span>👁️</span> Estimated Reach:
                                        </span>
                                        <span class="font-extrabold text-amber-900 text-xs">40,000 – 60,000+ Impressions</span>
                                    </div>
                                </div>

                                <!-- Static Guaranteed Package Features -->
                                <div class="pt-4 border-t border-slate-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Guaranteed Features</span>
                                        <span class="text-[10px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">Platform Standard</span>
                                    </div>
                                    <ul class="space-y-3 text-xs text-slate-700">
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Dual-Placement Feed</strong>
                                                <span class="text-slate-500 text-[11px]">Marketplace Feed + Main Social Newsfeed</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Sponsored Featured Badge</strong>
                                                <span class="text-slate-500 text-[11px]">High-contrast official promotion badge</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Fair-Play Rotation</strong>
                                                <span class="text-slate-500 text-[11px]">Equal exposure priority, never algorithmically buried</span>
                                            </div>
                                        </li>
                                        <li class="flex items-start gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5">✓</span>
                                            <div>
                                                <strong class="text-slate-900 block font-bold">Live Real-Time Analytics</strong>
                                                <span class="text-slate-500 text-[11px]">Verified impressions, views, clicks & CTR</span>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="pt-4 mt-6 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                                <span>Target: <strong class="text-slate-700">Product</strong></span>
                                <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[10px]">Active</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 4. Plan List Data Table -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Plan List & Catalog Control</h3>
                            <p class="text-xs text-slate-500">Manage all predefined boosting tiers, live status switches, and RevenueCat linkings.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">Filter:</span>
                            <select class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs text-slate-700 font-semibold focus:outline-none focus:border-cyan-500">
                                <option>All Entities (Product, Job, Pub)</option>
                                <option selected>Products Only</option>
                                <option>Job Posts Only</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-400 uppercase font-bold border-b border-slate-100 text-[10px]">
                                <tr>
                                    <th class="py-3.5 px-6">No.</th>
                                    <th class="py-3.5 px-6">Plan Name</th>
                                    <th class="py-3.5 px-6">Target Entity</th>
                                    <th class="py-3.5 px-6">Description</th>
                                    <th class="py-3.5 px-6">Status</th>
                                    <th class="py-3.5 px-6">Price</th>
                                    <th class="py-3.5 px-6">Active Campaigns</th>
                                    <th class="py-3.5 px-6 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-6 font-mono text-slate-400">01</td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-slate-900">Starter Boost</div>
                                        <span class="text-[10px] text-slate-400">7 Days duration</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] border border-blue-200">Product</span>
                                    </td>
                                    <td class="py-4 px-6 max-w-xs truncate text-slate-500">Get started with essential market visibility.</td>
                                    <td class="py-4 px-6">
                                        <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[11px]">Active</span>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900">$19.99</td>
                                    <td class="py-4 px-6 font-semibold text-slate-800">35,678</td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <button onclick="editPlanDemo('Starter Boost', 19.99, 7, 'Starter Tier', '7 Days Active Promotion', '5,000 – 10,000 Impressions', 'active', 'Get started with essential market visibility.', 'mindunite_starter_7d', 'mindunite_starter_7d', 'boost_starter')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100" title="Edit Plan">
                                            ✏️
                                        </button>
                                        <button onclick="openDeleteModal('Starter Boost')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Delete Plan">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-6 font-mono text-slate-400">02</td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-[#008f95]">Growth Boost</div>
                                        <span class="text-[10px] text-slate-400">14 Days duration</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] border border-blue-200">Product</span>
                                    </td>
                                    <td class="py-4 px-6 max-w-xs truncate text-slate-500">Maximize your reach, discovery, and engagement.</td>
                                    <td class="py-4 px-6">
                                        <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[11px]">Active</span>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900">$34.99</td>
                                    <td class="py-4 px-6 font-semibold text-slate-800">4,923</td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <button onclick="editPlanDemo('Growth Boost', 34.99, 14, 'Most Popular Plan', '14 Days Extended Multi-Channel Reach', '15,000 – 25,000 Impressions', 'active', 'Maximize your reach, discovery, and engagement.', 'mindunite_growth_14d', 'mindunite_growth_14d', 'boost_growth')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100" title="Edit Plan">
                                            ✏️
                                        </button>
                                        <button onclick="openDeleteModal('Growth Boost')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Delete Plan">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="py-4 px-6 font-mono text-slate-400">03</td>
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-amber-700">Best Value Boost</div>
                                        <span class="text-[10px] text-slate-400">30 Days duration</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold text-[10px] border border-blue-200">Product</span>
                                    </td>
                                    <td class="py-4 px-6 max-w-xs truncate text-slate-500">For maximum exposure, lead capture & ROI.</td>
                                    <td class="py-4 px-6">
                                        <span class="badge-active px-2.5 py-0.5 rounded-full font-bold text-[11px]">Active</span>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-900">$69.99</td>
                                    <td class="py-4 px-6 font-semibold text-slate-800">457</td>
                                    <td class="py-4 px-6 text-right space-x-2">
                                        <button onclick="editPlanDemo('Best Value Boost', 69.99, 30, 'Best Value (Save 18%)', '30 Days Full-Month Non-Stop Placement', '40,000 – 60,000+ Impressions', 'active', 'For maximum exposure, lead capture & ROI.', 'mindunite_best_value_30d', 'mindunite_best_value_30d', 'boost_vip')" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-100" title="Edit Plan">
                                            ✏️
                                        </button>
                                        <button onclick="openDeleteModal('Best Value Boost')" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50" title="Delete Plan">
                                            🗑️
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- ==============================================================
                 VIEW 2: CREATE / EDIT PLAN FORM (Corrected Form Screen)
                 ============================================================== -->
            <div id="view-create" class="space-y-6 hidden">
                <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm max-w-4xl mx-auto">
                    <div class="flex items-center justify-between pb-6 border-b border-slate-100 mb-6">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-xl font-extrabold text-slate-900" id="formHeaderTitle">Create New Boosting Plan</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-100 text-[#008f95]">Admin Portal</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Configure plan parameters, pricing, duration, features, and optional mobile in-app purchase credentials.</p>
                        </div>
                        <button type="button" onclick="switchView('overview')" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100">
                            ← Back to Plans
                        </button>
                    </div>

                    <!-- FORM BODY -->
                    <form onsubmit="handleFormSubmit(event)" class="space-y-6 text-xs">

                        <!-- Row 1: Plan Name & Target Entity (CORRECTED FIGMA BUG: was labeled "Name targeted entity *") -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Boosting Plan Name <span class="text-rose-500">*</span></label>
                                <input type="text" id="inputPlanName" required placeholder="e.g. Growth Boost" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-semibold text-xs outline-none transition-all">
                                <span class="text-[11px] text-slate-400 mt-1 block">Displayed on checkout paywall cards.</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Target Entity <span class="text-rose-500">*</span> <span class="text-[10px] font-semibold text-emerald-600">(Corrected label)</span></label>
                                <select id="inputTargetEntity" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-800 font-semibold text-xs outline-none transition-all bg-white">
                                    <option value="product" selected>Industry Products (Marketplace Feed)</option>
                                    <option value="job">Industry Job Posts (Career Hub)</option>
                                    <option value="publication">Research Publications & Clinical Papers</option>
                                    <option value="post">Newsfeed Community Posts</option>
                                    <option value="all">Universal (All Entities)</option>
                                </select>
                                <span class="text-[11px] text-slate-400 mt-1 block">Specifies which marketplace/feed this plan can boost.</span>
                            </div>
                        </div>

                        <!-- Row 2: Duration Days & Price -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Duration (Days) <span class="text-rose-500">*</span></label>
                                <input type="number" id="inputDuration" required min="1" max="365" placeholder="e.g. 14" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-semibold text-xs outline-none transition-all">
                                <span class="text-[11px] text-slate-400 mt-1 block">Boost duration clock starts after successful payment.</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Price ($ USD) <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-2.5 text-slate-400 font-bold">$</span>
                                    <input type="number" id="inputPrice" required step="0.01" min="0" placeholder="34.99" class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-bold text-xs outline-none transition-all">
                                </div>
                                <span class="text-[11px] text-slate-400 mt-1 block">Stripe card charge and checkout price.</span>
                            </div>
                        </div>

                        <!-- Row 3: Package Description (CORRECTED CRITICAL FIGMA BUG: was labeled "Job Description *") -->
                        <div class="p-4 rounded-2xl bg-amber-50/50 border border-amber-200/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="font-bold text-slate-800">Package Description <span class="text-rose-500">*</span></label>
                                <span class="text-[10px] font-extrabold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full uppercase">Fixed: Figma had "Job Description"</span>
                            </div>
                            <textarea id="inputDescription" rows="3" required placeholder="e.g. Maximize your reach, discovery, and engagement across the Psychology and Biotechnology network..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 text-xs outline-none transition-all bg-white"></textarea>
                            <span class="text-[11px] text-slate-500 mt-1 block">Clear promotional summary shown to users on paywall cards.</span>
                        </div>

                        <!-- Row 4: Badge Ribbon (Optional) & Promotion Duration Highlight (Badge) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Badge Ribbon (Optional)</label>
                                <input type="text" id="inputBadge" placeholder="e.g. Most Popular Plan" value="Most Popular Plan" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-semibold text-xs outline-none transition-all">
                                <span class="text-[11px] text-slate-400 mt-1 block">Displays highlighted badge pill above card (e.g. Most Popular Plan, Best Value).</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Promotion Duration Highlight (Badge)</label>
                                <input type="text" id="inputPromotionHighlight" placeholder="e.g. 14 Days Extended Multi-Channel Reach" value="14 Days Extended Multi-Channel Reach" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-semibold text-xs outline-none transition-all">
                                <span class="text-[11px] text-slate-400 mt-1 block">Highlighted pill shown inside card for duration & placement.</span>
                            </div>
                        </div>

                        <!-- Row 5: Estimated Impression & Status -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Estimated Impression</label>
                                <input type="text" id="inputEstimatedImp" placeholder="e.g. 15,000 – 25,000 Impressions" value="15,000 – 25,000 Impressions" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-900 font-semibold text-xs outline-none transition-all">
                                <span class="text-[11px] text-slate-400 mt-1 block">Estimated campaign views/impressions displayed on card.</span>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 mb-1.5">Status <span class="text-rose-500">*</span></label>
                                <select id="inputStatus" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-[#00c2cb] focus:ring-2 focus:ring-[#00c2cb]/20 text-slate-800 font-bold text-xs outline-none transition-all bg-white">
                                    <option value="active" selected>Active (Available for purchase)</option>
                                    <option value="inactive">Inactive (Hidden from checkout)</option>
                                </select>
                                <span class="text-[11px] text-slate-400 mt-1 block">Controls visibility in checkout paywall dialog.</span>
                            </div>
                        </div>

                        <!-- Row 6: STATIC PLATFORM PACKAGE FEATURES (Informational / Read-Only) -->
                        <div class="p-4 rounded-2xl bg-cyan-50/60 border border-cyan-200/80">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-slate-800 flex items-center gap-2">
                                    <span class="text-base text-[#008f95]">✨</span>
                                    <span>Package Features (Static & Guaranteed for All Plans)</span>
                                </span>
                                <span class="text-[10px] font-bold text-[#008f95] bg-cyan-100 px-2.5 py-0.5 rounded-full border border-cyan-200">Automatic / No Admin Input Needed</span>
                            </div>
                            <p class="text-[11px] text-slate-600 mb-3 leading-relaxed">
                                Because all boosting plans receive equal fair-play exposure across our network, these 4 core capabilities are statically embedded into every plan. Admins do not need to configure features manually:
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs font-semibold text-slate-800">
                                <div class="p-2.5 rounded-xl bg-white border border-cyan-100 shadow-2xs">
                                    <div class="flex items-center gap-2 text-slate-900 font-bold">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0">✓</span>
                                        <span>1. Dual Placement (Feed + Newsfeed)</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-normal mt-1 ml-7">Promoted in both marketplace product feed and main community newsfeed slot #4.</p>
                                </div>
                                <div class="p-2.5 rounded-xl bg-white border border-cyan-100 shadow-2xs">
                                    <div class="flex items-center gap-2 text-slate-900 font-bold">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0">✓</span>
                                        <span>2. Featured 'Sponsored' Badge</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-normal mt-1 ml-7">Cyan highlight ribbon on product card builds trust and attracts instant clicks.</p>
                                </div>
                                <div class="p-2.5 rounded-xl bg-white border border-cyan-100 shadow-2xs">
                                    <div class="flex items-center gap-2 text-slate-900 font-bold">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0">✓</span>
                                        <span>3. Fair-Play Exposure Rotation</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-normal mt-1 ml-7">Impression-balancing round-robin ensures ads get equal views and never stay buried.</p>
                                </div>
                                <div class="p-2.5 rounded-xl bg-white border border-cyan-100 shadow-2xs">
                                    <div class="flex items-center gap-2 text-slate-900 font-bold">
                                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs shrink-0">✓</span>
                                        <span>4. Live Verified Analytics</span>
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-normal mt-1 ml-7">Real-time tracking of actual user views, direct clicks, and buyer inquiries.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Row 7: Mobile In-App Purchase Accordion (RevenueCat Credentials) -->
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4">
                            <button type="button" onclick="toggleIapSection()" class="w-full flex items-center justify-between text-left font-bold text-slate-800 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">📱</span>
                                    <span>Mobile In-App Purchase (RevenueCat Credentials)</span>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">iOS & Android</span>
                                </div>
                                <span id="iapToggleIcon" class="text-slate-400">▼</span>
                            </button>
                            <div id="iapFieldsSection" class="mt-4 pt-4 border-t border-slate-200 space-y-4">
                                <p class="text-[11px] text-slate-500">Required for mobile apps to unlock App Store & Google Play in-app billing for this package.</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block font-semibold text-slate-600 mb-1">iOS Store ID</label>
                                        <input type="text" id="inputIosStoreId" placeholder="mindunite_growth_14d" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-600 mb-1">Android Store ID</label>
                                        <input type="text" id="inputAndroidStoreId" placeholder="mindunite_growth_14d" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono bg-white">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-slate-600 mb-1">Entitlement ID</label>
                                        <input type="text" id="inputEntitlementId" placeholder="boost_growth" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono bg-white">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Footer -->
                        <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                            <button type="button" onclick="switchView('overview')" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all">
                                Cancel
                            </button>
                            <button type="button" onclick="alert('Draft saved successfully!')" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 font-bold hover:bg-slate-200 transition-all">
                                Save as Draft
                            </button>
                            <button type="submit" class="btn-cyan px-6 py-2.5 rounded-xl font-bold shadow-md">
                                Save Boosting Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==============================================================
                 VIEW 5: FIGMA AUDIT & FIXES CHECKLIST
                 ============================================================== -->
            <div id="view-audit" class="space-y-6 hidden">
                <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm max-w-5xl mx-auto">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                        <div>
                            <h3 class="text-xl font-extrabold text-slate-900">Figma Design Review & Architectural Audit</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Summary of all 9 architectural inconsistencies & false-advertising risks found in Figma with exact developer/designer fixes.</p>
                        </div>
                        <button type="button" onclick="switchView('overview')" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-100">
                            ← Back to Dashboard
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Point 1 -->
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-rose-100 text-rose-700 font-bold text-sm">1</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-rose-900 text-sm">Critical Typo: "Job Description *" in Create Plan Form</h4>
                                <p class="text-xs text-rose-700 mt-1">In Figma (Screen 2), the boosting plan description is labeled <strong>"Job Description *"</strong> with placeholder <em>"Describe the role, responsibilities, and requirements..."</em>. This was accidentally copy-pasted from an employment job post form.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Change label to <strong>"Package Description *"</strong> with placeholder <em>"Briefly describe the reach and benefits of this boosting package..."</em>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 2 -->
                        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-amber-100 text-amber-700 font-bold text-sm">2</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-amber-900 text-sm">Pricing Logic Error: "Basic Free" vs "$19.99 / 7 Days"</h4>
                                <p class="text-xs text-amber-700 mt-1">In Figma (Screen 1), Card 1 is titled <strong>"Basic Free"</strong>, but right underneath charges <strong>$19.99</strong>. A paid tier charging $19.99 cannot be named "Free".</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Rename Card 1 to <strong>"Starter Boost"</strong> or <strong>"Basic Boost"</strong>. (The only free boost is the 24h New Release promotion).
                                </div>
                            </div>
                        </div>

                        <!-- Point 3 -->
                        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-rose-100 text-rose-700 font-bold text-sm">3</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-rose-900 text-sm">Vocabulary Typo: "Push Policy" instead of "Pause Policy"</h4>
                                <p class="text-xs text-rose-700 mt-1">In Figma (Screens 1 & 3), the section is labeled <strong>"Push Policy"</strong> with fields *Max Days: 30, First Reminder: 27, Last Reminder: 29*.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: In our database and system blueprint, this is the <strong>"Campaign Pause Policy"</strong> (when an advertiser freezes their ad, auto-cancellation triggers after 30 days). Correct label to <strong>"Pause Policy"</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 4 -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-sm">4</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-900 text-sm">Grammar Error: "Name targeted entity *"</h4>
                                <p class="text-xs text-slate-600 mt-1">In Figma (Screen 2), the entity dropdown is labeled <strong>"Name targeted entity *"</strong> which is broken English.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Change label to <strong>"Target Entity *"</strong> with options: <em>Products, Job Posts, Publications, Community Posts</em>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 5 -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-sm">5</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-900 text-sm">Irrelevant Client Checkbox in Admin Dashboard</h4>
                                <p class="text-xs text-slate-600 mt-1">Screen 2 includes: <em>"I confirm that all information provided is accurate and that I am authorized to publish this plan on behalf of my organization"</em>. A platform Super Admin does not need an external organization authorization waiver to configure platform plans.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Remove this client waiver and replace with <strong>Status (Active / Inactive)</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 6 -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-sm">6</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-900 text-sm">Breadcrumb Route Mismatch</h4>
                                <p class="text-xs text-slate-600 mt-1">Top breadcrumb says <strong>"Dashboard > User Management > Basic Users"</strong>, but the sidebar highlighted item is <strong>Subscription > Boosting Plan</strong>.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Breadcrumb should correctly read <strong>"Dashboard > Monetization > Boosting Plans"</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 7 -->
                        <div class="p-4 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-cyan-100 text-[#008f95] font-bold text-sm">7</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-cyan-950 text-sm">Missing Mobile In-App Purchase Fields (RevenueCat)</h4>
                                <p class="text-xs text-cyan-800 mt-1">MindUnite mobile apps (iOS & Android) require Apple Store and Google Play IAP Product IDs to sell boost packages inside mobile apps. Figma was missing these fields completely.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Add collapsible <strong>"Mobile App Store / RevenueCat IAP (Optional)"</strong> section with iOS Store ID, Android Store ID, and Entitlement ID.
                                </div>
                            </div>
                        </div>

                        <!-- Point 8 -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-slate-200 text-slate-700 font-bold text-sm">8</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-900 text-sm">Priority Weight Meaning Clarity</h4>
                                <p class="text-xs text-slate-600 mt-1">Admin needs clear guidance on what "Priority Weight" does. In our fair-play platform, it controls the <strong>sorting order in the pricing table</strong> (which plan is featured first to vendors).</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Add helpful label tooltip: <em>"Used for pricing table sorting order (1 = Standard, 2 = Growth, 3 = VIP)"</em>.
                                </div>
                            </div>
                        </div>

                        <!-- Point 9: Anti-Misleading Advertising & Real-Value Standard -->
                        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-emerald-100 text-emerald-800 font-bold text-sm">9</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-emerald-950 text-sm">Zero Negativity Standard: Elimination of Fake Impression Guarantees & Phantom Services</h4>
                                <p class="text-xs text-emerald-800 mt-1">Figma contained unfulfillable promises like <strong>"Up to 15,000 / 100,000 guaranteed impressions"</strong>, <strong>"Dedicated account manager support"</strong>, and <strong>"Location targeting"</strong> for $19.99–$69.99 self-serve boost packages. When real marketplace visitor volume fluctuates or when automated systems cannot provide 1-on-1 human managers, advertisers file chargebacks and leave toxic reviews.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-900 bg-white/90 p-2.5 rounded-xl border border-emerald-200">
                                    ✓ Fix: Strictly enforce MindUnite's <strong>100% Deliverable Services Standard</strong>:
                                    <ul class="list-disc list-inside mt-1 space-y-0.5 text-slate-700 font-normal">
                                        <li><strong>Guaranteed Duration:</strong> 7, 14, 30 continuous active days of marketplace top placement.</li>
                                        <li><strong>'Sponsored' Badge Ribbon:</strong> Highlighting cards with dedicated verified badge styling.</li>
                                        <li><strong>Fair-Play Exposure Rotation:</strong> Impression equalization guarantees every boosted product gets front-row rotation and never stays buried.</li>
                                        <li><strong>Multi-Channel Reach:</strong> Dual placement across Marketplace Feed and Network Newsfeed stream.</li>
                                        <li><strong>Real-Time Analytics:</strong> Live verified view impressions and real click-through reporting.</li>
                                        <li><strong>Direct Buyer Inquiries:</strong> Direct messaging channel between interested buyers and vendors.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Point 10: Elimination of Pointless "Package Features" Input in Boosting -->
                        <div class="p-4 rounded-2xl bg-cyan-50 border border-cyan-200 flex items-start gap-3">
                            <span class="p-2 rounded-xl bg-cyan-100 text-[#008f95] font-bold text-sm">10</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-cyan-950 text-sm">Elimination of Useless "Package Features" Input in Package Creation</h4>
                                <p class="text-xs text-cyan-800 mt-1">In Figma (Screen 2), an arbitrary freeform "Package Features" chip input was included. Unlike subscription membership tiers where user permissions vary, product boosting is a duration-based exposure service. Every single boosted product on MindUnite automatically receives all platform capabilities (Continuous Top Placement, Fair-Play Rotation, Sponsored Badge, and Live Analytics). Forcing an admin to manually type or check arbitrary features creates confusion, maintenance overhead, and false expectations.</p>
                                <div class="mt-2 text-xs font-semibold text-emerald-800 bg-emerald-100/70 p-2 rounded-xl inline-block">
                                    ✓ Fix: Completely removed "Package Features" from the plan creation form. The admin only manages real, concrete parameters: <strong>Plan Name, Duration (Days), Price ($), Badge Ribbon, Description, and RevenueCat Mobile IAP Identifiers</strong>.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ==============================================================
         MODAL 1: PLAN OVERVIEW / SETTINGS MODAL (INTERACTIVE)
         ============================================================== -->
    <div id="modalSettings" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-xl w-full p-6 sm:p-8 max-h-[90vh] overflow-y-auto custom-scrollbar">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-cyan-100 text-[#008f95] flex items-center justify-center font-bold">⚙️</div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Global Boosting Settings</h3>
                        <p class="text-xs text-slate-400">Updates <span class="font-mono text-[#008f95]">PATCH /api/admin/boost-settings</span></p>
                    </div>
                </div>
                <button type="button" onclick="closeSettingsModal()" class="text-slate-400 hover:text-slate-700 text-lg font-bold">✕</button>
            </div>

            <form onsubmit="handleSettingsSave(event)" class="space-y-5 text-xs">
                <!-- 1. Custom Plan Slider -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800">1. Custom Duration Slider</span>
                        <input type="checkbox" checked class="w-4 h-4 accent-[#00c2cb]">
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-500 mb-1">Daily Rate ($)</label>
                            <input type="number" step="0.01" value="3.00" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 font-bold bg-white">
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Min Days</label>
                            <input type="number" value="3" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Max Days</label>
                            <input type="number" value="60" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                    </div>
                </div>

                <!-- 2. Newsfeed Ad Injector -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-800">2. Newsfeed Ad Injection & Fatigue</span>
                        <input type="checkbox" checked class="w-4 h-4 accent-[#00c2cb]">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-500 mb-1">First Ad Slot</label>
                            <input type="number" value="4" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-slate-500 mb-1">Ad Interval (Posts)</label>
                            <input type="number" value="8" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-500 mb-1">Max Daily Impressions / User (Anti-Fatigue)</label>
                        <input type="number" value="5" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 font-bold text-[#008f95] bg-white">
                    </div>
                </div>

                <!-- 3. Campaign Pause Policy (CORRECTED) -->
                <div class="p-4 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-amber-900 flex items-center gap-1.5">
                            <span>3. Campaign Pause Policy</span>
                            <span class="text-[9px] font-bold text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">Fixed</span>
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-slate-600 mb-1">Max Days</label>
                            <input type="number" value="30" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 font-bold bg-white">
                        </div>
                        <div>
                            <label class="block text-slate-600 mb-1">1st Reminder</label>
                            <input type="number" value="27" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                        <div>
                            <label class="block text-slate-600 mb-1">Last Reminder</label>
                            <input type="number" value="29" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 bg-white">
                        </div>
                    </div>
                </div>

                <!-- 4. Free New Release Promotion -->
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                    <label class="block font-bold text-slate-800">4. Free New Release Promotion Window</label>
                    <div class="flex items-center gap-3">
                        <div class="relative w-32">
                            <input type="number" value="24" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-slate-900 font-bold bg-white">
                            <span class="absolute right-3 top-1.5 text-slate-400 font-semibold">Hours</span>
                        </div>
                        <p class="text-[11px] text-slate-500">Newly added products automatically get top placement & New Release badge.</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeSettingsModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="btn-cyan px-5 py-2 rounded-xl font-bold shadow-md">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ==============================================================
         MODAL 2: DELETE CONFIRMATION MODAL (INTERACTIVE)
         ============================================================== -->
    <div id="modalDelete" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl max-w-md w-full p-6 text-center">
            <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-500 border border-rose-100 flex items-center justify-center text-2xl mx-auto mb-4">
                ⚠️
            </div>
            <h3 class="text-base font-bold text-slate-900 mb-1">Delete Boosting Plan?</h3>
            <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                Are you sure you want to delete <strong id="deletePlanNameTarget" class="text-slate-800">Growth Boost</strong>? Active campaigns currently using this package cannot be deleted until expired.
            </p>
            <div class="flex items-center justify-center gap-3 text-xs">
                <button type="button" onclick="closeDeleteModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 font-bold text-slate-600 hover:bg-slate-100 transition-all">
                    No, Don't Delete
                </button>
                <button type="button" onclick="confirmDeleteAction()" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-md transition-all">
                    Yes, Delete Plan
                </button>
            </div>
        </div>
    </div>

    <!-- ==============================================================
         JAVASCRIPT CONTROLLERS FOR INTERACTIVITY
         ============================================================== -->
    <script>
        function switchView(viewName) {
            const overviewEl = document.getElementById('view-overview');
            const createEl = document.getElementById('view-create');
            const auditEl = document.getElementById('view-audit');

            overviewEl.classList.add('hidden');
            createEl.classList.add('hidden');
            auditEl.classList.add('hidden');

            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('bg-[#00c2cb]', 'text-[#043940]', 'font-bold');
                btn.classList.add('text-cyan-100');
            });

            const activeBtn = document.getElementById('btn-view-' + viewName);
            if (activeBtn) {
                activeBtn.classList.remove('text-cyan-100');
                activeBtn.classList.add('bg-[#00c2cb]', 'text-[#043940]', 'font-bold');
            }

            if (viewName === 'overview') {
                overviewEl.classList.remove('hidden');
                document.getElementById('currentBreadcrumbText').innerText = 'Boosting Plans';
                document.getElementById('pageTitleText').innerText = 'Boosting Analysis & Management';
                document.getElementById('pageSubtitleText').innerText = 'Monitor live campaign revenue, configure multi-channel boosting tiers, and manage global pacing policies.';
            } else if (viewName === 'create') {
                createEl.classList.remove('hidden');
                document.getElementById('currentBreadcrumbText').innerText = 'Create Plan';
                document.getElementById('pageTitleText').innerText = 'Create / Edit Boosting Plan';
                document.getElementById('pageSubtitleText').innerText = 'Define plan parameters, pricing, duration, features, and RevenueCat mobile identifiers.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else if (viewName === 'audit') {
                auditEl.classList.remove('hidden');
                document.getElementById('currentBreadcrumbText').innerText = 'Figma Audit';
                document.getElementById('pageTitleText').innerText = 'Figma Design Audit & Fixes';
                document.getElementById('pageSubtitleText').innerText = 'Complete analysis of all 9 architectural inconsistencies & false-advertising risks in Figma and how they are resolved.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function editPlanDemo(name, price, days, badge, highlight, impressions, status, desc, iosId, androidId, entId) {
            switchView('create');
            document.getElementById('formHeaderTitle').innerText = 'Edit Boosting Plan: ' + name;
            document.getElementById('inputPlanName').value = name;
            document.getElementById('inputPrice').value = price;
            document.getElementById('inputDuration').value = days;
            document.getElementById('inputDescription').value = desc || ('Elevate visibility and reach across the psychology and biotechnology network for ' + days + ' continuous days.');

            const badgeInput = document.getElementById('inputBadge');
            if (badgeInput) {
                badgeInput.value = badge || (days === 7 ? 'Starter Tier' : (days === 14 ? 'Most Popular Plan' : 'Best Value (Save 18%)'));
            }

            const highlightInput = document.getElementById('inputPromotionHighlight');
            if (highlightInput) {
                highlightInput.value = highlight || (days === 7 ? '7 Days Active Promotion' : (days === 14 ? '14 Days Extended Multi-Channel Reach' : '30 Days Full-Month Non-Stop Placement'));
            }

            const impInput = document.getElementById('inputEstimatedImp');
            if (impInput) {
                impInput.value = impressions || (days === 7 ? '5,000 – 10,000 Impressions' : (days === 14 ? '15,000 – 25,000 Impressions' : '40,000 – 60,000+ Impressions'));
            }

            const statusInput = document.getElementById('inputStatus');
            if (statusInput) {
                statusInput.value = status || 'active';
            }

            const iosInput = document.getElementById('inputIosStoreId');
            if (iosInput) {
                iosInput.value = iosId || ('mindunite_boost_' + days + 'd');
            }

            const androidInput = document.getElementById('inputAndroidStoreId');
            if (androidInput) {
                androidInput.value = androidId || ('mindunite_boost_' + days + 'd');
            }

            const entInput = document.getElementById('inputEntitlementId');
            if (entInput) {
                entInput.value = entId || 'boost_general';
            }
        }

        function toggleIapSection() {
            const section = document.getElementById('iapFieldsSection');
            const icon = document.getElementById('iapToggleIcon');
            section.classList.toggle('hidden');
            icon.innerText = section.classList.contains('hidden') ? '▼' : '▲';
        }

        function openSettingsModal() {
            document.getElementById('modalSettings').classList.remove('hidden');
        }

        function closeSettingsModal() {
            document.getElementById('modalSettings').classList.add('hidden');
        }

        function handleSettingsSave(e) {
            e.preventDefault();
            alert('Settings successfully updated! Dispatched PATCH /api/admin/boost-settings.');
            closeSettingsModal();
        }

        function openDeleteModal(planName) {
            document.getElementById('deletePlanNameTarget').innerText = planName;
            document.getElementById('modalDelete').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('modalDelete').classList.add('hidden');
        }

        function confirmDeleteAction() {
            alert('Boosting plan deleted successfully!');
            closeDeleteModal();
        }

        function handleFormSubmit(e) {
            e.preventDefault();

            const name = document.getElementById('inputPlanName')?.value || 'New Boosting Plan';
            const duration = parseInt(document.getElementById('inputDuration')?.value || '7', 10);
            const price = parseFloat(document.getElementById('inputPrice')?.value || '19.99');
            const badge = document.getElementById('inputBadge')?.value || 'None';
            const highlight = document.getElementById('inputPromotionHighlight')?.value || '';
            const impressions = document.getElementById('inputEstimatedImp')?.value || '';

            alert(`✅ Boosting Package "${name}" Saved Successfully!\n\n• Duration: ${duration} Days\n• Price: $${price.toFixed(2)}\n• Badge Ribbon: ${badge}\n• Promotion Highlight: ${highlight}\n• Estimated Impressions: ${impressions}\n\nPlatform Static Features (Guaranteed for All Plans):\n1. Marketplace Feed + Main Newsfeed\n2. Sponsored Badge\n3. Fair-Play\n4. Live Analytics`);
            switchView('overview');
        }
    </script>
</body>
</html>
