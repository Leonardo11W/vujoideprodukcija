<div class="top-header-box d-flex flex-column flex-md-row align-items-center justify-content-between mb-3">
    <h4 class="font-size-21-3 mb-0">{{ __('frontend.wallet_balance') }}</h4>
    <div class="d-flex align-items-center gap-lg-5 gap-3">
        <a href="#" class="btn btn-link font-size-16" data-bs-toggle="modal"
            data-bs-target="#withdrawModal">{{ __('frontend.withdrawal') }}</a>
        <a href="#" class="btn btn-link font-size-16" data-bs-toggle="modal"
            data-bs-target="#topUpModal">{{ __('frontend.top_up') }}</a>
    </div>
    <!-- Withdraw Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-labelledby="withdrawModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered pop-up-box">
            <div class="modal-content">
                <div class="modal-header pb-0 d-flex justify-content-between align-items-center">
                    <h3 class="modal-title font-size-21-3 mb-0" id="withdrawModalLabel">{{ __('frontend.withdrawal') }}</h3>
                    <button type="button" class="btn-close close-btn" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div
                        class="balance-section d-flex flex-column flex-md-row align-items-center justify-content-between rounded-3 p-3 mb-4">
                        <div>
                            <p class="mb-0">{{ __('frontend.total_balance') }}</p>
                        </div>
                        <h5 class="text-success">{{ \Currency::format(optional(auth()->user()->wallet)->amount) }}</h5>
                    </div>

                    <form id="withdrawForm" action="{{ route('wallet.withdraw') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="withdrawAmount" class="form-label">{{ __('frontend.enter_amount') }}</label>
                            <input type="number"  id="withdrawAmount" name="amount"
                                class="form-control @error('amount') is-invalid @enderror" placeholder="eg. $150">
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <div
                                class="heading-box d-flex
                            justify-content-between align-items-center">
                                <label for="chooseBank" class="form-label d-flex justify-content-between">
                                    {{ __('frontend.choose_bank') }}
                                </label>
                                <a href="#" class="btn btn-link font-size-12 fw-semibold"
                                    onclick="openBankModal(); return false;">{{ __('frontend.add_bank') }}</a>
                            </div>
                            <select id="chooseBank" class="form select2 @error('bank_id') is-invalid @enderror"
                                name="bank_id">
                                <option value="" disabled>{{ __('frontend.select_bank') }}</option>
                                @php $defaultBankId = $banks->firstWhere('is_default', 1)?->id; @endphp
                                @foreach ($banks as $bank)
                                    <option value="{{ $bank->id }}"
                                        @if ($defaultBankId == $bank->id) selected @endif>{{ $bank->bank_name }} -
                                        {{ substr($bank->account_no, -4) }}</option>
                                @endforeach
                            </select>
                            <div id="bank-error-feedback" class="invalid-feedback"></div>
                            @error('bank_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>


                    </form>
                </div>

                <div class="modal-footer d-flex flex-wrap align-items-center gap-lg-4 gap-2">
                    <button type="button" class="btn btn-secondary font-size-14 fw-semibold"
                        data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                    <button type="submit" form="withdrawForm"
                        class="btn btn-primary font-size-14 fw-semibold">{{ __('frontend.submit') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Top-Up Modal -->
    <div class="modal fade" id="topUpModal" tabindex="-1" aria-labelledby="topUpModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered pop-up-box">
            <div class="modal-content">
                <div class="modal-header pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="title-text mb-0" id="topUpModalLabel">{{ __('frontend.top_up') }}</h6>
                    <button type="button" class="btn-close close-btn" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div
                        class="balance-section d-flex flex-column flex-md-row align-items-center justify-content-between rounded-3 p-3 mb-5">
                        <div>
                            <p class="mb-0">{{ __('frontend.available_balance') }}</p>
                        </div>
                        <div class="balance-amount font-size-18 text-success">
                            {{ \Currency::format(optional(auth()->user()->wallet)->amount) }}</div>
                    </div>
                    <div class="mt-3">
                        <h6>{{ __('frontend.top_up_wallet') }}</h6>
                        <p class="font-size-12">{{ __('frontend.what_amount_would_you_prefer_to_top_up_with') }}
                        </p>
                        <div class="amount-box d-flex flex-column align-items-start justify-content-center rounded-2">
                            <div class="mb-3">
                                <label for="topUpAmount"
                                    class="form-label">{{ __('frontend.enter_or_selected_amount') }}</label>
                                <input type="number" class="form-control" id="topUpAmount" name="topUpAmount"
                                    min="1" placeholder="{{ \Currency::format(0) }}">
                                <span id="top-up-amount-error" class="text-danger"></span>
                            </div>

                            <div class="d-flex gap-2 mb-3">
                                <button id="" class="btn amt-btn font-size-14">{{ \Currency::format(150)}}</button>
                                <button class="btn amt-btn font-size-14">{{ \Currency::format(200)}}</button>
                                <button class="btn amt-btn font-size-14">{{ \Currency::format(500)}}</button>
                                <button class="btn amt-btn font-size-14">{{ \Currency::format(1000)}}</button>
                                <button class="btn amt-btn font-size-14">{{ \Currency::format(5000)}}</button>
                            </div>
                        </div>
                    </div>


                    @php
                        $is_stripe     = App\Models\Setting::get('str_payment_method');
                        $is_razorpay   = App\Models\Setting::get('razor_payment_method');
                    @endphp

                    @if ($is_stripe == 0 && $is_razorpay == 0)


                        <span class="text-danger mt-5"> {{ __('frontend.no_payment_methods_available') }}</span>
                    @else
                        <div class="mt-5">
                            <h6>{{ __('frontend.payment_method') }}</h6>
                            <p class="font-size-12">{{ __('frontend.select_your_payment_method_to_add_balance') }}</p>
                            <span id="top-up-amount-error" class="text-danger"></span>


                            <div class="payments-container bg-gray-800 rounded mt-3">
                                <a class="d-flex justify-content-between align-items-center gap-3 payments-show-list"
                                    href="#booking-payments-method" data-bs-toggle="collapse" aria-expanded="true">
                                    <div class="d-flex align-items-center gap-2">
                                        <img id="selected-payment-icon"
                                            src="{{ asset('img/frontend/stripe-payment.png') }}" alt="payment-method"
                                            class="img-fluid flex-shrink-0">
                                        <span id="selected-payment-text"
                                            class="flex-shrink-0 font-size-14 fw-medium heading-color">{{ __('frontend.payment_methods') }}</span>
                                    </div>
                                    <i class="ph ph-caret-down"></i>
                                </a>
                            </div>

                            <div id="booking-payments-method"
                                class="bg-gray-800 rounded booking-payment-method mt-3 collapse show">
                                @if ($is_stripe == 1)
                                    <div class="form-check payment-method-items p-0 d-flex justify-content-between align-items-center gap-3 payment-method-card"
                                        data-payment-method="Stripe">
                                        <label class="form-check-label d-flex gap-2 align-items-center w-100"
                                            for="method-Stripe">
                                            <img src="{{ asset('img/frontend/stripe.svg') }}" alt="Stripe"
                                                class="avatar avatar-20">
                                            <span class="h6 fw-semibold m-0">{{ __('frontend.stripe') }}</span>
                                        </label>
                                        <input class="form-check-input payment-radio" type="radio"
                                            name="payment_method" value="Stripe" id="method-Stripe">
                                    </div>
                                @endif



                                @if ($is_razorpay == 1)
                                    <div class="form-check payment-method-items p-0 d-flex justify-content-between align-items-center gap-3 payment-method-card"
                                        data-payment-method="Razorpay">
                                        <label class="form-check-label d-flex gap-2 align-items-center w-100"
                                            for="method-Razorpay">
                                            <img src="{{ asset('img/frontend/razorpay.svg') }}" alt="Razorpay"
                                                class="avatar avatar-20">
                                            <span class="h6 fw-semibold m-0">{{ __('frontend.razorpay') }}</span>
                                        </label>
                                        <input class="form-check-input payment-radio" type="radio"
                                            name="payment_method" value="Razorpay" id="method-Razorpay">
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    <span id="payment-method-error" class="text-danger"></span>
                </div>
                <div class="modal-footer d-flex flex-wrap align-items-center gap-lg-4 gap-2">
                    <button type="button" class="btn btn-secondary m-0"
                        data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                    <button type="button" id="proceedTopUp"
                        class="btn btn-primary m-0">{{ __('frontend.proceed') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="balance-section d-flex flex-column flex-md-row align-items-center justify-content-between rounded-3">
    <div>
        <p class="mb-0">{{ __('frontend.total_balance') }}</p>
    </div>
    <div class="balance-amount fs-4 text-success">{{ \Currency::format(optional(auth()->user()->wallet)->amount) }}
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                dropdownParent: $('#withdrawModal'),
                width: '100%',
                placeholder: '{{ __("frontend.select_bank") }}',
                language: {
                    noResults: function() {
                        return '{{ __("frontend.no_results_found") }}';
                    }
                }
            });

            // Auto-select Stripe when top-up modal opens
            $('#topUpModal').on('shown.bs.modal', function() {
                // Re-initialize payment method selection when modal opens
                setTimeout(function() {
                    initializePaymentMethodSelection();
                    
                    // Select Stripe radio button by default
                    const stripeRadio = document.querySelector('input[name="payment_method"][value="Stripe"]');
                    if (stripeRadio) {
                        stripeRadio.checked = true;
                        // Update dropdown header
                        updatePaymentMethodHeader('Stripe');
                    }
                }, 100);
            });

            // Handle form submission
            $('#withdrawForm').on('submit', function(e) {
                e.preventDefault(); 

                // Clear previous error messages
                $('.invalid-feedback').hide();
                $('.is-invalid').removeClass('is-invalid');
                $('#bank-error-feedback').text('').hide();

                // Validate form fields
                let isValid = true;
                let errorMessage = '';

                // Validate amount
                const amount = $('#withdrawAmount').val().trim();
                if (!amount) {
                    $('#withdrawAmount').addClass('is-invalid');
                    $('#withdrawAmount').after(
                        '<div class="invalid-feedback">{{ __('frontend.amount_is_required') }}</div>');
                    isValid = false;
                } else if (isNaN(amount) || parseFloat(amount) <= 0) {
                    $('#withdrawAmount').addClass('is-invalid');
                    $('#withdrawAmount').after(
                        '<div class="invalid-feedback">{{ __('frontend.amount_must_be_greater_than_0') }}</div>'
                    );
                    isValid = false;
                }

                // Validate bank selection
                const bankId = $('#chooseBank').val();
                if (!bankId) {
                    $('#chooseBank').addClass('is-invalid');
                    $('#bank-error-feedback').text('{{ __('frontend.please_select_a_bank') }}').show();
                    isValid = false;
                }

                if (!isValid) {
                    return false;
                }

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        // Close the modal
                        $('#withdrawModal').modal('hide');

                        // Show success snackbar
                        window.successSnackbar(
                            '{{ __('frontend.withdrawal_request_submitted_successfully') }}'
                        );

                        // Reset form
                        $('#withdrawForm')[0].reset();

                        // Reload page after a short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            // Handle validation errors by showing them as field errors
                            if (xhr.status === 422) {
                                // Clear previous errors
                                $('.invalid-feedback').hide();
                                $('.is-invalid').removeClass('is-invalid');
                                $('#bank-error-feedback').text('').hide();

                                // Show validation errors as field errors
                                if (xhr.responseJSON.errors) {
                                    $.each(xhr.responseJSON.errors, function(field, errors) {
                                        if (field === 'amount') {
                                            $('#withdrawAmount').addClass('is-invalid');
                                            $('#withdrawAmount').after(
                                                '<div class="invalid-feedback">' +
                                                errors[0] + '</div>');
                                        } else if (field === 'bank_id') {
                                            $('#chooseBank').addClass('is-invalid');
                                            $('#bank-error-feedback').text(errors[0])
                                                .show();
                                        }
                                    });
                                } else {
                                    // If no specific field errors, show general message
                                    $('#withdrawAmount').addClass('is-invalid');
                                    $('#withdrawAmount').after(
                                        '<div class="invalid-feedback">' + xhr.responseJSON
                                        .message + '</div>');
                                }
                            } else {
                                // For non-validation errors, show SweetAlert
                                $('#withdrawModal').modal('hide');
                                setTimeout(function() {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '{{ __('frontend.withdrawal_error') }}',
                                        text: xhr.responseJSON.message,
                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false,
                                    });
                                }, 400);
                            }
                        } else {
                            window.errorSnackbar(
                                '{{ __('frontend.error_submitting_withdrawal_request') }}');
                        }
                    }
                });
            });
        });
    </script>

    <script>
        document.querySelectorAll('.amt-btn').forEach(button => {
            button.addEventListener('click', function() {
                const selectedAmount = this.innerText;
                // Extract only the numeric value (remove currency symbol)
                const numericAmount = selectedAmount.replace(/[^\d]/g, '');

                const amountInput = document.getElementById('topUpAmount');
                // Set only the numeric value for number input
                amountInput.value = numericAmount;

                document.querySelectorAll('.amt-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.getElementById('proceedTopUp').addEventListener('click', function() {
            const amountValue = document.getElementById('topUpAmount').value.trim();
            // Extract only the numeric value (remove currency symbol) for backend
            const amount = amountValue.replace(/[^\d.]/g, '');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

            // Clear previous errors
            $('#top-up-amount-error').text('');

            $('#payment-method-error').text('');

            let isValid = true;

            if (!amount) {
                $('#top-up-amount-error').text('{{ __('frontend.amount_is_required') }}');
                isValid = false;
            } else if (isNaN(amount) || parseFloat(amount) <= 0) {
                $('#top-up-amount-error').text('{{ __('frontend.amount_must_be_greater_than_0') }}');
                isValid = false;
            }

            if (!paymentMethod) {
                $('#payment-method-error').text('{{ __('frontend.payment_method_is_required') }}');
                isValid = false;
            }

            if (!isValid) return;


            $.ajax({
                url: '{{ route('wallet.topup') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    amount: amount,
                    payment_method: paymentMethod
                },
                beforeSend: function() {
                    $('#proceedTopUp').prop('disabled', true).text(
                        '{{ __('frontend.processing') }}...');
                },
                success: function(response) {
                    $('#proceedTopUp').prop('disabled', false).text('{{ __('frontend.proceed') }}');

                    if (response.status && response.redirect_url) {

                        if (paymentMethod == 'Stripe') {

                            window.location.href = response.redirect_url;

                        } else if (paymentMethod == 'Razorpay') {

                            openRazorpay(response)

                        } else {

                            $('#topUpModal').modal('hide');
                            Swal.fire({
                                title: '{{ __('frontend.error') }}',
                                text: response.message ||
                                    '{{ __('frontend.payment_failed') }}',
                                icon: 'error',
                                confirmButtonText: '{{ __('frontend.ok') }}',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },

                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ route('wallet') }}';
                                }
                            });

                        }

                    } else {
                        $('#topUpModal').modal('hide');
                        Swal.fire({
                            title: '{{ __('frontend.error') }}',
                            text: response.message || '{{ __('frontend.payment_failed') }}',
                            icon: 'error',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route('wallet') }}';
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    $('#topUpModal').modal('hide');
                    $('#proceedTopUp').prop('disabled', false).text('{{ __('frontend.proceed') }}');

                    // Improved error handling with detailed information
                    let errorMessage = '{{ __('frontend.server_error') }}';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.status === 500) {
                        errorMessage = '{{ __('frontend.internal_server_error') }}';
                    } else if (xhr.status === 422) {
                        errorMessage = '{{ __('frontend.validation_error') }}';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Service not found. Please contact support.';
                    }

                    console.error('TopUp Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });

                    Swal.fire({
                        title: '{{ __('frontend.error') }}',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = '{{ route('wallet') }}';
                        }
                    });
                }
            });
        });

        // Initialize payment method selection with proper error handling and timing
        window.initializePaymentMethodSelection = function() {
            try {
                // Wait for elements to be available
                const maxRetries = 10;
                let retryCount = 0;
                
                function tryInitialize() {
                    const paymentCards = document.querySelectorAll('.payment-method-card');
                    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
                    const iconElement = document.getElementById('selected-payment-icon');
                    const textElement = document.getElementById('selected-payment-text');
                    
                    // Check if all required elements are available
                    if (paymentCards.length === 0 || paymentRadios.length === 0 || !iconElement || !textElement) {
                        if (retryCount < maxRetries) {
                            retryCount++;
                            setTimeout(tryInitialize, 100);
                            return;
                        } else {
                            console.warn('Payment method elements not found after maximum retries');
                            return;
                        }
                    }
                    
                    // Set default payment method
                    const defaultPayment = document.querySelector('input[name="payment_method"][value="Stripe"]');
                    if (defaultPayment) {
                        defaultPayment.checked = true;
                    }

                    // Add click handlers for payment method cards
                    paymentCards.forEach(card => {
                        card.addEventListener('click', function(e) {
                            // Don't trigger if clicking on the radio button itself
                            if (e.target.type === 'radio') {
                                return;
                            }

                            // Find the radio button within this card
                            const radio = this.querySelector('input[type="radio"]');
                            if (radio) {
                                radio.checked = true;

                                // Remove active class from all cards
                                document.querySelectorAll('.payment-method-card').forEach(c => {
                                    c.classList.remove('active');
                                });

                                // Add active class to clicked card
                                this.classList.add('active');

                                // Immediately update dropdown header
                                updatePaymentMethodHeader(radio.value);
                            }
                        });
                    });

                    // Handle radio button change to update card styling and dropdown header
                    paymentRadios.forEach(radio => {
                        radio.addEventListener('change', function() {
                            // Remove active class from all cards
                            document.querySelectorAll('.payment-method-card').forEach(card => {
                                card.classList.remove('active');
                            });

                            // Add active class to the card containing the checked radio
                            if (this.checked) {
                                const card = this.closest('.payment-method-card');
                                if (card) {
                                    card.classList.add('active');
                                }

                                // Update dropdown header
                                updatePaymentMethodHeader(this.value);
                            }
                        });

                        // Also add click event for immediate response
                        radio.addEventListener('click', function() {
                            // Remove active class from all cards
                            document.querySelectorAll('.payment-method-card').forEach(card => {
                                card.classList.remove('active');
                            });

                            // Add active class to the card containing the clicked radio
                            const card = this.closest('.payment-method-card');
                            if (card) {
                                card.classList.add('active');
                            }

                            // Immediately update dropdown header
                            updatePaymentMethodHeader(this.value);
                        });
                    });

                    // Set initial active state
                    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
                    if (checkedRadio) {
                        const card = checkedRadio.closest('.payment-method-card');
                        if (card) {
                            card.classList.add('active');
                        }
                        // Update dropdown header with initial selection
                        updatePaymentMethodHeader(checkedRadio.value);
                    }
                }
                
                tryInitialize();
                
            } catch (error) {
                console.error('Error initializing payment method selection:', error);
            }
        }

        // Function to update payment method header
        window.updatePaymentMethodHeader = function(selectedMethod) {
            try {
                const iconElement = document.getElementById('selected-payment-icon');
                const textElement = document.getElementById('selected-payment-text');

                if (!iconElement || !textElement) {
                    console.warn('Payment method header elements not found');
                    return;
                }

                if (selectedMethod === 'Stripe') {
                    iconElement.src = "{{ asset('img/frontend/stripe-payment.png') }}";
                    textElement.textContent = "{{ __('frontend.stripe') }}";
                } else if (selectedMethod === 'Razorpay') {
                    iconElement.src = "{{ asset('img/frontend/razorpay.svg') }}";
                    textElement.textContent = "{{ __('frontend.razorpay') }}";
                }
            } catch (error) {
                console.error('Error updating payment method header:', error);
            }
        }

        // Initialize when DOM is ready using jQuery (more reliable than DOMContentLoaded)
        $(document).ready(function() {
            initializePaymentMethodSelection();
        });

        function openRazorpay(options) {

            var razorpay = new Razorpay({
                key: options.key,
                amount: options.amount, // Backend now sends the correct amount in smallest unit
                currency: options.formattedCurrency,
                name: options.name,
                description: 'Wallet Top-Up',
                order_id: options.order_id,
                handler: function(response) {

                    axios.post(options.redirect_url, {
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        transaction_id: options.transaction_id
                    }).then(res => {
                        window.location.reload();
                    }).catch(err => {
                        Swal.fire({
                            title: '{{ __('frontend.error') }}',
                            text: '{{ __('frontend.payment_failed') }}',
                            icon: 'error',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '{{ route('wallet') }}';
                            }
                        });
                    });
                },
                prefill: {
                    name: options.name,
                    email: options.email,
                    contact: options.contact
                },
                theme: {
                    color: "#0D6EFD"
                }
            });

            try {
                razorpay.open();
            } catch (error) {
                console.error('Razorpay error:', error);
                Swal.fire({
                    title: '{{ __('frontend.error') }}',
                    text: '{{ __('frontend.payment_gateway_error') }}',
                    icon: 'error',
                    confirmButtonText: '{{ __('frontend.ok') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route('wallet') }}';
                    }
                });
            }
        }

        function openBankModal() {
            // Reset the form first
            resetModalForAdd();

            // Temporarily hide the withdraw modal
            $('#withdrawModal').modal('hide');

            // Remove any existing backdrop that might interfere
            $('.modal-backdrop').remove();

            // Open the bank modal
            $('#bankInfoModal').modal('show');

            // Ensure modal is properly positioned and interactive
            setTimeout(function() {
                $('#bankInfoModal input:first').focus();
                // Force enable all form elements
                $('#bankInfoModal input, #bankInfoModal select, #bankInfoModal textarea, #bankInfoModal button')
                    .prop('disabled', false);
            }, 200);
        }

        function resetModalForAdd() {
            // Reset form fields
            $('#bankForm')[0].reset();
            $('#bankId').val('');
            $('#modalTitle').text('{{ __('Bank.add_bank') }}');

            // Clear error messages
            $('.invalid-feedback').hide();
            $('.is-invalid').removeClass('is-invalid');

            // Reset submit button
            $('#submitButton').text('{{ __('Bank.save') }}');
        }

        function closeBankModal() {
            // Close the bank modal
            $('#bankInfoModal').modal('hide');

            // Show the withdraw modal again
            setTimeout(function() {
                $('#withdrawModal').modal('show');
            }, 200);
        }

        // Handle bank form submission and modal events
        $(document).ready(function() {
            // Ensure bank modal is interactive when shown
            $('#bankInfoModal').on('shown.bs.modal', function() {
                $(this).find('input:first').focus();
                $(this).find('input, select, textarea, button').prop('disabled', false);
            });

            $('#bankForm').on('submit', function(e) {
                e.preventDefault();

                // Clear previous client-side errors
                $('.invalid-feedback').hide();
                $('.is-invalid').removeClass('is-invalid');

                // Client-side validation for all required fields
                let hasError = false;

                const branchName = $('#branch_name').val() ? $('#branch_name').val().trim() : '';
                const bankName = $('#bank_name').val() ? $('#bank_name').val().trim() : '';
                const accountNo = $('#account_no').val() ? $('#account_no').val().trim() : '';
                const ifscValue = $('#ifsc_no').val() ? $('#ifsc_no').val().trim() : '';

                if (!branchName) {
                    $('#branch_name_error').text('This field is required').show();
                    $('#branch_name').addClass('is-invalid');
                    hasError = true;
                }

                if (!bankName) {
                    $('#bank_name_error').text('This field is required').show();
                    $('#bank_name').addClass('is-invalid');
                    hasError = true;
                }

                if (!accountNo) {
                    $('#account_no_error').text('Account number is required').show();
                    $('#account_no').addClass('is-invalid');
                    hasError = true;
                } else if (!/^\d+$/.test(accountNo)) {
                    $('#account_no_error').text('Account number must be numeric').show();
                    $('#account_no').addClass('is-invalid');
                    hasError = true;
                }

                if (!ifscValue) {
                    $('#ifsc_no_error').text('IFSC code is required').show();
                    $('#ifsc_no').addClass('is-invalid');
                    hasError = true;
                }

                if (hasError) {
                    return;
                }

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.status) {
                            // Close bank modal
                            $('#bankInfoModal').modal('hide');

                            // Show success message
                            window.successSnackbar(
                                '{{ __('frontend.bank_added_successfully') }}');

                            // Show withdraw modal again
                            setTimeout(function() {
                                $('#withdrawModal').modal('show');

                                // Update bank list without page reload
                                $.ajax({
                                    url: '{{ route('wallet') }}',
                                    method: 'GET',
                                    success: function(htmlResponse) {
                                        // Extract the bank select options from the response
                                        const tempDiv = $('<div>').html(
                                            htmlResponse);
                                        const newBankSelect = tempDiv.find(
                                            '#chooseBank');

                                        if (newBankSelect.length > 0) {
                                            // Update the bank select options
                                            $('#chooseBank').html(
                                                newBankSelect.html());

                                            // Refresh select2
                                            $('#chooseBank').select2(
                                                'destroy').select2({
                                                dropdownParent: $(
                                                    '#withdrawModal'
                                                ),
                                                width: '100%',
                                                placeholder: '{{ __("frontend.select_bank") }}',
                                                language: {
                                                    noResults: function() {
                                                        return '{{ __("frontend.no_results_found") }}';
                                                    }
                                                }
                                            });

                                            // Select the newly added bank (first option after the placeholder)
                                            $('#chooseBank option:eq(1)')
                                                .prop('selected', true);
                                            $('#chooseBank').trigger(
                                                'change');
                                        }
                                    },
                                    error: function() {
                                        // If AJAX fails, reload the page as fallback
                                        setTimeout(function() {
                                            window.location
                                                .reload();
                                        }, 1000);
                                    }
                                });
                            }, 200);
                        } else {
                            // Show error message
                            window.errorSnackbar(response.message ||
                                '{{ __('frontend.error_adding_bank') }}');
                        }
                    },
                    error: function(xhr) {
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Clear previous errors
                            $('.invalid-feedback').hide();
                            $('.is-invalid').removeClass('is-invalid');

                            // Show validation errors
                            $.each(xhr.responseJSON.errors, function(field, errors) {
                                $('#' + field + '_error').text(errors[0]).show();
                                $('#' + field).addClass('is-invalid');
                            });
                        } else {
                            window.errorSnackbar('{{ __('frontend.error_adding_bank') }}');
                        }
                    }
                });
            });
        });
    </script>
