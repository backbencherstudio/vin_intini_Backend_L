{{-- @extends('admin.industry.layouts') --}}
@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0">
            <!-- Header with Filter & Search -->
            <div class="card-header bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-lg-3">
                        <h4 class="mb-0 fw-bold text-dark">Partners Management</h4>
                    </div>
                    <div class="col-lg-7">
                        <form action="{{ route('admin.partners.index') }}" method="GET" class="row g-2">
                            <div class="col-md-3">
                                <select name="network" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Networks</option>
                                    <option value="psychology" {{ request('network') == 'psychology' ? 'selected' : '' }}>
                                        Psychology</option>
                                    <option value="neuroscience"
                                        {{ request('network') == 'neuroscience' ? 'selected' : '' }}>Neuroscience</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="industry" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Industries</option>
                                    <option value="biotechnology"
                                        {{ request('industry') == 'biotechnology' ? 'selected' : '' }}>Biotechnology
                                    </option>
                                    <option value="psychopharmacology"
                                        {{ request('industry') == 'psychopharmacology' ? 'selected' : '' }}>
                                        Psychopharmacology</option>
                                    <option value="publications"
                                        {{ request('industry') == 'publications' ? 'selected' : '' }}>Publications</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search partner name..." value="{{ request('search') }}">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
                                    @if (request()->has('search') || request()->has('network') || request()->has('industry'))
                                        <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-danger"><i
                                                class="fa fa-times"></i></a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-2 text-end">
                        <button class="btn btn-primary btn-sm px-3 shadow-sm" onclick="openModal()">+ Add New
                            Partner</button>
                    </div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-uppercase small fw-bold text-muted">
                                <th class="ps-4">Network</th>
                                <th>Industry</th>
                                <th>Partner Name</th>
                                <th>Tag</th>
                                <th>Description</th>
                                <th>Link</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($partners as $partner)
                                <tr>
                                    <td class="ps-4">
                                        <span
                                            class="badge rounded-pill {{ $partner->network_type == 'neuroscience' ? 'bg-teal-subtle text-teal' : 'bg-purple-subtle text-purple' }}">
                                            {{ ucfirst($partner->network_type) }}
                                        </span>
                                    </td>
                                    <td><span
                                            class="text-muted small fw-bold">{{ ucfirst($partner->industry_type) }}</span>
                                    </td>
                                    <td><span class="fw-bold text-dark">{{ $partner->partner_name }}</span></td>
                                    <td><span
                                            class="badge border text-info bg-info-subtle px-2">{{ $partner->partner_tag ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted" title="{{ $partner->partner_desc }}">
                                            {{ Str::limit($partner->partner_desc, 40) }}
                                        </small>
                                    </td>
                                    <td>
                                        @if ($partner->partner_link)
                                            <a href="{{ $partner->partner_link }}" target="_blank"
                                                class="text-primary text-decoration-none small fw-bold">
                                                Learn More <i class="fa-solid fa-arrow-up-right-from-square ms-1"
                                                    style="font-size: 0.7rem;"></i>
                                            </a>
                                        @else
                                            <span class="text-muted small">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-white border shadow-sm"
                                            onclick="editPartner({{ $partner }})">
                                            <i class="fa-solid fa-edit text-warning"></i>
                                        </button>
                                        <a href="{{ route('admin.partners.delete', $partner->id) }}"
                                            class="btn btn-sm btn-white border shadow-sm ms-1"
                                            onclick="return confirm('Are you sure you want to delete this partner?')">
                                            <i class="fa-solid fa-trash text-danger"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="50"
                                            class="mb-3 opacity-50"><br>
                                        <span class="text-muted">No partners found matching your criteria.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer with Pagination -->
            <div class="card-footer bg-white border-0 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="small text-muted mb-0">Showing {{ $partners->firstItem() ?? 0 }} to
                        {{ $partners->lastItem() ?? 0 }} of {{ $partners->total() }} partners</p>
                    <div>
                        {{ $partners->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add/Edit Partner -->
    <div class="modal fade" id="partnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('admin.partners.store') }}" method="POST" id="partnerForm"
                class="modal-content border-0 shadow">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="partner_id" id="partner_id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Network Type</label>
                            <select name="network_type" id="network_type" class="form-select" required>
                                <option value="psychology">Psychology Network</option>
                                <option value="neuroscience">Neuroscience Network</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Industry Type</label>
                            <select name="industry_type" id="industry_type" class="form-select" required>
                                <option value="biotechnology">Biotechnology</option>
                                <option value="psychopharmacology">Psychopharmacology</option>
                                <option value="publications">Publications</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Partner Name</label>
                            <input type="text" name="partner_name" id="partner_name" class="form-control"
                                placeholder="Enter company name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Partner Tag</label>
                            <input type="text" name="partner_tag" id="partner_tag" class="form-control"
                                placeholder="e.g. CNS, FDA Approved">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="partner_desc" id="partner_desc" class="form-control" rows="3"
                                placeholder="Brief company legacy..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Website Link (URL)</label>
                            <input type="url" name="partner_link" id="partner_link" class="form-control"
                                placeholder="https://example.com">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .bg-teal-subtle {
            background-color: #e6fffa !important;
        }

        .text-teal {
            color: #2c7a7b !important;
        }

        .bg-purple-subtle {
            background-color: #faf5ff !important;
        }

        .text-purple {
            color: #6b46c1 !important;
        }

        .btn-white {
            background-color: #fff;
        }

        .pagination {
            margin-bottom: 0;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const myModal = new bootstrap.Modal(document.getElementById('partnerModal'));

                window.openModal = function() {
                    document.getElementById('modalTitle').innerText = "Add New Partner";
                    document.getElementById('partnerForm').reset();
                    document.getElementById('partner_id').value = "";
                    myModal.show();
                }

                window.editPartner = function(data) {
                    document.getElementById('modalTitle').innerText = "Edit Partner: " + data.partner_name;
                    document.getElementById('partner_id').value = data.id;
                    document.getElementById('network_type').value = data.network_type;
                    document.getElementById('industry_type').value = data.industry_type;
                    document.getElementById('partner_name').value = data.partner_name;
                    document.getElementById('partner_tag').value = data.partner_tag;
                    document.getElementById('partner_desc').value = data.partner_desc;
                    document.getElementById('partner_link').value = data.partner_link;
                    myModal.show();
                }
            });
        </script>
    @endpush
@endsection
