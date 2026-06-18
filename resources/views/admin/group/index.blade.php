<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Group Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="container mt-5">

    <h2 class="mb-4">Manage Groups</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Name</th>
                <th>Type</th>
                <th>Industry</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $group)
                <tr>
                    <td><img src="{{ $group->logo ? asset('storage/' . $group->logo) : 'https://via.placeholder.com/50' }}"
                            width="50"></td>
                    <td>{{ $group->name }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($group->type) }}</span></td>
                    <td>{{ is_array($group->industry) ? implode(', ', $group->industry) : '' }}</td>
                    <td>
                        <button class="btn btn-primary btn-sm editBtn" data-id="{{ $group->id }}"
                            data-name="{{ $group->name }}" data-description="{{ $group->description }}"
                            data-type="{{ $group->type }}" data-discoverability="{{ $group->discoverability }}"
                            data-location="{{ $group->location }}" data-rules="{{ $group->rules }}"
                            data-invites="{{ $group->allow_member_invites }}"
                            data-approval="{{ $group->require_post_approval }}"
                            data-industry="{{ json_encode($group->industry) }}" data-bs-toggle="modal"
                            data-bs-target="#editModal">Edit</button>

                        <form action="{{ route('groups.destroy', $group->id) }}" method="POST"
                            style="display:inline;">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('groups.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="group_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Edit Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Group Name</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Location</label>
                                <input type="text" name="location" id="location" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <label>Description</label>
                                <textarea name="description" id="description" class="form-control"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="public">Public</option>
                                    <option value="private">Private</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Discoverability</label>
                                <select name="discoverability" id="discoverability" class="form-control">
                                    <option value="listed">Listed</option>
                                    <option value="unlisted">Unlisted</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Logo</label>
                                <input type="file" name="logo" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Cover Photo</label>
                                <input type="file" name="cover_photo" class="form-control">
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="allow_member_invites" id="allow_member_invites"
                                        class="form-check-input">
                                    <label class="form-check-label">Allow Member Invites</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" name="require_post_approval" id="require_post_approval"
                                        class="form-check-input">
                                    <label class="form-check-label">Require Post Approval</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Update Group</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $('.editBtn').click(function() {
            $('#group_id').val($(this).data('id'));
            $('#name').val($(this).data('name'));
            $('#location').val($(this).data('location'));
            $('#description').val($(this).data('description'));
            $('#type').val($(this).data('type'));
            $('#discoverability').val($(this).data('discoverability'));

            // Boolean Checkboxes
            $('#allow_member_invites').prop('checked', $(this).data('invites') == 1);
            $('#require_post_approval').prop('checked', $(this).data('approval') == 1);
        });
    </script>
</body>

</html>
