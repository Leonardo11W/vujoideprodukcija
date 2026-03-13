<style>
/* Scoped Select2 primary theme for employee assign offcanvas */
#service-employee-assign-form .select2-container--default .select2-results__option--highlighted,
#service-employee-assign-form .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
}
#service-employee-assign-form .select2-container--default .select2-results__option[aria-selected=true] {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
}
#service-employee-assign-form .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--bs-primary) !important;
    border-color: var(--bs-primary) !important;
    color: #fff !important;
}
#service-employee-assign-form .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff !important;
    opacity: .9;
}
#service-employee-assign-form .select2-dropdown { border-color: var(--bs-primary) !important; }
#service-employee-assign-form .select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: var(--bs-primary) !important;
    box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb, 13, 110, 253), .25) !important;
}
#service-employee-assign-form .select2-container--default .select2-selection--single:focus,
#service-employee-assign-form .select2-container--default .select2-selection--multiple:focus {
    border-color: var(--bs-primary) !important;
    box-shadow: 0 0 0 .2rem rgba(var(--bs-primary-rgb, 13, 110, 253), .25) !important;
}
</style>
<form id="assign-employee-form" method="POST" action="{{ $service ? route('backend.services.assign_employee_update', $service->id) : '#' }}">
<div class="offcanvas offcanvas-end" tabindex="-1" id="service-employee-assign-form" aria-labelledby="form-offcanvasLabel">
        @csrf
        <div class="offcanvas-header border-bottom">
            <h6 class="m-0 h5">
                {{ __('service.singular_title') }}: <span>{{ $service ? $service->name : '' }}</span>
            </h6>
        </div>
        <div class="offcanvas-body">
            <div class="form-group">
                <div class="d-grid">
                    <div class="d-flex flex-column">
                        <div class="mb-4">
                            <label for="employees_ids">{{ __('messages.select_staff') }}</label>
                            <select id="employees_ids" name="employees[]" class="form-control" multiple="multiple" style="width: 100%;">
                                @foreach($employees as $employee)
                                    <option value="{{ $employee['id'] }}"
                                        @if(collect($assignedEmployees)->pluck('employee_id')->contains($employee['id'])) selected @endif
                                        data-avatar="{{ $employee['avatar'] }}"
                                    >{{ $employee['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" id="selected-employee-list">
                        @foreach($assignedEmployees as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center" data-employee-id="{{ $item['employee_id'] }}">
                                <div class="d-flex justify-between flex-grow-1 gap-2 my-2">
                                    <img src="{{ $item['avatar'] }}" class="avatar avatar-40 img-fluid rounded-pill" alt="user" />
                                    <div class="flex-grow-1 mt-2">{{ $item['name'] }}</div>
                                </div>
                                <button type="button" class="btn btn-sm text-danger remove-employee-btn"><i class="fa-regular fa-trash-can"></i></button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer">
            <p class="text-center mb-0"><small>{{ __('service.assign_staff_to_service') }}</small></p>
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

<script type="application/json" id="employees-json">{!! json_encode($employees) !!}</script>

<script>
$(document).ready(function() {
    // Destroy any previous Select2 instance to avoid conflicts
    if ($.fn.select2 && $('#employees_ids').data('select2')) {
        $('#employees_ids').select2('destroy');
    }

    // Enhanced Select2 without avatars
    $('#employees_ids').select2({
        placeholder: "{{ __('messages.select_staff') }}",
        width: '100%',
        allowClear: true,
        closeOnSelect: false, // Important for multi-select
        dropdownParent: $('#service-employee-assign-form')
    });

    // Update selected employee list on change
    $('#employees_ids').on('change', function() {
        var selectedIds = $(this).val() || [];
        var employees = [];
        try {
            var jsonText = document.getElementById('employees-json').textContent || '[]';
            employees = JSON.parse(jsonText);
        } catch (e) {
            employees = [];
        }
        var assigned = employees.filter(emp => selectedIds.includes(emp.id.toString()));
        var $list = $('#selected-employee-list');
        $list.empty();
        assigned.forEach(function(emp) {
            $list.append(`
                <div class="list-group-item d-flex justify-content-between align-items-center" data-employee-id="${emp.id}">
                    <div class="d-flex justify-between flex-grow-1 gap-2 my-2">
                        <img src="${emp.avatar}" class="avatar avatar-40 img-fluid rounded-pill" alt="user" />
                        <div class="flex-grow-1 mt-2">${emp.name}</div>
                    </div>
                    <button type="button" class="btn btn-sm text-danger remove-employee-btn"><i class="fa-regular fa-trash-can"></i></button>
                </div>
            `);
        });
    });

    // Remove employee from selection
    $(document).on('click', '.remove-employee-btn', function() {
        var $item = $(this).closest('.list-group-item');
        var empId = $item.data('employee-id').toString();
        var $select = $('#employees_ids');
        var selected = $select.val() || [];
        $select.val(selected.filter(id => id !== empId)).trigger('change');
    });

    // Prevent duplicate backdrops when opening this offcanvas via triggers
    $(document).on('show.bs.offcanvas', '#service-employee-assign-form', function () {
        try {
            // Hide any other open offcanvas first
            document.querySelectorAll('.offcanvas.show').forEach(function(el) {
                var inst = bootstrap.Offcanvas.getInstance(el);
                if (inst) inst.hide();
            });
            // Remove extra backdrops if multiple exist
            var backdrops = document.querySelectorAll('.offcanvas-backdrop');
            backdrops.forEach(function(bd, idx) { if (idx > 0 && bd && bd.parentNode) bd.parentNode.removeChild(bd); });
        } catch(e) { /* noop */ }
    });

    // AJAX form submit
    $('#assign-employee-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var action = $form.attr('action');
        // Don't submit if no service is selected
        if (action === '#') {
            return false;
        }
        $.ajax({
            url: action,
            method: 'POST',
            data: $form.serialize(),
            success: function(res) {
                if(res.status) {
                    window.successSnackbar(res.message);
                    bootstrap.Offcanvas.getInstance(document.getElementById('service-employee-assign-form')).hide();
                    if(window.renderedDataTable) {
                        window.renderedDataTable.ajax.reload(null, false);
                    }
                } else {
                    window.errorSnackbar(res.message);
                }
            },
            error: function(xhr) {
                window.errorSnackbar("{{ __('messages.something_went_wrong') }}");
            }
        });
    });
});

// Function to set dynamic route for employee assignment with proper base URL
function setEmployeeAssignRoute(serviceId) {
    // Get the current URL and extract the base path
    var currentPath = window.location.pathname;
    var basePath = '';
    
    // If we're in a subdirectory (like /frezka-giftcard/), extract it
    if (currentPath.includes('/app/')) {
        basePath = currentPath.split('/app/')[0];
    }
    
    var action = window.location.origin + basePath + '/app/services/assign-employee/' + serviceId;
    $('#assign-employee-form').attr('action', action);
}

// Function to reset the offcanvas form
function resetEmployeeOffcanvas() {
    // Reset the form
    $('#assign-employee-form')[0].reset();
    
    // Clear the selected employee list
    $('#selected-employee-list').empty();
    
    // Clear the select2 if it exists
    if ($('#employees_ids').hasClass('select2-hidden-accessible')) {
        $('#employees_ids').val(null).trigger('change');
    }
    
    // Reset the service name
    $('.offcanvas-header h6 span').text('');
}

// Reset form when offcanvas is hidden
$(document).on('hidden.bs.offcanvas', '#service-employee-assign-form', function() {
    resetEmployeeOffcanvas();
});
</script>