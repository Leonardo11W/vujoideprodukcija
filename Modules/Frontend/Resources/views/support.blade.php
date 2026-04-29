@extends('frontend::layouts.master')

@section('content')
    <x-breadcrumb title="{{ __('frontend.support') }}" />
    <div class="about-us-section section-spacing-inner-pages vujo-page-inner vujo-support-page">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4 class="mb-5 pb-3">{{ $supportTitle }}</h4>
                    <div class="mb-3">{!! $supportContent !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection 