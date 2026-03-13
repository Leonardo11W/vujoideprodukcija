@php
    $isBooking = $order->bookingProduct()->exists();
    $rawPrefix = $isBooking ? setting('inv_booking_prefix') : setting('inv_prefix');
    $prefixLower = strtolower(trim((string)$rawPrefix));
    if (str_contains($prefixLower, 'booking')) {
        $finalPrefix = str_ireplace('booking', __('booking.singular_title') ?? 'Booking', $rawPrefix);
    } elseif (str_contains($prefixLower, 'inv') || str_contains($prefixLower, 'invoice')) {
        $finalPrefix = str_ireplace(['inv', 'invoice'], __('booking.download_invoice') ?? 'Invoice', $rawPrefix);
    } else {
        $finalPrefix = $rawPrefix;
    }
    $finalPrefix = preg_replace('/(#\s*-\s*)+/', '# - ', trim($finalPrefix));
    $orderIdWithPrefix = optional($order->orderGroup)->formatted_order_code ?? ($finalPrefix . ' ' . $order->id);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $orderIdWithPrefix }}</title>
    <style>
        /* Ensure rupee symbol renders in PDF */
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            color: #6C757D;
            font-size: 16px;
            font-family: 'DejaVu Sans', sans-serif; /* Unicode-friendly font */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            color: #010A0F;
            font-weight: 500;
            font-family: 'DejaVu Sans', sans-serif;
        }

        p {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
        }

        .header {
            padding: 25px 0 0;
        }

        .border-bottom {
            border-bottom: 1px solid #CCCCCC;
        }

        .text-black {
            color: #050505;
        }

        .c-row {
            display: flex;
            flex-wrap: wrap;
        }

        .c-align-center {
            align-items: center;
        }

        .c-justify-between {
            justify-content: space-between;
        }

        .c-col-6 {
            flex: 0 0 auto;
            width: 50%;
        }

        .c-col-7 {
            flex: 0 0 auto;
            width: 58.33333333%;
        }

        .c-col-5 {
            flex: 0 0 auto;
            width: 41.66666667%;
        }

        .p-badge {
            background-color: #32A071;
            color: #ffffff;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .c-text-end {
            text-align: right;
        }

        ul {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }

        li {
            display: inline;
            margin-right: 16px;
        }

        .bg-color {
            background-color: #FAF8FF;
        }

        .fw-500 {
            font-weight: 500;
        }

        .fs-10 {
            font-size: 10px;
        }

        .fs-12 {
            font-size: 12px;
        }

        .information tr td {
            padding: 0 16px 0 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="main-logo" style="float: left;">

       
        <img src="{{ $logo }}" alt="Company Logo" style="width:150px; height:auto;">
        </div>
        <div style="float: right;">
            <p class="fw-500 fs-10">Invoice Date - <span class="text-black fs-10">{{ $order->created_at ? $order->created_at->format('d/m/Y') : '-' }}</span></p>
            <p class="fw-500 fs-10">Invoice ID - <span class="text-black fs-10">{{ $orderIdWithPrefix }}</span></p>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="main-content">
        <div style="padding: 24px 0 32px;">
            <div style="float: left;">
                <p class="fs-12">Thanks, you have already completed the payment for this invoice</p>
            </div>
            <div class="c-text-end" style="float: right;">
                <span class="text-black fs-10">Payment Status:</span>
                <span class="p-badge fs-10" style="margin-left: 10px;">{{ strtoupper($order->payment_status) }}</span>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div class="header-content" style="margin-bottom: 32px;">
            <div class="left-content" style="float: left; width: 70%;">
                <h4>{{ (setting('app_name')) }}</h4>
                <p class="fs-12" style="margin-top: 10px;">
                    @php
                        $addressParts = [];
                        if(setting('bussiness_address_line_1')) $addressParts[] = setting('bussiness_address_line_1');
                        if(setting('bussiness_address_line_2')) $addressParts[] = setting('bussiness_address_line_2');
                        if(setting('bussiness_address_city')) $addressParts[] = setting('bussiness_address_city');
                        if(setting('bussiness_address_state')) $addressParts[] = setting('bussiness_address_state');
                        if(setting('bussiness_address_country')) $addressParts[] = setting('bussiness_address_country');
                        if(setting('bussiness_address_postal_code')) $addressParts[] = setting('bussiness_address_postal_code');
                        echo implode(', ', $addressParts);
                    @endphp
                </p>
            </div>
            <div class="right-content text-black" style="text-align: right; float: right; width: 30%;">
                <a href="mailto:{{ setting('inquriy_email') }}" class="text-black fs-12 fw-500">{{ setting('inquriy_email') }}</a>
                <p class="fs-12 fw-500" style="margin: 10px 0 0;">{{ setting('helpline_number') }}</p>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div style="padding-bottom: 16px;">
            <h6 class="fs-10">Order Information:</h6>
            <div class="bg-color fs-10" style="margin-top: 4px; padding: 12px 14px;">
                <table class="information">
                    <tr>
                        <td><span class="text-black">ID: </span>{{ $orderIdWithPrefix }}</td>
                        <td><span class="text-black">Date: </span>{{ $order->created_at ? $order->created_at->format('d/m/Y') : '-' }}</td>
                       
                    </tr>
                </table>
            </div>
        </div>

        <div style="padding-bottom: 16px;">
            <h6 class="fs-10">Customer Detail:</h6>
            <div class="bg-color fs-10" style="margin-top: 4px; padding: 12px 14px;">
                <table class="information">
                    <tr>
                        <td><span class="text-black">Name: </span>{{ $address->first_name ?? $order->user->name ?? '' }}</td>
                        <td><span class="text-black">Mobile Number: </span>{{ $address->contact_number ?? $order->user->mobile ?? '' }}</td>
                        <td><span class="text-black">Email: </span>{{ $order->user->email ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>


        @if(isset($order->orderItems) && $order->orderItems->count() > 0)


        <!-- Product Table -->
        <table style="width: 100%; border: 1px solid #ccc;  border-radius: 8px;">
            <thead class="bg-color text-black">
                <th class="fs-10" style="padding:12px 16px; text-align: start;">Product Name</th>
                <th class="fs-10" style="padding:12px 16px; text-align: start;">Quantity</th>
                <th class="fs-10" style="padding:12px 16px; text-align: end;">Price</th>
                <th class="fs-10" style="padding:12px 16px; text-align: end;">Amount</th>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                @php
                    $product = $item->product_variation->product ?? null;
                @endphp
                <tr>
                    <td class="fs-10" style="padding:12px 16px; text-align: start;">{{ $item->product_variation->product->name ?? $item->product->name ?? 'Product' }}</td>
                    <td class="fs-10" style="padding:12px 16px; text-align: start;">{{ $item->qty ?? 1 }}</td>
                    <td class="fs-10" style="padding:12px 16px; text-align: end;">
    {{ \Currency::format($item->unit_price ?? 0) }} {{ setting('currency') }}
</td>
                    <td class="fs-10" style="padding:12px 16px; text-align: end;">{{ \Currency::format(($item->unit_price ?? 0) * ($item->qty ?? 1)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @endif
        <!-- Summary Table -->
        @php
            $bpSubtotal = $order->orderItems->sum(function($bp) { return ($bp->unit_price ?? 0) * ($bp->qty ?? 1); });
            $bpDiscount = $order->orderItems->sum('discount_value');
          
        @endphp
        <div style="margin-top: 12px">
            <table style="width: 100%; border-collapse: collapse; margin-top: 24px;">
                <tbody class="bg-color text-black">
                    @if($bpDiscount > 0)
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #6C757D;">Discount</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #09954D; font-weight: 500;">
    -{{ \Currency::format($bpDiscount) }} {{ setting('currency') }}
</td>
                    </tr>
                    @endif
                    @if(($order->coupon_discount_amount ?? 0) > 0)
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #6C757D;">Coupon</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #09954D; font-weight: 500;">-{{ \Currency::format($order->coupon_discount_amount ?? 0) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #6C757D;">Subtotal</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #050505;  font-weight: 500;">{{ \Currency::format($bpSubtotal) }}</td>
                    </tr>
                  
                    @if(isset($order->orderGroup->total_tax_amount) && $order->orderGroup->total_tax_amount > 0)
                    @php
                    $taxdata = json_decode($order->orderGroup->taxes) ?? [];
                    @endphp
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #6C757D;">Tax <br> @foreach($taxdata as $tax)
        {{ $tax->tax_name }} 
        ({{ $tax->tax_value }}{{ $tax->tax_type == 'percent' ? '%' : '' }})@if(!$loop->last), @endif
    @endforeach</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #EF3E36; font-weight: 500;">{{ \Currency::format($order->orderGroup->total_tax_amount ?? 0) }}</td>
                    </tr>
                    @endif

                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #6C757D;">Shipping Charge</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #050505;  font-weight: 500;">{{ \Currency::format($order->shipping_cost ?? 0) }}</td>
                    </tr>
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: start;" colspan="3"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: start; color: #050505; border-top:1px solid #ccc; font-weight: 600;">Total Amount:</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: end; color: #050505; border-top:1px solid #ccc; font-weight: 600;">
    {{ \Currency::format($order->total_admin_earnings ?? 0) }} {{ setting('currency') }}
</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bottom-section" style="margin-top: 24px;">
            <h6 class="fs-10" style="margin-bottom: 4px;">Terms & Condition</h6>
            <p style="font-size: 8px;">
                By purchasing and/or using our services, you agree to automatic billing based on the selected plan. You can cancel anytime through your account settings, but no refunds will be issued for the current billing cycle. For more details, see our website or contact support at <a href="mailto:{{ setting('inquriy_email')}}" class="text-black" style="text-decoration: none; color: #A82D86;">{{ setting('inquriy_email')}}</a>
            </p>
        </div>

        <footer style="margin-top: 16px;">
            <div class="" style="display: flex; align-items: center; gap: 10px;">
                <h6 style="font-size: 8px;">For more information, visit our website:</h6>
                <a href="{{ url('/') }}" style="color: #A82D86; font-size: 8px;">{{ url('/') }}</a>
            </div>
            <h5 style="font-weight: 500; font-size: 8px; margin-top: 16px;">© {{ date('Y') }} {{ setting('copyright_text')}}</h5>
        </footer>