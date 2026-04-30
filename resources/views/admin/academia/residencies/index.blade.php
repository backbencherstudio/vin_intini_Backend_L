@extends('admin.academia.layout')

@section('content')
    <div class="container-fluid">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-user-md text-success me-2"></i>Medical Residencies</h2>
                <p class="text-muted small">Showing {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} of
                    {{ $data->total() }} Programs</p>
            </div>
            <!-- Create Button Trigger -->
            <button type="button" class="btn btn-success shadow-sm px-4 py-2 fw-bold" data-bs-toggle="modal"
                data-bs-target="#createResidencyModal">
                <i class="fas fa-plus-circle me-2"></i>Add New Residency
            </button>
        </div>

        <!-- Filter & Search Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('admin.residencies.index') }}" method="GET" class="row g-3 align-items-end">
                    <!-- Search Input -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Search Program / City</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0"
                                placeholder="e.g. Psychiatry or Phoenix" value="{{ request('search') }}">
                        </div>
                    </div>

                    <!-- State Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Filter by State</label>
                        <select name="state_id" class="form-select shadow-sm">
                            <option value="">All States</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}"
                                    {{ request('state_id') == $state->id ? 'selected' : '' }}>
                                    {{ $state->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success w-100 shadow-sm"><i
                                    class="fas fa-filter me-2"></i>Apply Filter</button>
                            <a href="{{ route('admin.residencies.index') }}"
                                class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card shadow border-0 overflow-hidden text-center">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light border-bottom text-uppercase small fw-bold text-success">
                            <tr>
                                <th class="ps-4 py-3">ID</th>
                                <th class="text-start">Program Name</th>
                                <th>State</th>
                                <th>City / Location</th>
                                <th>Degree Types</th>
                                <th>Map Pin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $item->id }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $item->program_name }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            {{ $item->state->name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            {{ $item->location ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        @if ($item->degree_types && is_array($item->degree_types))
                                            @foreach ($item->degree_types as $degree)
                                                <span class="badge bg-info-subtle text-info border border-info-subtle x-small px-2 mb-1">{{ $degree }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->latitude && $item->longitude)
                                            <div class="bg-light p-1 rounded border d-inline-block px-2 shadow-sm">
                                                <code class="text-primary small">{{ number_format($item->latitude, 2) }}, {{ number_format($item->longitude, 2) }}</code>
                                            </div>
                                        @else
                                            <span class="text-danger x-small fw-bold">No GPS</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- Edit Trigger -->
                                            <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#editResidencyModal"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->program_name }}"
                                                data-state="{{ $item->state_id }}"
                                                data-location="{{ $item->location }}"
                                                data-lat="{{ $item->latitude }}"
                                                data-long="{{ $item->longitude }}"
                                                data-degrees="{{ is_array($item->degree_types) ? implode(', ', $item->degree_types) : '' }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Delete Form -->
                                            <form action="{{ route('admin.residencies.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete this program?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 shadow-sm">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-5 text-center text-muted">No Residency Programs Found.</td>
                                </tr>
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

    <!-- Create Modal -->
    <div class="modal fade" id="createResidencyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Residency</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.residencies.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Program Name</label>
                            <input type="text" name="program_name" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">State</label>
                                <select name="state_id" class="form-select" required>
                                    <option value="">Select State</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">City/Location</label>
                                <input type="text" name="location" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3 bg-light p-3 rounded border mx-0 shadow-sm">
                            <h6 class="fw-bold text-secondary mb-3 small">Map Coordinates</h6>
                            <div class="col-md-6">
                                <input type="text" name="latitude" class="form-control" placeholder="Lat (e.g. 33.44)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="longitude" class="form-control" placeholder="Long (e.g. -112.07)">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-bold text-success small">Degrees (Comma Separated)</label>
                            <input type="text" name="degree_types" class="form-control" placeholder="MD, DO, PhD">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 text-start">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-5 shadow">Save Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editResidencyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg text-start">
                <div class="modal-header bg-warning text-dark text-start">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Residency Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editResidencyForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-start d-block">Program Name</label>
                            <input type="text" name="program_name" id="edit_res_name" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-start d-block">State</label>
                                <select name="state_id" id="edit_res_state" class="form-select" required>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-start d-block">City / Location</label>
                                <input type="text" name="location" id="edit_res_loc" class="form-control">
                            </div>
                        </div>
                        <div class="row mb-3 bg-light p-3 rounded border mx-0 shadow-sm text-start">
                            <h6 class="fw-bold text-secondary mb-3 small">Map Coordinates</h6>
                            <div class="col-md-6">
                                <input type="text" name="latitude" id="edit_res_lat" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="longitude" id="edit_res_long" class="form-control">
                            </div>
                        </div>
                        <div class="mb-0 text-start">
                            <label class="form-label fw-bold text-success small text-start d-block">Degrees</label>
                            <input type="text" name="degree_types" id="edit_res_degrees" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning px-5 shadow fw-bold text-dark text-start">Update Program</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editResidencyModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const form = document.getElementById('editResidencyForm');
                form.action = `/admin/academia/residencies/${button.getAttribute('data-id')}`;

                document.getElementById('edit_res_name').value = button.getAttribute('data-name');
                document.getElementById('edit_res_state').value = button.getAttribute('data-state');
                document.getElementById('edit_res_loc').value = button.getAttribute('data-location');
                document.getElementById('edit_res_lat').value = button.getAttribute('data-lat');
                document.getElementById('edit_res_long').value = button.getAttribute('data-long');
                document.getElementById('edit_res_degrees').value = button.getAttribute('data-degrees');
            });
        });
    </script>

    <style>
        .x-small { font-size: 0.65rem; font-weight: 600; }
        .bg-info-subtle { background-color: #e1f5fe !important; border-color: #b3e5fc !important; color: #01579b !important; }
        .pagination svg { width: 1.2rem !important; }
        .modal-content { border-radius: 15px; }
        .table thead th { font-size: 0.8rem; letter-spacing: 0.5px; }
    </style>
@endsection
