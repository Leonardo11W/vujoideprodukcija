@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payment Failed</h2>
    <p>Unfortunately, your payment via to <strong>{{ ucfirst($gateway) }}</strong> could not be completed.</p>
    <a href="{{ route('payment.checkout') }}" class="btn btn-danger">Try Again</a>
</div>
@endsection 