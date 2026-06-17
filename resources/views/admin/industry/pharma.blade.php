@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header Section --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Psychotropics
                </h3>
                <p class="text-muted small mb-0">Managing Medications and clinical data for the community.</p>
            </div>
            <button class="btn btn-success px-4 fw-bold btn-sm shadow-sm" onclick="openModal()" style="border-radius: 8px;">
                <i class="fa-solid fa-plus me-2"></i> Add Medication
            </button>
        </div>

        {{-- Global Search Filter --}}
        <div class="card shadow-sm border-0 mb-5 p-2 bg-white" style="border-radius: 12px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm bg-light rounded border border-light px-2 overflow-hidden">
                        <span class="input-group-text border-0 bg-transparent text-muted"><i
                                class="fa fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-transparent"
                            placeholder="Search medication..." value="{{ request('search') }}">
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

        {{-- Section Wise View --}}
        @forelse ($sections as $section)
            <div class="pharma-section-block mb-5" data-section="{{ Str::slug($section->name) }}">

                <div class="mb-3 d-flex align-items-center">
                    <h5 class="fw-bold text-dark mb-0 text-uppercase" style="letter-spacing: 0.5px;">{{ $section->name }}
                    </h5>
                    <hr class="flex-grow-1 ms-3 opacity-10">
                </div>

                @php
                    $customTabs = $section->IndustryCategory->where('category_name', '!=', 'All');
                @endphp

                @if ($customTabs->count() > 0)
                    <div class="mb-4">
                        <ul class="nav nav-pills compact-tab-bar p-1 bg-white border rounded shadow-sm flex-nowrap overflow-auto"
                            style="width: fit-content;">
                            <li class="nav-item">
                                <button class="nav-link active btn-filter" onclick="filterPharma(this, 'all')">All
                                    Articles</button>
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
                <div class="row g-4 product-grid">
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
                                        onclick="return confirm('Delete?')"><i class="fa fa-trash"></i></a>
                                </div>

                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold text-dark mb-0 lh-sm pe-2">{{ $item->title }}</h5>
                                        <span class="badge pharma-tag-pill">{{ $item->tag ?? 'Approved' }}</span>
                                    </div>
                                    <p class="text-muted small mb-3 italic">({{ $item->sub_title }})</p>

                                    <div class="clinical-box mb-4">
                                        <div class="mb-2"><label class="label-heading">INDICATION</label>
                                            <div class="content-text">{{ $item->indication ?? 'N/A' }}</div>
                                        </div>
                                        <div><label class="label-heading">MOA</label>
                                            <div class="content-text">{{ Str::limit($item->moa, 60) }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-auto pt-3 border-top text-center">
                                        <a href="{{ $item->link ?? '#' }}" target="_blank"
                                            class="pharma-link text-decoration-none fw-bold small d-flex align-items-center">
                                            LEARN MORE <i class="fa-solid fa-arrow-up-right-from-square ms-2"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-4 text-center text-muted border border-dashed rounded-4 bg-light">No
                            medications in this section on this page.</div>
                    @endforelse
                    <div class="col-12 category-empty-msg" style="display: none;">
                        <div class="py-5 text-center bg-white rounded border border-dashed">
                            <h6 class="text-muted">No data found in this category.</h6>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded border">No structure found. Go to Category Management first.</div>
        @endforelse

        <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- Add/Edit Modal (Filtered to block sections with only 'All' tab) --}}
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
                            <label class="form-label fw-bold small text-uppercase text-muted">Placement (Section > Category
                                Tab)</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm" required>
                                <option value="">-- Choose Category --</option>
                                @foreach ($sections as $section)
                                    @php
                                        $customTabs = $section->IndustryCategory->where('category_name', '!=', 'All');
                                    @endphp

                                    @if ($customTabs->count() > 0)
                                        <optgroup label="{{ ucwords($section->name) }}">
                                            @foreach ($customTabs as $tab)
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

                        <div class="col-md-6"><label class="form-label fw-bold small">Medication Name</label><input
                                type="text" name="title" id="title" class="form-control shadow-sm border-light"
                                required placeholder="e.g. Cobenfy"></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Tag</label><input type="text"
                                name="tag" id="tag" class="form-control shadow-sm border-light"
                                placeholder="e.g. FDA Approved, OTC"></div>
                        <div class="col-md-12"><label class="form-label fw-bold small">Ingredients /
                                Sub-title</label><input type="text" name="sub_title" id="sub_title"
                                class="form-control shadow-sm border-light" placeholder="e.g. xanomeline + trospium">
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Indication</label><input
                                type="text" name="indication" id="indication"
                                class="form-control shadow-sm border-light" placeholder="e.g. Schizophrenia"></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">MOA</label><input type="text"
                                name="moa" id="moa" class="form-control shadow-sm border-light"
                                placeholder="Mechanism of action..."></div>
                        <div class="col-md-12"><label class="form-label fw-bold small">Learn More Link</label><input
                                type="url" name="link" id="link" class="form-control shadow-sm border-light"
                                placeholder="https://..."></div>
                        <div class="col-md-12"><label class="form-label fw-bold small">Short Description</label>
                            <textarea name="description" id="description" class="form-control shadow-sm border-light" rows="2"
                                placeholder="Brief summary..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow">Save Medication
                        Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .pharma-premium-card {
            border-radius: 20px !important;
            border: 1px solid #f1f5f9 !important;
            transition: 0.3s;
            background: #fff;
        }

        .pharma-premium-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.06) !important;
            border-color: #0ea5e9 !important;
        }

        .pharma-tag-pill {
            background-color: #f0f9ff !important;
            color: #0ea5e9 !important;
            border-radius: 30px !important;
            padding: 5px 12px !important;
            font-size: 0.62rem !important;
            font-weight: 800 !important;
        }

        .clinical-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #f1f5f9;
        }

        .label-heading {
            font-size: 0.6rem;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 4px;
        }

        .content-text {
            font-size: 0.88rem;
            color: #334155;
            font-weight: 500;
            line-height: 1.4;
        }

        .pharma-link {
            color: #0ea5e9 !important;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .compact-tab-bar .nav-link {
            font-size: 0.8rem;
            padding: 8px 18px;
            border-radius: 8px;
            border: none;
            background: none;
        }

        .compact-tab-bar .nav-link.active {
            background: #1e293b !important;
            color: #fff !important;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .admin-btns {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
            opacity: 0;
            transition: 0.3s;
        }

        .pharma-premium-card:hover .admin-btns {
            opacity: 1;
        }

        .btn-white {
            background: #fff;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #f1f5f9;
        }

        .italic {
            font-style: italic;
        }

        .border-dashed {
            border-style: dashed !important;
            border-width: 2px !important;
            border-color: #e2e8f0 !important;
        }
    </style>

    @push('scripts')
        <script>
            function filterPharma(btn, filterClass) {
                const section = btn.closest('.pharma-section-block');
                section.querySelectorAll('.btn-filter').forEach(b => {
                    b.classList.remove('active', 'bg-dark', 'text-white');
                });
                btn.classList.add('active', 'bg-dark', 'text-white');
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
                window.openModal = () => {
                    document.getElementById('modalTitle').innerText = "Add Medication";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }
                window.editItem = (data) => {
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
