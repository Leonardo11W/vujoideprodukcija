@props(['experts', 'tabpage'])

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="section-content py-4">
    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Toggle Section --}}
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center border p-3 rounded">
                        <div>
                            <h5 class="mb-0">Top Experts</h5>
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" id="section_7" class="form-check-input" name="status" data-type="section_7" style="width: 3em; height: 1.5em;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Expert Selection --}}
            <div class="row mb-4 d-none" id="enable_section_7">
                <div class="col">
                    <div class="border p-3 rounded bg-body text-start">
                        <h6 class="fw-semibold mb-3">Select Experts</h6>
                        <div class="mb-3">
                            <select id="expert_id" name="expert_id[]" placeholder="Select Experts" class="form-select w-100" multiple style="width: 100%;">
                                @foreach($experts as $expert)
                                    <option value="{{ $expert->id }}">
                                        {{ $expert->first_name }} {{ $expert->last_name }} 
                                    </option>
                                @endforeach
                            </select>

                            <span id="expertError" class="text-danger text-start mt-1" style="display:none"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Save Button --}}
            <div class="row">
                <div class="d-flex justify-content-end mt-4">
                    <button id="saveButton" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Snackbar --}}
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
</div>

<script>
$(function () {
    const $toggle = $('#section_7');
    const $expertSection = $('#enable_section_7');
    const $expertSelect = $('#expert_id');
    const $statusLabel = $('#statusLabel');
    const $saveButton = $('#saveButton');
    const expertError = $('#expertError');

    const sectionType = $toggle.data('type');
    const page = "{{ $tabpage ?? 'home' }}";
    const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";
    const postDataRoute = "{{ route('saveLandingLayoutPageConfig') }}";
    const _token = $('meta[name="csrf-token"]').attr('content');


    $expertSelect.select2({
        placeholder: 'Select Experts',
        width: '100%'
    });

    function toggleVisibility(show) {
        if (show) {
            $expertSection.removeClass('d-none').hide().slideDown();
            $expertSelect.prop('required', true);
            $statusLabel.text('Enabled');
        } else {
            $expertSection.slideUp(() => {
                $expertSection.addClass('d-none');
                $expertSelect.prop('required', false).val(null).trigger('change');
                $statusLabel.text('Disabled');
            });
        }
    }


    function showError(input, message) {
        input.text(message).show();
    }

    function clearError(input) {
        input.hide().text('');
    }


    function loadConfig() {
        $.ajax({
            url: getDataRoute,
            method: 'POST',
            data: {
                _token,
                type: 'landing-page-setting',
                page: page
            },
            success: function (res) {
                if (res.success && res.data) {
                    let config = {};
                    try {
                        config = typeof res.data.value === 'string'
                            ? JSON.parse(res.data.value)
                            : res.data.value || {};
                    } catch (e) {
                        console.error("Invalid JSON for section_7:", e);
                        config = {};
                    }

                    const status = Number(config.status || 0);
                    $toggle.prop('checked', status === 1);
                    toggleVisibility(status === 1);

                    if (config.expert_id) {
                        const selectedExperts = Array.isArray(config.expert_id)
                            ? config.expert_id.map(String)
                            : [String(config.expert_id)];
                        $expertSelect.val(selectedExperts).trigger('change');
                    }
                }
            },
            error: function () {
                toggleVisibility(false);
                $expertSelect.val(null).trigger('change');
            }
        });
    }

    $toggle.on('change', function () {
        toggleVisibility($(this).is(':checked'));
    });

    loadConfig();

    $saveButton.on('click', function () {
        const statusVal = $toggle.is(':checked') ? 1 : 0;
        const expertIds = $expertSelect.val();


        clearError(expertError);

        if (statusVal === 1 &&  expertIds.length === 0) {
            showError(expertError, 'Please select at least one export when enabled.');
            return;
        }


        const postData = {
            _token,
            type: sectionType,
            page,
            status: statusVal,
            expert_id: expertIds
        };

        $saveButton.prop('disabled', true).text('Saving...');

        $.ajax({
            url: postDataRoute,
            method: 'POST',
            data: postData,
            success: function (res) {
                if (res.success) {
                    window.successSnackbar(res.message || 'Saved successfully.');
                } else {
                    window.errorSnackbar(res.message || 'Failed to save.');
                }
            },
            error: function () {
                window.errorSnackbar('An error occurred while saving.');
            },
            complete: function () {
                $saveButton.prop('disabled', false).text('Save');
            }
        });
    });
});
</script>
