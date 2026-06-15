{{-- @extends('admin.industry.layouts') --}}
@extends('admin.layout')

@section('content')
<div class="container-fluid mt-4 pb-5">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">
                <span class="text-primary">{{ ucfirst($network) }}</span> Publications
            </h4>
            <p class="text-muted small mb-0">Managing Research Articles & Journals for the {{ $network }} network.</p>
        </div>
        <button class="btn btn-primary px-3 fw-bold btn-sm shadow-sm" onclick="openModal()">
            <i class="fa-solid fa-plus me-1"></i> Add Publication
        </button>
    </div>

    {{-- Filter Card --}}
    <div class="card shadow-sm border-0 mb-4 p-2 bg-white" style="border-radius: 10px;">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-select-sm" placeholder="Search article title..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-secondary w-100">Search</button>
            </div>
            <div class="col-md-1 text-center">
                <a href="{{ url()->current() }}" class="btn btn-sm btn-link text-decoration-none small text-muted">Reset</a>
            </div>
        </form>
    </div>

    {{-- Publications Grid (6 per row) --}}
    <div class="row g-2">
        @forelse ($items as $item)
            <div class="col-xl-2 col-lg-3 col-md-4 col-6">
                <div class="card h-100 pub-mini-card border-0 shadow-sm position-relative">

                    {{-- Admin Actions --}}
                    <div class="admin-btns">
                        <button class="btn btn-xs btn-white shadow-sm text-warning" onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                        <a href="{{ route('admin.publications.delete', $item->id) }}" class="btn btn-xs btn-white shadow-sm text-danger ms-1" onclick="return confirm('Delete publication?')"><i class="fa fa-trash"></i></a>
                    </div>

                    <div class="card-body p-3 d-flex flex-column text-start">
                        <div class="text-end mb-2">
                            <span class="badge bg-light text-muted border-0 px-1" style="font-size: 0.55rem;">{{ $item->tag ?? 'JOURNAL' }}</span>
                        </div>

                        <h6 class="fw-bold text-dark mb-2 lh-sm text-truncate-2" style="font-size: 0.85rem; min-height: 2.4em;">{{ $item->title }}</h6>

                        <p class="text-muted mb-3 x-small lh-sm text-truncate-3" style="min-height: 3.6em; font-size: 0.72rem;">
                            {{ $item->description }}
                        </p>

                        <div class="mt-auto pt-2 border-top">
                            <div class="x-small text-dark fw-bold mb-1" style="font-size: 0.65rem;"><i class="fa-regular fa-calendar-days me-1"></i> {{ $item->pub_date }}</div>
                            <div class="x-small text-truncate italic text-muted mb-2" title="{{ $item->extra_tag }}" style="font-size: 0.65rem;">{{ Str::limit($item->extra_tag, 30) }}</div>

                            <a href="{{ $item->link ?? '#' }}" target="_blank" class="text-info text-decoration-none fw-bold x-small d-block">Learn more <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.6rem;"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 bg-white rounded border">
                <i class="fa-solid fa-file-invoice text-light-emphasis d-block mb-2" style="font-size: 3rem;"></i>
                <h6 class="text-muted">No publications added yet for {{ ucfirst($network) }} network.</h6>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $items->links('pagination::bootstrap-5') }}</div>
</div>

{{-- Add/Edit Modal (Simplified) --}}
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.publications.store') }}" method="POST" id="itemForm" class="modal-content shadow-lg border-0">
            @csrf
            <div class="modal-header border-0 bg-light px-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Manage Publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="item_id" id="item_id">
                <input type="hidden" name="network_type" value="{{ $network }}">

                <div class="row g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold small text-uppercase">Article Title</label>
                        <input type="text" name="title" id="title" class="form-control shadow-sm border-light" required placeholder="e.g. Psychological Science">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-uppercase">Badge (e.g. TMS)</label>
                        <input type="text" name="tag" id="tag" class="form-control shadow-sm border-light" placeholder="e.g. CNS">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Brief Summary / Abstract</label>
                        <textarea name="description" id="description" class="form-control shadow-sm border-light" rows="3" placeholder="Key summary of the research article..."></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Publication Date</label>
                        <input type="text" name="pub_date" id="pub_date" class="form-control shadow-sm border-light" placeholder="e.g. Feb 2026">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Footer Note (Meta)</label>
                        <input type="text" name="extra_tag" id="extra_tag" class="form-control shadow-sm border-light" placeholder="e.g. Most downloaded article of 2025">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Reference Link (DOI / URL)</label>
                        <input type="url" name="link" id="link" class="form-control shadow-sm border-light" placeholder="https://doi.org/10.1177/...">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Publication Information</button>
            </div>
        </form>
    </div>
</div>

<style>
    .x-small { font-size: 0.65rem; }
    .pub-mini-card { border-radius: 12px !important; border: 1px solid #f2f2f2 !important; transition: 0.25s; background: #fff; }
    .pub-mini-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.06) !important; border-color: #0d6efd !important; }
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .text-truncate-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
    .admin-btns { position: absolute; top: 5px; right: 5px; z-index: 10; opacity: 0; transition: 0.3s; }
    .pub-mini-card:hover .admin-btns { opacity: 1; }
    .btn-xs { padding: 1px 5px; font-size: 0.6rem; border-radius: 4px; background: white; border: 1px solid #eee; }
    .italic { font-style: italic; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        const itemForm = document.getElementById('itemForm');
        window.openModal = () => { document.getElementById('modalTitle').innerText = "Add Publication ({{ ucfirst($network) }})"; itemForm.reset(); document.getElementById('item_id').value = ""; itemModal.show(); }
        window.editItem = (data) => {
            document.getElementById('modalTitle').innerText = "Edit Article";
            document.getElementById('item_id').value = data.id; document.getElementById('title').value = data.title;
            document.getElementById('tag').value = data.tag; document.getElementById('description').value = data.description;
            document.getElementById('pub_date').value = data.pub_date; document.getElementById('extra_tag').value = data.extra_tag;
            document.getElementById('link').value = data.link; itemModal.show();
        }
    });
</script>
@endpush
@endsection
