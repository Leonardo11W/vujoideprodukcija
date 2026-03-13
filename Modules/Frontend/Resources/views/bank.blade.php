@extends('frontend::layouts.master')

@section('title')
{{ __('frontend.bank_list') }}
@endsection

@section('content')
<x-breadcrumb title="{{ __('frontend.bank_list') }}" />
<div class="section-spacing">
    <div class="container">
        <x-banklist_section :banks="$banks" />
    </div>
</div>
@endsection