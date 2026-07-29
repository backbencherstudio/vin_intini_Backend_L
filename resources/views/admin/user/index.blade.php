<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .img-thumbnail { width: 50px; height: 50px; object-fit: cover; }
    </style>
</head>

<body class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>User Management</h2>
    </div>

    <!-- Success Message -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <table class="table table-hover border shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Institution</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                @php
                    $edu = $user->educations->first();
                @endphp
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>
                        @if ($user->profile_image)
                            <img src="{{ asset('storage/' . $user->profile_image) }}" alt="User Image" class="img-thumbnail">
                        @else
                            <span class="badge bg-secondary">No Image</span>
                        @endif
                    </td>
                    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        {{ $edu->institution->name ?? 'N/A' }}
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm editBtn"
                            data-id="{{ $user->id }}"
                            data-fname="{{ $user->first_name }}"
                            data-lname="{{ $user->last_name }}"
                            data-email="{{ $user->email }}"
                            data-institution="{{ $edu->institution_id ?? '' }}"
                            data-degree="{{ $edu->degree ?? '' }}"
                            data-field="{{ $edu->field_study ?? '' }}"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">
                            Edit
                        </button>

                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('users.update') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Update User & Education Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="user_id">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="fname" id="user_fname" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="lname" id="user_lname" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="user_email" class="form-control" required>
                        </div>

                        <hr>
                        <h6 class="text-primary">Education Details</h6>

                        <div class="mb-3">
                            <label class="form-label">Institution</label>
                            <select name="institution_id" id="user_institution" class="form-select" required>
                                <option value="">-- Select Institution --</option>
                                @foreach ($institutions as $inst)
                                    <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Degree</label>
                                <input type="text" name="degree" id="user_degree" class="form-control" placeholder="e.g. B.Sc, MBA" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Field of Study</label>
                                <input type="text" name="field_study" id="user_field" class="form-control" placeholder="e.g. Computer Science" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Records</button>
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
            // Edit Button Click Event
            $('.editBtn').on('click', function() {
                let id = $(this).data('id');
                let fname = $(this).data('fname');
                let lname = $(this).data('lname');
                let email = $(this).data('email');
                let institutionId = $(this).data('institution');
                let degree = $(this).data('degree');
                let field = $(this).data('field');

                $('#user_id').val(id);
                $('#user_fname').val(fname);
                $('#user_lname').val(lname);
                $('#user_email').val(email);

                $('#user_institution').val(institutionId);
                $('#user_degree').val(degree);
                $('#user_field').val(field);
            });
        });
    </script>
</body>

</html>
