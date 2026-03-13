@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Checkout</h2>
    <form method="POST" action="{{ route('payment.process') }}">
        @csrf
        <div class="mb-3">
            <label for="price" class="form-label">Amount</label>
            <input type="text" class="form-control" id="price" name="price" value="{{ old('price', $price ?? '') }}" readonly>
        </div>
        <div class="mb-3">
            <label for="payment_method" class="form-label">Select Payment Method</label>
            <select class="form-select" id="payment_method" name="payment_method" required>
                @foreach($methods as $method)
                    <option value="{{ $method }}">{{ ucfirst($method) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Pay Now</button>
    </form>
</div>
@endsection 