@extends('frontend::layouts.master')
@section('title') {{__('frontend.wishlist')}} @endsection

@section('content')

<x-breadcrumb :title="$pageTitle" />
<x-mywishlist_section/>

@endsection