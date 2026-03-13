<!-- Assign Branch Form Offcanvas -->
<style>
    /* Scoped Select2 primary theme for this offcanvas */
    #service-branch-assign-form .select2-container--default .select2-results__option--highlighted,
    #service-branch-assign-form .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
        background-color: var(--bs-primary) !important;
        color: #fff !important;
    }
    #service-branch-assign-form .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: var(--bs-primary) !important;
        color: #fff !important;
    }
    #service-branch-assign-form .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: var(--bs-primary) !important;
        border-color: var(--bs-primary) !important;
        color: #fff !important;
    }
    #service-branch-assign-form .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color: #fff !important;
        opacity: .9;
    }
    #service-branch-assign-form .select2-dropdown {
        border-color: var(--bs-primary) !important;
    }
    #service-branch-assign-form .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb, 13, 110, 253), .25) !important;
    }
    /* Single select focus ring */
    #service-branch-assign-form .select2-container--default .select2-selection--single:focus,
    #service-branch-assign-form .select2-container--default .select2-selection--multiple:focus {
        border-color: var(--bs-primary) !important;
        box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb, 13, 110, 253), .25) !important;
    }
</style>
<form id="assign-branch-form" method="POST">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="service-branch-assign-form"
        aria-labelledby="form-offcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h6 class="m-0 h5">
                {{ __('service.singular_title') }} : <span id="service-name"></span>
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        @csrf

        <div class="offcanvas-body">
            <div class="form-group">
                <div class="d-grid">
                    <div class="d-flex flex-column">
                        <div class="form-group">
                            <label for="branches_ids">{{ __('branch.select_branch') }}</label>
                            <select class="form-control" name="branches_ids[]" id="branches_ids" multiple>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" data-name="{{ $branch->name }}">
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="list-group list-group-flush mt-3" id="selected-branches-list">
                        <!-- Selected branches will be dynamically added here -->
                    </div>
                </div>
            </div>
        </div>

        <div class="offcanvas-footer">
            <p class="text-center mb-0"><small>{{ __('branch.assign_branch_to_service') }}</small></p>
            <div class="d-grid gap-3 p-3">
                <button type="submit" class="btn btn-primary d-block" id="submit-assign-btn">
                    <i class="fa-solid fa-floppy-disk"></i>
                    {{ __('messages.update') }}
                </button>
                <button class="btn btn-outline-primary d-block" type="button" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-angles-left"></i>
                    {{ __('messages.close') }}
                </button>
            </div>
        </div>
    </div>
    </div>
</form>

