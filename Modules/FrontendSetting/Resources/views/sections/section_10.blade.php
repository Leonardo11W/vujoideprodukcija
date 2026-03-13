<div class="section-content">
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="form-group">
                    <div
                        class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded">
                        <div>
                            <h4 class="text-xl font-bold">Happy Customer</h4>

                        </div>
                        <input type="checkbox" id="section_10" class="form-check-input section_10" name="status"
                            data-type="section_10" style="width: 3em; height: 1.5em;">
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button id="saveButton" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Snackbar container --}}
<div id="snackbar"
    style="visibility:hidden; min-width:250px;
            background-color:#333; color:#fff; text-align:center; border-radius:4px;
            padding:16px; position:fixed; z-index:9999;
            left:20px; bottom:30px; font-size:17px; opacity: 0; transition: opacity 0.5s ease;">
</div>

<script>
    $(document).ready(function() {
        const $section10 = $('#section_10');
        const $statusLabel = $('#statusLabel');
        const $snackbar = $('#snackbar');

        function updateSectionState(isEnabled) {
            $statusLabel.text(isEnabled ? 'Enabled' : 'Disabled');
        }

        // Snackbar helper
        function showSnackbar(message) {
            $snackbar.text(message);
            $snackbar.css({
                'background-color': '#333',
                'color': '#fff',
                visibility: 'visible',
                opacity: '1'
            });
            setTimeout(() => {
                $snackbar.css({
                    opacity: '0',
                    visibility: 'hidden'
                });
            }, 3000);
        }

        // Initialize label based on checkbox state
        updateSectionState($section10.prop('checked'));

        // Load config from server
        function loadConfig(type) {
            const _token = $('meta[name="csrf-token"]').attr('content');
            const page = "{{ $tabpage }}";
            const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";

            $('.form-check-input').prop('disabled', true);

            $.ajax({
                url: getDataRoute,
                type: "POST",
                data: {
                    type: 'landing-page-setting',
                    key: 'section_10',
                    _token,
                    page
                },
                success: function(response) {
                    if (response?.data?.value) {
                        $section10.prop('checked', response.data.status === 1);
                        updateSectionState(response.data.status === 1);
                    }
                },
                error: function(error) {
                    console.error('Load error:', error);
                    showSnackbar('Failed to load configuration.');
                },
                complete: function() {
                    $('.form-check-input').prop('disabled', false);
                }
            });
        }

        // Load initial config
        const sectionType = $section10.data('type');
        if (sectionType) loadConfig(sectionType);

        $section10.on('change', function() {
            updateSectionState($(this).prop('checked'));
        });

        // Save handler for the main Save button
        $('#saveButton').click(function(e) {
            e.preventDefault();

            const _token = $('meta[name="csrf-token"]').attr('content');
            const type = $section10.data('type');
            const page = "{{ $tabpage }}";

            const dataToSend = {
                _token: _token,
                type: type,
                page: page,
                status: $section10.prop('checked') ? 1 : 0,
                customer_id: $section10.prop('checked') ? 1 : 0
            };

            // Disable button to prevent multiple clicks
            $(this).prop('disabled', true).text('Saving...');

            $.ajax({
                url: "{{ route('saveLandingLayoutPageConfig') }}",
                method: 'POST',
                data: dataToSend,
                success: function(response) {
                    if (response.success) {
                        showSnackbar('Happy Customer section saved successfully!');
                    } else {
                        showSnackbar('Failed to save section.');
                    }
                },
                error: function(error) {
                    console.error('Save error:', error);
                    showSnackbar('An error occurred while saving.');
                },
                complete: function() {
                    $('#saveButton').prop('disabled', false).text('Save');
                }
            });
        });
    });
</script>
