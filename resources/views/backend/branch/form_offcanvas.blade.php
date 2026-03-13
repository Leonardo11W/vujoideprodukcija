<!-- Intl-Tel-Input CSS (correct) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">

@php $__currentUserIsManager = auth()->check() && auth()->user()->hasRole('manager'); @endphp
<form id="branch-form" enctype="multipart/form-data">
  @csrf
  <div id="branch-form-root" 
       data-store-url="{{ route('backend.branch.store') }}"
       data-update-url-base="{{ url('app/branch') }}"
       data-edit-url-base="{{ url('app/branch') }}"
       data-service-list-url="{{ url('app/services/index_list') }}"
       data-employee-list-url="{{ url('app/employees/employee_list') }}"
       data-country-url="{{ url('app/country/index_list') }}"
       data-state-url-base="{{ url('app/state/index_list') }}"
       data-city-url-base="{{ url('app/city/index_list') }}"
       data-select-branch-for='@json(($select_data["BRANCH_FOR"] ?? []))'
       data-select-payment-methods='@json(($select_data["PAYMENT_METHODS"] ?? []))'
       data-default-image="{{ default_feature_image() }}"
       data-i18n-select="{{ __('messages.select') }}"
       data-i18n-required="{{ __('validation.required', ['attribute' => __('messages.field')]) }}"
       data-i18n-invalid-email="{{ __('validation.email', ['attribute' => __('branch.lbl_contact_email')]) }}"
       data-i18n-male="{{ __('messages.male') }}"
       data-i18n-female="{{ __('messages.female') }}"
       data-i18n-unisex="{{ __('messages.unisex') }}"
       data-i18n-image-error="{{ __('messages.only_jpeg_jpg_png_gif_files_are_allowed_maximum_size_2_mb') }}">
    
    <div class="offcanvas offcanvas-end offcanvas-width" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
      <!-- Form Header -->
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="form-offcanvasLabel">
          <span id="branch-form-title-create">{{ __('messages.new') }} {{ __('branch.singular_title') }}</span>
          <span id="branch-form-title-edit" style="display:none;">{{ __('messages.edit') }} {{ __('branch.singular_title') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      
      <div class="offcanvas-body">
        <div class="row">
          <!-- Branch Name and Branch For Section -->
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_branch_name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('branch.branch_name') }}" />
              <span class="text-danger" data-error="name"></span>
            </div>
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_branch_for') }} <span class="text-danger">*</span></label>
              <div class="d-flex flex-wrap gap-2" role="group" aria-label="Basic example" id="branch_for_group">
                <!-- Radios injected by JS -->
              </div>
              <span class="text-danger" data-error="branch_for"></span>
            </div>
          </div>
          
          <!-- Image Upload Section -->
          <div class="col-md-6">
            <div class="form-group">
              <div class="text-center">
                <img id="feature-image-preview" src="" alt="feature-image" class="img-fluid mb-2 avatar-140 avatar-rounded" />
                <div id="feature-image-error" class="text-danger mb-2" style="display:none;"></div>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                  <input type="file" class="form-control d-none" id="feature_image" name="feature_image" accept=".jpeg, .jpg, .png, .gif" />
                  <label class="btn btn-info" for="feature_image" id="upload-image-btn">{{ __('messages.upload') }}</label>
                  <button type="button" class="btn btn-danger" id="remove-image-btn" style="display:none;">{{ __('messages.remove') }}</button>
                </div>
                <!-- <small id="feature-image-info" class="text-primary d-block">{{__('messages.only_jpeg_jpg_png_gif_files_are_allowed_maximum_size_2_mb')}}</small> -->
                <span class="text-danger" data-error="feature_image"></span>
              </div>
            </div>
          </div>
          <!-- Manager Selection -->
          <div class="col-12">
            <div class="form-group">
              <div class="d-flex justify-content-between">
                <label class="form-label" for="manager_id">{{ __('branch.lbl_select_manager') }} <span class="text-danger">*</span></label>
                <button type="button" class="btn btn-sm text-primary" data-bs-toggle="modal" data-bs-target="#managerCreateModal" id="manager-create-btn"><i class="fa-solid fa-plus"></i> {{ __('messages.create') }} {{ __('messages.new') }}</button>
              </div>
              <select class="form-control" id="manager_id" name="manager_id"></select>
              <span class="text-danger" data-error="manager_id"></span>
            </div>
          </div>

          <!-- Service Selection -->
          <div class="col-12">
            <div class="form-group">
              <label class="form-label" for="services_select">{{ __('branch.lbl_select_service') }}</label>
              <select class="form-control" id="services_select" name="service_id[]" multiple ></select>
              <span class="text-danger" data-error="service_id"></span>
            </div>
          </div>
          <!-- Contact Information -->
          <div class="col-md-6">
            <div class="form-group d-flex flex-column">
              <label class="form-label">{{ __('branch.lbl_contact_number') }} <span class="text-danger">*</span></label>
              <input type="tel" class="form-control flex-grow-1" id="contact_number" name="contact_number" />
              <span class="text-danger" data-error="contact_number"></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_contact_email') }} <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="{{ __('branch.enter_email') }}" />
              <span class="text-danger" data-error="contact_email"></span>
            </div>
          </div>
          
          <!-- Address Information -->
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_shop_number') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="address_line_1" placeholder="{{ __('branch.enter_landmark') }}" />
              <span class="text-danger" data-error="address_line_1"></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_landmark') }}</label>
              <input type="text" class="form-control" id="address_line_2" placeholder="{{ __('branch.enter_nearby') }}" />
              <span class="text-danger" data-error="address_line_2"></span>
            </div>
          </div>
          <!-- Location Information -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_country') }} <span class="text-danger">*</span></label>
              <select id="country" class="form-control"></select>
              <span class="text-danger" data-error="country"></span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_state') }} <span class="text-danger">*</span></label>
              <select id="state" class="form-control"></select>
              <span class="text-danger" data-error="state"></span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_city') }} <span class="text-danger">*</span></label>
              <select id="city" class="form-control"></select>
              <span class="text-danger" data-error="city"></span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_postal_code') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="postal_code" placeholder="{{ __('branch.select_code') }}" />
              <span class="text-danger" data-error="postal_code"></span>
            </div>
          </div>
          <!-- Coordinates -->
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_lat') }} <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="latitude" placeholder="{{ __('branch.enter_latitutude') }}" inputmode="decimal" step="any" min="-90" max="90" />
              <span class="text-danger" data-error="latitude"></span>
            </div>
          </div>
          <div class="col-md-3">
            <div class="form-group">
              <label class="form-label">{{ __('branch.lbl_long') }} <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="longitude" placeholder="{{ __('branch.enter_logtitude') }}" inputmode="decimal" step="any" min="-180" max="180" />
              <span class="text-danger" data-error="longitude"></span>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label class="form-label" for="payment-method">{{ __('branch.lbl_payment_method') }} <span class="text-danger">*</span></label>
              <div class="d-flex flex-wrap gap-3" role="group" aria-label="Basic checkbox toggle button group" id="payment_method_group">
                <!-- Checkboxes injected by JS -->
              </div>
              <span class="text-danger" data-error="payment_method"></span>
            </div>
          </div>

          <!-- Description -->
          <div class="col-12">
            <div class="form-group">
              <label class="form-label" for="description">{{ __('branch.lbl_description') }}</label>
              <textarea class="form-control" id="description" name="description" placeholder="{{ __('branch.enter_decription') }}" maxlength="250" data-max-length="250"></textarea>
              <div class="d-flex justify-content-end"><small class="text-muted" id="description-count">0/250</small></div>
              <span class="text-danger" data-error="description"></span>
            </div>
          </div>

          <!-- Custom Fields Section (if any) -->
          <div class="col-12">
            <div id="custom-fields-container">
              <!-- Custom fields will be injected here if available -->
            </div>
          </div>

          <!-- Status -->
          <div class="col-md-2">
            <div class="form-group">
              <div class="d-flex gap-3 align-items-center">
                <label class="form-label" for="branch-status">{{ __('branch.lbl_status') }}</label>
                <div class="form-check form-switch m-0">
                  <input class="form-check-input" type="checkbox" id="branch-status" name="status" value="1" checked>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Form Footer -->
      <div class="offcanvas-footer border-top">
        <div class="d-flex justify-content-start align-items-center gap-2 p-4 flex-wrap">
          <button type="submit" class="btn btn-primary" id="branch-submit-btn">
            <i class="fa-solid fa-floppy-disk mx-2"></i>{{ __('messages.save') }}
          </button>
          <button class="btn btn-outline-primary" type="button" data-bs-dismiss="offcanvas">
            <i class="fa-solid fa-angles-left"></i>{{ __('messages.close') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Hidden composed fields -->
    <input type="hidden" name="address" id="address_json">
    <input type="hidden" name="custom_fields_data" id="custom_fields_json">
    <input type="hidden" name="existing_image" id="existing_image">
    <input type="hidden" name="remove_feature_image" id="remove_feature_image" value="0">
  </div>
</form>

<!-- Employee Create Modal -->
<div class="modal fade" id="managerCreateModal" tabindex="-1" aria-labelledby="managerCreateModalLabel" aria-hidden="true" data-bs-focus="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="managerCreateModalLabel">{{ __('employee.lbl_create_manager') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="manager-create-form" method="POST" action="{{ route('backend.employees.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="row">
            <!-- First Name -->
            <div class="form-group col-md-6">
              <label for="first_name" class="form-label">{{ __('customer.lbl_first_name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="first_name" id="first_name" placeholder="{{ __('employee.first_name') }}" >
              <span class="text-danger" data-error="first_name"></span>
            </div>
            
            <!-- Last Name -->
            <div class="form-group col-md-6">
              <label for="last_name" class="form-label">{{ __('customer.lbl_last_name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="last_name" id="last_name" placeholder="{{ __('employee.last_name') }}">
              <span class="text-danger" data-error="last_name"></span>
            </div>

            <!-- Email -->
            <div class="form-group col-md-6">
              <label for="email" class="form-label">{{ __('customer.lbl_Email') }} <span class="text-danger">*</span></label>
              <input type="email" class="form-control" name="email" id="email" placeholder="{{ __('customer.lbl_Email') }}">
              <span class="text-danger" data-error="email"></span>
            </div>
            
            <!-- Phone Number -->
            <div class="form-group col-md-6">
              <label for="mobile" class="form-label">{{ __('branch.lbl_contact_number')}}<span class="text-danger">*</span></label>
              <input type="tel" class="form-control" name="mobile" id="mobile" placeholder="+1234567890">
              <span class="text-danger" data-error="mobile"></span>
            </div>
            
            <!-- Password -->
            <div class="form-group col-md-6">
              <label for="password" class="form-label">{{ __('employee.lbl_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="password" id="password" placeholder="{{ __('employee.lbl_password') }}">
              <span class="text-danger" data-error="password"></span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group col-md-6">
              <label for="confirm_password" class="form-label">{{ __('employee.lbl_confirm_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="{{ __('employee.lbl_confirm_password') }}">
              <span class="text-danger" data-error="confirm_password"></span>
            </div>
            
            <!-- Gender -->
            <div class="form-group col-md-12">
              <label class="form-label">{{ __('customer.lbl_gender') }}</label>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="gender-male" value="male" checked>
                <label class="form-check-label" for="gender-male">{{ __('messages.male') }}</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="gender-female" value="female">
                <label class="form-check-label" for="gender-female">{{ __('messages.female') }}</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="gender-unisex" value="unisex">
                <label class="form-check-label" for="gender-unisex">{{ __('messages.unisex') }}</label>
              </div>
            </div>
            
            <!-- Hidden Fields for Vue Compatibility -->
            <input type="hidden" name="show_in_calender" value="1">
            <input type="hidden" name="is_manager" value="1">
            <input type="hidden" name="confirmed" value="1">
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-primary" onclick="resetEmployeeForm()" data-bs-dismiss="modal">
            <i class="fa-solid fa-angles-left"></i>{{ __('messages.close') }}
          </button>
          <button type="submit" class="btn btn-primary">{{ __('messages.add_manager') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Intl-Tel-Input JS (correct) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<!-- Intl-Tel-Input utils for formatting/validation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
<script>
(function() {
  const root = document.getElementById('branch-form-root');
  const currentUserIsManager = @json($__currentUserIsManager);
  const form = document.getElementById('branch-form');
  const submitBtn = document.getElementById('branch-submit-btn');
  const maxSizeBytes = 2 * 1024 * 1024;
  let currentId = 0;
  const responseCache = new Map();

  const el = (id) => document.getElementById(id);
  // Normalize backend boolean-like values
  const toBool = (v) => {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number') return v === 1;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      if (s === '1' || s === 'true' || s === 'yes' || s === 'on') return true;
      if (s === '0' || s === 'false' || s === 'no' || s === 'off') return false;
    }
    return !!v;
  };
  // Human-friendly field labels for validation messages
  const fieldLabels = {
    name: '{{ __("branch.lbl_branch_name") }}',
    manager_id: '{{ __("branch.lbl_select_manager") }}',
    contact_number: '{{ __("branch.lbl_contact_number") }}',
    contact_email: '{{ __("branch.lbl_contact_email") }}',
    address_line_1: '{{ __("branch.lbl_shop_number") }}',
    address_line_2: '{{ __("branch.lbl_landmark") }}',
    country: '{{ __("branch.lbl_country") }}',
    state: '{{ __("branch.lbl_state") }}',
    city: '{{ __("branch.lbl_city") }}',
    postal_code: '{{ __("branch.lbl_postal_code") }}',
    latitude: '{{ __("branch.lbl_lat") }}',
    longitude: '{{ __("branch.lbl_long") }}',
  };
  const requiredOf = (fieldKey) => `${fieldLabels[fieldKey] || '{{ __("messages.field") }}'} {{ __("messages.is_required") ?? 'is required' }}`;
  const setError = (name, msg) => {
    const span = document.querySelector(`[data-error="${name}"]`);
    if (span) span.textContent = msg || '';
  };
  const clearErrors = () => document.querySelectorAll('#form-offcanvas [data-error]').forEach(s => s.textContent = '');
  const clearErrorByName = (name) => setError(name, '');

  // Description counter (shared)
  function updateDescriptionCount() {
    try {
      const description = document.getElementById('description');
      const descCount = document.getElementById('description-count');
      if (!description || !descCount) return;
      const maxLen = Number(description.dataset.maxLength || description.getAttribute('maxlength') || 250);
      // Enforce hard max (handles paste beyond maxlength in some browsers)
      if (description.value.length > maxLen) {
        description.value = description.value.slice(0, maxLen);
      }
      descCount.textContent = `${description.value.length}/${maxLen}`;
    } catch (_) {}
  }

  function safeParseToArray(data) {
    if (!data) return [];
    try {
      const parsed = JSON.parse(data);
      if (Array.isArray(parsed)) return parsed;
      if (parsed && typeof parsed === 'object') return Object.values(parsed);
      return [];
    } catch (e) {
      return [];
    }
  }

  function initOptionRadios() {
    const branchFor = safeParseToArray(root.dataset.selectBranchFor);
    const wrap = document.getElementById('branch_for_group');
    wrap.innerHTML = '';
    // Translation mapping for gender options
    const genderTranslations = {
      'male': root.dataset.i18nMale || 'Male',
      'female': root.dataset.i18nFemale || 'Female',
      'unisex': root.dataset.i18nUnisex || 'Unisex',
      'both': root.dataset.i18nBoth || 'Both'
    };
    // Enforce visual order: male, female, unisex (or both)
    const order = ['male', 'female', 'unisex', 'both'];
    branchFor.sort((a, b) => {
      const ai = order.indexOf(String(a.id).toLowerCase());
      const bi = order.indexOf(String(b.id).toLowerCase());
      return (ai === -1 ? 999 : ai) - (bi === -1 ? 999 : bi);
    });
    branchFor.forEach(item => {
      const id = `${item.id}-for`;
      // Use translated label if available, otherwise fallback to item.text
      const translatedText = genderTranslations[String(item.id).toLowerCase()] || item.text;
      wrap.insertAdjacentHTML('beforeend', `
        <input type="radio" class="btn-check" name="branch_for" id="${id}" value="${item.id}" autocomplete="off">
        <label class="btn btn-outline-primary" for="${id}">${translatedText}</label>
      `);
    });
    // default both if exists
    const both = wrap.querySelector('input[value="both"]');
    if (both) both.checked = true;
  }

  function initPaymentMethods() {
    const methods = safeParseToArray(root.dataset.selectPaymentMethods);
    const wrap = document.getElementById('payment_method_group');
    wrap.innerHTML = '';
    methods.forEach(item => {
      const id = `${item.id}-payment-method`;
      wrap.insertAdjacentHTML('beforeend', `
        <div class="d-flex gap-1 form-check">
          <input type="checkbox" class="form-check-input" id="${id}" name="payment_method[]" value="${item.id}">
          <label class="form-label mb-0" for="${id}">${item.text}</label>
        </div>
      `);
    });
    // default cash if exists
    const cash = wrap.querySelector('input[value="cash"]');
    if (cash) cash.checked = true;
  }

  // Handle employee quick create submit
  const managerCreateForm = document.getElementById('manager-create-form');
  if (managerCreateForm) {
    managerCreateForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      // Custom validation (replace browser default messages)
      try {
        const errs = managerCreateForm.querySelectorAll('[data-error]');
        errs.forEach(s => s.textContent = '');
        let valid = true;
        const getVal = (id) => (managerCreateForm.querySelector(`#${id}`)?.value || '').trim();
        const setErr = (name, msg) => { const s = managerCreateForm.querySelector(`[data-error="${name}"]`); if (s) s.textContent = msg; };
        const emailRegex = /^(?!\d)[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
        const first_name = getVal('first_name');
        const last_name = getVal('last_name');
        const email = getVal('email');
        const password = getVal('password');
        const confirm_password = getVal('confirm_password');
        // use intl-tel-input formatted
        let mobile = '';
        try { mobile = getManagerFormattedPhoneNumber(); } catch(_) { mobile = getVal('mobile'); }
        if (!first_name) { setErr('first_name', '{{ __("validation.required", ["attribute" => __("customer.lbl_first_name")]) }}'); valid = false; }
        if (!last_name) { setErr('last_name', '{{ __("validation.required", ["attribute" => __("customer.lbl_last_name")]) }}'); valid = false; }
        if (!email) { setErr('email', '{{ __("validation.required", ["attribute" => __("customer.lbl_Email")]) }}'); valid = false; }
        else if (!emailRegex.test(email)) { setErr('email', '{{ __("validation.email", ["attribute" => __("customer.lbl_Email")]) }}'); valid = false; }
        if (!mobile) { setErr('mobile', '{{ __("validation.required", ["attribute" => __("branch.lbl_contact_number")]) }}'); valid = false; }
        if (!password) { setErr('password', '{{ __("validation.required", ["attribute" => __("employee.lbl_password")]) }}'); valid = false; }
        else if (password.length < 6) { setErr('password', '{{ __("validation.min.string", ["attribute" => __("employee.lbl_password"), "min" => 6]) }}'); valid = false; }
        if (!confirm_password) { setErr('confirm_password', '{{ __("validation.required", ["attribute" => __("employee.lbl_confirm_password")]) }}'); valid = false; }
        else if (confirm_password !== password) { setErr('confirm_password', '{{ __("validation.same", ["attribute" => __("employee.lbl_confirm_password"), "other" => __("employee.lbl_password")]) }}'); valid = false; }
        if (!valid) return;
      } catch(_) {}
      const submitBtn = managerCreateForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __("messages.add_manager") }}...';
      
      try {
        const fd = new FormData(managerCreateForm);
        fd.append('confirmed', '1');
        fd.append('show_in_calender', '1');
        fd.append('is_manager', '1');
        // Ensure full international phone number is submitted
        try {
          const formatted = getManagerFormattedPhoneNumber();
          if (formatted) { fd.set('mobile', formatted); }
        } catch(_) {}
        // Keep role as provided elsewhere; do not override here
        
        if (currentId) {
            fd.append('branch_id', currentId);
        }
        
        const res = await fetch(managerCreateForm.action, { 
          method: 'POST', 
          headers: { 'Accept': 'application/json' }, 
          body: fd 
        });
        
        const json = await res.json();
        if (json.status) {
          if (window.successSnackbar) window.successSnackbar('Manager created successfully');
          
          // Check for specific replacement message
          if (json.message && json.message.includes('automatically replaced')) {
             const managerErrorSpan = document.querySelector('#form-offcanvas [data-error="manager_id"]');
             if(managerErrorSpan) {
                 managerErrorSpan.textContent = json.message;
                 // Keep default text-danger class and do not auto-hide
             }
          }

          // Bypass cache to get fresh list including the newly created manager
          await loadManagers(json.data?.id || null, true);
          const modalEl = document.getElementById('managerCreateModal');
          const closeBtn = modalEl.querySelector('[data-bs-dismiss="modal"]');
          if (closeBtn) closeBtn.click();
          resetEmployeeForm();
        } else {
          if (json.errors) {
            let errorMessage = 'Please fix the following errors:\n';
            Object.keys(json.errors).forEach(field => {
              errorMessage += `• ${field}: ${json.errors[field].join(', ')}\n`;
            });
            if (window.errorSnackbar) window.errorSnackbar(errorMessage);
          } else {
            if (window.errorSnackbar) window.errorSnackbar(json.message || 'Error creating manager');
          }
        }
      } catch (err) {
        console.error('Error creating manager:', err);
        if (window.errorSnackbar) window.errorSnackbar('An error occurred during submission');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      }
    });
  }
  
  // Intl-Tel-Input for Manager Modal
  let managerIti = null;
  function initManagerTelInput() {
    try {
      const input = document.getElementById('mobile');
      if (!input || !window.intlTelInput) return;
      // Destroy previous instance if exists
      if (managerIti && typeof managerIti.destroy === 'function') {
        managerIti.destroy();
      }
      managerIti = window.intlTelInput(input, {
        initialCountry: 'auto',
        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
        separateDialCode: true,
        nationalMode: false,
        geoIpLookup: function(callback) {
          try {
            fetch('https://ipapi.co/json').then(res => res.json()).then(data => callback(data && data.country ? data.country : 'IN')).catch(() => callback('IN'));
          } catch(_) { callback('IN'); }
        }
      });
    } catch(_) {}
  }
  function attachManagerRealtimeValidation() {
    try {
      const form = document.getElementById('manager-create-form');
      if (!form) return;
      const setErr = (name, msg) => { const s = form.querySelector(`[data-error="${name}"]`); if (s) s.textContent = msg || ''; };
      const clear = (name) => setErr(name, '');
      const onNonEmpty = (id, name) => {
        const el = form.querySelector(`#${id}`);
        if (!el) return;
        const handler = () => { if (el.value.trim()) clear(name); };
        el.removeEventListener('input', el.__mgrHandler || (()=>{}));
        el.addEventListener('input', handler);
        el.__mgrHandler = handler;
      };
      // First/Last name
      onNonEmpty('first_name', 'first_name');
      onNonEmpty('last_name', 'last_name');
      // Email
      const emailEl = form.querySelector('#email');
      if (emailEl) {
        const emailRegex = /^(?!\d)[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
        const handler = () => { const v = emailEl.value.trim(); if (v && emailRegex.test(v)) clear('email'); };
        emailEl.removeEventListener('input', emailEl.__mgrHandler || (()=>{}));
        emailEl.addEventListener('input', handler);
        emailEl.__mgrHandler = handler;
      }
      // Mobile (intl-tel-input validity or non-empty fallback)
      const mobileEl = form.querySelector('#mobile');
      if (mobileEl) {
        const handler = () => {
          let ok = false;
          try { ok = managerIti && typeof managerIti.isValidNumber === 'function' ? managerIti.isValidNumber() : false; } catch(_) {}
          if (!ok) {
            const digits = (mobileEl.value || '').replace(/\D/g, '');
            ok = digits.length >= 7;
          }
          if (ok) clear('mobile');
        };
        mobileEl.removeEventListener('input', mobileEl.__mgrHandler || (()=>{}));
        mobileEl.addEventListener('input', handler);
        mobileEl.__mgrHandler = handler;
      }
      // Password and confirm password
      const pwdEl = form.querySelector('#password');
      const cpEl = form.querySelector('#confirm_password');
      const pwdHandler = () => { const v = (pwdEl?.value || '').trim(); if (v.length >= 6) clear('password'); if (cpEl && cpEl.value && cpEl.value === v) clear('confirm_password'); };
      const cpHandler = () => { const v = (cpEl?.value || '').trim(); if (pwdEl && v && v === pwdEl.value) clear('confirm_password'); };
      if (pwdEl) { pwdEl.removeEventListener('input', pwdEl.__mgrHandler || (()=>{})); pwdEl.addEventListener('input', pwdHandler); pwdEl.__mgrHandler = pwdHandler; }
      if (cpEl) { cpEl.removeEventListener('input', cpEl.__mgrHandler || (()=>{})); cpEl.addEventListener('input', cpHandler); cpEl.__mgrHandler = cpHandler; }
    } catch(_) {}
  }
  function getManagerFormattedPhoneNumber() {
    try {
      const input = document.getElementById('mobile');
      if (managerIti && input) {
        const val = managerIti.getNumber();
        return val || input.value || '';
      }
      return (document.getElementById('mobile')?.value || '');
    } catch(_) { return (document.getElementById('mobile')?.value || ''); }
  }
  
  // Ensure modal is interactive while keeping offcanvas open: remove only offcanvas backdrops
  (function ensureModalUsableOverOffcanvas() {
    const managerModalEl = document.getElementById('managerCreateModal');
    if (!managerModalEl) return;
    managerModalEl.addEventListener('show.bs.modal', () => {
      // Initialize tel input for modal phone field
      setTimeout(initManagerTelInput, 0);
      // Attach realtime validation for modal inputs
      setTimeout(attachManagerRealtimeValidation, 0);
      // Temporarily make offcanvas inert to avoid competing focus traps
      try {
        const offcanvasEl = document.getElementById('form-offcanvas');
        if (offcanvasEl) {
          offcanvasEl.setAttribute('aria-hidden', 'true');
          offcanvasEl.setAttribute('inert', '');
          offcanvasEl.style.pointerEvents = 'none';
        }
      } catch (_) {}
      // Ensure focus goes to first input inside modal
      try {
        setTimeout(() => {
          const firstInput = document.querySelector('#managerCreateModal input, #managerCreateModal textarea, #managerCreateModal select');
          if (firstInput) firstInput.focus();
        }, 0);
      } catch(_) {}
      // Guarantee pointer events enabled for modal content
      try {
        const mc = document.querySelector('#managerCreateModal .modal-content');
        if (mc) mc.style.pointerEvents = 'auto';
      } catch(_) {}
    });
    managerModalEl.addEventListener('hidden.bs.modal', () => {
      // Restore offcanvas interactivity
      try {
        const offcanvasEl = document.getElementById('form-offcanvas');
        if (offcanvasEl) {
          offcanvasEl.removeAttribute('aria-hidden');
          offcanvasEl.removeAttribute('inert');
          offcanvasEl.style.pointerEvents = '';
        }
      } catch (_) {}
      // Destroy modal tel input instance to avoid leaks
      try { if (managerIti && typeof managerIti.destroy === 'function') { managerIti.destroy(); managerIti = null; } } catch(_) {}
    });
  })();
  
  function resetEmployeeForm() {
    const form = document.getElementById('manager-create-form');
    if (form) {
      form.reset();
      const maleRadio = form.querySelector('input[name="gender"][value="male"]');
      if (maleRadio) maleRadio.checked = true;
      
      const errorSpans = form.querySelectorAll('[data-error]');
      errorSpans.forEach(span => span.textContent = '');
    }
  }
  // Expose to global scope for inline onclick handler
  window.resetEmployeeForm = resetEmployeeForm;

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    return res.json();
  }

  async function fetchJSONCached(url) {
    if (responseCache.has(url)) {
      return responseCache.get(url);
    }
    const data = await fetchJSON(url);
    responseCache.set(url, data);
    return data;
  }

  async function populateSelect(select, list, valueKey = 'id', labelKey = 'name', placeholderText = null) {
    select.innerHTML = '';
    const optBlank = document.createElement('option');
    optBlank.value = '';
    optBlank.textContent = placeholderText || (root.dataset.i18nSelect || 'Select');
    select.appendChild(optBlank);
    list.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item[valueKey];
      opt.textContent = item[labelKey];
      select.appendChild(opt);
    });
  }

  // Initialize Select2 for manager (single select)
  function initManagerSelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $mgr = $('#manager_id');
    if (!$mgr.length) return;
    try {
      if ($mgr.data('select2')) { $mgr.select2('destroy'); }
    } catch (_) {}
    // Remove any leftover Select2 container next to the element
    try { $mgr.next('.select2').remove(); } catch (_) {}
    try {
      $mgr.select2({
        dropdownParent: $('#form-offcanvas'),
        width: '100%',
        placeholder: '{{ __("messages.select") }} {{ __("branch.lbl_manager_name") }}',
        allowClear: false,
        minimumResultsForSearch: 0,
        containerCssClass: 'select2-manager-dropdown'
      });
    } catch (_) {}
  }

  // Initialize Select2 for service select only (normal dropdown look; no tags/search)
  function initServiceSelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $svc = $('#services_select');
    if (!$svc.length) return;
    try {
      if ($svc.data('select2')) { $svc.select2('destroy'); }
    } catch (_) {}
    // Remove any leftover Select2 container next to the element
    try { $svc.next('.select2').remove(); } catch (_) {}
    
    try {
      $svc.select2({
        dropdownParent: $('#form-offcanvas'),
        width: '100%',
        placeholder: {
          id: '',
          text: '{{ __("branch.select_service") }}'
        },
        allowClear: false,
        tags: false,
        // Show normal Select2 search in dropdown (not inline), better UX for multiple
        minimumResultsForSearch: 0,
        // Keep dropdown open while selecting multiple items
        closeOnSelect: false,
        dropdownAutoWidth: true,
        adaptDropdownWidth: true,
        containerCssClass: 'select2-service-dropdown'
      });
    } catch (_) {}
  }

  // Initialize Select2 for country/state/city
  function initCountrySelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $el = $('#country');
    if (!$el.length) return;
    try { if ($el.data('select2')) { $el.select2('destroy'); } } catch (_) {}
    try { $el.next('.select2').remove(); } catch (_) {}
    try {
      $el.select2({
        dropdownParent: $('#form-offcanvas'),
        width: '100%',
        placeholder: '{{ __("branch.select_country") }}',
        allowClear: false,
        minimumResultsForSearch: 0,
        containerCssClass: 'select2-country-dropdown'
      });
      // Bind dependent change handlers (guard against duplicates)
      $el.off('change.branch').on('change.branch', function() {
        // Clear city immediately on country change
        populateSelect(el('city'), [], 'id', 'name');
        initCitySelect2();
        loadStates(this.value);
      }).off('select2:select.branch').on('select2:select.branch', function() {
        populateSelect(el('city'), [], 'id', 'name');
        initCitySelect2();
        loadStates(this.value);
      }).off('select2:clear.branch').on('select2:clear.branch', function() {
        populateSelect(el('state'), [], 'id', 'name');
        populateSelect(el('city'), [], 'id', 'name');
        initStateSelect2();
        initCitySelect2();
      });
    } catch (_) {}
  }

  function initStateSelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $el = $('#state');
    if (!$el.length) return;
    try { if ($el.data('select2')) { $el.select2('destroy'); } } catch (_) {}
    try { $el.next('.select2').remove(); } catch (_) {}
    try {
      $el.select2({
        dropdownParent: $('#form-offcanvas'),
        width: '100%',
        placeholder: '{{ __("branch.select_state") }}',
        allowClear: false,
        minimumResultsForSearch: 0,
        containerCssClass: 'select2-state-dropdown'
      });
      // Bind dependent change handlers (guard against duplicates)
      $el.off('change.branch').on('change.branch', function() {
        loadCities(this.value);
      }).off('select2:select.branch').on('select2:select.branch', function() {
        loadCities(this.value);
      }).off('select2:clear.branch').on('select2:clear.branch', function() {
        populateSelect(el('city'), [], 'id', 'name');
        initCitySelect2();
      });
    } catch (_) {}
  }

  function initCitySelect2() {
    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') return;
    const $el = $('#city');
    if (!$el.length) return;
    try { if ($el.data('select2')) { $el.select2('destroy'); } } catch (_) {}
    try { $el.next('.select2').remove(); } catch (_) {}
    try {
      $el.select2({
        dropdownParent: $('#form-offcanvas'),
        width: '100%',
        placeholder: '{{ __("branch.select_city") }}',
        allowClear: false,
        minimumResultsForSearch: 0,
        containerCssClass: 'select2-city-dropdown'
      });
    } catch (_) {}
  }

  async function loadManagers(selectedId = null, bypassCache = false) {
    const url = `${root.dataset.employeeListUrl}?role=manager&ignore_branch_filter=1&branch_id=${currentId || ''}`;
    // Clear cache if bypassCache is true (e.g., after creating new manager)
    if (bypassCache && responseCache.has(url)) {
      responseCache.delete(url);
    }
    const list = await fetchJSONCached(url);
    await populateSelect(el('manager_id'), list, 'id', 'name');
    // Initialize Select2 and set selected value if passed
    initManagerSelect2();
    if (selectedId) {
      if (typeof $ !== 'undefined') {
        $('#manager_id').val(String(selectedId)).trigger('change');
      } else {
        el('manager_id').value = String(selectedId);
      }
    }
  }

  async function loadServices(selectedIds = []) {
    const url = root.dataset.serviceListUrl;
    const list = await fetchJSONCached(url);
    // Add placeholder option for multiple select
    await populateSelect(el('services_select'), list, 'id', 'name', '{{ __("branch.select_service") }}');
    // Initialize Select2 and set values
    initServiceSelect2();
    if (typeof $ !== 'undefined') {
      const $svc = $('#services_select');
      if ($svc.length) {
        const values = Array.isArray(selectedIds) ? selectedIds.map(String) : [];
        $svc.val(values).trigger('change');
      }
    } else {
      (Array.isArray(selectedIds) ? selectedIds : []).forEach(v => {
        const opt = Array.from(el('services_select').options).find(o => String(o.value) === String(v));
        if (opt) opt.selected = true;
      });
    }
  }

  async function loadCountries(selectedId = null) {
    const url = root.dataset.countryUrl;
    const list = await fetchJSONCached(url);
    await populateSelect(el('country'), list, 'id', 'name');
    initCountrySelect2();
    if (selectedId) {
      if (typeof $ !== 'undefined') {
        $('#country').val(String(selectedId)).trigger('change');
      } else {
        el('country').value = String(selectedId);
      }
    }
  }

  async function loadStates(countryId, selectedId = null) {
    if (!countryId) { 
      await populateSelect(el('state'), [], 'id', 'name');
      await populateSelect(el('city'), [], 'id', 'name');
      initStateSelect2();
      initCitySelect2();
      return; 
    }
    const url = `${root.dataset.stateUrlBase}?country_id=${countryId}`;
    const list = await fetchJSONCached(url);
    await populateSelect(el('state'), list, 'id', 'name');
    initStateSelect2();
    if (selectedId) {
      if (typeof $ !== 'undefined') {
        $('#state').val(String(selectedId)).trigger('change');
      } else {
        el('state').value = String(selectedId);
      }
    }
  }

  async function loadCities(stateId, selectedId = null) {
    if (!stateId) { 
      await populateSelect(el('city'), [], 'id', 'name');
      initCitySelect2();
      return; 
    }
    const url = `${root.dataset.cityUrlBase}?state_id=${stateId}`;
    const list = await fetchJSONCached(url);
    await populateSelect(el('city'), list, 'id', 'name');
    initCitySelect2();
    if (selectedId) {
      if (typeof $ !== 'undefined') {
        $('#city').val(String(selectedId)).trigger('change');
      } else {
        el('city').value = String(selectedId);
      }
    }
  }

  // Dependent selects
  el('country').addEventListener('change', () => loadStates(el('country').value));
  el('state').addEventListener('change', () => loadCities(el('state').value));

  function attachRealtimeValidation() {
    // Text inputs: clear when non-empty
    const clearOnInputIds = [
      'name', 'address_line_1', 'address_line_2', 'postal_code'
    ];
    clearOnInputIds.forEach((id) => {
      const input = el(id);
      if (!input) return;
      input.removeEventListener('input', input.__clearHandler || (()=>{}));
      const handler = () => {
        if (input.value.trim()) clearErrorByName(id);
      };
      input.addEventListener('input', handler);
      input.__clearHandler = handler;
    });

    // Latitude/Longitude: show error when invalid decimal, clear when valid
    // Require decimal with fractional part (no pure integers)
    const decimalRegex = /^-?\d+\.\d+$/;
    const latInput = el('latitude');
    const longInput = el('longitude');
    const validateLatLong = (inputEl, key) => {
      const v = (inputEl?.value || '').trim();
      if (!v) { setError(key, requiredOf(key)); return; }
      if (!decimalRegex.test(v)) { setError(key, 'Enter decimal value (e.g., 23.123)'); return; }
      const num = Number(v);
      if (key === 'latitude' && (num <= -90 || num >= 90)) { setError(key, 'Latitude must be > -90 and < 90'); return; }
      if (key === 'longitude' && (num <= -180 || num >= 180)) { setError(key, 'Longitude must be > -180 and < 180'); return; }
      clearErrorByName(key);
    };
    if (latInput) {
      latInput.removeEventListener('input', latInput.__validateHandler || (()=>{}));
      latInput.removeEventListener('blur', latInput.__validateHandler || (()=>{}));
      const handler = () => validateLatLong(latInput, 'latitude');
      latInput.addEventListener('input', handler);
      latInput.addEventListener('blur', handler);
      latInput.__validateHandler = handler;
    }
    if (longInput) {
      longInput.removeEventListener('input', longInput.__validateHandler || (()=>{}));
      longInput.removeEventListener('blur', longInput.__validateHandler || (()=>{}));
      const handler = () => validateLatLong(longInput, 'longitude');
      longInput.addEventListener('input', handler);
      longInput.addEventListener('blur', handler);
      longInput.__validateHandler = handler;
    }

    // Email: clear when non-empty and valid
    const emailInput = el('contact_email');
    if (emailInput) {
      const emailRegex = /^(?!\d)[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
      emailInput.removeEventListener('input', emailInput.__clearHandler || (()=>{}));
      const handler = () => {
        const v = emailInput.value.trim();
        if (v && emailRegex.test(v)) clearErrorByName('contact_email');
      };
      emailInput.addEventListener('input', handler);
      emailInput.__clearHandler = handler;
    }

    // Description: update live character counter
    const description = el('description');
    const descCount = document.getElementById('description-count');
    if (description && descCount) {
      const maxLen = Number(description.dataset.maxLength || description.getAttribute('maxlength') || 500);
      const updateCount = () => {
        // keep in sync and enforce max
        if (description.value.length > maxLen) {
          description.value = description.value.slice(0, maxLen);
        }
        descCount.textContent = `${description.value.length}/${maxLen}`;
      };
      description.removeEventListener('input', description.__countHandler || (()=>{}));
      description.addEventListener('input', updateCount);
      description.__countHandler = updateCount;
      // Initialize on attach
      updateCount();
    }

    // Phone: clear when not empty
    const phoneInput = el('contact_number');
    if (phoneInput) {
      phoneInput.removeEventListener('input', phoneInput.__clearHandler || (()=>{}));
      const handler = () => { if (phoneInput.value.trim()) clearErrorByName('contact_number'); };
      phoneInput.addEventListener('input', handler);
      phoneInput.__clearHandler = handler;
    }

    // Selects: manager, country, state, city clear on change when selected
    const clearOnChangeIds = ['manager_id', 'country', 'state', 'city'];
    clearOnChangeIds.forEach((id) => {
      const select = el(id);
      if (!select) return;
      const clear = () => { if (select.value) clearErrorByName(id); };
      select.removeEventListener('change', select.__clearHandler || (()=>{}));
      select.addEventListener('change', clear);
      select.__clearHandler = clear;
      if (typeof $ !== 'undefined') {
        try { $(`#${id}`).off('select2:select.realtime select2:clear.realtime').on('select2:select.realtime select2:clear.realtime', clear); } catch(_) {}
      }
    });
  }

  function resetForm() {
    form.reset();
    clearErrors();
    currentId = 0;
    document.getElementById('branch-form-title-create').style.display = '';
    document.getElementById('branch-form-title-edit').style.display = 'none';
    el('feature-image-preview').src = root.dataset.defaultImage;
    document.getElementById('remove-image-btn').style.display = 'none';
    el('existing_image').value = '';
    el('remove_feature_image').value = '0';
    // Set status to checked (active) by default
    const statusEl = el('branch-status');
    if (statusEl) {
      statusEl.checked = true;
      statusEl.setAttribute('checked', 'checked');
    }
    // Ensure any previous Select2 instances are removed
    if (typeof $ !== 'undefined') {
      const $svc = $('#services_select');
      if ($svc.length && $svc.data('select2')) {
        try { $svc.select2('destroy'); } catch(_) {}
      }
      const $mgr = $('#manager_id');
      if ($mgr.length && $mgr.data('select2')) {
        try { $mgr.select2('destroy'); } catch(_) {}
      }
      const $country = $('#country');
      if ($country.length && $country.data('select2')) {
        try { $country.select2('destroy'); } catch(_) {}
      }
      const $state = $('#state');
      if ($state.length && $state.data('select2')) {
        try { $state.select2('destroy'); } catch(_) {}
      }
      const $city = $('#city');
      if ($city.length && $city.data('select2')) {
        try { $city.select2('destroy'); } catch(_) {}
      }
    }
    initOptionRadios();
    initPaymentMethods();
    loadManagers();
    loadServices([]);
    loadCountries();
    // Reset state and city with placeholder and init Select2
    populateSelect(el('state'), [], 'id', 'name');
    populateSelect(el('city'), [], 'id', 'name');
    initStateSelect2();
    initCitySelect2();
    // Initialize description counter after reset
    updateDescriptionCount();
    // Re-initialize telephone input when form is reset
    initTelephoneInput();
    // Attach realtime validation listeners
    attachRealtimeValidation();
    // Ensure manager select and create button are enabled for Add (create) mode
    try {
      const mgrSelect = el('manager_id');
      const mgrCreateBtn = document.getElementById('manager-create-btn');
      if (mgrSelect) { mgrSelect.disabled = false; mgrSelect.removeAttribute('title'); }
      if (mgrCreateBtn) { mgrCreateBtn.disabled = false; mgrCreateBtn.removeAttribute('title'); }
    } catch (_) {}
  }

  function fillForm(data) {
    // Basic fields
    el('name').value = data.name || '';
    // Ensure phone shows only national number in input while country code is handled by tel UI
    try {
      const rawPhone = data.contact_number || '';
      el('contact_number').value = '';
      initTelephoneInput();
      if (window.iti && rawPhone) {
        window.iti.setNumber(rawPhone);
        if (window.intlTelInputUtils && typeof window.iti.getNumber === 'function') {
          const national = window.iti.getNumber(window.intlTelInputUtils.numberFormat.NATIONAL) || '';
          el('contact_number').value = national;
        } else {
          const onlyDigits = String(rawPhone).replace(/[^\d]/g, '');
          const localGuess = onlyDigits.length > 12 ? onlyDigits.slice(-10) : onlyDigits.replace(/^\d{1,4}/, '');
          el('contact_number').value = localGuess;
        }
      } else {
        el('contact_number').value = rawPhone;
      }
    } catch (_) {
      el('contact_number').value = data.contact_number || '';
    }
    el('contact_email').value = data.contact_email || '';
    el('description').value = data.description || '';
    updateDescriptionCount();
    // Status reflect backend dynamically
    const statusEl = el('branch-status');
    const statusOn = toBool(data.status);
    statusEl.checked = statusOn;
    if (statusOn) {
      statusEl.setAttribute('checked', 'checked');
    } else {
      statusEl.removeAttribute('checked');
    }
    const statusLabel = document.getElementById('status-label');
    if (statusLabel) statusLabel.textContent = data.status ? '{{ __("messages.active") }}' : '{{ __("messages.inactive") }}';
    
    // Branch for radio
    const radio = document.querySelector(`#branch_for_group input[value="${data.branch_for}"]`);
    if (radio) radio.checked = true;
    
    // Image - Handle both URL and existing image paths
    if (data.feature_image) {
      if (typeof data.feature_image === 'string') {
        if (data.feature_image.startsWith('http')) {
          // Full URL
          el('feature-image-preview').src = data.feature_image;
          document.getElementById('remove-image-btn').style.display = '';
          // Store the original path for form submission
          el('existing_image').value = data.feature_image;
        } else if (data.feature_image.startsWith('/') || data.feature_image.includes('storage/')) {
          // Relative path or storage path - construct full URL
          const baseUrl = window.location.origin;
          el('feature-image-preview').src = baseUrl + (data.feature_image.startsWith('/') ? '' : '/') + data.feature_image;
          document.getElementById('remove-image-btn').style.display = '';
          // Store the original path for form submission
          el('existing_image').value = data.feature_image;
        } else {
          // Default image
          el('feature-image-preview').src = root.dataset.defaultImage;
          document.getElementById('remove-image-btn').style.display = 'none';
          el('existing_image').value = '';
        }
      } else {
        // Object or other format - try to get URL
        const imageUrl = data.feature_image.url || data.feature_image.path || data.feature_image;
        if (imageUrl && typeof imageUrl === 'string') {
          el('feature-image-preview').src = imageUrl;
          document.getElementById('remove-image-btn').style.display = '';
          // Store the original path for form submission
          el('existing_image').value = imageUrl;
        } else {
          el('feature-image-preview').src = root.dataset.defaultImage;
          document.getElementById('remove-image-btn').style.display = 'none';
          el('existing_image').value = '';
        }
      }
    } else {
      // No image data
      el('feature-image-preview').src = root.dataset.defaultImage;
      document.getElementById('remove-image-btn').style.display = 'none';
      el('existing_image').value = '';
    }
    
    // Address - Parse if it's a string
    let address = data.address;
    if (typeof address === 'string') {
      try {
        address = JSON.parse(address);
      } catch (e) {
        address = {};
      }
    }
    
    if (address) {
      el('postal_code').value = address.postal_code || '';
      el('address_line_1').value = address.address_line_1 || '';
      el('address_line_2').value = address.address_line_2 || '';
      el('latitude').value = address.latitude || '';
      el('longitude').value = address.longitude || '';
      
      // Load location data in sequence
      (async () => {
        await loadCountries(address.country || null);
        await loadStates(address.country || null, address.state || null);
        await loadCities(address.state || null, address.city || null);
      })();
    }
    
    // Payment methods
    const pmWrap = document.getElementById('payment_method_group');
    Array.from(pmWrap.querySelectorAll('input[type="checkbox"]')).forEach(cb => {
      cb.checked = (Array.isArray(data.payment_method) && data.payment_method.includes(cb.value));
    });
    
    // Load selects with data (ensure we wait for managers to be populated before mutating controls)
    loadManagers(data.manager_id || null).then(() => {
      try {
        const mgrSelect = el('manager_id');
        const mgrCreateBtn = document.getElementById('manager-create-btn');
        if (currentUserIsManager) {
          if (mgrSelect) { mgrSelect.disabled = true; mgrSelect.setAttribute('title', '{{ __("messages.permission_denied") }}'); }
          if (mgrCreateBtn) { mgrCreateBtn.disabled = true; mgrCreateBtn.setAttribute('title', '{{ __("messages.permission_denied") }}'); }
        } else {
          if (mgrSelect) { mgrSelect.disabled = false; mgrSelect.removeAttribute('title'); }
          if (mgrCreateBtn) { mgrCreateBtn.disabled = false; mgrCreateBtn.removeAttribute('title'); }
        }
      } catch (_) {}
    });
    
    // Service IDs - Parse if it's a string
    let serviceIds = data.service_id;
    if (typeof serviceIds === 'string') {
      try {
        serviceIds = JSON.parse(serviceIds);
      } catch (e) {
        serviceIds = [];
      }
    }
    loadServices(serviceIds || []);
    
    // Update titles
    document.getElementById('branch-form-title-create').style.display = 'none';
    document.getElementById('branch-form-title-edit').style.display = '';
  }

  // Image preview & validation
  el('feature_image').addEventListener('change', (e) => {
    const file = e.target.files[0];
    const errorEl = document.getElementById('feature-image-error');
    const infoEl = document.getElementById('feature-image-info');
    
    if (!file) {
      // Hide error and show info when no file selected
      if (errorEl) {
        errorEl.style.display = 'none';
        errorEl.textContent = '';
      }
      if (infoEl) infoEl.style.display = 'block';
      return;
    }
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const fileType = file.type.toLowerCase();
    const fileName = file.name.toLowerCase();
    const isValidType = allowedTypes.includes(fileType) || 
                        fileName.endsWith('.jpeg') || 
                        fileName.endsWith('.jpg') || 
                        fileName.endsWith('.png') || 
                        fileName.endsWith('.gif');
    
    // Validate file size
    const isValidSize = file.size <= maxSizeBytes;
    
    if (!isValidType) {
      // Show error for invalid file type
      if (errorEl) {
        errorEl.textContent = root.dataset.i18nImageError || 'Only JPEG, JPG, PNG, GIF files are allowed maximum size 2 MB.';
        errorEl.style.display = 'block';
      }
      if (infoEl) infoEl.style.display = 'none';
      e.target.value = '';
      return;
    }
    
    if (!isValidSize) {
      // Show error for file size
      if (errorEl) {
        errorEl.textContent = root.dataset.i18nImageError || 'Only JPEG, JPG, PNG, GIF files are allowed maximum size 2 MB.';
        errorEl.style.display = 'block';
      }
      if (infoEl) infoEl.style.display = 'none';
      e.target.value = '';
      return;
    }
    
    // File is valid - hide error and show info
    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = '';
    }
    if (infoEl) infoEl.style.display = 'block';
    
    // Reset remove flag when new image is selected
    el('remove_feature_image').value = '0';
    
    // Load and preview the image
    const reader = new FileReader();
    reader.onload = () => {
      el('feature-image-preview').src = reader.result;
      document.getElementById('remove-image-btn').style.display = '';
    };
    reader.readAsDataURL(file);
  });
  
  document.getElementById('remove-image-btn').addEventListener('click', () => {
    // Clear the file input
    el('feature_image').value = '';
    
    // Reset preview to default image
    el('feature-image-preview').src = root.dataset.defaultImage;
    
    // Hide remove button
    document.getElementById('remove-image-btn').style.display = 'none';
    
    // Mark image for removal if editing existing branch with image
    if (currentId && el('existing_image').value) {
      el('remove_feature_image').value = '1';
    }
    
    // Clear existing image reference
    el('existing_image').value = '';
    
    // Hide error and show info when image is removed
    const errorEl = document.getElementById('feature-image-error');
    const infoEl = document.getElementById('feature-image-info');
    if (errorEl) {
      errorEl.style.display = 'none';
      errorEl.textContent = '';
    }
    if (infoEl) infoEl.style.display = 'block';
    
    // Clear any validation errors for feature_image
    clearErrorByName('feature_image');
  });

  // Update status label on switch toggle
  document.getElementById('branch-status').addEventListener('change', function() {
    document.getElementById('status-label').textContent = this.checked ? '{{ __("messages.active") }}' : '{{ __("messages.inactive") }}';
  });

  // Initialize telephone input with telinput
  function initTelephoneInput() {
    const phoneInput = document.getElementById('contact_number');
    if (!phoneInput || !window.intlTelInput) return;
    
    try {
      // Destroy previous instance if exists
      if (window.iti && typeof window.iti.destroy === 'function') {
        window.iti.destroy();
      }
      
      window.iti = window.intlTelInput(phoneInput, {
        initialCountry: 'in',
        geoIpLookup: function(callback) {
          // Default to IN; you can replace with a real geo lookup service
          callback('in');
        },
        preferredCountries: ['in','us','gb','ca','au'],
        separateDialCode: true,
        nationalMode: true,
        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
        formatOnDisplay: true,
        autoHideDialCode: false,
        autoPlaceholder: 'aggressive'
      });
    } catch (error) {
      console.warn('Intl-Tel-Input initialization failed:', error);
    }
  }

  // Get formatted phone number for form submission
  function getFormattedPhoneNumber() {
    const input = document.getElementById('contact_number');
    if (window.iti && window.intlTelInputUtils && typeof window.iti.isValidNumber === 'function') {
      const value = window.iti.getNumber(window.intlTelInputUtils.numberFormat.E164);
      return value || (input ? input.value : '');
    }
    return input ? input.value : '';
  }

  // Validate phone number
  function validatePhoneNumber() {
    if (window.iti) {
      return window.iti.isValidNumber();
    }
    // Fallback validation
    const phoneNumber = document.getElementById('contact_number')?.value || '';
    const digitsOnly = phoneNumber.replace(/\D/g, '');
    return digitsOnly.length >= 7;
  }

  // Create/Edit trigger via global custom event
  document.addEventListener('crud_change_id', async (e) => {
    const rawId = e.detail?.form_id;
    const id = Number(rawId) || 0;
    if (!id) {
      resetForm();
      return;
    }
    currentId = id;
    clearErrors();
    const url = `${root.dataset.editUrlBase}/${id}/edit`;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const json = await res.json();
      if (json.status && json.data) {
        fillForm(json.data);
      }
    } catch (err) {
      console.error('Error loading edit data:', err);
    }
  });

  function validate() {
    let ok = true;
    clearErrors();
    const requiredMsg = root.dataset.i18nRequired || 'This field is required.';
    
    if (!el('name').value.trim()) { setError('name', requiredOf('name')); ok = false; }
  if (!(currentUserIsManager && currentId) && !el('manager_id').value) { setError('manager_id', requiredOf('manager_id')); ok = false; }
    
    // Phone number validation - only required
    if (!el('contact_number').value.trim()) { 
      setError('contact_number', requiredOf('contact_number')); 
      ok = false; 
    }
    
    const email = el('contact_email').value.trim();
    const emailRegex = /^(?!\d)[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
    if (!email) { setError('contact_email', requiredOf('contact_email')); ok = false; }
    else if (!emailRegex.test(email)) { setError('contact_email', root.dataset.i18nInvalidEmail || 'Invalid email.'); ok = false; }
    
    // Branch for (gender-like) validation
    try {
      const selectedBranchFor = document.querySelector('#branch_for_group input[name="branch_for"]:checked');
      if (!selectedBranchFor) { setError('branch_for', requiredOf('branch_for')); ok = false; }
    } catch(_) {}

    // Address validation
    if (!el('address_line_1').value.trim()) { setError('address_line_1', requiredOf('address_line_1')); ok = false; }
    if (!el('country').value) { setError('country', requiredOf('country')); ok = false; }
    if (!el('state').value) { setError('state', requiredOf('state')); ok = false; }
    if (!el('city').value) { setError('city', requiredOf('city')); ok = false; }
    if (!el('postal_code').value.trim()) { setError('postal_code', requiredOf('postal_code')); ok = false; }
    if (!el('latitude').value.trim()) { setError('latitude', requiredOf('latitude')); ok = false; }
    if (!el('longitude').value.trim()) { setError('longitude', requiredOf('longitude')); ok = false; }
    // Decimal (double) validation for latitude/longitude
    const latVal = el('latitude').value.trim();
    const longVal = el('longitude').value.trim();
    // Require decimal with fractional part (no pure integers)
    const decimalRegex = /^-?\d+\.\d+$/;
    if (latVal && !decimalRegex.test(latVal)) { setError('latitude', 'Enter decimal value (e.g., 23.123)'); ok = false; }
    if (longVal && !decimalRegex.test(longVal)) { setError('longitude', 'Enter decimal value (e.g., 72.987)'); ok = false; }
    const latNum = Number(latVal);
    const longNum = Number(longVal);
    if (latVal && (latNum <= -90 || latNum >= 90)) { setError('latitude', 'Latitude must be > -90 and < 90'); ok = false; }
    if (longVal && (longNum <= -180 || longNum >= 180)) { setError('longitude', 'Longitude must be > -180 and < 180'); ok = false; }
    
    return ok;
  }

  function composeHiddenFields() {
    const address = {
      address_line_1: el('address_line_1').value.trim(),
      address_line_2: el('address_line_2').value.trim(),
      country: el('country').value || '',
      state: el('state').value || '',
      city: el('city').value || '',
      postal_code: el('postal_code').value.trim(),
      latitude: el('latitude').value.trim(),
      longitude: el('longitude').value.trim(),
    };
    el('address_json').value = JSON.stringify(address);
    el('custom_fields_json').value = JSON.stringify({});
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) return;
    
    // Get formatted phone number before form submission
    const formattedPhone = getFormattedPhoneNumber();
    el('contact_number').value = formattedPhone;
    
    composeHiddenFields();
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${originalText}`;
    
    try {
      const fd = new FormData(form);
      const url = currentId ? `${root.dataset.updateUrlBase}/${currentId}` : root.dataset.storeUrl;
      if (currentId) fd.append('_method', 'PUT');
      const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
      const json = await res.json();
      if (json.status) {
        if (window.successSnackbar) window.successSnackbar(json.message || 'Branch saved successfully');
        if (window.renderedDataTable) renderedDataTable.ajax.reload(null, false);
        
        const offcanvasEl = document.getElementById('form-offcanvas');
        const closeBtn = offcanvasEl.querySelector('[data-bs-dismiss="offcanvas"]');
        if (closeBtn) closeBtn.click();
        resetForm();
      } else {
        // Map field errors inline
        let nonBranchForErrors = [];
        if (json.all_message) {
          Object.entries(json.all_message).forEach(([k, v]) => {
            const msg = Array.isArray(v) ? v[0] : v;
            setError(k, msg);
            // Exclude branch_for, contact_email, and contact_number from snackbar display
            if (k !== 'branch_for' && k !== 'contact_email' && k !== 'contact_number' && msg) {
              nonBranchForErrors.push(msg);
            }
          });
        }
        // Show snackbar only if there are errors other than inline field errors
        const showGeneric = (!json.all_message) || (nonBranchForErrors.length > 0);
        if (showGeneric && window.errorSnackbar) window.errorSnackbar(json.message || nonBranchForErrors[0] || 'Error');
      }
    } catch (err) {
      console.error('Form submission error:', err);
      if (window.errorSnackbar) window.errorSnackbar('An error occurred during submission');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Initialize on load for create
  document.addEventListener('DOMContentLoaded', resetForm);
  // Also ensure realtime validation is attached on DOM ready (in case resetForm is bypassed)
  document.addEventListener('DOMContentLoaded', attachRealtimeValidation);
  
  // Guard: ignore select2 close events on non-initialized elements
  if (typeof $ !== 'undefined') {
    $(document).on('select2:close', function(e) {
      const $t = $(e.target);
      if (!$t.hasClass('select2-hidden-accessible')) {
        e.stopImmediatePropagation();
      }
    });
    
         // Prevent email field from interfering with Select2 dropdowns
     $(document).on('click', '#contact_email', function(e) {
       // Stop event propagation to prevent any Select2 interference
       e.stopPropagation();
       e.stopImmediatePropagation();
       
       // Close any open Select2 dropdowns when clicking on email field
       $('.select2-dropdown').hide();
       $('.select2-container--open').removeClass('select2-container--open');
       
       // Ensure the email field gets focus without triggering Select2
       setTimeout(() => {
         $(this).focus();
       }, 10);
     });
     
     // Additional isolation for email field
     $(document).on('focus', '#contact_email', function(e) {
       // Close any open Select2 dropdowns when email field gets focus
       $('.select2-dropdown').hide();
       $('.select2-container--open').removeClass('select2-container--open');
     });
     
     // Prevent any Select2 events from interfering with email field
     $(document).on('mousedown', '#contact_email', function(e) {
       e.stopPropagation();
       e.stopImmediatePropagation();
     });
     
     $(document).on('mouseup', '#contact_email', function(e) {
       e.stopPropagation();
       e.stopImmediatePropagation();
     });
     
     // Ensure email field input events don't trigger Select2
     $(document).on('input', '#contact_email', function(e) {
       e.stopPropagation();
     });
    
    // Ensure proper event handling for Select2
    $(document).on('select2:open', function(e) {
      // Close other open dropdowns when opening a new one
      $('.select2-container--open').not($(e.target).closest('.select2-container')).removeClass('select2-container--open');
      $('.select2-dropdown').not($(e.target).closest('.select2-container').find('.select2-dropdown')).hide();
      // Auto-focus dropdown search for services select
      if (e.target && e.target.id === 'services_select') {
        setTimeout(function() {
          const input = document.querySelector('.select2-container--open .select2-search--dropdown .select2-search__field');
          if (input) { input.focus(); }
        }, 0);
      }
    });
  }
  
  // Reset form when offcanvas is hidden
  const off = document.getElementById('form-offcanvas');
  if (off) {
    off.addEventListener('hidden.bs.offcanvas', resetForm);
    off.addEventListener('shown.bs.offcanvas', function() {
      // Initialize telephone input when form is shown
      setTimeout(() => {
        initTelephoneInput();
        const cn = el('contact_number');
        if (window.iti && cn && cn.value) {
          try {
            // Only reset number if it is in international format; otherwise keep national display
            if (/^\s*\+/.test(cn.value)) {
              window.iti.setNumber(cn.value);
            }
          } catch (e) {}
        }
        // Ensure Select2 is applied to services on open
        initServiceSelect2();
        // Ensure Select2 is applied to manager on open
        initManagerSelect2();
        // Ensure Select2 is applied to location selects on open
        initCountrySelect2();
        initStateSelect2();
        initCitySelect2();
        // Ensure description counter reflects any pre-filled value on open
        updateDescriptionCount();
      }, 100);
    });
  }

  // Prevent multiple backdrops from stacking
  function dedupeBackdrops() {
    try {
      const oc = document.querySelectorAll('.offcanvas-backdrop');
      if (oc.length > 1) {
        oc.forEach((el, idx) => { if (idx > 0) el.remove(); });
      }
      const mb = document.querySelectorAll('.modal-backdrop');
      if (mb.length > 1) {
        mb.forEach((el, idx) => { if (idx > 0) el.remove(); });
      }
    } catch (_) { /* ignore */ }
  }
  document.addEventListener('shown.bs.offcanvas', () => setTimeout(dedupeBackdrops, 50));
  document.addEventListener('hidden.bs.offcanvas', () => setTimeout(dedupeBackdrops, 50));
})();
</script>

