<div class="section-spacing-inner-pages">
    <div class="container">
        <h5 class="mb-3 font-size-21-3">{{ __('frontend.change_password') }}</h5>

        <form action="{{ route('changepassword.update') }}" method="POST" id="changePasswordForm" novalidate>
            @csrf
            <div class="row gy-4">
                <!-- Old Password -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="old_password" class="form-label fw-medium">{{ __('frontend.old_password') }}<span
                                class="text-danger">*</span></label>
                        <div class="input-group custom-input-group">
                            <input type="password" name="old_password" id="old_password"
                                class="form-control @error('old_password') is-invalid @enderror"
                                placeholder="{{ __('frontend.enter_your_password') }}" />
                            <span class="input-group-text toggle-password" data-target="old_password"><i
                                    class="ph ph-eye-slash"></i></span>
                        </div>
                        @error('old_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="error-message text-danger mt-1" id="old_password_error" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- New Password -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="new_password" class="form-label fw-medium">{{ __('frontend.new_password') }}<span
                                class="text-danger">*</span></label>
                        <div class="input-group custom-input-group">
                            <input type="password" name="new_password" id="new_password"
                                class="form-control @error('new_password') is-invalid @enderror"
                                placeholder="{{ __('frontend.enter_your_password') }}" />
                            <span class="input-group-text toggle-password" data-target="new_password"><i
                                    class="ph ph-eye-slash"></i></span>
                        </div>
                        @error('new_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="error-message text-danger mt-1" id="new_password_error" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="confirm_password"
                            class="form-label fw-medium">{{ __('frontend.confirm_password') }}<span
                                class="text-danger">*</span></label>
                        <div class="input-group custom-input-group">
                            <input type="password" name="confirm_password" id="confirm_password"
                                class="form-control @error('confirm_password') is-invalid @enderror"
                                placeholder="{{ __('frontend.confirm_password') }}" />
                            <span class="input-group-text toggle-password" data-target="confirm_password"><i
                                    class="ph ph-eye-slash"></i></span>
                        </div>
                        @error('confirm_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="error-message text-danger mt-1" id="confirm_password_error" style="display: none;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="d-flex justify-content-end flex-wrap gap-3 mt-5 pt-lg-3 pt-0">

                <button type="submit" class="btn btn-primary">{{ __('frontend.update_password') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('changePasswordForm');
        const oldPassword = document.getElementById('old_password');
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

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

        // Function to show error message
        function showError(fieldId, message) {
            const errorElement = document.getElementById(fieldId + '_error');
            const field = document.getElementById(fieldId);

            errorElement.textContent = message;
            errorElement.style.display = 'block';
            field.classList.add('is-invalid');
        }

        // Function to hide error message
        function hideError(fieldId) {
            const errorElement = document.getElementById(fieldId + '_error');
            const field = document.getElementById(fieldId);

            errorElement.style.display = 'none';
            field.classList.remove('is-invalid');
        }

        // Function to validate required field
        function validateRequired(field, fieldName) {
            if (!field.value.trim()) {
                showError(field.id, fieldName + ' is required');
                return false;
            } else {
                hideError(field.id);
                return true;
            }
        }

        // Function to validate password strength
        function validatePassword(password) {
            const minLength = 8;
            const maxLength = 14;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            if (password.length < minLength || password.length > maxLength) {
                return 'Password length should be 8 to 14 Characters Long';
            }

            return null;
        }

        // Function to validate password match
        function validatePasswordMatch() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    showError('confirm_password', 'Passwords do not match');
                    return false;
                } else {
                    hideError('confirm_password');
                    return true;
                }
            }
            return true;
        }

        // Real-time validation for old password
        oldPassword.addEventListener('blur', function() {
            validateRequired(this, 'Old password');
        });

        oldPassword.addEventListener('input', function() {
            if (this.value.trim()) {
                hideError(this.id);
            }
        });

        // Real-time validation for new password
        newPassword.addEventListener('blur', function() {
            const isRequired = validateRequired(this, 'New password');
            if (isRequired) {
                const passwordError = validatePassword(this.value);
                if (passwordError) {
                    showError(this.id, passwordError);
                } else {
                    hideError(this.id);
                }
            }
            validatePasswordMatch();
        });

        newPassword.addEventListener('input', function() {
            if (this.value.trim()) {
                const passwordError = validatePassword(this.value);
                if (passwordError) {
                    showError(this.id, passwordError);
                } else {
                    hideError(this.id);
                }
            } else {
                hideError(this.id);
            }
            validatePasswordMatch();
        });

        // Real-time validation for confirm password
        confirmPassword.addEventListener('blur', function() {
            const isRequired = validateRequired(this, 'Confirm password');
            if (isRequired) {
                validatePasswordMatch();
            }
        });

        confirmPassword.addEventListener('input', function() {
            if (this.value.trim()) {
                hideError(this.id);
            }
            validatePasswordMatch();
        });

        // Form submission validation
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Validate all required fields
            if (!validateRequired(oldPassword, 'Old password')) {
                isValid = false;
            }

            if (!validateRequired(newPassword, 'New password')) {
                isValid = false;
            } else {
                const passwordError = validatePassword(newPassword.value);
                if (passwordError) {
                    showError('new_password', passwordError);
                    isValid = false;
                }
            }

            if (!validateRequired(confirmPassword, 'Confirm password')) {
                isValid = false;
            } else {
                if (!validatePasswordMatch()) {
                    isValid = false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    });
</script>
