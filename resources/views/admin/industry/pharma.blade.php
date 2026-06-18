@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header Section (Global Add Button) --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Psychopharmacology
                </h4>
                <p class="text-muted small mb-0">Managing Medications and clinical data for the community.</p>
            </div>
            <button class="btn btn-primary px-4 py-2 fw-bold btn-sm shadow-sm rounded-pill" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Medication
            </button>
        </div>

        {{-- Global Search Filter --}}
        <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 12px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm bg-light rounded border px-2 overflow-hidden">
                        <span class="input-group-text border-0 bg-transparent text-muted"><i class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent shadow-none"
                            placeholder="Search medication name..." value="{{ request('search') }}">
                    </div>
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
            <div class="pharma-section-block mb-5">

                {{-- Full Width Highlighted Section Header --}}
                <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded-3 shadow-xs border"
                     style="background: linear-gradient(135deg, #f8f9fa 0%, #eef2f5 100%); border-left: 5px solid #0ea5e9 !important;">
                    <h5 class="fw-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.5px; font-size: 1.1rem;">
                        <i class="fa-solid fa-capsules text-primary me-2 opacity-75"></i>{{ $section->name }}
                    </h5>
                    <button class="btn btn-sm btn-primary fw-bold px-3 rounded-pill shadow-xs d-flex align-items-center"
                            style="font-size: 0.75rem;"
                            onclick="openModal({{ $section->id }}, '{{ addslashes($section->name) }}')">
                        <i class="fa-solid fa-circle-plus me-2 fs-6"></i> Add to this Section
                    </button>
                </div>

                {{-- Horizontal Filter Tabs --}}
                @php
                    $customTabs = $section->IndustryCategory->where('category_name', '!=', 'All');
                @endphp

                @if ($customTabs->count() > 0)
                    <div class="mb-4 px-1">
                        <ul class="nav nav-pills compact-tab-bar p-1 bg-white border rounded-pill shadow-sm flex-nowrap overflow-auto"
                            style="width: fit-content;">
                            <li class="nav-item">
                                <button class="nav-link active btn-filter" onclick="filterPharma(this, 'all')">All Items</button>
                            </li>
                            @foreach ($customTabs as $cat)
                                <li class="nav-item">
                                    <button class="nav-link text-muted btn-filter"
                                        onclick="filterPharma(this, 'cat-{{ $cat->id }}')">{{ $cat->category_name }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Cards Grid (4 per row: col-xl-3) --}}
                <div class="row g-4 product-grid px-1">
                    @php
                        $catIds = $section->IndustryCategory->pluck('id')->toArray();
                        $sectionItems = $items->whereIn('category_id', $catIds);
                    @endphp

                    @forelse ($sectionItems as $item)
                        <div class="col-xl-3 col-lg-4 col-md-6 product-card cat-{{ $item->category_id }}">
                            <div class="card h-100 pharma-premium-card border-0 shadow-sm position-relative">

                                <div class="admin-btns">
                                    <button class="btn btn-sm btn-white shadow text-warning rounded-circle mb-1"
                                        onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                                    <a href="{{ route('admin.pharma.delete', $item->id) }}"
                                        class="btn btn-sm btn-white shadow text-danger rounded-circle"
                                        onclick="return confirm('Delete medication?')"><i class="fa fa-trash"></i></a>
                                </div>

                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-dark mb-0 lh-sm pe-2" style="font-size: 1.05rem;">{{ $item->title }}</h5>
                                        <span class="badge pharma-tag-pill shadow-xs">{{ $item->tag ?? 'FDA' }}</span>
                                    </div>
                                    <p class="text-muted small mb-3 italic">({{ $item->sub_title }})</p>

                                    <div class="clinical-box mb-4">
                                        <div class="mb-2">
                                            <label class="label-heading">INDICATION</label>
                                            <div class="content-text">{{ $item->indication ?? 'N/A' }}</div>
                                        </div>
                                        <div>
                                            <label class="label-heading">MOA</label>
                                            <div class="content-text">{{ Str::limit($item->moa, 60) }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-3 border-top">
                                        <a href="{{ $item->link ?? '#' }}" target="_blank"
                                            class="pharma-link text-decoration-none fw-bold small d-flex align-items-center justify-content-center">
                                            LEARN MORE <i class="fa-solid fa-arrow-up-right-from-square ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-5 text-center text-muted border border-dashed rounded-4 bg-white opacity-75">No
                            medications in this section.</div>
                    @endforelse

                    <div class="col-12 category-empty-msg" style="display: none;">
                        <div class="py-5 text-center bg-white rounded-4 border border-dashed">
                            <h6 class="text-muted">No data found in this category tab.</h6>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded border shadow-sm mx-1">
                <i class="fa-solid fa-folder-open text-muted opacity-50 mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted">No structure found.</h5>
                <a href="{{ route('admin.categories.psychology') }}" class="btn btn-outline-primary mt-2">Go to Category Management</a>
            </div>
        @endforelse

        <div class="mt-4 px-2">{{ $items->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- Add/Edit Modal --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.pharma.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0" style="border-radius: 20px;">
                @csrf
                <div class="modal-header border-0 bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase text-muted">Placement (Section > Tab)</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm border border-secondary-subtle" required>
                                <option value="">-- Select Specific Tab --</option>
                                @foreach ($sections as $sec)
                                    @php $tabsForModal = $sec->IndustryCategory->where('category_name', '!=', 'All'); @endphp
                                    @if ($tabsForModal->count() > 0)
                                        <optgroup label="{{ ucwords($sec->name) }}" data-section-id="{{ $sec->id }}">
                                            @foreach ($tabsForModal as $tab)
                                                <option value="{{ $tab->id }}">{{ $tab->category_name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            <div class="mt-2 p-2 bg-light rounded border border-light-subtle">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                    <strong>Note:</strong> A section will only appear here if you have added at least one
                                    custom tab in Category Management.
                                </small>
                            </div>
                        </div>

                        <div class="col-md-6"><label class="form-label fw-bold small text-muted">Medication Name</label>
                            <input type="text" name="title" id="title" class="form-control border border-secondary-subtle shadow-none" required placeholder="e.g. Cobenfy">
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small text-muted">Badge/Tag</label>
                            <input type="text" name="tag" id="tag" class="form-control border border-secondary-subtle shadow-none" placeholder="e.g. FDA Approved">
                        </div>
                        <div class="col-md-12"><label class="form-label fw-bold small text-muted">Ingredients / Sub-title</label>
                            <input type="text" name="sub_title" id="sub_title" class="form-control border border-secondary-subtle shadow-none" placeholder="e.g. xanomeline + trospium">
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small text-muted">Indication</label>
                            <input type="text" name="indication" id="indication" class="form-control border border-secondary-subtle shadow-none" placeholder="Specific use case...">
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small text-muted">Mechanism of Action (MOA)</label>
                            <input type="text" name="moa" id="moa" class="form-control border border-secondary-subtle shadow-none" placeholder="How it works...">
                        </div>
                        <div class="col-md-12"><label class="form-label fw-bold small text-muted">Learn More Link</label>
                            <input type="url" name="link" id="link" class="form-control border border-secondary-subtle shadow-none" placeholder="https://...">
                        </div>
                        <div class="col-md-12"><label class="form-label fw-bold small text-muted">Short Description</label>
                            <textarea name="description" id="description" class="form-control border border-secondary-subtle shadow-none" rows="2" placeholder="Summary notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow-sm rounded-3">Save Medication Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .pharma-premium-card { border-radius: 20px !important; border: 1px solid #f1f5f9 !important; transition: 0.3s; background: #fff; overflow: visible; }
        .pharma-premium-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08) !important; border-color: #0ea5e9 !important; }

        .pharma-tag-pill { background-color: #f0f9ff !important; color: #0ea5e9 !important; border: 1px solid #e0f2fe !important; border-radius: 30px !important; padding: 5px 12px !important; font-size: 0.62rem !important; font-weight: 800 !important; text-transform: uppercase; }

        .clinical-box { background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #f1f5f9; }
        .label-heading { font-size: 0.6rem; font-weight: 800; color: #94a3b8; letter-spacing: 1px; display: block; margin-bottom: 4px; }
        .content-text { font-size: 0.88rem; color: #334155; font-weight: 500; line-height: 1.4; }

        .compact-tab-bar .nav-link { font-size: 0.8rem; padding: 8px 18px; border-radius: 30px; border: none; background: none; color: #64748b; }
        .compact-tab-bar .nav-link.active { background: #1e293b !important; color: #fff !important; font-weight: 600; }

        .admin-btns { position: absolute; top: 15px; right: 15px; z-index: 10; opacity: 0; transition: 0.3s; }
        .pharma-premium-card:hover .admin-btns { opacity: 1; }
        .btn-white { background: #fff; border: 1px solid #e2e8f0; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }

        .pharma-link { color: #0ea5e9 !important; font-size: 0.75rem; letter-spacing: 0.5px; }
        .italic { font-style: italic; } .border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #cbd5e1 !important; }
        .shadow-xs { box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    </style>

    @push('scripts')
        <script>
            function filterPharma(btn, filterClass) {
                const section = btn.closest('.pharma-section-block');
                section.querySelectorAll('.btn-filter').forEach(b => { b.classList.remove('active', 'bg-dark', 'text-white'); });
                btn.classList.add('active', 'bg-dark', 'text-white');
                const cards = section.querySelectorAll('.product-card'); const emptyMsg = section.querySelector('.category-empty-msg');
                let count = 0; cards.forEach(card => { if (filterClass === 'all' || card.classList.contains(filterClass)) { card.style.display = 'block'; count++; } else { card.style.display = 'none'; } });
                emptyMsg.style.display = (count === 0) ? 'block' : 'none';
            }

            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');
                const categorySelect = document.getElementById('category_id');
                const optgroups = categorySelect.querySelectorAll('optgroup');

                window.openModal = (sectionId = null, sectionName = null) => {
                    itemForm.reset(); document.getElementById('item_id').value = "";
                    if (sectionId) {
                        document.getElementById('modalTitle').innerText = "Add Medication to: " + sectionName;
                        optgroups.forEach(group => {
                            if (group.getAttribute('data-section-id') == sectionId) {
                                group.hidden = false; group.disabled = false;
                            } else {
                                group.hidden = true; group.disabled = true;
                            }
                        });
                    } else {
                        document.getElementById('modalTitle').innerText = "Add Medication ({{ ucfirst($network) }})";
                        optgroups.forEach(group => { group.hidden = false; group.disabled = false; });
                    }
                    itemModal.show();
                }

                window.editItem = (data) => {
                    optgroups.forEach(group => { group.hidden = false; group.disabled = false; });
                    document.getElementById('modalTitle').innerText = "Edit Medication Data";
                    document.getElementById('item_id').value = data.id;
                    document.getElementById('category_id').value = data.category_id;
                    document.getElementById('title').value = data.title;
                    document.getElementById('sub_title').value = data.sub_title;
                    document.getElementById('tag').value = data.tag;
                    document.getElementById('indication').value = data.indication;
                    document.getElementById('moa').value = data.moa;
                    document.getElementById('link').value = data.link;
                    document.getElementById('description').value = data.description;
                    itemModal.show();
                }
            });
        </script>
    @endpush
@endsection
