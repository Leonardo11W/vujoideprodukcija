<div class="leave-section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-7 col-lg-8">
                <div class="bg-gray-800 p-3 p-md-4 rounded">
                    <div class="mb-5">
                        <h4>{{__("frontend.leave_form_title")}}</h4>
                        <p class="font-size-14">{{__("frontend.leave_form_subtitle")}}</p>
                    </div>
                    <!-- @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif -->
                    
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    <form action="{{ route('inquiry.store') }}" method="POST" id="inquiryForm">
    @csrf
    <div class="row gy-4">
        <div class="col-lg-6">
            <label class="form-label">{{ __("frontend.name") }} <span class="text-danger">*</span></label>
            <div class="input-group custom-input-group">
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __("frontend.placeholder_name") }}">
                <span class="input-group-text"><i class="ph ph-user"></i></span>
            </div>
        </div>

        <div class="col-lg-6">
            <label class="form-label">{{ __("frontend.email") }} <span class="text-danger">*</span></label>
            <div class="input-group custom-input-group">
                <input type="text" name="email" class="form-control" value="{{ old('email') }}" placeholder="{{ __("frontend.placeholder_email") }}">
                <span class="input-group-text"><i class="ph ph-envelope"></i></span>
            </div>
        </div>

        <div class="col-md-12">
            <label class="form-label">{{ __("frontend.subject") }} <span class="text-danger">*</span></label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="{{ __("frontend.placeholder_subject") }}">
        </div>

        <div class="col-md-12">
            <label class="form-label">{{ __("frontend.comment") }} <span class="text-danger">*</span></label>
            <textarea name="message" class="form-control" rows="5" placeholder="{{ __("frontend.placeholder_comment") }}">{{ old('message') }}</textarea>
        </div>

        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">{{ __("frontend.submit") }}</button>
        </div>
    </div>
</form>
                </div>
            </div>
            <div class="col-md-5 col-lg-4">
                <div class="mb-5 pb-lg-2">
                    <h4>{{__("frontend.leave_form_text")}}</h4>
                    <p>{{__("frontend.leave_form_description")}}</p>
                </div>

                @if(setting('helpline_number'))
                <div class="mb-5 pb-lg-2">
                    <p class="font-size-14 heading-color mb-1">{{__("frontend.leave_form_call")}}</p>
                    <a href="tel:{{setting('helpline_number')}}" class="text-decoration-none font-size-20">{{setting('helpline_number')}}</a>
                </div>
                @endif

                @if(setting('bussiness_address_line_1') || setting('bussiness_address_line_2'))
                <div class="mb-5 pb-lg-2">
                    <h5>Registered Salon address</h5>
                    <p>{{setting('bussiness_address_line_1')}} {{setting('bussiness_address_line_2')}} {{setting('bussiness_address_city')}}, {{setting('bussiness_address_state')}} {{setting('bussiness_address_postal_code')}}, {{setting('bussiness_address_country')}}</p>
                </div>
                @endif

                @php

                $footerSetting = \Modules\FrontendSetting\Models\FrontendSetting::where('type', 'footer-setting')->where('key', 'footer-setting')->first();
                $sectionValues = $footerSetting ? (is_array($footerSetting->value) ? $footerSetting->value : json_decode($footerSetting->value, true)) : [];

                @endphp
                @if (!empty($sectionValues['stayconnected']) && 
                    (!empty($sectionValues['social_links']['facebook']) || 
                     !empty($sectionValues['social_links']['instagram']) || 
                     !empty($sectionValues['social_links']['twitter']) || 
                     !empty($sectionValues['social_links']['youtube'])
                    ))

                <div class="">
                    <h5>{{__('frontend.follow_us')}}</h5>
                    <ul class="social-icons list-inline d-flex flex-wrap align-items-center gap-3">
                        @if (!empty($sectionValues['social_links']['facebook']))
                            <li>
                                <a href="{{ $sectionValues['social_links']['facebook'] }}" target="_blank">
                                    <i class="ph ph-facebook-logo align-middle"></i>
                                </a>
                            </li>
                        @endif
                        @if (!empty($sectionValues['social_links']['twitter']))
                            <li>
                                <a href="{{ $sectionValues['social_links']['twitter'] }}" target="_blank">
                                    <i class="ph ph-x align-middle"></i>
                                </a>
                            </li>
                        @endif
                        @if (!empty($sectionValues['social_links']['youtube']))
                        <li>
                            <a href="{{ $sectionValues['social_links']['youtube'] }}" target="_blank">
                                <i class="ph ph-youtube-logo align-middle"></i>
                            </a>
                        </li>
                        @endif
                    
                        @if (!empty($sectionValues['social_links']['instagram']))
                        <li>
                            <a href="{{ $sectionValues['social_links']['instagram'] }}" target="_blank">
                                <i class="ph ph-instagram-logo align-middle"></i>
                            </a>
                        </li>                  
                        @endif
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
 
@push('scripts')
<script>

document.getElementById('inquiryForm').addEventListener('submit', function(e) {
    e.preventDefault(); // stop form submit
    let isValid = true;

    // Remove old errors
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

    // Get form inputs
    let name = document.querySelector('[name="name"]');
    let email = document.querySelector('[name="email"]');
    let subject = document.querySelector('[name="subject"]');
    let message = document.querySelector('[name="message"]');

    // Validate name
    if (name.value.trim() === '') {
        showError(name, 'Name is required');
        isValid = false;
    }

    // Validate email
    if (email.value.trim() === '') {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        showError(email, 'Enter a valid email address');
        isValid = false;
    }

    // Validate subject
    if (subject.value.trim() === '') {
        showError(subject, 'Subject is required');
        isValid = false;
    }

    // Validate message
    if (message.value.trim() === '') {
        showError(message, 'Message is required');
        isValid = false;
    }

    // Submit if valid
    if (isValid) {
        this.submit();
    }
});

// Function to show error
function showError(input, message) {
    input.classList.add('is-invalid');
    let errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback d-block';
    errorDiv.textContent = message;

    // Place error message after the input or textarea
    if (input.parentNode.classList.contains('input-group')) {
        input.parentNode.parentNode.appendChild(errorDiv);
    } else {
        input.parentNode.appendChild(errorDiv);
    }
}

</script>

<style>
/* Hide error messages by default */
.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

/* Style for invalid inputs */
.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

/* Make sure the error message container is displayed */
.form-group .invalid-feedback,
.col-lg-6 .invalid-feedback,
.col-md-12 .invalid-feedback {
    display: block;
}
</style>
@endpush
