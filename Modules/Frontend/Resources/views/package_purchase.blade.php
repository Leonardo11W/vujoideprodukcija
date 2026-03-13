@extends('frontend::layouts.master')

@section('content')

@php
    $totalServicePrice = $package->serviceItems->sum(function($service) {
        return $service->pivot->service_price * $service->pivot->qty;
    });

    // Calculate tax amount based on type
    $totalTaxAmount = 0;
    $taxDetails = [];

    foreach($tax as $taxItem) {
        if($taxItem->type === 'percent') {
            $taxAmount = ($package->package_price * $taxItem->value) / 100;
        } else {
            $taxAmount = $taxItem->value;
        }
        $totalTaxAmount += $taxAmount;
        $taxDetails[] = [
            'title' => $taxItem->title,
            'type' => $taxItem->type,
            'value' => $taxItem->value,
            'amount' => $taxAmount
        ];
    }
@endphp

<section class="payment-section section-spacing-inner-pages">
    <div class="container">
        <div class="row">
            <!-- Left Section -->
            <div class="col-lg-8">
                <h6>Selected Package</h6>
                <div class="d-flex justify-content-between selected-package-card">
                    <div>
                        <h6>{{ $package->name }}</h6>
                        <span>
                            <span class="package-price text-primary fw-semibold">{{ Currency::format($package->package_price) }}</span>
                            @if($totalServicePrice > $package->package_price)
                            <span class="package-old-price text-decoration-line-through fw-bold">{{ Currency::format($totalServicePrice) }}</span>
                            @endif
                            <span class="month text-secondary">/ {{ $package->package_validity ?? 1 }} month{{ $package->package_validity > 1 ? 's' : '' }}</span>
                        </span>
                    </div>
                    <div>
                        @if($totalServicePrice > $package->package_price)
                        <div class="badge rounded-pill text-bg-success package-discount font-size-14">{{ round((($totalServicePrice - $package->package_price) / $totalServicePrice) * 100) }}% OFF</div>
                        @endif
                    </div>
                </div>
                <!-- Choose Payment Method Section -->
                <div>
                    <h5>Choose Payment Method</h5>

                    <div class="payments-container bg-gray-800 rounded mt-3">
                        <a class="d-flex justify-content-between align-items-center gap-3 payments-show-list" href="#booking-payments-method" data-bs-toggle="collapse" aria-expanded="true">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ asset('img/frontend/stripe.svg') }}" alt="stripe-payment" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Stripe</span>
                            </div>
                            <i class="ph ph-caret-down"></i>
                        </a>
                    </div>
                    <div id="booking-payments-method" class="bg-gray-800 rounded booking-payment-method mt-3 collapse show">
                        {{-- <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-cash">
                                <img src="{{asset ('img/frontend/cash.svg')}}" alt="cash" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Cash</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="cash">
                        </div> --}}
                        @if(setting('str_payment_method') == 1)
                            <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                                <label class="form-check-label d-flex gap-2 align-items-center" for="method-Stripe">
                                    <img src="{{asset ('img/frontend/stripe.svg')}}" alt="Stripe" class="flex-shrink-0 avatar avatar-18">
                                    <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Stripe</span>
                                </label>
                                <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Stripe">
                            </div>
                        @endif
                        @if(setting('razor_payment_method') == 1)
                            <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                                <label class="form-check-label d-flex gap-2 align-items-center" for="method-razorpay">
                                    <img src="{{asset ('img/frontend/stripe.svg')}}" alt="Stripe" class="flex-shrink-0 avatar avatar-18">
                                    <span class="flex-shrink-0 font-size-14 fw-medium heading-color">RazorPay</span>
                                </label>
                                <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Razorpay">
                            </div>
                        @endif
                        @if(setting('paystack_payment_method') == 1)
                            <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                                <label class="form-check-label d-flex gap-2 align-items-center" for="method-Paystack">
                                    <img src="{{asset ('img/frontend/paystack.svg')}}" alt="Paystack" class="flex-shrink-0 avatar avatar-18">
                                    <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Paystack</span>
                                </label>
                                <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Paystack">
                            </div>
                        @endif
                        @if(setting('paypal_payment_method') == 1)
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-PayPal">
                                <img src="{{asset ('img/frontend/paypal.svg')}}" alt="PayPal" class="flex-shrink-0 avatar avatar-18">
                                    <span class="flex-shrink-0 font-size-14 fw-medium heading-color">PayPal</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="PayPal">
                        </div>
                        @endif
                        @if(setting('flutterwave_payment_method') == 1)
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Flutterwave">
                                <img src="{{asset ('img/frontend//flutterwave.svg')}}" alt="Flutterwave" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Flutterwave</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Flutterwave">
                        </div>
                        @endif
                    </div>
                    <p class="text-primary mt-2 mb-0"><i class="ph ph-info align-middle"></i> &nbsp; No online Payment is available. Pay in salon after appointment</p>
                </div>
            </div>
            <!-- Payment Details Section -->
            <div class="col-lg-4 mt-lg-0">
                <!-- Coupon Section -->
                <div class="coupon-wrap">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                        <h5 class="mb-0">Available Coupons</h5>
                        <button class="font-size-14 btn btn-link" data-bs-toggle="modal" data-bs-target="#coupon-modal">View All</button>
                    </div>
                    <div class="input-group coupon-input-group mb-3">
                        <input type="text" class="form-control coupon-input" placeholder="Enter coupon code" aria-label="Coupon code" aria-describedby="coupon-addon">
                        <span class="input-group-text coupon-icon" id="coupon-addon">
                            <i class="ph ph-seal-percent"></i>
                        </span>
                    </div>
                </div>
                <div class="payment-container" id="payment-container">
                    <h6>Payment Detail</h6>
                    <!-- Payment Summary -->
                    <div class="payment-summary">
                        <!-- Package Name and Price -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>
                                <span class="font-size-14">{{ $package->name }}</span>
                                @if($totalServicePrice > $package->package_price)
                                <span class="badge rounded-pill bg-success font-size-10">{{ round((($totalServicePrice - $package->package_price) / $totalServicePrice) * 100) }}% OFF</span>
                                @endif
                            </div>
                            <span class="font-size-14 fw-medium heading-color">{{ Currency::format($totalServicePrice) }}</span>
                        </div>
                        <hr class="line-divider" />
                        <!-- Coupon -->
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Coupon</span>
                            <span class="font-size-14 text-danger fw-medium">{{ Currency::format(0) }}</span>
                        </div>

                        <!-- Discount -->
                        @if($totalServicePrice > $package->package_price)
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Discount <span class="text-success font-size-12">({{ round((($totalServicePrice - $package->package_price) / $totalServicePrice) * 100) }}%)</span></span>
                            <span class="font-size-14 text-success fw-medium">{{ Currency::format($totalServicePrice - $package->package_price) }}</span>
                        </div>
                        @endif

                        <!-- Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Subtotal</span>
                            <span class="font-size-14 fw-medium heading-color">{{ Currency::format($package->package_price) }}</span>
                        </div>

                        <!-- Tax (collapsible) -->
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Tax</span>
                            <span class="d-flex align-items-center font-size-14 gap-lg-2 gap-1 taxDetails"  data-bs-toggle="collapse" href="#taxDetails" role="button" aria-expanded="true" aria-controls="taxDetails">
                                <i class="ph ph-caret-down"></i>
                                <span class="font-size-14 text-danger fw-medium">{{ Currency::format($totalTaxAmount) }}</span>
                            </span>
                        </div>

                        <div class="collapse show mt-2 mb-2" id="taxDetails">
                            <div class="text-calculate card py-2 px-3">
                                @foreach($taxDetails as $taxItem)
                                <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-1' : '' }}">
                                    <span class="font-size-12">{{ $taxItem['title'] }}
                                        @if($taxItem['type'] === 'percent')
                                        ({{ $taxItem['value'] }}%)
                                        @endif
                                    </span>
                                    <span class="text-danger fw-medium font-size-12">{{ Currency::format($taxItem['amount']) }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="line-divider" />

                        <!-- Total -->
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total</span>
                            <span class="total-value fw-semibold text-primary">{{ Currency::format($package->package_price + $totalTaxAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="onclick-page-redirect bg-orange p-3 d-none" id="submit-button-container">
    <div class="container">
        <div class="text-end">
            @if(auth()->check())
              
                <button class="btn btn-primary px-5" data-bs-target="#exampleModalToggle" data-bs-toggle="modal">Submit</button>
            @else
        
                <button class="btn btn-primary px-5" id="trigger-login-modal">Submit</button>
            @endif
        </div>
    </div>
</div>

<!-- Coupon Modal -->
<div class="modal fade coupon-modal" id="coupon-modal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
        <div class="modal-content bg-gray-900 rounded">
            <div class="modal-body">
                <h5 class="mb-0">Apply Coupons</h5>
                <div class="coupon-container">
                    <div class="coupon-card rounded bg-purple d-flex align-items-sm-center flex-sm-row flex-column p-3 gap-3 mt-3">
                        <div class="flex-shrink-0">
                            <input class="form-check-input form-check-secondary" type="checkbox" id="coupon1" value="" aria-label="..." checked>
                        </div>
                        <div class="d-flex align-items-start jutify-content-between gap-3 flex-grow-1">
                            <div class="flex-grow-1">
                                <p class="font-size-14 mt-0 mb-1">Get 10% off on first booking</p>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="font-size-14">use code:</span>
                                    <span class="heading-color">FIRST50</span>
                                </div>
                            </div>
                            <button class="btn btn-link text-success font-size-12">Applied</button>
                        </div>
                    </div>
                    <div class="coupon-card rounded bg-gray-800 d-flex align-items-sm-center flex-sm-row flex-column p-3 gap-3 mt-3">
                        <div class="flex-shrink-0">
                            <input class="form-check-input form-check-secondary" type="checkbox" id="coupon1" value="" aria-label="...">
                        </div>
                        <div class="d-flex align-items-start jutify-content-between gap-3 flex-grow-1">
                            <div class="flex-grow-1">
                                <p class="font-size-14 mt-0 mb-1">Get 10% off on first booking</p>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="font-size-14">use code:</span>
                                    <span class="heading-color">FIRST50</span>
                                </div>
                            </div>
                            <button class="btn btn-link font-size-12">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="btn btn-secondary" data-bs-target="#exampleModalToggle" data-bs-toggle="modal">Book Appointment</button>
    </div>
</div>

@include('components.login_modal')
@if ($errors->any())
  <script>
    $(document).ready(function() {
      $('#loginModal').modal('show');
      $('#modal_login_error_message').removeClass('d-none').text("{{ $errors->first() }}");
    });
  </script>
@endif

@endsection

<script>

     document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('.payment-radio');
        const submitBtnContainer = document.getElementById('submit-button-container');

        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                submitBtnContainer.classList.remove('d-none');
            });
        });

          const loginTrigger = document.getElementById('trigger-login-modal');
        if (loginTrigger) {
            loginTrigger.addEventListener('click', function () {
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            });
        }
    });

</script>