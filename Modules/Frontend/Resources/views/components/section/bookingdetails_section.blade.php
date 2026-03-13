@php
    $useBackendPaymentSummary = isset($paymentSummary) && is_array($paymentSummary) && array_key_exists('subtotal', $paymentSummary);
    $branchId = $booking->branch_id ?? null;
    $serviceSubtotal = 0;
    foreach ($booking->booking_service as $service) {
        $servicePrice = $service->service_price ?? 0;
        // Get branch-specific price if branch exists
        if ($branchId && $service->service_id) {
            $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                ->where('branch_id', $branchId)
                ->first();
            if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                $servicePrice = $branchService->service_price;
            }
        }
        $serviceSubtotal += $servicePrice;
    }
    $productSubtotal = isset($booking->products) ? collect($booking->products)->sum('discounted_price') : 0;
    $packageSubtotal = isset($booking->packages) ? collect($booking->packages)->sum('package_price') : 0;
    
    // Total subtotal for display
    $subtotal = $serviceSubtotal + $productSubtotal + $packageSubtotal;
    
    // Taxable subtotal (Services + Packages) - as per backend logic
    $taxableSubtotal = $serviceSubtotal + $packageSubtotal;

    if (!isset($employeeReview)) {
        $employeeReview = null;
    }
    $latestTransaction =
        $booking->bookingTransaction && count($booking->bookingTransaction) > 0
            ? collect($booking->bookingTransaction)->sortByDesc('id')->first()
            : null;
    $discountAmount = $latestTransaction->discount_amount ?? 0;
    $couponPercent = $latestTransaction->discount_percentage ?? 0;

    $totalTax = 0;
    $taxBase = max(0, $taxableSubtotal - $discountAmount);

    if (isset($taxes) && $taxes->count() > 0 && $taxBase > 0) {
        foreach ($taxes as $taxItem) {
            if ($taxItem->type == 'fixed') {
                $taxAmount = $taxItem->value;
            } else {
                $taxAmount = ($taxBase * $taxItem->value) / 100;
            }
            $totalTax += $taxAmount;
        }
    }

    $status = strtolower($booking->status);
    $statusColor = match ($status) {
        'pending' => 'text-warning',
        'confirmed' => 'text-primary',
        'cancelled' => 'text-danger',
        'complete', 'completed' => 'text-success',
        default => 'text-secondary',
    };

    if ($useBackendPaymentSummary) {
        $subtotal = (float) $paymentSummary['subtotal'];
        $discountAmount = (float) ($paymentSummary['coupon_discount'] ?? 0);
        $taxSum = (float) ($paymentSummary['tax_sum'] ?? 0);
        $couponPercent = (float) ($paymentSummary['coupon_percent'] ?? 0);
        $finalTotal = (float) ($paymentSummary['total'] ?? 0);
        $taxes = $booking->payment->tax_percentage ?? [];
    } else {
        if (!isset($taxes)) {
            $taxes = \Modules\Tax\Models\Tax::where('status', 1)
                ->where(function ($query) {
                    $query->where('module_type', 'services')->orWhereNull('module_type');
                })
                ->get();
        }
        $serviceSubtotal = 0;
        foreach ($booking->booking_service ?? [] as $service) {
            $servicePrice = $service->service_price ?? 0;
            if ($branchId && $service->service_id) {
                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                    ->where('branch_id', $branchId)
                    ->first();
                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                    $servicePrice = $branchService->service_price;
                }
            }
            $serviceSubtotal += $servicePrice;
        }
        $productSubtotal = isset($booking->products) ? collect($booking->products)->sum('discounted_price') : 0;
        $packageSubtotal = isset($booking->packages) ? collect($booking->packages)->sum('package_price') : 0;
        $subtotal = $serviceSubtotal + $productSubtotal + $packageSubtotal;
        $latestTransaction =
            $booking->bookingTransaction && count($booking->bookingTransaction) > 0
                ? collect($booking->bookingTransaction)->sortByDesc('id')->first()
                : null;
        $discountFromTransaction = (float) ($latestTransaction->discount_amount ?? 0);
        $discountFromCouponRedeem = (float) (optional($booking->userCouponRedeem)->discount ?? 0);
        $discountAmount = $discountFromTransaction > 0 ? $discountFromTransaction : $discountFromCouponRedeem;
        $couponPercent = $latestTransaction->discount_percentage ?? 0;
        $taxSum = 0;
        $taxes = $booking->payment->tax_percentage ?? [];
        if (!empty($taxes) && is_array($taxes) && $subtotal > 0) {
            foreach ($taxes as $taxItem) {
                if (($taxItem['type'] ?? '') == 'fixed') {
                    $taxSum += $taxItem['tax_amount'] ?? $taxItem['amount'] ?? 0;
                } else {
                    $taxSum += (($subtotal - $discountAmount) * ($taxItem['percent'] ?? 0)) / 100;
                }
            }
        }
        $finalTotal = $subtotal + $taxSum - $discountAmount;
        $taxes = $booking->payment->tax_percentage ?? [];
    }
    $coupon_discount_display = $discountAmount;
    $coupon_applied = $coupon_discount_display > 0 || $booking->userCouponRedeem !== null;
