{{-- @extends('admin.industry.layouts') --}}
@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Biotechnology
                </h4>
                <p class="text-muted small mb-0">Manage equipment using sections and tabs.</p>
            </div>
            <button class="btn btn-primary px-3 fw-bold btn-sm shadow-sm" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Product
            </button>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 10px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-select-sm"
                        placeholder="Search by title..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-secondary w-100">Search</button>
                </div>
                <div class="col-md-1 text-center">
                    <a href="{{ url()->current() }}"
                        class="btn btn-sm btn-link text-decoration-none small text-muted">Reset</a>
                </div>
            </form>
        </div>

        {{-- Section Content --}}
        @forelse ($sections as $section)
            <div class="biotech-section mb-5">
                <div class="mb-3">
                    <h6
                        class="fw-bold text-dark d-inline-block border-bottom border-primary border-3 pb-1 text-uppercase small">
                        {{ $section->name }}</h6>
                </div>

                {{-- Tabs --}}
                <div class="mb-3">
                    <ul class="nav nav-pills compact-tab-bar p-1 bg-white border rounded shadow-sm flex-nowrap overflow-auto"
                        style="width: fit-content;">
                        <li class="nav-item">
                            <button class="nav-link active btn-filter" onclick="filterByJS(this, 'all')">All</button>
                        </li>
                        @foreach ($section->IndustryCategory as $cat)
                            @if ($cat->category_name !== 'All')
                                <li class="nav-item">
                                    <button class="nav-link text-muted btn-filter"
                                        onclick="filterByJS(this, 'cat-{{ $cat->id }}')">{{ $cat->category_name }}</button>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>

                {{-- Cards Grid (6 per row) --}}
                <div class="row g-2">
                    @php
                        $catIds = $section->IndustryCategory->pluck('id')->toArray();
                        $sectionItems = $items->whereIn('category_id', $catIds);
                    @endphp
                    @forelse ($sectionItems as $item)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-6 product-card cat-{{ $item->category_id }}">
                            <div class="card h-100 biotech-mini-card border-0 shadow-sm position-relative">
                                <div class="admin-btns">
                                    <button class="btn btn-xs btn-white shadow-sm text-warning"
                                        onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                                    <a href="{{ route('admin.biotech.delete', $item->id) }}"
                                        class="btn btn-xs btn-white shadow-sm text-danger ms-1"
                                        onclick="return confirm('Delete?')"><i class="fa fa-trash"></i></a>
                                </div>
                                <div class="card-img-box bg-light d-flex align-items-center justify-content-center border-bottom overflow-hidden"
                                    style="height: 100px; padding: 5px;">
                                    @if ($item->image)
                                        <img src="{{ asset('storage/' . $item->image) }}" class="img-fluid"
                                            style="max-height: 100%; width: 100%; object-fit: contain;">
                                    @else
                                        <i class="fa-regular fa-image text-muted opacity-30" style="font-size: 1.5rem;"></i>
                                    @endif
                                </div>
                                <div class="card-body p-2 d-flex flex-column text-center">
                                    <h6 class="fw-bold text-dark mb-1 text-truncate" style="font-size: 0.8rem;"
                                        title="{{ $item->title }}">{{ $item->title }}</h6>
                                    <p class="text-primary x-small mb-1 text-truncate fw-semibold">{{ $item->sub_title }}
                                    </p>
                                    <div class="mt-auto pt-1 border-top">
                                        <a href="{{ $item->link ?? '#' }}" target="_blank"
                                            class="text-info text-decoration-none fw-bold x-small">Learn more</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-3 text-center text-muted small border-dashed rounded">No items found in this
                            section for the current page.</div>
                    @endforelse
                    <div class="col-12 category-empty-msg" style="display: none;">
                        <div class="py-4 text-center bg-white rounded border border-dashed">
                            <p class="text-muted small">This tab is currently empty.</p>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded border">No structure found. Create sections in Category Management
                first.</div>
        @endforelse
    </div>

    {{-- Add/Edit Modal --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.biotech.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0">
                @csrf
                <div class="modal-header border-0 bg-light px-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Section Context
                                ({{ ucfirst($network) }})</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm" required>
                                <option value="">-- Select Section Headings & Tabs --</option>
                                @foreach ($sections as $section)
                                    <optgroup label="{{ ucwords($section->name) }}">
                                        @foreach ($section->IndustryCategory as $tab)
                                            <option value="{{ $tab->id }}">{{ $tab->category_name }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Product Title</label><input
                                type="text" name="title" id="title" class="form-control" required
                                placeholder="e.g. Magstim TMS"></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Subtitle / Company</label><input
                                type="text" name="sub_title" id="sub_title" class="form-control"
                                placeholder="e.g. Brain Products"></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Tag</label><input type="text"
                                name="tag" id="tag" class="form-control" placeholder="e.g. TMS"></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Learn More Link</label><input
                                type="url" name="link" id="link" class="form-control"
                                placeholder="https://..."></div>
                        <div class="col-md-6"><label class="form-label fw-bold small">Image</label><input type="file"
                                name="image" class="form-control shadow-none"></div>
                        <div class="col-md-12"><label class="form-label fw-bold small">Description</label>
                            <textarea name="description" id="description" class="form-control" rows="2"
                                placeholder="Brief description..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0"><button type="submit"
                        class="btn btn-success w-100 py-2 fw-bold shadow">Save Changes</button></div>
            </form>
        </div>
    </div>

    <style>
        .x-small {
            font-size: 0.65rem;
        }

        .biotech-mini-card {
            border-radius: 10px !important;
            border: 1px solid #eee !important;
            transition: 0.2s;
            background: #fff;
            overflow: hidden;
        }

        .biotech-mini-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.06) !important;
            border-color: #ccc !important;
        }

        .compact-tab-bar .nav-link {
            font-size: 0.72rem;
            padding: 5px 14px;
            border-radius: 6px;
            border: none;
            background: none;
        }

        .compact-tab-bar .nav-link.active {
            background: #222 !important;
            color: white !important;
            font-weight: bold;
        }

        .admin-btns {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10;
            opacity: 0;
            transition: 0.3s;
        }

        .biotech-mini-card:hover .admin-btns {
            opacity: 1;
        }

        .btn-xs {
            padding: 1px 5px;
            font-size: 0.6rem;
            border-radius: 4px;
            background: white;
            border: 1px solid #eee;
        }

        .border-dashed {
            border-style: dashed !important;
            border-width: 2px !important;
        }
    </style>

    @push('scripts')
        <script>
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
                window.openModal = () => {
                    document.getElementById('modalTitle').innerText = "Add Product ({{ ucfirst($network) }})";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }
                window.editItem = (data) => {
                    document.getElementById('modalTitle').innerText = "Edit: " + data.title;
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
