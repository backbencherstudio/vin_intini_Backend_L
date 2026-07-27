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
                        <!-- ================= VIEW SECTION (Document Style) ================= -->
                        <div id="viewSection" class="p-4 shadow-inner" style="min-height: 600px;">
                            <!-- Document Paper -->
                            <div class="document-paper mx-auto shadow-lg p-5 mb-4 bg-white"
                                style="max-width: 850px; border: 1px solid #dee2e6; min-height: 800px; position: relative; border-radius: 2px;">

                                <!-- Document Header inside Paper -->
                                <div
                                    class="document-header d-flex justify-content-between align-items-start border-bottom border-2 pb-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold text-uppercase mb-1"
                                            style="letter-spacing: 1px; color: #1a237e; font-family: 'Segoe UI', Tahoma, sans-serif;">
                                            {{ $page->title }}</h3>
                                        <p class="text-muted small mb-0">
                                            <i class="fa-solid fa-clock me-1"></i> Last Updated:
                                            {{ $page->updated_at->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge border border-primary text-primary px-3 py-2 rounded-0 fw-bold"
                                            style="font-size: 10px; letter-spacing: 0.5px;">OFFICIAL DOCUMENT</span>
                                    </div>
                                </div>

                                <!-- Document Content Area -->
                                <div class="policy-content">
                                    {!! $page->content ??
                                        '<div class="text-center py-5 text-muted"><p>No content available. Click edit to add content.</p></div>' !!}
                                </div>

                                <!-- Document Footer -->
                                <div class="document-footer mt-5 pt-4 border-top text-center">
                                    <p class="text-muted mb-0" style="font-size: 11px;">This document is the property of
                                        {{ config('app.name') }}. All rights reserved.</p>
                                    {{-- <small class="text-muted opacity-50" style="font-size: 9px;">Digital System Generated
                                        Document</small> --}}
                                </div>
                            </div>
                        </div>

                        <!-- ================= EDIT SECTION (Hidden) ================= -->
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
                                        <textarea name="content" id="policyEditor" class="form-control">{{ $page->content }}</textarea>
                                    </div>
                                </div>

                                <input type="hidden" name="video_option" value="none">

                                <div class="text-end border-top pt-3">
                                    <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
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

    <!-- ================= STYLES ================= -->
    <style>
        /* Dotted Background for View Section */
        #viewSection {
            background-color: #f0f2f5 !important;
            background-image: radial-gradient(#cfd8dc 1.2px, transparent 1.2px);
            background-size: 24px 24px;
        }

        /* Policy Content Styling (Dashed Headlines) */
        .policy-content {
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
            font-size: 1.05rem;
        }

        .policy-content h1,
        .policy-content h2,
        .policy-content h3 {
            color: #1a237e;
            margin-top: 30px;
            margin-bottom: 15px;
            font-weight: 700;
            /* border-bottom: 1px dashed #e0e0e0;  */
            padding-bottom: 10px;
        }

        .policy-content p {
            margin-bottom: 1.5rem;
        }

        .policy-content ul,
        .policy-content ol {
            margin-bottom: 1.5rem;
            padding-left: 25px;
        }

        .shadow-inner {
            box-shadow: inset 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        /* Print View Support */
        @media print {
            #viewSection {
                background: white !important;
            }

            .document-paper {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: none !important;
            }

            .card-header,
            .btn {
                display: none !important;
            }
        }
    </style>

    <!-- ================= SCRIPTS ================= -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <script>
        $(document).ready(function() {
            const editBtn = document.getElementById('editBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const viewSection = document.getElementById('viewSection');
            const editSection = document.getElementById('editSection');

            // Switch to Edit Mode
            editBtn.addEventListener('click', function() {
                viewSection.classList.add('d-none');
                editSection.classList.remove('d-none');
                editBtn.classList.add('d-none');
                cancelBtn.classList.remove('d-none');
            });

            // Switch to View Mode
            cancelBtn.addEventListener('click', function() {
                viewSection.classList.remove('d-none');
                editSection.classList.add('d-none');
                editBtn.classList.remove('d-none');
                cancelBtn.classList.add('d-none');
            });

            // Initialize Summernote Editor
            $('#policyEditor').summernote({
                placeholder: 'Write document content here...',
                tabsize: 2,
                height: 450,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview']]
                ]
            });
        });
    </script>
@endsection
