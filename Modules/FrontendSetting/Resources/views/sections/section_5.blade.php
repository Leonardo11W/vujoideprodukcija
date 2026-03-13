{{-- section_5.blade.php --}}
@props(['tabpage'])

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- jQuery -->
<!-- <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script> -->

<!-- Select2 CSS -->
<!-- <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" /> -->

<!-- Select2 JS -->
<!-- <script src="{{ asset('vendor/select2/select2.min.js') }}"></script> -->

<div class="p-4 bg-body shadow rounded max-w-sm">
    <div class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded">
        <div>
            <h4 class="text-xl font-bold">Package Section</h4>
            <label class="form-check-label d-block" for="section_5" id="statusLabel">Disabled</label>
        </div>
        <input type="checkbox" id="section_5" class="form-check-input section_5" name="status" data-type="section_5" style="width: 3em; height: 1.5em;">
    </div>

    <div id="enable_section_5" class="d-none mt-3">
        <label for="package_ids" class="form-label">Select Packages</label>
        <select id="package_ids" name="package_ids[]" class="form-select w-full p-2 border rounded" multiple></select>
    </div>
</div>

<div class="d-flex justify-content-end mt-4">
    <button id="saveButton" class="btn btn-primary">Save</button>
</div>

<div id="snackbar"
     style="visibility:hidden; min-width:250px;
            background-color:#333; color:#fff; text-align:center; border-radius:4px;
            padding:16px; position:fixed; z-index:9999;
            left:20px; bottom:30px; font-size:17px; opacity: 0; transition: opacity 0.5s ease;">
</div>

<script>
$(document).ready(function() {
    const $section_5 = $('#section_5');
    const $enableSection5 = $('#enable_section_5');
    const $statusLabel = $('#statusLabel');
    const $packageIds = $('#package_ids');
    const _token = $('meta[name="csrf-token"]').attr('content');
    const page = "{{ $tabpage ?? 'home' }}";
    const type = 'landing-page-setting';
    const key = 'section_5';

    const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";
    const getPackagesRoute = "{{ route('get.packages') }}";
    const saveConfigRoute = "{{ route('saveLandingLayoutPageConfig') }}";

    $packageIds.select2({
        placeholder: "Select one or more packages",
        width: '100%'
    });

    function showSnackbar(message) {
        const snackbar = $('#snackbar');
        snackbar.text(message).css({visibility: 'visible', opacity: 1});
        setTimeout(() => {
            snackbar.css({opacity: 0});
            setTimeout(() => snackbar.css('visibility', 'hidden'), 500);
        }, 3000);
    }

    function updateSectionState(isEnabled) {
        if (isEnabled) {
            $enableSection5.removeClass('d-none').hide().slideDown(200);
            $packageIds.prop('required', true);
        } else {
            $enableSection5.slideUp(200, function() {
                $(this).addClass('d-none');
                $packageIds.prop('required', false).val(null).trigger('change');
            });
        }
    }

    function loadPackages(selectedIds = []) {
        $.ajax({
            url: getPackagesRoute,
            method: 'POST',
            data: { _token, branch_id: 1 },
            success: function(response) {
                if (response.success && Array.isArray(response.packages)) {
                    $packageIds.empty();
                    response.packages.forEach(pkg => {
                        const selected = selectedIds.includes(pkg.id.toString());
                        $packageIds.append(`<option value="${pkg.id}" ${selected ? 'selected' : ''}>${pkg.name}</option>`);
                    });
                    $packageIds.trigger('change');
                }
            },
            error: function(err) {
                console.error('Failed to load packages', err);
            }
        });
    }

    function loadConfig() {
        $section_5.prop('disabled', true);

        $.ajax({
            url: getDataRoute,
            method: 'POST',
            data: { _token, type, key, page },
            success: function(response) {
                let config = {};
                try {
                    config = typeof response.data?.value === 'string'
                        ? JSON.parse(response.data.value)
                        : response.data?.value || {};
                } catch (err) {
                    console.error("Invalid JSON:", response.data?.value, err);
                    showSnackbar('Invalid configuration data.');
                }

                const enabled = config.status == 1;
                const selectedIds = (config.package_ids || []).map(String);

                $section_5.prop('checked', enabled);
                $statusLabel.text(enabled ? 'Enabled' : 'Disabled');
                updateSectionState(enabled);

                if (enabled) {
                    loadPackages(selectedIds);
                }
            },
            error: function(error) {
                console.error('Error loading config:', error);
            },
            complete: function() {
                $section_5.prop('disabled', false);
            }
        });
    }

    updateSectionState($section_5.prop('checked'));
    $statusLabel.text($section_5.prop('checked') ? 'Enabled' : 'Disabled');
    loadConfig();

    $section_5.on('change', function() {
        const isChecked = $(this).prop('checked');
        updateSectionState(isChecked);
        $statusLabel.text(isChecked ? 'Enabled' : 'Disabled');
        if (isChecked) loadPackages();
    });

    $('#saveButton').on('click', function(e) {
        e.preventDefault();

        const isEnabled = $section_5.prop('checked');
        // Ensure package_ids is an array of integers
        const selectedIds = (isEnabled ? $packageIds.val() : []) || [];
        const packageIdsInt = selectedIds.map(id => parseInt(id, 10)).filter(id => !isNaN(id));

        // Always set page to 'landing-page-setting' as required by controller
        const dataToSend = {
            _token,
            type: 'section_5',
            page: 'landing-page-setting',
            status: isEnabled ? 1 : 0,
            package_ids: isEnabled ? packageIdsInt : [],
        };

        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: saveConfigRoute,
            method: 'POST',
            data: dataToSend,
            success: function(response) {
                if (response.success) {
                    showSnackbar('Settings saved successfully!');
                } else {
                    showSnackbar('Failed to save settings. ' + (response.message || ''));
                }
            },
            error: function(err) {
                console.error('Save error:', err);
                showSnackbar('An error occurred while saving settings.');
            },
            complete: function() {
                $('#saveButton').prop('disabled', false).text('Save');
            }
        });
    });
});
</script>
