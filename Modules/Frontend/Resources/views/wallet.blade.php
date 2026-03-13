@extends('frontend::layouts.master')
@section('title') {{__('frontend.wallet_balance')}} @endsection

@section('content')
    <x-breadcrumb title="{{ __('frontend.wallet') }}" />
    <div class="section-spacing-inner-pages">
        <div class="wallet-container">
            <div class="container">
                <x-balance_section :banks="$banks" :withdrawals="$withdrawals" />
                <x-history_section :withdrawals="$withdrawals" />
            </div>
        </div>
    </div>
@endsection
