@extends('frontend::layouts.guest')

@section('title', __('frontend.sign_up'))

@section('content')
<meta name="base-url" content="{{ url('/') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<section class="auth-page" style="background-image: url('{{ asset('img/frontend/sign-up-bg.png') }}'); backgound-repat: no-repeat; background-size: cover;">
    <div class="containe h-100">
        <div class="row align-items-center justify-content-center h-100">
            <div class="col-xl-8 col-md-10">
                <div class="py-5 px-3">
                    <div class="register-bg register-background-padding">
                        <div class="text-center mb-5">
                            
                            <a class="navbar-brand text-primary" href="{{ route('index') }}"> 
        <div class="logo-main">
            <div class="logo-mini d-none">
                <img src="{{ asset(setting('mini_logo')) }}" height="50" alt="{{ app_name() }}">
            </div>
            <div class="logo-normal">
                <img src="{{ asset(setting('logo')) }}" height="50" alt="{{ app_name() }}">
            </div>
            <div class="logo-dark">
                <img src="{{ asset(setting('dark_logo')) }}" height="50" alt="{{ app_name() }}">
            </div>
        </div>
    
                            </a>
                            <h5 class="mb-1 register-title">{{__('frontend.create_your_account')}}</h5>
                            <p class="font-size-14 mb-5">{{__('frontend.create_account_for_better_experience')}}</p>

                        </div>

                        <form id="registerForm" method="POST" action="{{ route('register') }}" class="needs-validation" novalidate>
                            @csrf
                         
                            <!-- <div id="error_message" class="text-danger mt-2 text-center"></div> -->
                            <div class="row gy-4 mt-5">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label fw-medium">{{__('frontend.first_name')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" id="first_name" class="form-control" placeholder="eg 'Martina'" name="first_name" required data-error="{{ __('frontend.first_name_field_is_required') }}"/>
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="first_name_error">{{__('frontend.first_name_field_is_required')}}</div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="last_name" class="form-label fw-medium">{{__('frontend.last_name')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" id="last_name" name="last_name" class="form-control" data-error="{{ __('frontend.last_name_field_is_required') }}" placeholder="eg 'Abhrahim'" required/>
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                    <div class="invalid-feedback" id="last_name_error">{{__('frontend.last_name_field_is_required')}}</div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label fw-medium">{{__('frontend.email')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="email" id="email" name="email" class="form-control" placeholder="demo@gmail.com" data-error="{{ __('frontend.email_field_is_required') }}" required/>
                                            <span class="input-group-text"><i class="ph ph-envelope-simple"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="email_error" style="display: none;">{{__('frontend.email_field_is_required')}}</div>
                                        <!-- <div id="email-availability-error" class="text-danger mt-1" style="display: none;"></div> -->
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="mobile" class="form-label fw-medium">{{__('frontend.contact_number')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="tel" id="mobile" name="mobile" class="form-control" placeholder="01234 56789" data-error="{{ __('frontend.contact_number_is_required') }}" required/>
                                            <span class="input-group-text"><i class="ph ph-phone-call"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="mobile_error">{{__('frontend.contact_number_is_required')}}</div>
                                        <div id="mobile-error-msg" class="text-danger mt-1" style="display:none;"></div>
                                        <div id="mobile-success-msg" class="text-success mt-1" style="display:none;"></div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="password" class="form-label fw-medium">{{__('frontend.password')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="password" id="password" name="password" class="form-control" placeholder="eg '#123@Abc'" data-error="{{ __('frontend.password_field_is_required') }}" required/>
                                            <span class="input-group-text" id="togglePassword" style="cursor: pointer;"><i class="ph ph-eye-slash"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="password_error">{{__('frontend.password_field_is_required')}}</div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label fw-medium">{{__('frontend.confirm_password')}}<span class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" data-error="{{ __('frontend.confirm_password_field_is_required') }}" placeholder="Confirm your password" required/>
                                            <span class="input-group-text" id="toggleConfirmPassword" style="cursor: pointer;"><i class="ph ph-eye-slash"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="confirm_password_error">{{__('frontend.confirm_password_field_is_required')}}</div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">{{__('frontend.gender')}}<span class="text-danger">*</span></label>
                                    <div class="select-gender d-flex align-items-center flex-wrap gap-3">
                                        <div class="form-check">
                                            <label class="form-check-label" for="gender_male">
                                                <input class="form-check-input" value="male" type="radio" name="gender" id="gender_male" checked />
                                                {{__('frontend.male')}}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <label class="form-check-label" for="gender_female">
                                                <input class="form-check-input" type="radio" value="female" name="gender" id="gender_female" />
                                                {{__('frontend.female')}}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <label class="form-check-label" for="gender_other">
                                                <input class="form-check-input" value="other" type="radio" name="gender" id="gender_other" />
                                                {{__('frontend.other')}}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="invalid-feedback" id="gender_error">{{__('frontend.gender_is_required')}}</div>
                                </div>
                            </div>
                            <!-- <div class="col-12 mt-5">
                                <div class="referral-code bg-purple rounded text-center">
                                    <h6 class="font-size-14">Do You have a referral code ? (Optional)</h6>
                                    <div class="row gy-3">
                                        <div class="col-xl-4 col-lg-3 d-lg-block d-none"></div>
                                        <div class="col-xl-4 col-lg-6">
                                            <input type="text" id="referral_code" class="form-control referral-code-input" placeholder="Referral Code" name="referral_code">
                                            <span class="text-success font-size-12 fw-medium mt-8">Success! Your referral is good to go!</span>
                                            <span class="text-danger font-size-12 fw-medium mt-8">Oops! That Referral Code doesn't Exist</span>
                                            <div class="invalid-feedback d-block"></div>
                                        </div>
                                        <div class="col-xl-4 col-lg-3 d-lg-block d-none"></div>
                                    </div>
                                </div>
                            </div> -->

                            <div class="col-lg-12">
                                <div class="d-flex align-items-center justify-content-center flex-wrap gap-3 register-btn">
                                    <button id="register-button" class="btn btn-secondary px-sm-5 px-3" type="submit" data-login-text="Sign Up">
                                        {{__('frontend.sign_up')}}
                                    </button>
                                </div>
                                <div class="d-flex justify-content-center flex-wrap gap-1 mt-3">
                                    <span class="font-size-14 text-body">{{__('frontend.already_have_an_account')}}</span>
                                    <a href="{{ route('login') }}" class="text-primary font-size-14 fw-medium text-decoration-underline">{{__('frontend.sign_in')}}</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<!-- Intl-tel-input and other scripts are now loaded in the main layout -->
<script src="{{ asset('js/auth.min.js') }}" defer></script>


    <!-- IntlTelInput Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const errorMessage = document.getElementById('error_message');

    // Initialize IntlTelInput for mobile field
    var mobileInput = document.querySelector("#mobile");
    if (mobileInput && window.intlTelInput) {
        window.iti = window.intlTelInput(mobileInput, {
            initialCountry: "us",
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
            autoPlaceholder: "aggressive",
            nationalMode: false,
            formatOnDisplay: true,
            autoHideDialCode: false
        });
        
        // Set the existing number and let IntlTelInput auto-detect the country
        if (mobileInput.value) {
            window.iti.setNumber(mobileInput.value);
        }
        
        // Add event listener for validation feedback
        mobileInput.addEventListener('blur', function() {
            if (window.iti) {
                const isValid = window.iti.isValidNumber();
                const fullNumber = window.iti.getNumber();
                console.log('Number validation on blur:', { isValid, fullNumber });
            }
        });
    }

    // Password visibility toggles
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const mobile = document.getElementById('mobile');

    // Toggle password visibility
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const currentType = password.getAttribute('type');
            const newType = currentType === 'password' ? 'text' : 'password';
            
            console.log('Password toggle clicked - Current type:', currentType, 'New type:', newType);
            
            // Force the type change using multiple methods
            password.setAttribute('type', newType);
            password.type = newType;
            
            // Additional method to ensure type change
            setTimeout(() => {
                password.type = newType;
                console.log('Password type after timeout:', password.type);
            }, 10);
            
            const icon = this.querySelector('i');
            if (icon) {
                if (newType === 'text') {
                    icon.classList.remove('ph-eye-slash');
                    icon.classList.add('ph-eye');
                } else {
                    icon.classList.remove('ph-eye');
                    icon.classList.add('ph-eye-slash');
                }
                console.log('Icon classes updated:', icon.className);
            }
        });
    }

    if (toggleConfirmPassword && confirmPassword) {
        toggleConfirmPassword.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const currentType = confirmPassword.getAttribute('type');
            const newType = currentType === 'password' ? 'text' : 'password';
            
            console.log('Confirm password toggle clicked - Current type:', currentType, 'New type:', newType);
            
            // Force the type change using multiple methods
            confirmPassword.setAttribute('type', newType);
            confirmPassword.type = newType;
            
            // Additional method to ensure type change
            setTimeout(() => {
                confirmPassword.type = newType;
                console.log('Confirm password type after timeout:', confirmPassword.type);
            }, 10);
            
            const icon = this.querySelector('i');
            if (icon) {
                if (newType === 'text') {
                    icon.classList.remove('ph-eye-slash');
                    icon.classList.add('ph-eye');
                } else {
                    icon.classList.remove('ph-eye');
                    icon.classList.add('ph-eye-slash');
                }
                console.log('Confirm password icon classes updated:', icon.className);
            }
        });
    }

    // Password and Confirm Password validation on blur
    function validatePasswordMatch() {
        if (password && confirmPassword && password.value && confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                const feedback = confirmPassword.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = '{{ __("frontend.passwords_do_not_match") }}';
                    feedback.style.display = 'block';
                }
            } else {
                confirmPassword.classList.remove('is-invalid');
                const feedback = confirmPassword.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            }
        }
    }

    // Email validation on blur
    const email = document.getElementById('email');
    if (email) {
        email.addEventListener('blur', function() {
            const emailValue = this.value.trim();
            if (emailValue) {
                // Email format validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailValue)) {
                    this.classList.add('is-invalid');
                    const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = '{{ __("frontend.invalid_email_format") }}';
                        feedback.style.display = 'block';
                    }
                } else {
                    // Check if email already exists
                    checkEmailAvailability(emailValue);
                }
            }
        });
    }

    // Function to check email availability
    function checkEmailAvailability(email) {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        console.log('Checking email availability for:', email);
        
        fetch('/check-email-availability', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                email: email
            })
        })
        .then(response => {
            console.log('Email check response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Email check response data:', data);
            const emailInput = document.getElementById('email');
            const feedback = emailInput?.closest('.form-group')?.querySelector('.invalid-feedback');
            const availabilityError = document.getElementById('email-availability-error');
            
            if (data.exists) {
                // Email already exists
                console.log('Email already exists, showing error');
                emailInput.classList.add('is-invalid');
                if (availabilityError) {
                    availabilityError.textContent = '{{ __("frontend.email_already_registered") }}';
                    availabilityError.style.display = 'block';
                    console.log('Error message set:', availabilityError.textContent);
                }
                if (feedback) {
                    feedback.style.display = 'none';
                }
            } else {
                // Email is available
                console.log('Email is available');
                emailInput.classList.remove('is-invalid');
                if (availabilityError) {
                    availabilityError.style.display = 'none';
                }
                if (feedback) {
                    feedback.style.display = 'none';
                }
            }
        })
        .catch(error => {
            console.error('Error checking email availability:', error);
        });
    }

    // Add blur event listeners for password fields
    if (password) {
        password.addEventListener('blur', function() {
            // Validate password length (8-14 characters)
            if (this.value && this.value.length < 8) {
                this.classList.add('is-invalid');
                const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = '{{ __("frontend.password_min_length") }}';
                    feedback.style.display = 'block';
                }
            } else if (this.value && this.value.length > 14) {
                this.classList.add('is-invalid');
                const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = 'Password must not exceed 14 characters.';
                    feedback.style.display = 'block';
                }
            } else if (this.value) {
                this.classList.remove('is-invalid');
                const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.style.display = 'none';
                }
            }
            
            // Check password match when password field loses focus
            validatePasswordMatch();
        });
    }

    if (confirmPassword) {
        confirmPassword.addEventListener('blur', function() {
            validatePasswordMatch();
        });
    }

    // Mobile input validation
    if (mobile) {
        // Real-time input validation
        mobile.addEventListener('input', function(e) {
            var value = this.value;
            var cleanedValue = value.replace(/[^\d]/g, ''); // Only allow digits
            if (cleanedValue !== value) {
                this.value = cleanedValue;
            }
        });

        // Prevent paste of non-digit characters
        mobile.addEventListener('paste', function(e) {
            e.preventDefault();
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            var cleanedText = pastedText.replace(/[^\d]/g, ''); // Only allow digits
            this.value = cleanedText;
        });

        // Prevent keydown of non-digit characters
        mobile.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter, and navigation keys
            if ([8, 9, 27, 13, 46, 37, 38, 39, 40].indexOf(e.keyCode) !== -1 ||
                // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            
            // Block + key since country code is separate
            
            // Allow only digits
            if ((e.keyCode >= 48 && e.keyCode <= 57) || (e.keyCode >= 96 && e.keyCode <= 105)) {
                return;
            }
            
            // Prevent all other keys
            e.preventDefault();
        });
    }

    // Form validation
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();

            // Reset all error states
            const invalidFeedbacks = form.querySelectorAll('.invalid-feedback');
            invalidFeedbacks.forEach(feedback => {
                feedback.textContent = '';
                feedback.style.display = 'none';
            });

            const formControls = form.querySelectorAll('.form-control');
            formControls.forEach(control => {
                control.classList.remove('is-invalid');
            });

            let isValid = true;
            const errors = [];

            // Validate required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                    const feedback = field.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = field.getAttribute('data-error') || '{{ __("frontend.this_field_is_required") }}';
                        feedback.style.display = 'block';
                    }
                    errors.push(field.getAttribute('data-error'));
                }
            });

            // Email validation
            const email = document.getElementById('email');
            if (email && email.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    isValid = false;
                    email.classList.add('is-invalid');
                    const feedback = email.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = '{{ __("frontend.please_enter_valid_email") }}';
                        feedback.style.display = 'block';
                    }
                    errors.push('{{ __("frontend.please_enter_valid_email") }}');
                }
            }

            // Password validation (8-14 characters)
            if (password && password.value) {
                if (password.value.length < 8) {
                    isValid = false;
                    password.classList.add('is-invalid');
                    const feedback = password.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = '{{ __("frontend.password_min_length") }}';
                        feedback.style.display = 'block';
                    }
                    errors.push('{{ __("frontend.password_min_length") }}');
                } else if (password.value.length > 14) {
                    isValid = false;
                    password.classList.add('is-invalid');
                    const feedback = password.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = 'Password must not exceed 14 characters.';
                        feedback.style.display = 'block';
                    }
                    errors.push('Password must not exceed 14 characters.');
                }
            }

            // Confirm password validation
            if (confirmPassword && password) {
                if (confirmPassword.value !== password.value) {
                    isValid = false;
                    confirmPassword.classList.add('is-invalid');
                    const feedback = confirmPassword.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = '{{ __("frontend.passwords_do_not_match") }}';
                        feedback.style.display = 'block';
                    }
                    errors.push('{{ __("frontend.passwords_do_not_match") }}');
                }
            }

            // Mobile number validation - required and format validation
            const mobile = document.getElementById('mobile');
            if (mobile) {
                if (!mobile.value || mobile.value.trim() === '') {
                    // Required validation
                    isValid = false;
                    mobile.classList.add('is-invalid');
                    const feedback = mobile.closest('.form-group')?.querySelector('.invalid-feedback');
                    if (feedback) {
                        feedback.textContent = '{{ __("frontend.contact_number_is_required") }}';
                        feedback.style.display = 'block';
                    }
                    errors.push('{{ __("frontend.contact_number_is_required") }}');
                } else {
                    // Use IntlTelInput validation if available
                    if (window.iti && typeof window.iti.isValidNumber === 'function') {
                        // Get the full international number for validation
                        const fullNumber = window.iti.getNumber();
                        const isValidNumber = window.iti.isValidNumber();
                        console.log('Validating number:', { fullNumber, isValidNumber, rawValue: mobile.value });
                        
                        // Check if we have a valid number and it's not empty
                        if (!fullNumber || fullNumber.trim() === '' || !isValidNumber) {
                            isValid = false;
                            mobile.classList.add('is-invalid');
                            const feedback = mobile.closest('.form-group')?.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = '{{ __("frontend.please_enter_valid_mobile_number") }}';
                                feedback.style.display = 'block';
                            }
                            errors.push('{{ __("frontend.please_enter_valid_mobile_number") }}');
                        } else {
                            // Clear any previous errors
                            mobile.classList.remove('is-invalid');
                            const feedback = mobile.closest('.form-group')?.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.style.display = 'none';
                            }
                        }
                    } else {
                        // Fallback validation - basic format check for raw input
                        const mobileRegex = /^[\d\s\-\+\(\)]{8,20}$/;
                        if (!mobileRegex.test(mobile.value)) {
                            isValid = false;
                            mobile.classList.add('is-invalid');
                            const feedback = mobile.closest('.form-group')?.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.textContent = '{{ __("frontend.please_enter_valid_mobile_number") }}';
                                feedback.style.display = 'block';
                            }
                            errors.push('{{ __("frontend.please_enter_valid_mobile_number") }}');
                        } else {
                            // Clear any previous errors
                            mobile.classList.remove('is-invalid');
                            const feedback = mobile.closest('.form-group')?.querySelector('.invalid-feedback');
                            if (feedback) {
                                feedback.style.display = 'none';
                            }
                        }
                    }
                }
            }

            if (!isValid) {
                if (errorMessage) {
                    errorMessage.textContent = 'Please fix the errors in the form.';
                    errorMessage.style.display = 'block';
                }
                return;
            }

            // Show loading indicator
            const loadingIndicator = form.querySelector('.loading-indicator');
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton ? submitButton.innerHTML : '';
            
            if (loadingIndicator) loadingIndicator.classList.remove('d-none');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = 'Processing...';
            }

            // Prepare form data
            const formData = new FormData(form);
            
            // Get the full international number from IntlTelInput
            if (window.iti && typeof window.iti.getNumber === 'function') {
                const fullNumber = (window.intlTelInputUtils && window.intlTelInputUtils.numberFormat)
                    ? window.iti.getNumber(window.intlTelInputUtils.numberFormat.INTERNATIONAL) // e.g. +1 555 123 4567
                    : window.iti.getNumber(); // fallback (E.164, no spaces)
                if (fullNumber) {
                    formData.set('mobile', fullNumber);
                    console.log('Full international number:', fullNumber);
                }
            }
            
            // Get CSRF token
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const baseUrl = document.querySelector('meta[name="base-url"]').getAttribute('content');

            // Send AJAX request
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: formData
            })
            .then(response => {

                console.log(response)

                if(response.status=='422'){
                    return response.json().then(err => {
                        console.log('422 Error response:', err);
                        
                        // Handle email validation errors (single error message)
                        if (err.errors && err.errors.error && err.errors.error.includes('email')) {
                            const input = document.querySelector(`[name="email"]`);
                            const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                            const availabilityError = document.getElementById('email-availability-error');
                            
                            if (input) {
                                input.classList.add('is-invalid');
                            }
                            
                            // Use the availability error div for better visibility
                            if (availabilityError) {
                                availabilityError.textContent = '{{ __("frontend.email_already_registered") }}';
                                availabilityError.style.display = 'block';
                            }
                            
                            // Also set the feedback div as fallback
                            if (feedback) {
                                feedback.textContent = '{{ __("frontend.email_already_registered") }}';
                                feedback.style.display = 'block';
                            }
                        }
                        // Handle email validation errors (field-specific)
                        else if (err.errors && err.errors.email) {
                            const input = document.querySelector(`[name="email"]`);
                            const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                            const availabilityError = document.getElementById('email-availability-error');
                            
                            if (input) {
                                input.classList.add('is-invalid');
                            }
                            
                            // Use the availability error div for better visibility
                            if (availabilityError) {
                                availabilityError.textContent = Array.isArray(err.errors.email) ? err.errors.email[0] : err.errors.email;
                                availabilityError.style.display = 'block';
                            }
                            
                            // Also set the feedback div as fallback
                            if (feedback) {
                                feedback.textContent = Array.isArray(err.errors.email) ? err.errors.email[0] : err.errors.email;
                                feedback.style.display = 'block';
                            }
                        }
                        
                        // Handle mobile number validation errors
                        if (err.errors && err.errors.mobile) {
                            console.log('Mobile validation error:', err.errors.mobile);
                            const input = document.querySelector(`[name="mobile"]`);
                            const errorMsg = document.getElementById('mobile-error-msg');
                            const errorMessage = err.errors.mobile[0] || '{{ __("frontend.contact_number_already_exists") }}';
                            
                            if (input) {
                                input.classList.add('is-invalid');
                            }
                            if (errorMsg) {
                                errorMsg.textContent = errorMessage;
                                errorMsg.style.display = 'block';
                            }
                        }
                        
                        throw err;
                    });
                }


                if (!response.ok) {
                    return response.json().then(err => {
                        throw err;
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const redirectUrl = data.redirect_url ? data.redirect_url : baseUrl + '/';
                    const emailValue = formData.get('email');
                    const passwordValue = formData.get('password');
                    
                    if (data.message) {
                        localStorage.setItem('registerSuccessMessage', data.message);
                    }

                    const proceedToRedirect = (destination) => {
                        window.location.href = destination || redirectUrl;
                    };

                    return refreshCsrfToken(baseUrl)
                        .catch(() => token)
                        .then((freshToken) => {
                            return autoLoginAfterRegister({
                                email: emailValue,
                                password: passwordValue,
                                token: freshToken,
                                baseUrl
                            });
                        })
                        .then(loginResponse => {
                            if (loginResponse && loginResponse.success && loginResponse.redirect_url) {
                                proceedToRedirect(loginResponse.redirect_url);
                            } else {
                                proceedToRedirect();
                            }
                        })
                        .catch(error => {
                            console.error('Auto login failed:', error);
                            proceedToRedirect();
                        });
                }

                throw new Error(data.message || 'Registration failed.');
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button state
                if (loadingIndicator) loadingIndicator.classList.add('d-none');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
                
                // Handle validation errors (422 response)
                if (error.errors) {
                    console.log('Validation errors:', error.errors);
                    
                    // Handle contact number error (when it comes as a single error message)
                    if (error.errors.error && error.errors.error.includes('contact number')) {
                        const input = document.querySelector(`[name="mobile"]`);
                        const errorMsg = document.getElementById('mobile-error-msg');
                        
                        if (input) {
                            input.classList.add('is-invalid');
                        }
                        if (errorMsg) {
                            errorMsg.textContent = error.errors.error;
                            errorMsg.style.display = 'block';
                        }
                    }
                    // Handle email error (when it comes as a single error message)
                    else if (error.errors.error && error.errors.error.includes('email')) {
                        const input = document.querySelector(`[name="email"]`);
                        const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                        const availabilityError = document.getElementById('email-availability-error');
                        
                        if (input) {
                            input.classList.add('is-invalid');
                        }
                        
                        // Use the availability error div for better visibility
                        if (availabilityError) {
                            availabilityError.textContent = '{{ __("frontend.email_already_registered") }}';
                            availabilityError.style.display = 'block';
                        }
                        
                        // Also set the feedback div as fallback
                        if (feedback) {
                            feedback.textContent = '{{ __("frontend.email_already_registered") }}';
                            feedback.style.display = 'block';
                        }
                    }
                    // Handle email validation errors
                    else if (error.errors.email) {
                        const input = document.querySelector(`[name="email"]`);
                        const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                        const availabilityError = document.getElementById('email-availability-error');
                        
                        if (input) {
                            input.classList.add('is-invalid');
                        }
                        
                        // Use the availability error div for better visibility
                        if (availabilityError) {
                            availabilityError.textContent = Array.isArray(error.errors.email) ? error.errors.email[0] : error.errors.email;
                            availabilityError.style.display = 'block';
                        }
                        
                        // Also set the feedback div as fallback
                        if (feedback) {
                            feedback.textContent = Array.isArray(error.errors.email) ? error.errors.email[0] : error.errors.email;
                            feedback.style.display = 'block';
                        }
                    }
                    // Handle mobile validation errors (standard Laravel validation format)
                    else if (error.errors.mobile) {
                        const input = document.querySelector(`[name="mobile"]`);
                        const errorMsg = document.getElementById('mobile-error-msg');
                        const errorMessage = Array.isArray(error.errors.mobile) ? error.errors.mobile[0] : error.errors.mobile;
                        
                        if (input) {
                            input.classList.add('is-invalid');
                        }
                        if (errorMsg) {
                            errorMsg.textContent = errorMessage;
                            errorMsg.style.display = 'block';
                        }
                    }
                    // Handle other field errors
                    else {
                        Object.entries(error.errors).forEach(([field, messages]) => {
                            if (field !== 'email' && field !== 'mobile' && field !== 'error') {
                                const input = document.querySelector(`[name="${field}"]`);
                                const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                                if (input && feedback) {
                                    input.classList.add('is-invalid');
                                    feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                                    feedback.style.display = 'block';
                                }
                            }
                        });
                    }
                }
                // Handle other types of errors
                else if (error.response && error.response.data) {
                    const { errors, message } = error.response.data;
                    
                    if (errors) {
                        Object.entries(errors).forEach(([field, messages]) => {
                            const input = document.querySelector(`[name="${field}"]`);
                            const feedback = input?.closest('.form-group')?.querySelector('.invalid-feedback');
                            if (input && feedback) {
                                input.classList.add('is-invalid');
                                feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                                feedback.style.display = 'block';
                            }
                        });
                    } else if (message) {
                        if (errorMessage) {
                            errorMessage.textContent = message;
                            errorMessage.style.display = 'block';
                        }
                    }
                } else {
                    if (errorMessage) {
                        errorMessage.textContent = 'An error occurred. Please try again.';
                        errorMessage.style.display = 'block';
                    }
                }
            });
        });

        // Clear validation on input
        form.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.closest('.form-group')?.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.textContent = '';
                    feedback.style.display = 'none';
                }
                
                // Clear email availability error
                if (this.id === 'email') {
                    const availabilityError = document.getElementById('email-availability-error');
                    if (availabilityError) {
                        availabilityError.style.display = 'none';
                    }
                }
                
                if (errorMessage) {
                    errorMessage.style.display = 'none';
                }
            });
        });
    }
});

