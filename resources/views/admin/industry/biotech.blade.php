@extends('admin.industry.layouts')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <h5 class="mb-0 fw-bold text-dark">Biotech Products</h5>
                    </div>

                    <!-- Advanced Filtering & Search -->
                    <div class="col-md-8">
                        <form action="{{ route('admin.biotech.index') }}" method="GET" class="row g-2">
                            <!-- Network Filter -->
                            <div class="col-md-2">
                                <select name="network" class="form-select form-select-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <option value="">Network</option>
                                    <option value="psychology" {{ request('network') == 'psychology' ? 'selected' : '' }}>
                                        Psychology</option>
                                    <option value="neuroscience"
                                        {{ request('network') == 'neuroscience' ? 'selected' : '' }}>Neuroscience</option>
                                </select>
                            </div>

                            <!-- Section Filter -->
                            <div class="col-md-3">
                                <select name="section" class="form-select form-select-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <option value="">All Sections</option>
                                    @foreach ($sections as $sec)
                                        <option value="{{ $sec }}"
                                            {{ request('section') == $sec ? 'selected' : '' }}>
                                            {{ ucwords($sec) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Category Dropdown -->
                            <div class="col-md-3">
                                <select name="category" class="form-select form-select-sm shadow-sm"
                                    onchange="this.form.submit()">
                                    <option value="">All Tabs/Categories</option>
                                    @foreach ($categories->groupBy('section_name') as $sectionName => $tabs)
                                        <optgroup label="{{ ucwords($sectionName) }}">
                                            @foreach ($tabs as $tab)
                                                <option value="{{ $tab->id }}"
                                                    {{ request('category') == $tab->id ? 'selected' : '' }}>
                                                    {{ $tab->category_name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group input-group-sm shadow-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Search..."
                                        value="{{ request('search') }}">
                                    <button class="btn btn-secondary" type="submit"><i class="fa fa-search"></i></button>
                                </div>
                            </div>

                            <div class="col-md-1 text-center">
                                <a href="{{ route('admin.biotech.index') }}"
                                    class="btn btn-sm btn-link text-decoration-none p-0 mt-1 small">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-2 text-end">
                        <button class="btn btn-primary btn-sm shadow-sm fw-bold" onclick="openModal()">+ Add
                            Product</button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small fw-bold">
                            <tr>
                                <th>Network & Section</th>
                                <th>Category Tab</th>
                                <th class="ps-4">Image</th>
                                <th>Product Details</th>
                                <th>Description</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        <span
                                            class="badge {{ $item->IndustryCategory->network_type == 'neuroscience' ? 'bg-teal-subtle text-teal' : 'bg-purple-subtle text-purple' }} border px-2 py-1 mb-1">
                                            {{ ucfirst($item->IndustryCategory->network_type) }}
                                        </span>
                                        <div class="small fw-semibold text-muted">
                                            {{ $item->IndustryCategory->section_name }}</div>
                                    </td>
                                    <td><span
                                            class="badge bg-light text-dark border px-2">{{ $item->IndustryCategory->category_name }}</span>
                                    </td>

                                    <td class="ps-4">
                                        @if ($item->image)
                                            <img src="{{ asset('uploads/industry/' . $item->image) }}"
                                                class="rounded border shadow-sm" width="55" height="40"
                                                style="object-fit: cover;">
                                        @else
                                            <div class="bg-light rounded border text-center pt-2"
                                                style="width:55px; height:40px;"><i class="fa fa-image text-muted"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->title }}</div>
                                        <div class="small text-muted">{{ Str::limit($item->sub_title, 30) }} | <span
                                                class="text-info">{{ $item->tag }}</span></div>
                                    </td>

                                    <!--  (Shortened to 50 chars) -->
                                    <td>
                                        <small class="text-muted" title="{{ $item->description }}" style="cursor: help;">
                                            {{ Str::limit($item->description, 50, '...') }}
                                        </small>
                                    </td>


                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-light border shadow-sm"
                                            onclick="editItem({{ $item }})"><i
                                                class="fa fa-pen text-warning"></i></button>
                                        <a href="{{ route('admin.biotech.delete', $item->id) }}"
                                            class="btn btn-sm btn-light border ms-1 shadow-sm"
                                            onclick="return confirm('Delete this product?')"><i
                                                class="fa fa-trash text-danger"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No biotechnology products found
                                        matching your criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 border-0">
                {{ $items->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.biotech.store') }}" method="POST" id="itemForm" enctype="multipart/form-data"
                class="modal-content shadow-lg border-0">
                @csrf
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold" id="modalTitle">Add Biotech Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="item_id" id="item_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Category / Tab Selection</label>
                            <select name="category_id" id="category_id" class="form-select shadow-sm" required>
                                <option value="">-- Select Tab --</option>
                                @foreach ($categories->groupBy('section_name') as $section => $tabs)
                                    <optgroup label="{{ ucwords($section) }}"> 
                                        @foreach ($tabs as $tab)
                                            <option value="{{ $tab->id }}">
                                                {{ ucfirst($tab->network_type) }} — {{ $tab->category_name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Product Title</label>
                            <input type="text" name="title" id="title" class="form-control shadow-sm" required
                                placeholder="e.g. Magstim TMS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Company / Sub-title</label>
                            <input type="text" name="sub_title" id="sub_title" class="form-control shadow-sm"
                                placeholder="e.g. Magstim/ Neurosoft">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Product Tag</label>
                            <input type="text" name="tag" id="tag" class="form-control shadow-sm"
                                placeholder="e.g. TMS, FDA Approved">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Image Upload</label>
                            <input type="file" name="image" class="form-control shadow-sm">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase">Learn More Link (URL)</label>
                            <input type="url" name="link" id="link" class="form-control shadow-sm"
                                placeholder="https://example.com">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-uppercase">Short Description</label>
                            <textarea name="description" id="description" class="form-control shadow-sm" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow">Save Product
                        Information</button>
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
    </style>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
                const itemForm = document.getElementById('itemForm');

                window.openModal = function() {
                    document.getElementById('modalTitle').innerText = "Add Biotech Product";
                    itemForm.reset();
                    document.getElementById('item_id').value = "";
                    itemModal.show();
                }

                window.editItem = function(data) {
                    document.getElementById('modalTitle').innerText = "Edit Product: " + data.title;
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
