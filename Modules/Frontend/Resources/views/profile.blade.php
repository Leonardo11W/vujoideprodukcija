 @extends('frontend::layouts.master')

@section('title', __('frontend.profile'))

@section('content')
    {{--
<x-profile_section /> --}}
    <x-breadcrumb title="{{ __('frontend.profile') }}" />
    <div class="section-spacing-inner-pages">
        <div class="container">
            <div class="profile-box row gy-3">
                <div class="col-lg-4 col-md-6">
                    <div class="profile-image-section position-relative">
                        <div class="img-container">
                            <img src="{{ $user->getFirstMediaUrl('profile_image') ?: asset(default_user_avatar()) }}" id="profileImagePreview" alt="Profile Image"
                                class="profile-image rounded-2 object-fit-cover"
                                data-has-custom-image="{{ $user->hasMedia('profile_image') ? 'true' : 'false' }}">
                        </div>

                        <div
                            class="image-actions mt-3 position-absolute d-flex flex-nowrap align-items-center justify-content-center">
                            <label for="profile_image"
                                class="change-btn btn border-0 d-inline-flex align-items-center justify-content-center text-nowrap">
                                <i class="ph ph-pencil-simple-line font-size-18 icon-color"></i>

                            </label>
                            <input type="file" id="profile_image" class="d-none" accept="image/*"
                                onchange="previewSelectedImage(event)">
                            <button
                                class="border-0 delete-btn btn d-inline-flex flex-column align-items-center justify-content-center"
                                id="deleteImageBtn" style="display: none;"><i
                                    class="ph ph-trash font-size-18 icon-color"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-6">
                    <form class="profile-form">
                        <div class="row gy-4">
                            <!-- First Name -->
                            <div class="col-lg-6">
                                <label for="first_name" class="form-label">{{__("frontend.first_name")}}</label>
                                <div class="input-group custom-input-group position-relative">
                                    <input type="text" id="first_name" name="first_name" class="form-control font-size-14"
                                        value="{{ $user->first_name ?? '' }}" placeholder="{{__("frontend.enter_first_name")}}" autocomplete="given-name" />
                                    <span class="input-group-text"><i class="ph ph-user"></i></span>
                                </div>
                                <div class="invalid-feedback" id="first_name_error">{{__("frontend.first_name_field_is_required")}}</div>
                            </div>


                            <!-- Last Name -->
                            <div class="col-lg-6">
                                <label for="last_name" class="form-label">{{__("frontend.last_name")}}</label>
                                <div class="input-group custom-input-group position-relative">
                                    <input type="text" id="last_name" name="last_name" class="form-control font-size-14"
                                        value="{{ $user->last_name ?? '' }}" placeholder="{{__("frontend.enter_last_name")}}" autocomplete="family-name" />
                                    <span class="input-group-text"><i class="ph ph-user"></i></span>
                                </div>
                                <div class="invalid-feedback" id="last_name_error">{{__("frontend.last_name_field_is_required")}}</div>
                            </div>

                            <!-- Email -->
                            <div class="col-lg-6">
                                <label for="email" class="form-label">{{__("frontend.email")}}</label>
                                <div class="input-group custom-input-group position-relative">
                                    <input type="email" id="email" name="email" class="form-control font-size-14"
                                        value="{{ $user->email ?? '' }}" placeholder="{{__("frontend.enter_email")}}" autocomplete="email" />
                                    <span class="input-group-text"><i class="ph ph-envelope"></i></span>
                                </div>
                                <div class="invalid-feedback" id="email_error">{{__("frontend.email_field_is_required")}}</div>
                            </div>
                            <!-- Contact Number -->
                            <div class="col-lg-6">
                                <label for="contact_number" class="form-label">{{__("frontend.contact_number")}}</label>
                                <div class="input-group custom-input-group position-relative">
                                    <input type="tel" id="mobileInput" name="mobile" class="form-control font-size-14"
                                        value="{{ $user->mobile ?? '' }}" autocomplete="tel">
                                    <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                </div>
                                <div class="invalid-feedback" id="mobile_error">{{__("frontend.contact_number_is_required")}}</div>
                            </div>


                            <!-- Gender -->
                            <div class="col-12">
                                <label class="form-label">{{__("frontend.gender")}}</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female"
                                            value="female" {{ $user->gender == 'female' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="female">{{__("frontend.female")}}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male"
                                            value="male" {{ $user->gender == 'male' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="male">{{__("frontend.male")}}</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="other"
                                            value="other" {{ $user->gender == 'other' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="other">{{__("frontend.other")}}</label>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="gender_error">{{__("frontend.gender_is_required")}}</div>
                            </div>
                        </div>
                        <div class="form-actions mt-4 d-flex justify-content-end gap-3">
                            {{-- <button type="button" class="btn btn-primary">{{__("frontend.cancel")}}</button> --}}
                            <button type="submit" id="updateProfileBtn" class="btn btn-secondary">{{__("frontend.save")}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    <style>
        #deleteImageBtn {
            display: none !important;
        }
        #deleteImageBtn.show {
            display: flex !important;
        }

        /* Make delete button red */
        #deleteImageBtn {
            background-color: #dc3545 !important;
            color: white !important;
        }

        #deleteImageBtn:hover {
            background-color: #c82333 !important;
            color: white !important;
        }

        #deleteImageBtn i {
            color: white !important;
        }

        /* Make edit button use theme color */
        .change-btn {
            background-color: var(--bs-primary) !important;
            color: white !important;
            border-color: var(--bs-primary) !important;
        }

        .change-btn:hover {
            background-color: var(--bs-primary) !important;
            color: white !important;
            border-color: var(--bs-primary) !important;
            opacity: 0.9;
        }

        .change-btn i {
            color: white !important;
        }

        /* Dark mode styling for IntlTelInput dropdown - matching app's dark theme */
        .iti__country-list {
            background-color: var(--bs-body-bg) !important;
            border: 1px solid var(--bs-border-color) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3) !important;
        }

        /* Profile image styling - make image smaller while keeping container size */
        .profile-image-section .img-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-image-section .img-container img {
            width: 60% !important;
            height: auto !important;
            aspect-ratio: 1/1 !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            border: 4px solid var(--bs-gray-900) !important;
        }

        /* Ensure placeholder image shows properly */
        .profile-image-section .img-container img[src*="avatar.png"],
        .profile-image-section .img-container img[src*="user.png"] {
            opacity: 1 !important;
            filter: none !important;
        }

    </style>
    <script>
        let isDefaultImage = false;

        // Check initial state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const profileImage = document.getElementById('profileImagePreview');
            const deleteBtn = document.getElementById('deleteImageBtn');
            const hasCustomImage = profileImage.getAttribute('data-has-custom-image') === 'true';

            console.log('Profile image src:', profileImage.src);
            console.log('Has custom image:', hasCustomImage);
            console.log('Data attribute:', profileImage.getAttribute('data-has-custom-image'));

            if (hasCustomImage) {
                isDefaultImage = false;
                deleteBtn.classList.add('show');
                console.log('Showing delete button');
            } else {
                isDefaultImage = true;
                deleteBtn.classList.remove('show');
                console.log('Hiding delete button');
            }
        });

        function previewSelectedImage(event) {
            const file = event.target.files[0];
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImagePreview').src = e.target.result;
                isDefaultImage = false;
                // Show trash bin when user selects a new image
                document.getElementById('deleteImageBtn').classList.add('show');
            };
            reader.readAsDataURL(file);
        }

        // IntlTelInput setup
        var mobileInput = document.querySelector("#mobileInput");

        var iti = window.intlTelInput(mobileInput, {
            initialCountry: "auto",
            separateDialCode: true,
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js",
            autoPlaceholder: "aggressive",
            nationalMode: false,
            formatOnDisplay: true,
            autoHideDialCode: false,
            preferredCountries: ['in', 'us', 'gb', 'au', 'ca', 'de', 'fr', 'it', 'es', 'nl', 'se', 'no', 'dk', 'fi', 'jp', 'kr', 'cn', 'br', 'mx', 'af', 'al', 'dz'],
            geoIpLookup: function(callback) {
                // Use IP geolocation to detect user's country
                fetch('https://ipapi.co/json/')
                    .then(function(res) { return res.json(); })
                    .then(function(data) { 
                        console.log('IP geolocation detected:', data.country_code);
                        // Store the detected country for later use
                        window.detectedCountry = data.country_code;
                        console.log('Stored detected country:', window.detectedCountry);
                        callback(data.country_code); 
                    })
                    .catch(function() { 
                        console.log('IP geolocation failed, using US as default');
                        window.detectedCountry = 'us';
                        callback('us'); 
                    });
            }
        });

        // Set the existing number and let IntlTelInput auto-detect the country
        if (mobileInput.value) {
            console.log('Original number:', mobileInput.value);
            
            // Clean the number format for better detection
            let cleanNumber = mobileInput.value;
            
            // If number starts with country code but doesn't have +, add it
            if (cleanNumber.match(/^\d{2,3}-\d+/) && !cleanNumber.startsWith('+')) {
                const countryCode = cleanNumber.split('-')[0];
                cleanNumber = '+' + countryCode + cleanNumber.substring(countryCode.length);
                console.log('Cleaned number:', cleanNumber);
            }
            
            // Wait for the library to be fully initialized
            setTimeout(() => {
                try {
                    // Use IntlTelInput's built-in country detection
                    iti.setNumber(cleanNumber);
                    
                    // Wait for the library to process the number
                    setTimeout(() => {
                        const countryData = iti.getSelectedCountryData();
                        if (countryData && countryData.name) {
                            console.log('Country detected:', countryData.name, 'Code:', countryData.dialCode);
                        } else {
                            console.log('Country detection failed, trying alternative approach');
                            // Try to get the number and see if it was parsed correctly
                            const number = iti.getNumber();
                            console.log('Parsed number:', number);
                            
                            // Try setting the country manually based on the number
                            const originalNumber = mobileInput.value;
                            console.log('Original number for manual detection:', originalNumber);
                            
                            // Manual country detection for common cases
                            if (originalNumber.startsWith('213') || originalNumber.includes('213')) {
                                iti.setCountry('dz'); // Algeria
                                console.log('Manually set to Algeria');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('355') || originalNumber.includes('355')) {
                                iti.setCountry('al'); // Albania
                                console.log('Manually set to Albania');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('93') || originalNumber.includes('93')) {
                                iti.setCountry('af'); // Afghanistan
                                console.log('Manually set to Afghanistan');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('44') || originalNumber.includes('44')) {
                                iti.setCountry('gb'); // UK
                                console.log('Manually set to UK');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('61') || originalNumber.includes('61')) {
                                iti.setCountry('au'); // Australia
                                console.log('Manually set to Australia');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('91') || originalNumber.includes('91')) {
                                iti.setCountry('in'); // India
                                console.log('Manually set to India');
                                iti.setNumber(originalNumber);
                            } else if (originalNumber.startsWith('1') && originalNumber.length === 10) {
                                // This is likely a US number without country code
                                iti.setCountry('us'); // US
                                console.log('Manually set to US');
                                iti.setNumber(originalNumber);
                            } else {
                                // For existing users, try to extract country from the stored number format
                                console.log('Trying to extract country from stored number format');
                                
                                // Check if the number has a country code prefix
                                if (originalNumber.startsWith('+')) {
                                    // Number has + prefix, let IntlTelInput handle it
                                    iti.setNumber(originalNumber);
                                } else if (originalNumber.includes('-')) {
                                    // Number has format like "91-1234567890", extract country code
                                    const parts = originalNumber.split('-');
                                    if (parts.length === 2) {
                                        const countryCode = parts[0];
                                        const phoneNumber = parts[1];
                                        
                                        // Map common country codes to country codes
                                        const countryMap = {
                                            '91': 'in', '1': 'us', '44': 'gb', '61': 'au', 
                                            '49': 'de', '33': 'fr', '39': 'it', '34': 'es',
                                            '31': 'nl', '46': 'se', '47': 'no', '45': 'dk',
                                            '358': 'fi', '81': 'jp', '82': 'kr', '86': 'cn',
                                            '55': 'br', '52': 'mx', '93': 'af', '355': 'al',
                                            '213': 'dz'
                                        };
                                        
                                        if (countryMap[countryCode]) {
                                            iti.setCountry(countryMap[countryCode]);
                                            iti.setNumber(phoneNumber);
                                            console.log('Set country from stored number:', countryMap[countryCode]);
                                        } else {
                                            iti.setNumber(originalNumber);
                                        }
                                    } else {
                                        iti.setNumber(originalNumber);
                                    }
                                } else {
                                    // No clear country code, use IP geolocation fallback
                                    const fallbackCountry = window.detectedCountry || 'us';
                                    iti.setCountry(fallbackCountry);
                                    iti.setNumber(originalNumber);
                                    console.log('Using IP geolocation fallback:', fallbackCountry);
                                }
                            }
                        }
                    }, 200);
                } catch (error) {
                    console.error('Error setting number:', error);
                }
            }, 500); // Wait longer for library initialization
        } else {
            // For new users with no phone number, use IP geolocation
            console.log('No phone number found, using IP geolocation for new user');
            setTimeout(() => {
                const countryData = iti.getSelectedCountryData();
                console.log('Country from IP geolocation:', countryData.name, 'Code:', countryData.dialCode);
                
                // Ensure the country is properly set for new users
                if (countryData && countryData.name) {
                    console.log('Country set from IP geolocation:', countryData.name);
                } else {
                    console.log('IP geolocation failed, using default country');
                }
            }, 1000);
        }

        // Add event listener for country change
        mobileInput.addEventListener('countrychange', function() {
            const countryData = iti.getSelectedCountryData();
            console.log('Country changed to:', countryData.name, 'Code:', countryData.dialCode);
            
            // Update profile dropdown in real-time
            updateProfileDropdownContact();
        });

        // Ensure country is set correctly after initialization (only for new users)
        setTimeout(() => {
            if (window.detectedCountry && !mobileInput.value) {
                console.log('Setting country from stored IP detection for new user:', window.detectedCountry);
                iti.setCountry(window.detectedCountry);
            }
        }, 2000);

        // Add digit-only validation for mobile input
        mobileInput.addEventListener('input', function(e) {
            var value = this.value;
            // Remove any non-digit characters except + (for country code)
            var cleanedValue = value.replace(/[^\d+]/g, '');

            // If the cleaned value is different from the original, update the input
            if (cleanedValue !== value) {
                this.value = cleanedValue;
            }
            
            // Update profile dropdown in real-time when number changes
            updateProfileDropdownContact();
        });

        // Prevent paste of non-digit characters
        mobileInput.addEventListener('paste', function(e) {
            e.preventDefault();
            var pastedText = (e.clipboardData || window.clipboardData).getData('text');
            var cleanedText = pastedText.replace(/[^\d+]/g, '');
            this.value = cleanedText;
        });

        // Handle delete profile image
        document.querySelector('.delete-btn').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('profileImagePreview').src = '{{ asset(default_user_avatar()) }}';
            document.getElementById('profile_image').value = '';
            isDefaultImage = true;
            // Hide trash bin when image is removed
            document.getElementById('deleteImageBtn').classList.remove('show');
        });

        // Handle form submission
        document.getElementById('updateProfileBtn').addEventListener('click', function(e) {
            e.preventDefault();

            // Reset validation
            $('.invalid-feedback').hide();
            $('input').removeClass('is-invalid');

            let valid = true;

            // Validate required fields
            const fieldsToValidate = [
                {
                    name: 'first_name',
                    errorElement: '#first_name_error'
                },
                {
                    name: 'last_name',
                    errorElement: '#last_name_error'
                }
            ];

            fieldsToValidate.forEach(field => {
                const value = $(`input[name="${field.name}"]`).val().trim();
                if (!value) {
                    $(field.errorElement).show();
                    $(`input[name="${field.name}"]`).addClass('is-invalid');
                    valid = false;
                }
            });

            // Validate email
            const emailInput = $('input[name="email"]');
            const emailValue = emailInput.val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailValue) {
                $('#email_error').text('Email field is required').show();
                emailInput.addClass('is-invalid');
                valid = false;
            } else if (!emailRegex.test(emailValue)) {
                $('#email_error').text('Please enter a valid email address').show();
                emailInput.addClass('is-invalid');
                valid = false;
            }

            // Validate mobile number
            const mobileValue = mobileInput.value.trim();
            if (!mobileValue) {
                $('#mobileInput').addClass('is-invalid');
                $('#mobile_error').show();
                valid = false;
            }

            // Validate gender
            const genderSelected = $('input[name="gender"]:checked').length > 0;
            if (!genderSelected) {
                $('#gender_error').show();
                valid = false;
            }

            if (!valid) {
                return;
            }

            const form = document.querySelector('.profile-form');
            const formData = new FormData(form);

            // Append full international number with spaces (e.g., +36 6798 689 768)
            const number = iti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
            formData.set('mobile', number);

            // Handle profile image
            if (isDefaultImage) {
                formData.append('remove_image', '1');
            } else {
                const image = document.getElementById('profile_image').files[0];
                if (image) {
                    formData.append('profile_image', image);
                }
            }

            // UI feedback
            const btn = this;
            btn.disabled = true;
            btn.textContent = '{{__("frontend.updating")}}';

            fetch("{{ route('profile.update') }}", {
                method: 'POST',
                body: formData,
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(async (res) => {
                const data = await res.json();
                if (!res.ok) throw data;

                    window.successSnackbar(data.message || 'Profile updated');

                    if (data.data) {
                        const user = data.data;
                        form.first_name.value = user.first_name || '';
                        form.last_name.value = user.last_name || '';
                        form.email.value = user.email || '';
                        // Set formatted number back into the input and plugin
                        const savedNumber = user.mobile || '';
                        try {
                            iti.setNumber(savedNumber);
                            // Ensure input shows INTERNATIONAL format
                            mobileInput.value = iti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
                        } catch (e) {
                            mobileInput.value = savedNumber;
                        }

                    if (user.gender) {
                        form.querySelector(`input[name="gender"][value="${user.gender}"]`).checked = true;
                    }

                    // Update profile dropdown contact number after successful save
                    updateProfileDropdownContact();

                        const imgElement = document.getElementById('profileImagePreview');

                        if (user.profile_image) {
                            // Method 1: Clear and set with cache-busting
                            imgElement.src = '';
                            setTimeout(() => {
                                const timestamp = new Date().getTime();
                                const imageUrl = user.profile_image + (user.profile_image.includes('?') ? '&' : '?') + 't=' + timestamp;
                                imgElement.src = imageUrl;

                                // Update header profile images as well
                                updateHeaderProfileImages(imageUrl);
                            }, 50);

                            // Method 2: Force reload after a short delay
                            setTimeout(() => {
                                const timestamp2 = new Date().getTime();
                                const baseUrl = user.profile_image.split('?')[0];
                                imgElement.src = baseUrl + '?t=' + timestamp2;

                                // Update header profile images again
                                updateHeaderProfileImages(baseUrl + '?t=' + timestamp2);
                            }, 200);

                            isDefaultImage = false;
                            // Show trash bin when user has a custom image
                            document.getElementById('deleteImageBtn').classList.add('show');
                        } else {
                            // Image was removed, set to default avatar
                            const defaultAvatarUrl = '{{ asset(default_user_avatar()) }}';
                            imgElement.src = defaultAvatarUrl;

                            // Update header profile images with default avatar
                            updateHeaderProfileImages(defaultAvatarUrl);

                            isDefaultImage = true;
                            // Hide trash bin when using default avatar
                            document.getElementById('deleteImageBtn').classList.remove('show');
                        }
                    }
                })
                .catch((err) => {
                    console.error(err);
                    window.errorSnackbar(err.message || 'Something went wrong');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = '{{__("frontend.save")}}';
                });
        });

        // Function to update header profile images
        function updateHeaderProfileImages(imageUrl) {
            // Update all header profile images
            const headerImages = document.querySelectorAll('.user-image, .dropdown-user-menu-image');
            headerImages.forEach(img => {
                if (img) {
                    img.src = imageUrl;
                }
            });
        }

        // Function to update profile dropdown contact number in real-time
        function updateProfileDropdownContact() {
            try {
                // Get the current full international number with spaces for display
                const fullNumber = iti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
                console.log('Updating profile dropdown with number:', fullNumber);
                
                // Update all profile dropdown contact numbers in the header
                const contactElements = document.querySelectorAll('.dropdown-user-menu .content .text-body.small:last-child');
                contactElements.forEach(element => {
                    if (element && fullNumber) {
                        element.textContent = fullNumber;
                        console.log('Updated contact element:', element.textContent);
                    }
                });
                
                // Also update any other contact number displays
                const allContactElements = document.querySelectorAll('[data-contact-number]');
                allContactElements.forEach(element => {
                    if (element && fullNumber) {
                        element.textContent = fullNumber;
                        element.setAttribute('data-contact-number', fullNumber);
                    }
                });
                
            } catch (error) {
                console.error('Error updating profile dropdown contact:', error);
            }
        }
    </script>
@endsection