<script>
    let selectedBranches = [];
    let currentService = null;
    const CURRENCY_SYMBOL = '{{ config('app.currency_symbol', "$") }}';

    // Initialize the assign branch form
    function initAssignBranchForm() {
        const branchesSelect = document.getElementById('branches_ids');
        const selectedBranchesList = document.getElementById('selected-branches-list');

        // Remove any existing Select2 containers for this specific select
        $(branchesSelect).next('.select2-container').remove();

        // Destroy existing Select2 instance if it exists
        if ($(branchesSelect).hasClass('select2-hidden-accessible')) {
            $(branchesSelect).select2('destroy');
        }

        // Remove any select2 classes that might have been added
        $(branchesSelect).removeClass('select2-hidden-accessible');

        // Initialize Select2 for branches
        $(branchesSelect).select2({
            placeholder: '{{ __('branch.select_branch') }}',
            allowClear: true,
            dropdownParent: $('#service-branch-assign-form'),
            width: '100%'
        });

        // Handle branch selection
        $(branchesSelect).on('change', function() {
            const selectedValues = $(this).val();
            const selectedOptions = $(this).find('option:selected');

            // Clear the list
            selectedBranchesList.innerHTML = '';
            const previousSelectedBranches = Array.isArray(selectedBranches) ? selectedBranches.slice() : [];
            selectedBranches = [];

            // Add selected branches to the list
            selectedOptions.each(function(index) {
                const branchId = $(this).val();
                const branchName = $(this).data('name');

                // Get default values from current service
                const defaultPrice = currentService ? currentService.default_price : '';
                const defaultDuration = currentService ? currentService.duration_min : '';

                // Try to keep existing values if already assigned
                const existing = previousSelectedBranches.find(b => String(b.branch_id) === String(branchId));
                const branchData = {
                    branch_id: branchId,
                    name: branchName,
                    service_id: currentService ? currentService.id : null,
                    service_price: existing && existing.service_price !== undefined && existing.service_price !== null && existing.service_price !== ''
                        ? existing.service_price
                        : defaultPrice,
                    duration_min: existing && existing.duration_min !== undefined && existing.duration_min !== null && existing.duration_min !== ''
                        ? existing.duration_min
                        : defaultDuration
                };

                selectedBranches.push(branchData);
                addBranchToList(branchData, index + 1);
            });

            // Log for debugging
            console.log('Current service data:', currentService);
            console.log('Selected branches:', selectedBranches);
        });
    }

    // Add branch to the list with input fields
    function addBranchToList(branch, index) {
        const selectedBranchesList = document.getElementById('selected-branches-list');

        const branchItem = document.createElement('div');
        branchItem.className = 'list-group-item';
        branchItem.innerHTML = `
        <div class="d-flex justify-between align-items-center flex-grow-1 gap-2 mt-2">
            <span>${index} - </span>
            <div class="flex-grow-1">${branch.name}</div>
            <button type="button" onclick="removeBranch('${branch.branch_id}')" class="btn btn-sm text-danger">
                <i class="fa-regular fa-trash-can"></i>
            </button>
        </div>
        <div class="row mb-2">
            <div class="d-flex justify-content-end align-items-center gap-2 col-6">
                <i class="fa-regular fa-clock"></i>
                <input type="number" name="branches[${branch.branch_id}][duration_min]"
                       value="${branch.duration_min || ''}" class="form-control"
                       placeholder="{{ __('service.lbl_duration_min') }}" required />
            </div>
            <div class="d-flex justify-content-end align-items-center gap-2 col-6">
                ${CURRENCY_SYMBOL}
                <input type="text" name="branches[${branch.branch_id}][service_price]"
                       value="${branch.service_price || ''}" class="form-control"
                       placeholder="{{ __('service.lbl_default_price') }}" required />
            </div>
        </div>
    `;

        selectedBranchesList.appendChild(branchItem);
    }

    // Remove branch from the list
    function removeBranch(branchId) {
        selectedBranches = selectedBranches.filter(branch => branch.branch_id !== branchId);

        // Update the select2
        const branchesSelect = document.getElementById('branches_ids');
        const currentValues = $(branchesSelect).val() || [];
        const newValues = currentValues.filter(id => id !== branchId);
        $(branchesSelect).val(newValues).trigger('change');
    }

    // Load service data and existing branch assignments
    function loadServiceData(serviceId) {
        if (!serviceId) return;

        // Fetch service details
        fetch(`{{ route('backend.services.get_service_data', ':id') }}`.replace(':id', serviceId))
            .then(response => response.json())
            .then(data => {
                if (data.status && data.data) {
                    currentService = data.data;
                    document.getElementById('service-name').textContent = currentService.name;

                    // Fetch existing branch assignments
                    fetch(`{{ route('backend.services.assign_branch_list', ':id') }}`.replace(':id', serviceId))
                        .then(response => response.json())
                        .then(assignData => {
                            if (assignData.status && assignData.data) {
                                selectedBranches = assignData.data;

                                // Update the select2 with existing assignments
                                const branchesSelect = document.getElementById('branches_ids');
                                const branchIds = selectedBranches.map(branch => branch.branch_id);
                                $(branchesSelect).val(branchIds).trigger('change');

                                // Refresh the branch list with current service data
                                refreshBranchList();
                            } else {
                                // No existing branches, clear the list
                                selectedBranches = [];
                                const selectedBranchesList = document.getElementById('selected-branches-list');
                                selectedBranchesList.innerHTML = '';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching branch assignments:', error);
                        });
                }
            })
            .catch(error => {
                console.error('Error fetching service data:', error);
            });
    }

    // Refresh branch list with current service data
    function refreshBranchList() {
        if (!currentService) return;

        const selectedBranchesList = document.getElementById('selected-branches-list');
        selectedBranchesList.innerHTML = '';

        selectedBranches.forEach((branch, index) => {
            // Fill only if truly missing (null/undefined/empty string)
            if ((branch.service_price === undefined || branch.service_price === null || branch.service_price === '') && currentService.default_price !== undefined) {
                branch.service_price = currentService.default_price;
            }
            if ((branch.duration_min === undefined || branch.duration_min === null || branch.duration_min === '') && currentService.duration_min !== undefined) {
                branch.duration_min = currentService.duration_min;
            }
            addBranchToList(branch, index + 1);
        });
    }

    // Handle form submission
    document.getElementById('assign-branch-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submit-assign-btn');
        const originalText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('messages.updating') }}';

        // Prepare data in the format expected by the controller
        const branches = [];
        selectedBranches.forEach(branch => {
            const durationInput = document.querySelector(
                `input[name="branches[${branch.branch_id}][duration_min]"]`);
            const priceInput = document.querySelector(
                `input[name="branches[${branch.branch_id}][service_price]"]`);

            if (durationInput && priceInput) {
                branches.push({
                    branch_id: branch.branch_id,
                    service_price: priceInput.value,
                    duration_min: durationInput.value
                });
            }
        });

        // Alert if no branches selected
        if (!branches.length) {

            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('branches', JSON.stringify(branches));

        fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {

                hideOffcanvas();

                if (data.status) {
                    window.renderedDataTable.ajax.reload(null, false);
                    window.successSnackbar(data.message);
                    return;
                } else {
                    if (typeof window.errorSnackbar === 'function') {
                        window.errorSnackbar(data.message);
                    }
                }


            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof window.errorSnackbar === 'function') {
                    window.errorSnackbar('{{ __('messages.something_went_wrong') }}');
                } else {
                    alert('{{ __('messages.something_went_wrong') }}');
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });



    function hideOffcanvas() {
        const offcanvasElement = document.getElementById('service-branch-assign-form');
        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
        if (offcanvas) {
            offcanvas.hide();
        }
    }


    function openAssignBranchOffcanvas(serviceId) {
        // Reset form
        document.getElementById('assign-branch-form').reset();
        document.getElementById('selected-branches-list').innerHTML = '';
        selectedBranches = [];
        currentService = null;

        // Update form action
        document.getElementById('assign-branch-form').action =
            `{{ route('backend.services.assign_branch_update', ':id') }}`.replace(':id', serviceId);

        // Open offcanvas first
        const offcanvas = new bootstrap.Offcanvas(document.getElementById('service-branch-assign-form'));
        offcanvas.show();

        // Initialize Select2 after offcanvas is shown
        setTimeout(() => {
            initAssignBranchForm();
            // Load service data after Select2 is initialized
            loadServiceData(serviceId);
        }, 100);
    }

    // Handle assign branch button clicks from datatable
    document.addEventListener('click', function(e) {
        if (e.target.closest('[data-assign-event="branch_assign"]')) {
            const button = e.target.closest('[data-assign-event="branch_assign"]');
            const serviceId = button.getAttribute('data-assign-module');
            openAssignBranchOffcanvas(serviceId);
        }
    });

    // Clean up Select2 when offcanvas is closed
    document.addEventListener('hidden.bs.offcanvas', function(e) {
        if (e.target.id === 'service-branch-assign-form') {
            const branchesSelect = document.getElementById('branches_ids');
            if (branchesSelect && $(branchesSelect).hasClass('select2-hidden-accessible')) {
                $(branchesSelect).select2('destroy');
                $(branchesSelect).next('.select2-container').remove();
            }
        }
    });
</script>
