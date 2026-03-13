@extends('frontend::layouts.master')

@section('content')

<div class="order-details-section section-spacing-inner-pages">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-8">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <i class="ph ph-caret-left align-middle icon-colo font-size-20"></i>
                        <a href="#" class="btn btn-link text-body font-size-16">Back</a>
                    </div>
                    <button class="btn btn-primary">Download Invoice</button>                    
                </div>

                <div class="mt-5">
                    <div class="order-content d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <h6 class="mb-0">Order ID</h6>
                        <a href="#" class="btn btn-link font-size-16">#158</a>
                    </div>
                </div>
                
                <div class="mt-5">
                    <h5>Order Details</h5>
                    <div class="order-content">
                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <h6 class="mb-1">Date and time:</h6>
                                <span class="font-size-14">20/06/2024 at 10:00 AM</span>
                            </div>
                            <div class="col-lg-4 col-md-6 mt-3 mt-md-0">
                                <h6 class="mb-1">Payment</h6>
                                <span class="font-size-14">Cash</span>
                            </div>
                            <div class="col-lg-4 col-md-12 mt-3 mt-lg-0">
                                <h6 class="mb-1">Delivery Status:</h6>
                                <span class="font-size-14">Order Placed / Delivered</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <h5>About Product</h5>
                    <div class="order-content order-product-info">
                        <div class="d-flex align-items-center column-gap-4 row-gap-3 flex-sm-nowrap flex-wrap">
                            <div class="order-product-images">
                                <img src="{{asset ('img/frontend/product.png')}}" class="avatar avatar-70 object-cover" alt="Salon Image">
                            </div>
                            <div>
                                <h5>Hydrate shampoo</h5>
                                <div class="d-flex align-items-center column-gap-5 row-gap-2 flex-wrap">
                                    <div>
                                        <span class="font-size-14">Price:</span>
                                        <span class="text-primary fw-medium">$125.00</span>
                                    </div>
                                    <div>
                                        <span class="font-size-14">Quantity:</span>
                                        <span class="heading-color fw-medium">01</span>
                                    </div>
                                    <div>
                                        <span class="font-size-14">Delivery Date:</span>
                                        <span class="heading-color fw-medium">12/05/2024</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-content order-product-info">
                        <div class="d-flex align-items-center column-gap-4 row-gap-3 flex-sm-nowrap flex-wrap">
                            <div class="order-product-images">
                                <img src="{{asset ('img/frontend/product.png')}}" class="avatar avatar-70 object-cover" alt="Salon Image">
                            </div>
                            <div>
                                <h5>Hydrate shampoo</h5>
                                <div class="d-flex align-items-center column-gap-5 row-gap-2 flex-wrap">
                                    <div>
                                        <span class="font-size-14">Price:</span>
                                        <span class="text-primary fw-medium">$125.00</span>
                                    </div>
                                    <div>
                                        <span class="font-size-14">Quantity:</span>
                                        <span class="heading-color fw-medium">01</span>
                                    </div>
                                    <div>
                                        <span class="font-size-14">Delivery Date:</span>
                                        <span class="heading-color fw-medium">12/05/2024</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <h5>Shipping Detail</h5>
                    <div class="order-content">
                        <h6>Martina Alen</h6>
                        <p class="mb-2">Apt. 765 11149 Goodwin Wells, New Kerryfurt, New Jersy, USA</p>
                        <div><span>Contact Number:</span> <a href="#" class="btn btn-link heading-color">+1 234 567 890</a></div>
                    </div>
                </div>
                
            </div>
            <div class="col-md-4 payment-section">
                <div class="payment-container">
                    <h6>Payment Detail</h6>
                    <!-- Payment Summary -->
                    <div class="payment-summary">
                        <!-- Package Name and Price -->
                        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
                            <span class="font-size-14">Platinum Membership Applied</span>
                            <span class="badge rounded-pill bg-success font-size-10 text-uppercase">$20000% OFF</span>
                        </div>

                        <!-- Subtotal -->
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Subtotal</span>
                            <span class="heading-color">$189</span>
                        </div>

                        <!-- dilivery charge -->
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Delivery Charges</span>
                            <span class="heading-color">$15</span>
                        </div>

                        <!-- Tax (collapsible) -->
                         <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">Tax</span>
                            <div class="d-flex gap-1 align-items-center gap-3 taxDetails" data-bs-toggle="collapse" href="#taxDetails" role="button" aria-expanded="true" aria-controls="taxDetails">
                                <i class="ph ph-caret-down"></i>
                                <span class="text-danger fw-semibold">$10</span>
                            </div>
                         </div>
                        <div class="collapse show mt-2 mb-2" id="taxDetails">
                            <div class="text-calculate card py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="font-size-12">Service (5%)</span>
                                    <span class="text-danger fw-semibold">$5</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="font-size-12">VAT (5%)</span>
                                    <span class="text-danger fw-semibold">$5</span>
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
</div>

@endsection