@extends('frontend::layouts.master')
@section('title') {{__('frontend.manage_address')}} @endsection

@section('content')

<x-breadcrumb title="{{ __('frontend.address') }}" />
<x-address_section
    :countries="$countries"
    :states="$states"
    :cities="$cities"
/>
@endsection 