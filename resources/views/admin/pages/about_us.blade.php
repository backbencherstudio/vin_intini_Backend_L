@extends('admin.layout')

@section('content')
    <div class="about-page-wrapper px-3" style="height: calc(100vh - 100px); overflow: hidden;">
        <form action="{{ route('admin.pages.update', $page->id) }}" method="POST" enctype="multipart/form-data"
            class="h-100 d-flex flex-column">
            @csrf

            <!-- 1. FIXED HEADER -->
            <div class="card shadow-sm border-0 mb-3 rounded-3 flex-shrink-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>Edit About Us</h4>
                        <small class="text-muted">Slug: {{ $page->slug }} | Fill all required fields carefully.</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        @if ($errors->any())
                            <span class="badge bg-danger animate__animated animate__shakeX">Fix errors in the highlighted
                                tabs</span>
                        @endif
                        <button type="submit" class="btn btn-success px-5 shadow-sm fw-bold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>

            {{-- @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-3 py-2 flex-shrink-0">
                    {{ session('success') }}
                </div>
            @endif --}}

            <!-- 2. MAIN CONTENT AREA -->
            <div class="row flex-grow-1 overflow-hidden g-3 mb-2">

                <!-- FIXED LEFT SIDEBAR -->
                <div class="col-lg-3 h-100">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-body p-2">
                            <div class="nav flex-column nav-pills h-100" id="v-pills-tab" role="tablist">
                                <button class="nav-link active text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-who" type="button">
                                    <i class="fa-solid fa-bullseye me-2"></i> Who We Are
                                    @if ($errors->has('title') || $errors->has('vision') || $errors->has('mission') || $errors->has('strategy'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-what" type="button">
                                    <i class="fa-solid fa-briefcase me-2"></i> What We Do
                                    @if ($errors->has('content') || $errors->has('what_we_do_image'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-team" type="button">
                                    <i class="fa-solid fa-users-gear me-2"></i> Meet The Team (<strong>{{ count($page->team_members ?? []) }}</strong>)
                                    @if ($errors->has('team.*'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-video"
                                    type="button">
                                    <i class="fa-solid fa-clapperboard me-2"></i> Key Features (<strong>{{ count($page->features_videos ?? []) }}</strong>)
                                    @if ($errors->has('videos.*'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SCROLLABLE RIGHT CONTENT AREA -->
                <div class="col-lg-9 h-100">
                    <div class="tab-content h-100 scrollable-content p-1">

                        <!-- Tab 1: Who We Are -->
                        <div class="tab-pane fade show active" id="tab-who">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <h5 class="fw-bold mb-4 border-bottom pb-2 text-dark">1. Vision, Mission & Strategy</h5>
                                <div class="mb-4">
                                    <label class="form-label fw-bold">Page Header Title <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="title"
                                        class="form-control @error('title') is-invalid @enderror"
                                        value="{{ old('title', $page->title) }}" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-muted small">Vision Statement</label>
                                    <textarea name="vision" class="form-control @error('vision') is-invalid @enderror" rows="3">{{ old('vision', $page->vision) }}</textarea>
                                    @error('vision')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-muted small">Mission Statement</label>
                                    <textarea name="mission" class="form-control @error('mission') is-invalid @enderror" rows="3">{{ old('mission', $page->mission) }}</textarea>
                                    @error('mission')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold text-muted small">Strategy Plan</label>
                                    <textarea name="strategy" class="form-control @error('strategy') is-invalid @enderror" rows="3">{{ old('strategy', $page->strategy) }}</textarea>
                                    @error('strategy')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: What We Do -->
                        <div class="tab-pane fade" id="tab-what">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <h5 class="fw-bold mb-4 border-bottom pb-2 text-dark">2. Description & Diagram</h5>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Description Content</label>
                                    <textarea name="content" id="summernote" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
                                    @error('content')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div
                                    class="text-center bg-light p-4 rounded border @error('what_we_do_image') border-danger @enderror">
                                    <label class="form-label fw-bold d-block mb-3">Collaboration Diagram (Click to
                                        change)</label>
                                    <div class="image-upload-wrapper">
                                        @php $diagImg = $page->what_we_do_image ? asset('storage/'.$page->what_we_do_image) : 'https://placehold.co/600x400?text=Click+to+Upload'; @endphp
                                        <label for="diagram_input" class="cursor-pointer">
                                            <img src="{{ $diagImg }}" id="diag_preview"
                                                class="img-fluid rounded shadow-sm border preview-img"
                                                style="max-height: 250px; cursor: pointer;">
                                        </label>
                                        <input type="file" name="what_we_do_image" id="diagram_input" class="d-none"
                                            onchange="previewImage(this)">
                                    </div>
                                    @error('what_we_do_image')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tab 3: Meet The Team (Repeater with Error Highlights) -->
                        <div class="tab-pane fade h-100" id="tab-team">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold m-0 text-dark">3. Team Management</h5>
                                        <small class="text-muted mt-1 d-block" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                            Member photo size must be less than 5MB
                                        </small>
                                    </div>
                                    <button type="button" id="add-member"
                                        class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                        <i class="fa fa-plus me-1"></i> Add Member
                                    </button>
                                </div>

                                <div id="team-repeater">
                                    @php $team = old('team', $page->team_members ?? []); @endphp
                                    @foreach ($team as $index => $member)
                                        <div
                                            class="team-item border rounded-3 p-3 mb-3 bg-light shadow-sm @if ($errors->has("team.$index.*")) border-danger @endif">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-md-2 text-center border-end">
                                                    <div class="image-upload-wrapper position-relative">
                                                        @php
                                                            $oldPhoto = old(
                                                                "team.$index.old_photo",
                                                                $member['photo'] ?? '',
                                                            );
                                                            $displayUrl = $oldPhoto
                                                                ? asset('storage/' . $oldPhoto)
                                                                : 'https://ui-avatars.com/api/?name=' .
                                                                    ($member['name'] ?? 'Team');
                                                        @endphp

                                                        <label class="mb-0 cursor-pointer">
                                                            <img src="{{ $displayUrl }}"
                                                                class="rounded-circle shadow border preview-img"
                                                                style="width: 85px; height: 85px; object-fit: cover; cursor: pointer;">

                                                            <input type="file" name="team[{{ $index }}][photo]"
                                                                class="d-none @error("team.$index.photo") is-invalid @enderror"
                                                                onchange="previewImage(this)">
                                                        </label>

                                                        <input type="hidden" name="team[{{ $index }}][old_photo]"
                                                            value="{{ $oldPhoto }}">

                                                        @error("team.$index.photo")
                                                            <div class="text-danger fw-bold"
                                                                style="font-size: 10px; margin-top: 5px;">{{ $message }}
                                                            </div>
                                                        @enderror
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="team[{{ $index }}][name]"
                                                        class="form-control mb-2 @error("team.$index.name") is-invalid @enderror"
                                                        placeholder="Full Name" value="{{ $member['name'] ?? '' }}">
                                                    @error("team.$index.name")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    <input type="text" name="team[{{ $index }}][title]"
                                                        class="form-control @error("team.$index.title") is-invalid @enderror"
                                                        placeholder="Designation" value="{{ $member['title'] ?? '' }}">
                                                    @error("team.$index.title")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <textarea name="team[{{ $index }}][bio]" class="form-control @error("team.$index.bio") is-invalid @enderror"
                                                        rows="3" placeholder="Bio description...">{{ $member['bio'] ?? '' }}</textarea>
                                                    @error("team.$index.bio")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="col-md-1 text-center">
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-item rounded-circle shadow-sm"><i
                                                            class="fa fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Tab 4: Key Features -->
                        <div class="tab-pane fade h-100" id="tab-video">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold m-0 text-dark">4. Tutorial Videos</h5>
                                        <small class="text-muted mt-1 d-block" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                            Uploaded video size must be less than 100MB
                                        </small>
                                    </div>
                                    <button type="button" id="add-video"
                                        class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                        <i class="fa fa-plus me-1"></i> Add Video Card
                                    </button>
                                </div>
                                <div id="video-repeater" class="row g-3">
                                    @php $videos = old('videos', $page->features_videos ?? []); @endphp
                                    @foreach ($videos as $vIndex => $video)
                                        <div class="col-md-6 video-item">
                                            <div
                                                class="card border p-3 h-100 shadow-sm bg-light @if ($errors->has("videos.$vIndex.*")) border-danger @endif">
                                                <input type="text" name="videos[{{ $vIndex }}][title]"
                                                    class="form-control mb-2 @error("videos.$vIndex.title") is-invalid @enderror"
                                                    placeholder="Video Title" value="{{ $video['title'] ?? '' }}">
                                                @error("videos.$vIndex.title")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror

                                                <label class="small fw-bold">Video Source:</label>
                                                <select name="videos[{{ $vIndex }}][source]"
                                                    class="form-select form-select-sm mb-2"
                                                    onchange="toggleVideoInput(this)">
                                                    <option value="url"
                                                        {{ ($video['source'] ?? 'url') == 'url' ? 'selected' : '' }}>
                                                        YouTube/Embed URL</option>
                                                    <option value="file"
                                                        {{ ($video['source'] ?? '') == 'file' ? 'selected' : '' }}>Upload
                                                        Video File</option>
                                                </select>

                                                <div
                                                    class="url-input-div {{ ($video['source'] ?? 'url') == 'file' ? 'd-none' : '' }}">
                                                    <input type="text" name="videos[{{ $vIndex }}][url]"
                                                        class="form-control mb-2 @error("videos.$vIndex.url") is-invalid @enderror"
                                                        placeholder="Embed URL" value="{{ $video['url'] ?? '' }}">
                                                    @error("videos.$vIndex.url")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div
                                                    class="file-input-div {{ ($video['source'] ?? 'url') == 'url' ? 'd-none' : '' }}">
                                                    <input type="file" name="videos[{{ $vIndex }}][file]"
                                                        class="form-control mb-2 @error("videos.$vIndex.file") is-invalid @enderror"
                                                        accept="video/*">
                                                    @error("videos.$vIndex.file")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror

                                                    @if (isset($video['path']) && $video['path'])
                                                        <small class="text-success"><i class="fa fa-circle-check"></i>
                                                            File Uploaded</small>
                                                        <input type="hidden" name="videos[{{ $vIndex }}][path]"
                                                            value="{{ $video['path'] }}">
                                                    @endif
                                                </div>

                                                <div
                                                    class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                                    <button type="button"
                                                        class="btn btn-sm btn-link text-danger remove-item p-0 text-decoration-none fw-bold"><i
                                                            class="fa fa-trash me-1"></i>Remove</button>

                                                    @if (($video['url'] ?? '') || ($video['path'] ?? ''))
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-primary px-3 rounded-pill view-video-btn"
                                                            data-title="{{ $video['title'] }}"
                                                            data-source="{{ $video['source'] ?? 'url' }}"
                                                            data-url="{{ $video['url'] ?? '' }}"
                                                            data-path="{{ isset($video['path']) ? asset('storage/' . $video['path']) : '' }}">
                                                            <i class="fa-solid fa-play-circle me-1"></i> View
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Video Preview Modal -->
    <div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title" id="previewTitle">Video Preview</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-black text-center">
                    <div id="modalVideoContent" class="ratio ratio-16x9"></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* 1. LAYOUT CONTROL */
        .about-page-wrapper {
            display: flex;
            flex-direction: column;
        }

        .scrollable-content {
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: #0EA5E9 #f1f1f1;
        }

        .scrollable-content::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .scrollable-content::-webkit-scrollbar-thumb {
            background: #0EA5E9;
            border-radius: 10px;
        }

        /* 2. TAB STYLES */
        .nav-pills .nav-link {
            border-radius: 10px;
            font-weight: 500;
            color: #555;
            transition: 0.3s;
            margin-bottom: 5px;
        }

        .nav-pills .nav-link.active {
            background-color: #0EA5E9;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
            color: #fff;
        }

        /* 3. IMAGES & BORDERS */
        .preview-img {
            transition: 0.3s ease-in-out;
            border: 2px solid transparent;
        }

        .preview-img:hover {
            opacity: 0.8;
            filter: brightness(80%);
            border: 2px solid #0d6efd !important;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .team-item {
            transition: 0.2s;
        }

        .team-item:hover {
            border-color: #0d6efd !important;
            background-color: #f8f9ff !important;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }
    </style>

    <script>
        // 1. Live Image Preview
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var previewer = input.closest('.image-upload-wrapper').querySelector('.preview-img');
                    if (previewer) {
                        previewer.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 2. Toggle Video Source
        function toggleVideoInput(select) {
            let card = select.closest('.video-item');
            if (select.value === 'url') {
                card.querySelector('.url-input-div').classList.remove('d-none');
                card.querySelector('.file-input-div').classList.add('d-none');
            } else {
                card.querySelector('.url-input-div').classList.add('d-none');
                card.querySelector('.file-input-div').classList.remove('d-none');
            }
        }

        // 3. Tab Persistence & Error Focus
        document.addEventListener("DOMContentLoaded", function() {
            @if ($errors->has('team.*'))
                var activeTab = '#tab-team';
            @elseif ($errors->has('videos.*')) var activeTab = '#tab-video';
            @elseif ($errors->has('content') || $errors->has('what_we_do_image')) var activeTab = '#tab-what';
            @else
                var activeTab = localStorage.getItem('activeAboutTab');
            @endif

            if (activeTab) {
                var tabEl = document.querySelector(`button[data-bs-target="${activeTab}"]`);
                if (tabEl) {
                    var tab = new bootstrap.Tab(tabEl);
                    tab.show();
                }
            }
            document.querySelectorAll('button[data-bs-toggle="pill"]').forEach(function(button) {
                button.addEventListener('shown.bs.tab', function(event) {
                    localStorage.setItem('activeAboutTab', event.target.getAttribute(
                        'data-bs-target'));
                });
            });
        });

        // 4. Video Modal Preview Logic
        document.addEventListener('click', function(e) {
            if (e.target.closest('.view-video-btn')) {
                let btn = e.target.closest('.view-video-btn');
                let title = btn.getAttribute('data-title');
                let source = btn.getAttribute('data-source');
                let url = btn.getAttribute('data-url');
                let path = btn.getAttribute('data-path');
                let container = document.getElementById('modalVideoContent');

                document.getElementById('previewTitle').innerText = title;
                container.innerHTML = '';

                if (source === 'url' && url) {
                    let embedUrl = url.includes('watch?v=') ? url.replace("watch?v=", "embed/") : url;
                    container.innerHTML =
                        `<iframe src="${embedUrl}?autoplay=1" allow="autoplay; fullscreen" allowfullscreen></iframe>`;
                } else if (source === 'file' && path) {
                    container.innerHTML =
                        `<video controls autoplay class="w-100 h-100"><source src="${path}" type="video/mp4"></video>`;
                }
                var myModal = new bootstrap.Modal(document.getElementById('videoPreviewModal'));
                myModal.show();
            }
        });

        document.getElementById('videoPreviewModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalVideoContent').innerHTML = '';
        });

        // 5. Team Repeater
        document.getElementById('add-member').addEventListener('click', function() {
            let container = document.getElementById('team-repeater');
            let idx = container.querySelectorAll('.team-item').length;
            let html = `
            <div class="team-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                <div class="row g-3 align-items-center">
                    <div class="col-md-2 text-center border-end">
                        <div class="image-upload-wrapper position-relative">
                            <label class="mb-0 cursor-pointer">
                                <img src="https://ui-avatars.com/api/?name=New" class="rounded-circle shadow border preview-img" style="width: 85px; height: 85px; object-fit: cover; cursor: pointer;">
                                <input type="file" name="team[${idx}][photo]" class="d-none" onchange="previewImage(this)">
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="team[${idx}][name]" class="form-control mb-2" placeholder="Full Name">
                        <input type="text" name="team[${idx}][title]" class="form-control" placeholder="Designation">
                    </div>
                    <div class="col-md-6"><textarea name="team[${idx}][bio]" class="form-control" rows="3" placeholder="Bio..."></textarea></div>
                    <div class="col-md-1 text-center"><button type="button" class="btn btn-danger btn-sm remove-item rounded-circle shadow-sm"><i class="fa fa-trash"></i></button></div>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', html);
        });

        // 6. Video Repeater
        document.getElementById('add-video').addEventListener('click', function() {
            let container = document.getElementById('video-repeater');
            let idx = container.querySelectorAll('.video-item').length;
            let html = `
            <div class="col-md-6 video-item">
                <div class="card border p-3 h-100 shadow-sm bg-light">
                    <input type="text" name="videos[${idx}][title]" class="form-control mb-2" placeholder="Video Title">
                    <select name="videos[${idx}][source]" class="form-select form-select-sm mb-2" onchange="toggleVideoInput(this)">
                        <option value="url">YouTube/Embed URL</option>
                        <option value="file">Upload Video File</option>
                    </select>
                    <div class="url-input-div"><input type="text" name="videos[${idx}][url]" class="form-control mb-2" placeholder="URL"></div>
                    <div class="file-input-div d-none"><input type="file" name="videos[${idx}][file]" class="form-control mb-2" accept="video/*"></div>
                    <button type="button" class="btn btn-sm btn-link text-danger remove-item p-0 mt-auto text-start text-decoration-none fw-bold"><i class="fa fa-trash"></i> Remove Card</button>
                </div>
            </div>`;
            container.insertAdjacentHTML('beforeend', html);
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-item')) {
                if (confirm('Are you sure?')) {
                    e.target.closest('.team-item, .video-item').remove();
                }
            }
        });
    </script>

    <!-- Summernote -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#summernote').summernote({
                height: 200,
                placeholder: 'Mind Unite description...'
            });
        });
    </script>
@endsection
