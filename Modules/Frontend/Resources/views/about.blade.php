@extends('frontend::layouts.master')

@section('content')
    <x-breadcrumb title="{{ __('frontend.about_us') }}" />
    <div class="about-us-section section-spacing-inner-pages">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4 class="mb-5 pb-3">{{ $aboutTitle }}</h4>
                    <div class="mb-3">{!! $aboutContent !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