@endphp

<div class="row mt-5">
    <div class="col-lg-8">
        <div class="mb-5">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <a href="{{ route('bookings') }}" class="text-body fw-medium">
                    <span class="d-flex align-items-center gap-1">
                        <i class="ph ph-caret-left"></i>
                        <span>{{ __('frontend.back') }}</span>
                    </span>
                </a>
                @php
                    $rawPaymentStatus = $booking->payment->payment_status ?? null;
                    $paymentStatus =
                        $rawPaymentStatus === 1 || $rawPaymentStatus === '1' || strtolower($rawPaymentStatus) === 'paid'
                            ? 'Paid'
                            : 'Unpaid';
                @endphp
                @if ($paymentStatus === 'Paid' && in_array($status, ['completed','complete']))
                    <a href="{{ route('booking.invoice.download', $booking->id) }}"
                        class="btn btn-primary">{{ __('frontend.download_invoice') }}</a>
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
            @if (empty($employeeReview) && $paymentStatus === 'Paid' && in_array($status, ['completed','complete']))
                <h5 class="mb-0 mt-0">{{ __('frontend.you_havent_rated_yet') }}</h5>
                <button class="fw-semibold letter-spacing-2-percent btn btn-link" data-bs-toggle="modal"
                    data-bs-target="#review-service">{{ __('frontend.rate_now') }}</button>
            @endif
        </div>

        <div class="mt-5">
            <div class="booking-details-box booking-details-box-20">
                <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <h5 class="flex-grow-1 mb-0">
                        {{ __('frontend.booking_id') }}
                    </h5>
                    <span class="flex-shrink-0 text-primary">#{{ $booking->id ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h5 class="mb-2 mt-0">{{ __('frontend.salon_information') }}</h5>
            <div class="booking-details-box booking-details-box-30">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.salon_name') }}</h6>
                        <span class="font-size-14">{{ $booking->branch->name ?? '-' }}</span>
                    </div>
                    <div class="col-lg-4 col-md-6 mt-3 mt-md-0">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.address') }}</h6>
                        <span class="font-size-14">
                            {{ $booking->branch->address->address_line_1 ?? '-' }}
                            {{ $booking->branch->address->address_line_2 ? ', ' . $booking->branch->address->address_line_2 : '' }}
                        </span>
                    </div>
                    <div class="col-lg-4 col-md-12 mt-3 mt-lg-0">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.phone') }}</h6>
                        <span class="font-size-14">{{ $booking->branch->contact_number ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h5 class="mb-2 mt-0">{{ __('frontend.booking_information') }}</h5>
            <div class="booking-details-box booking-details-box-30">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.date_and_time') }}</h6>
                        <span
                            class="font-size-14">{{ \Carbon\Carbon::parse($booking->start_date_time)->format('d/m/Y \a\t h:i A') }}</span>
                    </div>
                    <div class="col-lg-3 col-md-6 mt-3 mt-md-0">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.specialist') }}</h6>
                        <span class="font-size-14">
                            {{ $booking->booking_service->first()->employee->full_name ?? '-' }}
                        </span>
                    </div>
                    <div class="col-lg-3 col-md-6 mt-3 mt-lg-0">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.booking_status') }}</h6>
                        <span class="font-size-14 {{ $statusColor }}">{{ ucfirst($booking->status ?? '-') }}</span>
                    </div>
                    <div class="col-lg-3 col-md-6 mt-3 mt-lg-0">
                        <h6 class="mb-1 fw-normal font-size-14">{{ __('frontend.payment_status') }}</h6>
                        @php
                            $rawPaymentStatus = $booking->payment->payment_status ?? null;
                            $paymentStatus =
                                $rawPaymentStatus === 1 ||
                                $rawPaymentStatus === '1' ||
                                strtolower($rawPaymentStatus) === 'paid'
                                    ? 'Paid'
                                    : 'Unpaid';
                            $paymentColor = $paymentStatus === 'Paid' ? 'text-success' : 'text-danger';
                        @endphp
                        <span class="font-size-14 {{ $paymentColor }}">{{ $paymentStatus }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h5 class="mb-2 mt-0">{{ __('frontend.services') }}</h5>
            <div class="booking-details-box booking-details-box-30">
                <ul class="list-inline m-0 p-0">
                    @foreach ($booking->booking_service as $service)
                        @php
                            // Get branch-specific price, fallback to saved service_price or default_price
                            $servicePrice = $service->service_price ?? 0;
                            if ($branchId && $service->service_id) {
                                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                                    ->where('branch_id', $branchId)
                                    ->first();
                                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                                    $servicePrice = $branchService->service_price;
                                }
                            }
                            // Final fallback to default_price if service_price is still 0
                            if ($servicePrice == 0 && $service->service && $service->service->default_price) {
                                $servicePrice = $service->service->default_price;
                            }
                        @endphp
                        <li class="mb-2 pb-1 border-bottom">
                            <span class="d-flex align-items-center justify-content-between gap-3">
                                <span class="d-flex align-items-center gap-2 flex-grow-1">
                                    @if ($service->service && $service->service->feature_image)
                                        <img src="{{ asset($service->service->feature_image) }}"
                                            alt="{{ $service->service->name ?? 'Service' }}" class="rounded"
                                            style="width: 40px; height: 40px; object-fit: cover;">
                                    @endif
                                    <span class="font-size-14">{{ $service->service->name ?? '-' }}</span>
                                </span>
                                <span
                                    class="flex-shrink-0 font-size-14 heading-color">{{ \Currency::format($servicePrice) }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if (!empty($booking->packages) && count($booking->packages) > 0)
            <div class="mt-5">
                <h5 class="mb-2 mt-0">{{ __('frontend.package_details') }}</h5>
                <div class="booking-details-box booking-details-box-30">
                    @foreach ($booking->packages as $package)
                        <div
                            class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3 pb-3 border-bottom">
                            <span class="flex-grow-1 font-size-14">{{ $package['name'] ?? '-' }}:</span>
                            <span
                                class="flex-shrink-0 font-size-14 heading-color">{{ \Currency::format($package['package_price'] ?? 0) }}</span>
                        </div>
                        <h6 class="mb-3 font-size-14">{{ __('frontend.your_booked_services') }}</h6>
                        <ul class="list-inline m-0 p-0">
                            @foreach ($package['services'] ?? [] as $pkgService)
                                <li>
                                    <span
                                        class="d-flex align-items-sm-baseline justify-content-between gap-2 font-size-14 flex-sm-row flex-column">
                                        <span
                                            class="d-flex align-items-center flex-wrap row-gap-1 column-gap-3 flex-grow-1">
                                            <i class="ph ph-arrow-right"></i>
                                            <span>{{ $pkgService['service_name'] ?? '-' }} - <span
                                                    class="heading-color">{{ $pkgService['duration'] ?? '-' }}
                                                    mins</span></span>
                                            @if (isset($pkgService['remaining']))
                                                <span class="text-primary">(remaining -
                                                    {{ $pkgService['remaining'] }})</span>
                                            @endif
                                        </span>
                                        <span class="flex-shrink-0 d-flex align-items-center gap-2">
                                            <span>Qty:</span>
                                            <span class="heading-color">{{ $pkgService['qty'] ?? '1' }}</span>
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-5">
            <h5 class="mb-2 mt-0">{{ __('frontend.payment_mode') }}</h5>
            <div class="payments-container bg-gray-800 rounded mt-3">
                <div class="d-flex align-items-center gap-2">
                    @php
                        // Use transaction_type from latest bookingTransaction as payment method
                        $paymentMethod = '-';
                        $paymentIcon = 'stripe.svg';
                        if (!empty($booking->bookingTransaction) && count($booking->bookingTransaction) > 0) {
                            $latestTransaction = collect($booking->bookingTransaction)->sortByDesc('id')->first();
                            $paymentMethod = $latestTransaction->transaction_type ?? '-';
                        }
                        switch (strtolower($paymentMethod)) {
                            case 'stripe':
                                $paymentIcon = 'stripe.svg';
                                break;
                            case 'cash':
                                $paymentIcon = 'cash.svg';
                                break;
                            case 'razorpay':
                                $paymentIcon = 'razorpay.svg';
                                break;
                            case 'paystack':
                                $paymentIcon = 'paystack.svg';
                                break;
                            case 'paypal':
                                $paymentIcon = 'paypal.svg';
                                break;
                            case 'flutterwave':
                                $paymentIcon = 'flutterwave.svg';
                                break;
                            default:
                                $paymentIcon = 'stripe.svg';
                                break;
                        }
                    @endphp
                    <img src="{{ asset('img/frontend/' . $paymentIcon) }}" alt="{{ $paymentMethod }}"
                        class="flex-shrink-0 avatar avatar-18">
                    <span
                        class="flex-shrink-0 font-size-14 fw-medium heading-color">{{ ucfirst($paymentMethod) }}</span>
                </div>
            </div>
        </div>

        {{-- Review Card Section (after Payment Mode) --}}
        <div id="review-section">
            @if (!empty($employeeReview))
                <div class="mt-5">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                        <h5 class="mb-0">{{ __('frontend.rate_our_services') }}</h5>
                        <div class="d-flex align-items-center gap-lg-4 gap-2 flex-shrink-0">
                            <button class="btn btn-link text-success border-0 fs-5 edit-review-btn"
                                data-bs-toggle="modal" data-bs-target="#review-service"><i
                                    class="ph ph-pencil-simple-line align-middle"></i></button>
                            <button class="btn btn-link border-0 fs-5 delete-review-btn"><i
                                    class="ph ph-trash align-middle"></i></button>
                        </div>
                    </div>
                    <div class="row gy-5">
                        <div class="col-12">
                            <div class="review-card">
                                <div class="review-card-user-info">
                                    <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-3">
                                        <div class="flex-shrink-0">
                                            <img src="{{ auth()->user()->profile_image ?? asset('img/frontend/rating-user.png') }}"
                                                alt="review-card-user-image" class="review-card-user-image">
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="d-flex align-items-baseline justify-content-between gap-2">
                                                <span
                                                    class="d-inline-flex align-items-center gap-1 rouneded bg-white rounded py-1 px-2 lh-base border-radius">
                                                    <span class="text-warning">
                                                        <i class="ph-fill ph-star"></i>
                                                    </span>
                                                    <span class="fw-medium font-size-14 text-secondary"
                                                        id="review-rating-display">{{ $employeeReview->rating ?? '-' }}</span>
                                                </span>
                                                <div class="flex-shrink-0 font-size-14 fw-medium"
                                                    id="review-date-display">
                                                    {{ $employeeReview->created_at ? \Carbon\Carbon::parse($employeeReview->created_at)->format('d/m/Y') : '-' }}
                                                </div>
                                            </span>
                                            <h6 class="mt-1 mb-0 rating-card-user-title" id="review-user-display">
                                                {{ auth()->user()->full_name ?? '-' }}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-card-content mt-4">
                                    <p class="m-0" id="review-text-display">
                                        {{ $employeeReview->review_msg ?? '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- <div class="mt-5 text-center">
                <button class="fw-semibold letter-spacing-2-percent btn btn-link" id="rate-now-btn" data-bs-toggle="modal" data-bs-target="#review-service">Rate Now</button>
            </div> -->
            @endif
        </div>
    </div>
    <div class="col-lg-4 mt-lg-0 mt-5">
        <h5 class="mb-2">{{ __('frontend.payment_details') }}</h5>
        <div class="payment-section">
            <div class="payment-summary">
                {{-- Services --}}
                @foreach ($booking->booking_service as $service)
                    @php
                        // Get branch-specific price, fallback to saved service_price or default_price
                        $servicePrice = $service->service_price ?? 0;
                        if ($branchId && $service->service_id) {
                            $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                                ->where('branch_id', $branchId)
                                ->first();
                            if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                                $servicePrice = $branchService->service_price;
                            }
                        }
                        // Final fallback to default_price if service_price is still 0
                        if ($servicePrice == 0 && $service->service && $service->service->default_price) {
                            $servicePrice = $service->service->default_price;
                        }
                    @endphp
                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                        <span class="font-size-14">{{ $service->service->name ?? '-' }}</span>
                        <span class="font-size-14 fw-medium heading-color">
                            {{ \Currency::format($servicePrice) }}
                        </span>
                    </div>
                @endforeach
                {{-- Products --}}
                @if ($booking->products && count($booking->products))
                    @foreach ($booking->products as $product)
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">{{ $product->product->name ?? '-' }}
                                (x{{ $product->product_qty ?? 1 }})
                            </span>
                            <span class="font-size-14 fw-medium heading-color">
                                {{ \Currency::format($product->discounted_price ?? $product->product_price) }}
                            </span>
                        </div>
                    @endforeach
                @endif
                {{-- Packages --}}
                @if ($booking->packages && count($booking->packages))
                    @foreach ($booking->packages as $package)
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                            <span class="font-size-14">{{ $package['name'] ?? '-' }}</span>
                            <span class="font-size-14 fw-medium heading-color">
                                {{ \Currency::format($package['package_price'] ?? 0) }}
                            </span>
                        </div>
                    @endforeach
                @endif
                <hr class="line-divider" />

                {{-- Coupon Discount: show when coupon is applied (Payment Details) --}}
                @if ($coupon_applied)
                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                        <span class="font-size-14">{{ __('frontend.coupon_discount') }} @if ($couponPercent > 0)
                                ({{ $couponPercent }}%)
                            @endif
                        </span>
                        <span class="font-size-14 fw-medium text-success">
                            -{{ \Currency::format($coupon_discount_display) }}
                        </span>
                    </div>
                @endif
                {{-- Subtotal (after discount) --}}
                <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                    <span class="font-size-14">{{ __('frontend.subtotal') }}</span>
                    <span class="font-size-14 fw-medium heading-color">
                        {{ \Currency::format($subtotal - $coupon_discount_display) }}
                    </span>
                </div>
                
                {{-- Tax (visible row + collapsible breakdown) --}}
                @php
                    $taxSum = 0;
                    $taxes = $booking->payment->tax_percentage ?? [];

                @endphp
                @if (!empty($taxes) && is_array($taxes) && count($taxes) > 0 && $taxableSubtotal > 0)
                    @foreach ($taxes as $taxItem)
                        @php
                            if ($taxItem['type'] == 'fixed') {
                                $taxAmount = $taxItem['tax_amount'] ?? ($taxItem['amount'] ?? 0);
                            } else {
                                $taxAmount = (max(0, $taxableSubtotal - $discountAmount) * $taxItem['percent']) / 100;
                            }
                            $taxSum += $taxAmount;
                        @endphp
                    @endforeach
                @endif



                @if ($taxSum > 0)
                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                        <span class="font-size-14">{{ __('frontend.tax') }}</span>
                        <div class="d-flex justify-content-between align-items-center mb-1 price-item text-decoration-none taxDetails"
                            data-bs-toggle="collapse" href="#taxDetailsBreakdown" role="button"
                            aria-expanded="false" aria-controls="taxDetailsBreakdown">
                            <i class="ph ph-caret-down rotate-icon tax1"></i>
                            <span class="font-size-14 fw-medium text-danger">
                                {{ \Currency::format($taxSum) }}
                            </span>
                        </div>
                    </div>
                    <div class="collapse mt-2 mb-2" id="taxDetailsBreakdown">
                        <div class="text-calculate card py-2 px-3" id="tax-breakdown">
                            @if (isset($taxes) && $taxableSubtotal > 0)


                                @foreach ($taxes as $taxItem)
                                    @php
                                        if ($taxItem['type'] == 'fixed') {
                                            $taxAmount = $taxItem['tax_amount'] ?? ($taxItem['amount'] ?? 0);
                                        } else {
                                            $taxAmount = (max(0, $taxableSubtotal - $discountAmount) * $taxItem['percent']) / 100;
                                        }
                                    @endphp
                                    <div
                                        class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-1' : '' }}">
                                        <span class="font-size-12">{{ $taxItem['name'] }}
                                            {{ $taxItem['type'] == 'fixed' ? '' : '(' . $taxItem['percent'] . '%)' }}</span>
                                        <span class="font-size-12 text-danger fw-medium">
                                            <!-- @if (function_exists('Currency::format'))
                                                {{ Currency::format($taxAmount) }}
                                                @else
                                                ${{ number_format($taxAmount, 2) }}
                                                @endif -->
                                            {{ \Currency::format($taxAmount) }}
                                        </span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif
                <hr class="line-divider" />
                {{-- Total (backend-calculated: subtotal - discount + tax) --}}
                <div class="d-flex justify-content-between align-items-center">
                    <span>{{ __('frontend.total') }}</span>
                    <span class="total-value fw-semibold text-primary">
                        {{ \Currency::format($finalTotal) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade rating-modal" id="review-service" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content bg-gray-900 rounded">
            <div class="modal-body modal-body-inner rate-us-modal">
                <form id="reviewForm">
                    <div class="rate-box">
                        <h5 class="font-size-21-3 mb-0 text-center">{{ __('frontend.rate_our_service_now') }}</h5>
                        <p class="mb-0 mt-2 font-size-14 text-center">
                            {{ __('frontend.your_honest_feedback_helps_us_improve_and_serve_you_better') }}</p>
                        <div class="mt-5 pt-2">
                            <div class="form-group mb-4">
                                <label for="" class="form-label">{{ __('frontend.your_rating') }}</label>
                                <div class="bg-gray-800 form-control">
                                    <ul
                                        class="list-inline m-0 p-0 d-flex align-items-center justify-content-start gap-1 rating-list">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <li data-value="{{ $i }}"
                                                class="star{{ $employeeReview && $employeeReview->rating >= $i ? ' selected' : '' }}">
                                                <span class="text-warning icon">
                                                    <i class="ph-fill ph-star icon-fill"></i>
                                                    <i class="ph ph-star icon-normal"></i>
                                                </span>
                                            </li>
                                        @endfor
                                    </ul>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for=""
                                    class="form-label">{{ __('frontend.enter_your_feedback') }}</label>
                                <textarea class="form-control bg-gray-800"
                                    placeholder="{{ __('frontend.Share_your_experience!_Your_feedback_helps_others_make_informed_decisions_about_their_healthcare') }}"
                                    rows="3" id="reviewTextarea">{{ $employeeReview->review_msg ?? '' }}</textarea>
                            </div>
                            <div
                                class="mt-5 pt-3 d-flex align-items-center justify-content-center row-gap-3 column-gap-4 flex-wrap">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('frontend.submit') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Interactive Star Rating and Review AJAX Logic --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Star selection logic
        const stars = document.querySelectorAll('.rating-list li');
        let selectedRating = {{ isset($employeeReview) && $employeeReview ? $employeeReview->rating : 0 }};

        function updateStars(rating) {
            stars.forEach((star, idx) => {
                if (idx < rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
        stars.forEach((star, idx) => {
            star.addEventListener('mouseenter', () => updateStars(idx + 1));
            star.addEventListener('mouseleave', () => updateStars(selectedRating));
            star.addEventListener('click', () => {
                selectedRating = idx + 1;
                updateStars(selectedRating);
            });
        });
        updateStars(selectedRating);

        // Review form submit (AJAX)
        const reviewForm = document.getElementById('reviewForm');
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const review = document.getElementById('reviewTextarea').value;
            const bookingId = '{{ $booking->id }}';
            fetch("{{ url('review/submit') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        rating: selectedRating,
                        review,
                        booking_id: bookingId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Set a flag in sessionStorage to show success alert after reload
                        sessionStorage.setItem('reviewSuccess', '1');
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.failed_to_submit_review') }}',
                            text: data.error ||
                                '{{ __('frontend.something_went_wrong_please_try_again') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('frontend.failed_to_submit_review') }}',
                        text: '{{ __('frontend.a_network_or_server_error_occured') }}',
                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                    });
                });
        });

        // Show SweetAlert2 success after reload if review was just submitted
        if (sessionStorage.getItem('reviewSuccess') === '1') {
            Swal.fire({
                icon: 'success',
                title: 'Thank you!',
                text: 'Your review has been submitted successfully.',
                confirmButtonText: '{{ __('frontend.ok') }}',
                buttonsStyling: false, // ✅ disable default styles
                customClass: {
                    confirmButton: 'btn btn-primary' // ✅ your primary button
                }
            });
            sessionStorage.removeItem('reviewSuccess');
        }
        // Edit review button
        const editBtn = document.querySelector('.edit-review-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                // Pre-fill modal with existing review
                document.getElementById('reviewTextarea').value = document.getElementById(
                    'review-text-display').innerText;
                selectedRating = parseInt(document.getElementById('review-rating-display').innerText) ||
                    0;
                updateStars(selectedRating);
            });
        }
        // Delete review button
        const deleteBtn = document.querySelector('.delete-review-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                Swal.fire({
                    title: '{{ __('frontend.are_you_sure_you_want_to_delete_your_review') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true,
                    customClass: {
                        cancelButton: 'btn btn-secondary',
                        confirmButton: 'btn btn-primary',
                    },
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('review/delete') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    booking_id: '{{ $booking->id }}'
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '{{ __('frontend.failed_to_delete_review') }}',
                                        text: data.error ||
                                            '{{ __('frontend.something_went_wrong_please_try_again') }}',
                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false,
                                    });
                                }
                            })
                            .catch(err => {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('frontend.failed_to_delete_review') }}',
                                    text: '{{ __('frontend.a_network_or_server_error_occured') }}',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false,
                                });
                            });
                    }
                });
            });
        }
    });
</script>
