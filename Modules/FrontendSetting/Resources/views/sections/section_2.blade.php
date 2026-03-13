{{-- section_2.blade.php --}}

<div class="section-content p-4 border rounded">

    <div class="row">
        <div class="form-group w-100">
            <div class="form-check form-switch d-flex justify-content-between align-items-center p-3 border rounded">
                <div>
                    <h4 class="text-xl font-bold">Quick booking</h4>

                </div>
                <input type="checkbox" id="section_2" class="form-check-input" name="status" data-type="section_2"
                    style="width: 3em; height: 1.5em;">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-4">
        <button id="saveButton" class="btn btn-primary">Save</button>
    </div>

</div>

<!-- Snackbar container -->
<div id="snackbar" class="snackbar-container  snackbar-pos bottom-left">
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
    $(document).ready(function() {
        const $section2 = $('#section_2');
        const $statusLabel = $('#statusLabel');
        const $snackbar = $('#snackbar');
        const sectionType = $section2.data('type');
        const page = "{{ $tabpage }}";
        const getDataRoute = "{{ url('/api/get-landing-layout-page-config') }}";
        const postDataRoute = "{{ route('saveLandingLayoutPageConfig') }}";
        const _token = $('meta[name="csrf-token"]').attr('content');


        // Load initial config
        loadConfig(sectionType);

        $section2.on('change', function() {
            updateSectionState($(this).prop('checked'));
        });

        $('#saveButton').on('click', function(e) {
            e.preventDefault();

            const sectionEnabled = $section2.is(':checked');
            const section_2 = sectionEnabled ? 1 : 0;
            const statusMessage = sectionEnabled ? 'Enabled' : 'Disabled';

            const postData = {
                _token: _token,
                type: 'section_2',
                section_2: section_2,
                message: statusMessage,
                page: page
            };

            $('#saveButton').prop('disabled', true).text('Saving...');

            $.ajax({
                url: postDataRoute,
                type: 'POST',
                data: postData,
                success: function(res) {
                    window.successSnackbar(res.message || 'Saved successfully');
                },
                error: function(xhr) {
                    let message = 'Error saving data.';
                    if (xhr.responseJSON?.message) {
                        message = xhr.responseJSON.message;
                    }
                    window.errorSnackbar(message);
                },
                complete: function() {
                    $('#saveButton').prop('disabled', false).text('Save');
                }
            });
        });

        function updateSectionState(isEnabled) {
            $statusLabel.text(isEnabled ? 'Enabled' : 'Disabled');
        }

        function loadConfig(type) {
            $('.form-check-input').prop('disabled', true);

            $.ajax({
                url: getDataRoute,
                type: 'POST',
                data: {
                    type: 'landing-page-setting',
                    key: 'section_2',
                    _token,
                    page
                },
                success: function(response) {

                    let config = response && response.data && response.data.value ? response.data
                        .value : {};
                    if (typeof config === 'string') {
                        try {
                            config = JSON.parse(config);
                        } catch (e) {
                            config = {};
                        }
                    }

                    let isEnabled = Number(config.section_2) === 1;
                    $section2.prop('checked', isEnabled);
                    updateSectionState(isEnabled);
                    if (typeof config.section_2 === 'undefined') {
                        console.warn('config.section_2 is undefined! Full config:', config);
                    }
                },
                error: function() {
                    $section2.prop('checked', false);
                    updateSectionState(false);
                },
                complete: function() {
                    $('.form-check-input').prop('disabled', false);
                }
            });
        }



    });
</script>
