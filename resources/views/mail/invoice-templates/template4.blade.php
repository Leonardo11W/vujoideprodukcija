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

    .invoice {
      width: 190mm;
      height: auto;
      box-sizing: border-box;
    }

    .invoice-header,
    .invoice-footer {
      text-align: center;
    }

    .invoice-header h1 {
      margin: 0;
    }

    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .invoice-table th, .invoice-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
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
    }
  </style>
</head>
<body>

  <div class="invoice">
    <div class="invoice-header" style="text-align: center; margin-bottom: 20px;">
      @if(isset($logo) && $logo)
        <img src="{{ $logo }}" alt="logo" style="max-width: 200px; height: auto; margin-bottom: 10px;">
      @elseif(setting('logo'))
        <img src="{{ public_path(setting('logo')) }}" alt="logo" style="max-width: 200px; height: auto; margin-bottom: 10px;">
      @endif
      <h1>{{ __('booking.download_invoice') }}</h1>
    </div>

    <div class="invoice-details">
      <p><strong>{{ __('booking.download_invoice') }} {{ __('booking.booking_id') }}:</strong>
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

    <table class="invoice-table">
      <thead>
        <tr>
          <th>{{ __('booking.services') }}/{{ __('booking.products') }}</th>
          <th>{{ __('booking.qty') }}</th>
          <th>{{ __('booking.unit_price') }}</th>
          <th>{{ __('booking.total_price') }}</th>
        </tr>
      </thead>
      @php
            $productPrice = 0;
            $package_price = 0;
          @endphp
     <tbody>
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
    </table>

    <div class="total">
        <p><strong>{{ __('booking.grand_total') }}:</strong>{{ \Currency::format($data['serviceAmount'] + $productPrice + $data['package_price']) }}

</p>
    </div>

    <div class="thank-you">
      <p>{{ setting('spacial_note') }}</p>
    </div>
  </div>

</body>
</html>
