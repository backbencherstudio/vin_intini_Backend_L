<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academia Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .sidebar { width: 260px; height: 100vh; position: fixed; background: #2c3e50; color: white; transition: 0.3s; }
        .sidebar .nav-link { color: #bdc3c7; padding: 15px 25px; border-bottom: 1px solid #34495e; transition: 0.3s; }
        .sidebar .nav-link:hover { background: #34495e; color: white; padding-left: 35px; }
        .sidebar .nav-link.active { background: #3498db; color: white; }
        .main-content { margin-left: 260px; padding: 30px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <div class="sidebar shadow">
        <div class="p-4 text-center border-bottom border-secondary">
            <h4 class="fw-bold text-white mb-0"><i class="fas fa-brain me-2"></i>Academia</h4>
        </div>
        <div class="nav flex-column mt-3">
            <a href="{{ route('admin.universities.index') }}" class="nav-link {{ request()->is('*universities*') ? 'active' : '' }}">
                <i class="fas fa-university me-2"></i> Universities
            </a>
            <a href="{{ route('admin.residencies.index') }}" class="nav-link {{ request()->is('*residencies*') ? 'active' : '' }}">
                <i class="fas fa-user-md me-2"></i> Medical Residencies
            </a>
            <a href="{{ route('admin.facilities.index') }}" class="nav-link {{ request()->is('*facilities*') ? 'active' : '' }}">
                <i class="fas fa-hospital me-2"></i> Hospitals / Facilities
            </a>
        </div>
    </div>

    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
