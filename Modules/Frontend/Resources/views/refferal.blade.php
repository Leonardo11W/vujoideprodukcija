@extends('frontend::layouts.master')

@section('content')

{{-- <x-referral_section/>
<x-howitwork_section/>
<x-loyaltypoint_section/>
<x-usepoint_section/>
<x-removepoint_section/>
<x-bookingdetails_section/> --}}

<div class="section-spacing">
    <div class="refferal-container">
        <div class="container">
            <!-- Tabs Navigation -->
            <ul class="nav nav-pills row-gap-2 column-gap-3 branch-tab-content mb-5 m-0" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" data-bs-toggle="tab" href="#loyalty-tab" aria-selected="true" role="tab">
                        <span>Loyalty</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" data-bs-toggle="tab" href="#Referral" aria-selected="false" role="tab" tabindex="-1">
                        <span>Referral</span>
                    </a>
                </li>
            </ul>

            <!-- Tabs Content -->
            <div class="tab-content mt-5">

                <div class="tab-pane p-0 fade show active" id="loyalty-tab" role="tabpanel">
                    <x-loyaltypoint_section/>
                </div>

                <div class="tab-pane p-0 fade" id="Referral" role="tabpanel">
                    <x-referral_section />
                    <x-howitwork_section />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
