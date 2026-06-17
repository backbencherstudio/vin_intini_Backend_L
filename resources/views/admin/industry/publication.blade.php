@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4 pb-5">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">
                    <span class="text-primary">{{ ucfirst($network) }}</span> Publications
                </h4>
                <p class="text-muted small mb-0">Managing Research Articles & Journals in table view.</p>
            </div>
            <button class="btn btn-primary px-3 fw-bold btn-sm shadow-sm" onclick="openModal()">
                <i class="fa-solid fa-plus me-1"></i> Add Publication
            </button>
        </div>

        {{-- Filter Card --}}
        <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 10px;">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control form-select-sm shadow-none border"
                        placeholder="Search by article title..." value="{{ request('search') }}">
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

        {{-- Publications Table --}}
        <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 12px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4 py-3">Article Title & Summary</th>
                                <th>Badge</th>
                                <th>Meta (Date / Footer)</th>
                                <th>Reference</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="bg-white">
                                    {{-- বাম পাশের কালার স্ট্রাইপ সহ কলাম --}}
                                    <td class="ps-4 py-3 {{ $network == 'psychology' ? 'stripe-psychology' : 'stripe-neuroscience' }}"
                                        style="max-width: 400px;">
                                        <div class="fw-bold text-dark h6 mb-1">{{ $item->title }}</div>
                                        <div class="text-muted small lh-sm">
                                            {{ Str::limit($item->description, 100) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1"
                                            style="font-size: 0.65rem;">
                                            {{ $item->tag ?? 'JOURNAL' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-dark fw-bold mb-1">
                                            <i class="fa-regular fa-calendar-check text-muted me-1"></i>
                                            {{ $item->pub_date ?? 'N/A' }}
                                        </div>
                                        <div class="small text-muted italic text-truncate" style="max-width: 200px;"
                                            title="{{ $item->extra_tag }}">
                                            <i class="fa-solid fa-quote-left me-1"
                                                style="font-size: 0.6rem; opacity: 0.5;"></i>
                                            {{ Str::limit($item->extra_tag, 40) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($item->link)
                                            <a href="{{ $item->link }}" target="_blank"
                                                class="btn btn-link btn-sm p-0 text-info text-decoration-none fw-bold small">
                                                View DOI <i class="fa-solid fa-arrow-up-right-from-square ms-1"
                                                    style="font-size: 0.7rem;"></i>
                                            </a>
                                        @else
                                            <span class="text-muted small italic">No link</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm border rounded">
                                            <button class="btn btn-sm btn-white text-warning border-end px-3"
                                                onclick="editItem({{ $item }})" title="Edit"><i
                                                    class="fa fa-pen"></i></button>
                                            <a href="{{ route('admin.publications.delete', $item->id) }}"
                                                class="btn btn-sm btn-white text-danger px-3"
                                                onclick="return confirm('Delete publication?')" title="Delete"><i
                                                    class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-file-circle-exclamation mb-2 d-block fs-3"></i>
                                        No publications found for this search.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    {{-- Add/Edit Modal --}}
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.publications.store') }}" method="POST" id="itemForm"
                class="modal-content shadow-lg border-0" style="border-radius: 15px;">
                @csrf
                <div class="modal-header border-0 bg-light px-4 py-3">
                    <h5 class="modal-title fw-bold" id="modalTitle">Manage Publication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <input type="hidden" name="network_type" value="{{ $network }}">

                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-uppercase text-muted">Article / Journal
                                Title</label>
                            <input type="text" name="title" id="title"
                                class="form-control border border-secondary-subtle shadow-none" required
                                placeholder="e.g. Psychological Science">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Badge (e.g. TMS)</label>
                            <input type="text" name="tag" id="tag"
                                class="form-control border border-secondary-subtle shadow-none" placeholder="e.g. CNS">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase text-muted">Brief Summary /
                                Abstract</label>
                            <textarea name="description" id="description" class="form-control border border-secondary-subtle shadow-none"
                                rows="3" placeholder="Key summary of the research findings..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Publication Date</label>
                            <input type="text" name="pub_date" id="pub_date"
                                class="form-control border border-secondary-subtle shadow-none"
                                placeholder="e.g. Feb 2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted">Footer Note (Extra
                                Meta)</label>
                            <input type="text" name="extra_tag" id="extra_tag"
                                class="form-control border border-secondary-subtle shadow-none"
                                placeholder="e.g. Most downloaded article of 2025">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase text-muted">Reference Link (DOI /
                                URL)</label>
                            <input type="url" name="link" id="link"
                                class="form-control border border-secondary-subtle shadow-none"
                                placeholder="https://doi.org/...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow-sm"
                        style="border-radius: 10px;">Save Publication Information</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* টেবিলের বাম পাশের বর্ডার লাইন */
        .stripe-psychology {
            border-left: 4px solid #007bff !important;
        }

        .stripe-neuroscience {
            border-left: 4px solid #00cba9 !important;
        }

        .btn-white {
            background: #fff;
        }

        .btn-white:hover {
            background: #f8f9fa;
        }

        .italic {
            font-style: italic;
        }

        /* মোডালের ইনপুট বর্ডার স্টাইল */
        .form-control {
            border: 1px solid #ced4da !important;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #007bff !important;
        }

        .table thead th {
            border: none !important;
            color: #666;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: background 0.2s;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');
                window.openModal = () => {
                    document.getElementById('modalTitle').innerText = "Add Publication ({{ ucfirst($network) }})";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }
                window.editItem = (data) => {
                    document.getElementById('modalTitle').innerText = "Edit Article";
                    document.getElementById('item_id').value = data.id;
                    document.getElementById('title').value = data.title;
                    document.getElementById('tag').value = data.tag;
                    document.getElementById('description').value = data.description;
                    document.getElementById('pub_date').value = data.pub_date;
                    document.getElementById('extra_tag').value = data.extra_tag;
                    document.getElementById('link').value = data.link;
                    itemModal.show();
                }
            });
        </script>
    @endpush
@endsection
