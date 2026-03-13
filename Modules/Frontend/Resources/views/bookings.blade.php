@extends('frontend::layouts.master')
@section('title') {{__('messages.booking')}} @endsection

@section('content')

<x-breadcrumb title="{{ __('frontend.my_bookings') }}" />
<div class="section-spacing-inner-pages">
    <div class="container">
        <x-booking_section :bookings="$bookings" :allBookingsCount="$allBookingsCount" :upcomingBookingsCount="$upcomingBookingsCount" :completedBookingsCount="$completedBookingsCount"/>
    </div>
</div>

@endsection