function refreshCsrfToken(baseUrl) {
    return fetch((baseUrl || '') + '/csrf-token', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to refresh CSRF token.');
        }
        return response.json();
    })
    .then(data => {
        if (data && data.token) {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                metaToken.setAttribute('content', data.token);
            }
            return data.token;
        }
        throw new Error('Invalid CSRF token response.');
    });
}

function autoLoginAfterRegister({ email, password, token, baseUrl }) {
    if (!email || !password) {
        return Promise.reject('Missing login credentials.');
    }

    const loginData = new FormData();
    loginData.append('email', email);
    loginData.append('password', password);

    return fetch((baseUrl || '') + '/login', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: loginData
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => Promise.reject(err));
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            return data;
        }
        return Promise.reject(data || { message: 'Auto login failed.' });
        
    });
}
</script>

@push('after-scripts')
<script>
// Mobile number input validation - only allow numbers, +, - and spaces
$(document).ready(function() {
    let contactNumberTimeout;
    
    $('#mobile').on('input', function(e) {
        var value = $(this).val();
        var $error = $('#mobile-error-msg');
        var $success = $('#mobile-success-msg');
        
        // Clear previous timeout
        if (contactNumberTimeout) {
            clearTimeout(contactNumberTimeout);
        }
        
        // Allow numbers, +, - and spaces
        if (/[^0-9+\s-]/.test(value)) {
            $error.text('Only numbers, +, - and spaces are allowed.').show();
            $success.hide();
            // Remove invalid characters
            $(this).val(value.replace(/[^0-9+\s-]/g, ''));
        } else {
            // Only hide error if it's the format error, not the uniqueness error
            if ($error.text().includes('Only numbers')) {
                $error.hide();
            }
            
            // Check for contact number uniqueness if value is long enough
            if (value && value.length >= 8) {
                // Show checking message
                $error.text('{{ __("frontend.checking_contact_number") }}').show();
                $success.hide();
                
                // Debounce the validation
                contactNumberTimeout = setTimeout(() => {
                    checkContactNumberAvailability(value);
                }, 500);
            }
        }
    });
    
    function checkContactNumberAvailability(contactNumber) {
        $.ajax({
            url: '/check-contact-number',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            data: JSON.stringify({
                contact_number: contactNumber
            }),
            success: function(data) {
                var $error = $('#mobile-error-msg');
                var $success = $('#mobile-success-msg');
                var $input = $('#mobile');
                
                if (data.exists) {
                    // Contact number already exists
                    $input.removeClass('is-valid').addClass('is-invalid');
                    $error.text('{{ __("frontend.contact_number_already_exists") }}').show();
                    $success.hide();
                } else {
                    // Contact number is available
                    $input.removeClass('is-invalid').addClass('is-valid');
                    $error.hide();
                    $success.text('{{ __("frontend.contact_number_available") }}').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking contact number:', error);
                var $error = $('#mobile-error-msg');
                var $success = $('#mobile-success-msg');
                var $input = $('#mobile');
                
                $input.removeClass('is-invalid is-valid');
                $error.hide();
                $success.hide();
            }
        });
    }
});
</script>
@endpush