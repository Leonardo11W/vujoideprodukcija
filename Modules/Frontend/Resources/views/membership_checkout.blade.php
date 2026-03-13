@extends('frontend::layouts.master')

@section('content')

<x-breadcrumb title="{{ __('frontend.membership_checkout') }}" />

<div class="section-spacing-inner-pages payment-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h5>Selected Membership</h5>
                <div class="profilemembership-card-box d-flex flex-wrap align-items-start justify-content-between gap-3 bg-purple p-4 rounded position-relative overflow-hidden">
                    <div>
                        <h5 class="mb-2">Silver Membership</h5>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="mb-0 text-primary">$199</h4>
                            <span class="font-size-14 fw-semibold">/3 months</span>
                        </div>
                    </div>
                    <img src="{{ asset('images/referral-bg-img.svg') }}" alt="profilemembership-bg-img" class="profilemembership-bg-img position-absolute">
                </div>

                <div class="mt-5">
                    <h5 class="mb-2 mt-0">Select Payment Method</h5>
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
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-cash">
                                <img src="{{asset ('img/frontend/cash.svg')}}" alt="cash" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Cash</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="cash">
                        </div>

                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Stripe">
                                <img src="{{asset ('img/frontend/stripe.svg')}}" alt="Stripe" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Stripe</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Stripe">
                        </div>
                        
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Paystack">
                                <img src="{{asset ('img/frontend/paystack.svg')}}" alt="Paystack" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Paystack</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Paystack">
                        </div>

                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-PayPal">
                                <img src="{{asset ('img/frontend/paypal.svg')}}" alt="PayPal" class="flex-shrink-0 avatar avatar-18">
                                    <span class="flex-shrink-0 font-size-14 fw-medium heading-color">PayPal</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="PayPal">
                        </div>
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Flutterwave">
                                <img src="{{asset ('img/frontend//flutterwave.svg')}}" alt="Flutterwave" class="flex-shrink-0 avatar avatar-18">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Flutterwave</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Flutterwave">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-5 mt-lg-0">
                <h5>Payment Details</h5>
                <div class="payment-summary">
                    <!-- Coupon -->
                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                        <span class="font-size-14">Silver Membership</span>
                        <span class="font-size-14 fw-medium heading-color">$99</span>
                    </div>

                    <hr class="line-divider">

                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                        <span class="font-size-14">Subtotal</span>
                        <span class="font-size-14 fw-medium heading-color">$400</span>
                    </div>

                    <!-- Tax (collapsible) -->
                    <div class="d-flex justify-content-between align-items-center mb-1 price-item cursor-pointer" data-bs-toggle="collapse" href="#taxDetails" role="button" aria-expanded="true" aria-controls="taxDetails">
                        <span class="d-flex align-items-center justify-content-between font-size-14 gap-lg-3 gap-2">
                            <span>Tax</span>
                            <i class="ph ph-caret-down"></i>
                        </span>
                        <span class="font-size-14 fw-medium text-danger">$10</span>
                    </div>
                    <div class="collapse show mt-2 mb-2" id="taxDetails">
                        <div class="text-calculate card py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="font-size-12">Service (5%)</span>
                                <span class="font-size-12 text-danger fw-medium">$5</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-size-12">VAT (5%)</span>
                                <span class="font-size-12 text-danger fw-medium">$5</span>
                            </div>
                        </div>
                    </div>

                    <hr class="line-divider">

                    <!-- Total -->
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total</span>
                        <span class="total-value fw-semibold text-primary">$189</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="onclick-page-redirect bg-orange p-3">
    <div class="container">
        <div class="text-end">
            <button class="btn btn-secondary px-5">Submit</button>
        </div>
    </div>
</div>


@endsection