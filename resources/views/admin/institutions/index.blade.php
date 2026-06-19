<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Institutions</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Institution Management</h5>
            </div>
            <div class="card-body p-0">

                @if (session('success'))
                    <div class="alert alert-success m-3 alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">S.L.</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>State</th>
                                <th>Country</th>
                                <th>Website</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($institutions as $item)
                                <tr>

                                    <td class="ps-3">
                                        {{ ($institutions->currentPage() - 1) * $institutions->perPage() + $loop->iteration }}
                                    </td>

                                    <td><strong>{{ $item->name }}</strong></td>
                                    <td>{{ $item->type ?? 'N/A' }}</td>
                                    <td>{{ $item->state ?? 'N/A' }}</td>
                                    <td>{{ $item->country ?? 'N/A' }}</td>
                                    <td>
                                        @if ($item->website)
                                            <a href="{{ $item->website }}" target="_blank"
                                                class="btn btn-sm btn-link p-0 text-decoration-none">Visit</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                            data-id="{{ $item->id }}" data-name="{{ $item->name }}"
                                            data-type="{{ $item->type }}" data-state="{{ $item->state }}"
                                            data-country="{{ $item->country }}" data-website="{{ $item->website }}"
                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No institutions found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer bg-white pt-3">
                <div class="d-flex justify-content-center">
                    {!! $institutions->links('pagination::bootstrap-5') !!}
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Update Institution</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Institution Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Institution Type</label>
                            <input type="text" name="type" id="edit_type" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">State</label>
                                <input type="text" name="state" id="edit_state" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Country</label>
                                <input type="text" name="country" id="edit_country" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Website URL</label>
                            <input type="url" name="website" id="edit_website" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.edit-btn').on('click', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const type = $(this).data('type');
                const state = $(this).data('state');
                const country = $(this).data('country');
                const website = $(this).data('website');

                $('#edit_name').val(name);
                $('#edit_type').val(type);
                $('#edit_state').val(state);
                $('#edit_country').val(country);
                $('#edit_website').val(website);

                let url = "{{ route('institutions.update', ':id') }}";
                url = url.replace(':id', id);
                $('#editForm').attr('action', url);
            });
        });
    </script>

</body>

</html>
