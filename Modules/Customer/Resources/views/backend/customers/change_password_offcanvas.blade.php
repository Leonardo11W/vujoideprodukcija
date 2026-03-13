<form id="customer-change-password-form">
  @csrf
  <div class="offcanvas offcanvas-end" id="Employee_change_password" aria-labelledby="form-offcanvasLabel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title">{{ __('messages.change_password') }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
      <div class="row">
        <div class="col-12">
          <div class="form-group">
            <label class="form-label">{{ __('messages.old_password') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="old_password" id="cp_old_password" placeholder="{{ __('messages.old_password') ?? 'Old password' }}">
              <span class="input-group-text toggle-password" data-target="cp_old_password"><i class="fa-solid fa-eye-slash"></i></span>
            </div>
            <span class="text-danger" data-error="old_password"></span>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('employee.lbl_password') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="password" id="cp_password" placeholder="{{ __('customer.password') }}">
              <span class="input-group-text toggle-password" data-target="cp_password"><i class="fa-solid fa-eye-slash"></i></span>
            </div>
            <span class="text-danger" data-error="password"></span>
          </div>
          <div class="form-group">
            <label class="form-label">{{ __('employee.lbl_confirm_password') }} <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" class="form-control" name="confirm_password" id="cp_confirm_password" placeholder="{{ __('customer.confirm_password') }}">
              <span class="input-group-text toggle-password" data-target="cp_confirm_password"><i class="fa-solid fa-eye-slash"></i></span>
            </div>
            <span class="text-danger" data-error="confirm_password"></span>
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
<script type="text/javascript">
(function($){
  const offcanvasEl = document.getElementById('Employee_change_password');
  if(!offcanvasEl) return;
  const instance = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl);
  const $form = $('#customer-change-password-form');
  let currentUserId = 0;
  document.addEventListener('employee_assign', (ev) => {
    currentUserId = (ev.detail && ev.detail.form_id) ? parseInt(ev.detail.form_id) : 0;
  })
  // Live password validations
  const $old = $('#cp_old_password');
  const $password = $('#cp_password');
  const $confirm = $('#cp_confirm_password');
  const $errorOld = $('[data-error="old_password"]');
  const $errorPassword = $('[data-error="password"]');
  const $errorConfirm = $('[data-error="confirm_password"]');
  const mismatchMsg = "{{ __('messages.password_mismatch') }}";
  const newSameAsOldMsg = "{{ __('messages.new_password_mismatch') }}";

  function validatePasswords() {
    const o = ($old.val() || '').trim();
    const p = ($password.val() || '').trim();
    const c = ($confirm.val() || '').trim();
    // Clear previous state
    $errorOld.text('');
    $errorPassword.text('');
    $errorConfirm.text('');
    $old.removeClass('is-invalid');
    $password.removeClass('is-invalid');
    $confirm.removeClass('is-invalid');

    // New must be different than old (when both provided)
    if (o !== '' && p !== '' && o === p) {
      $errorPassword.text(newSameAsOldMsg);
      $password.addClass('is-invalid');
      return { ok: false, msg: newSameAsOldMsg };
    }

    if (p !== '' && c !== '' && p !== c) {
      $errorConfirm.text(mismatchMsg);
      $confirm.addClass('is-invalid');
      return { ok: false, msg: mismatchMsg };
    }
    return { ok: true };
  }

  $old.on('input', function() {
    validatePasswords();
    $old.removeClass('is-invalid');
    $errorOld.text('');
  });
  $password.on('input', function() {
    validatePasswords();
    $password.removeClass('is-invalid');
    $errorPassword.text('');
  });
  $confirm.on('input', function() {
    validatePasswords();
    $confirm.removeClass('is-invalid');
    $errorConfirm.text('');
  });
  // Toggle password visibility (eye icon)
  $(document).on('click', '.toggle-password', function(){
    const targetId = $(this).data('target');
    const $input = $('#'+targetId);
    if(!$input.length) return;
    const $icon = $(this).find('i');
    const isHidden = $input.attr('type') === 'password';
    $input.attr('type', isHidden ? 'text' : 'password');
    $icon.toggleClass('fa-eye-slash fa-eye');
  })

  // Reset visibility state on offcanvas close
  offcanvasEl.addEventListener('hidden.bs.offcanvas', function(){
    ['cp_password','cp_confirm_password'].forEach(function(id){
      const $input = $('#'+id);
      const $icon = $('.toggle-password[data-target="'+id+'"]').find('i');
      if($input.length){ $input.attr('type','password'); }
      if($icon.length){ $icon.removeClass('fa-eye').addClass('fa-eye-slash'); }
    })
  })
  $form.on('submit', function(e){
    e.preventDefault();
    const result = validatePasswords();
    if (!result.ok) {
      return;
    }
    const data = $form.serializeArray();
    data.push({name: 'user_id', value: currentUserId});
    $.post(`{{ url('app/customers/change-password') }}`, $.param(data)).done(function(res){
      if(res?.status){
        window.successSnackbar && window.successSnackbar(res.message);
        if(window.renderedDataTable){ window.renderedDataTable.ajax.reload(null, false); }
        instance.hide();
        $form[0].reset();
      } else {
        if (res?.errors) {
          Object.keys(res.errors).forEach(k => {
            const message = Array.isArray(res.errors[k]) ? res.errors[k][0] : res.errors[k];
            $form.find(`[data-error="${k}"]`).text(message || '');
            $form.find(`[name="${k}"]`).addClass('is-invalid');
          })
        }
      }
    }).fail(function(xhr){
      const json = xhr.responseJSON || {};
      if (json?.errors) {
        Object.keys(json.errors).forEach(k => {
          const message = Array.isArray(json.errors[k]) ? json.errors[k][0] : json.errors[k];
          $form.find(`[data-error="${k}"]`).text(message || '');
          $form.find(`[name="${k}"]`).addClass('is-invalid');
        })
      }
    })
  })
})(window.$)
</script>
@endpush


