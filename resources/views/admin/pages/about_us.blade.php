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
                        <h4 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>Edit About Us Page
                        </h4>
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
                                {{-- <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-what" type="button">
                                    <i class="fa-solid fa-briefcase me-2"></i> What We Do
                                    @if ($errors->has('content') || $errors->has('what_we_do_image'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button> --}}
                                <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-team" type="button">
                                    <i class="fa-solid fa-users-gear me-2"></i> Meet The Team
                                    (<strong>{{ count($page->team_members ?? []) }}</strong>)
                                    @if ($errors->has('team.*'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3" data-bs-toggle="pill" data-bs-target="#tab-video"
                                    type="button">
                                    <i class="fa-solid fa-clapperboard me-2"></i> Key Features
                                    (<strong>{{ count($page->features_videos ?? []) }}</strong>)
                                    @if ($errors->has('videos.*'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-faq" type="button">
                                    <i class="fa-solid fa-circle-question me-2"></i> FAQ Management
                                    (<strong>{{ count($page->faqs ?? []) }}</strong>)
                                    @if ($errors->has('faqs.*'))
                                        <i class="fa-solid fa-circle-exclamation text-danger float-end mt-1"></i>
                                    @endif
                                </button>
                                <button class="nav-link text-start py-3 mb-2" data-bs-toggle="pill"
                                    data-bs-target="#tab-institutions" type="button">
                                    <i class="fa-solid fa-university me-2"></i> Leading Institutions
                                    (<strong>{{ count($page->leading_institutions ?? []) }}</strong>)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SCROLLABLE RIGHT CONTENT AREA -->
                <div class="col-lg-9 h-100">
                    <div class="tab-content h-100 scrollable-content p-1">

                        <!-- Tab 1: Who We Are -->
                        <input type="text" name="title" value="About Us" class="d-none">

                        <div class="tab-pane fade show active" id="tab-who">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <h5 class="fw-bold mb-4 border-bottom pb-2 text-dark">1. Founder Profile & Core Values</h5>

                                <!-- Founder Photo & Basic Info Row -->
                                <div class="row mb-4 pb-4 border-bottom">
                                    @php
                                        $founder = $page->founder_info ?? [];
                                    @endphp

                                    <!-- Left: Photo -->
                                    <div class="col-md-4 text-center border-end">
                                        <label class="form-label fw-bold d-block">Founder Photo</label>
                                        <div class="image-upload-wrapper">
                                            @php
                                                $founderImg = !empty($founder['photo'])
                                                    ? asset('storage/' . $founder['photo'])
                                                    : 'https://placehold.co/300x300?text=Founder+Photo';
                                            @endphp
                                            <label for="founder_photo_input" class="cursor-pointer">
                                                <img src="{{ $founderImg }}" id="founder_preview"
                                                    class="img-fluid rounded shadow-sm border preview-img"
                                                    style="width: 220px; height: 260px; object-fit: cover; cursor: pointer;">
                                            </label>
                                            <input type="file" name="founder_photo" id="founder_photo_input"
                                                class="d-none" onchange="previewImage(this)">
                                        </div>

                                        @error('founder_photo')
                                            <div class="text-danger fw-bold mt-2" style="font-size: 12px;">
                                                <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $message }}
                                            </div>
                                        @else
                                            {{-- <small class="text-muted mt-2 d-block">Recommended size: 500x600px</small> --}}
                                        @enderror
                                    </div>

                                    <!-- Right: Details -->
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">Founder Name</label>
                                                <input type="text" name="founder_name" class="form-control"
                                                    value="{{ old('founder_name', $founder['name'] ?? '') }}"
                                                    placeholder="e.g. Vanni Intini">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">Designation</label>
                                                <input type="text" name="founder_designation" class="form-control"
                                                    value="{{ old('founder_designation', $founder['designation'] ?? '') }}"
                                                    placeholder="e.g. Founder & CEO">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label fw-bold small">Biography / Intro
                                                    Text</label>
                                                <textarea name="founder_bio" class="form-control" rows="4" placeholder="Hello, I created Mind unite...">{{ old('founder_bio', $founder['bio'] ?? '') }}</textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label fw-bold small">Signature
                                                    (Text)</label>
                                                <div class="d-flex align-items-center gap-3">
                                                    <input type="text" name="founder_signature" id="signature_input"
                                                        class="form-control w-50"
                                                        value="{{ old('founder_signature', $founder['signature'] ?? '') }}"
                                                        placeholder="Type name for signature">

                                                    <div class="signature-preview px-3 py-1 border rounded bg-white shadow-sm"
                                                        style="font-family: 'Great Vibes', cursive; font-size: 28px; color: #555; min-width: 180px; text-align: center;">
                                                        <span
                                                            id="signature_preview_text">{{ $founder['signature'] ?? 'Your Signature' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vision, Mission, Strategy -->
                                <div class="row mt-2">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold fw-bold text-muted small">Our Vision</label>
                                        <textarea name="vision" class="form-control" rows="6">{{ old('vision', $page->vision) }}</textarea>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold fw-bold text-muted small">Our Mission</label>
                                        <textarea name="mission" class="form-control" rows="6">{{ old('mission', $page->mission) }}</textarea>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold fw-bold text-muted small">Our Strategy</label>
                                        <textarea name="strategy" class="form-control" rows="6">{{ old('strategy', $page->strategy) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Signature Font & Logic -->
                        <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
                        <script>
                            document.getElementById('signature_input').addEventListener('input', function() {
                                document.getElementById('signature_preview_text').innerText = this.value || 'Your Signature';
                            });
                        </script>

                        <!-- Tab 2: What We Do -->
                        {{-- <div class="tab-pane fade" id="tab-what">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <h5 class="fw-bold mb-4 border-bottom pb-2 text-dark">2. Description & Diagram</h5>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Description Content</label>
                                    <textarea name="content" class="form-control @error('content') is-invalid @enderror">{{ old('content', $page->content) }}</textarea>
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
                        </div> --}}

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
                                        <div class="team-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-md-1 text-center border-end">
                                                    <div class="team-drag-handle" style="cursor: move;">
                                                        <i class="fa-solid fa-grip-vertical fa-2x text-muted"></i>
                                                    </div>
                                                    <span class="team-serial-text small fw-bold text-secondary d-block mt-1">#{{ $index + 1 }}</span>
                                                </div>

                                                <div class="col-md-2 text-center border-end">
                                                    <div class="image-upload-wrapper position-relative">
                                                        @php
                                                            $oldPhoto = old("team.$index.old_photo", $member['photo'] ?? '');
                                                            $displayUrl = $oldPhoto ? asset('storage/' . $oldPhoto) : 'https://ui-avatars.com/api/?name=' . ($member['name'] ?? 'Team');
                                                        @endphp

                                                        <label class="mb-0 cursor-pointer">
                                                            <img src="{{ $displayUrl }}" class="rounded-circle shadow border preview-img"
                                                                style="width: 80px; height: 80px; object-fit: cover;">
                                                            <input type="file" name="team[{{ $index }}][photo]" class="d-none" onchange="previewImage(this)">
                                                        </label>
                                                        <input type="hidden" name="team[{{ $index }}][old_photo]" value="{{ $oldPhoto }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <input type="text" name="team[{{ $index }}][name]" class="form-control mb-2"
                                                        placeholder="Full Name" value="{{ $member['name'] ?? '' }}" required>
                                                    <input type="text" name="team[{{ $index }}][title]" class="form-control"
                                                        placeholder="Designation" value="{{ $member['title'] ?? '' }}" required>
                                                </div>

                                                <div class="col-md-5">
                                                    <textarea name="team[{{ $index }}][bio]" class="form-control" rows="3"
                                                        placeholder="Bio description...">{{ $member['bio'] ?? '' }}</textarea>
                                                </div>

                                                <div class="col-md-1 text-end">
                                                    <button type="button" class="btn btn-danger btn-sm remove-item border-0">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
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
                                        <i class="fa-solid fa-plus me-1"></i> Add Video Card
                                    </button>
                                </div>

                                <div id="video-repeater" class="row g-3">
                                    @php $videos = old('videos', $page->features_videos ?? []); @endphp
                                    @foreach ($videos as $vIndex => $video)
                                        <div class="col-md-4 video-item">
                                            <div class="card border-0 h-100 shadow bg-white overflow-hidden @if ($errors->has("videos.$vIndex.*")) border border-danger @endif">

                                                <div class="video-drag-handle position-absolute top-0 start-0 p-2 text-white bg-dark bg-opacity-50 rounded-bottom-end"
                                                    style="cursor: move; z-index: 10; border-radius: 0 0 10px 0;">
                                                    <i class="fa-solid fa-grip-vertical"></i>
                                                </div>

                                                <!-- Full Width Thumbnail Section -->
                                                <div class="position-relative bg-dark image-upload-wrapper"
                                                    style="aspect-ratio: 16/9; overflow: hidden;">
                                                    @php
                                                        $thumbUrl = isset($video['thumbnail']) && $video['thumbnail']
                                                                ? asset('storage/' . $video['thumbnail'])
                                                                : 'https://placehold.co/640x360?text=No+Thumbnail';
                                                    @endphp
                                                    <label class="cursor-pointer m-0 w-100 h-100">
                                                        <img src="{{ $thumbUrl }}" class="w-100 h-100 preview-img"
                                                            style="object-fit: cover;">
                                                        <input type="file" name="videos[{{ $vIndex }}][thumbnail]" class="d-none"
                                                            onchange="previewImage(this)">

                                                        <!-- Overlay on Hover -->
                                                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-25 opacity-hover">
                                                            <i class="fa-solid fa-camera text-white fs-4 mt-5"></i>
                                                        </div>
                                                    </label>
                                                    <input type="hidden" name="videos[{{ $vIndex }}][old_thumbnail]" value="{{ $video['thumbnail'] ?? '' }}">
                                                </div>

                                                <!-- Card Body with Padding -->
                                                <div class="card-body p-3 d-flex flex-column bg-light ">
                                                    <input type="text" name="videos[{{ $vIndex }}][title]"
                                                        class="form-control mb-2 fw-bold @error("videos.$vIndex.title") is-invalid @enderror"
                                                        placeholder="Video Title" value="{{ $video['title'] ?? '' }}">

                                                    <div class="mb-2">
                                                        <label class="small fw-bold text-muted">Video Source:</label>
                                                        <select name="videos[{{ $vIndex }}][source]" class="form-select form-select-sm" onchange="toggleVideoInput(this)">
                                                            <option value="url" {{ ($video['source'] ?? 'url') == 'url' ? 'selected' : '' }}>YouTube Embed URL</option>
                                                            <option value="file" {{ ($video['source'] ?? '') == 'file' ? 'selected' : '' }}>Local File Upload</option>
                                                        </select>
                                                    </div>

                                                    <div class="url-input-div {{ ($video['source'] ?? 'url') == 'file' ? 'd-none' : '' }}">
                                                        <input type="text" name="videos[{{ $vIndex }}][url]" class="form-control form-control-sm mb-2"
                                                            placeholder="Paste youtube embed link here" value="{{ $video['url'] ?? '' }}">
                                                    </div>

                                                    <div class="file-input-div {{ ($video['source'] ?? 'url') == 'url' ? 'd-none' : '' }}">
                                                        <input type="file" name="videos[{{ $vIndex }}][file]" class="form-control form-control-sm mb-2" accept="video/*">
                                                    </div>

                                                    <!-- Footer / Status Area -->
                                                    <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                                        <button type="button" class="btn btn-sm btn-link text-danger remove-item p-0 text-decoration-none fw-bold">
                                                            <i class="fa-solid fa-xmark me-1"></i> Remove
                                                        </button>

                                                        <div class="video-type-indicator">
                                                            @if (($video['source'] ?? 'url') == 'url' && !empty($video['url']))
                                                                <small class="text-danger fw-bold"><i class="fa-brands fa-youtube"></i> YouTube</small>
                                                            @elseif(($video['source'] ?? '') == 'file' && !empty($video['path']))
                                                                <small class="text-success fw-bold"><i class="fa-solid fa-folder-open"></i> Local</small>
                                                                <input type="hidden" name="videos[{{ $vIndex }}][path]" value="{{ $video['path'] }}">
                                                            @endif
                                                        </div>

                                                        @if (($video['url'] ?? '') || ($video['path'] ?? ''))
                                                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 view-video-btn"
                                                                data-title="{{ $video['title'] }}"
                                                                data-source="{{ $video['source'] ?? 'url' }}"
                                                                data-url="{{ $video['url'] ?? '' }}"
                                                                data-path="{{ isset($video['path']) ? asset('storage/' . $video['path']) : '' }}">
                                                                <i class="fa-solid fa-play me-1"></i> View
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>


                        <!-- Tab 5: FAQ Management -->
                        <div class="tab-pane fade h-100" id="tab-faq">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold m-0 text-dark">5. Frequently Asked Questions</h5>
                                        <small class="text-muted mt-1 d-block">Manage the FAQ section for this
                                            page.</small>
                                    </div>
                                    <button type="button" id="add-faq"
                                        class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-plus me-1"></i> Add FAQ
                                    </button>
                                </div>

                                <div id="faq-repeater">
                                    @php $faqs = old('faqs', $page->faqs ?? []); @endphp
                                    @foreach ($faqs as $fIndex => $faq)
                                        <div class="faq-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                                            <div class="row g-2 align-items-center mb-2">
                                                <div class="col-md-2 d-flex align-items-center">
                                                    <div class="faq-drag-handle me-2" style="cursor: move;">
                                                        <i class="fa-solid fa-grip-vertical text-muted"></i>
                                                    </div>
                                                    <label class="fw-bold text-primary m-0 faq-serial">Question #{{ $fIndex + 1 }}</label>
                                                </div>

                                                <div class="col-md-10 text-end">
                                                    <button type="button" class="btn btn-danger btn-sm remove-item border-0">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <input type="text" name="faqs[{{ $fIndex }}][question]"
                                                        class="form-control mb-2" placeholder="Enter Question"
                                                        value="{{ $faq['question'] ?? '' }}" required>

                                                    <textarea name="faqs[{{ $fIndex }}][answer]" class="form-control"
                                                        rows="3" placeholder="Enter Answer" required>{{ $faq['answer'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>


                        <div class="tab-pane fade h-100" id="tab-institutions">
                            <div class="card border-0 shadow-sm rounded-3 p-4 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold m-0 text-dark">6. Leading Institutions Management</h5>
                                        <small class="text-muted mt-1 d-block">Drag items using the grip icon to change their display order.</small>
                                    </div>
                                    <button type="button" id="add-institution" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-plus me-1"></i> Add New Institution
                                    </button>
                                </div>

                                <div id="institution-repeater">
                                    @php $institutions = old('leading_institutions', $page->leading_institutions ?? []); @endphp
                                    @foreach ($institutions as $idx => $item)
                                        <div class="institution-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm align-items-center">
                                            <div class="row g-3 align-items-center">
                                                <div class="col-md-2 text-center border-end d-flex align-items-center justify-content-around">
                                                    <div class="drag-handle text-muted" style="cursor: move;" title="Drag to reorder">
                                                        <i class="fa-solid fa-grip-vertical fa-lg"></i>
                                                    </div>
                                                    <div class="serial-badge bg-primary text-white rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 14px;">
                                                        {{ $idx + 1 }}
                                                    </div>
                                                </div>

                                                <div class="col-md-2 text-center border-end">
                                                    <div class="image-upload-wrapper">
                                                        @php $logo = !empty($item['logo']) ? asset('storage/' . $item['logo']) : 'https://placehold.co/100x100?text=Logo'; @endphp
                                                        <label class="cursor-pointer m-0">
                                                            <img src="{{ $logo }}" class="img-fluid rounded border preview-img" style="width: 60px; height: 60px; object-fit: contain;">
                                                            <input type="file" name="leading_institutions[{{ $idx }}][logo]" class="d-none" onchange="previewImage(this)">
                                                        </label>
                                                        <input type="hidden" name="leading_institutions[{{ $idx }}][old_logo]" value="{{ $item['logo'] ?? '' }}">
                                                    </div>
                                                </div>

                                                <div class="col-md-5">
                                                    <label class="small fw-bold text-muted mb-1">Institution Name</label>
                                                    <input type="text" name="leading_institutions[{{ $idx }}][name]" class="form-control" value="{{ $item['name'] ?? '' }}" placeholder="e.g. Stanford University" required>
                                                </div>

                                                <div class="col-md-1 text-center">
                                                    <label class="small fw-bold text-muted mb-1 d-block">Status</label>
                                                    <div class="form-check form-switch d-inline-block">
                                                        <input class="form-check-input" type="checkbox" name="leading_institutions[{{ $idx }}][is_active]" {{ ($item['is_active'] ?? true) ? 'checked' : '' }}>
                                                    </div>
                                                </div>

                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-danger btn-sm remove-item shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                                                </div>
                                            </div>

                                            <input type="hidden" name="leading_institutions[{{ $idx }}][order]" class="institution-order-input" value="{{ $item['order'] ?? $idx }}">
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
        document.addEventListener("DOMContentLoaded", function() {
            const teamEl = document.getElementById('team-repeater');
            if (teamEl) {
                new Sortable(teamEl, {
                    animation: 200,
                    handle: '.team-drag-handle',
                    ghostClass: 'bg-info-subtle',
                    onEnd: function() {
                        reIndexTeam();
                    },
                });
            }

            function reIndexTeam() {
                const items = document.querySelectorAll('.team-item');
                items.forEach((item, index) => {
                    const serialText = item.querySelector('.team-serial-text');
                    if (serialText) serialText.innerText = `#${index + 1}`;

                    item.querySelectorAll('input, textarea').forEach(input => {
                        let name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/team\[\d+\]/, `team[${index}]`));
                        }
                    });
                });

                const countBadge = document.querySelector('[data-bs-target="#tab-team"] strong');
                if(countBadge) countBadge.innerText = items.length;
            }

            document.getElementById('add-member').addEventListener('click', function() {
                let container = document.getElementById('team-repeater');
                let idx = container.querySelectorAll('.team-item').length;

                let html = `
                <div class="team-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-1 text-center border-end">
                            <div class="team-drag-handle" style="cursor: move;"><i class="fa-solid fa-grip-vertical fa-2x text-muted"></i></div>
                            <span class="team-serial-text small fw-bold text-secondary d-block mt-1">#${idx + 1}</span>
                        </div>
                        <div class="col-md-2 text-center border-end">
                            <div class="image-upload-wrapper">
                                <label class="mb-0 cursor-pointer">
                                    <img src="https://ui-avatars.com/api/?name=New" class="rounded-circle shadow border preview-img" style="width: 80px; height: 80px; object-fit: cover;">
                                    <input type="file" name="team[${idx}][photo]" class="d-none" accept="image/*" onchange="previewImage(this)">
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="team[${idx}][name]" class="form-control mb-2" placeholder="Full Name" required>
                            <input type="text" name="team[${idx}][title]" class="form-control" placeholder="Designation" required>
                        </div>
                        <div class="col-md-5">
                            <textarea name="team[${idx}][bio]" class="form-control" rows="3" placeholder="Bio..."></textarea>
                        </div>
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-item border-0"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                reIndexTeam();
            });

        });

        // 6. Video Repeater
        document.addEventListener("DOMContentLoaded", function() {
            const videoEl = document.getElementById('video-repeater');
            if (videoEl) {
                new Sortable(videoEl, {
                    animation: 200,
                    handle: '.video-drag-handle',
                    ghostClass: 'bg-info-subtle',
                    onEnd: function() {
                        reIndexVideos();
                    },
                });
            }

            function reIndexVideos() {
                const items = document.querySelectorAll('.video-item');
                items.forEach((item, index) => {
                    item.querySelectorAll('input, select, textarea').forEach(input => {
                        let name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/videos\[\d+\]/, `videos[${index}]`));
                        }
                    });
                });
                const badge = document.querySelector('[data-bs-target="#tab-video"] strong');
                if(badge) badge.innerText = items.length;
            }

            document.getElementById('add-video').addEventListener('click', function() {
                let container = document.getElementById('video-repeater');
                let idx = container.querySelectorAll('.video-item').length;
                let html = `
                <div class="col-md-4 video-item">
                    <div class="card border-0 h-100 shadow bg-white overflow-hidden">
                        <div class="video-drag-handle position-absolute top-0 start-0 p-2 text-white bg-dark bg-opacity-50 rounded-bottom-end" style="cursor: move; z-index: 10;">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </div>
                        <div class="position-relative bg-dark image-upload-wrapper" style="aspect-ratio: 16/9;">
                            <label class="cursor-pointer w-100 h-100">
                                <img src="https://placehold.co/640x360?text=Upload+Thumbnail" class="w-100 h-100 preview-img" style="object-fit: cover;">
                                <input type="file" name="videos[${idx}][thumbnail]" class="d-none" accept="image/*" onchange="previewImage(this)">
                            </label>
                        </div>
                        <div class="card-body p-3 d-flex flex-column bg-light">
                            <input type="text" name="videos[${idx}][title]" class="form-control mb-2 fw-bold" placeholder="Video Title" required>
                            <select name="videos[${idx}][source]" class="form-select form-select-sm mb-2" onchange="toggleVideoInput(this)">
                                <option value="url">YouTube Embed URL</option>
                                <option value="file">Local File Upload</option>
                            </select>
                            <div class="url-input-div"><input type="text" name="videos[${idx}][url]" class="form-control form-control-sm mb-2" placeholder="URL"></div>
                            <div class="file-input-div d-none"><input type="file" name="videos[${idx}][file]" class="form-control form-control-sm mb-2" accept="video/*"></div>
                            <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-link text-danger remove-item p-0 fw-bold"><i class="fa-solid fa-xmark"></i> Remove</button>
                            </div>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                reIndexVideos();
            });
        });

        // FAQ Repeater
        document.addEventListener("DOMContentLoaded", function() {
            const faqEl = document.getElementById('faq-repeater');
            if (faqEl) {
                new Sortable(faqEl, {
                    animation: 200,
                    handle: '.faq-drag-handle',
                    ghostClass: 'bg-info-subtle',
                    onEnd: function() {
                        reIndexFaqs();
                    },
                });
            }

            function reIndexFaqs() {
                const items = document.querySelectorAll('.faq-item');
                items.forEach((item, index) => {
                    const serialLabel = item.querySelector('.faq-serial');
                    if (serialLabel) serialLabel.innerText = `Question #${index + 1}`;

                    item.querySelectorAll('input, textarea').forEach(input => {
                        let name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/faqs\[\d+\]/, `faqs[${index}]`));
                        }
                    });
                });

                const countBadge = document.querySelector('[data-bs-target="#tab-faq"] strong');
                if(countBadge) countBadge.innerText = items.length;
            }

            document.getElementById('add-faq').addEventListener('click', function() {
                let container = document.getElementById('faq-repeater');
                let idx = container.querySelectorAll('.faq-item').length;

                let html = `
                <div class="faq-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm">
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-md-2 d-flex align-items-center">
                            <div class="faq-drag-handle me-2" style="cursor: move;">
                                <i class="fa-solid fa-grip-vertical text-muted"></i>
                            </div>
                            <label class="fw-bold text-primary m-0 faq-serial">Question #${idx + 1}</label>
                        </div>
                        <div class="col-md-10 text-end">
                            <button type="button" class="btn btn-danger btn-sm remove-item border-0"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <input type="text" name="faqs[${idx}][question]" class="form-control mb-2" placeholder="Enter Question" required>
                            <textarea name="faqs[${idx}][answer]" class="form-control" rows="3" placeholder="Enter Answer" required></textarea>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
                reIndexFaqs();
            });

        });

        // Error focus persistence for FAQ
        @if ($errors->has('faqs.*'))
            var activeTab = '#tab-faq';
        @endif


        document.addEventListener("DOMContentLoaded", function() {
            const el = document.getElementById('institution-repeater');

            if (el) {
                new Sortable(el, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-info-subtle',
                    onEnd: function() {
                        updateInstitutionsOrder();
                    },
                });
            }

            function updateInstitutionsOrder() {
                const items = document.querySelectorAll('.institution-item');
                items.forEach((item, index) => {
                    const badge = item.querySelector('.serial-badge');
                    if (badge) {
                        badge.innerText = index + 1;
                    }

                    item.querySelectorAll('input, select, textarea').forEach(input => {
                        let name = input.getAttribute('name');
                        if (name) {
                            input.setAttribute('name', name.replace(/leading_institutions\[\d+\]/, `leading_institutions[${index}]`));
                        }
                    });
                });

                const countBadge = document.querySelector('[data-bs-target="#tab-institutions"] strong');
                if(countBadge) countBadge.innerText = items.length;
            }

            const addBtn = document.getElementById('add-institution');
            if (addBtn) {
                addBtn.addEventListener('click', function() {
                    let container = document.getElementById('institution-repeater');
                    let idx = container.querySelectorAll('.institution-item').length;

                    let html = `
                    <div class="institution-item drag-item border rounded-3 p-3 mb-3 bg-light shadow-sm align-items-center">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-2 text-center border-end d-flex align-items-center justify-content-around">
                                <div class="drag-handle text-muted" style="cursor: move;" title="Drag to reorder">
                                    <i class="fa-solid fa-grip-vertical fa-lg"></i>
                                </div>
                                <div class="serial-badge bg-primary text-white rounded-circle fw-bold d-flex align-items-center justify-content-center" style="width: 30px; height: 30px; font-size: 14px;">
                                    ${idx + 1}
                                </div>
                            </div>
                            <div class="col-md-2 text-center border-end">
                                <div class="image-upload-wrapper">
                                    <label class="cursor-pointer m-0">
                                        <img src="https://placehold.co/100x100?text=Upload" class="img-fluid rounded border preview-img" style="width: 60px; height: 60px; object-fit: contain;">
                                        <input type="file" name="leading_institutions[${idx}][logo]" class="d-none" accept="image/*" onchange="previewImage(this)">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted mb-1">Institution Name</label>
                                <input type="text" name="leading_institutions[${idx}][name]" class="form-control" placeholder="Institution Name" required>
                            </div>
                            <div class="col-md-1 text-center">
                                <label class="small fw-bold text-muted mb-1 d-block">Status</label>
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" name="leading_institutions[${idx}][is_active]" checked>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-danger btn-sm remove-item shadow-sm"><i class="fa-solid fa-xmark"></i></button>
                            </div>
                        </div>
                        <input type="hidden" name="leading_institutions[${idx}][order]" class="institution-order-input" value="${idx}">
                    </div>`;
                    container.insertAdjacentHTML('beforeend', html);
                    updateInstitutionsOrder();
                });
            }
        });

        //remove item
        document.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-item');

            if (removeBtn) {
                if (confirm('Are you sure! You want to remove this item?')) {
                    const itemToRemove = removeBtn.closest('.team-item, .video-item, .faq-item, .institution-item');

                    if (itemToRemove) {
                        const isFaq = itemToRemove.classList.contains('faq-item');
                        const isTeam = itemToRemove.classList.contains('team-item');
                        const isInst = itemToRemove.classList.contains('institution-item');

                        itemToRemove.remove();

                        if (isFaq) {
                            const faqItems = document.querySelectorAll('#faq-repeater .faq-item');
                            faqItems.forEach((el, index) => {
                                const label = el.querySelector('.faq-serial');
                                if (label) label.innerText = `Question #${index + 1}`;

                                el.querySelectorAll('input, textarea').forEach(input => {
                                    let name = input.getAttribute('name');
                                    if (name) input.setAttribute('name', name.replace(/faqs\[\d+\]/, `faqs[${index}]`));
                                });
                            });
                            document.querySelector('[data-bs-target="#tab-faq"] strong').innerText = faqItems.length;
                        }

                        if (isTeam) {
                            const teamItems = document.querySelectorAll('#team-repeater .team-item');
                            teamItems.forEach((el, index) => {
                                const serialText = el.querySelector('.team-serial-text');
                                if (serialText) serialText.innerText = `#${index + 1}`;

                                el.querySelectorAll('input, textarea').forEach(input => {
                                    let name = input.getAttribute('name');
                                    if (name) input.setAttribute('name', name.replace(/team\[\d+\]/, `team[${index}]`));
                                });
                            });
                            document.querySelector('[data-bs-target="#tab-team"] strong').innerText = teamItems.length;
                        }

                        if (isInst) {
                            const instItems = document.querySelectorAll('#institution-repeater .institution-item');
                            instItems.forEach((el, index) => {
                                const badge = el.querySelector('.serial-badge');
                                if (badge) badge.innerText = index + 1;

                                const orderInput = el.querySelector('.institution-order-input');
                                if (orderInput) orderInput.value = index;

                                el.querySelectorAll('input').forEach(input => {
                                    let name = input.getAttribute('name');
                                    if (name) {
                                        input.setAttribute('name', name.replace(/leading_institutions\[\d+\]/, `leading_institutions[${index}]`));
                                    }
                                });
                            });
                            document.querySelector('[data-bs-target="#tab-institutions"] strong').innerText = instItems.length;
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SortableJS CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endsection
