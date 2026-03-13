@extends('frontend::layouts.master')

@section('content')
{{--
<x-membership_section />
<x-history_section /> --}}

<div class="section-spacing">
    <div class="container">
        <h5 class="mb-3 font-size-21-3">My Membership</h5>
        <div class="profilemembership-card-box bg-purple p-4 rounded position-relative overflow-hidden mb-5 pb-3">
            <div class="d-flex flex-wrap justify-content-between gap-lg-3 gap-2 flex-wrap mb-3">
                <h5 class="fw-semibold mb-0">Silver Membership</h5>
                <div class="d-flex align-items-center flex-wrap gap-lg-4 gap-2">
                    <span class="font-size-14">Your Plan Will Be Expiring On: <span class="text-danger">01 May, 2025</span></span>
                    <span class="badge bg-success text-white font-size-14 px-3 py-2 rounded-pill">Active</span>
                </div>
            </div>
            <div class="mb-0 d-flex align-items-center gap-3">
                <span class="text-primary h4 mb-0">$99</span>
                <span class="font-size-14 fw-semibold">/ 3 Months</span>
            </div>
            <img src="{{ asset('images/referral-bg-img.svg') }}" alt="profilemembership-bg-img" class="profilemembership-bg-img position-absolute z-0">
        </div>

        <div class="history-section">
            <h5 class="title-text font-size-21-3 mb-3">History</h5>
            <div class="table-responsive">
                <table class="table rounded mb-0 custom-table">
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Prices</th>
                            <th>Duration</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Payment Mode</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Silver Membership</td>
                            <td>$99</td>
                            <td>3 months</td>
                            <td>07 Dec, 2024</td>
                            <td>07 Mar, 2025</td>
                            <td>Cash</td>
                            <td class="text-center"><a href="#" class="download-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Invoice">
                                    <i class="ph ph-download-simple font-size-18 icon-color"></i>
                                </a></td>
                        </tr>
                        <tr>
                            <td>Gold Membership</td>
                            <td>$199</td>
                            <td>6 Months</td>
                            <td>12 may, 2024</td>
                            <td>12 Aug, 2025</td>
                            <td>Stripe</td>
                            <td class="text-center"><a href="#" class="download-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Invoice">
                                    <i class="ph ph-download-simple font-size-18 icon-color"></i>
                                </a></td>
                        </tr>
                        <tr>
                            <td>Platinum Membership</td>
                            <td>$299</td>
                            <td>1 Year</td>
                            <td>21 Sep, 2024</td>
                            <td>21 Dec, 2025</td>
                            <td>Stripe</td>
                            <td class="text-center"><a href="#" class="download-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Invoice">
                                    <i class="ph ph-download-simple font-size-18 icon-color"></i>
                                </a></td>
                        </tr>
                        <tr>
                            <td>Gold Membership</td>
                            <td>$199</td>
                            <td>6 Months</td>
                            <td>05 Apr, 2024</td>
                            <td>05 Jul, 2025</td>
                            <td>Stripe</td>
                            <td class="text-center"><a href="#" class="download-btn" data-bs-toggle="tooltip" data-bs-placement="top" title="Download Invoice">
                                    <i class="ph ph-download-simple font-size-18 icon-color"></i>
                                </a></td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