@endpush

<style>
    .swal2-topup-zindex {
        z-index: 20000 !important;
    }

    /* Ensure bank modal is properly styled */
    #bankInfoModal {
        z-index: 1055 !important;
    }

    #bankInfoModal .modal-dialog {
        z-index: 1056 !important;
    }

    #bankInfoModal .modal-content {
        z-index: 1057 !important;
    }

    /* Ensure form elements are interactive */
    #bankInfoModal input,
    #bankInfoModal select,
    #bankInfoModal textarea,
    #bankInfoModal button {
        pointer-events: auto !important;
    }

    /* Payment method card styles */
    .payment-method-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
    }

    /* .payment-method-card:hover {
    background-color: rgba(13, 110, 253, 0.05);
    border-color: rgba(13, 110, 253, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.payment-method-card.active {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
} */

    .payment-method-card .form-check-input {
        margin-right: 0;
    }

    .payment-method-card .form-check-label {
        cursor: pointer;
        margin-bottom: 0;
    }

    /* Modal close button positioning */
    .modal-header .btn-close {
        margin: 0;
        padding: 0.5rem;
        background-size: 1rem;
        opacity: 0.7;
    }

    .modal-header .btn-close:hover {
        opacity: 1;
    }

    .modal-header .btn-close:focus {
        box-shadow: none;
    }
