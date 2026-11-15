@extends('admin.layout.page-app')
@section('page_title', 'YouTube Bulk Importer')
@section('tab_title', 'YouTube Bulk Importer')

@section('content')
    @include('admin.layout.sidebar')

    <div class="right-content">
        @include('admin.layout.header')

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />

        <div class="body-content">
            <h1 class="page-title-sm">YouTube Bulk Importer</h1>

            <div class="row">
                <div class="col-sm-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{__('label.dashboard')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">YouTube Bulk Importer</li>
                    </ol>
                </div>
            </div>

            <!-- Card 1: Optional YouTube Data API v3 Key -->
            <div class="card custom-border-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fa-brands fa-youtube text-danger mr-2"></i>YouTube Data API v3 Key (Optional — For 100s of Videos & Pagination)</span>
                    <span id="api_mode_badge" class="badge {{ $apiKey ? 'badge-success' : 'badge-warning text-dark' }}">
                        {{ $apiKey ? 'API Key Saved (Full Bulk Mode)' : 'Free Mode (Up to 15-30 Videos)' }}
                    </span>
                </div>
                <div class="card-body py-3">
                    <div class="form-row align-items-end">
                        <div class="col-md-8">
                            <label class="mb-1">YouTube Data API v3 Key</label>
                            <input type="text" id="youtube_api_key" class="form-control" value="{{ $apiKey }}"
                                placeholder="Paste Google Cloud YouTube Data API v3 Key (leave blank for Free Mode)">
                        </div>
                        <div class="col-md-4 mt-2 mt-md-0">
                            <button type="button" id="btn_save_api_key" class="btn btn-default w-100">
                                <i class="fa-solid fa-key mr-1"></i>Save API Key
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Tip: Free mode works without any key (fetches ~15-30 recent videos or pasted links). For huge channels/playlists (50 to 500+ episodes with pagination, exact duration & embeddable check), save a free <strong>YouTube Data API v3</strong> key from Google Cloud Console.
                    </small>
                </div>
            </div>

            <!-- Card 2: Fetch Source -->
            <div class="card custom-border-card mb-3">
                <div class="card-header">
                    <i class="fa-solid fa-cloud-arrow-down mr-2"></i>Step 1: Fetch Videos from YouTube Channel, Playlist, or Video Links
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" id="sourceTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-url" data-toggle="tab" href="#pane-url" role="tab">
                                <i class="fa-solid fa-link mr-1"></i>Channel or Playlist URL
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-links" data-toggle="tab" href="#pane-links" role="tab">
                                <i class="fa-solid fa-list mr-1"></i>Paste Multiple Video Links
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Tab 1: Channel or Playlist URL -->
                        <div class="tab-pane fade show active" id="pane-url" role="tabpanel">
                            <div class="form-row align-items-end">
                                <div class="col-md-7">
                                    <label>YouTube Channel URL / Handle OR Playlist URL<span class="text-danger">*</span></label>
                                    <input type="text" id="yt_source_url" class="form-control"
                                        placeholder="e.g. https://www.youtube.com/@ChannelName or https://www.youtube.com/playlist?list=PL...">
                                </div>
                                <div class="col-md-2 mt-2 mt-md-0">
                                    <label>Batch Limit</label>
                                    <select id="yt_fetch_limit" class="form-control">
                                        <option value="25">25 videos</option>
                                        <option value="50" selected>50 videos</option>
                                        <option value="100">100 videos</option>
                                        <option value="200">200 videos</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mt-2 mt-md-0">
                                    <button type="button" id="btn_fetch_url" class="btn btn-default w-100">
                                        <i class="fa-solid fa-magnifying-glass mr-1"></i>Fetch Videos
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: Multiple Links -->
                        <div class="tab-pane fade" id="pane-links" role="tabpanel">
                            <div class="form-group mb-2">
                                <label>Paste YouTube Video URLs (one per line or comma-separated)<span class="text-danger">*</span></label>
                                <textarea id="yt_multi_links" class="form-control" rows="4"
                                    placeholder="https://www.youtube.com/watch?v=...&#10;https://youtu.be/..."></textarea>
                            </div>
                            <button type="button" id="btn_fetch_links" class="btn btn-default">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i>Fetch Pasted Links
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Preview & Select Videos -->
            <div class="card custom-border-card mb-3 d-none" id="preview_card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img id="source_thumb" src="" alt="" class="rounded mr-2 d-none" style="width:36px;height:36px;object-fit:cover;">
                        <div>
                            <strong id="source_title">Fetched Videos</strong>
                            <small id="source_meta" class="text-muted d-block"></small>
                        </div>
                    </div>
                    <div class="mt-2 mt-md-0 d-flex align-items-center" style="gap:8px;">
                        <span class="badge badge-primary p-2" id="selected_count_badge">0 Selected</span>
                        <button type="button" id="btn_load_more" class="btn btn-outline-primary btn-sm d-none">
                            <i class="fa-solid fa-plus mr-1"></i>Load Next Batch
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap:8px;">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-success" id="btn_select_all">Select All</button>
                            <button type="button" class="btn btn-outline-primary" id="btn_select_new">Select Only New</button>
                            <button type="button" class="btn btn-outline-secondary" id="btn_deselect_all">Deselect All</button>
                        </div>
                        <div style="min-width:260px;flex:1;max-width:360px;">
                            <input type="text" id="filter_videos" class="form-control form-control-sm" placeholder="Filter videos by title...">
                        </div>
                    </div>

                    <div class="table-responsive" style="max-height:480px;overflow-y:auto;border:1px solid #e9ecef;border-radius:6px;">
                        <table class="table table-hover table-sm mb-0" id="videos_table">
                            <thead class="thead-light" style="position:sticky;top:0;z-index:2;">
                                <tr>
                                    <th style="width:42px;" class="text-center">#</th>
                                    <th style="width:110px;">Poster</th>
                                    <th>Title (Editable before import)</th>
                                    <th style="width:95px;">Duration</th>
                                    <th style="width:105px;">Published</th>
                                    <th style="width:140px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="videos_tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Card 4: Destination & Import -->
            <div class="card custom-border-card mb-4 d-none" id="import_card">
                <div class="card-header">
                    <i class="fa-solid fa-file-import mr-2"></i>Step 2: Choose Where to Save Selected Videos
                </div>
                <div class="card-body">
                    <form id="import_form" enctype="multipart/form-data">
                        @csrf
                        <div class="form-row mb-3">
                            <div class="col-md-4">
                                <label>Import As<span class="text-danger">*</span></label>
                                <select name="import_target" id="import_target" class="form-control">
                                    <option value="tv_show" selected>TV Show Episodes</option>
                                    <option value="movie">Movies / Individual Videos</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Access Level</label>
                                <select name="is_premium" id="is_premium" class="form-control">
                                    <option value="0" selected>Free (All Users)</option>
                                    <option value="1">VIP / Premium Only</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Import Order</label>
                                <select name="sort_direction" id="sort_direction" class="form-control">
                                    <option value="as_listed" selected>As Listed (Top -> Bottom)</option>
                                    <option value="reverse">Reverse (Bottom -> Top / Oldest First)</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="skip_duplicates" checked>
                                    <label class="custom-control-label" for="skip_duplicates">Skip Duplicates</label>
                                </div>
                            </div>
                        </div>

                        <!-- TV Show Settings -->
                        <div id="tv_show_settings">
                            <div class="form-row mb-3">
                                <div class="col-md-4">
                                    <label>TV Show Selection</label>
                                    <select name="show_mode" id="show_mode" class="form-control">
                                        <option value="existing" {{ count($shows) > 0 ? 'selected' : '' }}>Add to Existing TV Show</option>
                                        <option value="new" {{ count($shows) === 0 ? 'selected' : '' }}>+ Create New TV Show</option>
                                    </select>
                                </div>
                                <div class="col-md-5" id="existing_show_box">
                                    <label>Select TV Show<span class="text-danger">*</span></label>
                                    <select name="show_id" id="show_id" class="form-control">
                                        <option value="">-- Choose TV Show --</option>
                                        @foreach($shows as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Season<span class="text-danger">*</span></label>
                                    <select name="season_id" id="season_id" class="form-control">
                                        @foreach($seasons as $se)
                                            <option value="{{ $se->id }}">{{ $se->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- New Show Sub-form -->
                            <div id="new_show_box" class="border rounded p-3 mb-3 bg-light {{ count($shows) > 0 ? 'd-none' : '' }}">
                                <h6 class="mb-3 font-weight-bold">New TV Show Details</h6>
                                <div class="form-row">
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Show Name<span class="text-danger">*</span></label>
                                            <input type="text" name="new_show_name" id="new_show_name" class="form-control" placeholder="Enter TV Show title">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Content Tab (Type)<span class="text-danger">*</span></label>
                                            <select name="new_show_type_id" id="new_show_type_id" class="form-control">
                                                @foreach($types as $t)
                                                    <option value="{{ $t->id }}" {{ $t->type == 2 ? 'selected' : '' }}>{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Show Poster Image (Optional — auto-uses first video)</label>
                                            <input type="file" name="new_show_thumbnail" class="form-control-file" accept="image/*">
                                            <input type="hidden" name="new_show_poster_url" id="new_show_poster_url">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Categories<span class="text-danger">*</span></label>
                                            <select name="new_show_category_id[]" id="new_show_category_id" class="form-control select2-multi" multiple>
                                                @foreach($categories as $c)
                                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Languages<span class="text-danger">*</span></label>
                                            <select name="new_show_language_id[]" id="new_show_language_id" class="form-control select2-multi" multiple>
                                                @foreach($languages as $l)
                                                    <option value="{{ $l->id }}">{{ $l->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Description</label>
                                            <input type="text" name="new_show_description" id="new_show_description" class="form-control" placeholder="Short show summary">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Movie / Standalone Video Settings -->
                        <div id="movie_settings" class="d-none border rounded p-3 mb-3 bg-light">
                            <h6 class="mb-3 font-weight-bold">Movies / Standalone Videos Settings</h6>
                            <div class="form-row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Content Tab (Type)<span class="text-danger">*</span></label>
                                        <select name="movie_type_id" id="movie_type_id" class="form-control">
                                            @foreach($types as $t)
                                                <option value="{{ $t->id }}" {{ $t->type == 1 ? 'selected' : '' }}>{{ $t->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Categories<span class="text-danger">*</span></label>
                                        <select name="movie_category_id[]" id="movie_category_id" class="form-control select2-multi" multiple>
                                            @foreach($categories as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Languages<span class="text-danger">*</span></label>
                                        <select name="movie_language_id[]" id="movie_language_id" class="form-control select2-multi" multiple>
                                            @foreach($languages as $l)
                                                <option value="{{ $l->id }}">{{ $l->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="button" id="btn_import_submit" class="btn btn-default btn-lg">
                                <i class="fa-solid fa-upload mr-2"></i>Import Selected Videos to ZuLive
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('pagescript')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2-multi').select2({ width: '100%', placeholder: 'Select...' });

            let fetchedVideos = [];
            let nextPageToken = '';
            let lastFetchPayload = {};

            function updateSelectedBadge() {
                const count = $('.vid-check:checked').length;
                $('#selected_count_badge').text(count + ' Selected');
            }

            function renderTable(append = false) {
                const filter = $('#filter_videos').val().toLowerCase().trim();
                const tbody = $('#videos_tbody');
                if (!append) tbody.empty();

                fetchedVideos.forEach((v, idx) => {
                    if (append && $(`#row_vid_${idx}`).length > 0) return;

                    const matchesFilter = !filter || (v.title || '').toLowerCase().includes(filter);
                    let statusBadges = '';
                    if (v.exists_in_show) statusBadges += '<span class="badge badge-info mr-1">In TV Show</span>';
                    if (v.exists_in_movie) statusBadges += '<span class="badge badge-secondary mr-1">In Movies</span>';
                    if (!v.exists_in_show && !v.exists_in_movie) statusBadges += '<span class="badge badge-success mr-1">New</span>';
                    if (v.embeddable === false) statusBadges += '<span class="badge badge-danger">Embed Disabled</span>';

                    const safeTitle = $('<div>').text(v.title || '').html();
                    const rowHtml = `
                        <tr id="row_vid_${idx}" class="${matchesFilter ? '' : 'd-none'}">
                            <td class="text-center align-middle">
                                <input type="checkbox" class="vid-check" data-index="${idx}" ${(!v.exists_in_show && v.embeddable !== false) ? 'checked' : ''}>
                            </td>
                            <td class="align-middle">
                                <img src="${v.thumbnail}" alt="" style="width:96px;height:54px;object-fit:cover;border-radius:4px;">
                            </td>
                            <td class="align-middle">
                                <input type="text" class="form-control form-control-sm vid-title-input" data-index="${idx}" value="${safeTitle}">
                                <small class="text-muted">
                                    <a href="${v.url}" target="_blank" rel="noopener">${v.id} <i class="fa-solid fa-arrow-up-right-from-square fa-xs"></i></a>
                                </small>
                            </td>
                            <td class="align-middle"><span class="badge badge-dark">${v.duration_str || 'YouTube'}</span></td>
                            <td class="align-middle"><small>${v.published_at || ''}</small></td>
                            <td class="align-middle">${statusBadges}</td>
                        </tr>
                    `;
                    tbody.append(rowHtml);
                });

                updateSelectedBadge();
            }

            $(document).on('change', '.vid-check', updateSelectedBadge);
            $(document).on('input', '.vid-title-input', function() {
                const idx = $(this).data('index');
                if (fetchedVideos[idx]) fetchedVideos[idx].title = $(this).val();
            });

            $('#filter_videos').on('input', function() {
                const q = $(this).val().toLowerCase().trim();
                fetchedVideos.forEach((v, idx) => {
                    const row = $(`#row_vid_${idx}`);
                    if (!q || (v.title || '').toLowerCase().includes(q)) row.removeClass('d-none');
                    else row.addClass('d-none');
                });
            });

            $('#btn_select_all').on('click', function() {
                $('#videos_tbody tr:not(.d-none) .vid-check').prop('checked', true);
                updateSelectedBadge();
            });
            $('#btn_deselect_all').on('click', function() {
                $('#videos_tbody tr:not(.d-none) .vid-check').prop('checked', false);
                updateSelectedBadge();
            });
            $('#btn_select_new').on('click', function() {
                fetchedVideos.forEach((v, idx) => {
                    const cb = $(`#row_vid_${idx} .vid-check`);
                    if (!$(`#row_vid_${idx}`).hasClass('d-none')) {
                        cb.prop('checked', !v.exists_in_show && !v.exists_in_movie && v.embeddable !== false);
                    }
                });
                updateSelectedBadge();
            });

            $('#import_target').on('change', function() {
                if ($(this).val() === 'tv_show') {
                    $('#tv_show_settings').removeClass('d-none');
                    $('#movie_settings').addClass('d-none');
                } else {
                    $('#tv_show_settings').addClass('d-none');
                    $('#movie_settings').removeClass('d-none');
                }
            });

            $('#show_mode').on('change', function() {
                if ($(this).val() === 'new') {
                    $('#existing_show_box').addClass('d-none');
                    $('#new_show_box').removeClass('d-none');
                } else {
                    $('#existing_show_box').removeClass('d-none');
                    $('#new_show_box').addClass('d-none');
                }
            });

            // Save API Key
            $('#btn_save_api_key').on('click', function() {
                const key = $('#youtube_api_key').val().trim();
                $('#dvloader').show();
                $.post('{{ route("admin.youtube.savekey") }}', {
                    _token: '{{ csrf_token() }}',
                    youtube_api_key: key
                }, function(resp) {
                    $('#dvloader').hide();
                    if (resp.status == 200) {
                        toastr.success(resp.success);
                        $('#api_mode_badge')
                            .text(key ? 'API Key Saved (Full Bulk Mode)' : 'Free Mode (Up to 15-30 Videos)')
                            .removeClass('badge-success badge-warning text-dark')
                            .addClass(key ? 'badge-success' : 'badge-warning text-dark');
                    } else {
                        toastr.error(resp.errors || 'Failed to save key');
                    }
                }).fail(function() {
                    $('#dvloader').hide();
                    toastr.error('Request failed');
                });
            });

            function doFetch(payload, append = false) {
                $('#dvloader').show();
                $.post('{{ route("admin.youtube.fetch") }}', payload, function(resp) {
                    $('#dvloader').hide();
                    if (resp.status != 200) {
                        const msg = Array.isArray(resp.errors) ? resp.errors.join('<br>') : (resp.errors || 'Fetch failed');
                        toastr.error(msg);
                        return;
                    }

                    if (!append) {
                        fetchedVideos = resp.videos || [];
                        const src = resp.source || {};
                        $('#source_title').text(src.title || 'YouTube Videos');
                        if (src.thumbnail) {
                            $('#source_thumb').attr('src', src.thumbnail).removeClass('d-none');
                            $('#new_show_poster_url').val(src.thumbnail);
                        } else {
                            $('#source_thumb').addClass('d-none');
                        }
                        if (!$('#new_show_name').val() && src.title && src.title !== 'Pasted Video Links') {
                            $('#new_show_name').val(src.title);
                        }
                    } else {
                        fetchedVideos = fetchedVideos.concat(resp.videos || []);
                    }

                    nextPageToken = resp.next_page_token || '';
                    const modeText = resp.mode === 'api' ? 'YouTube Data API v3' : (resp.mode === 'oembed' ? 'Direct Link Parser' : 'Free Public Feed');
                    $('#source_meta').text(`${fetchedVideos.length} video(s) loaded via ${modeText}`);

                    if (nextPageToken) $('#btn_load_more').removeClass('d-none');
                    else $('#btn_load_more').addClass('d-none');

                    $('#preview_card, #import_card').removeClass('d-none');
                    renderTable(append);
                    toastr.success(`Loaded ${(resp.videos || []).length} video(s).`);
                }).fail(function(xhr) {
                    $('#dvloader').hide();
                    toastr.error(xhr.responseJSON?.message || 'Error connecting to server');
                });
            }

            $('#btn_fetch_url').on('click', function() {
                const url = $('#yt_source_url').val().trim();
                if (!url) { toastr.error('Please enter a Channel or Playlist URL'); return; }
                lastFetchPayload = {
                    _token: '{{ csrf_token() }}',
                    source_type: 'url',
                    url: url,
                    api_key: $('#youtube_api_key').val().trim(),
                    limit: $('#yt_fetch_limit').val(),
                    page_token: ''
                };
                doFetch(lastFetchPayload, false);
            });

            $('#btn_load_more').on('click', function() {
                if (!nextPageToken) return;
                lastFetchPayload.page_token = nextPageToken;
                doFetch(lastFetchPayload, true);
            });

            $('#btn_fetch_links').on('click', function() {
                const links = $('#yt_multi_links').val().trim();
                if (!links) { toastr.error('Please paste at least one YouTube link'); return; }
                lastFetchPayload = {
                    _token: '{{ csrf_token() }}',
                    source_type: 'links',
                    video_links: links,
                    api_key: $('#youtube_api_key').val().trim()
                };
                doFetch(lastFetchPayload, false);
            });

            // Import Selected Videos
            $('#btn_import_submit').on('click', function() {
                const selected = [];
                $('.vid-check:checked').each(function() {
                    const idx = $(this).data('index');
                    if (fetchedVideos[idx]) selected.push(fetchedVideos[idx]);
                });

                if (selected.length === 0) {
                    toastr.error('Please select at least one video to import.');
                    return;
                }

                const formData = new FormData($('#import_form')[0]);
                formData.set('videos', JSON.stringify(selected));
                formData.set('skip_duplicates', $('#skip_duplicates').is(':checked') ? '1' : '0');

                $('#dvloader').show();
                $('#btn_import_submit').prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    url: '{{ route("admin.youtube.import") }}',
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(resp) {
                        $('#dvloader').hide();
                        $('#btn_import_submit').prop('disabled', false);

                        if (resp.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Import Complete!',
                                text: resp.success,
                                showCancelButton: true,
                                confirmButtonText: 'View Imported Content',
                                cancelButtonText: 'Stay Here'
                            }).then((result) => {
                                if (result.isConfirmed && resp.redirect_url) {
                                    window.location.href = resp.redirect_url;
                                }
                            });
                        } else {
                            const msg = Array.isArray(resp.errors) ? resp.errors.join('<br>') : (resp.errors || 'Import failed');
                            toastr.error(msg);
                        }
                    },
                    error: function(xhr) {
                        $('#dvloader').hide();
                        $('#btn_import_submit').prop('disabled', false);
                        toastr.error(xhr.responseJSON?.message || 'Import request failed');
                    }
                });
            });
        });
    </script>
@endsection
