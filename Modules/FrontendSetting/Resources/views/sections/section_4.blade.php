
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="section-content py-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Toggle Section -->
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center border p-3 rounded">
                        <div>
                            <h5 class="mb-0">Top Categories</h5>
                            
                        </div>
                        <div class="form-check form-switch">
                            <input type="checkbox" id="section_4" class="form-check-input" name="status" data-type="section_4" style="width: 3em; height: 1.5em;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 d-none" id="enable_section_4">
                <div class="col">
                    <div class="border p-3 rounded bg-body">
                        <h6 class="fw-semibold mb-3 text-start">Select Categories</h6>
                        <div class="mb-3">
                            <select id="select_category" name="select_category[]" class="form-select w-100" multiple="multiple"></select>
                        </div>
                        <span id="categoryError" class="text-danger text-start mt-1" style="display:none"></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="d-flex justify-content-end mt-4">
                    <button id="saveButton" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Snackbar -->
<div id="snackbar" class="snackbar-container  snackbar-pos bottom-left">
</div>

<script>
$(function () {
    const $toggle = $('#section_4');
    const $categorySection = $('#enable_section_4');
    const $categorySelect = $('#select_category');
    const $statusLabel = $('#statusLabel');
    const sectionType = $toggle.data('type');
    const page = "{{ $tabpage ?? 'home' }}";

    const getDataRoute = "{{ url('/api/get-landing-layout-page-config') }}";
    const postDataRoute = "{{ url('/api/save-landing-layout-page-config') }}";
    const fetchCategoryRoute = "{{ route('fetch.names') }}";
    const _token = $('meta[name="csrf-token"]').attr('content');
    const categoryError = $('#categoryError');


    function showError(input, message) {
        input.text(message).show();
    }

    function clearError(input) {
        input.hide().text('');
    }


    $categorySelect.select2({
        placeholder: 'Search Categories...',
        allowClear: true,
        width: '100%',
        ajax: {
            url: fetchCategoryRoute,
            type: 'POST',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term, _token: _token };
            },
            processResults: function (data) {
                return { results: data.map(item => ({ id: item.id, text: item.name })) };
            },
            cache: true
        }
    });

    function toggleVisibility(show) {
        if (show) {
            $categorySection.removeClass('d-none').hide().slideDown();
            $categorySelect.prop('required', true);
            $statusLabel.text('Enabled');
        } else {
            $categorySection.slideUp(() => {
                $categorySection.addClass('d-none');
                $categorySelect.prop('required', false).val(null).trigger('change');
                $statusLabel.text('Disabled');
            });
        }
    }

    // **Initialize visibility on page load based on checkbox**
    toggleVisibility($toggle.is(':checked'));

    function loadConfig() {
        $.ajax({
            url: getDataRoute,
            method: 'POST',
            data: { _token: _token, type: 'landing-page-setting', key: 'section_4', page: page },
            success: function (res) {
                let config = res?.data?.value || {};
                if (typeof config === 'string') {
                    try { config = JSON.parse(config); } catch { config = {}; }
                }
                let status = Number(config.section_4) === 1;
                $toggle.prop('checked', status);
                toggleVisibility(status);

                const selectedIds = config.select_category || [];
                if (selectedIds.length > 0) {
                    $.ajax({
                        url: fetchCategoryRoute,
                        type: 'POST',
                        data: { ids: selectedIds, _token: _token },
                        success: function (categories) {
                            $categorySelect.empty();
                            categories.forEach(cat => {
                                const option = new Option(cat.name, cat.id, true, true);
                                $categorySelect.append(option);
                            });
                            $categorySelect.trigger('change');
                        }
                    });
                }
            },
            error: function () {
                showSnackbar("Failed to load configuration.");
                toggleVisibility(false);
                $categorySelect.empty().trigger('change');
            }
        });
    }

    $toggle.on('change', function () {
        toggleVisibility($(this).is(':checked'));
    });

    loadConfig();

    $('#saveButton').on('click', function () {
        const statusVal = $toggle.is(':checked') ? 1 : 0;
        const selectedCategories = $categorySelect.val() || [];

        clearError(categoryError);

        if (statusVal === 1 && selectedCategories.length === 0) {
            showError(categoryError, 'Please select at least one category when enabled.');
            return;
        }

        $.ajax({
            url: postDataRoute,
            method: 'POST',
            data: { _token: _token, type: sectionType, page: page, status: statusVal, select_category: selectedCategories },
            success: function (res) {
                window.successSnackbar(res.message || 'Saved successfully.');
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    let msg = 'Validation errors:\n';
                    for (let field in errors) { msg += `${field}: ${errors[field].join(', ')}\n`; }
                    window.errorSnackbar(msg);
                } else { window.errorSnackbar('An error occurred while saving.'); }
            }
        });
    });
});
</script>
