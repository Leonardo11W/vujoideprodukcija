@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <style>
        .alternate-list {
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
        }
        .alternate-list li:not(:last-child){
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
    </style>

<style type="text/css" media="print">
      @page :footer {
        display: none !important;
      }

      @page :header {
        display: none !important;
      }
      @page { size: landscape;
        margin: 0;
    }
      /* @page { margin: 0; } */

      .pr-hide {
        display: none;
        }

      button {
        display: none !important;
      }
      * {
        -webkit-print-color-adjust: none !important;   /* Chrome, Safari 6 – 15.3, Edge */
        color-adjust: none !important;                 /* Firefox 48 – 96 */
        print-color-adjust: none !important;           /* Firefox 97+, Safari 15.4+ */
      }
    </style>

    <div class="row pr-hide">
        <div class="col-12">
            <div class="card ">
                <div class="card-header border-bottom-0">
                    <div class="row pr-hide">
                        <div class="col-auto col-lg-4 mb-4">
                            <div class="input-group">
                                <select class="form-select select2" name="payment_status"
                                    data-minimum-results-for-search="Infinity" id="update_payment_status">
                                    <option value="" disabled>
                                        Payment Status
                                    </option>
                                    <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>
                                        Paid
                                    </option>
                                    <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>
                                        Unpaid
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-auto col-lg-4 mb-4">
                            <div class="input-group">
                                <select name="delivery_status" class="form-control select2" name="delivery_status"
                                    data-ajax--url="{{ route('backend.get_search_data', ['type' => 'constant', 'sub_type' => 'ORDER_STATUS']) }}"
                                    data-ajax--cache="true">
                                    <option value="" disabled>Delivery Status</option>
                                    @if (isset($order->delivery_status))
                                        <option value="{{ $order->delivery_status }}" selected>
                                            {{ Str::title(Str::replace('_', ' ', $order->delivery_status)) }}
                                        </option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-auto col-lg-4 mb-4 text-center text-lg-end">
                            <a class="btn btn-primary" href="{{route('backend.orders.downloadInvoice', ['id' => request()->id])}}">
                                <i class="fa-solid fa-download"></i>
                                Download Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!--Main Invoice-->
        <div class="col-xl-9 order-2 order-md-2 order-lg-2 order-xl-1">
            <div class="card mb-4" id="section-1">
                <div class="card-body">
                    <!--Order Detail-->
                    <div class="row justify-content-between align-items-center g-3 mb-4">
                        <div class="col-auto flex-grow-1">
                            <img src="{{ asset(setting('logo')) }}" alt="logo" class="img-fluid" width="200">
                        </div>
                        <div class="col-auto text-end">
                            <h5 class="mb-0">Invoice
                                <span
                                    class="text-accent">{{ optional($order->orderGroup)->formatted_order_code ?? '-' }}</span>
                            </h5>
                            <span class="text-muted">Order Date:
                                {{ date('d M, Y', strtotime($order->created_at)) }}
                            </span>
                            <br>
                            <span class="text-muted">Delivery Date:
                                @php
                                    $deliveryDate = null;
                                    $shippingAddress = $address;
                                    if (!$shippingAddress && $order->user && $order->user->addresses) {
                                        $shippingAddress = $order->user->addresses->first();
                                    }

                                    if ($shippingAddress && $shippingAddress->country_data) {
                                        $countryId = $shippingAddress->country_data->id;
                                        $logisticZone = \Modules\Logistic\Models\LogisticZone::where('country_id', $countryId)->first();

                                        if ($logisticZone && $order->created_at) {
                                            $days = 0;
                                            if (!empty($logisticZone->standard_delivery_time)) {
                                                // Handle different formats: '3 days', '1 - 3 days', '3-5 days', '2 Day', etc.
                                                $deliveryTime = trim($logisticZone->standard_delivery_time);

                                                // Check if it's a range (contains dash or hyphen)
                                                if (preg_match('/(\d+)\s*[-–]\s*(\d+)/', $deliveryTime, $matches)) {
                                                    // Range format: take the maximum days for conservative estimate
                                                    $days = max((int)$matches[1], (int)$matches[2]);
                                                } elseif (preg_match('/(\d+)/', $deliveryTime, $matches)) {
                                                    // Single number format: '3 days', '3', '2 Day', etc.
                                                    $days = (int)$matches[1];
                                                }
                                            }
                                            $deliveryDate = \Carbon\Carbon::parse($order->created_at)->addDays($days)->format('d M, Y');
                                        } else {
                                            $deliveryDate = date('d M, Y', strtotime($order->updated_at));
                                        }
                                    } else {
                                        $deliveryDate = date('d M, Y', strtotime($order->updated_at));
                                    }
                                @endphp
                                {{ $deliveryDate }}
                            </span>
                            @if ($order->location_id != null)
                                <div>
                                    <span class="text-muted">
                                        <!-- <i class="las la-map-marker"></i> {{ optional($order->location)->name }} -->
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="row d-flex justify-content-md-between justify-content-center g-3">
                        <div class="col-md-3">
                            <!--Customer Detail-->
                            <div class="welcome-message">
                                <h5 class="mb-2">Customer Info</h6>
                                    @if(optional($order->user)->full_name)
                                        <p class="mb-0">Name: <strong>{{ $order->user->full_name }}</strong></p>
                                    @endif

                                    @if(optional($order->user)->email)
                                        <p class="mb-0">Email: <strong>{{ $order->user->email }}</strong></p>
                                    @endif

                                    @if(optional($order->user)->mobile)
                                        <p class="mb-0">Phone: <strong>{{ $order->user->mobile }}</strong></p>
                                    @endif
                            </div>
                            @php
                                $bookingProduct = $order->bookingProduct;
                                $transactionType = null;
                                if ($bookingProduct && $bookingProduct->booking_id) {
                                    $transaction = \Modules\Booking\Models\BookingTransaction::where('booking_id', $bookingProduct->booking_id)->first();
                                    if ($transaction) {
                                        $transactionType = $transaction->transaction_type;
                                    }
                                }
                            @endphp
                            <div class="col-auto mt-3">
                                <h6 class="d-inline-block">Payment Method: </h6>
                                <span class="badge bg-primary">
                                    {{ $transactionType ?? ucwords(str_replace('_', ' ', optional($order->orderGroup)->payment_method ?? 'N/A')) }}
                                </span>
                            </div>
                            @php
                                $shippingAddress = $address;
                                if (!$shippingAddress && $order->user && $order->user->addresses) {
                                    $shippingAddress = $order->user->addresses->first();
                                }
                                $billingAddress = optional($order->orderGroup)->billingAddress;
                                if (!$billingAddress && $order->user && $order->user->addresses) {
                                    $billingAddress = $order->user->addresses->first();
                                }
                            @endphp
                            @php
                                $logisticName = null;
                                $countryId = null;
                                $deliveryCharge = null;
                                if ($shippingAddress && $shippingAddress->country_data) {
                                    $countryId = $shippingAddress->country_data->id;
                                }
                                if ($countryId) {
                                    $logisticZone = \Modules\Logistic\Models\LogisticZone::where('country_id', $countryId)->first();
                                    if ($logisticZone && $logisticZone->logistic_id) {
                                        $logistic = \Modules\Logistic\Models\Logistic::find($logisticZone->logistic_id);
                                        if ($logistic) {
                                            $logisticName = $logistic->name;
                                            $deliveryCharge = $logisticZone->standard_delivery_charge;
                                        }
                                    }
                                }
                                if (!$logisticName) {
                                    $logisticName = 'Default Logistic';
                                }
                                if ($deliveryCharge === null) {
                                    $deliveryCharge = optional($order->orderGroup)->total_shipping_cost ?? 0;
                                }
                            @endphp
                            <h6 class="col-auto d-inline-block">Logistic: </h6>
                            <span class="badge bg-primary">{{ $logisticName ?? ($order->logistic_name ?? '-') }}</span><br>
                            <h6 class="col-auto d-inline-block">Status: </h6> <span>{{ Str::title(Str::replace('_', ' ', $order->delivery_status ?? '-')) }}</span>
                        </div>
                        <div class="col">
                            <div class="shipping-address d-flex justify-content-md-end gap-3 mb-3">
                                <div class="border-end w-25">
                                    <h5 class="mb-2">Shipping Address</h5>
                                    @if($shippingAddress)
                                        <p class="mb-0 text-wrap">
                                            {{ $shippingAddress->address_line_1 ?? '' }},
                                            {{ optional($shippingAddress->city_data)->name ?? '' }},
                                            {{ optional($shippingAddress->state_data)->name ?? '' }},
                                            {{ optional($shippingAddress->country_data)->name ?? '' }}
                                        </p>
                                    @else
                                        <p class="mb-0 text-wrap">-</p>
                                    @endif
                                </div>
                                <div class="w-25">
                                    <h5 class="mb-2">Billing Address</h5>
                                    @if($billingAddress)
                                        <p class="mb-0 text-wrap">
                                            {{ $billingAddress->address_line_1 ?? '' }},
                                            {{ optional($billingAddress->city_data)->name ?? '' }},
                                            {{ optional($billingAddress->state_data)->name ?? '' }},
                                            {{ optional($billingAddress->country_data)->name ?? '' }}
                                        </p>
                                    @else
                                        <p class="mb-0 text-wrap">-</p>
                                    @endif
                                </div>
                            </div>
                            <!-- <div class="shipping-address d-flex justify-content-md-end gap-3">
                                <div class="w-25"></div>
                                <div class="w-25">

                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
                @php
                    $hasDiscount = $order->orderItems->contains(function ($item) {
                        return $item->discount_price > 0;
                    });
                     $colspan = $hasDiscount ? 5 : 4;
                @endphp
                <!--order details-->
                <table class="table table-bordered border-top" data-use-parent-width="true">
                    <thead>
                        <tr>
                            <th class="text-center" width="7%">S/L</th>
                            <th>Products</th>
                            <th class="text-end">Unit Price</th>
                            @if($hasDiscount)
                                <th class="text-end">Discounted Price</th>
                            @endif
                            <th class="text-end">QTY</th>
                            <th class="text-end">Total Price</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($order->orderItems as $key => $item)
                            @php
                                $product = optional($item->product_variation)->product;
                            @endphp
                            <tr>
                                <td class="text-center">{{ $key + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                    <div> <img src="{{ optional($product?->media?->first())?->getFullUrl() ?? default_feature_image() }}" alt="{{ $product?->name ?? __('Product') }}"
                                                class="avatar avatar-50 rounded-pill">
                                        </div>
                                        <div class="ms-2">
                                            <h6 class="fs-lg mb-0" style="max-width: 280px; white-space: normal;">
                                                {{ $product?->name ?? __('Product unavailable') }}
                                            </h6>
                                            <div class="text-muted">
                                                @foreach (generateVariationOptions(optional($item->product_variation)->combinations ?? []) as $variation)
                                                    <span class="fs-xs">
                                                        {{ $variation['name'] }}:
                                                        @foreach ($variation['values'] as $value)
                                                            {{ $value['name'] }}
                                                        @endforeach
                                                        @if (!$loop->last)
                                                            ,
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <span class="fw-bold">{{ $item->discount_price > 0 ?  \Currency::format($item->original_price) : \Currency::format($item->unit_price) }}
                                    </span>
                                </td>
                                @if($hasDiscount)
                                    <td class="text-end">
                                        <span class="fw-bold">{{ $item->discount_price > 0 ? \Currency::format($item->discount_price) : '-' }}</span>
                                    </td>
                                @endif
                                <td class="fw-bold text-end">{{ $item->qty }}</td>

                                <td class=" text-end">
                                    @if ($item->refundRequest && $item->refundRequest->refund_status == 'refunded')
                                        <span
                                            class="badge bg-soft-info rounded-pill text-capitalize">{{ $item->refundRequest->refund_status }}</span>
                                    @endif
                                    <span class="text-accent fw-bold">{{ \Currency::format($item->total_price) }}
                                    </span>

                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                    @php
                        $subTotal = optional($order->orderGroup)->sub_total_amount;
                        if ($subTotal === null || $subTotal == 0) {
                            $subTotal = $order->orderItems->sum(function($item) {
                                return $item->unit_price * $item->qty;
                            });
                        }
                        $tax = optional($order->orderGroup)->total_tax_amount;
                        if ($tax === null) { $tax = 0; }
                        $delivery = optional($order->orderGroup)->total_shipping_cost;
                        if ($delivery === null) { $delivery = 0; }
                        $tips = optional($order->orderGroup)->total_tips_amount;
                        if ($tips === null) { $tips = 0; }
                        $coupon = optional($order->orderGroup)->total_coupon_discount_amount;
                        if ($coupon === null) { $coupon = 0; }
                        $grandTotal = optional($order->orderGroup)->grand_total_amount;
                        if ($grandTotal === null || $grandTotal == 0) {
                            $grandTotal = $subTotal + $tax + $deliveryCharge + $tips - $coupon;
                        }
                    @endphp
                    <tfoot class="text-end">
                        <tr>
                            <td colspan="{{ $colspan }}">
                                <h6 class="d-inline-block me-3">Sub Total: </h6>
                            </td>
                            <td width="10%">
                                <strong>{{ \Currency::format($subTotal) }}</strong></td>
                        </tr>
                        @if ($tips > 0)
                            <tr>
                                <td colspan="{{ $colspan }}">
                                    <h6 class="d-inline-block me-3">Tips: </h6>
                                </td>
                                <td width="10%" class="text-end">
                                    <strong>{{ \Currency::format($tips) }}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="{{ $colspan }}">
                                <h6 class="d-inline-block me-3">Tax: </h6>
                            </td>
                            <td width="10%" class="text-end">
                                <strong>{{ \Currency::format($tax) }}</strong></td>
                        </tr>
                        <tr>
                            <td colspan="{{ $colspan }}">
                                <h6 class="d-inline-block me-3">Delivery Charge: </h6>
                            </td>
                            <td width="10%" class="text-end">
                                <strong>{{ \Currency::format($deliveryCharge) }}</strong></td>
                        </tr>
                        @if ($coupon > 0)
                            <tr>
                                <td colspan="{{ $colspan }}">
                                    <h6 class="d-inline-block me-3">Coupon Discount: </h6>
                                </td>
                                <td width="10%" class="text-end">
                                    <strong>{{ \Currency::format($coupon) }}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="{{ $colspan }}">
                                <h6 class="d-inline-block me-3">Grand Total: </h6>
                            </td>
                            <td width="10%" class="text-end"><strong
                                    class="text-accent">{{ \Currency::format($grandTotal) }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!--Note-->
                <div class="card-body">
                    <div class="card-footer border-top-0 px-4 py-4 rounded bg-soft-gray border border-2">
                        <p class="mb-0">{{ setting('spacial_note') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!--Order Status-->
        <div class="col-xl-3 order-1 order-md-1 order-lg-1 order-xl-2 pr-hide">
            <div class="sticky-sidebar">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">Order Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="alternate-list list-unstyled">

                            @forelse ($order->orderUpdates as $orderUpdate)
                                <li>
                                    <a class="{{ $loop->first ? 'active' : '' }}">
                                        {{ $orderUpdate->note }} <br> By
                                        <span class="text-capitalize">{{ optional($orderUpdate->user)->name }}</span>
                                        at
                                        {{ date('d M, Y', strtotime($orderUpdate->created_at)) }}.</a>
                                </li>
                            @empty
                                <li>
                                    <a class="active">
                                        Order placed with status: {{ Str::title(Str::replace('_', ' ', $order->delivery_status ?? 'order_placed')) }} <br>
                                        By <span class="text-capitalize">{{ optional($order->user)->name ?? 'System' }}</span>
                                        at {{ date('d M, Y', strtotime($order->created_at)) }}.
                                    </a>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        function invoicePrint() {
            window.print()
        }

        function updateStatusAjax(__this, url) {
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    order_id: {{ $order->id }},
                    status: __this.val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if (res.status) {
                        window.successSnackbar(res.message)
                        setTimeout(() => {
                            location.reload()
                        }, 100);
                    }
                }
            });
        }
        $('[name="payment_status"]').on('change', function() {
            if ($(this).val() !== '') {
                updateStatusAjax($(this), "{{ route('backend.orders.update_payment_status') }}")
            }
        })

        $('[name="delivery_status"]').on('change', function() {
            if ($(this).val() !== '') {
                updateStatusAjax($(this), "{{ route('backend.orders.update_delivery_status') }}")
            }
        })
    </script>
@endpush
