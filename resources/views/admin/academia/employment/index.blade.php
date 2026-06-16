@extends('admin.layout')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-briefcase text-primary me-2"></i>Manage Job Openings</h2>
                <p class="text-muted small">Showing {{ $data->firstItem() ?? 0 }}-{{ $data->lastItem() ?? 0 }} of
                    {{ $data->total() }} Records</p>
            </div>
            <!-- Create Button Trigger -->
            <button type="button" class="btn btn-primary shadow-sm px-4 py-2 fw-bold" data-bs-toggle="modal"
                data-bs-target="#createJobModal">
                <i class="fas fa-plus-circle me-2"></i>Add New Job
            </button>
        </div>

        <!-- Search & Filter Section -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form action="{{ route('admin.jobs.index') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Search Job or Company</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i
                                    class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0"
                                placeholder="Title, company..." value="{{ request('search') }}">
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

                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">Category</label>
                        <select name="category" class="form-select shadow-sm">
                            <option value="">All Categories</option>
                            <option value="state_institution"
                                {{ request('category') == 'state_institution' ? 'selected' : '' }}>State Institution
                            </option>
                            <option value="private_practice"
                                {{ request('category') == 'private_practice' ? 'selected' : '' }}>Private Practice</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm"><i
                                    class="fas fa-filter me-2"></i>Apply</button>
                            <a href="{{ route('admin.jobs.index') }}"
                                class="btn btn-outline-secondary w-100 shadow-sm">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="bg-light border-bottom">
                            <tr class="text-secondary text-uppercase small fw-bold">
                                <th class="ps-4 py-3">ID</th>
                                <th class="text-start">Job Title & Company</th>
                                <th>State</th>
                                <th>Category</th>
                                <th>Type & Mode</th>
                                <th>Salary Range</th>
                                <th>GPS Pin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $job)
                                <tr>
                                    <td class="ps-4 text-muted small">#{{ $job->id }}</td>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $job->title }}</div>
                                        <div class="text-muted small">
                                            <i class="fas fa-building text-secondary me-1"></i> {{ $job->company_name }}
                                        </div>
                                        <div class="text-muted x-small">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            {{ $job->location ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark border px-2 py-1">{{ $job->state->name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if ($job->category == 'state_institution')
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle x-small">State Institution</span>
                                        @else
                                            <span class="badge bg-info-subtle text-info border border-info-subtle x-small">Private Practice</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="badge bg-dark-subtle text-dark x-small mb-1 d-block">{{ $job->employment_type ?? 'Full Time' }}</div>
                                        <div class="badge bg-secondary-subtle text-secondary x-small d-block">{{ ucfirst($job->work_mode ?? 'On-site') }}</div>
                                    </td>
                                    <td class="small fw-bold text-dark">
                                        @if($job->salary_min && $job->salary_max)
                                            ${{ number_format($job->salary_min / 1000, 0) }}k - ${{ number_format($job->salary_max / 1000, 0) }}k
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($job->latitude && $job->latitude != 0)
                                            <code class="text-primary x-small">{{ $job->latitude }}, {{ $job->longitude }}</code>
                                        @else
                                            <span class="text-danger x-small fw-bold">No GPS</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm"
                                                data-bs-toggle="modal" data-bs-target="#editJobModal"
                                                data-id="{{ $job->id }}"
                                                data-title="{{ $job->title }}"
                                                data-company="{{ $job->company_name }}"
                                                data-state="{{ $job->state_id }}"
                                                data-category="{{ $job->category }}"
                                                data-location="{{ $job->location }}"
                                                data-min="{{ (int)$job->salary_min }}"
                                                data-max="{{ (int)$job->salary_max }}"
                                                data-mode="{{ $job->work_mode }}"
                                                data-type="{{ $job->employment_type }}"
                                                data-lat="{{ $job->latitude }}"
                                                data-long="{{ $job->longitude }}">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form action="{{ route('admin.jobs.destroy', $job->id) }}" method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this job listing?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-3 shadow-sm">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-5 text-center text-muted">No jobs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $data->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <!-- Create Job Modal -->
    <div class="modal fade" id="createJobModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Post New Job</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('admin.jobs.store') }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Job Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. UI Designer" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Company Name</label>
                                <input type="text" name="company_name" class="form-control" placeholder="e.g. PixelCraft Ltd." required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">State</label>
                                <select name="state_id" class="form-select" required>
                                    <option value="">Select State</option>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Category</label>
                                <select name="category" class="form-select" required>
                                    <option value="state_institution">State Institution</option>
                                    <option value="private_practice">Private Practice</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Location Address</label>
                            <input type="text" name="location" class="form-control" placeholder="City, State or Specific Address">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Salary Min ($)</label>
                                <input type="number" name="salary_min" class="form-control" placeholder="30000">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Salary Max ($)</label>
                                <input type="number" name="salary_max" class="form-control" placeholder="45000">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Work Mode</label>
                                <select name="work_mode" class="form-select">
                                    <option value="onsite">On-site</option>
                                    <option value="remote">Remote</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Employment Type</label>
                                <select name="employment_type" class="form-select">
                                    <option value="Full Time">Full Time</option>
                                    <option value="Part Time">Part Time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                        </div>
                        <div class="row bg-light p-3 rounded border mx-0">
                            <h6 class="fw-bold text-secondary mb-3 small">GPS Coordinates (Optional)</h6>
                            <div class="col-md-6">
                                <label class="small text-muted">Latitude</label>
                                <input type="text" name="latitude" class="form-control" placeholder="e.g. 34.05">
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Longitude</label>
                                <input type="text" name="longitude" class="form-control" placeholder="e.g. -118.24">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">Save Job Opening</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Job Modal -->
    <div class="modal fade" id="editJobModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Job Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editJobForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body p-4">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Job Title</label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Company Name</label>
                                <input type="text" name="company_name" id="edit_company" class="form-control" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">State</label>
                                <select name="state_id" id="edit_state" class="form-select" required>
                                    @foreach ($states as $state)
                                        <option value="{{ $state->id }}">{{ $state->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Category</label>
                                <select name="category" id="edit_category" class="form-select" required>
                                    <option value="state_institution">State Institution</option>
                                    <option value="private_practice">Private Practice</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Location Address</label>
                            <input type="text" name="location" id="edit_location" class="form-control">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Salary Min</label>
                                <input type="number" name="salary_min" id="edit_min" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Salary Max</label>
                                <input type="number" name="salary_max" id="edit_max" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Work Mode</label>
                                <select name="work_mode" id="edit_mode" class="form-select">
                                    <option value="onsite">On-site</option>
                                    <option value="remote">Remote</option>
                                    <option value="hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold small">Employment Type</label>
                                <select name="employment_type" id="edit_type" class="form-select">
                                    <option value="Full Time">Full Time</option>
                                    <option value="Part Time">Part Time</option>
                                    <option value="Contract">Contract</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                        </div>
                        <div class="row bg-light p-3 rounded border mx-0">
                            <div class="col-md-6">
                                <label class="small text-muted">Latitude</label>
                                <input type="text" name="latitude" id="edit_lat" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted">Longitude</label>
                                <input type="text" name="longitude" id="edit_long" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning px-5 shadow fw-bold text-dark">Update Job Information</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const editJobModal = document.getElementById('editJobModal');
            editJobModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const form = document.getElementById('editJobForm');

                form.action = `/academia/jobs/${id}`;

                document.getElementById('edit_title').value = button.getAttribute('data-title');
                document.getElementById('edit_company').value = button.getAttribute('data-company');
                document.getElementById('edit_state').value = button.getAttribute('data-state');
                document.getElementById('edit_category').value = button.getAttribute('data-category');
                document.getElementById('edit_location').value = button.getAttribute('data-location') || '';
                document.getElementById('edit_min').value = button.getAttribute('data-min') || '';
                document.getElementById('edit_max').value = button.getAttribute('data-max') || '';
                document.getElementById('edit_mode').value = button.getAttribute('data-mode') || 'onsite';
                document.getElementById('edit_type').value = button.getAttribute('data-type') || 'Full Time';
                document.getElementById('edit_lat').value = button.getAttribute('data-lat') || '0';
                document.getElementById('edit_long').value = button.getAttribute('data-long') || '0';
            });
        });
    </script>

    <style>
        .x-small { font-size: 0.7rem; padding: 3px 8px; font-weight: 600; }
        .bg-primary-subtle { background-color: #cfe2ff !important; }
        .bg-info-subtle { background-color: #cff4fc !important; }
        .bg-secondary-subtle { background-color: #e2e3e5 !important; }
        .bg-dark-subtle { background-color: #d3d3d4 !important; }
        .pagination svg { width: 1.2rem !important; }
    </style>
@endsection
