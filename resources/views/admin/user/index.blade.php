<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">

    <h2 class="mb-4">User Management (Single Blade)</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-hover border">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->id }}</td>
                <td>
                    @if($user->profile_image)
                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="User Image" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                    @else
                        <span>No Image</span>
                    @endif
                </td>
                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    <!-- Edit Button triggers Modal -->
                    <button class="btn btn-primary btn-sm editBtn"
                            data-id="{{ $user->id }}"
                            data-fname="{{ $user->first_name }}"
                            data-lname="{{ $user->last_name }}"
                            data-email="{{ $user->email }}"
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
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.update') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="user_id">
                        <div class="mb-3">
                            <label>First Name</label>
                            <input type="text" name="fname" id="user_fname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Last Name</label>
                            <input type="text" name="lname" id="user_lname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="user_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Update Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script to Pass Data to Modal -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.editBtn').on('click', function() {
                let id = $(this).data('id');
                let fname = $(this).data('fname');
                let lname = $(this).data('lname');
                let email = $(this).data('email');

                $('#user_id').val(id);
                $('#user_fname').val(fname);
                $('#user_lname').val(lname);
                $('#user_email').val(email);
            });
        });
    </script>
</body>
</html>
