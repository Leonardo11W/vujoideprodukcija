<form id="customer-form" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="id" id="customer_id" value="0">
  <input type="hidden" name="custom_fields_data" id="customer_custom_fields_data" value="{}">
  <input type="hidden" name="remove_profile_image" id="customer_remove_profile_image_flag" value="0">

  <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="form-offcanvasLabel">
        <span id="customer-form-title-create">{{ __('messages.new') }} {{ __('customer.singular_title_new') }}</span>
        <span id="customer-form-title-edit" style="display:none;">{{ __('messages.edit') }} {{ __('customer.singular_title_edit') }}</span>
      </h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
      <div class="row">
        <div class="col-md-12 row">
          <div class="col-md-12 text-center">
            <img src="{{ default_user_avatar() }}" class="img-fluid avatar avatar-120 avatar-rounded mb-2" alt="profile-image" id="customer_profile_preview" />
            <div class="text-danger mb-2 d-none" id="customer_image_validation"></div>
            <div class="d-flex align-items-center justify-content-center gap-2">
              <input type="file" class="form-control d-none" id="customer_profile_image" name="profile_image" accept=".jpeg,.jpg,.png,.gif" />
              <label class="btn btn-info mb-3" for="customer_profile_image">{{ __('messages.upload') }}</label>
              <button type="button" class="btn btn-danger mb-3 d-none" id="customer_remove_profile_image">{{ __('messages.remove') }}</button>
            </div>
            <span class="text-danger" data-error="profile_image"></span>
          </div>

          <div class="col-md-12 form-group">
            <label class="form-label">{{ __('customer.lbl_first_name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="first_name" id="first_name" placeholder="{{ __('customer.first_name') }}" autocomplete="off">
            <span class="text-danger" data-error="first_name"></span>
          </div>
          <div class="col-md-12 form-group">
            <label class="form-label">{{ __('customer.lbl_last_name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="last_name" id="last_name" placeholder="{{ __('customer.last_name') }}" autocomplete="off">
            <span class="text-danger" data-error="last_name"></span>
          </div>

          <div class="col-md-12 form-group">
            <label class="form-label">{{ __('customer.lbl_Email') }} <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" id="email" placeholder="{{ __('customer.email_address') }}" autocomplete="off">
            <span class="text-danger" data-error="email"></span>
          </div>

          <div class="form-group col-md-12">
            <label class="form-label">{{ __('customer.lbl_phone_number') }} <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" name="mobile" id="mobile" placeholder="+1 555 123 4567" autocomplete="off">
            <span class="text-danger" data-error="mobile"></span>
          </div>

          <div class="row" id="password-row">
            <div class="col-md-12 form-group">
              <label class="form-label">{{ __('employee.lbl_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="password" id="password" placeholder="{{ __('customer.password') }}" autocomplete="new-password">
              <span class="text-danger" data-error="password"></span>
            </div>
            <div class="col-md-12 form-group">
              <label class="form-label">{{ __('employee.lbl_confirm_password') }} <span class="text-danger">*</span></label>
              <input type="password" class="form-control" name="confirm_password" id="confirm_password" placeholder="{{ __('customer.confirm_password') }}" autocomplete="new-password">
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
  const $form = $('#customer-form');
  const $id = $('#customer_id');
  const $profile = $('#customer_profile_image');
  const $profilePreview = $('#customer_profile_preview');
  const $removeProfileBtn = $('#customer_remove_profile_image');
  const $imageValidation = $('#customer_image_validation');
  const $passwordRow = $('#password-row');
  const $customFieldsJson = $('#customer_custom_fields_data');
  const $removeProfileFlag = $('#customer_remove_profile_image_flag');
  
  // Initialize telinput and store instance
  let telInputInstance = null;
  const mobileInput = document.querySelector("#mobile");
  
  function initTelInput() {
    if(mobileInput && window.intlTelInput && !telInputInstance) {
      telInputInstance = window.intlTelInput(mobileInput, {
        preferredCountries: ["in", "us", "ae", "gb"],
        separateDialCode: true,
        nationalMode: false,
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js"
      });
    }
  }
  
  // Try to initialize immediately, or wait for library to load
  if(window.intlTelInput) {
    initTelInput();
  } else {
    // Wait for library to load
    const checkInterval = setInterval(function() {
      if(window.intlTelInput) {
        initTelInput();
        clearInterval(checkInterval);
      }
    }, 100);
    // Stop checking after 5 seconds
    setTimeout(function() { clearInterval(checkInterval); }, 5000);
  }
  
  // When opening in edit mode, set value and country from DB/mobile
  document.addEventListener('crud_change_id', function(ev){
    setTimeout(function() {
      if(!telInputInstance) initTelInput();
      var val = mobileInput ? mobileInput.value.trim() : '';
      if(val && telInputInstance) {
        telInputInstance.setNumber(val);
      }
    }, 300);
  });

  const clearErrors = () => $form.find('[data-error]').text('');
  const setErrors = (errors) => {
    if(!errors) return;
    Object.keys(errors).forEach(k => {
      const message = Array.isArray(errors[k]) ? errors[k][0] : errors[k];
      $form.find(`[data-error="${k}"]`).text(message || '');
    })
  }
  // Live validation: clear error when user types
  function attachLiveValidation(){
    // Simple required fields
    $form.on('input', '#first_name, #last_name, #mobile', function(){
      const name = this.name;
      if(String($(this).val()).trim().length){
        $form.find(`[data-error="${name}"]`).text('');
      }
    });
    // Email: clear on input, basic regex on blur
    $form.on('input', '#email', function(){
      $form.find('[data-error="email"]').text('');
    });
    $('#email').on('blur', function(){
      const email = $(this).val();
      if(email && !EMAIL_REGX.test(email)){
        $form.find('[data-error="email"]').text('Please enter a valid email address.');
      }
    });
    // Password + confirm: clear on input, show match/min helpers
    $form.on('input', '#password', function(){
      const val = $(this).val();
      if(val){ $form.find('[data-error="password"]').text(''); }
      // If confirm present, re-check match
      const confirm = $('#confirm_password').val();
      if(confirm){
        $form.find('[data-error="confirm_password"]').text(val === confirm ? '' : 'Passwords do not match.');
      }
    });
    $form.on('input', '#confirm_password', function(){
      const confirm = $(this).val();
      if(confirm){ $form.find('[data-error="confirm_password"]').text(''); }
      const pass = $('#password').val();
      if(pass && confirm && pass !== confirm){
        $form.find('[data-error="confirm_password"]').text('Passwords do not match.');
      }
    });
    // Custom fields
    $form.on('input change', '[data-custom-field]', function(){
      const key = $(this).data('custom-field');
      if(String($(this).val()).trim().length){
        $form.find(`[data-error="custom_fields_data.${key}"]`).text('');
      }
    });
  }
  function validateForm(){
    let ok = true;
    const firstName = $('#first_name').val().trim();
    const lastName = $('#last_name').val().trim();
    const emailVal = $('#email').val().trim();
    const mobileVal = $('#mobile').val().trim();
    const isEdit = parseInt($id.val() || 0) > 0;
    if(!firstName){ $form.find('[data-error="first_name"]').text('First name is required.'); ok = false; }
    if(!lastName){ $form.find('[data-error="last_name"]').text('Last name is required.'); ok = false; }
    if(!emailVal){
      $form.find('[data-error="email"]').text('Email is required.'); ok = false;
    } else if(!EMAIL_REGX.test(emailVal)){
      $form.find('[data-error="email"]').text('Please enter a valid email address.'); ok = false;
    }
    if(!mobileVal){ $form.find('[data-error="mobile"]').text('Phone number is required.'); ok = false; }
    if(!isEdit){
      const pass = $('#password').val();
      const confirm = $('#confirm_password').val();
      if(!pass){
        $form.find('[data-error="password"]').text('Password is required.'); ok = false;
      } else if(String(pass).length < 8){
        $form.find('[data-error="password"]').text('Password must be at least 8 characters.'); ok = false;
      }
      if(!confirm){
        $form.find('[data-error="confirm_password"]').text('Confirm password is required.'); ok = false;
      } else if(pass !== confirm){
        $form.find('[data-error="confirm_password"]').text('Passwords do not match.'); ok = false;
      }
    }
    return ok;
  }
  const resetForm = () => {
    clearErrors();
    $form[0].reset();
    $id.val(0);
    $profile.val('');
    $profilePreview.attr('src', '{{ default_user_avatar() }}');
    $removeProfileBtn.addClass('d-none');
    $imageValidation.addClass('d-none').text('');
    $customFieldsJson.val('{}');
    $removeProfileFlag.val('0');
    $passwordRow.show();
    $('#customer-form-title-create').show();
    $('#customer-form-title-edit').hide();
  }

  // Image preview + 2MB validation
  $profile.on('change', function(){
    const file = this.files && this.files[0];
    if(!file) return;
    const max = 2 * 1024 * 1024;
    if(file.size > max){
      $imageValidation.removeClass('d-none').text(`{{ __('messages.file_too_large') ?? 'File size exceeds' }} 2 MB.`);
      $profile.val('');
      return;
    }
    $removeProfileFlag.val('0');
    $imageValidation.addClass('d-none').text('');
    const reader = new FileReader();
    reader.onload = (ev) => {
      $profilePreview.attr('src', ev.target.result);
      $removeProfileBtn.removeClass('d-none');
    }
    reader.readAsDataURL(file);
  })
  $removeProfileBtn.on('click', function(){
    $profile.val('');
    $profilePreview.attr('src', '{{ default_user_avatar() }}');
    $(this).addClass('d-none');
    $removeProfileFlag.val('1');
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

  // Email unique check on blur
  const EMAIL_REGX = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
  $('#email').on('blur', function(){
    const email = $(this).val();
    if(!EMAIL_REGX.test(email)) return;
    $.post(`{{ url('app/customers/unique_email') }}`, { email, user_id: $id.val(), _token: '{{ csrf_token() }}' })
      .done(function(res){
        if(res && res.isUnique === false){
          $form.find('[data-error="email"]').text('email must be unique');
        }
      })
  })

  // Open create/edit
  document.addEventListener('crud_change_id', async (ev) => {
    const formId = (ev.detail && ev.detail.form_id) ? parseInt(ev.detail.form_id) : 0;
    resetForm();
    if(formId && formId > 0){
      // edit mode
      $('#customer-form-title-create').hide();
      $('#customer-form-title-edit').show();
      $passwordRow.hide();
      $id.val(formId);
      $.get(`{{ url('app/customers') }}/${formId}/edit`).then(resp => {
        if(!resp?.status) return;
        const d = resp.data || {};
        $('#first_name').val(d.first_name || '');
        $('#last_name').val(d.last_name || '');
        $('#email').val(d.email || '');
        $('#mobile').val(d.mobile || '');
        // Update telinput if mobile value exists
        if(d.mobile && telInputInstance && mobileInput) {
          setTimeout(function() {
            telInputInstance.setNumber(d.mobile);
          }, 100);
        }
        const gender = d.gender || 'male';
        $(`input[name="gender"][value="${gender}"]`).prop('checked', true);
        if(d.profile_image){ $profilePreview.attr('src', d.profile_image); $removeProfileBtn.removeClass('d-none'); }
        const cf = d.custom_field_data || {};
        Object.keys(cf).forEach(k => { $form.find(`[data-custom-field="${k}"]`).val(cf[k]); })
        collectCustomFields();
      })
    }
  })

  // When opened for create from button
  offcanvasEl.addEventListener('show.bs.offcanvas', async () => {
    if(parseInt($id.val() || 0) === 0){ resetForm(); }
  })

  // Submit
  $form.on('submit', function(e){
    e.preventDefault();
    clearErrors();
    collectCustomFields();
    // Client-side validation (inline, no snackbar)
    if(!validateForm()){
      return;
    }
    // Set telinput value before collecting form data
    if(mobileInput && mobileInput.value.trim()) {
      // Initialize telinput if not already initialized
      if(!telInputInstance) {
        initTelInput();
      }
      if(telInputInstance) {
        const fullNumber = telInputInstance.getNumber(); // Returns E.164 format like +918899665600
        if(fullNumber) {
          // Get country data to extract dial code
          const countryData = telInputInstance.getSelectedCountryData();
          const dialCode = countryData ? countryData.dialCode : '';
          
          if(dialCode && fullNumber.startsWith('+' + dialCode)) {
            // Extract national number by removing +dialCode from the beginning
            const nationalNumber = fullNumber.substring(('+' + dialCode).length);
            // Format as: +[dialCode] [nationalNumber]
            mobileInput.value = '+' + dialCode + ' ' + nationalNumber;
          } else {
            // Fallback: use regex to split country code (1-4 digits) from number
            const match = fullNumber.match(/^(\+\d{1,4})(.+)$/);
            if(match) {
              mobileInput.value = match[1] + ' ' + match[2];
            } else {
              mobileInput.value = fullNumber;
            }
          }
        }
      }
    }
    const formData = new FormData($form[0]);
    const isEdit = parseInt($id.val()) > 0;
    // Prevent clearing profile image on edit if no new file selected
    if(isEdit){
      const hasNewImage = ($profile[0] && $profile[0].files && $profile[0].files.length > 0);
      if(!hasNewImage){ formData.delete('profile_image'); }
    }
    const url = isEdit ? `{{ url('app/customers') }}/${$id.val()}` : `{{ url('app/customers') }}`;
    const method = 'POST';
    if(isEdit){ formData.append('_method', 'PUT'); }
    $.ajax({ url, method, data: formData, processData: false, contentType: false })
      .done(function(res){
        if(res && res.status){
          window.successSnackbar && window.successSnackbar(res.message || 'Saved successfully.');
          if(window.renderedDataTable){ window.renderedDataTable.ajax.reload(null, false); }
          instance.hide();
        } else {
          const fieldErrors = (res && res.all_message) || {};
          setErrors(fieldErrors);
          const hasFieldErrors = fieldErrors && Object.keys(fieldErrors).length > 0;
          if(!hasFieldErrors){
            window.errorSnackbar && window.errorSnackbar(res.message || 'Validation error.');
          }
        }
      })
      .fail(function(xhr){
        const json = (xhr && xhr.responseJSON) || {};
        const fieldErrors = (json && (json.errors || json.all_message)) || {};
        setErrors(fieldErrors);
        const hasFieldErrors = fieldErrors && Object.keys(fieldErrors).length > 0;
        if(!hasFieldErrors){
          window.errorSnackbar && window.errorSnackbar(json.message || 'Server error.');
        }
      })
  })

  // init
  attachLiveValidation();
})(window.$)


</script>
@endpush


