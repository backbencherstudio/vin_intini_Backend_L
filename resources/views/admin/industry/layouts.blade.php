<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MindUnite Admin Panel</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            height: 100vh;
            width: 260px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding-top: 20px;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar .nav-link {
            color: #444;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            margin: 2px 15px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .sidebar .nav-link:hover {
            background-color: #f1f3f5;
            color: #00796b;
        }

        .sidebar .nav-link.active {
            background-color: #e0f2f1;
            color: #00796b;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
        }

        /* Submenu Styling */
        .submenu .nav-link {
            padding: 8px 20px 8px 45px;
            font-size: 0.82rem;
            margin: 0 15px;
        }

        .submenu-icon {
            font-size: 0.7rem;
            transition: transform 0.3s;
        }

        .nav-link:not(.collapsed) .submenu-icon {
            transform: rotate(180deg);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .navbar-brand {
            font-weight: bold;
            padding-left: 25px;
            color: #00796b !important;
            font-size: 1.5rem;
        }

        @media (max-width: 992px) {
            .sidebar {
                width: 70px;
            }

            .sidebar span,
            .sidebar .submenu-icon,
            .sidebar small {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .sidebar .nav-link {
                padding: 12px;
                text-align: center;
            }

            .sidebar .nav-link i {
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar shadow-sm">
        <a class="navbar-brand d-block mb-4" href="#">
            <i class="fa-solid fa-brain"></i> <span>MindUnite</span>
        </a>

        <nav class="nav flex-column">
            <small class="text-uppercase text-muted px-4 mb-2"
                style="font-size: 0.65rem; letter-spacing: 1px;">Management</small>

            <!-- Partners -->
            <a class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}"
                href="{{ route('admin.partners.index') }}">
                <i class="fa-solid fa-handshake"></i> <span>Partners</span>
            </a>

            <!-- Categories Collapsible -->
            <div class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center {{ request()->is('admin/categories*') ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#categorySubMenu">
                    <span><i class="fa-solid fa-layer-group"></i> Categories</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ request()->is('admin/categories*') ? 'show' : '' }}"
                    id="categorySubMenu">
                    <a class="nav-link {{ request()->routeIs('admin.categories.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.categories.psychology') }}">Psychology</a>
                    <a class="nav-link {{ request()->routeIs('admin.categories.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.categories.neuroscience') }}">Neuroscience</a>
                </div>
            </div>

            <hr class="mx-3 my-3 opacity-10">
            <small class="text-uppercase text-muted px-4 mb-2"
                style="font-size: 0.65rem; letter-spacing: 1px;">Networks</small>

            <!-- Psychology Network Group -->
            <div class="nav-item">
                @php $isPsychologyActive = request()->is('admin/biotech/psychology*') || request()->is('admin/pharma/psychology*') || request()->is('admin/publications/psychology*'); @endphp
                <a class="nav-link d-flex justify-content-between align-items-center {{ $isPsychologyActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#psychologyGroupMenu">
                    <span><i class="fa-solid fa-brain text-primary"></i> Psychology</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ $isPsychologyActive ? 'show' : '' }}" id="psychologyGroupMenu">
                    <a class="nav-link {{ request()->routeIs('admin.biotech.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.biotech.psychology') }}">
                        <i class="fa-solid fa-seedling me-1"></i> Biotechnology
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.pharma.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.pharma.psychology') }}">
                        <i class="fa-solid fa-capsules me-1"></i> Psychopharmacology
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.publications.psychology') ? 'active' : '' }}"
                        href="{{ route('admin.publications.psychology') }}">
                        <i class="fa-solid fa-book-open me-1"></i> Publications
                    </a>
                </div>
            </div>

            <!-- Neuroscience Network Group -->
            <div class="nav-item">
                @php $isNeuroActive = request()->is('admin/biotech/neuroscience*') || request()->is('admin/pharma/neuroscience*') || request()->is('admin/publications/neuroscience*'); @endphp
                <a class="nav-link d-flex justify-content-between align-items-center {{ $isNeuroActive ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse" href="#neuroscienceGroupMenu">
                    <span><i class="fa-solid fa-microscope text-info"></i> Neuroscience</span>
                    <i class="fa-solid fa-chevron-down submenu-icon"></i>
                </a>
                <div class="collapse submenu {{ $isNeuroActive ? 'show' : '' }}" id="neuroscienceGroupMenu">
                    <a class="nav-link {{ request()->routeIs('admin.biotech.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.biotech.neuroscience') }}">
                        <i class="fa-solid fa-seedling me-1"></i> Biotechnology
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.pharma.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.pharma.neuroscience') }}">
                        <i class="fa-solid fa-capsules me-1"></i> Psychopharmacology
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.publications.neuroscience') ? 'active' : '' }}"
                        href="{{ route('admin.publications.neuroscience') }}">
                        <i class="fa-solid fa-book-open me-1"></i> Publications
                    </a>
                </div>
            </div>

            <hr class="mx-3 mt-4 opacity-10">
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark">@yield('page_title', 'Industry Management')</h4>
        </div>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Main Dynamic Content -->
        @yield('content')
    </div>

    <!-- Bootstrap 5 JS and Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>

</html>
