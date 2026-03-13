<!-- Snackbar div, place this in your header -->
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
    <span id="snackbar-message"></span>
</div>

@if (session('success'))
    <script>
        window.onload = function() {
            const modalHtml = `
                <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="successModalLabel">Success</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        {{ session('success') }}
                      </div>
                    </div>
                  </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        };
    </script>
@endif

<form method="POST" action="{{ route('heading_page_settings') }}" enctype="multipart/form-data" id="myForm">
    @csrf


    <input type="hidden" name="id" value="{{ $landing_page_data->id ?? '' }}">
    <input type="hidden" name="type" value="{{ $page }}">

    <div class="p-4 bg-body shadow rounded mb-4">
        <div class="border-bottom pb-3 mb-4">
            <h2 class="text-xl font-bold text-start">Header Settings</h2>
        </div>

        <div class="form-group mb-3">
            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-body">
                <label for="header_setting"
                    class="mb-0 flex-grow-1 text-start">{{ __('messages.enable_header_setting') }}</label>
                <div class="form-check form-switch m-0">
                    <input type="hidden" name="status" value="0">
                    <input type="checkbox" class="form-check-input header_setting" name="status" id="header_setting"
                        value="1" data-type="header_setting"
                        {{ !empty($landing_page_data) && $landing_page_data->status == 1 ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </div>

    @php
        $valueArray = $landing_page_data->value ?? '{}';
        // Safely decode, handling both string and array cases
        while (is_string($valueArray)) {
            $valueArray = json_decode($valueArray, true);
        }
        if (!is_array($valueArray)) {
            $valueArray = [];
        }
        $menuSections = ['selectbranch', 'mybooking', 'category', 'service', 'shop'];
    @endphp

    <div class="row d-none" id='enable_header_setting'>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body setting-pills">
                    <div class="row">
                        <div class="col-12">
                            <div class="p-4 bg-body shadow rounded mb-4">
                                <h3 class="text-lg font-semibold mb-3 text-start">Menu Items</h3>
                                <ul class="nav flex-column nav-pills nav-fill tabslink list row-gap-2 column-gap-1 rounded-0 p-3"
                                    id="tabs-text" role="tablist">
                                    @foreach ($menuSections as $key)
                                        <li class="nav-item list-item mb-3" data-section="{{ $key }}"
                                            draggable="true" id="item-{{ $key }}">
                                            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-body"
                                                style="cursor: grab;">
                                                <label for="{{ $key }}"
                                                    class="mb-0 flex-grow-1 text-start">{{ __('messages.' . $key) }}</label>
                                                <div class="form-check form-switch m-0">
                                                    <input type="checkbox" class="form-check-input"
                                                        name="{{ $key }}" id="{{ $key }}"
                                                        {{ !empty($valueArray[$key]) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="p-4 bg-body shadow rounded">
                                <h3 class="text-lg font-semibold mb-3 text-start">Additional Settings</h3>
                                @foreach ([
        'enable_search' => 'Search',
        'enable_language' => 'Language',
        'enable_darknight_mode' => 'Dark Mode',
    ] as $field => $label)
                                    <div class="form-group mb-3">
                                        <div
                                            class="d-flex align-items-center justify-content-between p-2 border rounded bg-body">
                                            <label for="{{ $field }}" class="mb-0 flex-grow-1 text-start">Enable
                                                {{ $label }}</label>
                                            <div class="form-check form-switch m-0">
                                                <input type="hidden" name="{{ $field }}" value="0">
                                                <input type="checkbox" class="form-check-input"
                                                    name="{{ $field }}" id="{{ $field }}" value="1"
                                                    {{ !empty($valueArray[$field]) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-body">
                <label for="header_offer_section"
                    class="mb-0 flex-grow-1 text-start">{{ __('messages.enable_header_offer_section') }}</label>
                <div class="form-check form-switch m-0">
                    <input type="checkbox" class="form-check-input header_offer_section" name="header_offer_section"
                        id="header_offer_section" value="1" data-type="header_offer_section"
                        {{ !empty($valueArray['header_offer_section']) ? 'checked' : '' }}>
                </div>
            </div>
        </div>
        <div class="form-group mb-3" id="header_offer_title_group" style="display: none;">
            <label for="header_offer_title"
                class="mb-0 flex-grow-1 text-start">{{ __('messages.header_offer_title') }}</label>
            <input type="text" class="form-control" name="header_offer_title" id="header_offer_title"
                value="{{ !empty($valueArray['header_offer_title']) ? $valueArray['header_offer_title'] : '' }}"
                placeholder="{{ __('messages.enter_offer_title') }}">
        </div>

    </div>


    <div class="d-flex justify-content-end mt-4">
        <button id="saveButton" class="btn btn-primary">Save</button>
    </div>
</form>

<script>
    // Snackbar show function
    function showSnackbar(message, duration = 3000) {
        const snackbar = document.getElementById('snackbar');
        snackbar.textContent = message;
        snackbar.style.visibility = 'visible';
        snackbar.style.opacity = '1';

        setTimeout(() => {
            snackbar.style.opacity = '0';
            setTimeout(() => {
                snackbar.style.visibility = 'hidden';
            }, 500);
        }, duration);
    }

    (function() {
        function toggleHeaderSettings(value) {
            const container = $('#enable_header_setting');
            if (value) {
                container.removeClass('d-none').hide().slideDown(200);
            } else {
                container.slideUp(200, function() {
                    container.addClass('d-none');
                });
            }
        }

        function initializeHeaderSettings() {
            const isChecked = $('#header_setting').prop('checked');
            toggleHeaderSettings(isChecked);

            // Initialize the hidden status field
            const hiddenStatus = $('input[name="status"][type="hidden"]');
            if (isChecked) {
                hiddenStatus.prop('disabled', true);
            } else {
                hiddenStatus.prop('disabled', false);
            }
        }

        function initializeDragAndDrop() {
            const items = document.querySelectorAll('.list-item');
            items.forEach(item => {
                item.addEventListener('dragstart', e => {
                    e.dataTransfer.setData('text/plain', item.id);
                    item.style.opacity = '0.5';
                });

                item.addEventListener('dragend', () => {
                    item.style.opacity = '1';
                });

                item.addEventListener('dragover', e => {
                    e.preventDefault();
                });

                item.addEventListener('drop', e => {
                    e.preventDefault();
                    const draggedId = e.dataTransfer.getData('text/plain');
                    const draggedEl = document.getElementById(draggedId);
                    if (draggedEl && draggedEl !== item) {
                        item.parentNode.insertBefore(draggedEl, item.nextSibling);
                        updateOrder();
                    }
                });
            });
        }

        function updateOrder() {
            const items = document.querySelectorAll('.list-item');
            const order = Array.from(items).map(item => item.dataset.section);
            $('#myForm').find('input[name="menu_order"]').remove();
            $('#myForm').append(`<input type="hidden" name="menu_order" value='${JSON.stringify(order)}'>`);
        }

        $(function() {
            initializeHeaderSettings();
            initializeDragAndDrop();

            $('#header_setting').change(function() {
                const isChecked = $(this).prop('checked');
                toggleHeaderSettings(isChecked);

                // Handle the hidden status field
                const hiddenStatus = $('input[name="status"][type="hidden"]');
                if (isChecked) {
                    hiddenStatus.prop('disabled', true);

                } else {
                    hiddenStatus.prop('disabled', false);

                }
            });

            function toggleHeaderOfferTitle() {
                if ($('#header_offer_section').prop('checked')) {
                    $('#header_offer_title_group').slideDown(200);
                } else {
                    $('#header_offer_title_group').slideUp(200);
                }
            }
            $('#header_offer_section').change(function() {
                toggleHeaderOfferTitle();
            });
            toggleHeaderOfferTitle();

            $('#saveButton').click(function(e) {
                e.preventDefault();
                updateOrder();

                if ($('#header_offer_section').prop('checked')) {
                    var offerTitle = $('#header_offer_title').val();
                    if (!offerTitle || offerTitle.trim() === '') {
                        showSnackbar('Offer title is required when offer section is enabled.');
                        $('#header_offer_title').focus();
                        return;
                    }
                }

                const isHeaderChecked = $('#header_setting').prop('checked');
                const hiddenStatus = $('input[name="status"][type="hidden"]');
                hiddenStatus.val(isHeaderChecked ? 1 : 0);

                const formData = {};
                $('#myForm').serializeArray().forEach(function(item) {
                    formData[item.name] = item.value;
                });

                const saveDataRoute = "{{ route('heading_page_settings') }}";

                $('#saveButton').prop('disabled', true).text('Saving...');

                $.ajax({
                    url: saveDataRoute,
                    type: 'POST',
                    data: $.param(formData),
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: function(response) {

                        window.successSnackbar(response.message ||
                            'Settings saved successfully!');
                    },
                    error: function(xhr) {
                        console.error("Save error:", xhr.responseText);
                        window.errorSnackbar(
                            'Error saving settings. Check console for details.');
                    },
                    complete: function() {
                        $('#saveButton').prop('disabled', false).text('Save');
                    }
                });
            });
        });
    })();
</script>
