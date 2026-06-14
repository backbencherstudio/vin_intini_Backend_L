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
        /* Sidebar Styling */
        .sidebar {
            height: 100 vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            font-weight: 500;
            border-radius: 8px;
            margin: 4px 15px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            background-color: #e9ecef;
            color: #0d6efd;
        }
        .sidebar .nav-link.active {
            background-color: #e0f2f1; /* MindUnite style light teal */
            color: #00796b;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        /* Main Content Styling */
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .navbar-brand {
            font-weight: bold;
            padding-left: 20px;
            color: #00796b !important;
            font-size: 1.5rem;
        }
        @media (max-width: 992px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .main-content { margin-left: 70px; }
            .navbar-brand span { display: none; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <a class="navbar-brand d-block mb-4" href="#">
            <i class="fa-solid fa-brain"></i> <span>MindUnite</span>
        </a>

        <nav class="nav flex-column">
            <small class="text-uppercase text-muted px-4 mb-2" style="font-size: 0.7rem;">Management</small>

            <!-- Partners Tab -->
            <a class="nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}" href="{{ route('admin.partners.index') }}">
                <i class="fa-solid fa-handshake"></i> <span>Partners</span>
            </a>

            <small class="text-uppercase text-muted px-4 mt-3 mb-2" style="font-size: 0.7rem;">Industries</small>

            <!-- Categories Tab -->
            <a class="nav-link {{ request()->is('admin/categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                <i class="fa-solid fa-list"></i> <span>Categories</span>
            </a>

            <!-- Biotechnology Tab -->
            <a class="nav-link {{ request()->is('admin/biotech*') ? 'active' : '' }}" href="{{ route('admin.biotech.index') }}">
                <i class="fa-solid fa-seedling"></i> <span>Biotechnology</span>
            </a>

            <!-- Psychopharmacology Tab -->
            <a class="nav-link {{ request()->is('admin/pharma*') ? 'active' : '' }}" href="{{ route('admin.pharma.index') }}">
                <i class="fa-solid fa-capsules"></i> <span>Psychopharmacology</span>
            </a>

            <!-- Publications Tab -->
            <a class="nav-link {{ request()->is('admin/publications*') ? 'active' : '' }}" href="{{ route('admin.publications.index') }}">
                <i class="fa-solid fa-book-open"></i> <span>Publications</span>
            </a>

            <hr class="mx-3 mt-4">
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">@yield('page_title', 'Industries')</h4>
            {{-- <div class="user-info">
                <span class="me-2 text-muted">Admin Panel</span>
                <img src="https://ui-avatars.com/api/?name=Admin&background=00796b&color=fff" class="rounded-circle" width="35">
            </div> --}}
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        @endif

        <!-- Main Dynamic Content -->
        @yield('content')
    </div>

    <!-- Bootstrap 5 JS and Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Extra scripts if needed -->
    @stack('scripts')
</body>
</html>
