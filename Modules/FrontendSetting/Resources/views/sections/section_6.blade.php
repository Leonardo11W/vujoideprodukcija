@props(['tabpage'])

<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- jQuery -->
<!-- <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script> -->

<!-- Select2 CSS -->
<!-- <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" /> -->
<!-- Select2 JS -->
<!-- <script src="{{ asset('vendor/select2/select2.min.js') }}"></script> -->

<div class="section-content">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="form-group">
                    <div
                        class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded">
                        <div>
                            <h4 class="text-xl font-bold">Membership Section</h4>
                            <label class="form-check-label d-block" for="section_6" id="statusLabel">Disabled</label>
                        </div>
                        <input type="checkbox" id="section_6" class="form-check-input" name="status"
                            data-type="section_6" style="width: 3em; height: 1.5em;">
                    </div>
                </div>
            </div>

            <div class="row mt-3 d-none" id="enable_section_6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="membership_id">
                    <label class="form-check-label" for="membership_id">Enable Membership Display</label>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button id="saveButton" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar"
    style="visibility:hidden; min-width:250px;
            background-color:#333; color:#fff; text-align:center; border-radius:4px;
            padding:16px; position:fixed; z-index:9999;
            left:20px; bottom:30px; font-size:17px; opacity: 0; transition: opacity 0.5s ease;">
</div>

<script>
    $(document).ready(function() {
        const $section6 = $('#section_6');
        const $enableSection6 = $('#enable_section_6');
        const $statusLabel = $('#statusLabel');
        const $membershipId = $('#membership_id');
        const _token = $('meta[name="csrf-token"]').attr('content');
        const page = "{{ $tabpage ?? 'home' }}";
        const type = $section6.data('type') || 'section_6';

        function showSnackbar(message) {
            const snackbar = $('#snackbar');
            snackbar.text(message);
            snackbar.css({
                visibility: 'visible',
                opacity: 1
            });
            setTimeout(() => {
                snackbar.css({
                    opacity: 0
                });
                setTimeout(() => snackbar.css('visibility', 'hidden'), 500);
            }, 3000);
        }

        function updateSectionState(isEnabled) {
            if (isEnabled) {
                $enableSection6.removeClass('d-none').hide().slideDown(200);
                $membershipId.prop('required', true);
            } else {
                $enableSection6.slideUp(200, function() {
                    $(this).addClass('d-none');
                    $membershipId.prop('required', false);
                });
            }
        }

        function loadConfig() {
            $.ajax({
                url: "{{ route('getLandingLayoutPageConfig') }}",
                type: 'POST',
                data: {
                    _token,
                    type,
                    page
                },
                success: function(response) {
                    let config = {};
                    try {
                        config = typeof response.data?.value === 'string' ?
                            JSON.parse(response.data.value) :
                            response.data?.value || {};
                    } catch (e) {
                        console.error("Invalid config format", e);
                        showSnackbar("Error parsing configuration.");
                    }

                    const isEnabled = config.status == 1;
                    const membershipChecked = config.membership_id == 1;

                    $section6.prop('checked', isEnabled);
                    $membershipId.prop('checked', membershipChecked);
                    updateSectionState(isEnabled);
                    $statusLabel.text(isEnabled ? 'Enabled' : 'Disabled');
                },
                error: function(error) {
                    console.error('Error loading config:', error);
                    showSnackbar("Failed to load configuration.");
                }
            });
        }

        $('#saveButton').click(function() {
            const isEnabled = $section6.prop('checked');
            const statusMessage = isEnabled ? 'Enabled' : 'Disabled';

            const dataToSend = {
                _token: _token,
                type: type,
                page: page,
                status: isEnabled ? 1 : 0,
                membership_id: $membershipId.prop('checked') ? 1 : 0,
                message: statusMessage // <- send status message
            };

            $(this).prop('disabled', true).text('Saving...');

            $.ajax({
                url: "{{ route('saveLandingLayoutPageConfig') }}",
                type: 'POST',
                data: dataToSend,
                success: function(response) {
                    if (response.success) {
                        showSnackbar('Settings saved successfully!');
                    } else {
                        showSnackbar('Failed to save settings.');
                    }
                },
                error: function(error) {

                    showSnackbar('Error saving settings.');
                },
                complete: function() {
                    $('#saveButton').prop('disabled', false).text('Save');
                }
            });
        });

        // Initialize state
        updateSectionState($section6.prop('checked'));
        $statusLabel.text($section6.prop('checked') ? 'Enabled' : 'Disabled');
        loadConfig();

        $section6.on('change', function() {
            const isChecked = $(this).prop('checked');
            updateSectionState(isChecked);
            $statusLabel.text(isChecked ? 'Enabled' : 'Disabled');
        });
    });
</script>
