<!-- Assign Branch Employee Offcanvas (Blade version) -->
<form id="assign-branch-employee-form">
    <div class="offcanvas offcanvas-end" tabindex="-1" id="staff-assign-form" aria-labelledby="form-offcanvasLabel" data-autoclose="true">
        <div class="offcanvas-header">
            <h4 id="branch-name"></h4>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column">
                <div class="mb-4">
                    <select id="employee-select" class="form-control" multiple></select>
                </div>
                <div id="selected-employees-list"></div>
            </div>
        </div>
        <div class="offcanvas-footer">
            <div class="d-grid gap-3 p-3">
                <button type="submit" class="btn btn-primary d-block">
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
</form>
@push('after-scripts')
<script>
    let assignIds = [];
    let initialAssignIds = [];
    let selectedEmployees = [];
    let staffList = [];
    let branchId = null;

    // Resolve URLs from Laravel so prefixes are correct
    const ASSIGN_GET_URL = (id) => "{{ route('backend.branch.assign_list', ['id' => 'ID_PLACEHOLDER']) }}".replace('ID_PLACEHOLDER', id);
    const ASSIGN_POST_URL = (id) => "{{ route('backend.branch.assign_update', ['id' => 'ID_PLACEHOLDER']) }}".replace('ID_PLACEHOLDER', id);
    const EMPLOYEE_LIST_URL = "{{ route('backend.employees.employee_list') }}?ignore_branch_filter=1";

    // Open offcanvas and load data
    window.openAssignOffcanvas = function(branch_id) {
        branchId = branch_id;

        assignIds = [];
        initialAssignIds = [];
        selectedEmployees = [];
        staffList = [];

        const $select = $('#employee-select');

        // Always attempt to destroy any existing Select2 instance to avoid leftover containers
        try {
            if ($select && $select.data('select2')) {
                $select.select2('destroy');
            }
        } catch (err) {
            // ignore
        }

        $select.empty().val(null);
        $('#selected-employees-list').html('');
        $('#branch-name').text('');

        const offcanvas = new bootstrap.Offcanvas(
            document.getElementById('staff-assign-form')
        );
        offcanvas.show();

        fetchEmployeeList(() => {
            fetchAssignList();
        });
    };

    // Clean up Select2 containers/dropdowns when the offcanvas is hidden to avoid orphaned dropdowns
    (function() {
        const staffAssignEl = document.getElementById('staff-assign-form');
        if (!staffAssignEl) return;

        staffAssignEl.addEventListener('hidden.bs.offcanvas', function() {
            try {
                const $select = $('#employee-select');
                if ($select && $select.data('select2')) {
                    try {
                        $select.select2('destroy');
                    } catch (e) {}
                }
            } catch (e) {}

            try {
                // Remove any leftover select2 DOM nodes inside the offcanvas
                $('#staff-assign-form').find('.select2-container, .select2-dropdown, .select2-selection__rendered').remove();
                // Also remove any orphaned dropdown appended to body that might belong to this select
                $('body').find('.select2-dropdown[aria-hidden="false"]').remove();
            } catch (e) {}
        });
    })();


    // Attach click handler to Datatable action button
    $(document).on('click', "[data-assign-target='#staff-assign-form'][data-assign-module]", function() {
        const id = $(this).data('assign-module');
        if (id) {
            window.openAssignOffcanvas(id);
        }
    });

    // Fetch assigned employees for the branch
    function fetchAssignList() {
        $.get(ASSIGN_GET_URL(branchId), function(res) {
            if (res.status) {
                selectedEmployees = res.data;
                // Convert to Numbers to ensure matching with staffList ids
                assignIds = res.data.map(item => Number(item.employee_id));
                initialAssignIds = [...assignIds];
                $('#branch-name').text((selectedEmployees.length ? selectedEmployees[0].branch_name : '') || '');
                renderSelectedEmployees();
                
                // Ensure options exist before setting value
                const $select = $('#employee-select');
                $select.val(assignIds).trigger('change');
            }
        });
    }

    // Fetch all employees for selection
    function fetchEmployeeList(callback) {
        $.get(`${EMPLOYEE_LIST_URL}&branch_id=${branchId}`, function(res) {
            staffList = res;

            const $select = $('#employee-select');

            $select.empty();
            res.forEach(emp => {
                $select.append(`<option value="${emp.id}">${emp.name}</option>`);
            });

            // Init Select2 if not already init
            if ($.fn.select2 && !$select.data('select2')) {
                $select.select2({
                    width: '100%',
                    placeholder: 'Select staff',
                    dropdownParent: $('#staff-assign-form')
                });
            }

            if (typeof callback === 'function') callback();
            
            // If assignIds already populated (rare racing), set them now
            if (assignIds.length > 0) {
                $select.val(assignIds).trigger('change');
            }
        });
    }


    // Render selected employees list
    function renderSelectedEmployees() {
        let html = '';
        selectedEmployees.forEach(emp => {
            html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div class="d-flex justify-between flex-grow-1 gap-2 my-2">
                    <img src="${emp.avatar}" class="avatar avatar-40 img-fluid rounded-pill" alt="user" />
                    <div class="flex-grow-1 mt-2">${emp.name}</div>
                </div>
                ${selectedEmployees.length > 1 ? `
                <button type="button" class="btn btn-sm text-danger" onclick="removeEmployee(${emp.employee_id})">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
                ` : ''}
            </div>
        `;
        });
        $('#selected-employees-list').html(html);
    }

    // Handle employee selection
    $(document).on('change', '#employee-select', function() {
        assignIds = $(this).val() ? $(this).val().map(Number) : [];
        selectedEmployees = staffList.filter(emp => assignIds.includes(emp.id));
        renderSelectedEmployees();
    });

    // Remove employee
    window.removeEmployee = function(empId) {
        assignIds = assignIds.filter(id => id != empId);
        selectedEmployees = selectedEmployees.filter(emp => emp.employee_id != empId && emp.id != empId);
        $('#employee-select').val(assignIds).trigger('change');
        renderSelectedEmployees();
        if (window.successSnackbar) window.successSnackbar('Employee removed successfully');
    }

    // Submit form
    $(document).on('submit', '#assign-branch-employee-form', function(e) {
        e.preventDefault();
        if (JSON.stringify(assignIds) !== JSON.stringify(initialAssignIds)) {
            Swal.fire({
                title: 'Do you want to make sure that if the staff is in another branch, they will be moved here?',
                showCancelButton: true,
                confirmButtonText: 'Yes',
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: ASSIGN_POST_URL(branchId),
                        method: 'POST',
                        data: {
                            users: assignIds,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(res) {
                            if (res.status) {
                                if (window.successSnackbar) window.successSnackbar(res.message);
                                if (window.renderedDataTable) renderedDataTable.ajax.reload(null, false);
                                // Hide offcanvas only if data-autoclose is not explicitly set to "false"
                                try {
                                    const offEl = document.getElementById('staff-assign-form');
                                    const autoClose = offEl && offEl.dataset && offEl.dataset.autoclose !== 'false';
                                    if (autoClose) {
                                        bootstrap.Offcanvas.getInstance(offEl).hide();
                                    }
                                } catch (e) {}
                            } else {
                                if (window.errorSnackbar) window.errorSnackbar(res.message);
                            }
                        }
                    });
                }
            });
        } else {
            if (window.renderedDataTable) renderedDataTable.ajax.reload(null, false);
            try {
                const offEl = document.getElementById('staff-assign-form');
                const autoClose = offEl && offEl.dataset && offEl.dataset.autoclose !== 'false';
                if (autoClose) {
                    bootstrap.Offcanvas.getInstance(offEl).hide();
                }
            } catch (e) {}
        }
    });
</script>
@endpush