
@extends('frontend::layouts.master')
@section('title'){{__('frontend.contact_us')}} @endsection
@section('content')
<x-breadcrumb title="{{ __('frontend.contact_us') }}" />
<div class="contact-us-section section-spacing-inner-pages vujo-page-inner vujo-contact-page">
    <x-location_section/>
    <x-contact_banner_section :branch="$branch"/>
    <x-leave_section/>
</div>
@endsection