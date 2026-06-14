@extends('admin.industry.layouts')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <h4 class="mb-0 fw-bold">Tab Categories</h4>
                    </div>
                    <!-- Filtering Section -->
                    <div class="col-md-7">
                        <form action="{{ route('admin.categories.index') }}" method="GET" class="row g-2">
                            <div class="col-md-3">
                                <select name="network" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Networks</option>
                                    <option value="psychology" {{ request('network') == 'psychology' ? 'selected' : '' }}>
                                        Psychology</option>
                                    <option value="neuroscience" {{ request('network') == 'neuroscience' ? 'selected' : '' }}>
                                        Neuroscience</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="industry" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Industries</option>
                                    <option value="biotechnology" {{ request('industry') == 'biotechnology' ? 'selected' : '' }}>
                                        Biotech</option>
                                    <option value="psychopharmacology"
                                        {{ request('industry') == 'psychopharmacology' ? 'selected' : '' }}>Pharma</option>
                                    <option value="publications" {{ request('industry') == 'publications' ? 'selected' : '' }}>
                                        Pubs</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control form-control-sm"
                                    placeholder="Search tab name..." value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-sm btn-secondary w-100 text-uppercase fw-bold"
                                    style="font-size: 11px;">Filter</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-primary btn-sm shadow-sm fw-bold" onclick="openModal()">+ Add New
                            Tab</button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4">Network</th>
                                <th>Industry Type</th>
                                <th>Section (Heading)</th>
                                <th>Category (Tab Name)</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $cat)
                                <tr>
                                    <td class="ps-4"><span
                                            class="badge bg-dark-subtle text-dark border px-2 py-1">{{ ucfirst($cat->network_type) }}</span>
                                    </td>
                                    <td><span
                                            class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">{{ ucfirst($cat->industry_type) }}</span>
                                    </td>
                                    <td><span class="text-muted fw-semibold">{{ $cat->section_name }}</span></td>
                                    <td>
                                        @if ($cat->category_name == 'All' && $cat->industry_type == 'psychopharmacology')
                                            <span class="text-muted italic small">(No Tabs)</span>
                                        @else
                                            <strong>{{ $cat->category_name }}</strong>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border text-warning shadow-sm"
                                            onclick="editCategory({{ $cat }})"><i class="fa fa-edit"></i>
                                            Edit</button>
                                        <a href="{{ route('admin.categories.delete', $cat->id) }}"
                                            class="btn btn-sm btn-light border text-danger ms-1 shadow-sm"
                                            onclick="return confirm('Delete this category? Items under it will also be deleted.')"><i
                                                class="fa fa-trash"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No categories found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.categories.store') }}" method="POST" id="categoryForm"
                class="modal-content border-0 shadow-lg">
                @csrf
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Category Tab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="category_id" id="category_id">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Network</label>
                            <select name="network_type" id="network_type" class="form-select shadow-sm" required>
                                <option value="psychology">Psychology Network</option>
                                <option value="neuroscience">Neuroscience Network</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold small text-uppercase">Industry Type</label>
                            <select name="industry_type" id="industry_type" class="form-select shadow-sm" required
                                onchange="handleIndustryChange()">
                                <option value="biotechnology">Biotechnology</option>
                                <option value="psychopharmacology">Psychopharmacology</option>
                                {{-- <option value="publications">Publications</option> --}}
                            </select>
                        </div>
                    </div>

                    <!-- Section Name Selection Logic -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-uppercase mb-0">Section Name (Main
                                Heading)</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-bold"
                                id="toggleSectionBtn" onclick="toggleNewSection(true)">+ Create New</button>
                        </div>

                        <div id="existing_section_wrapper">
                            <select id="section_name_select" class="form-select shadow-sm">
                                <option value="">-- Select Existing Section --</option>
                                @foreach ($uniqueSections as $section)
                                    <option value="{{ $section }}">{{ $section }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="new_section_wrapper" style="display: none;">
                            <div class="input-group">
                                <input type="text" id="section_name_new" class="form-control shadow-sm"
                                    placeholder="e.g. Psychotropic Medications">
                                <button type="button" class="btn btn-outline-secondary shadow-sm"
                                    onclick="toggleNewSection(false)">Back</button>
                            </div>
                        </div>
                        <input type="hidden" name="section_name" id="final_section_name">
                    </div>

                    <!-- Category Name Input (Conditional Visibility) -->
                    <div class="mb-2" id="category_name_wrapper">
                        <label class="form-label fw-semibold small text-uppercase">Category Name (Tab Name)</label>
                        <input type="text" name="category_name" id="category_name" class="form-control shadow-sm"
                            placeholder="e.g. fMRI, Diagnostic Ultrasounds">
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Tab Category</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const catModal = new bootstrap.Modal(document.getElementById('categoryModal'));
                const catForm = document.getElementById('categoryForm');

                window.handleIndustryChange = function() {
                    const industryType = document.getElementById('industry_type').value;
                    const catWrapper = document.getElementById('category_name_wrapper');
                    const catInput = document.getElementById('category_name');

                    if (industryType === 'psychopharmacology') {
                        catWrapper.style.display = 'none';
                        catInput.value = 'All';
                        catInput.required = false;
                    } else {
                        catWrapper.style.display = 'block';
                        if (catInput.value === 'All') catInput.value = '';
                        catInput.required = true;
                    }
                }

                window.toggleNewSection = function(isNew = true) {
                    const existingWrapper = document.getElementById('existing_section_wrapper');
                    const newWrapper = document.getElementById('new_section_wrapper');
                    const toggleBtn = document.getElementById('toggleSectionBtn');
                    const newInput = document.getElementById('section_name_new');

                    if (isNew) {
                        existingWrapper.style.display = 'none';
                        newWrapper.style.display = 'block';
                        toggleBtn.style.display = 'none';
                        newInput.focus();
                    } else {
                        existingWrapper.style.display = 'block';
                        newWrapper.style.display = 'none';
                        toggleBtn.style.display = 'block';
                        newInput.value = '';
                    }
                }

                catForm.addEventListener('submit', function() {
                    const selectVal = document.getElementById('section_name_select').value;
                    const newVal = document.getElementById('section_name_new').value;
                    document.getElementById('final_section_name').value = newVal || selectVal;
                });

                window.openModal = function() {
                    document.getElementById('modalTitle').innerText = "Add New Category Tab";
                    catForm.reset();
                    document.getElementById('category_id').value = "";
                    toggleNewSection(false);
                    handleIndustryChange();
                    catModal.show();
                }

                window.editCategory = function(data) {
                    document.getElementById('modalTitle').innerText = "Edit Tab: " + data.category_name;
                    document.getElementById('category_id').value = data.id;
                    document.getElementById('network_type').value = data.network_type;
                    document.getElementById('industry_type').value = data.industry_type;
                    document.getElementById('category_name').value = data.category_name;
                    document.getElementById('section_name_select').value = data.section_name;

                    toggleNewSection(false);
                    handleIndustryChange(); 
                    catModal.show();
                }
            });
        </script>
    @endpush
@endsection
