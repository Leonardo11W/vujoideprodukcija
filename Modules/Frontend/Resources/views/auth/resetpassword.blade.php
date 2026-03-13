@extends('frontend::layouts.guest')

@section('content')
<section class="auth-page" style="background-image: url('{{ asset('img/frontend/set-password-bg.png') }}'); backgound-repat: no-repeat; background-size: cover;">
    <div class="containe h-100">
        <div class="row align-items-center justify-content-center h-100">
            <div class="col-xl-3 col-lg-6 col-md-8 my-5">
                <div class="py-5 px-3">
                    <div class="register-bg register-background-padding">
                        <div class="text-center mb-5">
                            <a href="#">
                                <img src="{{asset('img/logo/logo.png')}}" class="img-fluid auth-logo" alt="logo">
                            </a>
                            <h5 class="mb-1 register-title">{{__('frontend.set_new_password')}}</h5>
                            <p class="font-size-14 mb-5">{{__('frontend.for_security_reasons_we_recommend_using_a_strong_password')}}</p>
                        </div>

                        @if(session('status'))
                            <div class="alert alert-success">
                                {{ session('status') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('password.update') }}" method="POST">
                            @csrf
                            <input type="hidden" name="token" value="{{ $token }}">
                            <input type="hidden" name="email" value="{{ $email }}">

                            <div class="row gy-4">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="password" class="form-label fw-medium">{{__('frontend.password')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="password" name="password" id="password" class="form-control" placeholder="eg '#123@Abc'" required />
                                            <span class="input-group-text toggle-password" data-target="password"><i class="ph ph-eye-slash"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="password_error">{{__('frontend.password_field_is_required')}}</div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="password_confirmation" class="form-label fw-medium">{{__('frontend.confirm_password')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm your password" required />
                                            <span class="input-group-text toggle-password" data-target="password_confirmation"><i class="ph ph-eye-slash"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="password_confirmation_error">{{__('frontend.confirm_password_field_is_required')}}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-4 mt-5">
                                <button type="submit" id="resetPasswordBtn" class="btn btn-secondary sucessfully-password flex-grow-1">{{__('frontend.reset_password')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');

    // Function to show validation error
    function showValidationError(input, message) {
        const container = input.closest('.form-group');
        const errorFeedback = container.querySelector('.invalid-feedback');
        if (errorFeedback) {
            errorFeedback.textContent = message;
            errorFeedback.style.display = 'block';
            input.classList.add('is-invalid');
        }
    }

    // Function to clear validation error
    function clearValidationError(input) {
        const container = input.closest('.form-group');
        const errorFeedback = container.querySelector('.invalid-feedback');
        if (errorFeedback) {
            errorFeedback.style.display = 'none';
            input.classList.remove('is-invalid');
        }
    }

    // Function to check password strength
    function isStrongPassword(pw) {
        // At least 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special char
        return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]).{8,}$/.test(pw);
    }

    // Add click event listeners to all toggle password buttons
    document.querySelectorAll('.toggle-password').forEach(function(toggleButton) {
        toggleButton.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');

            // Toggle password visibility
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';

            // Toggle icon
            icon.classList.remove(isHidden ? 'ph-eye-slash' : 'ph-eye');
            icon.classList.add(isHidden ? 'ph-eye' : 'ph-eye-slash');
        });
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        console.log('Form submission started');
        let isValid = true;

        // Check password
        if (!password.value.trim()) {
            showValidationError(password, '{{__('frontend.password_field_is_required')}}');
            isValid = false;
        } else if (password.value.length < 8) {
            showValidationError(password, 'Password must be at least 8 characters long');
            isValid = false;
        } else {
            clearValidationError(password);
        }

        // Check confirm password
        if (!confirmPassword.value.trim()) {
            showValidationError(confirmPassword, '{{__('frontend.confirm_password_field_is_required')}}');
            isValid = false;
        } else if (password.value !== confirmPassword.value) {
            showValidationError(confirmPassword, 'Passwords do not match');
            isValid = false;
        } else {
            clearValidationError(confirmPassword);
        }

        if (!isValid) {
            console.log('Form validation failed, preventing submission');
            e.preventDefault();
        } else {
            console.log('Form validation passed, submitting...');
            // Add loading state
            const submitBtn = document.getElementById('resetPasswordBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Resetting Password...';
        }
    });

    // Clear validation on input
    [password, confirmPassword].forEach(input => {
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                clearValidationError(this);
            }
        });
    });
});
</script>
@endsection