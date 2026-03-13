@extends('frontend::layouts.master')
@section('title') {{__('frontend.booking_details')}} @endsection

@section('content')
<x-breadcrumb :title="__('frontend.booking_details')" />
<div class="section-spacing-inner-pages">
    <div class="container">
        <!-- <a href="{{ route('booking.invoice.download', $booking->id) }}" class="btn btn-primary mb-3">Download Invoice</a> -->
        <x-bookingdetails_section :booking="$booking" :employee-review="$employeeReview ?? null" :payment-summary="$paymentSummary ?? null"/>
    </div>
</div>

@endsection