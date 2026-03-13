<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="section-content" style="text-align: left;">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="form-group">
                    <div
                        class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded">
                        <div>
                            <h4 class="text-xl font-bold">Faq Section</h4>
                        </div>
                        <input type="checkbox" id="section_9" class="form-check-input section_9" name="status"
                            data-type="section_9" style="width: 3em; height: 1.5em;">
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end mt-4">
                <button id="saveButton" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Snackbar --}}
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
    <span id="snackbar-message"></span>
</div>

<script>
    $(document).ready(function() {
        const $section9 = $('#section_9');
        const $enableSection9 = $('#enable_section_9');
        const $statusLabel = $('#statusLabel');
        const $titleId = $('#title_id');
        const $subtitleId = $('#subtitle_id');
        const $descriptionId = $('#description_id');
        const $snackbar = $('#snackbar');


        function updateSectionState(isEnabled) {
            if (isEnabled) {
                $enableSection9.removeClass('d-none').hide().slideDown(200);
                $titleId.prop('required', true);
                $subtitleId.prop('required', true);
                $descriptionId.prop('required', true);
            } else {
                $enableSection9.slideUp(200, () => $enableSection9.addClass('d-none'));
                $titleId.add($subtitleId).add($descriptionId).prop('required', false);
            }
        }

        $section9.on('change', function() {
            const isChecked = $(this).prop('checked');
            updateSectionState(isChecked);
            $statusLabel.text(isChecked ? 'Enabled' : 'Disabled');
        });

        function loadConfig(type) {
            const _token = $('meta[name="csrf-token"]').attr('content');
            const page = "{{ $tabpage }}";
            const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";

            $('.form-check-input').prop('disabled', true);

            $.ajax({
                url: getDataRoute,
                type: 'POST',
                data: {
                    type: 'landing-page-setting',
                    key: 'section_9',
                    page,
                    _token
                },
                success: function(response) {

                    let config = {};
                    try {
                        config = typeof response.data?.value === 'string' ?
                            JSON.parse(response.data.value) :
                            response.data?.value || {};
                    } catch (e) {
                        console.error("Invalid JSON in config:", e);
                    }

                    const enabled = config.status === 1;
                    $section9.prop('checked', enabled);
                    updateSectionState(enabled);
                    $statusLabel.text(enabled ? 'Enabled' : 'Disabled');

                    $titleId.val(config.title_id || '');
                    $subtitleId.val(config.subtitle_id || '');
                    $descriptionId.val(config.description_id || '');
                },
                error: function(error) {
                    console.error('Error loading configuration:', error);
                },
                complete: function() {
                    $('.form-check-input').prop('disabled', false);
                }
            });
        }

        loadConfig($section9.data('type'));

        $('#saveButton').click(function(e) {
            e.preventDefault();

            const _token = $('meta[name="csrf-token"]').attr('content');
            const type = $section9.data('type');
            const page = "{{ $tabpage }}";
            const status = $section9.prop('checked') ? 1 : 0;

            const dataToSend = {
                _token,
                type,
                page,
                status,
                title_id: status ? $titleId.val() : '',
                subtitle_id: status ? $subtitleId.val() : '',
                description_id: status ? $descriptionId.val() : ''
            };



            $(this).prop('disabled', true).text('Saving...');

            $.ajax({
                url: "{{ route('saveLandingLayoutPageConfig') }}",
                type: 'POST',
                data: dataToSend,
                success: function(response) {
                    window.successSnackbar(response.success ?
                        'FAQ section settings saved!' : 'Failed to save FAQ settings.');
                },
                error: function(error) {
                    console.error('Save error:', error);
                    window.errorSnackbar('An error occurred while saving.');
                },
                complete: function() {
                    $('#saveButton').prop('disabled', false).text('Save');
                }
            });
        });
    });
</script>
