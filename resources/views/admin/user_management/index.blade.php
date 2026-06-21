@extends('admin.layout')

@section('content')
<div class="container-fluid py-1">

    <!-- Statistics Cards with Icons -->
    <div class="row g-3 mb-4 text-center text-md-start">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.5px;">Total Register Users</h6>
                            <h3 class="fw-bold mb-0 text-primary">{{ number_format($totalUsers) }}</h3>
                        </div>
                        <div class="bg-primary-subtle p-3 rounded-3">
                            <i class="fas fa-users text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.5px;">Today's Joined</h6>
                            <h3 class="fw-bold mb-0 text-warning">{{ number_format($todayUsers) }}</h3>
                        </div>
                        <div class="bg-warning-subtle p-3 rounded-3">
                            <i class="fas fa-user-plus text-warning fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.5px;">Joined in {{ now()->format('F') }}</h6>
                            <h3 class="fw-bold mb-0 text-success">{{ number_format($currentMonthUsers) }}</h3>
                        </div>
                        <div class="bg-success-subtle p-3 rounded-3">
                            <i class="fas fa-calendar-check text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.5px;">Joined in {{ now()->subMonth()->format('F') }}</h6>
                            <h3 class="fw-bold mb-0 text-info">{{ number_format($previousMonthUsers) }}</h3>
                        </div>
                        <div class="bg-info-subtle p-3 rounded-3">
                            <i class="fas fa-history text-info fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form & Result Summary Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center g-3">
                <div class="col-md-9">
                    <form action="{{ route('admin.user.management') }}" method="GET" class="row g-2 align-items-center">
                        <!-- Search by Name/Email -->
                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by Name or Email..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <!-- Date Filters -->
                        <div class="col-md-2">
                            <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-sm btn-success px-4 fw-bold shadow-sm">Apply</button>
                            @if(request('search') || request('from_date'))
                                <a href="{{ route('admin.user.management') }}" class="btn btn-sm btn-light border px-3">Reset</a>
                            @endif
                        </div>
                    </form>
                </div>

                @if(request('search') || (request('from_date') && request('to_date')))
                <div class="col-md-3 text-md-end">
                    <div class="alert alert-success border-0 py-2 px-3 mb-0 d-inline-block rounded-pill small fw-bold shadow-sm">
                        Found {{ $filterCount }} Results
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- User Table Section -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom-0">
            <h6 class="fw-bold mb-0 text-dark">Verified User List</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive text-center">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                            <th class="ps-4 py-3">SL</th>
                            <th>Profile</th>
                            <th class="text-start">Full Name</th>
                            <th class="text-start">Email Address</th>
                            <th>Joined Date & Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4 text-muted small fw-bold">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                                <td>
                                    <img src="{{ $user->profile_image_url }}"
                                         class="rounded-circle border"
                                         style="width: 42px; height: 42px; object-fit: cover; border: 2px solid #fff !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                </td>
                                <td class="text-start">
                                    <div class="fw-bold text-dark">{{ $user->first_name }} {{ $user->last_name }}</div>
                                </td>
                                <td class="text-start">
                                    <span class="text-muted small">{{ $user->email }}</span>
                                </td>
                                <td>
                                    <div class="small fw-bold text-dark convert-to-local-date" data-utc="{{ $user->created_at->toIso8601String() }}">
                                        {{ $user->created_at->format('d M Y') }}
                                    </div>
                                    <div class="text-muted x-small convert-to-local-time" data-utc="{{ $user->created_at->toIso8601String() }}">
                                        {{ $user->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill small fw-bold">
                                        <i class="fas fa-check-circle me-1"></i> Verified
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-5 text-center text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2 opacity-25"></i>
                                    <p class="mb-0 small fw-bold">No verified users found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 border-top-0">
            <div class="d-flex justify-content-center">
                {{ $users->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dOpts = { day: '2-digit', month: 'short', year: 'numeric' };
        const tOpts = { hour: '2-digit', minute: '2-digit', hour12: true };

        document.querySelectorAll('.convert-to-local-date').forEach(el => {
            const utc = el.getAttribute('data-utc');
            if (utc) el.innerText = new Date(utc).toLocaleDateString('en-GB', dOpts);
        });

        document.querySelectorAll('.convert-to-local-time').forEach(el => {
            const utc = el.getAttribute('data-utc');
            if (utc) el.innerText = new Date(utc).toLocaleTimeString('en-US', tOpts);
        });
    });
</script>

<style>
    .bg-primary-subtle { background-color: #e7f1ff !important; }
    .bg-warning-subtle { background-color: #fff9e6 !important; }
    .bg-success-subtle { background-color: #e6fcf5 !important; }
    .bg-info-subtle { background-color: #e7faff !important; }
    .x-small { font-size: 0.72rem; }
    .table thead th { font-size: 0.7rem; color: #6c757d; }
    .table tbody td { border-bottom: 1px solid #f8f9fa; padding: 12px 10px; }
    .form-control-sm:focus { border-color: #198754; box-shadow: none; }
    .alert-success { background-color: #d1e7dd; color: #0f5132; }
</style>
@endsection
