<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() === 'ar' || app()->getLocale() === 'fa' || app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('booking.download_invoice') }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path('fonts/DejaVuSans-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            font-family: 'DejaVu Sans', Arial, sans-serif !important;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            direction: {{ app()->getLocale() === 'ar' || app()->getLocale() === 'fa' || app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }};
        }

        .currency-font {
            font-family: 'DejaVu Sans', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: #000000;
        }

        p {
            margin: 0 0 8px;
        }

        .invoice {
            width: 190mm;
            height: auto;
            box-sizing: border-box;
        }

        .invoice-header {
            text-align: center;
        }

        .invoice-header h1 {
            margin: 0 0 10px;
        }

        .invoice-logo-section {
            text-align: center;
            margin: 0 0 20px;
            padding: 0 0 20px;
            border-bottom: 1px solid #f1f1f1;
        }

        .invoice-detail-part {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            margin: 16px 0;
        }

        .invoice-customer,
        .invoice-billing {
            width: 45%;
        }

        .invoice-branch {
            width: 100%;
            text-align: right;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #f1f1f1;
            padding: 16px;
            font-size: 14px;
        }

        .invoice-table th {
            background-color: #f2f2f2;
        }

        .text-end {
            text-align: right;
        }

        strong {
            color: #000000;
        }

        table th {
            color: #000000;
        }

        .thank-you {
            margin-top: 20px;
            background: #f1f1f1;
            padding: 16px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="invoice">
        <div class="invoice-logo-section">
            @if (isset($logo) && $logo)
                <img src="{{ $logo }}" alt="logo" class="img-fluid" width="100"
                    style="max-width: 200px; height: auto;">
            @elseif(setting('logo'))
                <img src="{{ public_path(setting('logo')) }}" alt="logo" class="img-fluid" width="100"
                    style="max-width: 200px; height: auto;">
            @endif
        </div>
        <div class="text-end">
            <p><strong>{{ __('booking.download_invoice') }} {{ __('booking.booking_id') }}:</strong>
                @php
                    // Get prefix from settings
                    $rawPrefix = setting('inv_booking_prefix') ?: setting('inv_prefix');
                    $rawPrefix = trim($rawPrefix ?? '');

                    // Remove leading # or # -
                    $rawPrefix = preg_replace('/^#\s*-\s*/', '', $rawPrefix);

                    $prefixLower = strtolower($rawPrefix);

                    // Decide final label (DON'T replace text)
                    if (str_contains($prefixLower, 'booking')) {
                        $invPrefix = __('booking.singular_title'); // Booking
                    } elseif (str_contains($prefixLower, 'inv') || str_contains($prefixLower, 'invoice')) {
                        $invPrefix = __('booking.download_invoice'); // Invoice
                    } else {
                        $invPrefix = $rawPrefix;
                    }
                @endphp

                {{ '# - ' . $invPrefix . ' ' . $data['id'] }}

            </p>
        </div>

        <div class="invoice-detail-part">
            <div class="invoice-customer">
                <h3>{{ __('booking.customer_info') }}</h3>
                <p>{{ $data['user_name'] }}</p>
                <p>{{ $data['email'] }}</p>
                <p>{{ $data['mobile'] }}</p>
            </div>
            <div class="invoice-billing">
                <h3>{{ __('booking.billing_address') }}</h3>
                <p>{{ $data['venue_address'] }}</p>
            </div>
            <div class="invoice-branch">
                <h3>{{ __('booking.branch_details') }}</h3>
                <p>{{ __('booking.branch_name') }}: {{ $data['branch_name'] }}</p>
                <p>{{ __('booking.contact_number') }}: {{ $data['branch_number'] }}</p>
            </div>
        </div>

        <div class="invoice-info">
            <div>
                <p><strong>{{ __('booking.lbl_booking_date') }}:</strong></p>
                <p>{{ $data['booking_date'] }}</p>
            </div>
            <div>
                <p><strong>{{ __('booking.payment_method') }}:</strong></p>
                <p>
                    @php
                        $transactionType = $data['transaction_type'] ?? '';
                        $paymentMethodLabel = $transactionType;

                        if ($transactionType === 'upi') {
                            $paymentMethodLabel = __('booking.payment_method_upi');
                        } elseif ($transactionType === 'cash') {
                            $paymentMethodLabel = __('booking.payment_method_cash');
                        } elseif ($transactionType === 'card') {
                            $paymentMethodLabel = __('booking.payment_method_card');
                        } elseif ($transactionType === 'stripe') {
                            $paymentMethodLabel = __('booking.payment_method_stripe');
                        } elseif ($transactionType === 'razorpay') {
                            $paymentMethodLabel = __('booking.payment_method_razorpay');
                        } else {
                            $paymentMethodLabel = ucwords(str_replace('_', ' ', $transactionType));
                        }
                    @endphp
                    {{ $paymentMethodLabel }}
                </p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>{{ __('booking.services') }}/{{ __('booking.products') }}</th>
                    <th>{{ __('booking.qty') }}</th>
                    <th>{{ __('booking.unit_price') }}</th>
                    <th class="text-end">{{ __('booking.total_price') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data['extra']['services'] as $key => $value)
                    <tr>
                        <td>{{ $value['service_name'] }}</td>
                        <td>1</td>
                        <td class="text-end currency-font">{{ \Currency::format($value['service_price']) }}</td>
                        <td class="text-end currency-font">{{ \Currency::format($value['service_price']) }}</td>
                    </tr>
                @endforeach
                @php
                    $productPrice = 0;
                @endphp
                @foreach ($data['extra']['products'] as $key => $value)
                    <tr>
                        <td>{{ $value['product_name'] }}</td>
                        <td>{{ $value['product_qty'] }}</td>
                        @php
                            $price =
                                $value['discounted_price'] != $value['product_price']
                                    ? $value['discounted_price']
                                    : $value['product_price'];
                            $productPrice += $price * $value['product_qty'];
                        @endphp
                        <td class="text-end currency-font">{{ \Currency::format($price) }}</td>
                        <td class="text-end currency-font">{{ \Currency::format($price * $value['product_qty']) }}</td>
                    </tr>
                @endforeach
                @foreach ($data['extra']['packages'] as $key => $value)
                    <tr>
                        <td>{{ $value['name'] }}</td>
                        <td>1</td>
                        <td class="text-end currency-font">{{ \Currency::format($value['package_price']) }}</td>
                        <td class="text-end currency-font">{{ \Currency::format($value['package_price']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @if ($data['coupon_discount'])
                    <tr>
                        <td colspan="3" class="text-end"><strong>{{ __('booking.coupondiscount') }}:</strong></td>
                        <td class="text-end currency-font">{{ \Currency::format($data['coupon_discount']) }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="3" class="text-end"><strong>{{ __('booking.sub_total') }}:</strong></td>
                    <td class="text-end currency-font">
                        {{ \Currency::format($data['serviceAmount'] + $data['product_price'] + $data['package_price'] - $data['coupon_discount']) }}
                    </td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>{{ __('booking.tips') }}:</strong></td>
                    <td class="text-end currency-font">{{ \Currency::format($data['tip_amount']) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end"><strong>{{ __('booking.tax') }}:</strong></td>
                    <td class="text-end currency-font">{{ \Currency::format($data['tax_amount']) }}</td>
                </tr>

                <tr>
                    <td colspan="3" class="text-end"><strong>{{ __('booking.grand_total') }}:</strong></td>
                    <td class="text-end currency-font">{{ \Currency::format($data['grand_total']) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="thank-you">
            <p>{{ setting('spacial_note') }}</p>
        </div>
    </div>
</body>

</html>
