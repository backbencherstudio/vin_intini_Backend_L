@extends('admin.industry.layouts')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <h4 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-book-open me-2 text-primary"></i> Publications</h4>
                </div>

                <div class="col-md-7">
                    <form action="{{ route('admin.publications.index') }}" method="GET" class="row g-2">
                        <div class="col-md-4">
                            <select name="network" class="form-select form-select-sm shadow-sm" onchange="this.form.submit()">
                                <option value="">All Networks</option>
                                <option value="psychology" {{ request('network') == 'psychology' ? 'selected' : '' }}>Psychology</option>
                                <option value="neuroscience" {{ request('network') == 'neuroscience' ? 'selected' : '' }}>Neuroscience</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-sm shadow-sm">
                                <input type="text" name="search" class="form-control" placeholder="Search title..." value="{{ request('search') }}">
                                <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('admin.publications.index') }}" class="btn btn-sm btn-link text-decoration-none small text-muted">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="col-md-2 text-end">
                    <button class="btn btn-primary btn-sm shadow-sm fw-bold px-3" onclick="openModal()">+ Add Publication</button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small fw-bold">
                        <tr>
                            <th class="ps-4">Article Details</th>
                            <th>Meta Information</th>
                            <th>Network</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                        <tr class="bg-white border-bottom">
                            <td class="ps-4 py-3">
                                <div class="fw-bold text-dark h6 mb-1">{{ $item->title }}</div>
                                <div class="text-muted small lh-sm">{{ Str::limit($item->description, 70) }}</div>
                                <span class="badge bg-secondary-subtle text-secondary border px-2 mt-1" style="font-size: 0.65rem;">{{ $item->tag ?? 'No Tag' }}</span>
                            </td>
                            <td>
                                <div class="small mb-1"><strong>Date:</strong> {{ $item->pub_date ?? 'N/A' }}</div>
                                <div class="small text-muted italic">{{ Str::limit($item->extra_tag, 45) }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $item->IndustryCategory->network_type=='neuroscience'?'bg-teal-subtle text-teal':'bg-purple-subtle text-purple' }} border px-3 py-1 rounded-pill fw-bold" style="font-size: 0.7rem;">
                                    {{ ucfirst($item->IndustryCategory->network_type) }}
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group shadow-sm">
                                    <button class="btn btn-sm btn-light border text-warning px-3" onclick="editItem({{ $item }}, '{{ $item->IndustryCategory->network_type }}')"><i class="fa fa-pen"></i></button>
                                    <a href="{{ route('admin.publications.delete', $item->id) }}" class="btn btn-sm btn-light border text-danger px-3" onclick="return confirm('Delete this publication?')"><i class="fa fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-5 text-muted">No publications found.</td></tr>
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

<!-- Modal -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.publications.store') }}" method="POST" id="itemForm" class="modal-content shadow-lg border-0">
            @csrf
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold" id="modalTitle">Add Publication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="item_id" id="item_id">

                <div class="row g-3">
                    <!-- Network Selection -->
                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Select Network</label>
                        <select name="network_type" id="network_type" class="form-select shadow-sm" required>
                            <option value="psychology">Psychology Network</option>
                            <option value="neuroscience">Neuroscience Network</option>
                        </select>
                    </div>

                    <div class="col-md-9">
                        <label class="form-label fw-bold small text-uppercase">Article / Journal Title</label>
                        <input type="text" name="title" id="title" class="form-control shadow-sm" required placeholder="e.g. Psychological Science">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-uppercase">Badge Tag (TMS/CNS)</label>
                        <input type="text" name="tag" id="tag" class="form-control shadow-sm" placeholder="e.g. TMS">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Summary / Description</label>
                        <textarea name="description" id="description" class="form-control shadow-sm" rows="3"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Publication Date</label>
                        <input type="text" name="pub_date" id="pub_date" class="form-control shadow-sm" placeholder="e.g. Feb 2026">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase">Footer Note (Extra Tag)</label>
                        <input type="text" name="extra_tag" id="extra_tag" class="form-control shadow-sm" placeholder="e.g. Most downloaded article of 2025">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold small text-uppercase">Learn More Link (URL)</label>
                        <input type="url" name="link" id="link" class="form-control shadow-sm" placeholder="https://...">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Publication</button>
            </div>
        </form>
    </div>
</div>

<style>
    .bg-teal-subtle { background-color: #e6fffa; } .text-teal { color: #2c7a7b; }
    .bg-purple-subtle { background-color: #faf5ff; } .text-purple { color: #6b46c1; }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        const itemForm = document.getElementById('itemForm');

        window.openModal = function() {
            document.getElementById('modalTitle').innerText = "Add Publication";
            itemForm.reset();
            document.getElementById('item_id').value = "";
            itemModal.show();
        }

        window.editItem = function(data, network) {
            document.getElementById('modalTitle').innerText = "Edit Publication: " + data.title;
            document.getElementById('item_id').value = data.id;
            document.getElementById('network_type').value = network; // নেটওয়ার্ক সেট করা
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
