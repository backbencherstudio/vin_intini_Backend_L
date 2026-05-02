@extends('admin.academia.layout')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fas fa-hospital-symbol text-primary me-2"></i>Hospitals & Facilities</h2>
            <p class="text-muted small">Showing {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} of {{ $data->total() }} records</p>
        </div>
        <div class="d-flex align-items-center">
            <!-- Create Button -->
            <button class="btn btn-primary shadow-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#createFacilityModal">
                <i class="fas fa-plus-circle me-2"></i>Add New Facility
            </button>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="{{ route('admin.facilities.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Search Name / City</label>
                    <input type="text" name="search" class="form-control" placeholder="Type here..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Filter by State</label>
                    <select name="state_id" class="form-select">
                        <option value="">All States</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Filter by Type</label>
                    <select name="type" class="form-select text-capitalize">
                        <option value="">All Categories</option>
                        <option value="state_institution" {{ request('type') == 'state_institution' ? 'selected' : '' }}>State Institution</option>
                        <option value="university_hospital" {{ request('type') == 'university_hospital' ? 'selected' : '' }}>University Hospital</option>
                        <option value="va_facility" {{ request('type') == 'va_facility' ? 'selected' : '' }}>VA Facility</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                        <a href="{{ route('admin.facilities.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-body p-0 text-center">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light border-bottom">
                        <tr class="text-secondary text-uppercase small fw-bold">
                            <th class="ps-4">ID</th>
                            <th class="text-start">Facility Details</th>
                            <th>State</th>
                            <th>Category</th>
                            <th>Map Pin (Lat, Long)</th>
                            <th class="pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $item)
                            <tr>
                                <td class="ps-4 text-muted small">#{{ $item->id }}</td>
                                <td class="text-start">
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <div class="text-muted small"><i class="fas fa-map-marker-alt text-danger"></i> {{ $item->location ?? 'N/A' }}</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $item->state->name ?? 'N/A' }}</span></td>
                                <td>
                                    @php
                                        $badgeClass = $item->type == 'university_hospital' ? 'bg-info text-dark' : ($item->type == 'va_facility' ? 'bg-success text-white' : 'bg-warning text-dark');
                                    @endphp
                                    <span class="badge {{ $badgeClass }} rounded-pill px-3 py-2 small fw-bold">
                                        {{ str_replace('_', ' ', $item->type) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($item->latitude && $item->longitude)
                                        <code class="text-primary">{{ number_format($item->latitude, 4) }}, {{ number_format($item->longitude, 4) }}</code>
                                    @else
                                        <span class="text-danger small fw-bold">No GPS</span>
                                    @endif
                                </td>
                                <td class="pe-4">
                                    <div class="d-flex justify-content-center gap-2">
                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-outline-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#editFacilityModal"
                                            data-id="{{ $item->id }}" data-name="{{ $item->name }}" data-state="{{ $item->state_id }}"
                                            data-type="{{ $item->type }}" data-location="{{ $item->location }}" data-lat="{{ $item->latitude }}" data-long="{{ $item->longitude }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <form action="{{ route('admin.facilities.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this facility?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-5">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-4 d-flex justify-content-center">
        {{ $data->links('pagination::bootstrap-5') }}
    </div>
</div>

<!-- Create Facility Modal -->
<div class="modal fade" id="createFacilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Facility</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.facilities.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-bold">Facility Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold small">State</label><select name="state_id" class="form-select" required>
                            <option value="">Select State</option>@foreach($states as $state)<option value="{{ $state->id }}">{{ $state->name }}</option>@endforeach
                        </select></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Type</label><select name="type" class="form-select" required>
                            <option value="state_institution">State Institution</option><option value="university_hospital">University Hospital</option><option value="va_facility">VA Facility</option>
                        </select></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold">Location / City</label><input type="text" name="location" class="form-control" placeholder="e.g. Phoenix"></div>
                    <div class="row mb-3 bg-light p-3 rounded border mx-0 shadow-sm">
                        <h6 class="fw-bold text-secondary mb-3 small">Map Pin Coordinates</h6>
                        <div class="col-md-6"><label class="small text-muted">Latitude</label><input type="text" name="latitude" class="form-control form-control-sm" placeholder="e.g. 33.44"></div>
                        <div class="col-md-6"><label class="small text-muted">Longitude</label><input type="text" name="longitude" class="form-control form-control-sm" placeholder="e.g. -112.07"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-5 shadow">Save Facility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Facility Modal -->
<div class="modal fade" id="editFacilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-start">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Facility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editFacilityForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label fw-bold">Facility Name</label><input type="text" name="name" id="fac_name" class="form-control" required></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label fw-bold small">State</label><select name="state_id" id="fac_state" class="form-select" required>
                            @foreach($states as $state)<option value="{{ $state->id }}">{{ $state->name }}</option>@endforeach
                        </select></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Type</label><select name="type" id="fac_type" class="form-select" required>
                            <option value="state_institution">State Institution</option><option value="university_hospital">University Hospital</option><option value="va_facility">VA Facility</option>
                        </select></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold">Location</label><input type="text" name="location" id="fac_loc" class="form-control"></div>
                    <div class="row mb-3 bg-light p-3 rounded border mx-0 shadow-sm">
                        <h6 class="fw-bold text-secondary mb-3 small">Update Coordinates</h6>
                        <div class="col-md-6"><input type="text" name="latitude" id="fac_lat" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><input type="text" name="longitude" id="fac_long" class="form-control form-control-sm"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-5 shadow fw-bold text-dark">Update Facility</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editFacilityModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const form = document.getElementById('editFacilityForm');
            form.action = `/admin/academia/facilities/${button.getAttribute('data-id')}`;
            document.getElementById('fac_name').value = button.getAttribute('data-name');
            document.getElementById('fac_state').value = button.getAttribute('data-state');
            document.getElementById('fac_type').value = button.getAttribute('data-type');
            document.getElementById('fac_loc').value = button.getAttribute('data-location');
            document.getElementById('fac_lat').value = button.getAttribute('data-lat');
            document.getElementById('fac_long').value = button.getAttribute('data-long');
        });
    });
</script>

<style>
    .card { border-radius: 12px; }
    .pagination svg { width: 1.2rem !important; }
    .modal-content { border-radius: 15px; }
</style>
@endsection
