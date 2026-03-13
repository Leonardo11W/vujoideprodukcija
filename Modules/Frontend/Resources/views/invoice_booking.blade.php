<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $booking->id }}</title>
    <style>
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            color: #050505;
            font-weight: 500;
        }

        p {
            margin: 0;
        }

        body {
            color: #6C757D;
            font-size: 16px;
            font-family: 'DejaVu Sans', sans-serif;
            /* Unicode-friendly font */
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

        .bg-success {
            background: #32A071;
        }

        .bg-warning {
            background: #ef3e36;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="main-logo" style="float: left;">

            <img src="{{ $logo }}" alt="Company Logo" style="width:150px; height:auto;">

        </div>
        <div style="float: right;">
            <div style="display: inline;">
                <span class="fw-500 fs-10">Invoice Date - <span
                        class="text-black fs-10">{{ $booking->created_at ? $booking->created_at->format('d/m/Y') : '-' }}</span></span>
                <span class="fw-500 fs-10" style="margin-left: 8px;">Invoice ID - <span
                        class="text-black fs-10">#{{ $booking->id }}</span></span>
            </div>
        </div>
    </div>
    <div style="clear: both;"></div>
    <div class="main-content">
        <div style="padding: 24px 0 32px;">
            <div style="float: left;">
                <p class="fs-12">Thanks, you have already completed the payment for this booking</p>
            </div>
            <div class="c-text-end" style="float: right;">
                <span class="text-black fs-10">Payment Status:</span>
                @php
                    $paymentStatus = $booking->payment->payment_status ?? 0;

                    $statusClass = $paymentStatus == 1 ? 'bg-success' : 'bg-warning';
                    $statusText = $paymentStatus == 1 ? 'Paid' : 'Pending';
                @endphp
                <span class="p-badge {{ $statusClass }} fs-10">
                    {{ $statusText }}
                </span>
            </div>

        </div>

        <div class="header-content" style="margin-bottom: 32px;">
            <div class="left-content" style="float: left; width: 70%;">
                <h4>{{ setting('app_name') }}</h4>
                <p class="fs-12" style="margin-top: 10px;">
                    @php
                        $addressParts = [];
                        if (setting('bussiness_address_line_1')) {
                            $addressParts[] = setting('bussiness_address_line_1');
                        }
                        if (setting('bussiness_address_line_2')) {
                            $addressParts[] = setting('bussiness_address_line_2');
                        }
                        if (setting('bussiness_address_city')) {
                            $addressParts[] = setting('bussiness_address_city');
                        }
                        if (setting('bussiness_address_state')) {
                            $addressParts[] = setting('bussiness_address_state');
                        }
                        if (setting('bussiness_address_country')) {
                            $addressParts[] = setting('bussiness_address_country');
                        }
                        if (setting('bussiness_address_postal_code')) {
                            $addressParts[] = setting('bussiness_address_postal_code');
                        }
                        echo implode(', ', $addressParts);
                    @endphp
                </p>
            </div>
            <div class="right-content text-black" style="text-align: right; float: right; width: 30%;">
                <a href="mailto:{{ setting('inquriy_email') }}"
                    class="text-black fs-12 fw-500">{{ setting('inquriy_email') }}</a>
                <p class="fs-12 fw-500" style="margin: 10px 0 0;">{{ setting('helpline_number') }}</p>
            </div>
            <div style="clear: both;"></div>
        </div>

        <div style="padding-bottom: 16px;">
            <h6 class="fs-10">Order Information:</h6>
            <div class="bg-color fs-10" style="margin-top: 4px; padding: 12px 14px;">
                <table class="information">
                    <tr>
                        <td><span class="text-black">ID: </span>#{{ $booking->id }}</td>
                        <td><span class="text-black">Date:
                            </span>{{ $booking->start_date_time ? $booking->start_date_time : '-' }}</td>

                    </tr>
                </table>
            </div>
        </div>

        <div style="padding-bottom: 16px;">
            <h6 class="fs-10">Customer Detail:</h6>
            <div class="bg-color fs-10" style="margin-top: 4px; padding: 12px 14px;">
                <table class="information">
                    <tr>
                        <td><span class="text-black">Name:
                            </span>{{ $booking->user->first_name ?? ($booking->user->name ?? '') }}</td>
                        <td><span class="text-black">Mobile Number:
                            </span>{{ $booking->user->mobile ?? ($booking->user->mobile ?? '') }}</td>
                        <td><span class="text-black">Email: </span>{{ $booking->user->email ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div style="padding-bottom: 16px;">
            <h6 class="fs-10">Salon Detail:</h6>
            <div class="bg-color fs-10" style="margin-top: 4px; padding: 12px 14px;">
                <table class="information">
                    <tr>
                        <td><span class="text-black">Name: </span>{{ $booking->branch->name ?? '' }}</td>
                        <td><span class="text-black">Mobile Number: </span>{{ $booking->branch->contact_number ?? '' }}
                        </td>
                        <td><span class="text-black">Email: </span>{{ $booking->branch->contact_email ?? '' }}</td>
                    </tr>
                </table>
            </div>
        </div>


        <table style="width: 100%; border: 1px solid #ccc;  border-radius: 8px;">
            <thead class="bg-color text-black">
                <th class="fs-10" style="padding:12px 16px; text-align: left;">Service Name</th>
                <th class="fs-10" style="padding:12px 16px; text-align: left;">Duration</th>
                <th class="fs-10" style="padding:12px 16px; text-align: right;">Amount</th>

            </thead>
            <tbody>
                @foreach ($booking->booking_service as $service)
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: left;">
                            {{ $service->service->name ?? 'Service' }}</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: left;">{{ $service->duration_min }}
                            min</td>
                        <td class="fs-10" style="padding:12px 16px; text-align: right;">
                            {{ Currency::format($service->service_price) }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>

        @php
            $subtotal = $booking->booking_service->sum('service_price');
            $couponDiscount = $booking->payment->discount_amount ?? 0;

            $tax_percentage = $booking->payment->tax_percentage;

        @endphp

        <div style="margin-top: 12px">
            <table style="width: 100%; border-collapse: collapse; margin-top: 24px;">
                <tbody class="bg-color text-black">
                    @if ($couponDiscount > 0)
                        <tr>
                            <td class="fs-10" style="padding:12px 16px; text-align: left;"></td>
                            <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                            <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                            <td class="fs-10" style="padding:12px 16px; text-align: left; color: #6C757D;">Coupon
                            </td>
                            <td class="fs-10"
                                style="padding:12px 16px; text-align: right; color: #09954D; font-weight: 500;">
                                {{ Currency::format($couponDiscount) }}
                            </td>
                        </tr>
                    @endif                    
                
                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: left;"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: left; color: #6C757D;">Subtotal</td>
                        <td class="fs-10"
                            style="padding:12px 16px; text-align: right; color: #09954D; font-weight: 500;">
                            {{ Currency::format($subtotal - $couponDiscount) }}
                        </td>
                    </tr>



                    @php
                        $taxAmount = 0;
                    @endphp

                    @if (isset($tax_percentage) && $tax_percentage != null)
                        <tr>
                            <td class="fs-10" style="padding:12px 16px; text-align: left;" colspan="3"></td>
                            <td class="fs-10" style="padding:12px 16px; text-align: left; color: #6C757D;">
                                Tax <br>

                                @foreach ($tax_percentage as $tax)
                                    {{ $tax['name'] }}

                                    @if ($tax['type'] == 'percent')
                                        ({{ $tax['percent'] }}%)
                                        @php
                                            $taxAmount += ($subtotal - $couponDiscount) * ($tax['percent'] / 100);
                                        @endphp
                                    @else
                                        ({{ Currency::format($tax['tax_amount'] ?? ($tax['amount'] ?? 0)) }})
                                        @php
                                            $taxAmount += $tax['tax_amount'] ?? ($tax['amount'] ?? 0);
                                        @endphp
                                    @endif

                                    @if (!$loop->last)
                                        ,
                                    @endif
                                @endforeach
                            </td>
                            <td class="fs-10"
                                style="padding:12px 16px; text-align: right; color: #EF3E36; font-weight: 500;">
                                {{ Currency::format($taxAmount) }}
                            </td>
                        </tr>
                    @endif


                    <tr>
                        <td class="fs-10" style="padding:12px 16px; text-align: left;"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                        <td class="fs-10" style="padding:12px 16px; text-align: right;"></td>
                        <td class="fs-10"
                            style="padding:12px 16px; text-align: left; color: #050505; border-top:1px solid #ccc; font-weight: 600;">
                            Total Amount:</td>
                        <td class="fs-10"
                            style="padding:12px 16px; text-align: right; color: #050505; border-top:1px solid #ccc; font-weight: 600;">
                            {{ Currency::format($subtotal + $taxAmount - $couponDiscount) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>




        <div class="bottom-section" style="margin-top: 24px;">
            <h6 class="fs-10" style="margin-bottom: 4px;">Terms & Condition</h6>
            <p style="font-size: 8px;">
                By our services, you agree to automatic billing based on the selected plan. You can cancel anytime
                through your account settings, but no refunds will be issued for the current billing cycle. For more
                details, see our website or contact support at <a href="mailto:{{ setting('inquriy_email') }}"
                    class="text-black"
                    style="text-decoration: none; color: #A82D86;">{{ setting('inquriy_email') }}</a>
            </p>
        </div>

        <footer style="margin-top: 16px;">
            <div class="" style="display: flex; align-items: center; gap: 10px;">
                <h6 style="font-size: 8px;">For more information, visit our website:
                    <a href="#" style="color: #A82D86; font-size: 8px;">{{ url('/') ?? 'www.frezka.com' }}</a>
                </h6>
            </div>
            <h5 style="font-weight: 500; font-size: 8px; margin-top: 16px;">© {{ date('Y') }}
                {{ setting('copyright_text') }}</h5>
        </footer>
    </div>
