@extends('frontend::layouts.master')
@section('title'){{ $page->name }} @endsection

@section('content')
    <x-breadcrumb/>
    <div class="about-us-section section-spacing-inner-pages">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <h4 class="mb-5 pb-3">{{ $page->name }}</h4>
                    <div class="mb-3">{!! $page->description !!}</div>
                </div>
            </div>
        </div>
    </div>
@endsection 