@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Payment Successful!</h2>
    <p>Your payment via to <strong>{{ ucfirst($gateway) }}</strong> was successful.</p>
    <a href="{{ route('index') }}" class="btn btn-success">Return to Home</a>
</div>
@endsection 