@extends('frontend::layouts.master')
@section('title') {{__('frontend.orders')}} @endsection

@section('content')

<x-breadcrumb title="{{ __('frontend.my_orders') }}" />
<x-myorder_section/>

@endsection