@extends('admin.industry.layouts')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <h5 class="mb-0 fw-bold text-dark">Pharma Management</h5>
                    </div>
                    <div class="col-md-8">
                        <form action="{{ route('admin.pharma.index') }}" method="GET" class="row g-2">
                            <div class="col-md-3">
                                <select name="network" class="form-select form-select-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <option value="">Network</option>
                                    <option value="psychology" {{ request('network') == 'psychology' ? 'selected' : '' }}>
                                        Psychology</option>
                                    <option value="neuroscience" {{ request('network') == 'neuroscience' ? 'selected' : '' }}>
                                        Neuroscience</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select name="section" class="form-select form-select-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <option value="">All Sections</option>
                                    @foreach ($sections as $sec)
                                        <option value="{{ $sec }}" {{ request('section') == $sec ? 'selected' : '' }}>
                                            {{ ucwords($sec) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm shadow-sm">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search medication..." value="{{ request('search') }}">
                                    <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                            <div class="col-md-1"><a href="{{ route('admin.pharma.index') }}"
                                    class="btn btn-sm btn-link text-decoration-none p-0 mt-1 small text-muted">Reset</a>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-primary btn-sm shadow-sm fw-bold" onclick="openModal()">+ Add
                            Medication</button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4">Medication Name</th>
                                <th>Clinical Details (Indication/MOA)</th>
                                <th>Network & Context</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="bg-white">
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark h6 mb-0">{{ $item->title }}</div>
                                        <div class="small text-muted">{{ $item->sub_title }}</div>
                                        <span class="badge bg-info-subtle text-info border-0 mt-1"
                                            style="font-size: 0.65rem;">{{ $item->tag }}</span>
                                    </td>
                                    <td>
                                        <div class="mb-1" style="font-size: 0.85rem;">
                                            <strong class="text-dark">Indication:</strong>
                                            <span class="text-muted">{{ $item->indication ?? 'Not set' }}</span>
                                        </div>
                                        <div style="font-size: 0.85rem;">
                                            <strong class="text-dark">MOA:</strong>
                                            <span class="text-muted">{{ $item->moa ?? 'Not set' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $item->IndustryCategory->network_type == 'neuroscience' ? 'bg-teal-subtle text-teal' : 'bg-purple-subtle text-purple' }} border px-2 py-1 mb-1">
                                            {{ ucfirst($item->IndustryCategory->network_type) }}
                                        </span>
                                        <div class="small fw-semibold text-muted">
                                            {{ $item->IndustryCategory->section_name }}
                                            @if ($item->IndustryCategory->category_name != 'All')
                                                — {{ $item->IndustryCategory->category_name }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group shadow-sm">
                                            <button class="btn btn-sm btn-light border text-warning px-3"
                                                onclick="editItem({{ $item }})"><i class="fa fa-pen"></i></button>
                                            <a href="{{ route('admin.pharma.delete', $item->id) }}"
                                                class="btn btn-sm btn-light border text-danger px-3"
                                                onclick="return confirm('Delete this medication?')"><i
                                                    class="fa fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No medications found matching
                                        your criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 py-3">{{ $items->links('pagination::bootstrap-5') }}</div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.pharma.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0">
                @csrf
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Select Section Context</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm" required>
                                <option value="">-- Select Section --</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">
                                        {{ ucfirst($cat->network_type) }} — {{ ucwords($cat->section_name) }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted" style="font-size: 0.7rem;">Headings are managed in the Category
                                Management section.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Medication Name</label>
                            <input type="text" name="title" id="title" class="form-control shadow-sm" required
                                placeholder="e.g. Cobenfy">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Product Tag</label>
                            <input type="text" name="tag" id="tag" class="form-control shadow-sm"
                                placeholder="e.g. FDA Approved">
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
                                placeholder="https://...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Short Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control shadow-sm" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Medication Info</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .bg-teal-subtle {
            background-color: #e6fffa;
        }

        .text-teal {
            color: #2c7a7b;
        }

        .bg-purple-subtle {
            background-color: #faf5ff;
        }

        .text-purple {
            color: #6b46c1;
        }

        .btn-light:hover {
            background-color: #f1f3f5;
        }
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');

                window.openModal = function() {
                    document.getElementById('modalTitle').innerText = "Add Medication";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }

                window.editItem = function(data) {
                    document.getElementById('modalTitle').innerText = "Edit Medication: " + data.title;
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
