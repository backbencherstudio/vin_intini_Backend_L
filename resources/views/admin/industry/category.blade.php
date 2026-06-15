{{-- @extends('admin.industry.layouts') --}}
@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-layer-group text-primary me-2"></i>
                    Manage <span class="text-primary">{{ ucfirst($network) }}</span> Structure
                </h4>
                <p class="text-muted small mb-0">Configure Biotech and Pharma sections. (Publications are auto-managed)</p>
            </div>
            <button class="btn btn-primary shadow-sm fw-bold px-4 rounded-pill" onclick="openSectionModal()">
                <i class="fa-solid fa-plus me-1"></i> New Section
            </button>
        </div>

        {{-- Grid View --}}
        <div class="row g-4">
            @forelse ($sections as $section)
                @if ($section->industry_type !== 'publications')
                    <div class="col-xl-4 col-lg-6">
                        <div class="card shadow-sm border-0 h-100 {{ $section->industry_type == 'psychopharmacology' ? 'border-start border-info border-4' : '' }}"
                            style="border-radius: 16px; background: #fff;">

                            <div
                                class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-start">
                                <div>
                                    <span
                                        class="badge {{ $section->industry_type == 'biotechnology' ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }} text-uppercase mb-1"
                                        style="font-size: 10px; font-weight: 700;">
                                        {{ $section->industry_type }}
                                    </span>
                                    <h5 class="fw-bold text-dark mb-0">{{ $section->name }}</h5>
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i
                                            class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg">
                                        <li><a class="dropdown-item small fw-bold text-warning" href="javascript:void(0)"
                                                onclick="editSection({{ $section }})"><i
                                                    class="fa-solid fa-pen me-2"></i>Rename Section</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item small fw-bold text-danger"
                                                href="{{ route('admin.sections.delete', $section->id) }}"
                                                onclick="return confirm('Delete this section and all its contents?')"><i
                                                    class="fa-solid fa-trash me-2"></i>Delete Section</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="card-body px-4 pb-4">
                                <label class="small fw-bold text-muted text-uppercase mb-3 d-block"
                                    style="font-size: 9px; opacity: 0.6;">
                                    {{ $section->industry_type == 'psychopharmacology' ? 'Fixed Configuration' : 'Sub-Tabs / Categories' }}
                                </label>

                                <div class="d-flex flex-wrap gap-2 mb-4" style="min-height: 40px;">
                                    @php
                                        $customTabs = $section->IndustryCategory->where('category_name', '!=', 'All');
                                    @endphp

                                    @forelse ($customTabs as $cat)
                                        <div
                                            class="d-flex align-items-center bg-white border rounded-pill px-3 py-1 shadow-xs border-light-subtle">
                                            <span class="text-dark small fw-semibold">{{ $cat->category_name }}</span>

                                            @if ($section->industry_type == 'biotechnology')
                                                <div class="ms-2 ps-2 border-start d-flex gap-2">
                                                    <button class="btn btn-link btn-sm p-0 text-warning"
                                                        onclick="editCategory({{ $cat }}, '{{ $section->name }}')"><i
                                                            class="fa-solid fa-pencil"
                                                            style="font-size: 10px;"></i></button>
                                                    <a href="{{ route('admin.categories.delete', $cat->id) }}"
                                                        class="btn btn-link btn-sm p-0 text-danger"
                                                        onclick="return confirm('Delete tab?')"><i class="fa-solid fa-xmark"
                                                            style="font-size: 11px;"></i></a>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-muted small italic py-1 opacity-50">
                                            {{ $section->industry_type == 'psychopharmacology' ? 'Direct medication list (No tabs needed)' : 'No custom tabs added.' }}
                                        </div>
                                    @endforelse
                                </div>

                                @if ($section->industry_type == 'biotechnology')
                                    <button class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-bold"
                                        style="font-size: 10px;"
                                        onclick="openCategoryModal({{ $section->id }}, '{{ $section->name }}')">
                                        <i class="fa-solid fa-plus me-1"></i> ADD CUSTOM TAB
                                    </button>
                                @else
                                    <div class="text-center py-2 bg-light rounded border border-light-subtle">
                                        <small class="text-muted fw-bold" style="font-size: 9px;"><i
                                                class="fa-solid fa-lock me-1"></i> TABS DISABLED FOR PHARMA</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted small">No sections created yet.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Section Modal --}}
    <div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.sections.store') }}" method="POST" id="sectionForm"
                class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                @csrf
                <div class="modal-header border-0 bg-light px-4">
                    <h5 class="modal-title fw-bold" id="secModalTitle">Manage Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="sec_id">
                    <input type="hidden" name="network_type" value="{{ $network }}">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted text-uppercase">Industry Type</label>
                        <select name="industry_type" id="sec_industry" class="form-select border-0 bg-light" required>
                            <option value="biotechnology">Biotechnology</option>
                            <option value="psychopharmacology">Psychopharmacology</option>
                        </select>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-bold small text-muted text-uppercase">Section Heading</label>
                        <input type="text" name="name" id="sec_name" class="form-control border-0 bg-light" required
                            placeholder="e.g. Diagnostic Imaging">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow-sm rounded-3">Save Section
                        Information</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Category Modal --}}
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.categories.store') }}" method="POST" id="categoryForm"
                class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                @csrf
                <div class="modal-header border-0 bg-light px-4">
                    <h5 class="modal-title fw-bold" id="catModalTitle">Add Tab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="cat_id">
                    <input type="hidden" name="section_id" id="cat_section_id">
                    <div class="mb-3 bg-light p-2 rounded border border-light-subtle">
                        <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 9px;">Targeting
                            Section:</small>
                        <span id="target_section_display" class="fw-bold text-dark"></span>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-bold small text-muted text-uppercase">Tab Label (Category Name)</label>
                        <input type="text" name="category_name" id="cat_name"
                            class="form-control border-0 bg-light shadow-none" required
                            placeholder="e.g. fMRI, Diagnostic Ultrasounds">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-3">Save Tab
                        Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        body {
            background-color: #fcfcfc;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05) !important;
        }

        .shadow-xs {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .italic {
            font-style: italic;
        }
    </style>

    @push('scripts')
        <script>
            const secModal = new bootstrap.Modal(document.getElementById('sectionModal'));
            const catModal = new bootstrap.Modal(document.getElementById('categoryModal'));

            window.openSectionModal = () => {
                document.getElementById('secModalTitle').innerText = "Create New Section";
                document.getElementById('sectionForm').reset();
                document.getElementById('sec_id').value = "";
                secModal.show();
            }

            window.editSection = (data) => {
                document.getElementById('secModalTitle').innerText = "Rename Section";
                document.getElementById('sec_id').value = data.id;
                document.getElementById('sec_name').value = data.name;
                document.getElementById('sec_industry').value = data.industry_type;
                secModal.show();
            }

            window.openCategoryModal = (secId, secName) => {
                document.getElementById('catModalTitle').innerText = "Add Sub-Tab";
                document.getElementById('categoryForm').reset();
                document.getElementById('cat_id').value = "";
                document.getElementById('cat_section_id').value = secId;
                document.getElementById('target_section_display').innerText = secName;
                catModal.show();
            }

            window.editCategory = (data, secName) => {
                document.getElementById('catModalTitle').innerText = "Edit Tab Context";
                document.getElementById('cat_id').value = data.id;
                document.getElementById('cat_name').value = data.category_name;
                document.getElementById('cat_section_id').value = data.section_id;
                document.getElementById('target_section_display').innerText = secName;
                catModal.show();
            }
        </script>
    @endpush
@endsection
