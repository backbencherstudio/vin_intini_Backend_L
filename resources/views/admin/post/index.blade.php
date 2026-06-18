<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Post Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="container mt-5">

    <h2 class="mb-4">Post Management</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-striped border">
        <thead class="table-dark">
            <tr>
                <th>User</th>
                <th>Description</th>
                <th>Visibility</th>
                <th>Likes/Comments</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($posts as $post)
                <tr>
                    <td>{{ $post->user->name ?? 'Unknown' }}</td>
                    <td>{{ Str::limit($post->description, 50) }}</td>
                    <td><span class="badge bg-primary">{{ ucfirst($post->visibility) }}</span></td>
                    <td>
                        <small>Likes: {{ $post->total_like }} | Comments: {{ $post->total_comment }}</small>
                    </td>
                    <td>
                        <!-- Edit Button -->
                        <button class="btn btn-sm btn-warning editBtn" data-id="{{ $post->id }}"
                            data-description="{{ $post->description }}" data-visibility="{{ $post->visibility }}"
                            data-comment_permission="{{ $post->who_can_comment }}" data-bs-toggle="modal"
                            data-bs-target="#editPostModal">
                            Edit
                        </button>

                        <!-- Delete Form -->
                        <form action="{{ route('posts.destroy', $post->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this post?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Edit Modal -->
    <div class="modal fade" id="editPostModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('posts.update') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Post</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="post_id">

                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" id="post_description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>Visibility</label>
                            <select name="visibility" id="post_visibility" class="form-control">
                                <option value="public">Public</option>
                                <option value="connections">Connections</option>
                                <option value="groups">Groups</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Who Can Comment?</label>
                            <select name="who_can_comment" id="post_comment_permission" class="form-control">
                                <option value="anyone">Anyone</option>
                                <option value="connections">Connections</option>
                                <option value="no_one">No One</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update Post</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.editBtn').on('click', function() {
                // ডাটা সংগ্রহ
                let id = $(this).data('id');
                let description = $(this).data('description');
                let visibility = $(this).data('visibility');
                let comment_permission = $(this).data('comment_permission');

                // মডাল ফিল্ডে ডাটা সেট করা
                $('#post_id').val(id);
                $('#post_description').val(description);
                $('#post_visibility').val(visibility);
                $('#post_comment_permission').val(comment_permission);
            });
        });
    </script>
</body>

</html>
