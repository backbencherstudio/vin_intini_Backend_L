<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindUnite | Admin Control</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-active: #0ea5e9;
            --sidebar-text: #94a3b8;
            --main-bg: #f1f5f9;
            --sidebar-width: 270px;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* --- Sidebar - No Animation --- */
        .sidebar {
            height: 100vh;
            width: var(--sidebar-width);
            position: fixed;
            top: 0;
            left: 0;
            background-color: var(--sidebar-bg);
            z-index: 1050;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            transition: none !important;
            /* Disable Sidebar Animation */
        }

        .brand-section {
            padding: 20px 25px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.3rem;
            color: #fff !important;
            text-decoration: none;
        }

        .nav-section-label {
            color: #475569;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 25px 25px 10px;
        }

        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 10px 20px;
            font-weight: 500;
            margin: 2px 15px;
            border-radius: 6px;
            font-size: 0.88rem;
            display: flex;
            align-items: center;
            transition: none !important;
            /* Disable Hover Animation */
        }

        .sidebar .nav-link i {
            width: 22px;
            font-size: 1.05rem;
            margin-right: 12px;
            opacity: 0.7;
        }

        .sidebar .nav-link:hover {
            background-color: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background-color: var(--sidebar-active);
            color: #fff;
        }

        /* --- Disable Bootstrap Collapse Animation --- */
        .collapsing {
            transition: none !important;
            display: none !important;
            /* Prevent jerky movement */
        }

        .collapse.show {
            display: block !important;
        }

        .submenu-icon {
            font-size: 0.6rem;
            margin-left: auto;
            transition: none !important;
        }

        .collapse.submenu .nav-link {
            padding-left: 48px;
            font-size: 0.82rem;
        }

        /* --- Fixed Top Header --- */
        .fixed-header {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 40px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        /* --- Main Content Area --- */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 100px 40px 40px;
            /* Offset for fixed header */
        }

        /* Logout Section */
        .logout-section {
            margin-top: auto;
            padding: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .btn-logout {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.05);
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: none !important;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #fff;
        }

        @media (max-width: 992px) {
            :root {
                --sidebar-width: 80px;
            }

            .sidebar span,
            .sidebar .submenu-icon,
            .sidebar .nav-section-label,
            .admin-meta {
                display: none;
            }

            .fixed-header {
                left: 80px;
                padding: 0 20px;
            }

            .main-content {
                margin-left: 80px;
                padding: 90px 20px 20px;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar shadow-sm">
        <div class="brand-section">
            <a class="navbar-brand d-flex align-items-center" href="#">
                {{-- <i class="fa-solid fa-brain me-2 text-info"></i><span>MindUnite</span> --}}
                <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <nav class="nav flex-column mb-4">

            <div class="nav-section-label">User Management</div>
            <div class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}"
                    href="{{ route('admin.user.management') }}">
                    <i class="fas fa-users"></i> <span>Users</span>
                </a>
            </div>


            <div class="nav-section-label">Educational</div>
            <div class="nav-item">
                @php $isAcademiaActive = request()->is('*universities*') || request()->is('*residencies*') || request()->is('*facilities*') || request()->is('*jobs*'); @endphp
                <a class="nav-link d-flex align-items-center {{ $isAcademiaActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#academiaMenu">
                    <i class="fa-solid fa-graduation-cap"></i> <span>Academia</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ $isAcademiaActive ? 'show' : '' }}" id="academiaMenu">
                    <a class="nav-link {{ request()->is('*universities*') ? 'active' : '' }}"
                        href="{{ route('admin.universities.index') }}">
                        <i class="fa-solid fa-building-columns"></i> <span>Universities</span>
                    </a>
                    <a class="nav-link {{ request()->is('*residencies*') ? 'active' : '' }}"
                        href="{{ route('admin.residencies.index') }}">
                        <i class="fa-solid fa-user-doctor"></i> <span>Residencies</span>
                    </a>
                    <a class="nav-link {{ request()->is('*facilities*') ? 'active' : '' }}"
                        href="{{ route('admin.facilities.index') }}">
                        <i class="fa-solid fa-hospital"></i> <span>Facilities</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('*jobs*') ? 'active' : '' }}"
                        href="{{ route('admin.jobs.index') }}">
                        <i class="fa-solid fa-briefcase"></i> <span>Employment</span>
                    </a>
                </div>
            </div>

            <div class="nav-section-label">Industries Networks</div>

            <div class="nav-item">
                <a class="nav-link d-flex align-items-center {{ request()->is('admin/categories*') ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#categorySubMenu">
                    <i class="fa-solid fa-table-list"></i> <span>Categories</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ request()->is('admin/categories*') ? 'show' : '' }}"
                    id="categorySubMenu">
                    <a class="nav-link {{ request()->routeIs('admin.categories.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.categories.psychology') }}">
                        <i class="fa-solid fa-brain"></i> <span>Psychology</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.categories.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.categories.neuroscience') }}">
                        <i class="fa-solid fa-microscope"></i> <span>Neuroscience</span>
                    </a>
                </div>
            </div>

            <div class="nav-item">
                @php $isPsychActive = request()->is('admin/biotech/psychology*') || request()->is('admin/pharma/psychology*') || request()->is('admin/publications/psychology*'); @endphp
                <a class="nav-link d-flex align-items-center {{ $isPsychActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#psychologyGroupMenu">
                    <i class="fa-solid fa-head-side-virus"></i> <span>Psychology</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ $isPsychActive ? 'show' : '' }}" id="psychologyGroupMenu">
                    <a class="nav-link {{ request()->routeIs('admin.biotech.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.biotech.psychology') }}">
                        <i class="fa-solid fa-seedling"></i> <span>Biotechnology</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.pharma.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.pharma.psychology') }}">
                        <i class="fa-solid fa-capsules"></i> <span>Psychotropics</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.publications.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.publications.psychology') }}">
                        <i class="fa-solid fa-newspaper"></i> <span>Publications</span>
                    </a>
                </div>
            </div>

            <div class="nav-item">
                @php $isNeuroActive = request()->is('admin/biotech/neuroscience*') || request()->is('admin/pharma/neuroscience*') || request()->is('admin/publications/neuroscience*'); @endphp
                <a class="nav-link d-flex align-items-center {{ $isNeuroActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#neuroscienceGroupMenu">
                    <i class="fa-solid fa-dna"></i> <span>Neuroscience</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ $isNeuroActive ? 'show' : '' }}" id="neuroscienceGroupMenu">
                    <a class="nav-link {{ request()->routeIs('admin.biotech.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.biotech.neuroscience') }}">
                        <i class="fa-solid fa-seedling"></i> <span>Biotechnology</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.pharma.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.pharma.neuroscience') }}">
                        <i class="fa-solid fa-capsules"></i> <span>Psychotropics</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.publications.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.publications.neuroscience') }}">
                        <i class="fa-solid fa-newspaper"></i> <span>Publications</span>
                    </a>
                </div>
            </div>
            <a class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}"
                href="{{ route('admin.partners.index') }}">
                <i class="fa-solid fa-handshake-angle"></i> <span>Partners</span>
            </a>


            <div class="nav-section-label">Page Settings</div>

            <div class="nav-item">
                @php
                    $isPageActive = request()->is('admin/pages/*');
                @endphp

                <a class="nav-link d-flex align-items-center {{ $isPageActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#pagesMenu"
                    aria-expanded="{{ $isPageActive ? 'true' : 'false' }}">
                    <i class="fa-solid fa-book"></i> <span>Pages</span>
                    <i class="fa-solid fa-chevron-down submenu-icon ms-auto"></i>
                </a>

                <div class="collapse submenu {{ $isPageActive ? 'show' : '' }}" id="pagesMenu">

                    <!-- About Us -->
                    <a class="nav-link {{ request()->is('admin/pages/about-us') ? 'active' : '' }}"
                        href="{{ route('admin.pages.edit', 'about-us') }}">
                        <i class="fa-solid fa-circle-info"></i> <span>About Us</span>
                    </a>

                    <!-- Privacy Policy -->
                    <a class="nav-link {{ request()->is('admin/pages/privacy-policy') ? 'active' : '' }}"
                        href="{{ route('admin.pages.edit', 'privacy-policy') }}">
                        <i class="fa-solid fa-file-shield"></i> <span>Privacy Policy</span>
                    </a>

                    <!-- Terms & Conditions -->
                    <a class="nav-link {{ request()->is('admin/pages/terms-and-conditions') ? 'active' : '' }}"
                        href="{{ route('admin.pages.edit', 'terms-and-conditions') }}">
                        <i class="fa-solid fa-file-lines"></i> <span>Terms & Conditions</span>
                    </a>

                </div>
            </div>

        </nav>

        <div class="logout-section">
            <form action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout shadow-xs">
                    <i class="fa-solid fa-power-off me-2"></i> <span>Logout Dashboard</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Fixed Top Header -->
    <header class="fixed-header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div>
                <h5 class="fw-bold text-dark mb-0">@yield('page_title', 'Dashboard Panel')</h5>
            </div>
            <div class="d-flex align-items-center">
                <div class="text-end me-3 admin-meta">
                    <div class="small fw-bold text-dark lh-1">Administrator</div>
                    <small class="text-muted small">Systems Online</small>
                </div>
                <img src="https://ui-avatars.com/api/?name=Admin&background=0ea5e9&color=fff"
                    class="rounded-circle border shadow-sm" width="38">
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="main-content">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible border-0 shadow-sm mb-4"
                style="border-left: 5px solid #22c55e !important;" role="alert">
                <i class="fa-solid fa-circle-check me-2 text-success fs-5"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
