@extends('frontend::layouts.master')

@section('content')

<x-breadcrumb title="{{ __('frontend.membership') }}" />

<div class="section-spacing-inner-pages">
    <div class="container">
        <div class="section-title">
            <div class="text-center">
                <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">Our Membership</span>   
                <h4 class="title mb-0">Explore Our Exclusive Membership Plans</h4>
            </div>
        </div>
        <div class="row gy-5">
            <div class="col-lg-4 col-md-6">
                <x-membership_card/>
            </div>
            <div class="col-lg-4 col-md-6">
                <x-membership_card/>
            </div>
            <div class="col-lg-4 col-md-12">
                <x-membership_card/>
            </div>
        </div>
    </div>
</div>

@endsection