<div class="section-spacing">
    <div class="container">

        <div class="d-flex flex-column flex-md-row justify-content-between gap-lg-3 gap-2 mb-3">
            <h5 class="font-size-21-3 mb-0">{{ __('frontend.address_list') }}</h5>
            <div class="d-flex gap-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="search" class="form-control p-2" id="searchInput"
                        placeholder='{{ __('frontend.eg_john_new_york_12345') }}"'>
                </div>

                <button type="button" class="btn btn-primary p-2 flex-shrink-0" data-bs-toggle="modal"
                    data-bs-target="#addNewAddressModal">
                    {{ __('frontend.add_new_address') }}
                </button>

            </div>
        </div>

        {{-- Container where address cards will be rendered --}}
        <div id="addressCardContainer"></div>

        <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader list-inline">
            @for ($i = 0; $i < 3; $i++)
                @include('frontend::components.card.shimmer_address_card')
            @endfor
        </div>



        {{-- Hidden table for DataTables structure --}}
        <table id="address-cards-table" class="table d-none w-100">
            <thead>
                <tr>
                    <th>{{ __('frontend.card') }}</th>
                    <th>{{ __('frontend.name') }}</th> {{-- hidden column for search --}}
                </tr>
            </thead>
        </table>

        <!-- Add New Address Modal -->
        <div class="modal fade new-address-model" id="addNewAddressModal" tabindex="-1"
            aria-labelledby="addNewAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header pb-0">
                        <h6 class="font-size-21-3" id="addNewAddressModalLabel">{{ __('frontend.add_new_address') }}
                        </h6>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('frontend.address.store') }}" method="POST" novalidate>
                            @csrf
                            <div class="row gy-4">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="first_name"
                                            class="form-label fw-medium">{{ __('frontend.first_name') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="first_name" class="form-control" id="first_name"
                                                placeholder="eg. Michael"
                                                value="{{ old('first_name', auth()->user()->first_name ?? '') }}"
                                                />
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="first_name_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="last_name"
                                            class="form-label fw-medium">{{ __('frontend.last_name') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="last_name" class="form-control" id="last_name"
                                                placeholder="eg. Thompson"
                                                value="{{ old('last_name', auth()->user()->last_name ?? '') }}"
                                                />
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="last_name_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <label for="contact_number"
                                        class="form-label">{{ __('frontend.contact_number') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group position-relative">
                                        <input type="tel" id="mobileInput" name="contact_number"
                                            value="{{ old('mobile', auth()->user()->mobile ?? '') }}"
                                            class="form-control font-size-14">
                                        <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="contact_number_error"></div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="email"
                                            class="form-label fw-medium">{{ __('frontend.email') }}<span
                                                class="text-danger"></span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="email" name="email" class="form-control"
                                                value="{{ old('email', auth()->user()->email ?? '') }}" id="email"
                                                placeholder="eg. Thompson">
                                            <span class="input-group-text"><i class="ph ph-envelope-simple"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="email_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="country"
                                            class="form-label fw-medium">{{ __('frontend.country') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="country" id="country" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_country') }}</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
                                                        {{ old('country') == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="country_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="state"
                                            class="form-label fw-medium">{{ __('frontend.state') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="state" id="state" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_state') }}</option>
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="state_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="city"
                                            class="form-label fw-medium">{{ __('frontend.city') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="city" id="city" class="form-control">

                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_city') }}</option>
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>

                                        <div class="invalid-feedback" id="city_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="pin_code"
                                            class="form-label fw-medium">{{ __('frontend.pin_code') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="pin_code" class="form-control" id="pin_code"
                                                placeholder="eg. 900001" value="{{ old('pin_code') }}"
                                                pattern="^\d{6,7}$" maxlength="7" minlength="6"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_3134_40625)">
                                                        <path
                                                            d="M8 8.5C9.65685 8.5 11 7.15685 11 5.5C11 3.84315 9.65685 2.5 8 2.5C6.34315 2.5 5 3.84315 5 5.5C5 7.15685 6.34315 8.5 8 8.5Z"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M8 14V8.5" stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M2.5 14H13.5" stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_3134_40625">
                                                            <rect width="16" height="16" fill="white"
                                                                transform="translate(0 0.5)" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="pin_code_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="address"
                                            class="form-label fw-medium">{{ __('frontend.address') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="address" class="form-control" id="address"
                                                placeholder="eg. 123 Elm Street, Springfield"
                                                value="{{ old('address') }}" />
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_3134_40630)">
                                                        <path
                                                            d="M8 6.5C8.55228 6.5 9 6.05228 9 5.5C9 4.94772 8.55228 4.5 8 4.5C7.44772 4.5 7 4.94772 7 5.5C7 6.05228 7.44772 6.5 8 6.5Z"
                                                            fill="#A6A8A8" />
                                                        <path
                                                            d="M11.5 5.5C11.5 9 8 11 8 11C8 11 4.5 9 4.5 5.5C4.5 4.57174 4.86875 3.6815 5.52513 3.02513C6.1815 2.36875 7.07174 2 8 2C8.92826 2 9.8185 2.36875 10.4749 3.02513C11.1313 3.6815 11.5 4.57174 11.5 5.5Z"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path
                                                            d="M12.5 10.1963C13.7325 10.6513 14.5 11.2913 14.5 12C14.5 13.3807 11.59 14.5 8 14.5C4.41 14.5 1.5 13.3807 1.5 12C1.5 11.2913 2.2675 10.6513 3.5 10.1963"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_3134_40630">
                                                            <rect width="16" height="16" fill="white"
                                                                transform="translate(0 0.5)" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="address_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="set_as_primary"
                                            name="set_as_primary" {{ old('set_as_primary') ? 'checked' : '' }}>
                                        <label class="form-check-label"
                                            for="set_as_primary">{{ __('frontend.set_as_primary') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-5 pt-lg-4 pt-0">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                <button type="submit" class="btn btn-primary" id="add-address-save-btn">
                                    <span class="btn-text">{{ __('frontend.save') }}</span>
                                    <span class="spinner-border spinner-border-sm d-none ms-2" role="status"
                                        aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Address Modal -->
        <div class="modal fade new-address-model" id="editAddressModal" tabindex="-1"
            aria-labelledby="editAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header pb-0">
                        <h6 class="font-size-21-3" id="editAddressModalLabel">{{ __('frontend.edit_address') }}</h6>
                    </div>
                    <div class="modal-body">
                        <form id="editAddressForm" action="" method="POST" novalidate>
                            @csrf
                            @method('PUT')
                            <div class="row gy-4">
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_first_name"
                                            class="form-label fw-medium">{{ __('frontend.first_name') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="first_name" id="edit_first_name"
                                                class="form-control" placeholder="eg. Michael" />
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_first_name_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_last_name"
                                            class="form-label fw-medium">{{ __('frontend.last_name') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="last_name" id="edit_last_name"
                                                class="form-control" placeholder="eg. Thompson" />
                                            <span class="input-group-text"><i class="ph ph-user"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_last_name_error"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <label for="contact_number" class="form-label">Contact Number<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group position-relative">
                                        <input type="tel" id="edit_mobileInput" name="contact_number"
                                            value="" class="form-control font-size-14">
                                        <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="edit_contact_number_error"></div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="email" class="form-label fw-medium">Email<span
                                                class="text-danger"></span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="email" name="email" class="form-control" value=""
                                                id="edit_email" placeholder="eg. Thompson">
                                            <span class="input-group-text"><i
                                                    class="ph ph-envelope-simple"></i></span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_email_error"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_country"
                                            class="form-label fw-medium">{{ __('frontend.country') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="country" id="edit_country" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_country') }}</option>
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_country_error"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_state"
                                            class="form-label fw-medium">{{ __('frontend.state') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="state" id="edit_state" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_state') }}</option>
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_state_error"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_city"
                                            class="form-label fw-medium">{{ __('frontend.city') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <select name="city" id="edit_city" class="form-control">
                                                <option value="" disabled selected>
                                                    {{ __('frontend.select_city') }}</option>
                                            </select>
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        d="M13.5326 7.02976L8.53255 12.0298C8.46287 12.0997 8.38008 12.1552 8.28892 12.193C8.19775 12.2309 8.10001 12.2503 8.0013 12.2503C7.90259 12.2503 7.80485 12.2309 7.71369 12.193C7.62252 12.1552 7.53973 12.0997 7.47005 12.0298L2.47005 7.02976C2.32915 6.88886 2.25 6.69777 2.25 6.49851C2.25 6.29925 2.32915 6.10815 2.47005 5.96726C2.61095 5.82636 2.80204 5.74721 3.0013 5.74721C3.20056 5.74721 3.39165 5.82636 3.53255 5.96726L8.00193 10.4366L12.4713 5.96663C12.6122 5.82574 12.8033 5.74658 13.0026 5.74658C13.2018 5.74658 13.3929 5.82574 13.5338 5.96663C13.6747 6.10753 13.7539 6.29863 13.7539 6.49788C13.7539 6.69714 13.6747 6.88824 13.5338 7.02913L13.5326 7.02976Z"
                                                        fill="#A6A8A8" />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_city_error"></div>
                                    </div>
                                </div>

                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_pin_code"
                                            class="form-label fw-medium">{{ __('frontend.pin_code') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="pin_code" id="edit_pin_code"
                                                class="form-control" placeholder="eg. 900001" pattern="^\d{6,7}$"
                                                maxlength="7" minlength="6"
                                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_3134_40625)">
                                                        <path
                                                            d="M8 8.5C9.65685 8.5 11 7.15685 11 5.5C11 3.84315 9.65685 2.5 8 2.5C6.34315 2.5 5 3.84315 5 5.5C5 7.15685 6.34315 8.5 8 8.5Z"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M8 14V8.5" stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M2.5 14H13.5" stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_3134_40625">
                                                            <rect width="16" height="16" fill="white"
                                                                transform="translate(0 0.5)" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_pin_code_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <label for="edit_address"
                                            class="form-label fw-medium">{{ __('frontend.address') }}<span
                                                class="text-danger">*</span></label>
                                        <div class="input-group custom-input-group">
                                            <input type="text" name="address" id="edit_address"
                                                class="form-control" placeholder="eg. 123 Elm Street, Springfield"
                                                />
                                            <span class="input-group-text">
                                                <svg width="16" height="17" viewBox="0 0 16 17"
                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_3134_40630)">
                                                        <path
                                                            d="M8 6.5C8.55228 6.5 9 6.05228 9 5.5C9 4.94772 8.55228 4.5 8 4.5C7.44772 4.5 7 4.94772 7 5.5C7 6.05228 7.44772 6.5 8 6.5Z"
                                                            fill="#A6A8A8" />
                                                        <path
                                                            d="M11.5 5.5C11.5 9 8 11 8 11C8 11 4.5 9 4.5 5.5C4.5 4.57174 4.86875 3.6815 5.52513 3.02513C6.1815 2.36875 7.07174 2 8 2C8.92826 2 9.8185 2.36875 10.4749 3.02513C11.1313 3.6815 11.5 4.57174 11.5 5.5Z"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                        <path
                                                            d="M12.5 10.1963C13.7325 10.6513 14.5 11.2913 14.5 12C14.5 13.3807 11.59 14.5 8 14.5C4.41 14.5 1.5 13.3807 1.5 12C1.5 11.2913 2.2675 10.6513 3.5 10.1963"
                                                            stroke="#A6A8A8" stroke-width="1.5"
                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_3134_40630">
                                                            <rect width="16" height="16" fill="white"
                                                                transform="translate(0 0.5)" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="edit_address_error"></div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="edit_set_as_primary"
                                            name="set_as_primary">
                                        <label class="form-check-label"
                                            for="edit_set_as_primary">{{ __('frontend.set_as_primary') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-5 pt-lg-4 pt-0">
                                <button type="button" class="btn btn-primary"
                                    data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                <button type="submit" class="btn btn-secondary">{{ __('frontend.update') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
<script>
    var mobileInput = document.querySelector("#mobileInput");
    window.addIti = window.intlTelInput(mobileInput, {
        initialCountry: "in",
        separateDialCode: true,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
    });
    // If there is a pre-filled value (from old()/auth()), sync it into iti so flag matches
    if (mobileInput && mobileInput.value) {
        try { window.addIti.setNumber(mobileInput.value); } catch(e) {}
    }

    // Add digit-only validation for mobile input
    mobileInput.addEventListener('input', function(e) {
        var value = this.value;
        // Remove any non-digit characters except + (for country code)
        var cleanedValue = value.replace(/[^\d+]/g, '');

        // If the cleaned value is different from the original, update the input
        if (cleanedValue !== value) {
            this.value = cleanedValue;
        }
    });

    var edit_mobileInput = document.querySelector("#edit_mobileInput");
    window.editIti = window.intlTelInput(edit_mobileInput, {
        initialCountry: "in",
        separateDialCode: true,
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/utils.js"
    });

    // Add digit-only validation for mobile input
    edit_mobileInput.addEventListener('input', function(e) {
        var value = this.value;
        // Remove any non-digit characters except + (for country code)
        var cleanedValue = value.replace(/[^\d+]/g, '');

        // If the cleaned value is different from the original, update the input
        if (cleanedValue !== value) {
            this.value = cleanedValue;
        }
    });



    document.addEventListener('DOMContentLoaded', function() {
        const $table = $('#address-cards-table');
        const $container = $('#addressCardContainer');
        const shimmerLoader = document.querySelector('.shimmer-loader');

        const table = $table.DataTable({
            processing: false,
            serverSide: true,
            ajax: "{{ route('frontend.address.data') }}",
            columns: [{
                    data: 'card',
                    name: 'card',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name',
                    visible: false
                } // hidden but searchable

            ],
            pageLength: parseInt($('meta[name="data_table_limit"]').attr('content')) || 10,
            searching: true,
            lengthChange: false,
            pagingType: 'simple_numbers',
            dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
            language: {
                searchPlaceholder: '{{ __('frontend.search_addresses') }}',
                search: '',
                emptyTable: "<div class='text-center p-4'>{{ __('frontend.no_addresses_found') }}</div>",
                zeroRecords: "<div class='text-center p-4'>{{ __('frontend.no_matching_addresses_found') }}</div>",

            },
            drawCallback: function(settings) {
                const data = table.rows().data();
                $container.empty();

                if (data.length === 0) {
                    $container.append(
                        `<div class="text-center p-4">{{ __('frontend.no_data_available') }}</div>`
                    );
                } else {

                    for (let i = 0; i < data.length; i++) {
                        const cardHtml = data[i].card;
                        $container.append(cardHtml);
                    }

                }
            }
        });

        $('#searchInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        $('#searchInput').on('input', function() {
            if (this.value === '') {
                // Clear search and reset datatable
                table.search('').draw();
            }
        });

        // Show loader before AJAX
        table.on('preXhr.dt', function() {
            $('#addressCardContainer').empty();
            shimmerLoader.classList.remove('d-none');

        });

        // // Hide loader after data loads
        table.on('xhr.dt', function() {
            shimmerLoader.classList.add('d-none');

        });

        // Utility
        function loadDropdownData(url, targetSelect, placeholder, selectedValue = null) {
            targetSelect.innerHTML =
                `<option value="" disabled selected>{{ __('frontend.loading') }}</option>`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {

                    let options =
                        `<option value="" disabled ${!selectedValue ? 'selected' : ''}>${placeholder}</option>`;
                    data.forEach(item => {
                        options +=
                            `<option value="${item.id}" ${selectedValue == item.id ? 'selected' : ''}>${item.name}</option>`;
                    });
                    targetSelect.innerHTML = options;
                })
                .catch((error) => {
                    console.error(`Failed to load ${placeholder}:`, error);
                    targetSelect.innerHTML =
                        `<option value="" disabled selected>Failed to load ${placeholder}</option>`;
                });
        }

        function clearDropdown(...selects) {
            selects.forEach(sel => {
                sel.innerHTML =
                    `<option value="" disabled selected>{{ __('frontend.select') }}...</option>`;
            });
        }

        // Load Countries Initially
        const country = document.getElementById('country');
        const state = document.getElementById('state');
        const city = document.getElementById('city');

        const editCountry = document.getElementById('edit_country');
        const editState = document.getElementById('edit_state');
        const editCity = document.getElementById('edit_city');

        if (country) loadDropdownData(`{{ route('frontend.address.get-countries') }}`, country,
            'Select Country');
        if (editCountry) loadDropdownData(`{{ route('frontend.address.get-countries') }}`, editCountry,
            'Select Country');

        // Add Address Handler
        if (country) {
            country.addEventListener('change', function() {
                const id = this.value;
                clearDropdown(state, city);
                if (id) {
                    loadDropdownData(`{{ route('frontend.address.get-states') }}?country_id=${id}`,
                        state, 'Select State');
                }
            });
        }

        if (state) {
            state.addEventListener('change', function() {
                const id = this.value;
                clearDropdown(city);
                if (id) {
                    loadDropdownData(`{{ route('frontend.address.get-cities') }}?state_id=${id}`, city,
                        'Select City');
                }
            });
        }

        // Edit Address Handler
        if (editCountry) {
            editCountry.addEventListener('change', function() {
                const id = this.value;
                clearDropdown(editState, editCity);
                if (id) {
                    loadDropdownData(`{{ route('frontend.address.get-states') }}?country_id=${id}`,
                        editState, 'Select State');
                }
            });
        }

        if (editState) {
            editState.addEventListener('change', function() {
                const id = this.value;
                clearDropdown(editCity);
                if (id) {
                    loadDropdownData(`{{ route('frontend.address.get-cities') }}?state_id=${id}`,
                        editCity, 'Select City');
                }
            });
        }

        // On edit button click, you should fill in the current values (triggered externally)
        $(document).on('click', '.edit-address', function() {


            // Get values from data attributes
            const addressId = this.dataset.id;
            const firstName = this.dataset.first_name;
            const lastName = this.dataset.last_name;
            const pinCode = this.dataset.pin_code;
            const contactNumber = this.dataset.contact_number;
            const email = this.dataset.email;
            const address = this.dataset.address;
            const countryId = this.dataset.country;
            const stateId = this.dataset.state;
            const cityId = this.dataset.city;
            const isPrimary = this.dataset.is_primary;

            // Set form action
            document.getElementById('editAddressForm').action =
                `{{ route('frontend.address.update', '') }}/${addressId}`;

            // Fill text fields
            document.getElementById('edit_first_name').value = firstName;
            document.getElementById('edit_last_name').value = lastName;
            document.getElementById('edit_pin_code').value = pinCode;
            document.getElementById('edit_address').value = address;
            document.getElementById('edit_email').value = email;

            // Checkbox
            document.getElementById('edit_set_as_primary').checked = isPrimary == 1;

            // Set dropdown values
            const editCountry = document.getElementById('edit_country');
            const editState = document.getElementById('edit_state');
            const editCity = document.getElementById('edit_city');

            // Set country and load states
            editCountry.value = countryId;
            loadDropdownData(
                `{{ route('frontend.address.get-states') }}?country_id=${countryId}`,
                editState, 'Select State', stateId);

            // Delay city load after state is populated
            setTimeout(() => {
                loadDropdownData(
                    `{{ route('frontend.address.get-cities') }}?state_id=${stateId}`,
                    editCity, 'Select City', cityId);
            }, 300);

            // Set the phone number with correct country
            const editMobileInput = document.getElementById('edit_mobileInput');
            if (editMobileInput && window.editIti) {
                try {
                    if (contactNumber && contactNumber.trim().length) {
                        if (contactNumber.trim().startsWith('+')) {
                            window.editIti.setNumber(contactNumber.trim());
                        } else {
                            // Legacy stored number without '+' — try to coerce
                            window.editIti.setNumber('+' + contactNumber.trim());
                        }
                    } else {
                        window.editIti.setNumber('');
                    }
                } catch(e) {
                    editMobileInput.value = contactNumber || '';
                }
            }
        });

        // Delete Address Handler with SweetAlert2 (form button)
        $(document).on('click', '.delete-address-btn', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to delete this address?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('frontend.yes_delete_it') }}',
                cancelButtonText: '{{ __('frontend.cancel') }}',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                },
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $(document).on('submit', '#addNewAddressModal form', function(e) {
            e.preventDefault();
            var $form = $(this);

            // Clear previous errors
            $form.find('.invalid-feedback').text('').hide();
            $form.find('.is-invalid').removeClass('is-invalid');
            
            // Get the full number with country code from intl-tel-input
            if (window.addIti) {
                var fullNumber = window.addIti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
                // Update the input with the full number
                $form.find('input[name="contact_number"]').val(fullNumber);
            }
            
            // Client-side validation
            var hasError = false;
            function setError(fieldId, message) {
                var $input = $('#' + fieldId);
                $input.addClass('is-invalid');
                $('#' + fieldId + '_error').text(message).show();
                hasError = true;
            }

            var firstName = ($('#first_name').val() || '').trim();
            var lastName = ($('#last_name').val() || '').trim();
            var contactNumber = ($('#mobileInput').val() || '').trim();
            var emailVal = ($('#email').val() || '').trim();
            var countryVal = ($('#country').val() || '').trim();
            var stateVal = ($('#state').val() || '').trim();
            var cityVal = ($('#city').val() || '').trim();
            var pinCode = ($('#pin_code').val() || '').trim();
            var addressVal = ($('#address').val() || '').trim();

            if (!firstName) setError('first_name', "{{ __('frontend.first_name_field_is_required') }}");
            if (!lastName) setError('last_name', "{{ __('frontend.last_name_field_is_required') }}");
            if (!contactNumber) setError('mobileInput', "{{ __('frontend.contact_number_field_is_required') }}");
            if (emailVal && !/^\S+@\S+\.\S+$/.test(emailVal)) setError('email', "{{ __('frontend.enter_a_valid_email') }}");
            if (!countryVal) setError('country', "{{ __('frontend.please_select_a_country') }}");
            if (!stateVal) setError('state', "{{ __('frontend.please_select_a_state') }}");
            if (!cityVal) setError('city', "{{ __('frontend.please_select_a_city') }}");
            if (!pinCode) {
                setError('pin_code', "{{ __('frontend.pin_code_field_is_required') }}");
            } else if (!/^\d{6,7}$/.test(pinCode)) {
                setError('pin_code', "{{ __('frontend.pin_code_invalid') }}");
            }
            if (!addressVal) setError('address', "{{ __('frontend.address_field_is_required') }}");

            if (hasError) return false;

            var formData = $form.serialize();
            var $modal = $('#addNewAddressModal');
            var $loader = $('#address-loader-overlay');
            var $saveBtn = $('#add-address-save-btn');
            var $btnText = $saveBtn.find('.btn-text');
            var $btnSpinner = $saveBtn.find('.spinner-border');

            $saveBtn.prop('disabled', true);
            $btnSpinner.removeClass('d-none');
            $loader.show(); // Show overlay loader (optional)

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    $loader.hide();
                    $modal.modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    location.reload();
                },
                error: function(xhr) {
                    $loader.hide();
                    $saveBtn.prop('disabled', false);
                    $btnSpinner.addClass('d-none');

                    // Show validation errors under fields when 422
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            var fieldId = field;
                            // Map backend field names to input IDs if needed
                            if (field === 'contact_number') fieldId = 'mobileInput';
                            if (field === 'pin_code') fieldId = 'pin_code';
                            if (field === 'first_name') fieldId = 'first_name';
                            if (field === 'last_name') fieldId = 'last_name';
                            if (field === 'email') fieldId = 'email';
                            if (field === 'country') fieldId = 'country';
                            if (field === 'state') fieldId = 'state';
                            if (field === 'city') fieldId = 'city';
                            if (field === 'address') fieldId = 'address';

                            var $input = $('#' + fieldId);
                            var $err = $('#' + fieldId + '_error');
                            if ($input.length && $err.length) {
                                $input.addClass('is-invalid');
                                $err.text(messages[0]).show();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.error') }}',
                            text: '{{ __('frontend.failed_to_add_address') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'swal2-confirm btn btn-primary'
                            }
                        });
                    }
                }
            });
            return false;
        });

        // Handle edit form submission
        $(document).on('submit', '#editAddressForm', function(e) {
            e.preventDefault();
            var $form = $(this);

            // Clear previous errors
            $form.find('.invalid-feedback').text('').hide();
            $form.find('.is-invalid').removeClass('is-invalid');
            
            // Get the full number with country code from intl-tel-input
            var editMobileInput = document.querySelector("#edit_mobileInput");
            if (editMobileInput && window.editIti) {
                var fullNumber = window.editIti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
                // Update the hidden input with the full number
                $form.find('input[name="contact_number"]').val(fullNumber);
            }
            
            // Client-side validation
            var editHasError = false;
            function setEditError(fieldId, message) {
                var $input = $('#' + fieldId);
                $input.addClass('is-invalid');
                $('#' + fieldId + '_error').text(message).show();
                editHasError = true;
            }

            var eFirst = ($('#edit_first_name').val() || '').trim();
            var eLast = ($('#edit_last_name').val() || '').trim();
            var eContact = ($('#edit_mobileInput').val() || '').trim();
            var eEmail = ($('#edit_email').val() || '').trim();
            var eCountry = ($('#edit_country').val() || '').trim();
            var eState = ($('#edit_state').val() || '').trim();
            var eCity = ($('#edit_city').val() || '').trim();
            var ePin = ($('#edit_pin_code').val() || '').trim();
            var eAddress = ($('#edit_address').val() || '').trim();

            if (!eFirst) setEditError('edit_first_name', "{{ __('frontend.first_name_field_is_required') }}");
            if (!eLast) setEditError('edit_last_name', "{{ __('frontend.last_name_field_is_required') }}");
            if (!eContact) setEditError('edit_mobileInput', "{{ __('frontend.this_field_is_required') }}");
            if (eEmail && !/^\S+@\S+\.\S+$/.test(eEmail)) setEditError('edit_email', "{{ __('frontend.enter_a_valid_email') }}");
            if (!eCountry) setEditError('edit_country', "{{ __('frontend.please_select_a_country') }}");
            if (!eState) setEditError('edit_state', "{{ __('frontend.please_select_a_state') }}");
            if (!eCity) setEditError('edit_city', "{{ __('frontend.please_select_a_city') }}");
            if (!ePin) {
                setEditError('edit_pin_code', "{{ __('frontend.pin_code_field_is_required') }}");
            } else if (!/^\d{6,7}$/.test(ePin)) {
                setEditError('edit_pin_code', "{{ __('frontend.pin_code_invalid') }}");
            }
            if (!eAddress) setEditError('edit_address', "{{ __('frontend.address_field_is_required') }}");

            if (editHasError) return false;

            var formData = $form.serialize();
            
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                success: function(response) {
                    $('#editAddressModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    $('body').css('padding-right', '');
                    location.reload();
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            var fieldId = field;
                            if (field === 'contact_number') fieldId = 'edit_mobileInput';
                            if (field === 'pin_code') fieldId = 'edit_pin_code';
                            if (field === 'first_name') fieldId = 'edit_first_name';
                            if (field === 'last_name') fieldId = 'edit_last_name';
                            if (field === 'email') fieldId = 'edit_email';
                            if (field === 'country') fieldId = 'edit_country';
                            if (field === 'state') fieldId = 'edit_state';
                            if (field === 'city') fieldId = 'edit_city';
                            if (field === 'address') fieldId = 'edit_address';

                            var $input = $('#' + fieldId);
                            var $err = $('#' + fieldId + '_error');
                            if ($input.length && $err.length) {
                                $input.addClass('is-invalid');
                                $err.text(messages[0]).show();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.error') }}',
                            text: '{{ __('frontend.failed_to_update_address') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'swal2-confirm btn btn-primary'
                            }
                        });
                    }
                }
            });
            return false;
        });
    });
</script>
