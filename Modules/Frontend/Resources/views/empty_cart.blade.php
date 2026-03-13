@extends('frontend::layouts.master')
@section('title'){{__('frontend.cart')}} @endsection

@section('content')

<x-breadcrumb title="Cart" />


<div class="section-spacing-inner-pages">
    <div class="container">
       <div class="row gy-4">
          <div class="cart-page">            
             <div class="empty-cart-page d-flex flex-column justify-content-center align-items-center text-center=" >
                 <img src="{{ asset('img/frontend/empty-cart.jpg') }}" alt="Empty Cart" class="img-fluid mb-4 avatar-200">
                 <h5 class="mb-2">Your cart is empty</h5>
                 <p class="text-body mb-3">Add items to your cart to proceed with checkout</p>
                 <a href="{{ route('shop') }}" class="btn btn-primary">Continue Shopping</a>
             </div>
           
        </div>
        </div>
    </div>
</div>

@endsection
