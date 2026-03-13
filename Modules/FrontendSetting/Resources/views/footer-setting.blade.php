@php
    $valueArray = is_array($landing_page_data->value)
        ? $landing_page_data->value
        : json_decode($landing_page_data->value ?? '{}', true);
    $sectionValues = $valueArray['sections'] ?? $valueArray;
    $sectionKeys = ['about', 'category', 'quicklinks', 'stayconnected'];
    $footerStatus = isset($landing_page_data->status) ? (int) $landing_page_data->status : 0;
@endphp

<!-- Snackbar div -->
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
    <span id="snackbar-message"></span>
</div>

<form method="POST" id="myForm" action="{{ route('footer_page_settings') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $landing_page_data->id ?? '' }}">
    <input type="hidden" name="type" value="footer_layout">
    <input type="hidden" name="menu_order" id="menu_order">

    <div class="form-group mb-4 p-3 bg-body border rounded shadow-sm">
        
        <div class="form-check form-switch d-flex align-items-center justify-content-between">

        <h2 class="mb-2">Footer Settings</h2>

            <div>
                <input type="hidden" name="status" value="0">
                <input type="checkbox" class="form-check-input" name="status" value="1" id="footer_setting"
                    {{ $footerStatus === 1 ? 'checked' : '' }}>
            </div>
        </div>
    </div>

    <div id="enable_footer_setting" style="display: {{ $footerStatus === 1 ? 'block' : 'none' }};">
        <ul id="sortable-list" class="list-group mb-4">
            @foreach ($sectionKeys as $key)
                <li class="list-group-item d-flex justify-content-between align-items-center mb-3"
                    data-section="{{ $key }}" draggable="true">
                    <div class="d-flex align-items-center gap-2">
                        <span>{{ ucfirst($key) }}</span>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="{{ $key }}" value="0"
                            {{ !empty($sectionValues[$key]) ? 'disabled' : '' }}>
                        <input type="checkbox" class="form-check-input" 
                               name="{{ $key }}" value="1"
                               id="{{ $key }}_toggle"
                               {{ !empty($sectionValues[$key]) ? 'checked' : '' }}>
                    </div>
                </li>

                @if($key === 'category')
                    <div class="footer-section-links-wrapper"
                        style="display: {{ !empty($sectionValues[$key]) ? 'block' : 'none' }};" id="category_section">
                        <div class="card card-body mb-3">
                            <h5 class="mb-3">Select Category </h5>
                            <div class="form-group">
                                <div class="mb-3">
                                    <select id="select_category" name="select_category[]" class="form-select w-100"
                                        multiple="multiple"></select>
                                </div>
                                <span id="categoryError" class="text-danger text-start mt-1"
                                    style="display:none"></span>
                            </div>
                        </div>
                    </div>
                @elseif($key === 'stayconnected')
                    <!-- Social Media Links Section -->
                    <div class="footer-section-links-wrapper"
                        style="display: {{ !empty($sectionValues[$key]) ? 'block' : 'none' }};" id="stayconnected_section">
                        <div class="card card-body mb-3">
                            <h5 class="mb-3">Social Media Links</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="facebook_link" class="form-label">Facebook Link</label>
                                        <input type="url" name="social_links[facebook]" id="facebook_link"
                                            class="form-control social-link-input"
                                            placeholder="https://www.facebook.com/yourpage/"
                                            value="{{ $sectionValues['social_links']['facebook'] ?? '' }}">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="youtube_link" class="form-label">Youtube Link</label>
                                        <input type="url" name="social_links[youtube]" id="youtube_link"
                                            class="form-control social-link-input"
                                            placeholder="https://www.youtube.com/yourchannel"
                                            value="{{ $sectionValues['social_links']['youtube'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="instagram_link" class="form-label">Instagram Link</label>
                                        <input type="url" name="social_links[instagram]" id="instagram_link"
                                            class="form-control social-link-input"
                                            placeholder="https://www.instagram.com/yourprofile/"
                                            value="{{ $sectionValues['social_links']['instagram'] ?? '' }}">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="x_link" class="form-label">X Link</label>
                                        <input type="url" name="social_links[twitter]" id="twitter_link"
                                            class="form-control social-link-input"
                                            placeholder="https://twitter.com/yourprofile"
                                            value="{{ $sectionValues['social_links']['twitter'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Regular Footer Links Section -->
                    <div class="footer-section-links-wrapper"
                        style="display: {{ !empty($sectionValues[$key]) ? 'block' : 'none' }};" id="{{ $key }}_section">
                        <div class="footer-links-list" data-section-links="{{ $key }}">
                            @php
                                $links = $sectionValues[$key . '_links'] ?? [];
                            @endphp
                            @foreach ($links as $i => $link)
                                <div class="card card-body mb-2 footer-link-row">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <input type="text"
                                                name="footer_links[{{ $key }}][{{ $i }}][label]"
                                                class="form-control mb-2" placeholder="Label"
                                                value="{{ $link['label'] ?? '' }}" required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text"
                                                name="footer_links[{{ $key }}][{{ $i }}][url]"
                                                class="form-control mb-2" placeholder="URL"
                                                value="{{ $link['url'] ?? '' }}" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-center">
                                            <button type="button"
                                                class="btn btn-danger remove-link-btn">&times;</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </ul>
    </div>
    <div class="d-flex justify-content-end mt-4">
        <button id="saveButton" class="btn btn-primary">Save</button>
    </div>
</form>

<!-- Select2 JS -->
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>

<script>
$(document).ready(function() {
    // --- FOOTER ENABLE/DISABLE ---
    $('#footer_setting').on('change', function () {
        if ($(this).is(':checked')) {
            $('#enable_footer_setting').slideDown();
        } else {
            $('#enable_footer_setting').slideUp();
        }
    });


    // --- CATEGORY SELECT2 ---
    const sectionValues = @json($sectionValues);
    const savedCategories = sectionValues['select_category'] || [];

    $('#select_category').select2({
        placeholder: "Select one or more Categories",
        width: '100%'
    });

    function loadCategories(selectedIds = []) {
        $.ajax({
            url: "{{ route('service-section-list') }}",
            method: 'GET',
            success: function(response) {
                if (response.status && response.data) {
                    $('#select_category').empty();

                    response.data.forEach(service => {
                        const selected =
                            selectedIds.includes(service.id.toString()) ||
                            selectedIds.includes(service.id);

                        $('#select_category').append(
                            `<option value="${service.id}" ${selected ? 'selected' : ''}>${service.name}</option>`
                        );
                    });

                    $('#select_category').trigger('change');
                }
            },
            error: function(err) {
                console.error('Failed to load services', err);
                showSnackbar('Failed to load services');
            }
        });
    }

    // Load categories with saved selections
    loadCategories(savedCategories);

    // --- CATEGORY TOGGLE ---
    $('#category_toggle').on('change', function() {
        if ($(this).is(':checked')) {
            $('#category_section').slideDown();
            loadCategories(savedCategories);
        } else {
            $('#category_section').slideUp();
            $('#select_category').val(null).trigger('change');
        }
    });

    if ($('#category_toggle').is(':checked')) {
        $('#category_section').show();
    } else {
        $('#category_section').hide();
    }

    // --- OTHER SECTIONS TOGGLE ---
    ['about','quicklinks','stayconnected'].forEach(key => {
        $('#' + key + '_toggle').on('change', function() {
            if ($(this).is(':checked')) {
                $('#' + key + '_section').slideDown();
            } else {
                $('#' + key + '_section').slideUp();
            }
        });
    });
});
</script>
