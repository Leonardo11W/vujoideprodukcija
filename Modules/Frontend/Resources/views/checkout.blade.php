@extends('frontend::layouts.master')
@section('title', __('frontend.checkout'))

@section('content')

    {{-- Debug: Show logged in user name --}}
    {{-- {{ auth()->user()->name }} --}}

    <x-breadcrumb :title="$pageTitle" />
    <div class="cart-page section-spacing-inner-pages">
        <div class="container">
            <div class="row gy-4">
                <div class="col-md-7 col-lg-9">
                    <div class="cart-table">
                        <div class="table-responsive">
                            <div id="empty-cart-message" style="display:none;">
                                <div class="empty-cart text-center py-5">
                                    <img src="{{ asset('img/frontend/empty-cart.jpg') }}" alt="Empty Cart"
                                        class="img-fluid mb-3 avatar-150"
                                        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/shopping-cart.svg';">
                                    <h5>{{ __('frontend.your_cart_is_empty') }}</h5>
                                    <p class="text-body">
                                        {{ __('frontend.add_items_to_your_cart_to_proceed_with_checkout') }}</p>
                                    <a href="{{ route('shop') }}"
                                        class="btn btn-primary mt-3">{{ __('frontend.continue_shopping') }}</a>
                                </div>
                            </div>
                            <table id="checkout-table" class="table table-borderless custom-table-bg rounded">
                                <thead>
                                    <tr>
                                        <th>{{ __('frontend.product') }}</th>
                                        <th>{{ __('frontend.price') }}</th>
                                        <th>{{ __('frontend.discount') }} (%)</th>

                                        <th>{{ __('frontend.quantity') }}</th>
                                        <th>{{ __('frontend.subtotal') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>

                    <div id="checkout-sections">

                        <div class="address-block">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <h5 class="mb-0">{{ __('frontend.delivery_address') }}</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#modalAddAddress" onclick="prefillUserName()">
                                     {{ __('frontend.add_new_address') }}
                                </button>
                            </div>

                            @if ($addresses->isNotEmpty())
                                @php
                                    $validAddresses = $addresses->filter(function ($address) {
                                        return $address->address_line_1 ||
                                            $address->city_data ||
                                            $address->state_data ||
                                            $address->country_data ||
                                            $address->postal_code;
                                    });

                                    $primaryAddress =
                                        $validAddresses->firstWhere('is_primary', 1) ?? $validAddresses->first();
                                    $otherAddresses = $validAddresses->where('id', '!=', $primaryAddress->id);
                                @endphp

                                @if ($validAddresses->isNotEmpty())

                                    <div class="bg-gray-800 p-4 rounded d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 address-card active"
                                        data-id="{{ $primaryAddress->id }}"
                                        onclick="selectAddress({{ $primaryAddress->id }}, this)">
                                        <div class="d-flex align-items-center gap-3 w-100">
                                            <div class="form-check">
                                                <input class="form-check-input address-radio" type="radio" name="address"
                                                    value="{{ $primaryAddress->id }}" checked>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <p class="mb-1 fw-medium">
                                                            <span class="user-name">
                                                                {{ $primaryAddress->first_name }}
                                                                {{ $primaryAddress->last_name }}
                                                            </span>

                                                            @if ($primaryAddress->is_primary)
                                                                <span
                                                                    class="badge bg-primary ms-2">{{ __('frontend.primary') }}</span>
                                                            @endif
                                                        </p>
                                                        <p class="mb-1 small text-body address-line">
                                                            {{ $primaryAddress->address_line_1 }}
                                                            @if ($primaryAddress->address_line_2)
                                                                , {{ $primaryAddress->address_line_2 }}
                                                            @endif
                                                        </p>
                                                        <p class="mb-0 small text-body address-line">
                                                            {{ optional($primaryAddress->city_data)->name }},
                                                            {{ optional($primaryAddress->state_data)->name }},
                                                            {{ optional($primaryAddress->country_data)->name }} -
                                                            {{ $primaryAddress->postal_code }}
                                                        </p>
                                                        <p class="mb-0 small text-body contact-number mt-1">
                                                            <i class="ph ph-phone"></i><span
                                                                class="user-contact-number">{{ $primaryAddress->contact_number }}</span>
                                                        </p>
                                                        <p class="mb-0 small text-body  mt-1 d-none">
                                                            <i class="ph ph-envelope"></i><span
                                                                class="user-email">{{ $primaryAddress->email }}</span>
                                                        </p>
                                                    </div>
                                                    <button class="btn btn-link text-success p-0 edit-address-btn"
                                                        data-bs-target="#modalAddAddress" data-bs-toggle="modal"
                                                        onclick="event.stopPropagation(); editAddress({{ $primaryAddress->id }})">
                                                        <i class="ph ph-pencil-simple"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($otherAddresses->isNotEmpty())
                                        <div class="text-center mb-3">
                                            <a href="#otherAddresses" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="collapse" role="button" aria-expanded="false"
                                                aria-controls="otherAddresses">
                                               
                                                {{ __('frontend.view_other_addresses') }}
                                            </a>
                                        </div>


                                        <div class="collapse" id="otherAddresses">
                                            @foreach ($otherAddresses as $address)
                                                <div class="bg-gray-800 p-4 rounded d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 address-card cursor-pointer"
                                                    data-id="{{ $address->id }}"
                                                    onclick="selectAddress({{ $address->id }}, this)">
                                                    <div class="d-flex align-items-center gap-3 w-100">
                                                        <div class="form-check">
                                                            <input class="form-check-input address-radio" type="radio"
                                                                name="address" value="{{ $address->id }}">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <p class="mb-1 fw-medium">
                                                                        <span class="user-name">
                                                                            {{ $address->first_name }}
                                                                            {{ $address->last_name }}
                                                                        </span>
                                                                    </p>
                                                                    <p class="mb-1 small text-body address-line">
                                                                        {{ $address->address_line_1 }}
                                                                        @if ($address->address_line_2)
                                                                            , {{ $address->address_line_2 }}
                                                                        @endif
                                                                    </p>
                                                                    <p class="mb-0 small text-body address-line">
                                                                        {{ optional($address->city_data)->name }},
                                                                        {{ optional($address->state_data)->name }},
                                                                        {{ optional($address->country_data)->name }} -
                                                                        {{ $address->postal_code }}
                                                                    </p>
                                                                    <p class="mb-0 small text-body contact-number mt-1">
                                                                        <i class="ph ph-phone"></i><span
                                                                            class="user-contact-number">{{ $address->contact_number }}</span>
                                                                    </p>
                                                                    <p class="mb-0 small text-body mt-1 d-none">
                                                                        <i class="ph ph-envelope"></i><span
                                                                            class="user-email">{{ $address->email }}</span>
                                                                    </p>
                                                                </div>
                                                                <button
                                                                    class="btn btn-link text-success p-0 edit-address-btn"
                                                                    data-bs-target="#modalAddAddress" data-bs-toggle="modal"
                                                                    onclick="event.stopPropagation(); editAddress({{ $address->id }})">
                                                                    <i class="ph ph-pencil-simple"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <div class="bg-gray-800 p-4 rounded text-center">
                                        <p class="mb-0">
                                            {{ __('frontend.no_addresses_found_please_add_a_new_address_to_continue') }}
                                        </p>
                                    </div>
                                @endif
                            @else
                                <div class="bg-gray-800 p-4 rounded text-center">
                                    <p class="mb-0">
                                        {{ __('frontend.no_addresses_found_please_add_a_new_address_to_continue') }}</p>
                                </div>
                            @endif
                        </div>

                        @if ($addresses->isNotEmpty() && $validAddresses->isNotEmpty())
                            <div class="charges-block mt-4">
                                <h5 class="mb-3">{{ __('frontend.delivery_charges') }}</h5>
                                <div class="delivery-zones-container">
                                </div>
                            </div>
                        @endif



                        <!-- <div class="col-12 mt-4 mb-5">
                            <h5>{{ __('frontend.select_payment_method') }}</h5>
                            <div class="mb-5">
                                <div class="dropdown payment-method-dropdown mt-3" id="payment-method-dropdown">
                                    <button type="button"
                                        class="border-0 rounded w-100 payments-container d-flex justify-content-between align-items-center gap-3 payments-show-list bg-gray-800"
                                        id="selected-method-btn">
                                        <span class="d-flex align-items-center gap-2">
                                            <img id="selected-method-img avatar-24"
                                                src="{{ asset('img/frontend/cash.svg') }}" alt="Cash">
                                            <span id="selected-method-name"
                                                class="flex-shrink-0 font-size-14 fw-medium heading-color">{{ __('frontend.cash') }}</span>
                                        </span>
                                        <i class="ph ph-caret-down"></i>
                                    </button>
                                    <div class="dropdown-menu w-100 bg-gray-800 rounded booking-payment-method mt-3 show"
                                        id="payment-method-list">
                                        <div class="list-group " style="border: none;">
                                            @php $first = true; @endphp
                                            @if (isset($paymentMethods) && is_array($paymentMethods))
                                                @foreach ($paymentMethods as $method => $enabled)
                                                    @if ($enabled)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 payment-method-items cursor-pointer border-0 p-0 bg-transparent">
                                                            <span class="d-flex align-items-center gap-3">
                                                                @if ($method == 'cash')
                                                                    <img src="{{ asset('img/frontend/cash.svg') }}"
                                                                        alt="Cash" class="avatar-28">
                                                                    <span>{{ __('frontend.cash') }}</span>
                                                                @elseif($method == 'wallet')
                                                                    <img src="{{ asset('img/frontend/wallet.svg') }}"
                                                                        alt="Wallet" class="avatar-28">
                                                                    <span>Wallet</span>
                                                                    <span
                                                                        class="text-success">({{ Currency::format($walletBalance ?? 0) }})</span>
                                                                @else
                                                                    @php
                                                                        $icon = strtolower($method);
                                                                        $displayName = $method;
                                                                        if ($method == 'stripe') {
                                                                            $displayName = 'Stripe';
                                                                        }
                                                                    @endphp
                                                                    <img src="{{ asset('img/frontend/' . $icon . '.svg') }}"
                                                                        alt="{{ $displayName }}" class="avatar-28">
                                                                    <span>{{ ucfirst($displayName) }}</span>
                                                                @endif
                                                            </span>
                                                            <input type="radio"
                                                                class="form-check-input payment-radio m-0"
                                                                name="payment_method" value="{{ $method }}"
                                                                id="method-{{ strtolower($method) }}"
                                                                {{ $first ? 'checked' : '' }}>
                                                        </label>
                                                        @php $first = false; @endphp
                                                    @endif
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->

                        <div class="col-12 mt-4 mb-5">
    <h5>{{ __('frontend.select_payment_method') }}</h5>
    <div class="mb-5">
        <div class="payment-method-collapse mt-3" id="payment-method-collapse">
            <!-- Collapse toggle button -->
            <button type="button"
                class="border-0 rounded w-100 payments-container d-flex justify-content-between align-items-center gap-3 payments-show-list bg-gray-800"
                data-bs-toggle="collapse" data-bs-target="#payment-method-list"
                aria-expanded="false" aria-controls="payment-method-list"
                id="selected-method-btn">
                <span class="d-flex align-items-center gap-2">
                    <img id="selected-method-img" class="avatar-24"
                        src="{{ asset('img/frontend/cash.svg') }}" alt="Cash">
                    <span id="selected-method-name"
                        class="flex-shrink-0 font-size-14 fw-medium heading-color">{{ __('frontend.cash') }}</span>
                </span>
                <i class="ph ph-caret-down"></i>
            </button>

            <!-- Collapsible content -->
            <div class="collapse mt-3" id="payment-method-list">
                <div class="list-group booking-payment-method bg-gray-800 rounded" style="border: none;">
                    @php $first = true; @endphp
                    @if (isset($paymentMethods) && is_array($paymentMethods))
                        @foreach ($paymentMethods as $method => $enabled)
                            @if ($enabled)
                                <label
                                    class="list-group-item d-flex align-items-center justify-content-between gap-3 payment-method-items cursor-pointer border-0 p-0 bg-transparent">
                                    <span class="d-flex align-items-center gap-3">
                                        @if ($method == 'cash')
                                            <img src="{{ asset('img/frontend/cash.svg') }}"
                                                alt="Cash" class="avatar-28">
                                            <span>{{ __('frontend.cash') }}</span>
                                        @elseif($method == 'wallet')
                                            <img src="{{ asset('img/frontend/wallet.svg') }}"
                                                alt="Wallet" class="avatar-28">
                                            <span>Wallet</span>
                                            <span class="text-success">
                                                ({{ Currency::format($walletBalance ?? 0) }})
                                            </span>
                                        @else
                                            @php
                                                $icon = strtolower($method);
                                                $displayName = $method;
                                                if ($method == 'stripe') {
                                                    $displayName = 'Stripe';
                                                }
                                            @endphp
                                            <img src="{{ asset('img/frontend/' . $icon . '.svg') }}"
                                                alt="{{ $displayName }}" class="avatar-28">
                                            <span>{{ ucfirst($displayName) }}</span>
                                        @endif
                                    </span>
                                    <input type="radio"
                                        class="form-check-input payment-radio m-0"
                                        name="payment_method" value="{{ $method }}"
                                        id="method-{{ strtolower($method) }}"
                                        {{ $first ? 'checked' : '' }}>
                                </label>
                                @php $first = false; @endphp
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

                    </div>


                </div>
                <div class="col-md-5 col-lg-3">
                    <div class="cart-summary">
                        <h5 class="mb-3">{{ __('frontend.payment_details') }}</h5>
                        <div class="payment-details bg-gray-800 p-4 rounded">
                            <div id="checkout-summary">
                                {{-- Payment Summary (with correct total calculation) --}}
                                <div class="payment-summary mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $product_name ?? 'Product' }}</span>
                                        <span id="product-price">{{ Currency::format($subtotal ?? 0) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span>{{ __('frontend.subtotal') }}</span>
                                        <span id="subtotal">{{ Currency::format($subtotal ?? 0) }}</span>
                                    </div>
                                    @if (!empty($discount) && $discount > 0)
                                        <div class="d-flex justify-content-between align-items-center mt-2 border-bottom pb-2 mb-2">
                                            <span>{{ __('frontend.discount') }}</span>
                                            <span class="text-success"
                                                id="discount">-{{ Currency::format($discount) }}</span>
                                        </div>
                                    @endif
                                    @if (!empty($discount_percent) && $discount_percent > 0 && isset($coupon_discount))
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <span>{{ __('frontend.coupon_discount') }}
                                                ({{ $discount_percent ?? 0 }}%)</span>
                                            <span class="text-success"
                                                id="coupon-discount">-{{ Currency::format($coupon_discount ?? 0) }}</span>
                                        </div>
                                    @endif



                                    @if (isset($taxes) && $taxes->count() > 0 && ($subtotal ?? 0) > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                            <span class="font-size-14">{{ __('frontend.tax') }}</span>
                                            <div class="d-flex justify-content-between align-items-center mb-1 price-item text-decoration-none cursor-pointer taxDetails"
                                                data-bs-toggle="collapse" href="#taxDetailsCheckout" role="button"
                                                aria-expanded="false" aria-controls="taxDetailsCheckout"
                                                style="display:block;">
                                                <i class="ph ph-caret-down rotate-icon tax2"></i>
                                                <span class="font-size-14 fw-medium text-danger" id="tax">
                                                    {{ isset($taxes) && $taxes->count() > 0 && ($subtotal ?? 0) > 0
                                                        ? Currency::format(
                                                            $taxes->sum(function ($tax) use ($subtotal) {
                                                                if ($tax->type == 'fixed') {
                                                                    return $tax->value;
                                                                }
                                                                if ($tax->type == 'percent') {
                                                                    return (($subtotal ?? 0) * $tax->value) / 100;
                                                                }
                                                                return 0;
                                                            }),
                                                        )
                                                        : Currency::format(0) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="collapse mt-2 mb-2" id="taxDetailsCheckout">
                                            <div class="text-calculate card py-2 px-3" id="tax-details">

                                                @foreach ($taxes as $tax)
                                                    <div
                                                        class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-1' : '' }}">
                                                        <span
                                                            class="font-size-12">{{ $tax->title }}{{ $tax->type == 'percent' ? ' (' . $tax->value . '%)' : '' }}</span>
                                                        <span class="font-size-12 text-danger fw-medium">
                                                            {{ Currency::format($tax->type == 'fixed' ? $tax->value : (($subtotal ?? 0) * $tax->value) / 100) }}
                                                        </span>
                                                    </div>
                                                @endforeach

                                            </div>
                                        </div>
                                    @endif
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center fw-bold" id="total-row">
                                        <span>{{ __('frontend.total') }}</span>
                                        <span class="total-value text-primary" id="total-amount">
                                            {{ Currency::format(($subtotal ?? 0) - ($discount ?? 0) + (($service_tax ?? 0) + ($gst ?? 0))) }}
                                        </span>
                                    </div>


                                    <div id="wallet-payment-info" class="mt-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cart-summary mt-4">
                        <h5 class="mb-3">{{ __('frontend.order_summery') }}</h5>
                        <div class="payment-details bg-gray-800 p-4 rounded">
                            <div id="order-summary-box">
                                <div class="mb-3">
                                    <div class="d-flex gap-2 mb-2">
                                        <h6 class="m-0 flex-shrink-0">{{ __('frontend.user_name') }}</h6> <span
                                            id="order-summary-username"></span>
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <h6 class="m-0 flex-shrink-0">{{ __('frontend.email') }}:</h6> <span
                                            id="order-summary-email" class="text-break"></span>
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <h6 class="m-0 flex-shrink-0">{{ __('frontend.contact_number') }}:</h6> <span
                                            id="order-summary-mobile" class=""></span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <h6 class="m-0 flex-shrink-0">{{ __('frontend.address') }}</h6>
                                        <span id="order-summary-address"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="onclick-page-redirect bg-orange p-3" id="deliver_button">
        <div class="container">
            <div class="d-flex align-items-center justify-content-end">
                <button class="btn btn-secondary px-5" onclick="placeOrder()">{{ __('frontend.deliver_here') }}</button>
            </div>
        </div>
    </div>

    {{-- <div id="inlineAddressError" class="alert alert-danger" style="display:none;"></div> --}}

    <div class="modal fade" id="modalAddAddress" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header pb-0 d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="modal-title" id="modalAddAddressLabel">{{ __('frontend.add_new_address') }}</h5>

                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addressForm" novalidate>
                        @csrf
                        <input type="hidden" name="address_id" id="address_id">
                        <div class="row gy-4">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="first_name"
                                        class="form-label fw-medium">{{ __('frontend.first_name') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="first_name" class="form-control" id="first_name"
                                            placeholder="eg. Michael">
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
                                            placeholder="eg. Thompson">
                                        <span class="input-group-text"><i class="ph ph-user"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="last_name_error"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <label for="contact_number" class="form-label">{{ __('frontend.contact_number') }}<span
                                        class="text-danger">*</span></label>
                                <div class="input-group custom-input-group position-relative">
                                    <input type="tel" id="mobileInput" name="contact_number"
                                        class="form-control font-size-14">
                                    <span class="input-group-text"><i class="ph ph-phone"></i></span>
                                </div>
                                <div class="invalid-feedback" id="contact_number_error"></div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="email" class="form-label fw-medium">{{ __('frontend.email') }}<span
                                            class="text-danger"></span></label>
                                    <div class="input-group custom-input-group">
                                        <input type="email" name="email" class="form-control" id="email"
                                            placeholder="eg. Thompson">
                                        <span class="input-group-text"><i class="ph ph-envelope-simple"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="email_error"></div>
                                </div>
                            </div>

                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="country" class="form-label fw-medium">{{ __('frontend.country') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <select name="country" id="country" class="form-control">
                                            <option value="" disabled selected>
                                                {{ __('frontend.select_country') }}</option>
                                        </select>
                                        <span class="input-group-text">
                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
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
                                    <label for="state" class="form-label fw-medium">{{ __('frontend.state') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <select name="state" id="state" class="form-control">
                                            <option value="" disabled selected>{{ __('frontend.select_state') }}
                                            </option>
                                        </select>
                                        <span class="input-group-text">
                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
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
                                    <label for="city" class="form-label fw-medium">{{ __('frontend.city') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <select name="city" id="city" class="form-control">
                                            <option value="" disabled selected>{{ __('frontend.select_city') }}
                                            </option>
                                        </select>
                                        <span class="input-group-text">
                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
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
                                    <label for="pin_code" class="form-label fw-medium">{{ __('frontend.pin_code') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="pin_code" class="form-control" id="pin_code"
                                            placeholder="eg. 900001" pattern="^\d{6,7}$" maxlength="7" minlength="6"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                        <span class="input-group-text">
                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_3134_40625)">
                                                    <path
                                                        d="M8 8.5C9.65685 8.5 11 7.15685 11 5.5C11 3.84315 9.65685 2.5 8 2.5C6.34315 2.5 5 3.84315 5 5.5C5 7.15685 6.34315 8.5 8 8.5Z"
                                                        stroke="#A6A8A8" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
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
                                    <label for="address" class="form-label fw-medium">{{ __('frontend.address') }}<span
                                            class="text-danger">*</span></label>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="address" class="form-control" id="address_line_1"
                                            placeholder="eg. 123 Elm Street, Springfield">
                                        <span class="input-group-text">
                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_3134_40630)">
                                                    <path
                                                        d="M8 6.5C8.55228 6.5 9 6.05228 9 5.5C9 4.94772 8.55228 4.5 8 4.5C7.44772 4.5 7 4.94772 7 5.5C7 6.05228 7.44772 6.5 8 6.5Z"
                                                        fill="#A6A8A8" />
                                                    <path
                                                        d="M11.5 5.5C11.5 9 8 11 8 11C8 11 4.5 9 4.5 5.5C4.5 4.57174 4.86875 3.6815 5.52513 3.02513C6.1815 2.36875 7.07174 2 8 2C8.92826 2 9.8185 2.36875 10.4749 3.02513C11.1313 3.6815 11.5 4.57174 11.5 5.5Z"
                                                        stroke="#A6A8A8" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                    <path
                                                        d="M12.5 10.1963C13.7325 10.6513 14.5 11.2913 14.5 12C14.5 13.3807 11.59 14.5 8 14.5C4.41 14.5 1.5 13.3807 1.5 12C1.5 11.2913 2.2675 10.6513 3.5 10.1963"
                                                        stroke="#A6A8A8" stroke-width="1.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
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
                                        name="set_as_primary">
                                    <label class="form-check-label"
                                        for="set_as_primary">{{ __('frontend.set_as_primary') }}</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="submitAddress()">{{ __('frontend.confirm') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Razorpay JS SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

@endsection

@push('scripts')
    <script>
        function formatCurrencyvalue(value) {
            value = parseFloat(value);
            if (window.currencyFormat !== undefined) {
                return window.currencyFormat(value);
            }
            return value.toFixed(2);
        }

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

        // Prevent paste of non-digit characters

        $('.editable-field, .editable-span').css('cursor', 'pointer');

        let checkoutTable;

        $(document).ready(function() {
            // Initialize checkout DataTable
            checkoutTable = $('#checkout-table').DataTable({
                processing: '',
                serverSide: false,
                autoWidth: false,
                responsive: true,
                ajax: "{{ route('checkout.data') }}",
                columns: [{
                        data: 'product',
                        name: 'product',
                        width: '25%',
                        render: function(data, type, row) {
                            return `
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-gray-900 avatar avatar-50 rounded">
                                ${row.product_image ?
                                    `<img src="${row.product_image}" alt="${row.product_name}" class="img-fluid avatar avatar-50">` :
                                    `<img src="{{ asset('img/frontend/product.png') }}" alt="${row.product_name}" class="img-fluid avatar avatar-50">`
                                }
                            </div>
                            <h6 class="mb-0 text-body small">${row.product_name}</h6>
                        </div>
                    `;
                        }
                    },
                    {
                        data: 'price',
                        name: 'price',
                        width: '15%',
                        render: function(data, type, row) {
                            if (row.discount_value > 0) {
                                return `
                            <div class="small">
                                <del class="text-body">${formatCurrencyvalue(row.original_price)}</del>
                                <span class="text-primary">${formatCurrencyvalue(row.discounted_price)}</span>
                            </div>
                        `;
                            }
                            return `<div class="small">${formatCurrencyvalue(row.price)}</div>`;
                        }
                    },
                    {
                        data: 'discount_percentage',
                        name: 'discount_percentage',
                        width: '10%',
                        render: function(data, type, row) {
                            if (row.discount_value > 0) {
                                const currencySymbol = window.defaultCurrencySymbol || '$';
                                if (row.discount_type === 'fixed') {
                                    return `<div class="small text-success">${currencySymbol}${row.discount_value}</div>`;
                                } else {
                                    return `<div class="small text-success">${row.discount_value}%</div>`;
                                }
                            }
                            return `<div class="small">-</div>`;
                        }
                    },
                    // {
                    //     data: 'discount_amount',
                    //     name: 'discount_amount',
                    //     width: '10%',
                    //     render: function(data, type, row) {
                    //         if (row.discount_value > 0) {
                    //             const discountAmount = (row.original_price * row.discount_value /
                    //                 100).toFixed(2);
                    //             return `<div class="small text-success">-$${discountAmount}</div>`;
                    //         }
                    //         return `<div class="small">-</div>`;
                    //     }
                    // },
                    {
                        data: 'quantity',
                        name: 'quantity',
                        width: '15%',
                        render: function(data, type, row) {
                            const stockQty = row.product ? row.product.stock_qty : 0;
                            return `
                        <div class="btn-group iq-qty-btn" data-qty="btn" role="group">
                            <button type="button" class="btn btn-link border-0 iq-quantity-minus heading-color p-0" onclick="updateCartQuantity(${row.id}, 'decrease')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3" viewBox="0 0 6 3" fill="none">
                                    <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z" fill="currentColor"></path>
                                </svg>
                            </button>
                            <input type="text" class="btn btn-link border-0 input-display" data-qty="input" pattern="^(0|[1-9][0-9]*)$" minlength="1" maxlength="2" value="${row.quantity}" title="Qty" onchange="updateCartQuantity(${row.id}, 'set', this.value)" max="${stockQty}" readonly>
                            <button type="button" class="btn btn-link border-0 iq-quantity-plus heading-color p-0" onclick="updateCartQuantity(${row.id}, 'increase')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8" viewBox="0 0 9 8" fill="none">
                                    <path d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z" fill="currentColor"></path>
                                </svg>
                            </button>
                        </div>
                        <small class="text-success d-block mt-1 small">Available: ${stockQty}</small>
                    `;
                        }
                    },
                    {
                        data: 'subtotal',
                        name: 'subtotal',
                        width: '15%',
                        render: function(data, type, row) {
                            return `<div class="small">${formatCurrencyvalue(row.subtotal)}</div>`;
                        }
                    },
                    {
                        data: 'action',
                        name: 'action',
                        width: '10%',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                        <button class="btn btn-link border-0 text-danger p-0" onclick="removeFromCartdata(${row.product_id})">
                            <i class="ph ph-trash-simple"></i>
                        </button>
                    `;
                        }
                    }
                ],
                order: [
                    [0, 'asc']
                ],
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                language: {
                    emptyTable: `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="empty-cart">
                           <img src="{{ asset('img/frontend/empty-cart.jpg') }}" alt="Empty Cart" class="img-fluid mb-3 avatar-150" onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/gh/edent/SuperTinyIcons/images/svg/shopping-cart.svg';">
                            <h5>Your cart is empty</h5>
                            <p class="text-body">{{ __('frontend.add_items_to_your_cart_to_proceed_with_checkout') }}</p>
                            <a href="{{ route('shop') }}" class="btn btn-primary mt-3">{{ __('frontend.continue_shopping') }}</a>
                        </div>
                    </td>
                </tr>
            `
                },
                drawCallback: function() {
                    updateCheckoutSummary();
                    if (this.api().data().count() === 0) {
                        $("#deliver_button").addClass("d-none");
                    } else {
                        $("#deliver_button").removeClass("d-none");
                    }
                }
            });

            // Load countries on page load
            loadCountries();


            // Handle country change
            $('#country').on('change', function() {
                const countryId = $(this).val();
                if (countryId) {
                    loadStates(countryId);
                } else {
                    $('#state').html('<option value="">{{ __('frontend.select_state') }}</option>');
                    $('#city').html('<option value="">{{ __('frontend.city') }}</option>');
                }
            });

            // Handle state change
            $('#state').on('change', function() {
                const stateId = $(this).val();
                if (stateId) {
                    loadCities(stateId);
                } else {
                    $('#city').html('<option value="">{{ __('frontend.select_city') }}</option>');
                }
            });

            // Handle address selection
            $('input[name="address"]').on('change', function() {
                const addressId = $(this).val();
                // Save selected address to session for all payment methods
                fetch('/set-checkout-address', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        address_id: addressId
                    })
                });
                loadDeliveryZonesForAddress(addressId);
            });

            // Handle delivery zone selection
            $('input[name="delivery_zone"]').on('change', function() {
                updateCheckoutSummary();
            });

            // Always select the first available address if any
            if ($('input[name="address"]').length > 0) {
                $('input[name="address"]').first().prop('checked', true);
                loadDeliveryZonesForAddress($('input[name="address"]').first().val());

                // Set active state for the first address card
                $('.address-card').first().addClass('active');
            }

            // Handle modal show event for address modal
            $('#modalAddAddress').on('show.bs.modal', function() {
                $('#addressForm')[0].reset();
                loadCountries();
                $('#state').html('<option value="">{{ __('frontend.select_state') }}</option>');
                $('#city').html('<option value="">{{ __('frontend.select_city') }}</option>');
            });

            function toggleCheckoutSections() {
                // Check if the cart table is empty
                var isEmpty = $("#checkout-table tbody tr td:contains('Your cart is empty')").length > 0;
                if (isEmpty) {
                    $('#checkout-sections').hide();
                    $('.cart-summary').hide();
                    $('#checkout-table').hide();
                    $('#empty-cart-message').show();
                    $('.col-md-5.col-lg-3').hide(); // Hide right-side content
                } else {
                    $('#checkout-sections').show();
                    $('.cart-summary').show();
                    $('#checkout-table').show();
                    $('#empty-cart-message').hide();
                    $('.col-md-5.col-lg-3').show(); // Show right-side content
                }
            }
            // Call after table draw
            $('#checkout-table').on('draw.dt', toggleCheckoutSections);
            // Initial call
            toggleCheckoutSections();
        });



        var paymentDetailsTotalWithDelivery = null;

        function updateCheckoutSummary() {
            const selectedZoneId = $('input[name="delivery_zone"]:checked').val();
            $.get("{{ route('cart.summary') }}", {
                delivery_zone_id: selectedZoneId
            }, function(response) {
                if (response.status) {
                    let summaryHtml = '';
                    if (response.cart_items_count > 0) {
                        summaryHtml = `
                    <div class="payment-details-item  d-flex flex-wrap align-items-center justify-content-between mb-2  pb-2">
                        <div class="font-size-14">{{ __('frontend.subtotal') }}</div>
                        <h6 class="font-size-14 mb-0">${formatCurrencyvalue(response.subtotal)}</h6>
                    </div>

                    ${response.tax > 0 ? `
                                <div class="d-flex justify-content-between  mb-1 price-item">
                                    <span class="font-size-14">{{ __('frontend.tax') }}</span>
                                    <div class="payment-details-item  d-flex flex-wrap align-items-center mb-2 pb-2 text-decoration-none cursor-pointer"
                                        data-bs-toggle="collapse"
                                        href="#taxDetailsCheckout"
                                        role="button"
                                        aria-expanded="false"
                                        aria-controls="taxDetailsCheckout">
                                        <div class="d-flex align-items-center justify-content-between font-size-14 gap-2 taxDetails">
                                            <i class="ph ph-caret-down rotate-icon tax2"></i>
                                        </div>
                                        <h6 class="font-size-14 mb-0 text-danger" id="tax-amount">${formatCurrencyvalue(response.tax)}</h6>
                                    </div>
                                </div>
                                <div class="collapse mt-2 mb-2" id="taxDetailsCheckout">
                                    <div class="text-calculate card py-2 px-3" id="tax-details">
                                        ${(response.tax_breakdown && response.tax_breakdown.length > 0) ? response.tax_breakdown.map(tax => `
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-size-12">${tax.title}${tax.type === 'percent' ? ' (' + tax.value + '%)' : ''}</span>
                                        <span class="font-size-12 text-danger fw-medium">${formatCurrencyvalue(tax.amount)}</span>
                                    </div>
                                `).join('') : `
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-size-12">{{ __('frontend.tax') }}</span>
                                        <span class="font-size-12 text-danger fw-medium">0</span>
                                    </div>
                                `}
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        ` : ''}
                   ${response.delivery_charge > 0 ? `
                                                                                    <div class="payment-details-item border-bottom d-flex flex-wrap align-items-center justify-content-between mb-3 pb-3">
                                                                                        <div class="font-size-14">{{ __('frontend.delivery_charges') }}</div>
                                                                                        <h6 class="font-size-14 mb-0">${formatCurrencyvalue(response.delivery_charge)}</h6>
                                                                                    </div>
                                                                                ` : ''}
                    <div class="payment-details-item d-flex flex-wrap align-items-center justify-content-between mb-4">
                        <div class="font-size-14 fw-bold">{{ __('frontend.total_amount') }}</div>
                        <h6 class="font-size-14 mb-0 text-primary fw-bold">${formatCurrencyvalue(response.total_with_delivery)}</h6>
                    </div>
                `;
                    }

                    $('#checkout-summary').html(summaryHtml);

                    // Handle tax collapse toggle for dynamic content
                    $('[href="#taxDetailsCheckout"]').off('click').on('click', function(e) {
                        e.preventDefault();
                        const taxIcon = $(this).find('.tax2');
                        const isExpanded = $('#taxDetailsCheckout').hasClass('show');
                        if (isExpanded) {
                            taxIcon.css('transform', 'rotate(0deg)');
                        } else {
                            taxIcon.css('transform', 'rotate(180deg)');
                        }
                    });

                    paymentDetailsTotalWithDelivery = response.total_with_delivery;
                    updateOrderSummaryBox();
                }
            });
        }


        function loadCountries() {
            $.get("{{ route('frontend.address.get-countries') }}", function(data) {
                let options = '<option value="">{{ __('frontend.select_country') }}</option>';
                data.forEach(function(country) {
                    options += `<option value="${country.id}">${country.name}</option>`;
                });
                $('#country').html(options);
            }).fail(function(xhr, status, error) {
                console.error('Failed to load countries:', error);
                $('#country').html('<option value="">Failed to load countries</option>');
            });
        }

        function loadStates(countryId) {
            $.get(`{{ route('frontend.address.get-states') }}?country_id=${countryId}`, function(data) {
                let options = '<option value="">{{ __('frontend.select_state') }}</option>';
                data.forEach(function(state) {
                    options += `<option value="${state.id}">${state.name}</option>`;
                });
                $('#state').html(options);
                $('#city').html('<option value="">{{ __('frontend.select_city') }}</option>');
            }).fail(function(xhr, status, error) {
                console.error('Failed to load states:', error);
                $('#state').html('<option value="">Failed to load states</option>');
                $('#city').html('<option value="">{{ __('frontend.select_city') }}</option>');
            });
        }

        function loadCities(stateId) {
            $.get(`{{ route('frontend.address.get-cities') }}?state_id=${stateId}`, function(data) {
                let options = '<option value="">{{ __('frontend.select_city') }}</option>';
                data.forEach(function(city) {
                    options += `<option value="${city.id}">${city.name}</option>`;
                });
                $('#city').html(options);
            }).fail(function(xhr, status, error) {
                console.error('Failed to load cities:', error);
                $('#city').html('<option value="">Failed to load cities</option>');
            });
        }

        function updateCartQuantity(cartItemId, action, value = null) {
            let qty = value;
            if (!value) {
                const input = $(`input[data-qty="input"]`).filter(function() {
                    return $(this).closest('tr').find('button[onclick*="' + cartItemId + '"]').length > 0;
                });
                qty = parseInt(input.val());
                const maxQty = parseInt(input.attr('max'));

                if (action === 'increase') {
                    if (qty >= maxQty) {
                        toastr.warning(`Only ${maxQty} items available in stock`);
                        return;
                    }
                    qty++;
                } else if (action === 'decrease') {
                    qty = Math.max(1, qty - 1);
                }
            }

            $.ajax({
                url: "{{ route('cart.update') }}",
                type: 'POST',
                data: {
                    cart_item_id: cartItemId,
                    qty: qty,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {

                    if (response.status) {

                        $('#checkout-table').DataTable().ajax.reload();
                        updateOrderSummaryBox();
                        toastr.success('Cart updated successfully');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to update quantity. Please try again.');
                }
            });
        }

        function removeFromCartdata(productId) {


            $.ajax({
                url: "{{ route('cart.remove') }}",
                type: 'POST',
                data: {
                    product_id: productId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.status) {

                        checkoutTable.ajax.reload(null, false);
                        toastr.success('Product removed from cart successfully');
                        updateOrderSummaryBox();
                    } else {
                        checkoutTable.ajax.reload(null, false);
                        toastr.error(response.message);
                        updateOrderSummaryBox();
                    }
                },
                error: function() {
                    toastr.error('Failed to remove item from cart. Please try again.');
                }
            });
        }

        function clearAddressForm() {
            $('#addressForm')[0].reset();
            $('#address_id').val('');
            $('#state').html('<option value="">{{ __('frontend.select_state') }}</option>');
            $('#city').html('<option value="">{{ __('frontend.select_city') }}</option>');
        }

        // Utility function to load dropdown data
        function loadDropdownData(url, targetSelect, placeholder, selectedValue = null) {
            targetSelect.innerHTML = `<option value="" disabled selected>Loading...</option>`;

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

        function editAddress(addressId) {
            // Update modal title to "Edit Address"
            $('#modalAddAddressLabel').text('Edit Address');

            // Load address data and populate form
            $.get("{{ route('frontend.address.get', ['id' => ':id']) }}".replace(':id', addressId), function(response) {
                if (response.status) {
                    $('#address_id').val(response.address.id);
                    $('#first_name').val(response.address.first_name);
                    $('#last_name').val(response.address.last_name);
                    $('#email').val(response.address.email);
                    
                    // Set the phone number with correct country
                    if (mobileInput && window.addIti) {
                        try {
                            if (response.address.contact_number && response.address.contact_number.trim().length) {
                                if (response.address.contact_number.trim().startsWith('+')) {
                                    window.addIti.setNumber(response.address.contact_number.trim());
                                } else {
                                    // Legacy stored number without '+' — try to coerce
                                    window.addIti.setNumber('+' + response.address.contact_number.trim());
                                }
                            } else {
                                window.addIti.setNumber('');
                            }
                        } catch(e) {
                            mobileInput.value = response.address.contact_number || '';
                        }
                    }
                    
                    $('#address_line_1').val(response.address.address_line_1);
                    $('#address_line_2').val(response.address.address_line_2 || '');
                    $('#pin_code').val(response.address.postal_code);

                    // Load countries and set the selected country
                    loadDropdownData("{{ route('frontend.address.get-countries') }}", $('#country')[0],
                        'Select Country', response.address.country);

                    // Load states if country is selected
                    if (response.address.country) {
                        loadDropdownData("{{ route('frontend.address.get-states') }}?country_id=" + response
                            .address.country, $('#state')[0], 'Select State', response.address.state);

                        // Load cities if state is selected
                        if (response.address.state) {
                            setTimeout(() => {
                                loadDropdownData("{{ route('frontend.address.get-cities') }}?state_id=" + response
                                    .address.state, $('#city')[0], 'Select City', response.address.city);
                            }, 300);
                        }
                    }

                    $('#set_as_primary').prop('checked', response.address.is_primary == 1);
                }
            });
        }

        function submitAddress() {
            const form = $('#addressForm');
            
            // Clear previous errors
            form.find('.invalid-feedback').text('').hide();
            form.find('.is-invalid').removeClass('is-invalid');
            
            // Get the full number with country code from intl-tel-input
            if (window.addIti) {
                var fullNumber = window.addIti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
                // Update the input with the full number
                form.find('input[name="contact_number"]').val(fullNumber);
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
            var addressVal = ($('#address_line_1').val() || '').trim();

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
            if (!addressVal) setError('address_line_1', "{{ __('frontend.address_field_is_required') }}");

            if (hasError) return false;

            var formData = form.serialize();
            var addressId = $('#address_id').val();
            var url = addressId ? "{{ route('frontend.address.update', '') }}/" + addressId : "{{ route('frontend.address.store') }}";
            var method = addressId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: formData + '&_method=' + method,
                success: function(response) {
                    // Check if response is successful
                    if (response && response.status === true) {
                        // Clear any previous errors
                        form.find('.invalid-feedback').text('').hide();
                        form.find('.is-invalid').removeClass('is-invalid');
                        
                        // Show success message if available
                        if (response.message) {
                            toastr.success(response.message);
                        }
                        
                        // Close modal and reload
                        $('#modalAddAddress').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('padding-right', '');
                        
                        // Small delay before reload to ensure modal is closed
                        setTimeout(function() {
                            location.reload();
                        }, 100);
                    } else {
                        // Response indicates failure
                        var errorMsg = response && response.message ? response.message : '{{ __('frontend.failed_to_save_address') }}';
                        toastr.error(errorMsg);
                        
                        // If there are validation errors in success response, show them
                        if (response && response.errors) {
                            $.each(response.errors, function(field, messages) {
                                var fieldId = field;
                                var errorElementId = field + '_error';
                                
                                if (field === 'contact_number') {
                                    fieldId = 'mobileInput';
                                    errorElementId = 'contact_number_error';
                                } else if (field === 'address' || field === 'address_line_1') {
                                    fieldId = 'address_line_1';
                                    errorElementId = 'address_error';
                                }
                                
                                var $input = $('#' + fieldId);
                                var $err = $('#' + errorElementId);
                                
                                if ($input.length) {
                                    $input.addClass('is-invalid');
                                }
                                if ($err.length) {
                                    $err.text(messages[0] || messages).show();
                                }
                            });
                        }
                    }
                },
                error: function(xhr) {
                    // Clear previous errors first
                    form.find('.invalid-feedback').text('').hide();
                    form.find('.is-invalid').removeClass('is-invalid');
                    
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errors = xhr.responseJSON.errors;
                        var hasDisplayedError = false;
                        
                        $.each(errors, function(field, messages) {
                            var fieldId = field;
                            var errorElementId = field + '_error';
                            
                            // Map backend field names to input IDs if needed
                            if (field === 'contact_number') {
                                fieldId = 'mobileInput';
                                errorElementId = 'contact_number_error';
                            } else if (field === 'pin_code') {
                                fieldId = 'pin_code';
                                errorElementId = 'pin_code_error';
                            } else if (field === 'first_name') {
                                fieldId = 'first_name';
                                errorElementId = 'first_name_error';
                            } else if (field === 'last_name') {
                                fieldId = 'last_name';
                                errorElementId = 'last_name_error';
                            } else if (field === 'email') {
                                fieldId = 'email';
                                errorElementId = 'email_error';
                            } else if (field === 'country') {
                                fieldId = 'country';
                                errorElementId = 'country_error';
                            } else if (field === 'state') {
                                fieldId = 'state';
                                errorElementId = 'state_error';
                            } else if (field === 'city') {
                                fieldId = 'city';
                                errorElementId = 'city_error';
                            } else if (field === 'address' || field === 'address_line_1') {
                                fieldId = 'address_line_1';
                                errorElementId = 'address_error';
                            }

                            var $input = $('#' + fieldId);
                            var $err = $('#' + errorElementId);
                            
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                hasDisplayedError = true;
                            }
                            
                            if ($err.length) {
                                $err.text(messages[0] || messages).show();
                                hasDisplayedError = true;
                            } else if ($input.length) {
                                // If error element doesn't exist, show toastr notification
                                toastr.error(field + ': ' + (messages[0] || messages));
                                hasDisplayedError = true;
                            }
                        });
                        
                        // If no errors were displayed but we have error messages, show a general error
                        if (!hasDisplayedError && errors) {
                            var errorMsg = xhr.responseJSON.message || '{{ __('frontend.validation_error_occurred') }}';
                            toastr.error(errorMsg);
                        }
                    } else {
                        // Handle other error types
                        var errorMessage = '{{ __('frontend.error_occurred') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.status === 500) {
                            errorMessage = '{{ __('frontend.server_error') }}';
                        } else if (xhr.status === 404) {
                            errorMessage = '{{ __('frontend.address_not_found') }}';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.error') }}',
                            text: errorMessage,
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'swal2-confirm btn btn-primary'
                            }
                        });
                    }
                }
            });
            return false;
        }

        function formatCurrencyvalue(value) {
            value = parseFloat(value);
            if (window.currencyFormat !== undefined) {
                return window.currencyFormat(value);
            }
            return value.toFixed(2);
        }


        function placeOrder() {
            const addressId = $('input[name="address"]:checked').val();

            const deliveryZoneId = $('input[name="delivery_zone"]:checked').val();
            const paymentMethod = $('input[name="payment_method"]:checked').val();

            if (!addressId) {
                toastr.error('Please select a delivery address');
                return;
            }

            if (!deliveryZoneId) {
                toastr.error('Please select a delivery zone');
                return;
            }

            // Show loading state
            const button = $('button[onclick="placeOrder()"]');
            const originalText = button.text();
            button.prop('disabled', true).text('Processing...');

            if (paymentMethod === 'cash') {
                // Place order as before
                $.ajax({
                    url: "{{ url('api/place-order') }}",
                    type: 'POST',
                    data: {
                        shipping_address_id: addressId,
                        billing_address_id: addressId,
                        chosen_logistic_zone_id: deliveryZoneId,
                        payment_method: paymentMethod,
                        shipping_delivery_type: 'regular',
                        payment_status: 'unpaid',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.status) {
                            // Get order code with prefix (already formatted from API)
                            const displayOrderId = response.product?.order_group?.formatted_order_code || response.product?.order_group?.order_code || '';

                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('frontend.order_submitted') }}',
                                html: `
                            <h5>{{ __('frontend.thank_you_for_your_order') }}</h5>
                            <p>{{ __('frontend.your_order_has_been_successfully_booked') }}</p>
                            <div>
                                ${(displayOrderId ? `<span  class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.order_id') }}</span>: <span class="text-primary fw-bold font-size-14">${displayOrderId}</span></span>` : '')}
                                <span class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.payment_method') }}</span>: <span class="h6 m-0 fw-bold font-size-14">${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}</span></span>
                                <span class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.total_amount') }}</span>: <span class="h6 m-0 fw-bold  font-size-14">${formatCurrencyvalue(response.product.total_admin_earnings)}</span></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-md-nowrap flex-wrap justify-content-center mt-4">
                            <button id="swal-close-btn" class="btn btn-primary">{{ __('frontend.close') }}</button>
                            <button id="btn-goto-orders" class="btn btn-secondary">{{ __('frontend.go_to_orders') }}</button></div>
                        `,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    document.getElementById('swal-close-btn').onclick = () => window
                                        .location.href = "{{ url('/') }}";
                                    document.getElementById('btn-goto-orders').onclick = () =>
                                        window.location.href = "{{ route('myorder') }}";
                                }
                            });
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to place order. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        button.prop('disabled', false).text(originalText);
                    }
                });
            } else if (paymentMethod === 'wallet') {
                const walletBalance = parseFloat('{{ $walletBalance ?? 0 }}');
                const orderSubtotal = parseFloat('{{ $subtotal ?? 0 }}');
                if (isNaN(walletBalance) || walletBalance < orderSubtotal) {
                    toastr.error('{{ __('frontend.insufficient_wallet_balance') ?? 'Insufficient wallet balance.' }}');
                    button.prop('disabled', false).text(originalText);
                    return;
                }
                $.ajax({
                    url: "{{ url('api/place-order') }}",
                    type: 'POST',
                    data: {
                        shipping_address_id: addressId,
                        billing_address_id: addressId,
                        chosen_logistic_zone_id: deliveryZoneId,
                        payment_method: 'wallet',
                        shipping_delivery_type: 'regular',
                        payment_status: 'paid',
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response){
                        if (response.status) {
                            // Get order code with prefix (already formatted from API)
                            const displayOrderId = response.product?.order_group?.formatted_order_code || response.product?.order_group?.order_code || '';

                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('frontend.order_submitted') }}',
                                html: `
                                    <h5>{{ __('frontend.thank_you_for_your_order') }}</h5>
                                    <p>{{ __('frontend.your_order_has_been_successfully_booked') }}</p>
                                    <div>
                                        ${(displayOrderId ? `<span class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.order_id') }}</span>: <span class="text-primary fw-bold font-size-14">#${displayOrderId}</span></span>` : '')}
                                        <span class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.payment_method') }}</span>: <span class="h6 m-0 fw-bold font-size-14">Wallet</span></span>
                                        <span class="mb-2 d-flex align-items-center justify-content-center gap-2"><span class="h6 m-0 font-size-14">{{ __('frontend.total_amount') }}</span>: <span class="h6 m-0 fw-bold font-size-14">${formatCurrencyvalue(response.product?.total_admin_earnings || 0)}</span></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-md-nowrap flex-wrap justify-content-center mt-4">
                                        <button id="swal-close-btn" class="btn btn-primary">{{ __('frontend.close') }}</button>
                                        <button id="btn-goto-orders" class="btn btn-secondary">{{ __('frontend.go_to_orders') }}</button>
                                    </div>
                                `,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    document.getElementById('swal-close-btn').onclick = () => window.location.href = "{{ url('/') }}";
                                    document.getElementById('btn-goto-orders').onclick = () => window.location.href = "{{ route('myorder') }}";
                                }
                            });
                        } else {
                            toastr.error(response.message || '{{ __('frontend.failed_to_place_order') ?? 'Failed to place order.' }}');
                        }
                    },
                    error: function(xhr){
                        let msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : '{{ __('frontend.failed_to_place_order') ?? 'Failed to place order.' }}';
                        toastr.error(msg);
                    },
                    complete: function(){
                        button.prop('disabled', false).text(originalText);
                    }
                });
            } else if (paymentMethod === 'stripe') {
                // Disable the button and show spinner (optional)
                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );
                // Get the total amount from the payment summary DOM

                fetch("{{ url('/payment/process') }}", {

                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            shipping_address_id: addressId,
                            billing_address_id: addressId,
                            chosen_logistic_zone_id: deliveryZoneId,
                            payment_method: paymentMethod,
                            shipping_delivery_type: 'regular',
                            payment_status: 'unpaid',

                            _token: '{{ csrf_token() }}'
                        })
                    })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success && (response.redirect || response.session_url)) {
                            window.location.href = response.redirect || response.session_url;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Stripe Error',
                                text: response.message ||
                                    '{{ __('frontend.failed_to_initiate_stripe_payment') }}',
                                confirmButtonText: '{{ __('frontend.ok') }}',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false,
                            });
                        }
                    })
                    .catch(err => Swal.fire({
                        icon: 'error',
                        title: 'Stripe Error',
                        text: err.message || '{{ __('frontend.failed_to_initiate_stripe_payment') }}',

                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                    }))
                    .finally(function() {
                        button.prop('disabled', false).text(originalText);
                    });
            } else if (paymentMethod === 'razorpay') {

                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...'
                );

                fetch("{{ url('/payment/process') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({

                            shipping_address_id: addressId,
                            billing_address_id: addressId,
                            chosen_logistic_zone_id: deliveryZoneId,
                            payment_method: paymentMethod,
                            shipping_delivery_type: 'regular',
                            payment_status: 'unpaid',

                        })
                    })
                    .then(res => res.json())
                    .then(response => {

                        if (response.order_id && response.key) {

                            var options = {
                                key: response.razorpayKey,
                                amount: (response.amount * 100).toFixed(2), // Always use backend value in paise
                                currency: response.currency || 'INR',
                                name: '{{ config('app.name') }}',
                                description: 'Order Payment',
                                order_id: response.order.id,
                                handler: function(paymentResponse) {
                                    if (paymentResponse.razorpay_payment_id) {
                                        // Call backend to store transaction
                                        $.post("{{ url('/product/razorpay/success') }}", {
                                            razorpay_payment_id: paymentResponse.razorpay_payment_id,
                                            razorpay_order_id: options.order_id,
                                            _token: '{{ csrf_token() }}'
                                        }, function(response) {
                                            clearCheckoutData();
                                            Swal.fire({
                                                icon: 'success',
                                                title: '{{ __('frontend.payment_successfull') }}',
                                                text: '{{ __('frontend.your_payment_was_successfull_thank') }}',
                                                confirmButtonText: '{{ __('frontend.ok') }}',
                                                customClass: {
                                                    confirmButton: 'btn btn-primary'
                                                },
                                                buttonsStyling: false,
                                            });
                                            window.location.href = "{{ route('myorder') }}";
                                        }).fail(function() {
                                            Swal.fire({
                                                icon: 'error',
                                                title: '{{ __('frontend.server_error') }}',
                                                text: '{{ __('frontend.payment_succeeded_but_server_did_not_record_it_please_contact_support') }}',
                                                confirmButtonText: '{{ __('frontend.ok') }}',
                                                customClass: {
                                                    confirmButton: 'btn btn-primary'
                                                },
                                                buttonsStyling: false,
                                            });
                                        });
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: '{{ __('frontend.payment_failed') }}',
                                            text: '{{ __('frontend.payment_was_not_completed_please_try_again') }}',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false,
                                        });
                                    }
                                },
                                modal: {
                                    ondismiss: function() {
                                        Swal.fire({
                                            icon: 'info',
                                            title: '{{ __('frontend.payment_cancelled') }}',
                                            text: '{{ __('frontend.Payment_was_cancelled_Please_try_again') }}',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false,
                                        });
                                    }
                                },
                                prefill: response.prefill || {},
                                theme: {
                                    color: '#528FF0'
                                }
                            };
                            new Razorpay(options).open();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Razorpay Error',
                                text: response.message || 'Failed to initiate Razorpay payment.',
                                confirmButtonText: '{{ __('frontend.ok') }}',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false,
                            });
                        }
                    })
                    .catch(err => Swal.fire({
                        icon: 'error',
                        title: 'Razorpay Error',
                        text: err.message || 'Failed to initiate Razorpay payment.',
                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                    }))
                    .finally(function() {
                        button.prop('disabled', false).text(originalText);
                    });
            } else {
                toastr.error('Selected payment method is not implemented yet.');
                button.prop('disabled', false).text(originalText);
            }
        }

        function loadDeliveryZonesForAddress(addressId) {
            $.get("{{ route('frontend.delivery-zones') }}", {
                address_id: addressId
            }, function(response) {
                if (response.status && response.zones.length > 0) {
                    let zonesHtml = '';
                    response.zones.forEach(function(zone, index) {
                        const isChecked = index === 0 ? 'checked' : '';
                        const deliveryChargeDisplay = zone.standard_delivery_charge > 0 ?
                            formatCurrencyvalue(zone.standard_delivery_charge) : 'Free';

                        zonesHtml += `
            <div class="bg-gray-800 p-4 rounded d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery_zone" value="${zone.id}" ${isChecked}>
                    </div>
                    <div>
                        <p class="mb-0">${zone.name}</p>
                        ${zone.logistic_name ? `<small class="text-body">${zone.logistic_name}</small>` : ''}
                    </div>
                </div>
                <div>
                <div class="d-flex align-items-center gap-lg-4 gap-2">
                    <h5 class="mb-0 text-primary">${deliveryChargeDisplay}</h5>
                </div>
                 <small class="text-body">Estimate delivery in ${zone.standard_delivery_time || '3-5'} days</small>
                </div>


            </div>
        `;
                    });

                    $('.charges-block .delivery-zones-container').html(zonesHtml);

                    // Re-attach event listeners to new radio buttons
                    $('input[name="delivery_zone"]').on('change', function() {
                        updateCheckoutSummary();
                    });

                    // Update summary with first zone
                    updateCheckoutSummary();
                    $('.onclick-page-redirect button').prop('disabled', false); // Enable button
                    $('#inlineAddressError').hide();
                } else {
                    // Show message if no zones available for this address
                    $('.charges-block .delivery-zones-container').html(`
                <div class="bg-gray-800 p-4 rounded text-center">
                    <h6 class="mb-0">{{ __('frontend.no_delivery_zones_available_for_this_address_please_select_a_different_address') }}</h6>
                </div>
            `);
                    $('.onclick-page-redirect button').prop('disabled', true); // Disable button
                    $('#inlineAddressError').text(
                        'No delivery zones available for the selected address. Please select a different address.'
                    ).show();
                }
            }).fail(function() {
                $('.charges-block .delivery-zones-container').html(`
            <div class="bg-gray-800 p-4 rounded text-center">
                <p class="mb-0 text-danger">{{ __('frontend.failed_to_load_delivery_zones_please_try_again') }}</p>
            </div>
        `);
                $('.onclick-page-redirect button').prop('disabled', true); // Disable button
                $('#inlineAddressError').text('Failed to load delivery zones. Please try again.').show();
            });
        }

        function toggleAddressForm() {

            const form = document.getElementById('inlineAddAddressForm');
            const errorDiv = document.getElementById('inlineAddressError');
            if (!form) {

                if (errorDiv) {
                    errorDiv.innerText = 'Error: Address form not found on the page.';
                    errorDiv.style.display = 'block';
                }
                return;
            }
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';

            if (errorDiv) errorDiv.style.display = 'none';
        }

        function saveInlineAddress(event) {
            event.preventDefault();

            var form = document.getElementById('inline_addressForm');
            if (!form) {

                return;
            }
            var formData = new FormData(form);

            $.ajax({
                url: "{{ route('frontend.address.store') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.status) {
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function() {
                    toastr.error('Failed to save address. Please try again.');
                }
            });
        }

        function updateOrderSummaryBox() {


            var selectedAddress = $('input[name="address"]:checked').closest('.bg-gray-800');

            $('#order-summary-username').text('');
            $('#order-summary-email').text('');
            $('#order-summary-mobile').text('');

            // Always use the logged-in user's name, email, and mobile
            var userName = selectedAddress.find('.user-name').text() || window.loggedInUserName || '';
            var userEmail = selectedAddress.find('.user-email').text() || window.loggedInUserEmail || '';
            var userMobile = selectedAddress.find('.user-contact-number').text() || window.loggedInUserMobile || '';

            $('#order-summary-username').text(userName);
            $('#order-summary-email').text(userEmail);
            $('#order-summary-mobile').text(userMobile);

            // Get address from selected address radio

            var addressLines = selectedAddress.find('p.address-line').map(function() {
                return $(this).text();
            }).get().join('<br>');
            $('#order-summary-address').html(addressLines);
        }



        // Update order summary when table is redrawn or address changes
        $(document).ready(function() {
            $('#checkout-table').on('draw.dt', function() {
                updateOrderSummaryBox();
            });
            $(document).on('change', 'input[name="address"]', function() {

                updateOrderSummaryBox();
            });
            // Initial call
            updateOrderSummaryBox();
        });

        window.loggedInUserName = "{{ (auth()->user()->first_name ?? '') . ' ' . (auth()->user()->last_name ?? '') }}";
        window.loggedInUserEmail = "{{ auth()->user()->email ?? '' }}";
        window.loggedInUserMobile = "{{ auth()->user()->mobile ?? '' }}";

        $(document).on('click', '.editable-span', function() {
            var $span = $(this);
            var currentValue = $span.text();
            var inputType = $span.attr('id') === 'order-summary-email' ? 'email' : 'text';
            var $input = $('<input type="' + inputType +
                    '" class="form-control d-inline-block editable-input" style="width: 70%; max-width: 220px;" />')
                .val(currentValue);
            $span.replaceWith($input);
            $input.focus();

            function saveInput() {
                var newValue = $input.val();
                var newSpan = $('<span></span>')
                    .attr('id', $input.attr('id'))
                    .addClass('editable-field editable-span')
                    .text(newValue);
                $input.replaceWith(newSpan);
                // Re-apply cursor style for dynamically created elements
                $('.editable-field, .editable-span').css('cursor', 'pointer');
            }

            $input.on('blur', saveInput);
            $input.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveInput();
                }
            });
        });

        // Add this function to clear cart/checkout data
        function clearCheckoutData() {
            // If you use localStorage or sessionStorage for cart, clear it here
            if (window.localStorage) {
                localStorage.removeItem('cart');
                localStorage.removeItem('checkout');
            }
            // Optionally, reload the cart table if using AJAX
            if ($('#checkout-table').length) {
                $('#checkout-table').DataTable().clear().draw();
            }
            // Optionally, clear any summary boxes
            $('#checkout-summary').html('');
            $('#order-summary-box').html('');
            // Hide table and show empty cart message
            $('#checkout-table').hide();
            $('#empty-cart-message').show();
            $('#checkout-sections').hide();
            $('.cart-summary').hide();
            $('.col-md-5.col-lg-3').hide(); // Hide right-side content
        }

        // function selectAddress(addressId, element) {
        //     // Remove active class from all address cards
        //     $('.address-card').removeClass('active');

        //     // Add active class to clicked card
        //     $(element).addClass('active');

        //     // Check the radio button for this address
        //     $(element).find('.address-radio').prop('checked', true).trigger('change');
        // }
        function selectAddress(addressId, clickedCard) {
            const block = document.querySelector('.address-block');
            const currentPrimary = block.querySelector('.address-card.active');
            const collapseSection = document.getElementById('otherAddresses');

            // 👉 Prevent re-selecting the same address
            if (currentPrimary && currentPrimary.dataset.id === String(addressId)) {
                if (collapseSection && collapseSection.classList.contains('show')) {
                    const collapseInstance = bootstrap.Collapse.getInstance(collapseSection) || new bootstrap.Collapse(
                        collapseSection);
                    collapseInstance.hide();
                }
                return;
            }

            // Remove active from all
            document.querySelectorAll('.address-card').forEach(card => card.classList.remove('active'));
            // Uncheck all radios
            document.querySelectorAll('.address-radio').forEach(radio => radio.checked = false);

            // Clone clicked
            const newPrimary = clickedCard.cloneNode(true);
            newPrimary.classList.add('active');
            newPrimary.setAttribute('onclick', `selectAddress(${addressId}, this)`);

            const newPrimaryRadio = newPrimary.querySelector('.address-radio');
            if (newPrimaryRadio) newPrimaryRadio.checked = true;

            if (currentPrimary && currentPrimary !== clickedCard) {
                // Clone current primary to move to collapse
                const oldPrimary = currentPrimary.cloneNode(true);
                oldPrimary.classList.remove('active');
                oldPrimary.setAttribute('onclick', `selectAddress(${currentPrimary.dataset.id}, this)`);
                const oldRadio = oldPrimary.querySelector('.address-radio');
                if (oldRadio) oldRadio.checked = false;

                // Append old primary to collapse
                if (collapseSection) {
                    collapseSection.prepend(oldPrimary);
                }

                // Remove the clicked card from collapse
                clickedCard.remove();

                // Replace current primary with new selected
                currentPrimary.replaceWith(newPrimary);
            }

            loadDeliveryZonesForAddress(addressId);
            updateOrderSummaryBox();
            
            // Show success toast
            if (typeof toastr !== 'undefined') {
                toastr.success('{{ __("frontend.delivery_address_updated") }}', '', {
                    closeButton: true,
                    progressBar: true,
                    positionClass: 'toast-top-right',
                    timeOut: 3000
                });
            } 

            // Collapse section if open
            if (collapseSection && collapseSection.classList.contains('show')) {
                const collapseInstance = bootstrap.Collapse.getInstance(collapseSection) || new bootstrap.Collapse(
                    collapseSection);
                collapseInstance.hide();
            }
        }

        function prefillUserName() {
            // Reset modal title to "Add New Address"
            $('#modalAddAddressLabel').text('Add New Address');
            
            // Clear form and errors
            $('#addressForm')[0].reset();
            $('#address_id').val('');
            $('#addressForm').find('.invalid-feedback').text('').hide();
            $('#addressForm').find('.is-invalid').removeClass('is-invalid');

            // Prefill first and last name fields with logged-in user's name
            var firstName = "{{ auth()->user()->first_name ?? '' }}";
            var lastName = "{{ auth()->user()->last_name ?? '' }}";
            var email = "{{ auth()->user()->email ?? '' }}";
            var contact_number = "{{ auth()->user()->mobile ?? '' }}";
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('email').value = email;
            
            // Set the phone number with correct country
            if (mobileInput && window.addIti) {
                try {
                    if (contact_number && contact_number.trim().length) {
                        if (contact_number.trim().startsWith('+')) {
                            window.addIti.setNumber(contact_number.trim());
                        } else {
                            // Legacy stored number without '+' — try to coerce
                            window.addIti.setNumber('+' + contact_number.trim());
                        }
                    } else {
                        window.addIti.setNumber('');
                    }
                } catch(e) {
                    mobileInput.value = contact_number || '';
                }
            } else {
                document.getElementById('mobileInput').value = contact_number;
            }

            // Clear dropdowns
            $('#country').html('<option value="" disabled selected>{{ __('frontend.select_country') }}</option>');
            $('#state').html('<option value="" disabled selected>{{ __('frontend.select_state') }}</option>');
            $('#city').html('<option value="" disabled selected>{{ __('frontend.select_city') }}</option>');

            // Load countries dropdown
            loadDropdownData("{{ route('frontend.address.get-countries') }}", $('#country')[0], 'Select Country');
        }

        // Payment method selection function
        function selectPaymentMethod(method, element) {
            // Remove active class from all payment method cards
            $('.payment-method-card').removeClass('active');

            // Add active class to clicked card
            $(element).addClass('active');

            // Check the radio button for this payment method
            $(element).find('.payment-radio').prop('checked', true).trigger('change');

            // Update payment summary based on selected method
            updatePaymentSummary(method);
        }

        // Update payment summary based on selected payment method
        function updatePaymentSummary(selectedMethod) {
            const subtotal = parseFloat('{{ $subtotal ?? 0 }}');
            const walletBalance = parseFloat('{{ $walletBalance ?? 0 }}');

            if (selectedMethod === 'wallet') {
                if (walletBalance >= subtotal) {
                    // Full payment from wallet
                    $('#wallet-payment-info').html(`
                        <div class="alert alert-success">
                            <i class="ph ph-check-circle"></i> Full payment will be deducted from your wallet balance.
                            <br><small>Remaining balance: ${formatCurrencyvalue(walletBalance - subtotal)}</small>
                        </div>
                    `);
                } else {
                    // Partial payment from wallet
                    const remaining = subtotal - walletBalance;
                    $('#wallet-payment-info').html(`
                        <div class="alert alert-warning">
                            <i class="ph ph-warning"></i> Partial payment from wallet (${formatCurrencyvalue(walletBalance)}).
                            <br><small>Remaining amount (${formatCurrencyvalue(remaining)}) will be charged via another payment method.</small>
                        </div>
                    `);
                }
            } else {
                // Clear wallet payment info for other payment methods
                $('#wallet-payment-info').html('');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Handle tax collapse toggle for checkout
            const taxToggleCheckout = document.querySelector('[href="#taxDetailsCheckout"]');
            const taxDetailsCheckout = document.getElementById('taxDetailsCheckout');
            const taxIconCheckout = document.querySelector('.tax2');

            if (taxToggleCheckout && taxDetailsCheckout && taxIconCheckout) {
                taxToggleCheckout.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isExpanded = taxDetailsCheckout.classList.contains('show');
                    if (isExpanded) {
                        taxIconCheckout.style.transform = 'rotate(0deg)';
                    } else {
                        taxIconCheckout.style.transform = 'rotate(180deg)';
                    }
                });
            }

            // Add event listeners for address form dropdowns
            const countrySelect = document.getElementById('country');
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');

            if (countrySelect) {
                countrySelect.addEventListener('change', function() {
                    const countryId = this.value;
                    // Clear state and city dropdowns
                    stateSelect.innerHTML = '<option value="" disabled selected>Select State</option>';
                    citySelect.innerHTML = '<option value="" disabled selected>Select City</option>';

                    if (countryId) {
                        loadDropdownData("{{ route('frontend.address.get-states') }}?country_id=" +
                            countryId, stateSelect, 'Select State');
                    }
                });
            }

            if (stateSelect) {
                stateSelect.addEventListener('change', function() {
                    const stateId = this.value;
                    // Clear city dropdown
                    citySelect.innerHTML = '<option value="" disabled selected>Select City</option>';

                    if (stateId) {
                        loadDropdownData("{{ route('frontend.address.get-cities') }}?state_id=" + stateId,
                            citySelect, 'Select City');
                    }
                });
            }

            // Initialize payment method selection
            $('.payment-radio').on('change', function() {
                const selectedMethod = $(this).val();
                updatePaymentSummary(selectedMethod);
            });
        });

        // Handle payment method selection
        document.addEventListener('DOMContentLoaded', function() {
            const paymentMethodDropdown = document.getElementById('payment-method-dropdown');
            const selectedMethodBtn = document.getElementById('selected-method-btn');
            const selectedMethodImg = document.getElementById('selected-method-img');
            const selectedMethodName = document.getElementById('selected-method-name');
            const paymentRadios = document.querySelectorAll('.payment-radio');
            const paymentMethodList = document.getElementById('payment-method-list');

            // Close dropdown when clicking outside
            document.addEventListener('click', function(event) {
                if (paymentMethodDropdown && !paymentMethodDropdown.contains(event.target)) {
                    if (paymentMethodList) {
                        paymentMethodList.classList.remove('show');
                    }
                }
            });

            // Toggle dropdown
            if (selectedMethodBtn && paymentMethodList) {
                selectedMethodBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    paymentMethodList.classList.toggle('show');
                });
            }

            // Handle radio button changes
            paymentRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const label = this.closest('label');
                        const img = label.querySelector('img');
                        const name = label.querySelector('span:not(.text-success)').textContent
                            .trim();

                        // Update selected method display
                        if (selectedMethodImg && img) {
                            selectedMethodImg.src = img.src;
                            selectedMethodImg.alt = img.alt;
                        }
                        if (selectedMethodName && name) {
                            selectedMethodName.textContent = name;
                        }

                        // Close the dropdown
                        if (paymentMethodList) {
                            paymentMethodList.classList.remove('show');
                        }
                    }
                });
            });

            // Initialize with the first checked radio
            const initialChecked = document.querySelector('.payment-radio:checked');
            if (initialChecked) {
                const label = initialChecked.closest('label');
                const img = label.querySelector('img');
                const name = label.querySelector('span:not(.text-success)').textContent.trim();

                if (selectedMethodImg && img) {
                    selectedMethodImg.src = img.src;
                    selectedMethodImg.alt = img.alt;
                }
                if (selectedMethodName && name) {
                    selectedMethodName.textContent = name;
                }
            }
        });
    </script>
@endpush

@push('styles')
    <!-- <style>
            /* Payment Method Dropdown Styles */
            .payment-method-dropdown {
                position: relative;
                margin-bottom: 1rem;
            }

            .payment-method-dropdown .dropdown-menu {
                padding: 0;
                margin-top: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
                border: 1px solid #dee2e6;
                max-height: 300px;
                overflow-y: auto;
                display: none;
            }

            .payment-method-dropdown .dropdown-menu.show {
                display: block;
                animation: fadeIn 0.2s ease-in-out;
            }

            .payment-method-dropdown .list-group-item {
                border-radius: 0;
                border-left: none;
                border-right: none;
                padding: 0.75rem 1.25rem;
                transition: all 0.2s ease;
            }

            .payment-method-dropdown .list-group-item:first-child {
                border-top: none;
            }

            .payment-method-dropdown .list-group-item:last-child {
                border-bottom: none;
            }

            .payment-method-dropdown .list-group-item:hover {
                background-color: #f8f9fa;
            }

            .payment-method-dropdown .form-check-input {
                margin-top: 0;
            }

            .payment-method-dropdown .avatar-28 {
                width: 28px;
                height: 28px;
                object-fit: contain;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Hide default radio button */
            .payment-radio {
                position: absolute;
                opacity: 0;
                width: 0;
                height: 0;
            }

            /* Custom radio button */
            .payment-radio+.form-check-label::before {
                content: '';
                display: inline-block;
                width: 1.25rem;
                height: 1.25rem;
                border: 2px solid #dee2e6;
                border-radius: 50%;
                margin-right: 0.5rem;
                vertical-align: middle;
                transition: all 0.2s ease;
            }

            .payment-radio:checked+.form-check-label::before {
                border-color: #0d6efd;
                background-color: #0d6efd;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
                background-position: center;
                background-repeat: no-repeat;
                background-size: 60% 60%;
            }

            .order-summary-products-list {
                max-width: 220px;
            }

            .order-summary-product-name {
                display: inline-block;
                width: 120px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                vertical-align: middle;
            }

            .payment-method-card {
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }

            .payment-method-card:hover {
                border-color: #28a745;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .payment-method-card.active {
                border-color: #28a745;
                background-color: rgba(40, 167, 69, 0.1) !important;
            }

            .wallet-balance-info {
                animation: fadeIn 0.5s ease-in;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .editable-field {
                cursor: pointer;
                transition: color 0.2s, text-decoration 0.2s;
            }

            .editable-field:hover {
                color: #0d6efd;
                text-decoration: underline;
            }

            /* Address card styles */
            .address-card {
                border: 2px solid transparent;
                transition: all 0.3s ease;
            }

            .address-card:hover {
                border-color: rgba(13, 110, 253, 0.2);
                background-color: rgba(13, 110, 253, 0.05);
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .address-card.active {
                border-color: #0d6efd;
                background-color: rgba(13, 110, 253, 0.1);
                box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
            }

            /* Prevent text selection on address cards */
            .address-card {
                user-select: none;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
            }
        </style> -->


    <style>
.iti .iti__country-list {
    background-color: var(--bs-gary-900);
    box-shadow: none;
    border-color: var(--bs-border-color);
}
    </style>
@endpush
