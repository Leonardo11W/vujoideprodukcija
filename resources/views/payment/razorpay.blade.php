@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Pay with Razorpay</h2>
    <p>Amount: <strong>{{ \Currency::format($price) }}</strong></p>
    <button id="pay-btn" class="btn btn-primary">Pay Now</button>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    document.getElementById('pay-btn').onclick = function(e){
        var options = {
            "key": "{{ $razorpayKey }}",
            "amount": "{{ $order['amount'] }}",
            "currency": "{{ $currency }}",
            "name": "{{ config('app.name') }}",
            "description": "Booking Payment",
            "order_id": "{{ $order['id'] }}",
            "handler": function (response){
                window.location.href = "{{ route('payment.success', ['gateway' => 'razorpay']) }}?payment_id=" + response.razorpay_payment_id;
            },
            "prefill": {
                "name": "{{ auth()->user()->name ?? '' }}",
                "email": "{{ auth()->user()->email ?? '' }}",
                "contact": "{{ auth()->user()->phone ?? '' }}"
            },
            "theme": {"color": "#528FF0"}
        };
        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function (response){
            window.location.href = "{{ route('payment.cancel', ['gateway' => 'razorpay']) }}";
        });
        rzp.open();
        e.preventDefault();
    }
</script>
@endsection 