@extends('frontend::layouts.master')

@section('content')


<x-breadcrumb title="{{ __('frontend.checkout_details') }}" />
<div class="section-spacing-inner-pages">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <h5 class="mb-3">Shipping Address</h5>
                <div class="bg-gray-800 rounded p-4">
                    <h6>Martina Alen</h6>
                    <p class="mb-2">Apt. 765 11149 Goodwin Wells, New Kerryfurt, New Jersy, USA</p>
                    <div><span>Contact Number:</span> <a href="#" class="heading-color">+1 234 567 890</a></div>
                </div>
                <div class="checout-cart-spacing">
                    <div class="table-responsive">
                        <table class="table table-borderless rounded custom-table-bg">
                            <thead>
                                <tr>
                                    <th>product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="bg-gray-900 avatar avatar-70 rounded">
                                                <img src="{{ asset('img/frontend/product.png') }}" alt="product" class="img-fluid avatar avatar-70">
                                            </div>
                                            <h6 class="mb-0 text-body">Electric Nail Paint</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0">$99.02</h6>
                                    </td>
                                    <td>
                                        <div class="btn-group iq-qty-btn" data-qty="btn" role="group">
                                            <button type="button" class="btn btn-link border-0 iq-quantity-minus heading-color">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3" viewBox="0 0 6 3" fill="none">
                                                <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z" fill="currentColor"></path>
                                            </svg>
                                            </button>
                                            <input type="text" class="btn btn-link border-0 input-display" data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2" value="2" title="Qty">
                                            <button type="button" class="btn btn-link border-0 iq-quantity-plus heading-color">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8" viewBox="0 0 9 8" fill="none">
                                                <path d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z" fill="currentColor"></path>
                                            </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0">$99.02</h6>
                                    </td>
                                    <td>
                                        <button class="btn btn-link border-0 icon-color font-size-18"><i class="ph ph-trash-simple"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <div class="bg-gray-900 avatar avatar-70 rounded">
                                                <img src="{{ asset('img/frontend/product.png') }}" alt="product" class="img-fluid avatar avatar-70">
                                            </div>
                                            <h6 class="mb-0 text-body">Electric Nail Paint</h6>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0">$99.02</h6>
                                    </td>
                                    <td>
                                        <div class="btn-group iq-qty-btn" data-qty="btn" role="group">
                                            <button type="button" class="btn btn-link border-0 iq-quantity-minus heading-color">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3" viewBox="0 0 6 3" fill="none">
                                                <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z" fill="currentColor"></path>
                                            </svg>
                                            </button>
                                            <input type="text" class="btn btn-link border-0 input-display" data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2" value="2" title="Qty">
                                            <button type="button" class="btn btn-link border-0 iq-quantity-plus heading-color">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8" viewBox="0 0 9 8" fill="none">
                                                <path d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z" fill="currentColor"></path>
                                            </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0">$99.02</h6>
                                    </td>
                                    <td>
                                        <button class="btn btn-link border-0 icon-color font-size-18"><i class="ph ph-trash-simple"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="checout-cart-spacing">
                    <h5>Select Payment Method</h5>
                    <div class="payments-container bg-gray-800 rounded mt-3">
                        <a class="d-flex justify-content-between align-items-center gap-3 payments-show-list" href="#booking-payments-method" data-bs-toggle="collapse" aria-expanded="true">
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ asset('img/frontend/stripe-payment.png') }}" alt="stripe-payment" class="img-fluid flex-shrink-0">
                                <span class="flex-shrink-0 font-size-14 fw-medium heading-color">Stripe</span>
                            </div>
                            <i class="ph ph-caret-down"></i>
                        </a>
                    </div>
                    <div id="booking-payments-method" class="bg-gray-800 rounded booking-payment-method mt-3 collapse show">
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-cash">
                                <img src="{{asset ('img/frontend/cash.svg')}}" alt="cash" class="avatar avatar-20">
                                    <span class="h6 fw-semibold m-0">Cash</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="cash">
                        </div>

                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Stripe">
                                <img src="{{asset ('img/frontend/stripe.svg')}}" alt="Stripe" class="avatar avatar-20">
                                    <span class="h6 fw-semibold m-0">Stripe</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Stripe">
                        </div>
                        
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Paystack">
                                <img src="{{asset ('img/frontend/paystack.svg')}}" alt="Paystack" class="avatar avatar-20">
                                    <span class="h6 fw-semibold m-0">Paystack</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Paystack">
                        </div>

                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-PayPal">
                                <img src="{{asset ('img/frontend/paypal.svg')}}" alt="PayPal" class="avatar avatar-20">
                                    <span class="h6 fw-semibold m-0">PayPal</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="PayPal">
                        </div>
                        <div class="form-check payment-method-items ps-0 d-flex justify-content-between align-items-center gap-3">
                            <label class="form-check-label d-flex gap-2 align-items-center" for="method-Flutterwave">
                                <img src="{{asset ('img/frontend//flutterwave.svg')}}" alt="Flutterwave" class="avatar avatar-20">
                                    <span class="h6 fw-semibold m-0">Flutterwave</span>
                            </label>
                            <input class="form-check-input payment-radio" type="radio" name="payment_method" value="Flutterwave">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-3">Payment Details</h5>
                    <div class="payment-details bg-gray-800 p-4 rounded">
                        <div class="payment-details-item border-bottom d-flex flex-wrap align-items-center justify-content-between mb-3 pb-3">
                            <div class="font-size-14">Blusher</div>
                            <h6 class="font-size-14 mb-0">$32 <del>$42</del></h6>
                        </div>
                        <div class="payment-details-item d-flex flex-wrap align-items-center justify-content-between pb-3">
                            <div class="font-size-14">Discount <span class="text-success">(10%)</span></div>
                            <h6 class="font-size-14 mb-0 text-success">$10</h6>
                        </div>
                        <div class="payment-details-item d-flex flex-wrap align-items-center justify-content-between pb-3">
                            <div class="font-size-14">Subtotal</div>
                            <h6 class="font-size-14 mb-0">$189</h6>
                        </div>
                        <div class="payment-details-item border-bottom d-flex flex-wrap align-items-center justify-content-between mb-3 pb-3">
                            <div class="font-size-14">Delivery Charges</div>
                            <h6 class="font-size-14 mb-0">$15</h6>
                        </div>
                        <div class="payment-details-item d-flex flex-wrap align-items-center justify-content-between">
                            <div class="font-size-14">Total</div>
                            <h6 class="font-size-14 mb-0 text-primary">$189</h6>
                        </div>                       
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

@endsection