@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <!-- Breadcrumb & Title Section -->
        <div class="page-header mb-4">
            <div class="d-flex align-items-center">
                <!-- Premium Icon Box -->
                <div class="bg-white shadow-sm border rounded-3 d-flex align-items-center justify-content-center me-3"
                    style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-university text-primary fs-4"></i>
                </div>

                <div>
                    <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
                        {{ $institution->name }}
                    </h3>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small text-muted">
                            <li class="breadcrumb-item">
                                <a href="{{ route('institution.index') }}" class="text-muted text-decoration-none">
                                    <i class="fa-solid fa-house-chimney me-1" style="font-size: 10px;"></i> Institutions
                                </a>
                            </li>
                            <li class="breadcrumb-item active fw-semibold text-primary" aria-current="page">
                                Student Directory
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow-sm border-0 overflow-hidden">
            <!-- Card Header: Title and Filter combined in one row -->
            <div class="card-header py-2" style="background-color: #1E293B !important; border: none;">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <!-- Left: Title -->
                    <h6 class="card-title mb-0 text-white fw-bold py-2">
                        <i class="fa fa-list-ul me-2"></i>Student Academic Records
                    </h6>

                    <!-- Right: Filter Form -->
                    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center py-1">
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-0"><i
                                        class="fa fa-search"></i></span>
                                <input type="text" name="search" class="form-control border-0"
                                    placeholder="Search name or email..." value="{{ request('search') }}"
                                    style="width: 200px;">
                            </div>
                        </div>

                        <div class="col-auto">
                            <select name="status" class="form-select form-select-sm border-0">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Present</option>
                                <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Search</button>
                            <a href="{{ url()->current() }}" class="btn btn-light btn-sm px-2 ms-1 border-0" title="Reset">
                                <i class="fa fa-refresh text-dark"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0"
                        style="font-size: 13.5px; border-top: none;">
                        <thead class="table-light">
                            <tr class="text-center">
                                <th width="60" class="fw-bold border-top-0">#NO</th>
                                <th class="text-start border-top-0">Student Name</th>
                                <th class="text-start border-top-0">Student Email</th>
                                <th class="text-start border-top-0">Program & Field</th>
                                <th class="border-top-0">Academic Period</th>
                                <th class="border-top-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($educations as $edu)
                                @php
                                    $user = $edu->user;
                                    $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                                    $avatarUrl =
                                        $user && $user->profile_image
                                            ? $user->profile_image_url
                                            : 'https://ui-avatars.com/api/?name=' .
                                                urlencode($fullName) .
                                                '&background=6366f1&color=fff&bold=true';
                                @endphp
                                <tr class="text-center">
                                    <td class="text-muted fw-bold">
                                        {{ ($educations->currentPage() - 1) * $educations->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <img src="{{ $avatarUrl }}" class="rounded border me-2"
                                                style="width: 38px; height: 38px; object-fit: cover;">
                                            <span class="fw-bold text-dark">{{ $fullName }}</span>
                                        </div>
                                    </td>
                                    <td class="text-start">
                                        <strong>{{ $user->email ?? 'N/A' }}</strong>
                                    </td>
                                    <td class="text-start">
                                        <div class="fw-bold mb-0 text-dark">{{ $edu->degree }}</div>
                                        <small class="text-primary fw-medium">{{ $edu->field_study }}</small>
                                    </td>
                                    <td>
                                        <span class="">
                                            <i class="fa fa-calendar-o me-1"></i>
                                            {{ $edu->start_month }} {{ $edu->start_year }} —
                                            @if ($edu->is_current)
                                                <span class="text-success fw-bold">Present</span>
                                            @else
                                                {{ $edu->end_month }} {{ $edu->end_year }}
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if ($edu->is_current)
                                            <span
                                                class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3"
                                                style="font-size: 11px;">Present</span>
                                        @else
                                            <span
                                                class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle px-3"
                                                style="font-size: 11px;">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-info-circle me-1"></i> No student records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Card Footer (Pagination) -->
            <div class="card-footer bg-white py-3 border-top-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing <b>{{ $educations->firstItem() ?? 0 }}</b> to <b>{{ $educations->lastItem() ?? 0 }}</b> of
                        <b>{{ $educations->total() }}</b> results
                    </div>
                    <div>
                        {{ $educations->appends(request()->input())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
