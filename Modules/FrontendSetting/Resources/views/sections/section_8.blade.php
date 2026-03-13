<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="p-4 bg-body shadow rounded max-w-sm">
    <div class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded mb-3">
        <div>
            <h4 class="text-xl font-bold">Product Section</h4>
        </div>
        <input type="checkbox" id="section_8" class="form-check-input section_8" name="status" data-type="section_8" style="width: 3em; height: 1.5em;">
    </div>

    <div class="row mb-4 d-none" id="enable_section_8">
                <div class="col">
                    <div class="border p-3 rounded bg-body text-start">
                        <h6 class="fw-semibold mb-3">Select Products</h6>
                        <div class="mb-3">
                           <select id="product_id" name="product_id[]" class="form-select w-full p-2 border rounded" multiple></select>

                            <span id="productError" class="text-danger text-start mt-1" style="display:none"></span>
                        </div>
                    </div>
                </div>
            </div>

</div>

<div class="d-flex justify-content-end mt-4">
    <button id="saveButton" class="btn btn-primary">Save</button>
</div>

<!-- Snackbar container -->
<div id="snackbar" class="snackbar-container snackbar-pos bottom-left">
    <span id="snackbar-message"></span>
</div>

<script>
$(document).ready(function() {
    const $section_8 = $('#section_8');
    const $enableSection8 = $('#enable_section_8');
    const $statusLabel = $('#statusLabel');
    const $productId = $('#product_id');
    const _token = $('meta[name="csrf-token"]').attr('content');
    const page = "{{ $tabpage }}";
    const type = $section_8.data('type');
    const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";
    const productError = $('#productError');

    $productId.select2({
        placeholder: "Select Products",
        width: '100%'
    });

    function showError(input, message) {
        input.text(message).show();
    }

    function clearError(input) {
        input.hide().text('');
    }


    function updateSectionState(isEnabled) {
        if (isEnabled) {
            $enableSection8.removeClass('d-none').hide().slideDown(200);
            $productId.prop('required', true);
        } else {
            $enableSection8.slideUp(200, function() {
                $(this).addClass('d-none');
            });
            $productId.prop('required', false).val(null).trigger('change');
        }
    }

    function loadProducts(selectedIds = []) {
        $.ajax({
            url: "{{ route('get.products') }}",
            method: 'POST',
            data: { _token },
            success: function(response) {
                if (response.success && Array.isArray(response.products)) {
                    $productId.empty();
                    response.products.forEach(product => {
                        const isSelected = selectedIds.includes(product.id.toString()) || selectedIds.includes(product.id);
                        $productId.append(`<option value="${product.id}" ${isSelected ? 'selected' : ''}>${product.name}</option>`);
                    });
                    $productId.trigger('change');
                } else {
                    console.warn('No products found or invalid response format.');
                }
            },
            error: function(err) {
                console.error('Failed to load products:', err);
            }
        });
    }

    function loadConfig() {
        $('.form-check-input').prop('disabled', true);
        $.ajax({
            url: getDataRoute,
            type: 'POST',
            data: { type: 'landing-page-setting', key: 'section_8', _token, page },
            success: function(response) {
                let config = {};
                try {
                    config = typeof response.data?.value === 'string'
                        ? JSON.parse(response.data.value)
                        : response.data?.value || {};
                } catch (e) {
                    console.error('Failed to parse config JSON:', e);
                    config = {};
                }

                const enabled = config.status == 1;
                $section_8.prop('checked', enabled);
                $statusLabel.text(enabled ? 'Enabled' : 'Disabled');
                updateSectionState(enabled);

                if (enabled) {
                    const selectedIds = Array.isArray(config.product_id)
                        ? config.product_id.map(String)
                        : [];
                    loadProducts(selectedIds);
                } else {
                    $productId.empty().trigger('change');
                }
            },
            error: function(error) {
                console.error('Error loading configuration:', error);
                loadProducts();
            },
            complete: function() {
                $('.form-check-input').prop('disabled', false);
            }
        });
    }

    updateSectionState($section_8.prop('checked'));
    $statusLabel.text($section_8.prop('checked') ? 'Enabled' : 'Disabled');
    loadConfig();

    $section_8.on('change', function() {
        const isChecked = $(this).prop('checked');
        updateSectionState(isChecked);
        $statusLabel.text(isChecked ? 'Enabled' : 'Disabled');
        if (isChecked) {
            loadProducts();
        } else {
            $productId.val(null).trigger('change');
        }
    });

    $('#saveButton').on('click', function(e) {
        e.preventDefault();

        const isEnabled = $section_8.prop('checked');
        const selectedIds = $productId.val() || [];


        clearError(productError);

        if (isEnabled && selectedIds.length === 0) {
            showError(productError, 'Please select at least one product when enabled.');
            return;
        }

        const dataToSend = {
            _token,
            type,
            page,
            status: isEnabled ? 1 : 0,
            product_id: isEnabled ? selectedIds : [],
        };

        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            url: "{{ route('saveLandingLayoutPageConfig') }}",
            method: 'POST',
            data: dataToSend,
            success: function(response) {
                if (response.success) {
                    window.successSnackbar('Settings saved successfully!');
                } else {
                    window.errorSnackbar('Failed to save settings.');
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
