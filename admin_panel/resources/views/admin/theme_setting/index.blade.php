@extends('admin.layout.page-app')
@section('page_title', 'App Theme & Colors')
@section('tab_title', 'App Theme & Colors')

@section('content')
    @include('admin.layout.sidebar')

    <style>
        .preset-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all .18s ease;
            background: #fff;
            position: relative;
        }
        .preset-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
        }
        .preset-card.active {
            border-color: #111827;
            box-shadow: 0 0 0 3px rgba(17, 24, 39, .14);
        }
        .preset-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            display: none;
            font-size: 11px;
        }
        .preset-card.active .preset-badge {
            display: inline-block;
        }
        .swatch-row {
            display: flex;
            gap: 5px;
            margin-top: 8px;
        }
        .swatch-dot {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, .15);
        }
        .color-picker-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .color-picker-group input[type="color"] {
            width: 44px;
            height: 38px;
            padding: 2px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            cursor: pointer;
            background: transparent;
        }
        .phone-mockup {
            width: 280px;
            margin: 0 auto;
            border-radius: 28px;
            border: 6px solid #1f2937;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(0, 0, 0, .25);
            font-family: system-ui, -apple-system, sans-serif;
        }
        .phone-topbar {
            padding: 12px 14px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .phone-chips {
            display: flex;
            gap: 6px;
            padding: 4px 14px 10px;
            overflow: hidden;
        }
        .phone-chip {
            font-size: 10px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 14px;
        }
        .phone-hero {
            margin: 0 12px 12px;
            border-radius: 14px;
            padding: 14px;
            position: relative;
        }
        .phone-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 8px;
        }
        .phone-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            padding: 0 12px 14px;
        }
        .phone-poster {
            height: 78px;
            border-radius: 8px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .phone-bottomnav {
            display: flex;
            justify-content: space-around;
            padding: 10px 6px;
            font-size: 10px;
            font-weight: 600;
        }
    </style>

    <div class="right-content">
        @include('admin.layout.header')

        <div class="body-content">
            <h1 class="page-title-sm">App Theme &amp; Colors</h1>

            <div class="row mb-2">
                <div class="col-sm-12">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{__('label.dashboard')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">App Theme &amp; Colors</li>
                    </ol>
                </div>
            </div>

            <form id="theme_setting_form">
                @csrf
                <input type="hidden" name="theme_preset" id="theme_preset" value="{{ $current['theme_preset'] }}">

                <!-- Section 1: 1-Click Theme Presets -->
                <div class="card custom-border-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fa-solid fa-swatchbook mr-2"></i>1-Click Theme Presets</span>
                        <small class="text-muted">Click any preset to preview &amp; apply, or customize colors below</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($presets as $slug => $preset)
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="preset-card {{ $current['theme_preset'] === $slug ? 'active' : '' }}"
                                         data-preset="{{ $slug }}"
                                         data-colors='@json($preset)'>
                                        <span class="badge badge-success preset-badge"><i class="fa-solid fa-check"></i> Selected</span>
                                        <div class="font-weight-bold text-dark" style="font-size: 14px;">{{ $preset['name'] }}</div>
                                        <div class="text-muted" style="font-size: 11px;">{{ $preset['tagline'] }}</div>
                                        <div class="swatch-row">
                                            <span class="swatch-dot" style="background: {{ $preset['theme_bg_color'] }};" title="Background"></span>
                                            <span class="swatch-dot" style="background: {{ $preset['theme_surface_color'] }};" title="Card Surface"></span>
                                            <span class="swatch-dot" style="background: {{ $preset['theme_primary_color'] }};" title="Primary"></span>
                                            <span class="swatch-dot" style="background: {{ $preset['theme_primary_dark_color'] }};" title="Secondary"></span>
                                            <span class="swatch-dot" style="background: {{ $preset['theme_accent_color'] }};" title="Accent"></span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Section 2: Custom Color Pickers + Live Mobile Preview -->
                <div class="row">
                    <div class="col-lg-8 mb-3">
                        <div class="card custom-border-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <span><i class="fa-solid fa-palette mr-2"></i>Custom Theme Colors (App Live Sync)</span>
                                <button type="button" id="btn_reset_theme" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa-solid fa-rotate-left mr-1"></i>Reset ZuLive Default
                                </button>
                            </div>
                            <div class="card-body">
                                @php
                                    $fields = [
                                        ['key' => 'theme_primary_color', 'label' => 'Primary Brand Color', 'hint' => 'Active tabs, main buttons, focus ring & highlights'],
                                        ['key' => 'theme_primary_dark_color', 'label' => 'Secondary Gradient Color', 'hint' => 'Gradient partner for Watch buttons & badges'],
                                        ['key' => 'theme_accent_color', 'label' => 'Alert / Live / Rent Accent', 'hint' => 'Live badges, rent tags & secondary highlights'],
                                        ['key' => 'theme_bg_color', 'label' => 'Main App Background', 'hint' => 'Screen scaffold background across pages'],
                                        ['key' => 'theme_surface_color', 'label' => 'Card & Bottom Bar Surface', 'hint' => 'Cards, bottom navigation bar, dialogs & inputs'],
                                        ['key' => 'theme_panel_bg_color', 'label' => 'Auth & Drawer Panel', 'hint' => 'Login panel & elevated modal backgrounds'],
                                        ['key' => 'theme_title_text_color', 'label' => 'Heading & Title Text', 'hint' => 'Primary readable titles across app screens'],
                                        ['key' => 'theme_desc_text_color', 'label' => 'Subtitle & Muted Text', 'hint' => 'Descriptions, hints & inactive icons'],
                                        ['key' => 'theme_btn_text_color', 'label' => 'Button Text on Primary', 'hint' => 'Text/icon color inside Primary CTA buttons'],
                                    ];
                                @endphp

                                <div class="row">
                                    @foreach($fields as $f)
                                        <div class="col-md-6 mb-3">
                                            <label class="font-weight-bold mb-1" style="font-size: 13px;">{{ $f['label'] }}</label>
                                            <div class="color-picker-group">
                                                <input type="color"
                                                       class="theme-color-picker"
                                                       data-target="{{ $f['key'] }}"
                                                       value="{{ $current[$f['key']] }}">
                                                <input type="text"
                                                       name="{{ $f['key'] }}"
                                                       id="{{ $f['key'] }}"
                                                       class="form-control theme-hex-input"
                                                       value="{{ $current[$f['key']] }}"
                                                       maxlength="7"
                                                       placeholder="#000000">
                                            </div>
                                            <small class="text-muted">{{ $f['hint'] }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fa-solid fa-mobile-screen mr-1"></i>Updates mobile &amp; web app live via <code>/api/general_setting</code>
                                </small>
                                <button type="button" class="btn btn-default mw-120" onclick="saveThemeSettings()">
                                    <i class="fa-solid fa-floppy-disk mr-1"></i>Save &amp; Apply Theme
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Live Mobile Preview -->
                    <div class="col-lg-4 mb-3">
                        <div class="card custom-border-card h-100">
                            <div class="card-header">
                                <i class="fa-solid fa-mobile-screen-button mr-2"></i>Live App Preview
                            </div>
                            <div class="card-body d-flex align-items-center justify-content-center" style="background: #f8fafc;">
                                <div class="phone-mockup" id="preview_bg">
                                    <div class="phone-topbar">
                                        <span class="font-weight-bold" id="preview_brand" style="font-size: 15px;">ZuLive</span>
                                        <span class="badge" id="preview_accent" style="font-size: 9px; color: #fff;">LIVE</span>
                                    </div>
                                    <div class="phone-chips">
                                        <span class="phone-chip" id="preview_chip_active">Movies</span>
                                        <span class="phone-chip" id="preview_chip_1">TV Shows</span>
                                        <span class="phone-chip" id="preview_chip_2">Shorts</span>
                                    </div>
                                    <div class="phone-hero" id="preview_hero">
                                        <div class="font-weight-bold" id="preview_title" style="font-size: 14px;">Featured Premiere</div>
                                        <div id="preview_desc" style="font-size: 11px; margin-top: 2px;">Action • 2h 15m • HD</div>
                                        <div class="phone-btn" id="preview_cta">
                                            <i class="fa-solid fa-play"></i> Watch Now
                                        </div>
                                    </div>
                                    <div class="px-3 mb-2 font-weight-bold" id="preview_sec_title" style="font-size: 12px;">Trending Now</div>
                                    <div class="phone-cards">
                                        @for($i = 1; $i <= 3; $i++)
                                            <div class="phone-poster preview-surface">
                                                <span class="badge preview-badge-accent" style="font-size: 8px; width: fit-content; color:#fff;">HD</span>
                                                <span class="preview-card-title" style="font-size: 10px; font-weight: 600;">Title {{ $i }}</span>
                                            </div>
                                        @endfor
                                    </div>
                                    <div class="phone-bottomnav" id="preview_bottomnav">
                                        <span id="preview_nav_active"><i class="fa-solid fa-house mr-1"></i>Home</span>
                                        <span class="preview-nav-inactive"><i class="fa-solid fa-magnifying-glass mr-1"></i>Find</span>
                                        <span class="preview-nav-inactive"><i class="fa-solid fa-bolt mr-1"></i>Shorts</span>
                                        <span class="preview-nav-inactive"><i class="fa-solid fa-user mr-1"></i>Space</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('pagescript')
<script>
    const defaultColors = @json($defaults);

    function isValidHex(val) {
        return /^#[0-9A-Fa-f]{6}$/.test(val);
    }

    function autoContrastText(hex) {
        const clean = hex.replace('#', '');
        const r = parseInt(clean.substring(0, 2), 16);
        const g = parseInt(clean.substring(2, 4), 16);
        const b = parseInt(clean.substring(4, 6), 16);
        const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return luminance > 0.58 ? '#000000' : '#FFFFFF';
    }

    function refreshPreview() {
        const primary = $('#theme_primary_color').val() || '#BAFA34';
        const secondary = $('#theme_primary_dark_color').val() || '#6EBE6B';
        const accent = $('#theme_accent_color').val() || '#EC2B4E';
        const bg = $('#theme_bg_color').val() || '#000000';
        const surface = $('#theme_surface_color').val() || '#1F2229';
        const panel = $('#theme_panel_bg_color').val() || '#0E0E16';
        const title = $('#theme_title_text_color').val() || '#FFFFFF';
        const desc = $('#theme_desc_text_color').val() || '#A9AAAC';
        const btnText = $('#theme_btn_text_color').val() || '#000000';

        $('#preview_bg').css('background', bg);
        $('#preview_brand, #preview_title, #preview_sec_title, .preview-card-title').css('color', title);
        $('#preview_desc, .preview-nav-inactive').css('color', desc);
        $('#preview_accent, .preview-badge-accent').css('background', accent);
        $('#preview_chip_active').css({ background: primary, color: btnText });
        $('#preview_chip_1, #preview_chip_2').css({ background: surface, color: desc });
        $('#preview_hero').css({ background: 'linear-gradient(135deg, ' + surface + ', ' + panel + ')' });
        $('#preview_cta').css({
            background: 'linear-gradient(90deg, ' + primary + ', ' + secondary + ')',
            color: btnText
        });
        $('.preview-surface').css('background', surface);
        $('#preview_bottomnav').css('background', surface);
        $('#preview_nav_active').css('color', primary);
    }

    $(document).on('input', '.theme-color-picker', function () {
        const target = $(this).data('target');
        const hex = $(this).val().toUpperCase();
        $('#' + target).val(hex);
        if (target === 'theme_primary_color') {
            const suggested = autoContrastText(hex);
            $('#theme_btn_text_color').val(suggested);
            $('.theme-color-picker[data-target="theme_btn_text_color"]').val(suggested);
        }
        $('#theme_preset').val('custom');
        $('.preset-card').removeClass('active');
        refreshPreview();
    });

    $(document).on('input', '.theme-hex-input', function () {
        const val = $(this).val().trim().toUpperCase();
        const id = $(this).attr('id');
        if (isValidHex(val)) {
            $('.theme-color-picker[data-target="' + id + '"]').val(val);
            $('#theme_preset').val('custom');
            $('.preset-card').removeClass('active');
            refreshPreview();
        }
    });

    $(document).on('click', '.preset-card', function () {
        const slug = $(this).data('preset');
        const colors = $(this).data('colors');
        $('.preset-card').removeClass('active');
        $(this).addClass('active');
        $('#theme_preset').val(slug);

        Object.keys(colors).forEach(function (k) {
            if (k.startsWith('theme_')) {
                $('#' + k).val(colors[k]);
                $('.theme-color-picker[data-target="' + k + '"]').val(colors[k]);
            }
        });
        refreshPreview();
    });

    $('#btn_reset_theme').on('click', function () {
        $('.preset-card[data-preset="zulive_lime"]').trigger('click');
    });

    function saveThemeSettings() {
        $("#dvloader").show();
        const formData = new FormData($("#theme_setting_form")[0]);
        $.ajax({
            type: 'POST',
            url: '{{ route("admin.theme.setting.save") }}',
            data: formData,
            cache: false,
            contentType: false,
            processData: false,
            success: function (resp) {
                $("#dvloader").hide();
                if (resp.status == 200) {
                    toastr.success(resp.success);
                } else {
                    const errs = Array.isArray(resp.errors) ? resp.errors.join('<br>') : (resp.errors || 'Failed to save theme.');
                    toastr.error(errs);
                }
            },
            error: function (XMLHttpRequest) {
                $("#dvloader").hide();
                toastr.error(XMLHttpRequest.responseText || 'Request failed');
            }
        });
    }

    $(document).ready(function () {
        refreshPreview();
    });
</script>
@endsection
