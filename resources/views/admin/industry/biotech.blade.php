@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header Section (Global Add Button) --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Biotechnology
                </h4>
                <p class="text-muted small mb-0">Manage equipment using strictly defined sections and tabs.</p>
            </div>
            <button class="btn btn-primary px-4 py-2 fw-bold btn-sm shadow-sm rounded-pill" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Add New Product
            </button>
        </div>

        {{-- Global Search Filter --}}
        <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 12px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-select-sm border shadow-none"
                        placeholder="Search by equipment title..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100 fw-bold">Search</button>
                </div>
                <div class="col-md-1 text-center">
                    <a href="{{ url()->current() }}"
                        class="btn btn-sm btn-link text-decoration-none small text-muted">Reset</a>
                </div>
            </form>
        </div>

        {{-- Section Content Loop --}}
        @forelse ($sections as $section)
            <div class="biotech-section mb-5">

                {{-- Full Width Highlighted Section Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-3 shadow-sm border" style="background: linear-gradient(135deg, #f8f9fa 0%, #eef2f5 100%); border-left: 5px solid #007bff !important;">
                    <h5 class="fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 1.1rem;">
                        <i class="fa-solid fa-folder-open text-primary me-2 opacity-75"></i>{{ $section->name }}
                    </h5>
                    <button class="btn btn-sm btn-primary fw-bold px-3 rounded-pill shadow-xs d-flex align-items-center"
                            style="font-size: 0.75rem;"
                            onclick="openModal({{ $section->id }}, '{{ addslashes($section->name) }}')">
                        <i class="fa-solid fa-circle-plus me-2 fs-6"></i> Add to Section
                    </button>
                </div>

                {{-- Horizontal Filter Tabs --}}
                <div class="mb-3 px-1">
                    @php
                        $customTabs = $section->IndustryCategory->where('category_name', '!=', 'All');
                    @endphp

                    @if($customTabs->count() > 0)
                    <ul class="nav nav-pills compact-tab-bar p-1 bg-white border rounded shadow-sm flex-nowrap overflow-auto"
                        style="width: fit-content;">
                        <li class="nav-item">
                            <button class="nav-link active btn-filter" onclick="filterByJS(this, 'all')">All Items</button>
                        </li>
                        @foreach ($customTabs as $cat)
                            <li class="nav-item">
                                <button class="nav-link text-muted btn-filter"
                                    onclick="filterByJS(this, 'cat-{{ $cat->id }}')">{{ $cat->category_name }}</button>
                            </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                {{-- Product Cards Grid (6 per row) --}}
                <div class="row g-2 px-1">
                    @php
                        $catIds = $section->IndustryCategory->pluck('id')->toArray();
                        $sectionItems = $items->whereIn('category_id', $catIds);
                    @endphp
                    @forelse ($sectionItems as $item)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-6 product-card cat-{{ $item->category_id }}">
                            <div class="card h-100 biotech-mini-card border-0 shadow-sm position-relative">

                                {{-- Hover Admin Actions --}}
                                <div class="admin-btns">
                                    <button class="btn btn-xs btn-white shadow-sm text-warning"
                                        onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                                    <a href="{{ route('admin.biotech.delete', $item->id) }}"
                                        class="btn btn-xs btn-white shadow-sm text-danger ms-1"
                                        onclick="return confirm('Delete this product?')"><i class="fa fa-trash"></i></a>
                                </div>

                                {{-- Image Box --}}
                                <div class="card-img-box bg-white d-flex align-items-center justify-content-center border-bottom overflow-hidden"
                                    style="height: 110px; padding: 10px;">
                                    @if ($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" class="img-fluid"
                                            style="max-height: 100%; width: 100%; object-fit: contain;">
                                    @else
                                        <i class="fa-regular fa-image text-muted opacity-20" style="font-size: 2rem;"></i>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="card-body p-2 d-flex flex-column text-center">
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.8rem;"
                                        title="{{ $item->title }}">{{ $item->title }}</h6>
                                    <p class="text-primary x-small mb-1 text-truncate fw-semibold">{{ $item->sub_title }}</p>

                                    <div class="mt-auto pt-2 border-top">
                                        <a href="{{ $item->link ?? '#' }}" target="_blank"
                                            class="text-info text-decoration-none fw-bold x-small">Learn more ↗</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-3 text-center text-muted small border-dashed rounded">No items found in this section.</div>
                    @endforelse

                    {{-- Empty State Handled by JS --}}
                    <div class="col-12 category-empty-msg" style="display: none;">
                        <div class="py-4 text-center bg-white rounded border border-dashed shadow-xs">
                            <p class="text-muted small mb-0">No data found in this category tab.</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded border shadow-sm">
                <i class="fa-solid fa-folder-open text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No structure found.</h5>
                <a href="{{ route('admin.categories.' . $network) }}" class="btn btn-outline-primary mt-2">Go to Category Management</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- Add/Edit Modal --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.biotech.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0" style="border-radius: 20px;">
                @csrf
                <div class="modal-header border-0 bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage Biotech Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">

                        {{-- Filtered Dropdown Logic --}}
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase text-muted">Placement (Section > Category Tab)</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm border border-secondary-subtle" required>
                                <option value="">-- Select Specific Tab --</option>
                                @foreach ($sections as $sec)
                                    @php
                                        $customTabs = $sec->IndustryCategory->where('category_name', '!=', 'All');
                                    @endphp

                                    @if ($customTabs->count() > 0)
                                        <optgroup label="{{ ucwords($sec->name) }}" data-section-id="{{ $sec->id }}">
                                            @foreach ($customTabs as $tab)
                                                <option value="{{ $tab->id }}">{{ $tab->category_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="mt-2 p-2 bg-light rounded border border-light-subtle">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                    <strong>Note:</strong> A section will only appear here if you have added at least one custom tab in Category Management.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Product Title</label>
                            <input type="text" name="title" id="title" class="form-control border border-secondary-subtle shadow-none" required placeholder="e.g. Magstim TMS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Subtitle / Company</label>
                            <input type="text" name="sub_title" id="sub_title" class="form-control border border-secondary-subtle shadow-none" placeholder="e.g. Brain Products">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Product Tag</label>
                            <input type="text" name="tag" id="tag" class="form-control border border-secondary-subtle shadow-none" placeholder="e.g. TMS, EEG">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Learn More Link (URL)</label>
                            <input type="url" name="link" id="link" class="form-control border border-secondary-subtle shadow-none" placeholder="https://example.com">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">Product Image</label>
                            <input type="file" name="image" class="form-control border border-secondary-subtle shadow-none p-2 bg-light">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">Short Description</label>
                            <textarea name="description" id="description" class="form-control border border-secondary-subtle shadow-none" rows="2" placeholder="Brief summary of the equipment..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow">Save Product Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .x-small { font-size: 0.65rem; }
        .biotech-mini-card { border-radius: 12px !important; border: 1px solid #eef2f5 !important; transition: 0.2s; background: #fff; overflow: hidden; }
        .biotech-mini-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05) !important; border-color: #007bff !important; }

        /* New Section Background Styling */
        .biotech-section { padding-bottom: 20px; }

        .compact-tab-bar .nav-link { font-size: 0.72rem; padding: 6px 16px; border-radius: 6px; border: none; background: none; transition: 0.2s; }
        .compact-tab-bar .nav-link:hover { background: #f8f9fa; }
        .compact-tab-bar .nav-link.active { background: #1e293b !important; color: white !important; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

        .admin-btns { position: absolute; top: 6px; right: 6px; z-index: 10; opacity: 0; transition: 0.3s; }
        .biotech-mini-card:hover .admin-btns { opacity: 1; }
        .btn-xs { padding: 3px 6px; font-size: 0.65rem; border-radius: 6px; background: white; border: 1px solid #eee; }
        .btn-xs:hover { background: #f8f9fa; }

        .shadow-xs { box-shadow: 0 2px 4px rgba(0,0,0,0.04); }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #dee2e6 !important; }
        .form-control:focus { border-color: #007bff !important; box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.1); }
    </style>

    @push('scripts')
        <script>
            // Tab Filtering Logic
            function filterByJS(btn, filterClass) {
                const section = btn.closest('.biotech-section');
                section.querySelectorAll('.btn-filter').forEach(b => {
                    b.classList.remove('active', 'bg-dark', 'text-white');
                    b.classList.add('text-muted');
                });
                btn.classList.add('active', 'bg-dark', 'text-white');
                btn.classList.remove('text-muted');

                const cards = section.querySelectorAll('.product-card');
                const emptyMsg = section.querySelector('.category-empty-msg');
                let count = 0;

                cards.forEach(card => {
                    if (filterClass === 'all' || card.classList.contains(filterClass)) {
                        card.style.display = 'block';
                        count++;
                    } else {
                        card.style.display = 'none';
                    }
                });
                emptyMsg.style.display = (count === 0) ? 'block' : 'none';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');
                const categorySelect = document.getElementById('category_id');
                const optgroups = categorySelect.querySelectorAll('optgroup');

                // Modified OpenModal for Section Targeting
                window.openModal = (sectionId = null, sectionName = null) => {
                    itemForm.reset();
                    document.getElementById('item_id').value = "";

                    if (sectionId) {
                        document.getElementById('modalTitle').innerText = "Add Product to: " + sectionName;
                        // Hide unrelated optgroups
                        optgroups.forEach(group => {
                            if (group.getAttribute('data-section-id') == sectionId) {
                                group.hidden = false;
                                group.disabled = false;
                            } else {
                                group.hidden = true;
                                group.disabled = true;
                            }
                        });
                    } else {
                        document.getElementById('modalTitle').innerText = "Add Product ({{ ucfirst($network) }})";
                        // Show all optgroups
                        optgroups.forEach(group => {
                            group.hidden = false;
                            group.disabled = false;
                        });
                    }

                    itemModal.show();
                }

                window.editItem = (data) => {
                    // Make sure all optgroups are visible during edit
                    optgroups.forEach(group => { group.hidden = false; group.disabled = false; });

                    document.getElementById('modalTitle').innerText = "Edit Product";
                    document.getElementById('item_id').value = data.id;
                    document.getElementById('category_id').value = data.category_id;
                    document.getElementById('title').value = data.title;
                    document.getElementById('sub_title').value = data.sub_title;
                    document.getElementById('tag').value = data.tag;
                    document.getElementById('link').value = data.link;
                    document.getElementById('description').value = data.description;
                    itemModal.show();
                }
            });
        </script>
    @endpush
@endsection
