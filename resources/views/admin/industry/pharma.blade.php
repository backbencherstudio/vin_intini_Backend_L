{{-- @extends('admin.industry.layouts') --}}
@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Dynamic Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Psychopharmacology
                </h4>
                <p class="text-muted small mb-0">Managing Medications and clinical data for {{ $network }} network.</p>
            </div>
            <button class="btn btn-success px-3 fw-bold btn-sm shadow-sm" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Medication
            </button>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 10px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-select-sm"
                        placeholder="Search by medication name..." value="{{ request('search') }}">
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

        {{-- Content Rendering --}}
        @forelse ($sections as $section)
            <div class="pharma-section mb-5">
                <div class="mb-4">
                    <h6
                        class="fw-bold text-dark d-inline-block border-bottom border-primary border-3 pb-1 text-uppercase small">
                        {{ $section->name }}
                    </h6>
                </div>

                <div class="row g-2">
                    @php
                        $catIds = $section->IndustryCategory->pluck('id')->toArray();
                        $sectionItems = $items->whereIn('category_id', $catIds);
                    @endphp

                    @forelse ($sectionItems as $item)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100 pharma-ui-card border-0 shadow-sm position-relative">
                                <div class="admin-btns">
                                    <button class="btn btn-xs btn-white shadow-sm text-warning"
                                        onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                                    <a href="{{ route('admin.pharma.delete', $item->id) }}"
                                        class="btn btn-xs btn-white shadow-sm text-danger ms-1"
                                        onclick="return confirm('Delete?')"><i class="fa fa-trash"></i></a>
                                </div>

                                <div class="card-body p-3 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold text-dark mb-0 pe-1 text-truncate" style="font-size: 0.9rem;"
                                            title="{{ $item->title }}">{{ $item->title }}</h6>
                                        <span class="badge pharma-tag-badge">{{ $item->tag ?? 'Approved' }}</span>
                                    </div>
                                    <p class="text-muted mb-3 x-small text-truncate">({{ $item->sub_title }})</p>
                                    <div class="pharma-details mb-3">
                                        <div class="mb-2">
                                            <span class="detail-label text-uppercase">Indication:</span>
                                            <span class="detail-value">{{ Str::limit($item->indication, 35) }}</span>
                                        </div>
                                        <div>
                                            <span class="detail-label text-uppercase">MOA:</span>
                                            <span class="detail-value">{{ Str::limit($item->moa, 35) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-auto pt-2 border-top">
                                        <a href="{{ $item->link ?? '#' }}" target="_blank"
                                            class="pharma-link text-decoration-none fw-bold d-flex align-items-center">
                                            Learn more <i class="fa-solid fa-arrow-up-right-from-square ms-1"
                                                style="font-size: 0.6rem;"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 py-3 text-center text-muted small border-dashed rounded">No medications in this
                            section for the current page.</div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="text-center py-5 bg-white rounded border">
                <h6 class="text-muted mb-3">No structure found for {{ ucfirst($network) }}.</h6>
                <a href="{{ route('admin.categories.' . $network) }}" class="btn btn-sm btn-primary px-3 fw-bold">Go Create
                    a Section First</a>
            </div>
        @endforelse

        <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
    </div>

    {{-- Add/Edit Modal --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.pharma.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0">
                @csrf
                <div class="modal-header border-0 bg-light px-4">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">
                        {{-- Section Selection Fix --}}
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Select Section Heading</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm" required>
                                <option value="">-- Choose Section --</option>
                                @foreach ($sections as $section)
                                    @php
                                        $defaultTab = $section->IndustryCategory
                                            ->where('category_name', 'All')
                                            ->first();
                                    @endphp
                                    @if ($defaultTab)
                                        <option value="{{ $defaultTab->id }}">
                                            {{ ucwords($section->name) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.65rem;">Note: Sections are managed in the Category
                                Management section.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Medication Name</label>
                            <input type="text" name="title" id="title" class="form-control shadow-sm" required
                                placeholder="e.g. Cobenfy">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Tag</label>
                            <input type="text" name="tag" id="tag" class="form-control shadow-sm"
                                placeholder="e.g. FDA Approved, OTC">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Ingredients / Sub-title</label>
                            <input type="text" name="sub_title" id="sub_title" class="form-control shadow-sm"
                                placeholder="e.g. xanomeline + trospium">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Indication</label>
                            <input type="text" name="indication" id="indication" class="form-control shadow-sm"
                                placeholder="e.g. Schizophrenia">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Mechanism of Action (MOA)</label>
                            <input type="text" name="moa" id="moa" class="form-control shadow-sm"
                                placeholder="e.g. Muscarinic M1/M4 Agonist">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Learn More Link (URL)</label>
                            <input type="url" name="link" id="link" class="form-control shadow-sm"
                                placeholder="https://example.com/medication-info">
                        </div>

                        {{-- <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Image Upload (Standard Storage)</label>
                            <input type="file" name="image" class="form-control shadow-sm border-light">
                        </div> --}}

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Short Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control shadow-sm" rows="2"
                                placeholder="Briefly describe the medication summary..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Medication
                        Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .x-small {
            font-size: 0.65rem;
        }

        .pharma-ui-card {
            border-radius: 14px !important;
            border: 1px solid #f0f3f5 !important;
            background: #fff;
            transition: 0.2s;
        }

        .pharma-ui-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.06) !important;
            border-color: #00bcd4 !important;
        }

        .pharma-tag-badge {
            background-color: #e6f7f7 !important;
            color: #00bcd4 !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            font-size: 0.6rem !important;
            padding: 4px 10px !important;
        }

        .detail-label {
            color: #8898aa;
            font-size: 0.65rem;
            font-weight: 700;
            display: block;
            margin-bottom: 1px;
        }

        .detail-value {
            color: #444;
            font-size: 0.78rem;
            font-weight: 500;
            display: block;
        }

        .pharma-link {
            color: #00bcd4 !important;
            font-size: 0.78rem;
        }

        .admin-btns {
            position: absolute;
            top: 8px;
            right: 8px;
            z-index: 10;
            opacity: 0;
            transition: 0.3s;
        }

        .pharma-ui-card:hover .admin-btns {
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

        .italic {
            font-style: italic;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');
                window.openModal = () => {
                    document.getElementById('modalTitle').innerText = "Add Medication ({{ ucfirst($network) }})";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }
                window.editItem = (data) => {
                    document.getElementById('modalTitle').innerText = "Edit: " + data.title;
                    document.getElementById('item_id').value = data.id;
                    document.getElementById('category_id').value = data.category_id;
                    document.getElementById('title').value = data.title;
                    document.getElementById('tag').value = data.tag;
                    document.getElementById('sub_title').value = data.sub_title;
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
