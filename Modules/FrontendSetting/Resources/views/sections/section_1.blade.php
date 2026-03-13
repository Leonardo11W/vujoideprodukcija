@php
    $sectionData = $landing_page ?? [];

   
    if (!empty($sectionData->value)) {
        $sectionData = json_decode($sectionData->value, true);
    }
    $title = $sectionData['title'] ?? '';
    $description = $sectionData['description'] ?? '';
    $enableSearch = $sectionData['enable_search'] ?? 0;
    $sectionEnabled = $sectionData['section_1'] ?? 0;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="card">
    <div class="card-body">

        <!-- Toggle -->
        <div class="form-check form-switch d-flex justify-content-between align-items-center p-3 border rounded mb-3">
            <div>
                <h4 class="mb-0">Banner Section</h4>
            </div>
            <input type="checkbox" id="sectionToggle" class="form-check-input" {{ $sectionEnabled ? 'checked' : '' }}>
        </div>

        <!-- Fields -->
        <div id="sectionFields" class="{{ !$sectionEnabled ? 'd-none' : '' }}">
            <div class="mb-3">
                <label class="d-block text-start w-100 mb-2" for="title">Title <span class="text-danger">*</span></label>
                <input type="text" id="title" class="form-control" value="{{ $title }}" placeholder="Enter title">
                <span id="titleError" class="text-danger text-start mt-1" style="display:none"></span>
            </div>
            <div class="mb-3">
                <label class="d-block text-start w-100 mb-2" for="description">Description</label>
                <textarea id="description" class="form-control" placeholder="Enter description" maxlength="1000">{{ $description }}</textarea>
            </div>
            <div class="form-check form-switch d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                <label class="text-left mb-0" for="enableSearch">Enable Search</label>
                <input type="checkbox" id="enableSearch" class="form-check-input" {{ $enableSearch ? 'checked' : '' }}>
            </div>
        </div>

        <!-- Save -->
        <div class="text-end">
            <button id="saveBtn" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
    <span id="snackbar-message"></span>
</div>

<script>
$(function () {
    const toggle = $('#sectionToggle');
    const fields = $('#sectionFields');
    const title = $('#title');
    const description = $('#description');
    const search = $('#enableSearch');
    const saveBtn = $('#saveBtn');
    const titleError = $('#titleError');

    function showError(input, message) {
        input.text(message).show();
    }

    function clearError(input) {
        input.hide().text('');
    }

    // // Load section config and populate fields
    // function reloadSectionConfig() {
    //     $.post("{{ route('getLandingLayoutPageConfig') }}", {
    //         _token: $('meta[name="csrf-token"]').attr('content'),
    //         type: 'section_1',
    //         page: "{{ $tabpage ?? 'default' }}"
    //     }, function(res) {
    //         let config = res.data && res.data.value ? res.data.value : {};
    //         if (typeof config === 'string') {
    //             try { config = JSON.parse(config); } catch(e) { config = {}; }
    //         }

    //         const enabled = config.section_1 == 1;

    //         // Set toggle
    //         toggle.prop('checked', enabled);

    //         // Show/hide fields with slide animation
    //         if (enabled) {
    //             fields.removeClass('d-none').hide().slideDown();
    //         } else {
    //             fields.slideUp(0, function(){
    //                 fields.addClass('d-none');
    //             });
    //         }

    //         // Populate fields
    //         title.val(config.title || '');
    //         description.val(config.description || '');
    //         search.prop('checked', config.enable_search == 1);
    //     });
    // }

    // Toggle section smoothly
    toggle.on('change', function () {
        const isEnabled = $(this).is(':checked');
        if (isEnabled) {
            fields.removeClass('d-none').hide().slideDown();
        } else {
            fields.slideUp(300, function() {
                fields.addClass('d-none');
            });
        }
    });

    // Save section
    saveBtn.on('click', function () {
        const isEnabled = toggle.is(':checked');
        const titleVal = title.val().trim();
        const descriptionVal = description.val().trim();
        const enableSearchVal = search.is(':checked') ? 1 : 0;

        clearError(titleError);

        if (isEnabled && titleVal === '') {
            showError(titleError, 'Title is required');
            title.focus();
            return;
        }

        saveBtn.prop('disabled', true).text('Saving...');

        $.post("{{ route('saveLandingLayoutPageConfig') }}", {
            _token: $('meta[name="csrf-token"]').attr('content'),
            type: 'section_1',
            page: "{{ $tabpage ?? 'default' }}",
            title: titleVal,
            description: descriptionVal,
            enable_search: enableSearchVal,
            section_1: isEnabled ? 1 : 0
        }, function(res) {
            window.successSnackbar(res.message || 'Saved successfully.');
            // Reload fields to reflect saved data
           
        }).fail(function() {
            window.errorSnackbar('Save failed.');
        }).always(function() {
            saveBtn.prop('disabled', false).text('Save');
        });
    });

    // Initialize
    // reloadSectionConfig();
});

</script>
