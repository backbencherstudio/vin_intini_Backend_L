@extends('admin.layout')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                    <h5 class="mb-0 text-primary">
                        <i class="{{ $page->slug == 'privacy-policy' ? 'fa-solid fa-file-shield' : 'fa-solid fa-file-lines' }} me-2"></i>
                        Manage {{ $page->title }}
                    </h5>
                    <div>
                        <button id="editBtn" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit {{ $page->title }}
                        </button>
                        <button id="cancelBtn" class="btn btn-sm btn-danger d-none">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Success Message -->
                    {{-- @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif --}}

                    <!-- ================= VIEW SECTION (Default) ================= -->
                    <div id="viewSection" class="p-4 bg-light rounded border" style="min-height: 500px;">
                        <div class="policy-content">
                            {!! $page->content ?? '<p class="text-muted text-center mt-5">No content available. Click edit to add content.</p>' !!}
                        </div>
                    </div>

                    <!-- ================= EDIT SECTION (Hidden) ================= -->
                    <div id="editSection" class="d-none">
                        <form action="{{ route('admin.pages.update', $page->id) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold">Page Title</label>
                                <input type="text" name="title" class="form-control" value="{{ $page->title }}" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Detailed Content</label>
                                <textarea name="content" id="policyEditor" class="form-control">{{ $page->content }}</textarea>
                            </div>

                            <!-- Hidden Fields to keep database consistency -->
                            <input type="hidden" name="video_option" value="none">

                            <div class="text-end border-top pt-3">
                                <button type="submit" class="btn btn-success px-5 shadow-sm">
                                    <i class="fas fa-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    const editBtn = document.getElementById('editBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const viewSection = document.getElementById('viewSection');
    const editSection = document.getElementById('editSection');

    editBtn.addEventListener('click', function() {
        viewSection.classList.add('d-none');
        editSection.classList.remove('d-none');
        editBtn.classList.add('d-none');
        cancelBtn.classList.remove('d-none');
    });

    cancelBtn.addEventListener('click', function() {
        viewSection.classList.remove('d-none');
        editSection.classList.add('d-none');
        editBtn.classList.remove('d-none');
        cancelBtn.classList.add('d-none');
    });
</script>

<!-- Summernote (Rich Text Editor) -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
    $(document).ready(function() {
        $('#policyEditor').summernote({
            placeholder: 'Write your policy details here...',
            tabsize: 2,
            height: 450,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ]
        });
    });
</script>

<style>
    .policy-content {
        line-height: 1.8;
        color: #333;
        font-size: 1.05rem;
    }
    .policy-content h1, .policy-content h2, .policy-content h3 {
        margin-top: 25px;
        color: #000;
    }
    .card { border-radius: 12px; }
</style>
@endsection
