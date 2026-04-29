<x-auth-layout>
    <x-slot name="title">
        @lang('Login')
    </x-slot>

    @php
        // Ne koristiti setting('is_demo_login'): keš settings.all + config default '1' ako nema retka.
        $isDemoLoginEnabled = (string) (\App\Models\Setting::where('name', 'is_demo_login')->value('val') ?? '0') === '1';
    @endphp

    <x-auth-card>
        <x-slot name="logo">
            <a href="">
                <x-application-logo />
            </a>
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Social Login -->
        <x-auth-social-login />

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        {{-- ✅ Fixed form action --}}
        <form method="POST" action="{{ route('admin.login') }}">
            @csrf

            <!-- Email Address -->
            <div>
                <x-label for="email" :value="__('Email')" />

                <x-input id="email"
                         type="email"
                         name="email"
                         :value="old('email', ($isDemoLoginEnabled ? 'admin@salon.com' : null))"
                         placeholder="Enter Email"
                         required
                         autofocus
                         pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                         title="Please enter a valid email address." />

                <span id="emailError" class="text-danger" style="display: none;">Invalid email format</span>
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-label for="password" :value="__('Password')" />

                <x-input id="password"
                         type="password"
                         name="password"
                         :value="($isDemoLoginEnabled ? '12345678' : null)"
                         placeholder="Enter Password"
                         required
                         minlength="8"
                         maxlength="12"
                         autocomplete="current-password"
                         title="Password must be between 8 and 12 characters." />

                <span id="passwordError" class="text-danger" style="display: none;">Password must be between 8 and 12 characters</span>
            </div>



            <!-- Remember Me -->
            <div class="mt-4">
                <label for="remember_me" class="d-inline-flex">
                    <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                    <span class="ms-2">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="d-flex align-items-center justify-content-between mt-4">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 hover:text-gray-900"
                       href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-button>
                    {{ __('Log in') }}
                </x-button>
            </div>
        </form>
        @if($isDemoLoginEnabled)
            <div>
                <h6 class="text-center border-top py-3 mt-3">Demo Accounts</h6>
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-0" id="admin_email">admin@salon.com</p>
                        <p id="admin_password">12345678</p>
                    </div>
                    <div>
                        <a href="javascript:void(0)" onclick="setLoginCredentials('admin')" data-bs-toggle="tooltip" title="Click To Copy">
                            📋
                        </a>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-0" id="manager_email">manager@salon.com</p>
                        <p id="manager_password">12345678</p>
                    </div>
                    <div>
                        <a href="javascript:void(0)" onclick="setLoginCredentials('manager')" data-bs-toggle="tooltip" title="Click To Copy">
                            📋
                        </a>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="mb-0" id="staff_email">staff@salon.com</p>
                        <p id="staff_password">12345678</p>
                    </div>
                    <div>
                        <a href="javascript:void(0)" onclick="setLoginCredentials('staff')" data-bs-toggle="tooltip" title="Click To Copy">
                            📋
                        </a>
                    </div>
                </div>
            </div>
            @endif

        <x-slot name="extra">
            @if (Route::has('register'))
                <p class="text-center text-gray-600 mt-4">
                    Do not have an account? <a href="{{ route('register') }}"
                        class="underline hover:text-gray-900">Register</a>.
                </p>
            @endif
        </x-slot>
    </x-auth-card>

    <script>
        function domId(id) {
            return document.getElementById(id);
        }

        function setLoginCredentials(type) {
            domId('email').value = domId(type + '_email').textContent;
            domId('password').value = domId(type + '_password').textContent;
        }

        document.getElementById('email').addEventListener('input', function () {
            const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
            const emailField = this;
            const emailError = document.getElementById('emailError');

            if (emailField.value && !emailPattern.test(emailField.value)) {
                emailError.style.display = 'block';
                emailField.classList.add('is-invalid');
            } else {
                emailError.style.display = 'none';
                emailField.classList.remove('is-invalid');
            }
        });

        document.getElementById('password').addEventListener('input', function () {
            const passwordField = this;
            const passwordError = document.getElementById('passwordError');

            if (passwordField.value.length < 8 || passwordField.value.length > 12) {
                passwordError.style.display = 'block';
                passwordField.classList.add('is-invalid');
            } else {
                passwordError.style.display = 'none';
                passwordField.classList.remove('is-invalid');
            }
        });
    </script>
</x-auth-layout>
