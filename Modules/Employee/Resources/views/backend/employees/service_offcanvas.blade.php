<style>
  @media (min-width: 768px) { #package-service-form.offcanvas { width: 40%; } }
  @media (min-width: 1280px) { #package-service-form.offcanvas { width: 30%; } }
  #employee-services-table td, #employee-services-table th { white-space: nowrap; }
  #employee-services-table th:last-child, #employee-services-table td:last-child { width: 120px; }
</style>
<div class="offcanvas offcanvas-end" tabindex="-1" id="package-service-form" aria-labelledby="package-service-form-label">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="package-service-form-label">{{ __('service.title') ?? 'Services' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    <div class="d-flex flex-column">
      <div class="row g-2 align-items-end mb-3">
        <div class="col-8">
          <label class="form-label">{{ __('service.lbl_service_name') ?? 'Service' }}</label>
          <select id="employee-service-select" class="form-control select2" style="width:100%" multiple></select>
          <!-- <small class="text-muted">{{ __('messages.select_multiple') ?? 'You can select multiple services' }}</small> -->
        </div>
        <div class="col-4">
          <button type="button" class="btn btn-primary w-100" id="employee-add-service"><i class="fa-solid fa-plus me-1"></i>{{ __('messages.add') ?? 'Add' }}</button>
        </div>
      </div>
      <div class="table-responsive">
      <table class="table table-striped border dataTable no-footer mb-0" id="employee-services-table" style="display:none;">
        <thead>
          <tr>
            <th>{{ __('service.lbl_service_name') ?? 'Name' }}</th>
            <th>{{ __('service.lbl_price') ?? 'Price' }}</th>
            <th style="width:120px;">{{ __('messages.action') ?? 'Action' }}</th>
          </tr>
        </thead>
        <tbody id="employee-services-tbody"></tbody>
      </table>
      </div>
      <p class="text-muted" id="employee-services-empty">{{ __('messages.no_data_available') ?? 'No data available' }}</p>
    </div>
  </div>
</div>

@push('after-scripts')
<script type="text/javascript">
(function($){
  const offcanvasEl = document.getElementById('package-service-form');
  if(!offcanvasEl) return;
  const $tbody = $('#employee-services-tbody');
  const $table = $('#employee-services-table');
  const $empty = $('#employee-services-empty');
  var hasServiceChanged = false;
  var currentEmployeeId = 0;
  var $serviceSelect;

  function formatCurrency(value){
    if(window.currencyFormat !== undefined){ return window.currencyFormat(value); }
    return value;
  }

  function renderRows(items){
    $tbody.empty();
    if(Array.isArray(items) && items.length){
      items.forEach(it => {
        const name = it.service_name || it.name || '-';
        const price = formatCurrency(it.service_price ?? it.price ?? 0);
        const serviceId = it.service_id || it.id;
        $tbody.append(`<tr>
          <td><h6 class="m-0">${name}</h6></td>
          <td><h6 class="m-0 text-danger">${price}</h6></td>
          <td>
            <button type="button" class="btn btn-sm btn-soft-danger js-delete-employee-service" data-service-id="${serviceId}" data-service-name="${name}">
              <i class="fa-solid fa-trash"></i>
            </button>
          </td>
        </tr>`);
      })
      $table.show();
      $empty.hide();
    } else {
      $table.hide();
      $empty.show();
    }
  }

  function initServiceSelect(){
    $serviceSelect = $('#employee-service-select');
    // Destroy existing Select2 instance if it exists to avoid conflicts
    if ($serviceSelect.data('select2')) {
      $serviceSelect.select2('destroy');
    }
    $serviceSelect.select2({
      placeholder: '{{ __('messages.search...') ?? 'Search...' }}',
      multiple: true,
      closeOnSelect: false,
      ajax: {
        url: '{{ route('backend.services.index_list') }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          // Only include employee_id if it's a valid positive integer
          const params_data = { q: params.term || '', exclude_assigned: 1 };
          if (currentEmployeeId && currentEmployeeId > 0) {
            params_data.employee_id = currentEmployeeId;
          }
          const branchId = '{{ $selected_branch->id ?? 0 }}';
          if (branchId > 0) {
            params_data.branch_id = branchId;
          }
          return params_data;
        },
        processResults: function (data) {
          const results = (data || []).map(function(s){ return { id: s.id, text: s.name } });
          return { results: results };
        },
        cache: false  // Disable cache to ensure fresh data after creating new employee
      }
    });
  }

  async function loadServices(employeeId){
    try {
      const url = `{{ url('app/employees/empolye-services') }}/${employeeId}`;
      const res = await $.get(url);
      if(res && res.status){
        renderRows(res.data || []);
      } else {
        renderRows([]);
      }
    } catch (e){
      renderRows([]);
    }
  }

  document.addEventListener('package_service_form', function(ev){
    const employeeId = ev?.detail?.form_id ? parseInt(ev.detail.form_id) : 0;
    currentEmployeeId = employeeId > 0 ? employeeId : 0;
    // Clear Select2 cache and reinitialize if needed
    if($serviceSelect && $serviceSelect.data('select2')){
      $serviceSelect.val(null).trigger('change');
    }
    if(!$serviceSelect || !$serviceSelect.data('select2')){ 
      initServiceSelect(); 
    } else {
      // Update the AJAX data function to use new employee_id
      // Reinitialize to ensure fresh data
      initServiceSelect();
    }
    if(currentEmployeeId > 0){ 
      loadServices(currentEmployeeId); 
    } else {
      // Clear the services list if no employee selected
      renderRows([]);
    }
  });

  offcanvasEl.addEventListener('hidden.bs.offcanvas', function(){
    renderRows([]);
    if (hasServiceChanged && window.renderedDataTable) {
      try { window.renderedDataTable.ajax.reload(null, false) } catch(e) {}
      hasServiceChanged = false;
    }
  })
  // delete service assignment
  $(document).on('click', '.js-delete-employee-service', function(){
    const serviceId = $(this).data('service-id');
    const serviceName = $(this).data('service-name');
    if(!currentEmployeeId || !serviceId || currentEmployeeId <= 0 || serviceId <= 0) {
      window.errorSnackbar && window.errorSnackbar('Invalid employee or service ID');
      return;
    }
    const proceedDelete = () => {
      // Construct URL properly using route helper
      const deleteUrl = '{{ url("app/employees/employee-services") }}/' + parseInt(currentEmployeeId) + '/' + parseInt(serviceId);
      $.ajax({
        url: deleteUrl,
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      }).done(function(res){
        if(res?.status){
          hasServiceChanged = true;
          window.successSnackbar && window.successSnackbar(res.message || '{{ __('messages.delete_form', ['form' => __('service.singular_title')]) }}');
          loadServices(currentEmployeeId);
        } else {
          window.errorSnackbar && window.errorSnackbar(res.message || 'Failed to delete');
        }
      }).fail(function(xhr){
        let errorMsg = 'Server error';
        if(xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if(xhr.status === 405) {
          errorMsg = 'Method not allowed. Please check the route configuration.';
        }
        window.errorSnackbar && window.errorSnackbar(errorMsg);
      })
    };

    const deleteMessage = `{{ __('messages.are_you_sure?', ['name' => ':name', 'module' => __('service.singular_title')]) }}`.replace(':name', serviceName || 'this service');

    if (window.Swal && typeof window.Swal.fire === 'function') {
      Swal.fire({
        title: deleteMessage,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: `{{ __('messages.delete') ?? 'Delete' }}`,
        cancelButtonText: `{{ __('messages.cancel') ?? 'Cancel' }}`
      }).then((result) => { if (result.isConfirmed) proceedDelete(); })
    } else {
      if(confirm(deleteMessage)) proceedDelete();
    }
  })

  // add service assignment
  $('#employee-add-service').on('click', function(){
    const selected = $serviceSelect && $serviceSelect.val ? $serviceSelect.val() : [];
    if(!currentEmployeeId || currentEmployeeId <= 0 || !selected || selected.length === 0){ 
      window.errorSnackbar && window.errorSnackbar('Please select a service and ensure employee is selected');
      return; 
    }
    const addUrl = '{{ url("app/employees/employee-services") }}/' + parseInt(currentEmployeeId);
    $.ajax({
      url: addUrl,
      method: 'POST',
      data: { 
        service_ids: selected, 
        _token: '{{ csrf_token() }}' 
      }
    }).done(function(res){
      if(res?.status){
        hasServiceChanged = true;
        window.successSnackbar && window.successSnackbar(res.message || '{{ __('messages.create_form', ['form' => __('service.singular_title')]) }}');
        // clear selections
        $serviceSelect.val(null).trigger('change');
        loadServices(currentEmployeeId);
      } else {
        window.errorSnackbar && window.errorSnackbar(res.message || 'Failed to add');
      }
    }).fail(function(xhr){
      let errorMsg = 'Server error';
      if(xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
      }
      window.errorSnackbar && window.errorSnackbar(errorMsg);
    });
  })
})(window.$)
</script>
@endpush


