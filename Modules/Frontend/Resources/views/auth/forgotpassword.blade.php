@extends('frontend::layouts.guest')

@section('title', __('frontend.forgot_password'))

@section('content')
<section class="auth-page" style="background-image: url('{{ asset('img/frontend/forgot-password-bg.png') }}'); backgound-repat: no-repeat; background-size: cover;">
    <div class="containe h-100">
        <div class="row align-items-center justify-content-center h-100">
            <div class="col-xl-4 col-lg-7 col-md-9 my-5">
                <div class="py-5 px-3">
                    <div class="register-bg register-background-padding">
                        <div class="text-center mb-5">
                            <a href="{{ route('index') }}">
                                <img src="{{asset('img/logo/logo.png')}}" class="img-fluid auth-logo" alt="logo">
                            </a>
                            <h5 class="mb-1 register-title">{{__('frontend.forgot_your_password')}}</h5>
                            <p class="font-size-14 mb-5">{{__('frontend.no_worries_enter_your_registered_email_address_and_we_ll_send_you_instructions_to_reset_your_password')}}</p>
                        </div>

                        @if(session('status'))
                            <div class="alert {{ session('status')['status'] ? 'alert-success' : 'alert-danger' }}">
                                {{ session('status')['message'] }}
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

                        <form action="{{ route('password.email') }}" method="POST" novalidate>
                            @csrf
                            <div class="row gy-4">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="email" class="form-label fw-medium">{{__('frontend.email')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="email" name="email" id="email" class="form-control" placeholder="demo@gmail.com" value="{{ old('email') }}" required />
                                            <span class="input-group-text"><i class="ph ph-envelope-simple"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="email_error"></div>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-center flex-md-nowrap flex-wrap column-gap-4 row-gap-2 mt-5">
                                <a href="{{ route('login') }}" class="btn btn-primary">{{__('frontend.back_to_sign_in')}}</a>
                                <button type="submit" class="btn btn-secondary">{{__('frontend.reset_password')}}</button>
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
    const emailInput = document.getElementById('email');
    const emailError = document.getElementById('email_error');

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function showValidationError(input, message) {
        const container = input.closest('.form-group');
        const errorFeedback = container.querySelector('.invalid-feedback');
        if (errorFeedback) {
            errorFeedback.textContent = message;
            errorFeedback.style.display = 'block';
            input.classList.add('is-invalid');
        }
    }

    function clearValidationError(input) {
        const container = input.closest('.form-group');
        const errorFeedback = container.querySelector('.invalid-feedback');
        if (errorFeedback) {
            errorFeedback.style.display = 'none';
            input.classList.remove('is-invalid');
        }
    }

    // Email validation on input
    emailInput.addEventListener('input', function() {
        if (!this.value.trim()) {
            showValidationError(this, '{{__('frontend.email_field_is_required')}}');
        } else if (!validateEmail(this.value)) {
            showValidationError(this, '{{__('frontend.please_enter_a_valid_email_address')}}');
        } else {
            clearValidationError(this);
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate email
        if (!emailInput.value.trim()) {
            showValidationError(emailInput, '{{__('frontend.email_field_is_required')}}');
            isValid = false;
        } else if (!validateEmail(emailInput.value)) {
            showValidationError(emailInput, '{{__('frontend.please_enter_a_valid_email_address')}}');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
@endsection