<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' || app()->getLocale() === 'fa' || app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
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

    h1, h2, h3, h4, h5, h6 {
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
      text-align: right;
    }

    .invoice-header h1 {
      margin: 0;
    }

    .invoice-details {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .invoice-footer {
      text-align: center;
    }

    .invoice-logo img {
      height: 40px;
    }

    .invoice-logo-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 0 0 20px;
      padding: 0 0 20px;
      border-bottom: 1px solid #f1f1f1;
    }
    .invoice-detail-part {
        display: flex;
        justify-content: space-between;
        margin: 16px 0;
    }



    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .invoice-table th, .invoice-table td {
      border: 1px solid #f1f1f1;
      padding: 16px 16px;
      text-align: left;
      font-size: 14px;
    }

    .invoice-table th {
      background-color: #f2f2f2;
    }

    .total {
      margin-top: 20px;
      text-align: right;
    }

    .thank-you {
      margin-top: 20px;
      border-top: 1px solid #f1f1f1;
      border-bottom: 1px solid #f1f1f1;
      padding: 16px;
      text-align: center;
    }
    .thank-you p {
      margin: 0;
    }
    .invoice-customer p {
      margin: 0 0 10px;
    }
    .invoice-customer h3,
    .invoice-billing h3 {
      margin-top: 0;
      margin-bottom: 8px;
    }

    strong {
      color: #000000;
    }

    table th {
      color: #000000;
    }

    table.invoice-table tr th:last-child,
    table.invoice-table tr td:last-child {
        text-align: right;
    }

    .invoice-payment {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 20px;
    }

    .invoice-pay-info h3 {
      margin: 0 0 8px;
    }

    .invoice-payment ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .invoice-payment ul li {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 3rem;
      margin-top: 10px;
    }

    .invoice-payment ul li label {
      font-weight: 600;
    }

    .invoice-order {
        margin: 0;
        position: relative;
        background: #f1f1f1;
        padding: 16px;
    }


  </style>
</head>
<body>
  <div class="invoice">
    <div class="invoice-logo-section">
      <div class="invoice-logo">
        @if(isset($logo) && $logo)
          <img src="{{ $logo }}" alt="logo" style="max-width: 200px; height: auto;">
        @elseif(setting('logo'))
          <img src="{{ public_path(setting('logo')) }}" alt="logo" style="max-width: 200px; height: auto;">
        @endif
      </div>
      <div class="invoice-header">
        <h1>{{ __('booking.download_invoice') }}</h1>
      </div>
    </div>

    <div class="invoice-details">
      <p class="invoice-order"><strong>{{ __('booking.download_invoice') }} {{ __('booking.booking_id') }}:</strong>
        @php
          $invPrefix = setting('inv_prefix');
          $prefixLower = strtolower(trim($invPrefix));
          if (strpos($prefixLower, 'booking') !== false) {
              $invPrefix = str_ireplace('Booking', __('booking.singular_title'), $invPrefix);
          } elseif (strpos($prefixLower, 'inv') !== false || strpos($prefixLower, 'invoice') !== false) {
              $invPrefix = str_ireplace(['INV', 'Invoice'], __('booking.download_invoice'), $invPrefix);
          }
        @endphp
        {{ $invPrefix }}{{$data['id']}}
      </p>
      <p><strong>{{ __('booking.lbl_booking_date') }}: </strong>{{$data['booking_date']}}</p>
    </div>

    <div class="invoice-detail-part">
      <div class="invoice-customer">
        <h3>{{ __('booking.customer_info') }}</h3>
        <p>{{$data['user_name']}}</p>
        <p>{{$data['email']}}</p>
        <p>{{$data['mobile']}}</p>
      </div>
      <div class="invoice-billing">
        <h3>{{ __('booking.billing_address') }}</h3>
        <p>{{$data['venue_address']}}</p>
      </div>

    </div>

    <table class="invoice-table">
      <thead>
        <tr>
          <th>{{ __('booking.services') }}/{{ __('booking.products') }}</th>
          <th>{{ __('booking.qty') }}</th>
          <th>{{ __('booking.unit_price') }}</th>
          <th>{{ __('booking.total_price') }}</th>
        </tr>
      </thead>
      <tbody>
      @php
            $productPrice = 0;
            $packagePrice = 0;
          @endphp
        @foreach($data['extra']['services'] as $key => $value)
        <tr>
          <td>{{$value['service_name']}}</td>
          <td>1</td>
          <td>{{$value['service_price']}}</td>
          <td>{{$value['service_price']}}</td>
        </tr>
        @endforeach

        <!-- @if (!empty($data['product_name'])) -->



      @foreach($data['extra']['products'] as $key => $value)
        <tr>
        <td>{{$value['product_name']}}</td>
        <td>{{$value['product_qty']}}</td>

          @php
                $price = $value['product_price'];
                $delPrice = false;
                $discountType = $value['discount_type'];
                $discountValue = $value['discount_value'] . ($discountType == 'percent' ? '%' : '');
                if($price != $value['discounted_price']) {
                    $delPrice = $price;
                    $price = $value['discounted_price'];
                }
                $productPrice = $price * $value['product_qty'] +$productPrice
          @endphp

        <td>{{$price}}</td>
        <td>{{ $price * $value['product_qty'] }}</td>
        </tr>

      @endforeach
        <!-- @endif -->
        @foreach($data['extra']['packages'] as $key => $value)

        <tr>
          <td>{{$value['name']}}</td>
          <td>1</td>
          <td>{{$value['package_price']}}</td>
          <td>{{$value['package_price']}}</td>
        </tr>

@endforeach
      </tbody>
      <tfoot>
        @if($data['coupon_discount'])
        <tr>
          <td colspan="3" style="color: #000000; text-align: right;"><strong>{{ __('booking.coupondiscount') }}:</strong></td>
          <td>{{ \Currency::format($data['coupon_discount']) }}</td>
          </tr>
          @endif
        <tr>
          <td colspan="3" style="color: #000000; text-align: right;"><strong>{{ __('booking.sub_total') }}:</strong></td>
          <td>{{ \Currency::format($data['serviceAmount'] + $productPrice + $data['package_price'] - $data['coupon_discount']) }}</td>
        </tr>
        <tr>
          <td colspan="3" style="color: #000000; text-align: right;"><strong>{{ __('booking.tips') }}:</strong></td>
          <td>{{ \Currency::format($data['tip_amount']) }}</td>
        </tr>
        <tr>
          <td colspan="3" style="color: #000000; text-align: right;"><strong>{{ __('booking.tax') }}:</strong></td>
          <td>{{ \Currency::format($data['tax_amount']) }}</td>
        </tr>
        
        <tr>
          <td colspan="3" style="color: #000000; text-align: right;"><strong>{{ __('booking.grand_total') }}:</strong></td>
          <td>{{ \Currency::format($data['grand_total']) }}</td>
        </tr>
      </tfoot>
    </table>

    <div class="invoice-payment">
      <div class="invoice-pay-info">
        <h3>{{ __('booking.payment_details') }}:</h3>
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
        <!-- <p>A/C Name: Alex Jender</p> -->
      </div>
    </div>

    <div class="thank-you">
      <p>{{ setting('spacial_note') }}</p>
    </div>
  </div>

</body>
</html>