</style>

<!-- Add Bank Modal (copied from bank_list.blade.php) -->
<div class="modal fade" id="bankInfoModal" tabindex="-1" aria-labelledby="bankInfoModalLabel" aria-hidden="true"
    data-bs-backdrop="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="bankForm" method="POST" action="{{ route('bank.store') }}">
                @csrf
                <input type="hidden" id="bankId" name="bank_id">
                <div class="modal-body">
                    <h6 id="modalTitle" class="font-size-21-3 mb-3">{{ __('Bank.add_bank') }}</h6>
                    <div class="row gy-4">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="branch_name"
                                    class="form-label fw-medium">{{ __('Bank.branch_name') }}</label>
                                <span class="text-danger">*</span>
                                <div class="input-group custom-input-group">
                                    <input type="text" name="branch_name" id="branch_name" class="form-control"
                                        placeholder="{{ __('Bank.placeholder_branch_name') }}" />
                                    <span class="input-group-text"><i class="ph ph-piggy-bank"></i></span>
                                </div>
                                <div class="invalid-feedback" id="branch_name_error"></div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="bank_name"
                                    class="form-label fw-medium">{{ __('Bank.bank_name') }}</label>
                                <span class="text-danger">*</span>
                                <div class="input-group custom-input-group">
                                    <input type="text" name="bank_name" id="bank_name" class="form-control"
                                        placeholder="{{ __('Bank.placeholder_bank_name') }}" />
                                    <span class="input-group-text"><i class="ph ph-piggy-bank"></i></span>
                                </div>
                                <div class="invalid-feedback" id="bank_name_error"></div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="account_no"
                                    class="form-label fw-medium">{{ __('Bank.account_number') }}</label>
                                <span class="text-danger">*</span>
                                <div class="input-group custom-input-group">
                                    <input type="text" name="account_no" id="account_no" class="form-control"
                                        placeholder="{{ __('Bank.placeholder_account_number') }}" />
                                    <span class="input-group-text"><i class="ph ph-dots-three-circle"></i></span>
                                </div>
                                <div class="invalid-feedback" id="account_no_error"></div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="ifsc_no" class="form-label fw-medium">{{ __('Bank.ifsc_code') }}</label>
                                <span class="text-danger">*</span>
                                <div class="input-group custom-input-group">
                                    <input type="text" name="ifsc_no" id="ifsc_no" class="form-control"
                                        placeholder="{{ __('Bank.eg_SBIN5642310') }}" />
                                    <span class="input-group-text"><i class="ph ph-user"></i></span>
                                </div>
                                <div class="invalid-feedback" id="ifsc_no_error"></div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group d-flex justify-content-between align-items-center">
                                <label for="status"
                                    class="form-label fw-medium mb-0">{{ __('Bank.status') }}</label>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="status" name="status"
                                        value="active" checked>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="d-flex align-items-center justify-content-end gap-lg-4 gap-2 flex-wrap mt-5 pt-lg-3 pt-0">
                        <button type="button" class="btn btn-primary"
                            onclick="closeBankModal()">{{ __('Bank.cancel') }}</button>
                        <button type="submit" id="submitButton"
                            class="btn btn-secondary">{{ __('Bank.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
