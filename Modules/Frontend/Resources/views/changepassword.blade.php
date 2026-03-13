@extends('frontend::layouts.master')
@section('title') {{__('frontend.change_password')}} @endsection

@section('content')
    <x-breadcrumb title="{{ __('frontend.change_password') }}" />

    <x-changepassword_section/>

@endsection