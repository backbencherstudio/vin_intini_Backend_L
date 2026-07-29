@extends('admin.layout')

@section('content')
    <div class="container-fluid py-4">
        <!-- Breadcrumb & Title Section -->
        <div class="page-header mb-4">
            <div class="d-flex align-items-center">
                <!-- Icon Box -->
                <div class="bg-white shadow-sm border rounded-3 d-flex align-items-center justify-content-center me-3"
                    style="width: 50px; height: 50px;">
                    <i class="fa-solid fa-chart-column text-primary fs-4"></i>
                </div>

                <div>
                    <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">
                        Institutions Wise Student Statistics
                    </h3>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 small text-muted">
                            <li class="breadcrumb-item">
                                <a href="{{ route('institution.index') }}" class="text-muted text-decoration-none">
                                    <i class="fa-solid fa-house-chimney me-1" style="font-size: 10px;"></i> Institutions
                                </a>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card shadow-sm border-0 overflow-hidden">
            <!-- Card Header: Title and Search combined -->
            <div class="card-header py-2" style="background-color: #1E293B !important; border: none;">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <!-- Left: Title -->
                    <h6 class="card-title mb-0 text-white fw-bold py-2">
                        <i class="fa fa-list-ul me-2"></i>Institution Records List
                    </h6>

                    <!-- Right: Search Form -->
                    <form action="{{ route('institution.index') }}" method="GET" class="row g-2 align-items-center py-1">
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted border-0"><i
                                        class="fa fa-search"></i></span>
                                <input type="text" name="search" class="form-control border-0"
                                    placeholder="Search institution name..." value="{{ request('search') }}"
                                    style="width: 250px;">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">Search</button>
                            @if (request('search'))
                                <a href="{{ route('institution.index') }}" class="btn btn-light btn-sm px-2 ms-1 border-0"
                                    title="Reset">
                                    <i class="fa fa-refresh text-dark"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-center">
                            <tr class="text-center">
                                <th width="60" class="fw-bold border-top-0">#NO</th>
                                <th class="text-start border-top-0">Institution Name</th>
                                <th class="border-top-0">Total Students</th>
                                <th class="border-top-0">Completed Programs</th>
                                <th class="border-top-0">Present Students</th>
                                <th class="border-top-0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($institutions as $institution)
                                <tr class="text-center">
                                    <td class="text-muted fw-bold">
                                        {{ ($institutions->currentPage() - 1) * $institutions->perPage() + $loop->iteration }}
                                    </td>
                                    <td class="text-start">
                                        <span class="fw-bold text-dark">{{ $institution->name }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">
                                            {{ $institution->total_students }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $institution->completed_students }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $institution->present_students }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('institution.students', $institution->id) }}"
                                            class="btn btn-sm btn-success px-3 fw-bold shadow-xs">
                                            <i class="fa fa-eye me-1"></i> View Students
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-folder-open-o fa-2x d-block mb-2"></i>
                                        No institutions found.
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
                        Showing <b>{{ $institutions->firstItem() ?? 0 }}</b> to
                        <b>{{ $institutions->lastItem() ?? 0 }}</b> of
                        <b>{{ $institutions->total() }}</b> results
                    </div>
                    <div>
                        {{ $institutions->appends(request()->input())->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
