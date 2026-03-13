<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content p-0 overflow-hidden" style="border-radius: 1.25rem; background: #fff;">
      <div class="containe h-100">
        <div class="row align-items-center justify-content-center h-100">
          <div class="col-xl-12 col-lg-12 col-md-12 my-0">
            <div class="py-5 px-3">
              <div class="register-bg register-background-padding">
                <div class="text-center mb-5">
                  <a href="#">
                    <img src="{{asset('img/logo/logo.png')}}" class="img-fluid auth-logo" alt="logo">
                  </a>
                  <h5 class="mb-1 register-title">Welcome Back!</h5>
                  <p class="font-size-14 mb-5">You Have Been Missed For Long Time</p>
                </div>
                <form method="POST" action="{{ route('login') }}" class="needs-validation" novalidate autocomplete="off">
                  {{-- This route now points to AuthController@loginUser for frontend users --}}
                  @csrf
                  <input type="hidden" name="intended" id="modal_intended_url" value="{{ url()->current() }}">
                  <div id="modal_login_error_message" class="alert alert-danger d-none mb-3" style="font-size: 1rem; font-weight: 500;"></div>
                  <div class="row gy-4">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="modal_login_email" class="form-label fw-medium">Email<span class="text-danger">*</span></label>
                        <div class="input-group custom-input-group">
                          <input type="email" name="email" value="john@gmail.com" id="modal_login_email" class="form-control" placeholder="demo@gmail.com" required />
                          <span class="input-group-text"><i class="ph ph-envelope-simple"></i></span>
                        </div>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                      </div>
                    </div>
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="modal_login_password" class="form-label fw-medium">Password<span class="text-danger">*</span></label>
                        <div class="input-group custom-input-group">
                          <input type="password" name="password" value="12345678" id="modal_login_password" class="form-control" placeholder="eg-#123@Abc\" required />
                          <span class="input-group-text" id="modal_togglePassword"><i class="ph ph-eye-slash"></i></span>
                        </div>
                        <div class="invalid-feedback">Please enter your password.</div>
                      </div>
                    </div>
                  </div>
                  <div class="d-flex justify-content-between flex-wrap column-gap-3 row-gap-1 mt-2">
                    <div>
                      <label for="modal_login_remember" class="d-inline-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input m-0" id="modal_login_remember" name="remember">
                        <span class="font-size-14">Remember me</span>
                      </label>
                    </div>
                    <a href="{{ route('password.request') }}" class="fw-semibold font-size-14 fst-italic">Forgot Password?</a>
                  </div>
                  <div class="d-flex justify-content-between gap-3 mt-5 auth-btn">
                    <button type="submit" id="modal-login-button" class="btn btn-secondary flex-grow-1">Sign In</button>
                    <a href="{{ route('auth.google') }}" class="btn px-3 bg-gray-800" onclick="handleModalGoogleLogin(event)">
                      <img src="{{asset('img/frontend/google-icon.png')}}" alt="icon" class="img-fluid">
                    </a>
                  </div>
                  <div class="d-flex justify-content-center flex-wrap gap-1 mt-3">
                    <span class="font-size-14 text-body">Not a member?</span>
                    <a href="{{ route('signup') }}" class="text-primary font-size-14 fw-medium text-decoration-underline">Sign Up</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function() {
  // Ensure only one login modal in DOM
  if (window.__loginModalInitialized) return;
  window.__loginModalInitialized = true;

  // Debug: Confirm modal element exists
  console.log('[LoginModal] Modal element exists:', !!document.getElementById('loginModal'));

  // Debug: Try to open the modal and log result (one-time test)
  if (window.location.hash === '#test-login-modal') {
    try {
      $('#loginModal').modal('show');
      console.log('[LoginModal] Bootstrap modal show() called successfully');
    } catch (e) {
      console.error('[LoginModal] Error calling Bootstrap modal show():', e);
    }
  }

  // Toggle password visibility
  $('#modal_togglePassword').on('click', function() {
    const $input = $('#modal_login_password');
    const $icon = $(this).find('i');
    const isHidden = $input.attr('type') === 'password';
    $input.attr('type', isHidden ? 'text' : 'password');
    $icon.toggleClass('ph-eye-slash ph-eye');
  });

  // Update intended URL when modal is shown
  $('#loginModal').on('show.bs.modal', function () {
    $('#modal_intended_url').val(window.location.href);
    console.log('[LoginModal] Showing login modal');
  });

  // If redirected after login and branch modal exists, show it
  if (window.location.hash === '#branch-select' && $('#selectBranchModal').length) {
    $('#selectBranchModal').modal('show');
  }

  // Always define showLoginModal globally
  window.showLoginModal = function() {
    sessionStorage.setItem('intended_url', window.location.href);
    $('#loginModal').modal('show');
    console.log('[LoginModal] showLoginModal() called');
  };

  // Handle Google login in modal
  window.handleModalGoogleLogin = function(event) {
    event.preventDefault();
    console.log('[LoginModal] Google login clicked');
    
    // Store intended URL for after login
    sessionStorage.setItem('intended_url', window.location.href);
    
    // Redirect to Google OAuth
    window.location.href = "{{ route('auth.google') }}";
  };

  // Listen for a custom event or hook after successful login
  window.addEventListener('loginSuccess', function () {
    const branchId = '{{ session('selected_branch_id') }}';
    $('#loginModal').on('hidden.bs.modal', function () {
      if (!branchId) {
        if ($('#selectBranchModal').length) {
          $('#selectBranchModal').modal('show');
        }
      } else {
        let intendedUrl = sessionStorage.getItem('intended_url');
        if (intendedUrl) {
          sessionStorage.removeItem('intended_url');
          window.location.href = intendedUrl;
        } else {
          window.location.href = '/';
        }
      }
    });
    $('#loginModal').modal('hide');
  });
})();
</script>
@endpush 