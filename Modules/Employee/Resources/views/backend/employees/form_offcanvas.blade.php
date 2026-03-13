@php
  $authUser = auth()->user();
  $isManagerUser = $authUser && $authUser->hasRole('manager');
  $selectedBranchName = isset($selected_branch) ? $selected_branch->name : '';
@endphp

<form id="employee-form" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="id" id="employee_id" value="0">
  <input type="hidden" name="custom_fields_data" id="employee_custom_fields_data" value="{}">
  <input type="hidden" name="remove_profile_image" id="remove_profile_image" value="0">

  <div class="offcanvas offcanvas-end offcanvas-width" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="form-offcanvasLabel">
        <span id="employee-form-title-create">{{ __('messages.new') }} {{ __('employee.singular_title') }}</span>
        <span id="employee-form-title-edit" style="display:none;">{{ __('messages.edit') }} {{ __('employee.singular_title') }}</span>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
      <div class="row">
        <div class="col-12 row">
          <div class="col-md-8">
            <div class="row">
              <div class="col-md-6 form-group">
                <label class="form-label">{{ __('employee.lbl_first_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="first_name" id="first_name" placeholder="{{ __('employee.first_name') }}" autocomplete="off">
                <span class="text-danger" data-error="first_name"></span>
              </div>
              <div class="col-md-6 form-group">
                <label class="form-label">{{ __('employee.lbl_last_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="last_name" id="last_name" placeholder="{{ __('employee.last_name') }}" autocomplete="off">
                <span class="text-danger" data-error="last_name"></span>
              </div>

              <div class="col-md-6 form-group">
                <label class="form-label">{{ __('employee.lbl_Email') }} <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" id="email" placeholder="{{ __('employee.email_address') }}" autocomplete="off">
                <span class="text-danger" data-error="email"></span>
              </div>

              <div class="form-group col-md-6">
                <label class="form-label">{{ __('employee.lbl_phone_number') }} <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" name="mobile" id="mobile" placeholder="+1 555 123 4567" autocomplete="new-password">
                <span class="text-danger" data-error="mobile"></span>
              </div>
            </div>
          </div>

          <div class="col-md-4 text-center">
            <img src="{{ default_user_avatar() }}" class="img-fluid avatar avatar-120 avatar-rounded mb-2" alt="profile-image" id="employee_profile_preview" />
            <div class="text-danger mb-2 d-none" id="employee_image_validation"></div>
            <input type="hidden" id="existing_profile_image" value="" />
            <div class="d-flex align-items-center justify-content-center gap-2">
              <input type="file" class="form-control d-none" id="profile_image" name="profile_image" accept=".jpeg,.jpg,.png,.gif" />
              <label class="btn btn-info" for="profile_image" id="upload_profile_image_btn">{{ __('messages.upload') }}</label>
              <button type="button" class="btn btn-danger d-none" id="remove_profile_image_btn">{{ __('messages.remove') }}</button>
            </div>
            <span class="text-danger" data-error="profile_image"></span>
          </div>

          <div class="row" id="password-row">
            <div class="col-md-6 form-group">
              <label class="form-label">{{ __('employee.lbl_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="password" id="password" placeholder="{{ __('employee.password') }}" autocomplete="new-password">
              <span class="text-danger" data-error="password"></span>
            </div>
            <div class="col-md-6 form-group">
              <label class="form-label">{{ __('employee.lbl_confirm_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="{{ __('employee.confirm_password') }}" autocomplete="new-password">
              <span class="text-danger" data-error="confirm_password"></span>
            </div>
          </div>

          <div class="form-group col-md-4">
            <label class="w-100">{{ __('employee.lbl_gender') }}</label>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="gender" id="gender_male" value="male" checked>
              <label class="form-check-label" for="gender_male">{{ __('Male') }}</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="gender" id="gender_female" value="female">
              <label class="form-check-label" for="gender_female">{{ __('Female') }}</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="gender" id="gender_other" value="other">
              <label class="form-check-label" for="gender_other">{{ __('Intersex') }}</label>
            </div>
            <p class="mb-0 text-danger" data-error="gender"></p>
          </div>

          <div class="form-group m-0 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" name="show_in_calender" id="show_in_calender" checked>
              <label class="form-check-label" for="show_in_calender">{{ __('employee.lbl_show_in_calender') }}</label>
            </div>
          </div>

          @if(!$isManagerUser)
          <div class="form-group m-0 col-md-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" name="is_manager" id="is_manager">
              <label class="form-check-label" for="is_manager">{{ __('employee.lbl_is_manager') }}</label>
            </div>
          </div>
          @endif

          <div class="form-group col-md-12" id="branch_select_group">
            <label class="form-label" for="branch_id">{{ __('employee.lbl_select_branch') }} <span class="text-danger">*</span></label>
            <select id="branch_id" name="branch_id" class="form-control select2" data-placeholder="{{ __('messages.select_branch') }}"></select>
            <span class="text-danger" data-error="branch_id"></span>
          </div>



          <div class="form-group col-md-12">
            <label class="form-label" for="service_id">{{ __('employee.lbl_select_service') }}</label>
            <select id="service_id" name="service_id[]" class="form-control select2" data-placeholder="{{ __('messages.select_service') }}" multiple></select>
            <span class="text-danger" data-error="service_id"></span>
          </div>

          <div class="form-group col-md-12">
            <label class="form-label" for="commission_id">{{ __('employee.lbl_select_commission') }} <span class="text-danger">*</span></label>
            <select id="commission_id" name="commission_id" class="form-control select2" data-placeholder="{{ __('messages.select_commission') }}"></select>
            <span class="text-danger" data-error="commission_id"></span>
          </div>

          @if(!empty($customefield))
            @foreach($customefield as $field)
              <div class="form-group col-md-12">
                <label class="form-label" for="custom_field_{{ $field->id }}">{{ $field->label }}@if($field->required) <span class="text-danger">*</span>@endif</label>
                @if($field->type === 'text' || $field->type === 'number' || $field->type === 'password' || $field->type === 'date')
                  <input type="{{ $field->type }}" class="form-control" id="custom_field_{{ $field->id }}" data-custom-field="field_{{ $field->id }}" @if($field->required) required @endif />
                @elseif($field->type === 'select')
                  <select class="form-control" id="custom_field_{{ $field->id }}" data-custom-field="field_{{ $field->id }}" @if($field->required) required @endif>
                    <option value="">{{ __('messages.select') }}</option>
                    @if($field->value)
                      @foreach(json_decode($field->value) as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                      @endforeach
                    @endif
                  </select>
                @elseif($field->type === 'textarea')
                  <textarea class="form-control" id="custom_field_{{ $field->id }}" data-custom-field="field_{{ $field->id }}" @if($field->required) required @endif></textarea>
                @endif
                <span class="text-danger" data-error="custom_fields_data.field_{{ $field->id }}"></span>
              </div>
            @endforeach
          @endif

          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_about_self') }}</label>
            <input type="text" class="form-control" name="about_self" id="about_self" maxlength="191" placeholder="{{ __('employee.about_self') }}">
            <small class="text-muted d-block mt-1" id="about_self_counter">0/191</small>
            <span class="text-danger" data-error="about_self"></span>
          </div>
          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_expert') }}</label>
            <input type="text" class="form-control" name="expert" id="expert" placeholder="{{ __('employee.expert') }}">
            <span class="text-danger" data-error="expert"></span>
          </div>
          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_facebook_link') }}</label>
            <input type="text" class="form-control" name="facebook_link" id="facebook_link" placeholder="{{ __('employee.facebook_link') }}">
            <span class="text-danger" data-error="facebook_link"></span>
          </div>
          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_instagram_link') }}</label>
            <input type="text" class="form-control" name="instagram_link" id="instagram_link" placeholder="{{ __('employee.instagram_link') }}">
            <span class="text-danger" data-error="instagram_link"></span>
          </div>
          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_x_link') }}</label>
            <input type="text" class="form-control" name="twitter_link" id="twitter_link" placeholder="{{ __('employee.x_link') }}">
            <span class="text-danger" data-error="twitter_link"></span>
          </div>
          <div class="col-md-6 form-group">
            <label class="form-label">{{ __('employee.lbl_dribbble_link') }}</label>
            <input type="text" class="form-control" name="dribbble_link" id="dribbble_link" placeholder="{{ __('employee.dribble_link') }}">
            <span class="text-danger" data-error="dribbble_link"></span>
          </div>

          <div class="form-group col-md-12">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label" for="status">{{ __('employee.lbl_status') }}</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" id="status_employee" checked>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="offcanvas-footer border-top">
      <div class="d-grid d-md-flex gap-3 p-3">
        <button class="btn btn-primary d-block" type="submit">
          <i class="fa-solid fa-floppy-disk"></i>
          {{ __('messages.save') }}
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/css/intlTelInput.css" />
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js"></script>
@endpush

@push('after-scripts')
<script type="text/javascript">
(function($){
  const offcanvasEl = document.getElementById('form-offcanvas');
  if(!offcanvasEl) return;

  const instance = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
  const $form = $('#employee-form');
  const $id = $('#employee_id');
  const $branch = $('#branch_id');
  const $service = $('#service_id');
  const $commission = $('#commission_id');

  const $isManagerCheckbox = $('#is_manager');
  const $passwordRow = $('#password-row');
  const $statusEmployee = $('#status_employee');
  const $profile = $('#profile_image');
  const $profilePreview = $('#employee_profile_preview');
  const $removeProfileBtn = $('#remove_profile_image_btn');
  const $imageValidation = $('#employee_image_validation');
  const $customFieldsJson = $('#employee_custom_fields_data');
  const $existingProfileImage = $('#existing_profile_image');
  const $removeProfileImageFlag = $('#remove_profile_image');
  const IS_MANAGER = {{ $isManagerUser ? 'true' : 'false' }};
  const MANAGER_BRANCH_ID = {{ isset($selected_branch_id) ? (int) $selected_branch_id : 0 }};
  const MANAGER_BRANCH_NAME = {!! json_encode($selectedBranchName) !!};
  const LOGGED_IN_USER_ID = {{ auth()->id() ?? 0 }};
  const LOGGED_IN_USER_NAME = {!! json_encode(auth()->user()?->name ?? '') !!};

  let itiMobile = null;
  function initTelInput(){
    try {
      const input = document.querySelector('#mobile');
      if(!input || !window.intlTelInput) return;
      if(itiMobile) return;
      itiMobile = window.intlTelInput(input, {
        initialCountry: 'us',
        separateDialCode: true,
        nationalMode: false
      });
    } catch(e) {}
  }

  // helpers
  const clearErrors = () => $form.find('[data-error]').text('');
  const setErrors = (errors) => {
    if(!errors) return;
    Object.keys(errors).forEach(k => {
      const message = Array.isArray(errors[k]) ? errors[k][0] : errors[k];
      $form.find(`[data-error="${k}"]`).text(message || '');
    })
  }
  const resetForm = () => {
    clearErrors();
    $form[0].reset();
    $id.val(0);
    $statusEmployee.prop('checked', true);
    $service.val([]).trigger('change');
    $isManagerCheckbox.prop('checked', false);
    $profile.val('');
    $profilePreview.attr('src', '{{ default_user_avatar() }}');
    $removeProfileBtn.addClass('d-none');
    $imageValidation.addClass('d-none').text('');
    $customFieldsJson.val('{}');
    $existingProfileImage.val('');
    $removeProfileImageFlag.val('0');
    $passwordRow.show();
    $('#employee-form-title-create').show();
    $('#employee-form-title-edit').hide();
    if(itiMobile){ try { itiMobile.setNumber(''); } catch(e) {} }
  }

  // Live validation: clear errors on input/change
  $form.on('input', '#first_name, #last_name, #mobile', function(){
    const key = this.name;
    if(String($(this).val()).trim().length){ $form.find(`[data-error="${key}"]`).text(''); }
  })
  $form.on('input', '#email', function(){ $form.find('[data-error="email"]').text(''); })
  $form.on('input', '#password, #confirm_password', function(){
    const key = this.name;
    if(String($(this).val()).trim().length){ $form.find(`[data-error="${key}"]`).text(''); }
  })
  $form.on('change', '#branch_id, #commission_id', function(){
    const key = this.id;
    $form.find(`[data-error="${key}"]`).text('');
  })
  $form.on('change', '#service_id', function(){
    $form.find('[data-error="service_id"]').text('');
  })

  // Simple client-side validation
  const EMAIL_REGX = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
  const ABOUT_SELF_MAX = 191;

  function updateAboutSelfCounter(){
    const $about = $('#about_self');
    if(!$about.length) return;
    let val = String($about.val() || '');
    if(val.length > ABOUT_SELF_MAX){
      val = val.slice(0, ABOUT_SELF_MAX);
      $about.val(val);
    }
    $('#about_self_counter').text(`${val.length}/${ABOUT_SELF_MAX}`);
  }

  // About self: hard stop at max + live counter
  $form.on('input', '#about_self', function(){
    updateAboutSelfCounter();
  });

  function validateForm(){
    let ok = true;
    function setErr(field, msg){ $form.find(`[data-error="${field}"]`).text(msg); ok = false; }
    const first = $('#first_name').val().trim(); if(!first) setErr('first_name', 'First name is required.');
    const last = $('#last_name').val().trim(); if(!last) setErr('last_name', 'Last name is required.');
    const email = $('#email').val().trim();
    if(!email){ setErr('email', 'Email is required.'); }
    else if(!EMAIL_REGX.test(email)){ setErr('email', 'Please enter a valid email address.'); }
    const mobileVal = ($('#mobile').val() || '').trim();
    if(!mobileVal){ setErr('mobile', 'Phone number is required.'); }
    else if(itiMobile && !itiMobile.isValidNumber()){ setErr('mobile', 'Please enter a valid phone number.'); }
    const branchVal = $('#branch_id').val(); if(!branchVal){ setErr('branch_id', 'Branch is required.'); }
    const services = $('#service_id').val() || []; if(services.length === 0){ setErr('service_id', 'Please select at least one service.'); }
    const commissionVal = $('#commission_id').val(); if(!commissionVal){ setErr('commission_id', 'Commission is required.'); }

    const aboutSelf = ($('#about_self').val() || '').toString();
    if(aboutSelf.length > ABOUT_SELF_MAX){
      setErr('about_self', `About self must be at most ${ABOUT_SELF_MAX} characters.`);
    }
    
    // Password validation only in create mode
    const isEdit = parseInt($id.val()) > 0;
    if(!isEdit){
      const password = $('#password').val().trim();
      if(!password){ setErr('password', 'Password is required.'); }
      else if(password.length < 8){ setErr('password', 'Password must be at least 8 characters long.'); }
      const confirmPassword = $('#confirm_password').val().trim();
      if(!confirmPassword){ setErr('confirm_password', 'Confirm password is required.'); }
      else if(password !== confirmPassword){ setErr('confirm_password', 'Passwords do not match.'); }
    }
    
    return ok;
  }

  // populate selects
  function fetchBranches(selectedId = null) {
    if(IS_MANAGER && MANAGER_BRANCH_ID){
      return new Promise(resolve => {
        const label = MANAGER_BRANCH_NAME || '{{ __('messages.select_branch') }}';
        $branch.empty();
        $branch.append(`<option value="${MANAGER_BRANCH_ID}">${label}</option>`);
        $branch.val(String(MANAGER_BRANCH_ID));
        resolve([{ id: MANAGER_BRANCH_ID, name: label }]);
      });
    }
    return $.get(`{{ url('app/employees/index_list') }}`).then(list => {
      $branch.empty();
      $branch.append(`<option value="">{{ __('messages.select') }}</option>`);
      list.forEach(it => $branch.append(`<option value="${it.id}">${it.name}</option>`))
      if(selectedId) $branch.val(String(selectedId));
    })
  }

  function fetchServices(branchId, selected = []) {
    return $.get(`{{ url('app/service/index_list') }}`).then(resp => {
      const options = Array.isArray(resp) ? resp : (resp?.data || []);
      $service.empty();
      options.forEach(it => $service.append(`<option value="${it.id}">${it.name ?? it.text ?? it.label}</option>`))
      $service.val((selected || []).map(String)).trigger('change');
    })
  }
  function fetchCommissions(selectedId = null) {
    return $.get(`{{ url('app/commissions/index_list') }}`).then(list => {
      const options = Array.isArray(list) ? list : (list?.data || []);
      $commission.empty();
      $commission.append(`<option value="">{{ __('messages.select') }}</option>`);
      options.forEach(it => $commission.append(`<option value="${it.id}">${it.name ?? it.text ?? it.label}</option>`))
      if(selectedId) $commission.val(String(selectedId));
    })
  }

  // image preview + size check (2MB)
  $profile.on('change', function(e){
    const file = this.files && this.files[0];
    if(!file) return;
    const max = 2 * 1024 * 1024;
    if(file.size > max){
      $imageValidation.removeClass('d-none').text(`{{ __('messages.file_too_large') ?? 'File size exceeds' }} 2 MB.`);
      $profile.val('');
      return;
    }
    $imageValidation.addClass('d-none').text('');
    
    // Reset remove flag when new image is selected
    $removeProfileImageFlag.val('0');
    
    const reader = new FileReader();
    reader.onload = (ev) => {
      $profilePreview.attr('src', ev.target.result);
      $removeProfileBtn.removeClass('d-none');
    }
    reader.readAsDataURL(file);
  })
  
  $removeProfileBtn.on('click', function(){
    // Clear the file input
    $profile.val('');
    
    // Reset preview to default image
    $profilePreview.attr('src', '{{ default_user_avatar() }}');
    
    // Hide remove button
    $(this).addClass('d-none');
    
    // Mark image for removal if editing existing employee with image
    const currentEmployeeId = parseInt($id.val() || 0);
    if(currentEmployeeId > 0 && $existingProfileImage.val()){
      $removeProfileImageFlag.val('1');
    }
    
    // Clear existing image reference
    $existingProfileImage.val('');
    
    // Clear validation errors
    $imageValidation.addClass('d-none').text('');
    $form.find('[data-error="profile_image"]').text('');
  })

  // collect custom fields into hidden JSON
  function collectCustomFields(){
    const data = {};
    $form.find('[data-custom-field]').each(function(){
      const key = $(this).data('custom-field');
      data[key] = $(this).val();
    })
    $customFieldsJson.val(JSON.stringify(data));
  }

  // open create/edit via event from generic handler
  document.addEventListener('crud_change_id', async (ev) => {
    const formId = (ev.detail && ev.detail.form_id) ? parseInt(ev.detail.form_id) : 0;
    resetForm();
    initTelInput();
    await fetchBranches({{ isset($selected_branch_id) ? (int)$selected_branch_id : 0 }});
    await fetchCommissions();
    const selectedBranch = $('#branch_id').val();

    await fetchServices(selectedBranch);
    if($.fn.select2){
      $('#branch_id').select2({ dropdownParent: $('#form-offcanvas') });
      $('#service_id').select2({ dropdownParent: $('#form-offcanvas') });
      $('#commission_id').select2({ dropdownParent: $('#form-offcanvas') });

    }

    if(formId && formId > 0){
      // edit mode
      $('#employee-form-title-create').hide();
      $('#employee-form-title-edit').show();
      $passwordRow.hide();
      $id.val(formId);
      $.get(`{{ url('app/employees') }}/${formId}/edit`).then(resp => {
        if(!resp?.status) return;
        const d = resp.data || {};

        // fill
        $('#first_name').val(d.first_name || '');
        $('#last_name').val(d.last_name || '');
        $('#email').val(d.email || '');
        if(itiMobile && d.mobile){
          try { itiMobile.setNumber(d.mobile); } catch(e){ $('#mobile').val(d.mobile || ''); }
        } else {
          $('#mobile').val(d.mobile || '');
        }
        const gender = d.gender || 'male';
        $(`input[name="gender"][value="${gender}"]`).prop('checked', true);
        $('#show_in_calender').prop('checked', (d.show_in_calender ?? 1) == 1);
        $('#is_manager').prop('checked', (d.is_manager ?? 0) == 1);

        const statusToSet = (d.status ?? 1) == 1;
        $('#status_employee').prop('checked', statusToSet);
        $('#about_self').val(d.about_self || '');
        updateAboutSelfCounter();
        $('#expert').val(d.expert || '');
        $('#facebook_link').val(d.facebook_link || '');
        $('#instagram_link').val(d.instagram_link || '');
        $('#twitter_link').val(d.twitter_link || '');
        $('#dribbble_link').val(d.dribbble_link || '');
        
        // Handle profile image
        if(d.profile_image){ 
          $profilePreview.attr('src', d.profile_image); 
          $removeProfileBtn.removeClass('d-none');
          $existingProfileImage.val(d.profile_image);
        } else {
          $profilePreview.attr('src', '{{ default_user_avatar() }}');
          $removeProfileBtn.addClass('d-none');
          $existingProfileImage.val('');
        }
        
        // selects
        const branchId = (IS_MANAGER && MANAGER_BRANCH_ID) ? MANAGER_BRANCH_ID : (d.branch_id || '');
        $branch.val(String(branchId));
        if($.fn.select2 && $branch.data('select2')){
          $branch.trigger('change.select2');
        }
        
        // Instant warning if manager has no branch
        if (!branchId && (d.is_manager ?? 0) == 1) {
            $form.find('[data-error="branch_id"]').text('This branch is already assigned to another manager. Please select a different branch to continue.');
        }
        fetchServices(branchId, (d.service_id || [])).then(() => {
          if($.fn.select2){ $('#service_id').val((d.service_id || []).map(String)).trigger('change'); }
        });

        fetchCommissions(d.commission_id || '').then(() => {
          if($.fn.select2 && $commission.data('select2')){ $commission.trigger('change.select2'); }
        });
        // custom fields json
        const cf = d.custom_field_data || {};
        Object.keys(cf).forEach(k => {
          $form.find(`[data-custom-field="${k}"]`).val(cf[k]);
        })
        collectCustomFields();
      })
    }
  })

  // when opened via New button (no crud_change_id fired)
  offcanvasEl.addEventListener('show.bs.offcanvas', async () => {
    if(parseInt($id.val() || 0) === 0){
      resetForm();
      initTelInput();
      updateAboutSelfCounter();
      await fetchBranches({{ isset($selected_branch_id) ? (int)$selected_branch_id : 0 }});
      await fetchCommissions();
      await fetchServices($('#branch_id').val());
      // init select2 after options in place
      if($.fn.select2){
        $('#branch_id').select2({ dropdownParent: $('#form-offcanvas') });
        $('#service_id').select2({ dropdownParent: $('#form-offcanvas') });
        $('#commission_id').select2({ dropdownParent: $('#form-offcanvas') });

      }
    }
  })

  // on manager checkbox change
  $isManagerCheckbox.on('change', function(){
      if($(this).is(':checked')){
          $branch.trigger('change');
      } else {
          $form.find('[data-error="branch_id"]').text('');
      }
  });

  // on branch change, reload services and managers
  $branch.on('change', function(){
    const branchId = $(this).val();
    const currentEmployeeId = $id.val(); // Get ID from hidden input if editing

    // Availability Check
    if(branchId && $isManagerCheckbox.is(':checked')){
        $.post("{{ route('backend.employees.check_branch_availability') }}", {
            _token: "{{ csrf_token() }}",
            branch_id: branchId,
            employee_id: currentEmployeeId
        }).done(function(res){
            if(!res.status){
                $form.find('[data-error="branch_id"]').text(res.message);
            } else {
                $form.find('[data-error="branch_id"]').text('');
            }
        });
    } else if (!branchId && $isManagerCheckbox.is(':checked')) {
        $form.find('[data-error="branch_id"]').text('This branch is already assigned to another manager. Please select a different branch to continue.');
    } else {
        $form.find('[data-error="branch_id"]').text('');
    }

    fetchServices(branchId).then(() => {
      if($.fn.select2){ $('#service_id').trigger('change.select2'); }
    })

  })



  const $submitBtn = $form.find('button[type="submit"]');

  // submit
  $form.on('submit', function(e){
    e.preventDefault();
    clearErrors();
    collectCustomFields();
    if(!validateForm()){ return; }
    const formData = new FormData($form[0]);
    if(itiMobile){
      try { formData.set('mobile', itiMobile.getNumber()); } catch(e) {}
    }
    // checkbox normalizations
    const statusValue = $('#status_employee').is(':checked') ? 1 : 0;
    formData.set('status', statusValue);
    formData.set('show_in_calender', $('#show_in_calender').is(':checked') ? 1 : 0);
    formData.set('is_manager', $('#is_manager').is(':checked') ? 1 : 0);
    // multi services to CSV like current API expects
    const serviceVals = ($service.val() || []);
    formData.delete('service_id[]');
    formData.set('service_id', serviceVals.join(','));
    


    const isEdit = parseInt($id.val()) > 0;
    // Remove password fields in edit mode since they're not needed
    if(isEdit){
      formData.delete('password');
      formData.delete('confirm_password');
      formData.append('_method', 'PUT');
    }
    const url = isEdit ? `{{ url('app/employees') }}/${$id.val()}` : `{{ url('app/employees') }}`;
    const method = isEdit ? 'POST' : 'POST';

    $submitBtn.prop('disabled', true);
    const originalContent = $submitBtn.html();
    $submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + originalContent);

    $.ajax({
      url, method, data: formData, processData: false, contentType: false
    }).done(function(res){
      if(res && res.status){
        window.successSnackbar && window.successSnackbar(res.message || '{{ __('messages.saved_successfully') }}');
        if(window.renderedDataTable){ window.renderedDataTable.ajax.reload(null, false); }
        instance.hide();
      } else {
        const fieldErrors = (res && res.all_message) || {};
        setErrors(fieldErrors);
        const hasFieldErrors = fieldErrors && Object.keys(fieldErrors).length > 0;
        if(!hasFieldErrors){
          window.errorSnackbar && window.errorSnackbar(res && res.message ? res.message : '{{ __('messages.validation_error') }}');
        }
      }
    }).fail(function(xhr){
      const json = (xhr && xhr.responseJSON) || {};
      const fieldErrors = (json && (json.errors || json.all_message)) || {};
      setErrors(fieldErrors);
      const hasFieldErrors = fieldErrors && Object.keys(fieldErrors).length > 0;
      if(!hasFieldErrors){
        window.errorSnackbar && window.errorSnackbar((json && json.message) || '{{ __('messages.server_error') }}');
      }
    }).always(function(){
      $submitBtn.prop('disabled', false);
      $submitBtn.html(originalContent);
    })
  })
})(window.$)
</script>
@endpush


