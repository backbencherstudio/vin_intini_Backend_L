@extends('admin.layout')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <!-- Card Header -->
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                        <h5 class="mb-0 text-primary fw-bold">
                            <i
                                class="{{ $page->slug == 'privacy-policy' ? 'fa-solid fa-file-shield' : 'fa-solid fa-file-lines' }} me-2"></i>
                            Manage {{ $page->title }}
                        </h5>
                        <div class="d-flex gap-2">
                            <button id="editBtn" class="btn btn-sm btn-primary px-3 shadow-sm fw-bold">
                                <i class="fas fa-edit me-1"></i> Edit {{ $page->title }}
                            </button>
                            <button id="cancelBtn" class="btn btn-sm btn-danger d-none px-3 shadow-sm fw-bold">
                                <i class="fas fa-times me-1"></i> Cancel
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <!-- ================= VIEW SECTION ================= -->
                        <div id="viewSection" class="p-4 shadow-inner" style="min-height: 600px;">
                            <div class="document-paper mx-auto shadow-lg p-5 mb-4 bg-white"
                                style="max-width: 850px; border: 1px solid #dee2e6; min-height: 800px; border-radius: 2px;">

                                <div
                                    class="document-header d-flex justify-content-between align-items-start border-bottom border-2 pb-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold text-uppercase mb-1" style="color: #1a237e;">{{ $page->title }}
                                        </h3>
                                        <p class="text-muted small mb-0">Last Updated:
                                            {{ $page->updated_at->format('M d, Y') }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="badge border border-primary text-primary px-3 py-2 rounded-0 fw-bold">OFFICIAL
                                            DOCUMENT</span>
                                    </div>
                                </div>

                                <div class="policy-content">
                                    {!! $page->content ?? '<p class="text-center text-muted">No content available.</p>' !!}
                                </div>
                            </div>
                        </div>

                        <!-- ================= EDIT SECTION ================= -->
                        <div id="editSection" class="d-none p-4 bg-white">
                            <form action="{{ route('admin.pages.update', $page->id) }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-bold">Page Title</label>
                                        <input type="text" name="title" class="form-control"
                                            value="{{ $page->title }}" required>
                                    </div>
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label fw-bold">Detailed Content</label>
                                        <!-- Jodit Editor Target -->
                                        <textarea name="content" id="policyEditor">{{ $page->content }}</textarea>
                                    </div>
                                </div>
                                <div class="text-end border-top pt-3">
                                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">Save
                                        Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= STYLES & JODIT CSS ================= -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.9/jodit.min.css" />
    <style>
        #viewSection {
            background-color: #f8f9fa;
            background-image: radial-gradient(#dee2e6 1px, transparent 1px);
            background-size: 20px 20px;
        }

        .policy-content {
            line-height: 1.8;
            color: #2c3e50;
        }

        .shadow-inner {
            box-shadow: inset 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Jodit Custom Height */
        .jodit-container {
            min-height: 500px !important;
        }
    </style>

    <!-- ================= SCRIPTS ================= -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.9/jodit.min.js"></script>

    <script>
        $(document).ready(function() {
            // Jodit Editor Initialization
            const editor = new Jodit('#policyEditor', {
                height: 500,
                toolbarAdaptive: false,
                toolbarButtonSize: "middle",
                buttons: [
                    'source', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'font', 'fontsize', 'brush', 'paragraph', '|',
                    'ul', 'ol', 'align', '|',
                    'outdent', 'indent', '|',
                    'link', 'image', 'video', 'table', '|',
                    'hr', 'eraser', 'copyformat', '|',
                    'symbol', 'fullsize', 'print', 'undo', 'redo', 'about'
                ],
                uploader: {
                    insertImageAsBase64URI: true
                }
            });

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
        });
    </script>
@endsection
