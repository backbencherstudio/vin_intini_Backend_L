@extends('admin.academia.layout')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-university text-primary me-2"></i>Manage Universities</h2>
                <span class="badge bg-primary px-3 py-2 fs-6 shadow-sm">Showing
                    {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} of {{ $data->total() }} Records</span>
            </div>
            <!-- Create Button Trigger -->
            <button type="button" class="btn btn-primary shadow-sm px-4 py-2 fw-bold" data-bs-toggle="modal"
                data-bs-target="#createModal">
                <i class="fas fa-plus-circle me-2"></i>Add New University
            </button>
        </div>

        <!-- Search & Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('admin.universities.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small text-muted">Search University Name</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i
                                    class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0"
                                placeholder="Enter university name..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
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

                    <div class="col-md-5">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm"><i
                                    class="fas fa-filter me-2"></i>Apply Filters</button>
                            <a href="{{ route('admin.universities.index') }}"
                                class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card shadow-sm border-0 overflow-hidden text-center">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light border-bottom">
                            <tr class="text-secondary text-uppercase small fw-bold">
                                <th class="ps-4 py-3">ID</th>
                                <th class="text-start">University Name</th>
                                <th>State</th>
                                <th>Psychology Degrees</th>
                                <th>Neuroscience Degrees</th>
                                <th>Map Pin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $uni)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $uni->id }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $uni->name }}</div>
                                    </td>
                                    <td><span
                                            class="badge bg-light text-dark border px-2 py-1">{{ $uni->state->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if (!empty($uni->psychology_degrees) && is_array($uni->psychology_degrees))
                                            @foreach ($uni->psychology_degrees as $degree)
                                                <span
                                                    class="badge bg-info-subtle text-info border border-info-subtle x-small mb-1">{{ $degree }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!empty($uni->neuroscience_degrees) && is_array($uni->neuroscience_degrees))
                                            @foreach ($uni->neuroscience_degrees as $degree)
                                                <span
                                                    class="badge bg-success-subtle text-success border border-success-subtle x-small mb-1">{{ $degree }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted small">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($uni->latitude == 0 || $uni->latitude == null)
                                            <span class="text-danger x-small fw-bold">No GPS</span>
                                        @else
                                            <code class="text-primary x-small">{{ number_format($uni->latitude, 2) }},
                                                {{ number_format($uni->longitude, 2) }}</code>
                                        @endif
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#editModal"
                                                data-id="{{ $uni->id }}" data-name="{{ $uni->name }}"
                                                data-state="{{ $uni->state_id }}" data-lat="{{ $uni->latitude }}"
                                                data-long="{{ $uni->longitude }}"
                                                data-online="{{ $uni->has_online_options ? 1 : 0 }}"
                                                data-psych="{{ is_array($uni->psychology_degrees) ? implode(', ', $uni->psychology_degrees) : '' }}"
                                                data-neuro="{{ is_array($uni->neuroscience_degrees) ? implode(', ', $uni->neuroscience_degrees) : '' }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form action="{{ route('admin.universities.destroy', $uni->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this university? This action cannot be undone.');">
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
                                    <td colspan="7" class="py-5 text-center text-muted">No records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination Links -->
        <div class="mt-4 d-flex justify-content-center">
            {{ $data->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <!-- Create University Modal -->
    <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New University</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.universities.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">University Name</label>
                            <input type="text" name="name" class="form-control"
                                placeholder="Enter University Name" required>
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
                            <div class="col-md-6 pt-4 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="has_online_options"
                                        id="create_online">
                                    <label class="form-check-label fw-bold" for="create_online">Available Online?</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 bg-light p-3 rounded border mx-0">
                            <h6 class="fw-bold text-secondary mb-3 small">Map Coordinates</h6>
                            <div class="col-md-6">
                                <input type="text" name="latitude" class="form-control"
                                    placeholder="Latitude (e.g. 34.05)">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="longitude" class="form-control"
                                    placeholder="Longitude (e.g. -118.24)">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-info small">Psychology Degrees (BA, MS, PhD)</label>
                            <input type="text" name="psychology_degrees" class="form-control"
                                placeholder="Comma separated list">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success small">Neuroscience Degrees (BS, PhD)</label>
                            <input type="text" name="neuroscience_degrees" class="form-control"
                                placeholder="Comma separated list">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">Save University</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit University Modal (আগের মতোই থাকবে) -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit University Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">University Name</label>
                            <input type="text" name="name" id="modal_name" class="form-control" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">State</label>
                                <select name="state_id" id="modal_state" class="form-select" required>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 pt-4 mt-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="has_online_options"
                                        id="modal_online">
                                    <label class="form-check-label fw-bold" for="modal_online">Available Online?</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 bg-light p-3 rounded border mx-0">
                            <h6 class="fw-bold text-secondary mb-3 small">Map Coordinates</h6>
                            <div class="col-md-6">
                                <input type="text" name="latitude" id="modal_lat" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="longitude" id="modal_long" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-info small">Psychology Degrees</label>
                            <input type="text" name="psychology_degrees" id="modal_psych" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-success small">Neuroscience Degrees</label>
                            <input type="text" name="neuroscience_degrees" id="modal_neuro" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning px-5 shadow fw-bold text-dark">Update
                            University</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script to Handle Edit Modal Data -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const form = document.getElementById('editForm');
                form.action = `/admin/academia/universities/${id}`;

                document.getElementById('modal_name').value = button.getAttribute('data-name');
                document.getElementById('modal_state').value = button.getAttribute('data-state');
                document.getElementById('modal_lat').value = button.getAttribute('data-lat');
                document.getElementById('modal_long').value = button.getAttribute('data-long');
                document.getElementById('modal_psych').value = button.getAttribute('data-psych');
                document.getElementById('modal_neuro').value = button.getAttribute('data-neuro');
                document.getElementById('modal_online').checked = (button.getAttribute('data-online') == 1);
            });
        });
    </script>

    <style>
        .x-small {
            font-size: 0.65rem;
            padding: 2px 5px;
            font-weight: 600;
        }

        .bg-info-subtle {
            background-color: #e3f2fd !important;
        }

        .bg-success-subtle {
            background-color: #e8f5e9 !important;
        }

        .pagination svg {
            width: 1.2rem !important;
        }

        .modal-content {
            border-radius: 15px;
        }
    </style>
@endsection
