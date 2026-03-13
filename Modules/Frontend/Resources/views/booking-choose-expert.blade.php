{{-- - This is Important and Working Code - --}}


@extends('frontend::layouts.master')
@section('title')
    {{ __('frontend.choose_expert') }}
@endsection

@section('content')

    <x-breadcrumb :pageTitle="$pageTitle" />
    <div class="section-spacing-inner-pages">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                @php
                    $selectedServiceIds = isset($services) ? $services->pluck('id')->toArray() : [];
                    $serviceParams = $selectedServiceIds ? implode(',', $selectedServiceIds) : null;
                    $categorySlug = null;
                    if (isset($services) && $services->count() > 0) {
                        $firstService = $services->first();
                        $categorySlug = $firstService->category->slug ?? null;
                    }
                    $query = [];
                    if ($serviceParams) {
                        $query['selected_service'] = $serviceParams;
                    }
                    if ($categorySlug) {
                        $query['category'] = $categorySlug;
                    }

                    session([
                        'selected_service' => $serviceParams,
                        'selected_category' => $categorySlug,
                    ]);
                    $backUrl = route('service');
                    // If coming from Stripe, modify the back URL
                    if (request()->get('from_stripe') === 'true') {
                        $backUrl = route('booking-choose-expert', ['from_stripe' => 'true', 'step' => '2']);
                    }

                    // Check if there's a stored back URL from Stripe
if (request()->get('stripe_back') === 'true') {
    $backUrl = route('booking-choose-expert', ['from_stripe' => 'true', 'step' => '2']);
}

// Check if we're coming from the stripe-redirect route
                    if (request()->get('stripe_redirect') === 'true') {
                        $backUrl = route('booking-choose-expert', ['from_stripe' => 'true', 'step' => '2']);
                    }

                @endphp
                <a href="{{ $backUrl }}" class="text-body fw-medium d-inline-block" id="back-arrow-link">
                    <span class="d-flex align-items-center gap-1">
                        <i class="ph ph-caret-left"></i>
                        <span>{{ __('frontend.back') }}</span>
                    </span>
                </a>
            </div>
            @php
                $branchSelected = session()->has('selected_branch_id');
                $selectedServiceIds = $services->pluck('id')->toArray();
                $branches = \Modules\Service\Models\ServiceBranches::whereIn('service_id', $selectedServiceIds)
                    ->with('branch.media', 'branch.address')
                    ->get()
                    ->pluck('branch')
                    ->unique('id')
                    ->filter(function ($branch) {
                        return $branch && $branch->status == 1;
                    });
            @endphp

            @if (!$branchSelected)
                <div class="branch-section-wrapper">
                    <div class="container">
                        <!-- Stepper UI -->
                        <ul class="appointments-steps-list my-md-5 my-3">
                            <li class="appointments-steps-item active" data-check="false">
                                <div class="appointments-step">
                                    <a href="#" class="appointments-step-inner tab-index" data-index="0"
                                        data-check="false">
                                        <span class="d-flex align-items-center gap-3">
                                            <span class="step-counter">1</span>
                                            <span class="step-text">{{ __('frontend.select_branch') }}</span>
                                        </span>
                                    </a>
                                </div>
                            </li>
                            <li class="appointments-steps-item" data-check="false">
                                <div class="appointments-step">
                                    <a href="#" class="appointments-step-inner tab-index" data-index="1"
                                        data-check="false">
                                        <span class="d-flex align-items-center gap-3">
                                            <span class="step-counter">2</span>
                                            <span class="step-text">{{ __('frontend.choose_expert') }}</span>
                                        </span>
                                    </a>
                                </div>
                            </li>
                            <li class="appointments-steps-item" data-check="false">
                                <div class="appointments-step">
                                    <a href="#" class="appointments-step-inner tab-index" data-index="2"
                                        data-check="false">
                                        <span class="d-flex align-items-center gap-3">
                                            <span class="step-counter">3</span>
                                            <span class="step-text">{{ __('frontend.select_date_time_and_payment') }}</span>
                                        </span>
                                    </a>
                                </div>
                            </li>
                        </ul>
                        @if ($branches->isEmpty())
                            <div class="alert alert-warning text-center mt-4">
                                {{ __('frontend.No_branches_are_available_for_the_selected_service_Please_go_back_and_select_a_different_service') }}
                            </div>
                        @endif
                        <form id="branch-select-form">
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 gy-4"
                                id="branch-container">
                                @foreach ($branches as $branch)
                                    <div class="col branch-item @if ($loop->index >= 4) d-none @endif"
                                        data-index="{{ $loop->index }}">
                                        <label class="w-100 branch-card-label cursor-pointer">
                                            <input type="radio" name="branch_id" value="{{ $branch->id }}"
                                                class="d-none branch-radio"
                                                @if ($loop->first) checked @endif>
                                            <div
                                                class="branch-card rounded position-relative overflow-hidden @if ($loop->first) selected @endif">
                                                <div class="branch-image position-relative">
                                                    <img src="{{ $branch->media->pluck('original_url')->first() }}"
                                                        onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
                                                        class="card-img-top" alt="{{ $branch->name }}">
                                                    <span class="select-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                            height="24" viewBox="0 0 24 24" fill="none">
                                                            <rect width="24" height="24" rx="12"
                                                                fill="currentColor"></rect>
                                                            <g>
                                                                <path d="M7.375 12.75L10 15.375L16 9.375" stroke="white"
                                                                    stroke-width="3" stroke-linecap="round"
                                                                    stroke-linejoin="round"></path>
                                                            </g>
                                                            <defs>
                                                                <clipPath>
                                                                    <rect width="12" height="12" fill="white"
                                                                        transform="translate(5.5 6)"></rect>
                                                                </clipPath>
                                                            </defs>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="branch-info-box p-3">
                                                    <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                                        <h5 class="mb-0 fw-medium line-count-1">
                                                            <a
                                                                href="{{ route('branch-detail', $branch->id) }}">{{ $branch->name }}</a>
                                                        </h5>
                                                        <span
                                                            class="badge bg-purple text-body border rounded-pill text-uppercase">{{ $branch->type }}</span>
                                                    </div>
                                                    <span class="d-flex gap-2">
                                                        <i class="ph ph-map-pin align-middle"></i>
                                                        <span
                                                            class="font-size-14">{{ $branch->address->address_line_1 . ' ' . $branch->address->address_line_2 }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            @if ($branches->count() > 4)
                                <div class="text-center mt-3">
                                    <button type="button" id="view-more-branches" class="btn btn-outline-primary">
                                        {{ __('frontend.view_more') }} ({{ $branches->count() - 4 }}
                                        {{ __('frontend.more') }})
                                    </button>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end mt-4">
                                <button type="button" id="branch-next-btn" class="btn btn-primary px-5"
                                    @if ($branches->isEmpty()) disabled @endif>{{ __('frontend.next') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // View More functionality for branches
                        const viewMoreBtn = document.getElementById('view-more-branches');
                        const branchItems = document.querySelectorAll('.branch-item');
                        let allBranchesShown = false;

                        if (viewMoreBtn) {
                            viewMoreBtn.addEventListener('click', function() {
                                if (!allBranchesShown) {
                                    // Show all hidden branches
                                    branchItems.forEach(function(item) {
                                        item.classList.remove('d-none');
                                    });
                                    allBranchesShown = true;
                                    this.textContent = '{{ __('frontend.show_less') }}';
                                } else {
                                    // Hide branches after first 4
                                    branchItems.forEach(function(item, index) {
                                        if (index >= 4) {
                                            item.classList.add('d-none');
                                        }
                                    });
                                    allBranchesShown = false;
                                    this.textContent =
                                        '{{ __('frontend.view_more') }} ({{ $branches->count() - 4 }} {{ __('frontend.more') }})';
                                }
                            });
                        }

                        document.querySelectorAll('.branch-radio').forEach(function(radio) {
                            radio.addEventListener('change', function() {
                                document.querySelectorAll('.branch-card').forEach(function(card) {
                                    card.classList.remove('selected');
                                });
                                if (this.checked) {
                                    this.closest('.branch-card-label').querySelector('.branch-card').classList
                                        .add('selected');
                                }
                            });
                        });
                        document.getElementById('branch-next-btn').addEventListener('click', function() {
                            const selectedRadio = document.querySelector('.branch-radio:checked');
                            if (!selectedRadio) {
                                Swal.fire({
                                    icon: '{{ __('frontend.warning') }}',
                                    title: '{{ __('frontend.missing_information') }}',
                                    text: '{{ __('frontend.please_select_a_branch_before_proceeding') }}',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false
                                });
                                return;
                            }
                            const branchId = selectedRadio.value;
                            fetch("{{ route('branch.select') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        branch_id: branchId
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        window.location.reload();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: '{{ __('frontend.branch_selection_failed') }}',
                                            text: '{{ __('frontend.failed_to_set_branch') }}',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false,
                                        });
                                    }
                                });
                        });
                    });
                </script>
            @endif

            <div class="step" id="step1">
                <ul class="appointments-steps-list">
                    <li class="appointments-steps-item active" data-check="false">
                        <div class="appointments-step">
                            <a href="#" class="appointments-step-inner tab-index" data-index="0" data-check="false">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="step-counter">1</span>
                                    <span class="step-text">{{ __('frontend.choose_expert') }}</span>
                                </span>
                            </a>
                        </div>
                    </li>
                    <li class="appointments-steps-item" data-check="false">
                        <div class="appointments-step">
                            <a href="#" class="appointments-step-inner tab-index" data-index="1"
                                data-check="false">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="step-counter">2</span>
                                    <span class="step-text">{{ __('frontend.select_date_time_and_payment') }}</span>
                                </span>
                            </a>
                        </div>
                    </li>
                </ul>
                <div class="row mt-5">
                    <div class="col-xxl-9 col-lg-8">
                        <h5 class="mb-2">{{ __('frontend.choose_expert') }}</h5>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 row-cols-xxl-5 g-4">
                            @php
                                $employeesList = $employeesList ?? [];
                            @endphp
                            @if (count($employeesList) > 0)
                                @foreach ($employeesList as $employee)
                                    <div class="col">
                                        @include('frontend::components.card.choose_expert_card', [
                                            'employee' => $employee,
                                        ])
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <div class="p-4 rounded-3 text-center">
                                        <div class="mb-2">
                                            {{ __('frontend.No_experts_are_available_for_the_selected_service_at_the_moment') }}
                                        </div>
                                        <div class="d-flex justify-center">
                                            @php
                                                $selectedServiceIds = isset($services)
                                                    ? $services->pluck('id')->toArray()
                                                    : [];
                                                $serviceParams = $selectedServiceIds
                                                    ? implode(',', $selectedServiceIds)
                                                    : null;
                                                $backToServiceUrl = route(
                                                    'service',
                                                    $serviceParams ? ['selected_service' => $serviceParams] : [],
                                                );
                                            @endphp
                                            <a href="{{ $backUrl }}" class="btn btn-primary">
                                                {{ __('frontend.back_to_service') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @if (count($employeesList) > 10)
                            <div class="mt-5 text-center">
                                <button class="btn btn-secondary">{{ __('frontend.load_more') }}</button>
                            </div>
                        @endif
                    </div>
                    @if (count($employeesList) > 0)
                        <div class="col-xxl-3 col-lg-4 mt-lg-0 mt-5">
                            <div class="payment-section">
                                <h5>{{ __('frontend.payment_details') }}</h5>
                                <div class="payment-summary">
                                    @php
                                        $subtotal = 0;
                                        $totalTax = 0;
                                        $branchId = session('selected_branch_id');
                                    @endphp

                                    <!-- Selected Services -->
                                    @foreach ($services as $service)
                                        @php
                                            // Get branch-specific price, fallback to default_price if not found
                                            $servicePrice = $service->default_price;
                                            if ($branchId) {
                                                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->id)
                                                    ->where('branch_id', $branchId)
                                                    ->first();
                                                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                                                    $servicePrice = $branchService->service_price;
                                                }
                                            }
                                            $subtotal += $servicePrice;
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                            <span class="font-size-14">{{ $service->name }}</span>
                                            <span
                                                class="font-size-14 fw-medium heading-color">{{ Currency::format($servicePrice) }}</span>
                                        </div>
                                    @endforeach

                                    <hr class="line-divider" />

                                    <!-- Subtotal -->
                                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                        <span class="font-size-14">{{ __('frontend.subtotal') }}</span>
                                        <span
                                            class="font-size-14 fw-medium heading-color">{{ Currency::format($subtotal) }}</span>
                                    </div>

                                    <!-- Tax (collapsible) -->
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="font-size-14">{{ __('frontend.tax') }}</span>
                                        <div class="d-flex gap-2 align-items-center mb-1 price-item text-decoration-none  taxDetails {{ $totalTaxAmount == 0 ? 'd-none' : '' }}"
                                            data-bs-toggle="collapse" href="#taxDetailsStep1" role="button"
                                            aria-expanded="false" aria-controls="taxDetailsStep1">
                                            <i class="ph ph-caret-down rotate-icon tax1"></i>
                                            <span class="font-size-14 fw-medium text-danger">
                                                {{ Currency::format($totalTaxAmount ?? 0) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="collapse mt-2 mb-2" id="taxDetailsStep1">
                                        <div class="text-calculate card py-2 px-3" id="tax-breakdown">
                                            @foreach ($tax as $taxItem)
                                                @php
                                                    if ($taxItem->type == 'fixed') {
                                                        $taxAmount = $taxItem->value;
                                                    } else {
                                                        $taxAmount = ($subtotal * $taxItem->value) / 100;
                                                    }
                                                    $totalTax += $taxAmount;
                                                @endphp
                                                <div
                                                    class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-1' : '' }}">
                                                    <span class="font-size-12">{{ $taxItem->title }}
                                                        {{ $taxItem->type == 'fixed' ? '' : '(' . $taxItem->value . '%)' }}</span>
                                                    <span
                                                        class="font-size-12 text-danger fw-medium">{{ Currency::format($taxAmount) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <hr class="line-divider" />

                                    <!-- Total -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ __('frontend.total') }}</span>
                                        <span
                                            class="total-value fw-semibold text-primary">{{ Currency::format($subtotal + $totalTax) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <!-- Step 2 -->
            <div class="step" id="step2" style="display: none;">
                <!-- Use Points Section -->
                {{-- <div class="use-points-section mt-5">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <span class="text-primary fs-4">
                                    <i class="ph ph-hand-coins"></i>
                                </span>
                                <h5 class="mb-0">Use 200 points for $50 off</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 mt-sm-0 mt-4">
                            <div class="d-flex justify-content-end">
                                <a href="#" class="btn btn-secondary">Use Points</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patten-image">
                    <svg width="1920" height="27" viewBox="0 0 1920 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 8C16.6333 26.6667 33.2667 26.6667 49.9 8C66.5333 26.6667 83.1667 26.6667 99.8 8C116.433 26.6667 133.067 26.6667 149.7 8C166.333 26.6667 182.967 26.6667 199.6 8C216.233 26.6667 232.867 26.6667 249.5 8C266.133 26.6667 282.767 26.6667 299.4 8C316.033 26.6667 332.667 26.6667 349.3 8C365.933 26.6667 382.567 26.6667 399.2 8C415.833 26.6667 432.467 26.6667 449.1 8C465.733 26.6667 482.367 26.6667 499 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M499 8C514.867 26.6667 530.733 26.6667 546.6 8C562.467 26.6667 578.333 26.6667 594.2 8C610.067 26.6667 625.933 26.6667 641.8 8C657.667 26.6667 673.533 26.6667 689.4 8C705.267 26.6667 721.133 26.6667 737 8C752.867 26.6667 768.733 26.6667 784.6 8C800.467 26.6667 816.333 26.6667 832.2 8C848.067 26.6667 863.933 26.6667 879.8 8C895.667 26.6667 911.533 26.6667 927.4 8C943.267 26.6667 959.133 26.6667 975 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M975 8C990.867 26.6667 1006.73 26.6667 1022.6 8C1038.47 26.6667 1054.33 26.6667 1070.2 8C1086.07 26.6667 1101.93 26.6667 1117.8 8C1133.67 26.6667 1149.53 26.6667 1165.4 8C1181.27 26.6667 1197.13 26.6667 1213 8C1228.87 26.6667 1244.73 26.6667 1260.6 8C1276.47 26.6667 1292.33 26.6667 1308.2 8C1324.07 26.6667 1339.93 26.6667 1355.8 8C1371.67 26.6667 1387.53 26.6667 1403.4 8C1419.27 26.6667 1435.13 26.6667 1451 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M1451 8C1466.63 26.6667 1482.27 26.6667 1497.9 8C1513.53 26.6667 1529.17 26.6667 1544.8 8C1560.43 26.6667 1576.07 26.6667 1591.7 8C1607.33 26.6667 1622.97 26.6667 1638.6 8C1654.23 26.6667 1669.87 26.6667 1685.5 8C1701.13 26.6667 1716.77 26.6667 1732.4 8C1748.03 26.6667 1763.67 26.6667 1779.3 8C1794.93 26.6667 1810.57 26.6667 1826.2 8C1841.83 26.6667 1857.47 26.6667 1873.1 8C1888.73 26.6667 1904.37 26.6667 1920 8" stroke="currentColor" stroke-width="10"/>
                    </svg>
                </div>
            </div> --}}
                <!-- Use Points Section -->

                {{-- <div class="use-points-section mt-5">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <span class="text-success font-size-18 fw-medium">Applied:</span>
                                <h5 class="mb-0">$50 off with 200 points</h5>
                            </div>
                        </div>
                        <div class="col-sm-6 mt-sm-0 mt-4">
                            <div class="d-flex justify-content-end">
                                <a href="#" class="btn btn-secondary">Remove</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="patten-image">
                    <svg width="1920" height="27" viewBox="0 0 1920 27" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 8C16.6333 26.6667 33.2667 26.6667 49.9 8C66.5333 26.6667 83.1667 26.6667 99.8 8C116.433 26.6667 133.067 26.6667 149.7 8C166.333 26.6667 182.967 26.6667 199.6 8C216.233 26.6667 232.867 26.6667 249.5 8C266.133 26.6667 282.767 26.6667 299.4 8C316.033 26.6667 332.667 26.6667 349.3 8C365.933 26.6667 382.567 26.6667 399.2 8C415.833 26.6667 432.467 26.6667 449.1 8C465.733 26.6667 482.367 26.6667 499 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M499 8C514.867 26.6667 530.733 26.6667 546.6 8C562.467 26.6667 578.333 26.6667 594.2 8C610.067 26.6667 625.933 26.6667 641.8 8C657.667 26.6667 673.533 26.6667 689.4 8C705.267 26.6667 721.133 26.6667 737 8C752.867 26.6667 768.733 26.6667 784.6 8C800.467 26.6667 816.333 26.6667 832.2 8C848.067 26.6667 863.933 26.6667 879.8 8C895.667 26.6667 911.533 26.6667 927.4 8C943.267 26.6667 959.133 26.6667 975 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M975 8C990.867 26.6667 1006.73 26.6667 1022.6 8C1038.47 26.6667 1054.33 26.6667 1070.2 8C1086.07 26.6667 1101.93 26.6667 1117.8 8C1133.67 26.6667 1149.53 26.6667 1165.4 8C1181.27 26.6667 1197.13 26.6667 1213 8C1228.87 26.6667 1244.73 26.6667 1260.6 8C1276.47 26.6667 1292.33 26.6667 1308.2 8C1324.07 26.6667 1339.93 26.6667 1355.8 8C1371.67 26.6667 1387.53 26.6667 1403.4 8C1419.27 26.6667 1435.13 26.6667 1451 8" stroke="currentColor" stroke-width="10"/>
                        <path d="M1451 8C1466.63 26.6667 1482.27 26.6667 1497.9 8C1513.53 26.6667 1529.17 26.6667 1544.8 8C1560.43 26.6667 1576.07 26.6667 1591.7 8C1607.33 26.6667 1622.97 26.6667 1638.6 8C1654.23 26.6667 1669.87 26.6667 1685.5 8C1701.13 26.6667 1716.77 26.6667 1732.4 8C1748.03 26.6667 1763.67 26.6667 1779.3 8C1794.93 26.6667 1810.57 26.6667 1826.2 8C1841.83 26.6667 1857.47 26.6667 1873.1 8C1888.73 26.6667 1904.37 26.6667 1920 8" stroke="currentColor" stroke-width="10"/>
                    </svg>
                </div>
            </div> --}}


                <div class="container">
                    <ul class="appointments-steps-list">
                        <li class="appointments-steps-item complete" data-check="true">
                            <div class="appointments-step">
                                <a href="#" class="appointments-step-inner tab-index" data-index="0"
                                    data-check="true">
                                    <span class="d-flex align-items-center gap-3">
                                        <span class="step-counter">1</span>
                                        <span class="step-text">
                                            {{ __('frontend.choose_expert') }}
                                        </span>
                                    </span>
                                </a>
                            </div>
                        </li>
                        <li class="appointments-steps-item active" data-check="false">
                            <div class="appointments-step">
                                <a href="#" class="appointments-step-inner tab-index" data-index="1"
                                    data-check="false">
                                    <span class="d-flex align-items-center gap-3">
                                        <span class="step-counter">2</span>
                                        <span class="step-text">
                                            {{ __('frontend.select_date_time_and_payment') }}
                                        </span>
                                    </span>
                                </a>
                            </div>
                        </li>
                    </ul>

                    <div class="row mt-5">
                        <div class="col-xxl-9 col-lg-8">
                            <div class="row gy-5">
                                <div class="col-md-5">
                                    <h5>{{ __('frontend.select_date') }}</h5>
                                    <div class="flatpickr-inline-container">
                                        <input type="text" id="datePicker"
                                            class="form-control date-picker-opened mb-2" placeholder="Select Date"
                                            disabled />
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <h5>{{ __('frontend.select_time_slot') }}</h5>
                                    <div id="time-slots-container" class="bg-gray-800 rounded-3 p-5">
                                        <!-- Time slots will be rendered here -->
                                    </div>
                                </div>
                                <!-- <div class="col-12">
                                                        <h5>{{ __('frontend.select_payment_method') }}</h5>
                                                        <div>
                                                            <div class="dropdown payment-method-dropdown" id="payment-method-dropdown">
                                                                <button type="button"
                                                                    class="bg-gray-800 p-4 rounded d-flex flex-wrap align-items-center justify-content-between gap-3 w-100 border-0"
                                                                    id="selected-method-btn">
                                                                    <span class="d-flex align-items-center gap-3">
                                                                        <img id="selected-method-img"
                                                                            src="{{ asset('img/frontend/cash.svg') }}" alt="Cash">
                                                                        <span id="selected-method-name">{{ __('frontend.cash') }}</span>
                                                                    </span>
                                                                    <i class="ph ph-caret-down"></i>
                                                                </button><br>
                                                                <div class="dropdown-menu w-100 bg-gray-800 rounded booking-payment-method mt-3"
                                                                    id="payment-method-list">
                                                                    @php $first = true; @endphp
                                                                    <div class="list-group payment-list booking-payments-method">
                                                                        <label
                                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                            <span class="d-flex align-items-center gap-3">
                                                                                <img src="{{ asset('img/frontend/cash.svg') }}"
                                                                                    alt="Cash" class="avatar-28">
                                                                                <span>{{ __('frontend.cash') }}</span>
                                                                            </span>
                                                                            <input type="radio" class="form-check-input payment-radio"
                                                                                name="payment_method" value="cash" id="method-cash"
                                                                                @if ($first) checked @endif>
                                                                        </label>
                                                                        @if ($walletPayment)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/wallet.svg') }}"
                                                                                        alt="Wallet">
                                                                                    <span>{{ __('frontend.wallet') }}</span>
                                                                                    <span
                                                                                        class="text-success">({{ Currency::format($walletBalance) }})</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="wallet" id="method-wallet">
                                                                            </label>
    @endif
                                                                        @php $first = false; @endphp
                                                                        @if (setting('str_payment_method') == 1)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/stripe.svg') }}"
                                                                                        alt="Stripe">
                                                                                    <span>{{ __('frontend.stripe') }}</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="Stripe" id="method-Stripe">
                                                                            </label>
    @endif
                                                                        @if (setting('razor_payment_method') == 1)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/razorpay.svg') }}"
                                                                                        alt="RazorPay">
                                                                                    <span>{{ __('frontend.razorpay') }}</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="Razorpay"
                                                                                    id="method-razorpay">
                                                                            </label>
    @endif
                                                                        @if (setting('paystack_payment_method') == 1)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/paystack.svg') }}"
                                                                                        alt="Paystack">
                                                                                    <span>{{ __('frontend.paystack') }}</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="Paystack"
                                                                                    id="method-Paystack">
                                                                            </label>
    @endif
                                                                        @if (setting('paypal_payment_method') == 1)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/paypal.svg') }}"
                                                                                        alt="PayPal">
                                                                                    <span>{{ __('frontend.paypal') }}</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="PayPal" id="method-PayPal">
                                                                            </label>
    @endif
                                                                        @if (setting('flutterwave_payment_method') == 1)
    <label
                                                                                class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0  payment-method-items">
                                                                                <span class="d-flex align-items-center gap-3">
                                                                                    <img src="{{ asset('img/frontend/flutterwave.svg') }}"
                                                                                        alt="Flutterwave">
                                                                                    <span>{{ __('frontend.flutterwave') }}</span>
                                                                                </span>
                                                                                <input type="radio" class="form-check-input payment-radio"
                                                                                    name="payment_method" value="Flutterwave"
                                                                                    id="method-Flutterwave">
                                                                            </label>
    @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> -->

                                <div class="col-12">
                                    <h5>{{ __('frontend.select_payment_method') }}</h5>
                                    <div>
                                        <div class="payment-method-collapse" id="payment-method-collapse">
                                            <!-- Collapse toggle button -->
                                            <button type="button"
                                                class="bg-gray-800 p-4 rounded d-flex flex-wrap align-items-center justify-content-between gap-3 w-100 border-0"
                                                data-bs-toggle="collapse" data-bs-target="#payment-method-list"
                                                aria-expanded="false" aria-controls="payment-method-list"
                                                id="selected-method-btn">
                                                <span class="d-flex align-items-center gap-3">
                                                    <img id="selected-method-img"
                                                        src="{{ asset('img/frontend/cash.svg') }}" alt="Cash">
                                                    <span id="selected-method-name">{{ __('frontend.cash') }}</span>
                                                </span>
                                                <i class="ph ph-caret-down"></i>
                                            </button><br>

                                            <!-- Collapsible content -->
                                            <div class="collapse mt-3" id="payment-method-list">
                                                @php $first = true; @endphp
                                                <div
                                                    class="list-group payment-list booking-payments-method bg-gray-800 rounded booking-payment-method">
                                                    <label
                                                        class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                        <span class="d-flex align-items-center gap-3">
                                                            <img src="{{ asset('img/frontend/cash.svg') }}"
                                                                alt="Cash" class="avatar-28">
                                                            <span>{{ __('frontend.cash') }}</span>
                                                        </span>
                                                        <input type="radio" class="form-check-input payment-radio"
                                                            name="payment_method" value="cash" id="method-cash"
                                                            @if ($first) checked @endif>
                                                    </label>
                                                    @if ($walletPayment)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/wallet.svg') }}"
                                                                    alt="Wallet">
                                                                <span>{{ __('frontend.wallet') }}</span>
                                                                <span
                                                                    class="text-success">({{ Currency::format($walletBalance) }})</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="wallet" id="method-wallet">
                                                        </label>
                                                    @endif
                                                    @php $first = false; @endphp

                                                    @if (setting('str_payment_method') == 1)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/stripe.svg') }}"
                                                                    alt="Stripe">
                                                                <span>{{ __('frontend.stripe') }}</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="Stripe" id="method-Stripe">
                                                        </label>
                                                    @endif

                                                    @if (setting('razor_payment_method') == 1)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/razorpay.svg') }}"
                                                                    alt="RazorPay">
                                                                <span>{{ __('frontend.razorpay') }}</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="Razorpay"
                                                                id="method-razorpay">
                                                        </label>
                                                    @endif

                                                    @if (setting('paystack_payment_method') == 1)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/paystack.svg') }}"
                                                                    alt="Paystack">
                                                                <span>{{ __('frontend.paystack') }}</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="Paystack"
                                                                id="method-Paystack">
                                                        </label>
                                                    @endif

                                                    @if (setting('paypal_payment_method') == 1)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/paypal.svg') }}"
                                                                    alt="PayPal">
                                                                <span>{{ __('frontend.paypal') }}</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="PayPal" id="method-PayPal">
                                                        </label>
                                                    @endif

                                                    @if (setting('flutterwave_payment_method') == 1)
                                                        <label
                                                            class="list-group-item d-flex align-items-center justify-content-between gap-3 cursor-pointer border-0 bg-transparent p-0 payment-method-items">
                                                            <span class="d-flex align-items-center gap-3">
                                                                <img src="{{ asset('img/frontend/flutterwave.svg') }}"
                                                                    alt="Flutterwave">
                                                                <span>{{ __('frontend.flutterwave') }}</span>
                                                            </span>
                                                            <input type="radio" class="form-check-input payment-radio"
                                                                name="payment_method" value="Flutterwave"
                                                                id="method-Flutterwave">
                                                        </label>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-xxl-3 col-lg-4 mt-lg-0 mt-5">
                            <div class="payment-section">
                                <!-- Available Coupon Section -->
                                <div id="available-coupon-section" class="coupon-wrap">
                                    <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                        <h5 class="mb-0">{{ __('frontend.available_coupons') }}</h5>
                                        <button class="btn btn-link"
                                            id="view-all-coupons-btn">{{ __('frontend.view_all') }}</button>

                                    </div>
                                    <div class="input-group coupon-input-group mb-3">
                                        <input type="text" id="available-coupon-input"
                                            class="form-control coupon-input"
                                            placeholder="{{ __('frontend.enter_coupon_code') }}">
                                        <span class="input-group-text coupon-icon" id="coupon-addon">
                                            <i class="ph ph-seal-percent"></i>
                                        </span>
                                        <button class="btn btn-primary d-none" type="button"
                                            id="apply-coupon-btn">{{ __('frontend.apply') }}</button>
                                    </div>
                                    <span id="coupon-error-message" class="text-danger"></span>

                                    <!-- <button class="btn btn-link font-size-12 apply-coupon-btn">Apply</button> -->
                                </div>

                                <!-- Applied Coupon Section (Initially hidden) -->
                                <div id="applied-coupon-section" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                        <h5 class="mb-0">{{ __('frontend.apply_coupon') }}</h5>
                                        <button class="btn btn-link"
                                            id="coupon-cancel-btn">{{ __('frontend.remove') }}</button>
                                    </div>
                                    <div class="input-group coupon-input-group mb-3">
                                        <input type="text" id="applied-coupon-code" class="form-control coupon-input"
                                            placeholder="{{ __('frontend.enter_coupon_code') }}" readonly>
                                        <span
                                            class="position-absolute top-50 end-0 translate-middle-y fw-medium text-success"
                                            id="coupon-applied-label" style="display: none;">
                                            {{ __('frontend.applied') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-section">
                                <h5>{{ __('frontend.payment_details') }}</h5>
                                <div class="payment-summary">
                                    @php
                                        $subtotal = 0;
                                        $couponDiscount = 0;
                                        $serviceTax = $tax->where('type', 'fixed')->sum('value'); //  Fixed service tax
                                        $gstPercentage = $tax->where('type', 'percent')->sum('value'); // GST % sum
                                        $branchId = session('selected_branch_id');
                                    @endphp

                                    {{-- Hidden inputs for JavaScript calculations --}}
                                    <input type="hidden" id="gst-rate" value="{{ $gstPercentage }}">
                                    <input type="hidden" id="fixed-service-tax" value="{{ $serviceTax }}">

                                    {{-- List Services --}}
                                    @foreach ($services as $service)
                                        @php
                                            // Get branch-specific price, fallback to default_price if not found
                                            $servicePrice = $service->default_price;
                                            if ($branchId) {
                                                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->id)
                                                    ->where('branch_id', $branchId)
                                                    ->first();
                                                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                                                    $servicePrice = $branchService->service_price;
                                                }
                                            }
                                            $subtotal += $servicePrice;
                                        @endphp
                                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                            <span class="font-size-14">{{ $service->name }}</span>
                                            <span
                                                class="font-size-14 fw-medium heading-color">{{ Currency::format($servicePrice) }}</span>
                                        </div>
                                    @endforeach


                                    {{-- Coupon Discount (Hidden by default) --}}
                                    <div id="coupon-discount-section" class="d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                            <span class="font-size-14">
                                                {{ __('frontend.coupon_discount') }}<span id="discount-percentage-label"
                                                    class="text-success"></span>
                                            </span>
                                            <span class="font-size-14 fw-medium text-success" id="discount-amount"
                                                data-value="0">
                                                {{ Currency::format(0) }}
                                            </span>
                                        </div>
                                        <hr class="line-divider" />
                                    </div>


                                    {{-- Subtotal --}}
                                    <div class="d-flex justify-content-between align-items-center mb-1 price-item">
                                        <span class="font-size-14">{{ __('frontend.subtotal') }}</span>
                                        <span class="font-size-14 fw-medium heading-color" id="subtotal-amount"
                                            data-subtotal="{{ $subtotal }}">
                                            {{ Currency::format($subtotal) }}
                                        </span>
                                    </div>

                                    {{-- Tax Section --}}
                                    @if ($totalTaxAmount > 0)
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span>{{ __('frontend.tax') }}</span>
                                            <div class="d-flex gap-2 align-items-center mb-1 price-item text-decoration-none cursor-pointer  taxDetails"
                                                data-bs-toggle="collapse" href="#taxDetailsStep2" role="button"
                                                aria-expanded="false" aria-controls="taxDetailsStep2">
                                                <i class="ph ph-caret-down rotate-icon tax2"></i>
                                                <span class="font-size-14 fw-medium text-danger" id="tax-value-display">
                                                    {{ Currency::format($totalTaxAmount ?? 0) }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Tax Breakdown --}}
                                        <div class="collapse mt-2 mb-2" id="taxDetailsStep2">
                                            <div class="text-calculate card py-2 px-3" id="tax-breakdown">
                                                @foreach ($tax as $index => $taxItem)
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="font-size-12">{{ $taxItem->title }}
                                                            {{ $taxItem->type == 'percent' ? ' (' . $taxItem->value . '%)' : '' }}</span>
                                                        <span class="font-size-12 text-danger fw-medium"
                                                            id="tax-amount-{{ $index }}">{{ Currency::format($taxItem->type == 'fixed' ? $taxItem->value : (($subtotal ?? 0) * $taxItem->value) / 100) }}</span>
                                                    </div>
                                                @endforeach


                                            </div>
                                        </div>
                                    @endif

                                    <hr class="line-divider" />

                                    {{-- Total --}}
                                    <div class="d-flex justify-content-between align-items-center" id="total-row">
                                        <span>{{ __('frontend.total') }}</span>
                                        <span class="total-value fw-semibold text-primary" id="total-amount">
                                            {{ Currency::format($subtotal) }} {{-- JS will update this --}}
                                        </span>
                                    </div>


                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="onclick-page-redirect bg-orange p-3 d-none" id="expert-action-bar">
        <div class="container">
            <div class="text-end">
                <button type="button" class="btn btn-secondary px-5"
                    id="next-button">{{ __('frontend.next') }}</button>
            </div>
        </div>
    </div>
    <!-- Coupon Modal -->
    <div class="modal fade coupon-modal" id="coupon-modal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-md modal-dialog-scrollable">
            <div class="modal-content bg-gray-900 rounded">
                <div class="modal-header pb-0 flex-grow-1">
                    <h5 class="mb-0">{{ __('frontend.apply_coupon') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="coupon-inner-content">
                        <div class="coupon-container" id="coupon-list-container">
                            <!-- Coupons will be loaded here -->
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <!-- Optionally add more buttons here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Razorpay JS SDK -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id={{ setting('paypal_clientid') }}&currency=USD"></script>

    <script>
        const taxItems = @json($tax);
        console.log('Tax Items from server:', taxItems);
        // Initialize selectedCouponCode at the global scope
        let selectedCouponCode = null;
        window.isLoggedIn = @json(auth()->check());
        window.loginUrl = "{{ route('login') }}";

        document.addEventListener('DOMContentLoaded', function() {


            let currentStep = {{ $branchSelected ? 1 : 0 }};
            const totalSteps = {{ $branchSelected ? 2 : 3 }};
            const nextButton = document.getElementById('next-button');
            const backButton = document.querySelector('.text-body.fw-medium');
            let selectedEmployeeId = null;
            let branchId = '{{ session('selected_branch_id') }}';
            const datePicker = document.getElementById('datePicker');
            let selectedCouponCode = null;

            const availableInput = document.getElementById('available-coupon-input');
            const appliedInput = document.getElementById('applied-coupon-code');
            const appliedLabel = document.getElementById('coupon-applied-label');

            nextButton.disabled = true;

            // Initialize payment calculation on page load
            updateFinalTotal();

            if (datePicker) {
                datePicker.addEventListener('change', function() {
                    const selectedDate = datePicker.value;
                    const getAvailableSlotsUrl = "{{ route('get-available-slots') }}";
                    fetch(
                            `${getAvailableSlotsUrl}?date=${selectedDate}&branch_id=${branchId}&employee_id=${selectedEmployeeId}`
                        )
                        .then(response => response.json())
                        .then(data => {
                            const slotsContainer = document.getElementById('time-slots-container');
                            slotsContainer.innerHTML = '';

                            const today = new Date();
                            const yyyy = today.getFullYear();
                            const mm = String(today.getMonth() + 1).padStart(2, '0');
                            const dd = String(today.getDate()).padStart(2, '0');
                            const todayStr = `${yyyy}-${mm}-${dd}`;
                            const selectedDate = datePicker.value;

                            let slots = data.status === 'success' && Array.isArray(data.data) ? data
                                .data : [];
                            if (selectedDate === todayStr) {
                                const now = new Date();
                                slots = slots.filter(slot => {
                                    const slotDateTime = new Date(slot.value.replace(' ', 'T'));
                                    return slotDateTime > now;
                                });
                            }

                            if (slots.length) {
                                const morning = [],
                                    afternoon = [],
                                    evening = [];
                                slots.forEach(slot => {
                                    const time = slot.value.split(' ')[1];
                                    const [hour, minute] = time.split(':').map(Number);
                                    if (hour >= 9 && (hour < 12 || (hour === 11 && minute <=
                                            45))) {
                                        morning.push(slot);
                                    } else if (hour >= 12 && (hour < 17 || (hour === 16 &&
                                            minute <= 45))) {
                                        afternoon.push(slot);
                                    } else {
                                        evening.push(slot);
                                    }
                                });

                                // Create the booked-time container
                                const bookedTimeContainer = document.createElement('div');
                                bookedTimeContainer.className = 'booked-time';
                                slotsContainer.appendChild(bookedTimeContainer);

                                function renderGroup(title, group, isLast = false) {
                                    if (group.length) {
                                        const heading = document.createElement('h6');
                                        heading.className = 'mb-2';
                                        heading.textContent = title;
                                        bookedTimeContainer.appendChild(heading);

                                        const row = document.createElement('div');
                                        row.className = isLast ?
                                            'd-flex flex-wrap justify-content-start column-gap-4 row-gap-2' :
                                            'd-flex flex-wrap justify-content-start mb-5 column-gap-4 row-gap-2';
                                        group.forEach(slot => {
                                            const btn = document.createElement('button');
                                            btn.className = 'btn time-slot-btn';
                                            btn.textContent = slot.label;
                                            btn.disabled = slot.disabled;
                                            btn.setAttribute('data-value', slot.value);

                                            btn.addEventListener('click', function() {
                                                document.querySelectorAll(
                                                    '.time-slot-btn').forEach(b => {
                                                    b.classList.remove(
                                                        'selected');
                                                    b.classList.remove(
                                                        'active'
                                                    ); // Remove Bootstrap's active class
                                                });

                                                this.classList.add('selected');
                                                this.classList.add(
                                                    'active'
                                                ); // Keep Bootstrap's active style even after blur

                                                nextButton.disabled = false;
                                            });
                                            row.appendChild(btn);
                                        });
                                        bookedTimeContainer.appendChild(row);
                                    }
                                }

                                renderGroup('{{ __('frontend.morning') }}', morning);
                                renderGroup('{{ __('frontend.afternoon') }}', afternoon);
                                renderGroup('{{ __('frontend.evening') }}', evening, true);
                            } else {
                                slotsContainer.innerHTML =
                                    '<div class="text-danger">{{ __('frontend.no_slots_available') }}</div>';
                            }
                        })
                        .catch(console.error);
                });
            }

            const actionBar = document.getElementById('expert-action-bar');

            document.querySelectorAll('.choose-expert-card').forEach(card => {
                card.addEventListener('click', function() {
                    // nextButton.disabled = false;
                    selectedEmployeeId = this.dataset.employeeId;
                    document.querySelectorAll('.choose-expert-card').forEach(c => c.classList
                        .remove('selected'));
                    this.classList.add('selected');

                    const hiddenInput = document.getElementById('employee_id');
                    if (hiddenInput) hiddenInput.value = selectedEmployeeId;
                    if (actionBar) actionBar.classList.remove('d-none');
                    if (nextButton) nextButton.disabled = false;

                    // Reset time slots when expert changes
                    const slotsContainer = document.getElementById('time-slots-container');
                    if (slotsContainer) {
                        slotsContainer.innerHTML = '';
                    }
                    if (datePicker && datePicker.value) {
                        datePicker.dispatchEvent(new Event('change'));
                    }

                });
            });

            // Listen for expert selection changes from our component
            document.addEventListener('expertSelectionChanged', function(event) {
                if (event.detail && event.detail.selectedEmployeeId) {
                    selectedEmployeeId = event.detail.selectedEmployeeId;

                }
            });

            backButton.addEventListener('click', function(e) {
                if (currentStep > 1) {
                    e.preventDefault();
                    // If on payment step (step 2), go back to time slot selection (step 1)
                    if (currentStep === 2) {
                        document.getElementById('step2').style.display = 'none';
                        document.getElementById('step1').style.display = 'block';
                        currentStep = 1;
                        // Update stepper UI
                        document.querySelectorAll('.appointments-steps-item').forEach(function(item, idx) {
                            if (idx === 0) {
                                item.classList.add('active');
                                item.classList.remove('complete');
                            } else {
                                item.classList.remove('active');
                            }
                        });
                        nextButton.textContent = 'Next';
                        nextButton.disabled = true;
                    } else {
                        // Default: go back one step
                        document.getElementById(`step${currentStep}`).style.display = 'none';
                        currentStep--;
                        document.getElementById(`step${currentStep}`).style.display = 'block';
                        nextButton.disabled = true;
                    }
                }
            });

            // Handle back arrow from Stripe page
            const backArrowLink = document.getElementById('back-arrow-link');
            if (backArrowLink) {
                backArrowLink.addEventListener('click', function(e) {
                    const urlParams = new URLSearchParams(window.location.search);
                    const fromStripe = urlParams.get('from_stripe');

                    if (fromStripe === 'true') {
                        e.preventDefault();
                        // Redirect to step 2 of booking-choose-expert with from_stripe parameter
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('from_stripe', 'true');
                        currentUrl.searchParams.set('step', '2');
                        window.location.href = currentUrl.toString();
                    }
                });
            }

            // Handle direct access from Stripe back arrow
            document.addEventListener('DOMContentLoaded', function() {
                // Check if we're coming from Stripe back arrow
                const urlParams = new URLSearchParams(window.location.search);
                const fromStripeBack = urlParams.get('stripe_back');
                const fromStripe = urlParams.get('from_stripe');

                // Check if we're on the choose-expert route and should redirect to step 2
                const currentPath = window.location.pathname;
                const referrer = document.referrer;

                // If we're coming from Stripe (either via parameter or referrer), redirect to step 2
                if (fromStripeBack === 'true' || fromStripe === 'true' || urlParams.get(
                        'stripe_redirect') === 'true' ||
                    (currentPath.includes('choose-expert') && (referrer.includes('stripe.com') || referrer
                        .includes('checkout.stripe.com')))) {

                    // Redirect to step 2 with proper parameters
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('from_stripe', 'true');
                    currentUrl.searchParams.set('step', '2');
                    currentUrl.searchParams.delete('stripe_back');
                    currentUrl.searchParams.delete('stripe_redirect');

                    // Only redirect if we're not already on step 2
                    if (currentUrl.searchParams.get('step') !== '2') {
                        window.location.href = currentUrl.toString();
                    }
                }
            });

            // Handle back arrow click from Stripe page (when user clicks back arrow on Stripe)
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const fromStripe = urlParams.get('from_stripe');
                const backUrl = urlParams.get('back_url');
                const stripeBack = urlParams.get('stripe_back');

                if (fromStripe === 'true' && backUrl) {
                    // Store the back URL in session storage for later use
                    sessionStorage.setItem('stripe_back_url', backUrl);
                }

                // If coming from Stripe back arrow, redirect to step 2
                if (stripeBack === 'true') {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('from_stripe', 'true');
                    currentUrl.searchParams.set('step', '2');
                    currentUrl.searchParams.delete('stripe_back');
                    window.location.href = currentUrl.toString();
                }

                initializeCouponModal();
            });

            nextButton.addEventListener('click', function() {
                if (currentStep < totalSteps) {
                    document.getElementById(`step${currentStep}`).style.display = 'none';
                    currentStep++;
                    document.getElementById(`step${currentStep}`).style.display = 'block';
                    if (currentStep === totalSteps) {
                        nextButton.textContent = '{{ __('frontend.submit') }}';
                        updateFinalTotal();
                    }
                } else {
                    // Only on the final step (Submit), check for missing info and show confirmation
                    const employeeId = selectedEmployeeId;
                    const date = document.getElementById('datePicker')?.value;
                    const timeSlotBtn = document.querySelector('.time-slot-btn.selected');
                    const time = timeSlotBtn ? timeSlotBtn.getAttribute('data-value').split(' ')[1] :
                        document.getElementById('booking_time')?.value;
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')
                        ?.value;
                    if (!employeeId) {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ __('frontend.missing_information') }}',
                            text: '{{ __('frontend.please_select_an_expert') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                        return;
                    }
                    if (!date) {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ __('frontend.missing_information') }}',
                            text: '{{ __('frontend.please_select_a_date') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                        return;
                    }
                    if (!time) {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ __('frontend.missing_information') }}',
                            text: '{{ __('frontend.please_select_a_time_slot') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                        return;
                    }
                    if (!paymentMethod) {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ __('frontend.missing_information') }}',
                            text: '{{ __('frontend.please_select_a_payment_method') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false
                        });
                        return;
                    }
                    document.getElementById('confirmBooking')?.click();
                }
            });

            // Coupon modal functionality
            let couponModal = null;

            function initializeCouponModal() {
                const modalElement = document.getElementById('coupon-modal');
                if (!modalElement) return;

                // Initialize modal if not already done
                if (!couponModal) {
                    couponModal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });

                    // Load coupons on first initialization
                    loadCoupons();

                    // Clean up event listeners when modal is hidden
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        if (couponModal) {
                            couponModal.hide();
                        }
                    });
                }

                // Add event listeners
                modalElement.addEventListener('show.bs.modal', function() {
                    loadCoupons();
                });

                modalElement.addEventListener('hidden.bs.modal', function() {
                    // Clean up modal artifacts
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    if (typeof $ !== 'undefined') {
                        $('body').removeClass('modal-open').css({
                            overflow: '',
                            paddingRight: ''
                        });
                    }
                });
            }

            function loadCoupons() {
                const container = document.getElementById('coupon-list-container');
                if (!container) return;

                container.innerHTML = '<div class="text-center p-3">Loading coupons...</div>';

                fetch('{{ route('get-coupon-list') }}')
                    .then(response => response.json())
                    .then(data => {
                        if (data.status && data.data?.length) {
                            container.innerHTML = '';
                            let couponShown = false;

                            data.data.forEach((coupon) => {
                                // Filter out expired coupons
                                if (coupon.is_expired || coupon.is_expired === 1) return;

                                // Hide coupon if discount is greater than subtotal
                                var subtotal = parseFloat('{{ $subtotal ?? 0 }}');
                                if (coupon.discount_type === 'fixed' && parseFloat(coupon
                                        .discount_amount) > subtotal) return;

                                couponShown = true;
                                const card = document.createElement('div');
                                card.className =
                                    'coupon-card rounded bg-gray-800 d-flex align-items-sm-center flex-sm-row flex-column p-3 gap-3 mt-3 cursor-pointer';
                                card.style.cursor = 'pointer';
                                card.innerHTML = `
                                <div class="flex-shrink-0 d-flex align-items-center"></div>
                                <div class="d-flex align-items-start justify-content-between gap-3 flex-grow-1">
                                    <div class="flex-grow-1">
                                        <h6 class="font-size-16 mt-0 mb-1">${coupon.name || ''}</h6>
                                        <div class="mb-1 d-flex align-items-center gap-2">
                                            <span class="badge bg-success">${coupon.discount_type === 'fixed' ? (window.currencyFormat ? window.currencyFormat(coupon.discount_amount) + ' OFF' : ('$' + coupon.discount_amount + ' OFF')) : (coupon.discount_percentage + '% OFF')}</span>
                                            <span class="font-size-14">Code: <b>${coupon.coupon_code}</b></span>
                                        </div>
                                        <div class="font-size-13 text-body mb-1">${coupon.description || ''}</div>
                                        <div class="font-size-12 text-secondary">Valid: ${coupon.start_date_time} to ${coupon.end_date_time}</div>
                                    </div>
                                    <button class="btn btn-link font-size-12 apply-coupon-btn" data-code="${coupon.coupon_code}">Apply</button>
                                </div>
                            `;

                                container.appendChild(card);

                                // Add click handlers
                                const applyBtn = card.querySelector('.apply-coupon-btn');

                                function applyCoupon(e) {
                                    e.stopPropagation();
                                    applyCouponCode(coupon.coupon_code);
                                }

                                applyBtn.addEventListener('click', applyCoupon);
                                card.addEventListener('click', applyCoupon);
                            });

                            if (!couponShown) {
                                container.innerHTML =
                                    '<div class="text-center p-3">{{ __('frontend.no_coupons_available') }}</div>';
                            }
                        } else {
                            container.innerHTML =
                                '<div class="text-center p-3">{{ __('frontend.no_coupons_available') }}</div>';
                        }
                    })
                    .catch((error) => {
                        console.error('Coupon fetch error:', error);
                        container.innerHTML =
                            '<div class="text-center p-3 text-danger">{{ __('frontend.failed_to_load_coupons') }}</div>';
                    });
            }


            // initializeCouponModal();

            document.getElementById('coupon-cancel-btn')?.addEventListener('click', function() {
                resetCoupon();
            });

            document.addEventListener('click', function(e) {
                if (e.target && e.target.matches(
                        '[data-bs-toggle="modal"][data-bs-target="#coupon-modal"]')) {
                    e.preventDefault();
                    initializeCouponModal();
                    if (couponModal) {
                        couponModal.show();
                    }
                }
            });


            document.getElementById('view-all-coupons-btn')?.addEventListener('click', function(e) {
                e.preventDefault();

                // Re-initialize the modal to ensure it's properly set up
                const modalElement = document.getElementById('coupon-modal');
                if (modalElement) {
                    // Create new modal instance
                    couponModal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });

                    // Load coupons
                    loadCoupons();

                    // Show the modal
                    couponModal.show();

                    // Add cleanup handler
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        if (couponModal) {
                            couponModal.dispose();
                            couponModal = null;
                        }
                    }, {
                        once: true
                    });
                }
            });


            function resetCoupon() {
                document.getElementById('coupon-discount-section').classList.add('d-none');
                document.getElementById('coupon-error-message').textContent = '';
                // Hide the apply button
                const applyButton = document.getElementById('apply-coupon-btn');
                if (applyButton) {
                    applyButton.classList.add('d-none');
                }

                // Clear the applied coupon
                document.getElementById('available-coupon-input').value = '';
                document.getElementById('applied-coupon-code').value = '';

                // Hide the applied label
                document.getElementById('coupon-applied-label').style.display = 'none';

                // Reset discount amount
                document.getElementById('discount-amount').textContent = window.currencyFormat ? window
                    .currencyFormat(0) : '$0.00';
                document.getElementById('discount-amount').dataset.value = '0';
                document.getElementById('discount-percentage-label').textContent = '';

                // Show the available coupon section and hide the applied section
                document.getElementById('available-coupon-section').style.display = 'block';
                document.getElementById('applied-coupon-section').style.display = 'none';

                // Recalculate the total
                updateFinalTotal();

                // Clean up any modal artifacts that might interfere
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                if (typeof $ !== 'undefined') {
                    $('body').removeClass('modal-open').css({
                        overflow: '',
                        paddingRight: ''
                    });
                }


            }

        });

        // Move bookingConfirmed event listener outside DOMContentLoaded to avoid timing issues
        window.addEventListener('bookingConfirmed', function() {

            // Get selectedEmployeeId from the DOM or use a fallback
            let selectedEmployeeId = null;

            const selectedCard = document.querySelector('.choose-expert-card.selected');

            // Check if user is logged in
            if (!window.isLoggedIn) {
                // Collect selected service IDs from the DOM (checked checkboxes or from JS variable)
                let selectedServiceIds = [];
                document.querySelectorAll('.service-checkbox:checked').forEach(function(cb) {
                    selectedServiceIds.push(cb.value);
                });
                if (selectedServiceIds.length === 0) {
                    // Fallback to all services if none are checked (for single-service booking)
                    selectedServiceIds = @json($services->pluck('id'));
                }
                // Collect all required booking data
                const bookingData = {
                    expert_id: selectedEmployeeId,
                    date: document.getElementById('datePicker')?.value,
                    time: document.querySelector('.time-slot-btn.selected')?.getAttribute('data-value')?.split(
                        ' ')[1] || document.getElementById('booking_time')?.value,
                    payment_method: document.querySelector('input[name="payment_method"]:checked')?.value || '',
                    services: selectedServiceIds,
                    step: 'payment',
                    coupon_code: selectedCouponCode || '',
                    branch_id: '{{ session('selected_branch_id') }}'
                };

                fetch("{{ url('/store-booking-progress') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(bookingData)
                    })
                    .then(res => res.json())
                    .then(data => {
                        // Try multiple methods to show the login modal
                        setTimeout(() => {
                            if (typeof $ !== 'undefined' && $('#loginModal').length) {
                                try {
                                    $('#loginModal').modal('show');
                                } catch (e) {
                                    try {
                                        const loginModal = new bootstrap.Modal(document.getElementById(
                                            'loginModal'));
                                        loginModal.show();
                                    } catch (e2) {
                                        window.dispatchEvent(new Event('showLoginModal'));
                                    }
                                }
                            } else if (window.showLoginModal) {
                                window.showLoginModal();
                            } else {
                                // Try custom event as last resort
                                window.dispatchEvent(new Event('showLoginModal'));
                            }
                        }, 100);
                    })
                    .catch(error => {
                        setTimeout(() => {
                            if (typeof $ !== 'undefined' && $('#loginModal').length) {
                                $('#loginModal').modal('show');
                            }
                        }, 100);
                    });
                return;
            }
            if (selectedCard) {
                selectedEmployeeId = selectedCard.dataset.employeeId;
            }

            // Check if user is logged in
            if (!window.isLoggedIn) {

                // Collect selected service IDs from the DOM (checked checkboxes or from JS variable)
                let selectedServiceIds = [];
                document.querySelectorAll('.service-checkbox:checked').forEach(function(cb) {
                    selectedServiceIds.push(cb.value);
                });
                if (selectedServiceIds.length === 0) {
                    // Fallback to all services if none are checked (for single-service booking)
                    selectedServiceIds = @json($services->pluck('id'));
                }
                // Collect all required booking data
                const bookingData = {
                    expert_id: selectedEmployeeId,
                    date: document.getElementById('datePicker')?.value,
                    time: document.querySelector('.time-slot-btn.selected')?.getAttribute('data-value')?.split(
                        ' ')[1] || document.getElementById('booking_time')?.value,
                    payment_method: document.querySelector('input[name="payment_method"]:checked')?.value || '',
                    services: selectedServiceIds,
                    step: 'payment',
                    coupon_code: selectedCouponCode || '',
                    branch_id: '{{ session('selected_branch_id') }}'
                };

                fetch("{{ url('/store-booking-progress') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(bookingData)
                    })
                    .then(res => res.json())
                    .then(data => {

                        // Try multiple methods to show the login modal
                        setTimeout(() => {


                            if (typeof $ !== 'undefined' && $('#loginModal').length) {
                                try {
                                    $('#loginModal').modal('show');

                                } catch (e) {

                                    try {
                                        const loginModal = new bootstrap.Modal(document.getElementById(
                                            'loginModal'));
                                        loginModal.show();

                                    } catch (e2) {

                                        window.dispatchEvent(new Event('showLoginModal'));
                                    }
                                }
                            } else if (window.showLoginModal) {
                                window.showLoginModal();

                            } else {
                                // Try custom event as last resort
                                window.dispatchEvent(new Event('showLoginModal'));

                            }
                        }, 100);
                    })
                    .catch(error => {

                        setTimeout(() => {
                            if (typeof $ !== 'undefined' && $('#loginModal').length) {
                                $('#loginModal').modal('show');
                            }
                        }, 100);
                    });
                return;
            }
            const employeeId = selectedEmployeeId;
            const branchId = '{{ session('selected_branch_id') }}';
            const date = document.getElementById('datePicker')?.value;
            const timeSlotBtn = document.querySelector('.time-slot-btn.selected');
            const time = timeSlotBtn ? timeSlotBtn.getAttribute('data-value').split(' ')[1] : document
                .getElementById('booking_time')?.value;
            const services = @json($services->pluck('id'));
            const couponCode = document.querySelector('.coupon-input')?.value || '';
            const tip = 0;
            const subtotal = parseFloat('{{ $subtotal }}');
            const discount = parseFloat(document.getElementById('discount-amount')?.dataset.value || '0');
            const gstPercentage = parseFloat(document.getElementById('gst-rate')?.value || '0');
            const serviceTax = parseFloat(document.getElementById('fixed-service-tax')?.value || '0');

            // Calculate GST after discount
            const discountedSubtotal = subtotal - discount;
            const gstTax = (discountedSubtotal * gstPercentage) / 100;
            const taxItems = @json($tax);
            const taxBreakdown = taxItems.map(taxItem => {
                let amount = 0;

                if (taxItem.type === 'fixed') {
                    amount = parseFloat(taxItem.value);
                } else if (taxItem.type === 'percent') {
                    amount = (discountedSubtotal * parseFloat(taxItem.value)) / 100;
                }

                return {
                    name: taxItem.title,
                    type: taxItem.type,
                    percent: taxItem.type === 'percent' ? parseFloat(taxItem.value) : 0,
                    amount: parseFloat(amount.toFixed(2))
                };
            });

            // Final tax and total
            const totalTax = gstTax + serviceTax;
            const price = discountedSubtotal + totalTax;

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value;

            let discountPercentage = 0;
            const discountText = document.getElementById('discount-amount')?.textContent || '';
            const percentMatch = discountText.match(/(\d+)%/);
            if (percentMatch) {
                discountPercentage = parseFloat(percentMatch[1]);
            }

            if (!employeeId || !date || !time || !paymentMethod) {
                console.error('Missing required booking info:', {
                    employeeId,
                    date,
                    time,
                    paymentMethod
                });
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('frontend.missing_information') }}',
                    text: '{{ __('frontend.please_select') }}',
                    confirmButtonText: '{{ __('frontend.ok') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                return;
            }

            const payload = {
                employee_id: employeeId,
                branch_id: branchId,
                date: date,
                time: time,
                services: services,
                coupon_code: couponCode,
                tax_percentage: taxBreakdown,
                tip: tip,
                price: price,
                couponDiscountamount: discount,
                discount_amount: discount,
                discount_percentage: discountPercentage,
                payment_method: paymentMethod,
                _token: '{{ csrf_token() }}'
            };

            // Handle different payment methods
            if (paymentMethod.toLowerCase() === 'cash' || paymentMethod.toLowerCase() === 'wallet') {
                // Show loading state
                const submitBtn = document.querySelector('#next-button');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = '{{ __('frontend.processing') }}';

                if (paymentMethod.toLowerCase() === 'paystack') {
                    fetch("{{ route('booking.process-payment') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.success && response.reference) {
                                const paystackUrl = response.redirect;
                                const reference = response.reference;
                                const redirectWithRef = paystackUrl + '?reference=' + reference;
                                window.location.href = redirectWithRef;

                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('payment_initialization_failed') }}',
                                    text: response.error ||
                                        '{{ __('payment_initialization_failed') }}',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false,
                                });
                            }
                        })
                        .catch(err => Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.payment_error') }}',
                            text: 'Error: ' + err,
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        }));
                    return;
                }

                if (paymentMethod.toLowerCase() === 'flutterwave') {
                    fetch("{{ route('booking.process-payment') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.status === 'success' && response.data) {
                                const data = response.data;

                                FlutterwaveCheckout({
                                    public_key: data.public_key,
                                    tx_ref: data.tx_ref,
                                    amount: data.amount,
                                    currency: data.currency,
                                    country: data.country,
                                    payment_options: data.payment_options,
                                    customer: data.customer,
                                    meta: data.meta,
                                    customizations: data.customizations,
                                    redirect_url: data.redirect_url // auto-redirect after payment
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('payment_initialization_failed') }}',
                                    text: response.message ||
                                        '{{ __('frontend.flutterwave_payment_failed_to_initialize') }}',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false,
                                });
                            }
                        })
                        .catch(err => Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.payment_error') }}',
                            text: 'Error: ' + err,
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        }));

                    return;
                }

                if (paymentMethod.toLowerCase() === 'paypal') {
                    fetch("{{ route('booking.process-payment') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => res.json())
                        .then(response => {
                            if (response.order_id && response.client_id) {
                                const paypalContainer = document.createElement('div');
                                paypalContainer.id = 'paypal-button-container';
                                document.body.appendChild(paypalContainer);

                                paypal.Buttons({
                                    createOrder: function() {
                                        return response.order_id;
                                    },
                                    onApprove: function(data, actions) {
                                        return fetch("{{ route('booking.paypal-success') }}", {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json',
                                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                                },
                                                body: JSON.stringify({
                                                    orderID: data.orderID
                                                })
                                            })
                                            .then(res => res.json())
                                            .then(result => {
                                                if (result.success) {
                                                    window.location.href = result.redirect_url;
                                                } else {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: '{{ __('frontend.payment_failed') }}',
                                                        text: result.message ||
                                                            'Something went wrong.',
                                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                                        customClass: {
                                                            confirmButton: 'btn btn-primary'
                                                        },
                                                        buttonsStyling: false,
                                                    });
                                                }
                                            });
                                    },
                                    onCancel: function() {
                                        Swal.fire({
                                            icon: 'info',
                                            title: '{{ __('frontend.warning') }}',
                                            text: '{{ __('frontend.you_cancelled_the_paypal_payment') }}',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false,

                                        });
                                    },
                                    onError: function(err) {
                                        Swal.fire({
                                            icon: 'error',
                                            title: '{{ __('frontend.error') }}',
                                            text: 'PayPal error: ' + err.message,
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            },
                                            buttonsStyling: false,
                                        });
                                    }
                                }).render('#paypal-button-container');
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: '{{ __('frontend.payment_initialization_failed') }}',
                                    text: response.error ||
                                        '{{ __('frontend.unable_to_start_paypal_payment') }}',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false,
                                });
                            }
                        });
                    return;
                }



                fetch("{{ route('booking.process-payment') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(response => {
                        if (response.success) {
                            // Show success modal with booking details
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('frontend.booking_successful') }}',
                                html: `
                            <div>{{ __('frontend.thank_you_for_your_booking') }}</div>
                            {{ __('frontend.your_appointment_has_been_successfully_booked') }}<br><br>
                            <div>
                                ${response.booking_id ? `<span class="d-flex align-items-center justify-content-center gap-1 mb-2"><span class="heading-color fw-medium">{{ __('frontend.booking_id') }}</span> : <h6 class="mb-0 fw-bold text-primary">#${response.booking_id}</h6></span>` : ''}
                                <span class="d-flex align-items-center justify-content-center gap-1 mb-2"><span class="heading-color fw-medium">{{ __('frontend.payment_method') }}</span> : <h6 class="mb-0 fw-bold">${paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1)}</h6></span>
                                <span class="d-flex align-items-center justify-content-center gap-1"><span class="heading-color fw-medium">{{ __('frontend.total_amount') }}</span> : <h6 class="mb-0 fw-bold">${window.currencyFormat(price)}</h6></span>
                            </div>
                        `,
                                showConfirmButton: true,
                                showCancelButton: true,
                                confirmButtonText: '{{ __('frontend.View_Bookings') }}',
                                cancelButtonText: '{{ __('frontend.cancel') }}',
                                customClass: {
                                    confirmButton: 'swal2-confirm btn btn-primary',
                                    cancelButton: 'swal2-cancel btn btn-secondary'
                                },
                                allowOutsideClick: false,
                                reverseButtons: true,
                                didOpen: () => {
                                    // Optionally, you can style the icon or replace it with a custom SVG
                                    const icon = document.querySelector('.swal2-success');
                                    if (icon) {
                                        icon.style.borderColor = '#b2f2bb';
                                        icon.style.color = '#38b000';
                                    }
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = '{{ route('bookings') }}';
                                } else if (result.isDismissed) {
                                    window.location.href = '{{ route('service') }}';
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('frontend.booking_failed') }}',
                                text: response.message ||
                                    '{{ __('frontend.Failed_to_process_booking_Please_try_again') }}',
                                confirmButtonText: '{{ __('frontend.ok') }}',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false,
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.booking_error') }}',
                            text: '{{ __('frontend.An_error_occurred_while_processing_your_booking_Please_try_again') }}',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        });
                    })
                    .finally(() => {
                        // Reset button state
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
                return;
            }

            // For online payment methods, redirect if response.success and response.redirect
            fetch("{{ route('booking.process-payment') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {

                    return res.json();
                })
                .then(response => {

                    // Handle Razorpay response
                    if (response.key && response.amount && paymentMethod.toLowerCase() === 'razorpay') {
                        var options = {
                            key: response.key,
                            amount: response.amount,
                            currency: response.currency,
                            name: response.name,
                            description: response.description,
                            order_id: response.order_id,
                            handler: function(paymentResponse) {
                                if (paymentResponse.razorpay_payment_id) {
                                    const successUrl = new URL(response.success_url);
                                    successUrl.searchParams.append('gateway', 'razorpay');
                                    successUrl.searchParams.append('razorpay_payment_id',
                                        paymentResponse.razorpay_payment_id);
                                    successUrl.searchParams.append('razorpay_order_id', response
                                        .order_id);
                                    window.location.href = successUrl.toString();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '{{ __('frontend.payment_failed') }}',
                                        text: '{{ __('frontend.payment_was_not_completed_please_try_again') }}',
                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false,
                                    });
                                }
                            },
                            modal: {
                                ondismiss: function() {
                                    Swal.fire({
                                        icon: 'info',
                                        title: '{{ __('frontend.payment_cancelled') }}',
                                        text: '{{ __('frontend.Payment_was_cancelled_Please_try_again') }}',
                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        },
                                        buttonsStyling: false,
                                    });
                                }
                            },
                            prefill: response.prefill || {},
                            theme: {
                                color: "#F37254"
                            }
                        };
                        new Razorpay(options).open();
                        return;
                    }

                    // Handle other payment methods
                    if (response.redirect) {
                        // For Stripe, add back URL parameter
                        if (paymentMethod.toLowerCase() === 'stripe') {
                            const redirectUrl = new URL(response.redirect);
                            const backUrl = new URL(window.location.href);
                            backUrl.searchParams.set('from_stripe', 'true');
                            backUrl.searchParams.set('step', '2');
                            // Store the back URL in session storage
                            sessionStorage.setItem('stripe_back_url', backUrl.toString());
                            redirectUrl.searchParams.set('back_url', backUrl.toString());
                            window.location.href = redirectUrl.toString();
                        } else {
                            window.location.href = response.redirect;
                        }
                    } else if (response.success) {
                        window.location.href = '{{ route('bookings') }}';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('frontend.booking_failed') }}',
                            text: response.error || response.message || 'Payment failed.',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        });
                    }
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('frontend.error') }}',
                        text: 'An error occurred: ' + err,
                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                    });
                });
        });



        const dropdownBtn = document.getElementById('selected-method-btn');
        const dropdownMenu = document.getElementById('payment-method-list');
        const radios = document.querySelectorAll('.payment-radio');
        const selectedImg = document.getElementById('selected-method-img');
        const selectedName = document.getElementById('selected-method-name');

        // Helper: move UI to step 2 (Select date/time and payment)
        function goToStep2() {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            if (step1 && step2) {
                step1.style.display = 'none';
                step2.style.display = 'block';
            }
            currentStep = 2;
            // Update stepper UI (mark first as complete, second as active)
            const stepItems = document.querySelectorAll('.appointments-steps-item');
            stepItems.forEach((item, idx) => {
                if (idx === 0) {
                    item.classList.add('complete');
                    item.classList.remove('active');
                } else if (idx === 1) {
                    item.classList.add('active');
                    item.classList.remove('complete');
                }
            });
            // Show action bar and enable submit
            const actionBar = document.getElementById('expert-action-bar');
            if (actionBar) {
                actionBar.classList.remove('d-none');
                actionBar.style.display = '';
            }
            if (nextButton) {
                nextButton.disabled = false;
                nextButton.textContent = '{{ __('frontend.submit') }}';
            }
            // Close the dropdown if open
            if (dropdownMenu) dropdownMenu.style.display = 'none';
        }

        // Toggle dropdown
        dropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(event) {
            // Only close if the click is outside the dropdown and the button
            if (!dropdownMenu.contains(event.target) && !dropdownBtn.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });

        // Update selected method on radio change
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (radio.checked) {
                    let label = radio.value.charAt(0).toUpperCase() + radio.value.slice(1);

                    // Special handling for wallet to show balance
                    if (radio.value.toLowerCase() === 'wallet') {
                        const walletBalance = '{{ Currency::format($walletBalance ?? 0) }}';
                        selectedName.textContent = `Wallet (${walletBalance})`;
                    } else {
                        selectedName.textContent = label;
                    }

                    let imgSrc = '';
                    switch (radio.value.toLowerCase()) {
                        case 'cash':
                            imgSrc = '{{ asset('img/frontend/cash.svg') }}';
                            break;
                        case 'wallet':
                            imgSrc = '{{ asset('img/frontend/wallet.svg') }}';
                            break;
                        case 'stripe':
                            imgSrc = '{{ asset('img/frontend/stripe.svg') }}';
                            break;
                        case 'razorpay':
                            imgSrc = '{{ asset('img/frontend/razorpay.svg') }}';
                            break;
                        case 'paystack':
                            imgSrc = '{{ asset('img/frontend/paystack.svg') }}';
                            break;
                        case 'paypal':
                            imgSrc = '{{ asset('img/frontend/paypal.svg') }}';
                            break;
                        case 'flutterwave':
                            imgSrc = '{{ asset('img/frontend/flutterwave.svg') }}';
                            break;
                        default:
                            imgSrc = '';
                    }
                    if (selectedImg && imgSrc) {
                        selectedImg.src = imgSrc;
                        selectedImg.alt = label;
                    }
                    // Close the dropdown after selection
                    if (dropdownMenu) dropdownMenu.style.display = 'none';

                    // If Stripe is selected, immediately jump to Step 2
                    if (radio.value.toLowerCase() === 'stripe') {
                        goToStep2();
                    }
                }
            });
        });

        @if (!$branchSelected)
            // Branch selection logic
            const branchNextBtn = document.getElementById('branch-next-btn');
            branchNextBtn?.addEventListener('click', function() {
                const selectedBranch = document.querySelector('input[name="branch_id"]:checked')?.value;
                if (!selectedBranch) {
                    alert('Please select a branch.');
                    return;
                }
                fetch("{{ route('branch.select') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            branch_id: selectedBranch
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Hide branch step, show expert step
                            document.getElementById('step0').style.display = 'none';
                            document.getElementById('step1').style.display = 'block';
                            currentStep = 1;
                            // Optionally reload page to update session
                            window.location.reload();
                        } else {
                            alert('Failed to set branch.');
                        }
                    });
            });
            // Hide expert step initially
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'none';
        @endif

        // On page load, if all are present in bookingProgress, jump to payment step and pre-fill
        @if (isset($bookingProgress['expert_id']) &&
                isset($bookingProgress['date']) &&
                isset($bookingProgress['time']) &&
                isset($bookingProgress['payment_method']))
            document.addEventListener('DOMContentLoaded', function() {
                // Hide all steps
                if (document.getElementById('step0')) document.getElementById('step0').style.display = 'none';
                if (document.getElementById('step1')) document.getElementById('step1').style.display = 'none';
                if (document.getElementById('step2')) document.getElementById('step2').style.display = 'block';

                // Pre-fill date picker
                let datePicker = document.getElementById('datePicker');
                if (datePicker && '{{ $bookingProgress['date'] }}') {
                    datePicker.value = '{{ $bookingProgress['date'] }}';
                    // Optionally trigger change event if needed
                    if (typeof $ !== 'undefined') {
                        $(datePicker).trigger('change');
                    } else {
                        datePicker.dispatchEvent(new Event('change'));
                    }
                }

                let flatpickr = document.querySelector('.flatpickr-current-month');
                if (flatpickr) {
                    flatpickr.classList.add('d-none');
                }



                // Pre-select time slot
                setTimeout(function() {
                    let timeBtn = document.querySelector(
                        '.time-slot-btn[data-value*="{{ $bookingProgress['time'] }}"]');
                    if (timeBtn) {
                        timeBtn.classList.add('selected');
                    }

                    // Pre-select payment method
                    let paymentRadio = document.querySelector(
                        'input[name="payment_method"][value="{{ $bookingProgress['payment_method'] }}"]'
                    );
                    if (paymentRadio) {
                        paymentRadio.checked = true;
                        // Update payment method UI (icon and label)
                        let label = '{{ ucfirst($bookingProgress['payment_method']) }}';
                        let imgSrc = '';
                        switch ('{{ strtolower($bookingProgress['payment_method']) }}') {
                            case 'cash':
                                imgSrc = '{{ asset('img/frontend/cash.svg') }}';
                                break;
                            case 'wallet':
                                imgSrc = '{{ asset('img/frontend/wallet.svg') }}';
                                label = `Wallet ({{ Currency::format($walletBalance ?? 0) }})`;
                                break;
                            case 'stripe':
                                imgSrc = '{{ asset('img/frontend/stripe.svg') }}';
                                break;
                            case 'razorpay':
                                imgSrc = '{{ asset('img/frontend/razorpay.svg') }}';
                                break;
                            case 'paystack':
                                imgSrc = '{{ asset('img/frontend/paystack.svg') }}';
                                break;
                            case 'paypal':
                                imgSrc = '{{ asset('img/frontend/paypal.svg') }}';
                                break;
                            case 'flutterwave':
                                imgSrc = '{{ asset('img/frontend/flutterwave.svg') }}';
                                break;
                            default:
                                imgSrc = '';
                        }
                        const selectedImg = document.getElementById('selected-method-img');
                        const selectedName = document.getElementById('selected-method-name');
                        if (selectedImg && imgSrc) {
                            selectedImg.src = imgSrc;
                            selectedImg.alt = label;
                        }
                        if (selectedName) {
                            selectedName.textContent = label;
                        }
                    }

                    // Show and enable the submit button
                    const nextButton = document.getElementById('next-button');
                    if (nextButton) {
                        nextButton.style.display = '';
                        nextButton.disabled = false;
                        nextButton.textContent = '{{ __('frontend.submit') }}';
                    }
                    // Show the action bar
                    const actionBar = document.getElementById('expert-action-bar');
                    if (actionBar) {
                        actionBar.classList.remove('d-none');
                        actionBar.style.display = '';
                    }

                    // Make sure the correct step is visible
                    if (document.getElementById('step2')) {
                        document.getElementById('step2').style.display = 'block';
                    }
                    if (document.getElementById('step1')) {
                        document.getElementById('step1').style.display = 'none';
                    }
                    if (document.getElementById('step0')) {
                        document.getElementById('step0').style.display = 'none';
                    }
                }, 500);
            });
        @endif

        // Handle back arrow redirect from Stripe page
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const fromStripe = urlParams.get('from_stripe');

            if (fromStripe === 'true') {
                // Hide branch selection if exists
                if (document.getElementById('step0')) {
                    document.getElementById('step0').style.display = 'none';
                }

                // Hide expert selection step
                if (document.getElementById('step1')) {
                    document.getElementById('step1').style.display = 'none';
                }

                // Show payment step (step 2)
                if (document.getElementById('step2')) {
                    document.getElementById('step2').style.display = 'block';
                }

                // Update current step
                currentStep = 2;

                // Show action bar
                const actionBar = document.getElementById('expert-action-bar');
                if (actionBar) {
                    actionBar.classList.remove('d-none');
                    actionBar.style.display = '';
                }

                // Enable and update next button
                const nextButton = document.getElementById('next-button');
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.textContent = '{{ __('frontend.submit') }}';
                }

                // Update stepper UI to show step 2 as active
                document.querySelectorAll('.appointments-steps-item').forEach(function(item, idx) {
                    if (idx === 0) {
                        item.classList.add('complete');
                        item.classList.remove('active');
                    } else if (idx === 1) {
                        item.classList.add('active');
                        item.classList.remove('complete');
                    }
                });

                // Pre-fill any existing session data
                setTimeout(function() {
                    // Pre-fill date picker if available in session
                    let datePicker = document.getElementById('datePicker');
                    if (datePicker && '{{ session('booking_date') }}') {
                        datePicker.value = '{{ session('booking_date') }}';
                        datePicker.dispatchEvent(new Event('change'));
                    }

                    // Pre-select time slot if available
                    if ('{{ session('booking_time') }}') {
                        let timeBtn = document.querySelector(
                            '.time-slot-btn[data-value*="{{ session('booking_time') }}"]');
                        if (timeBtn) {
                            timeBtn.classList.add('selected');
                        }
                    }

                    // Pre-select payment method if available
                    if ('{{ session('payment_method') }}') {
                        let paymentRadio = document.querySelector(
                            'input[name="payment_method"][value="{{ session('payment_method') }}"]');
                        if (paymentRadio) {
                            paymentRadio.checked = true;
                            // Update payment method UI
                            let label = '{{ ucfirst(session('payment_method')) }}';
                            let imgSrc = '';
                            switch ('{{ strtolower(session('payment_method')) }}') {
                                case 'cash':
                                    imgSrc = '{{ asset('img/frontend/cash.svg') }}';
                                    break;
                                case 'stripe':
                                    imgSrc = '{{ asset('img/frontend/stripe.svg') }}';
                                    break;
                                case 'razorpay':
                                    imgSrc = '{{ asset('img/frontend/razorpay.svg') }}';
                                    break;
                                case 'paystack':
                                    imgSrc = '{{ asset('img/frontend/paystack.svg') }}';
                                    break;
                                case 'paypal':
                                    imgSrc = '{{ asset('img/frontend/paypal.svg') }}';
                                    break;
                                case 'flutterwave':
                                    imgSrc = '{{ asset('img/frontend/flutterwave.svg') }}';
                                    break;
                                default:
                                    imgSrc = '';
                            }
                            const selectedImg = document.getElementById('selected-method-img');
                            const selectedName = document.getElementById('selected-method-name');
                            if (selectedImg && imgSrc) {
                                selectedImg.src = imgSrc;
                                selectedImg.alt = label;
                            }
                            if (selectedName) {
                                selectedName.textContent = label;
                            }
                        }
                    }
                }, 500);
            }
        });

        // Restore step from URL after login
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const step = urlParams.get('step');
            const fromStripe = urlParams.get('from_stripe');

            if (step) {
                for (let i = 0; i <= 2; i++) {
                    let stepDiv = document.getElementById('step' + i);
                    if (stepDiv) stepDiv.style.display = 'none';
                }
                if (document.getElementById('step' + step)) {
                    document.getElementById('step' + step).style.display = 'block';
                }
                window.currentStep = step;
            }

            // If coming from Stripe, ensure we're on step 2
            if (fromStripe === 'true') {
                // Hide all steps first
                for (let i = 0; i <= 2; i++) {
                    let stepDiv = document.getElementById('step' + i);
                    if (stepDiv) stepDiv.style.display = 'none';
                }

                // Show step 2
                if (document.getElementById('step2')) {
                    document.getElementById('step2').style.display = 'block';
                }

                // Update current step
                currentStep = 2;

                // Show action bar
                const actionBar = document.getElementById('expert-action-bar');
                if (actionBar) {
                    actionBar.classList.remove('d-none');
                    actionBar.style.display = '';
                }

                // Enable and update next button
                const nextButton = document.getElementById('next-button');
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.textContent = 'Submit';
                }

                // Update stepper UI
                document.querySelectorAll('.appointments-steps-item').forEach(function(item, idx) {
                    if (idx === 0) {
                        item.classList.add('complete');
                        item.classList.remove('active');
                    } else if (idx === 1) {
                        item.classList.add('active');
                        item.classList.remove('complete');
                    }
                });
            }
        });

        // Update the URL with the current step whenever the user moves to a new step
        function updateStepInUrl(step) {
            const url = new URL(window.location.href);
            url.searchParams.set('step', step);
            window.history.replaceState({}, '', url);
        }

        // Example: Call updateStepInUrl(currentStep) whenever the step changes in your step navigation logic
        // ... existing code ...





        // ... existing code ...

        // --- Off Day Disabling Logic for Date Picker ---
        document.addEventListener('DOMContentLoaded', function() {
            let branchId = '{{ session('selected_branch_id') }}' || 1;
            let offDays = [];
            let holidayDates = []; // Array to store specific holiday dates
            const datePicker = document.getElementById('datePicker');

            // Fetch off days (days of week that are holiday)
            function fetchOffDays(branchId) {
                const apiUrl = "{{ url('/app/bussinesshours/index_list') }}?branch_id=" + (branchId || 1);
                fetch(apiUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status && Array.isArray(data.data)) {
                            offDays = data.data.filter(d => d.is_holiday == 1).map(d => d.day.charAt(0)
                                .toUpperCase() + d.day.slice(1).toLowerCase());

                        } else {
                            offDays = [];

                        }
                        updateDatePickerDisabledDays();
                    })
                    .catch(() => {
                        offDays = [];

                        updateDatePickerDisabledDays();
                    });
            }

            // Fetch specific holiday dates
            function fetchHolidayDates(branchId) {
                const apiUrl = "{{ url('/app/holidays/index_list') }}?branch_id=" + (branchId || 1);
                fetch(apiUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status && Array.isArray(data.data)) {
                            holidayDates = data.data.map(h => h.date);
                        } else {
                            holidayDates = [];
                        }
                        updateDatePickerDisabledDays();
                    })
                    .catch(() => {
                        holidayDates = [];
                        updateDatePickerDisabledDays();
                    });
            }

            function updateDatePickerDisabledDays() {
                if (window.flatpickr && datePicker && datePicker._flatpickr) {
                    datePicker._flatpickr.set('disable', [
                        function(date) {
                            // Check if it's an off day (day of week)
                            const dayName = date.toLocaleDateString('en-US', {
                                weekday: 'long'
                            });
                            if (offDays.includes(dayName)) {
                                return true;
                            }

                            // Check if it's a specific holiday date
                            // Format date in local timezone to avoid UTC conversion issues
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const dateStr = `${year}-${month}-${day}`; // Format: YYYY-MM-DD
                            if (holidayDates.includes(dateStr)) {
                                return true;
                            }

                            return false;
                        }
                    ]);
                    datePicker._flatpickr.redraw();
                } else {
                    setTimeout(updateDatePickerDisabledDays, 100);
                }
            }

            // Initialize Flatpickr
            if (window.flatpickr && datePicker) {
                flatpickr(datePicker, {
                    inline: true,
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                    defaultDate: new Date(),
                    allowInput: false, // prevents typing in the input
                    onReady: function(selectedDates, dateStr, instance) {
                        const monthSection = instance.calendarContainer.querySelector(
                            '.flatpickr-current-month');
                        if (monthSection) {
                            monthSection.classList.add('d-none');
                        }

                        instance.input.readOnly = true; // prevent manual typing (extra safety)
                        updateDatePickerDisabledDays();
                        setTimeout(function() {
                            datePicker.dispatchEvent(new Event('change'));
                        }, 100);
                    },
                    onMonthChange: updateDatePickerDisabledDays,
                    onYearChange: updateDatePickerDisabledDays
                });
            }

            // Fetch off days and holiday dates on page load
            fetchOffDays(branchId);
            fetchHolidayDates(branchId);
        });

        function applyCouponCode(code) {
            selectedCouponCode = code;
            document.getElementById('available-coupon-input').value = selectedCouponCode;
            document.getElementById('applied-coupon-code').value = selectedCouponCode;
            document.getElementById('coupon-applied-label').style.display = 'inline';
            document.getElementById('available-coupon-section').style.display = 'none';
            document.getElementById('applied-coupon-section').style.display = 'block';

            fetch(`{{ route('get-coupon-details') }}?code=${selectedCouponCode}`)
                .then(res => res.json())
                .then(data => {
                    // If coupon is invalid → show SweetAlert and reset UI
                    if (data.status === 'error') {

                        document.getElementById('coupon-error-message').textContent = data.message ||
                            'This coupon code is not valid.';
                        // Swal.fire({


                        //     icon: 'error',
                        //     title: 'Invalid Coupon',
                        //     text: data.message || 'This coupon code is not valid.',
                        //     confirmButtonText: 'OK',
                        //     customClass: {
                        //         confirmButton: 'btn btn-primary'
                        //     },
                        //     buttonsStyling: false,
                        // });


                        // Reset coupon UI state
                        document.getElementById('available-coupon-input').value = '';
                        document.getElementById('applied-coupon-code').value = '';
                        document.getElementById('coupon-applied-label').style.display = 'none';
                        document.getElementById('available-coupon-section').style.display = 'block';
                        document.getElementById('applied-coupon-section').style.display = 'none';
                        document.getElementById('applied-coupon-section').style.display = 'none';
                        document.getElementById('apply-coupon-btn').classList.add('d-none');

                        return;
                    }

                    if (data.data) {
                        const subtotal = parseFloat('{{ $subtotal }}');
                        const gstPercentage = parseFloat(document.getElementById('gst-rate')?.value || '0');
                        const serviceTax = parseFloat(document.getElementById('fixed-service-tax')?.value || '0');
                        let discount = 0;
                        let discountText = '';
                        const coupon = data.data;

                        if (coupon.discount_type === 'fixed') {
                            discount = Math.min(parseFloat(coupon.discount_amount) || 0, subtotal);
                            discountText = window.currencyFormat ? window.currencyFormat(discount) :
                                `${discount.toFixed(2)}`;
                        } else {
                            const percent = parseFloat((coupon.discount_percentage || '0').toString().replace('%',
                                '')) || 0;
                            discount = (subtotal * percent) / 100;
                            discount = Math.min(discount, subtotal);
                            discountText = window.currencyFormat ? window.currencyFormat(discount) :
                                `${discount.toFixed(2)}`;
                            document.getElementById('discount-percentage-label').textContent = ` (${percent}%)`;
                        }

                        const discountedSubtotal = subtotal - discount;
                        const gstTax = (discountedSubtotal * gstPercentage) / 100;
                        const totalTax = gstTax + serviceTax;
                        const grandTotal = discountedSubtotal + totalTax;

                        const taxItems = @json($tax);
                        const taxBreakdown = taxItems.map((taxItem, index) => {
                            let amount = 0;

                            if (taxItem.type === 'fixed') {
                                amount = parseFloat(taxItem.value);
                            } else if (taxItem.type === 'percent') {
                                // ✅ apply coupon: percent tax on discountedSubtotal
                                amount = (discountedSubtotal * parseFloat(taxItem.value)) / 100;
                            }

                            amount = parseFloat(amount.toFixed(2));

                            // ✅ Update the DOM for this tax line only
                            const amountEl = document.getElementById(`tax-amount-${index}`);
                            if (amountEl) {
                                amountEl.textContent = window.currencyFormat(amount);
                                amountEl.dataset.value = amount;
                            }

                            return {
                                name: taxItem.title,
                                type: taxItem.type,
                                percent: taxItem.type === 'percent' ? parseFloat(taxItem.value) : 0,
                                amount: Number.isFinite(amount) ? parseFloat(amount.toFixed(2)) : 0
                            };
                        });


                        const discountSection = document.getElementById('coupon-discount-section');
                        const discountAmountEl = document.getElementById('discount-amount');
                        if (discount > 0) {
                            discountSection.classList.remove('d-none');
                            discountAmountEl.textContent = window.currencyFormat(discount);
                            discountAmountEl.dataset.value = discount.toFixed(2);
                        } else {
                            discountSection.classList.add('d-none');
                        }

                        updateFinalTotal();

                        const modalElement1 = document.getElementById('coupon-modal');
                        if (modalElement1) {
                            modalElement1.style.display = 'none';
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) {
                                backdrop.remove();
                            }
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }

                    }
                })
                .catch(err => console.error("Coupon fetch failed:", err));
        }

        function updateFinalTotal() {
            const discountEl = document.getElementById('discount-amount');
            const totalAmountEl = document.getElementById('total-amount');
            const taxDisplayEl = document.getElementById('tax-value-display');

            const subtotal = parseFloat('{{ $subtotal ?? 0 }}');
            const gstPercentage = parseFloat(document.getElementById('gst-rate')?.value || '0');
            const serviceTax = parseFloat(document.getElementById('fixed-service-tax')?.value || '0');
            const discount = parseFloat(discountEl?.dataset.value || '0');

            const discountedSubtotal = subtotal - discount;
            const gstTax = (discountedSubtotal * gstPercentage) / 100;
            const totalTax = gstTax + serviceTax;
            const grandTotal = discountedSubtotal + totalTax;

            // Update total amount
            if (totalAmountEl) {
                totalAmountEl.textContent = window.currencyFormat(grandTotal);
            }

            // Update tax display
            if (taxDisplayEl) {
                taxDisplayEl.textContent = window.currencyFormat(totalTax);
            }

            // Update individual tax amounts
            const gstTaxEl = document.getElementById('gst-tax-amount');
            if (gstTaxEl) {
                gstTaxEl.textContent = window.currencyFormat(gstTax);
            }

            const serviceTaxEl = document.getElementById('service-tax-display');
            if (serviceTaxEl) {
                serviceTaxEl.textContent = window.currencyFormat(serviceTax);
            }

            // Update subtotal display (service amount - discount)
            const subtotalAmountEl = document.getElementById('subtotal-amount');
            if (subtotalAmountEl) {
                subtotalAmountEl.textContent = window.currencyFormat(discountedSubtotal);
                subtotalAmountEl.setAttribute('data-subtotal', discountedSubtotal);
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            const confirmBookingBtn = document.getElementById('confirmBooking');
            if (confirmBookingBtn) {
                confirmBookingBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '<div><img src="' + confirmBookingBtn.getAttribute(
                                'data-swal-image') +
                            '" /></div>{{ __('frontend.confirm_booking') }}',
                        html: `
                        <div>
                            <label>
                                <input type="checkbox" id="swal-terms-checkbox">
                                <span>
                                    {{ __('frontend.i_have_read_the_desclaimer_and_agree_upon_the') }}
                                    <a href="{{ route('page.show', 'terms-conditions') }}" target="_blank">{{ __('frontend.terms_and_conditions') }}</a>
                                </span>
                            </label>
                        </div>
                    `,
                        showCancelButton: true,
                        confirmButtonText: '{{ __('frontend.confirm') }}',
                        cancelButtonText: '{{ __('frontend.cancel') }}',
                        focusConfirm: false,
                        customClass: {
                            confirmButton: 'swal2-confirm btn btn-primary', // You can add your own classes here
                            cancelButton: 'swal2-cancel btn btn-secondary',
                            validationMessage: 'h6 text-danger fw-semibold body-bg my-2'
                        },
                        preConfirm: () => {
                            if (!document.getElementById('swal-terms-checkbox').checked) {
                                Swal.showValidationMessage(
                                    '{{ __('frontend.you_must_agree_to_the_terms_and_conditions') }}'
                                );
                                return false;
                            }
                            return true;
                        },
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.dispatchEvent(new Event('bookingConfirmed'));
                        }
                    });
                });
            }
        });

        // Debounce function to limit API calls
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, 500); // 500ms delay
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            const couponInput = document.getElementById('available-coupon-input');
            const applyButton = document.getElementById('apply-coupon-btn');
            const errorMessage = document.getElementById('coupon-error-message');

            errorMessage.textContent = '';

            const validateCoupon = debounce(async function(e) {
                const couponCode = e.target.value.trim();

                if (couponCode === '') {
                    applyButton.classList.add('d-none');
                    errorMessage.textContent = '';
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ route('get-coupon-details') }}?code=${encodeURIComponent(couponCode)}`
                        );
                    const data = await response.json();

                    if (data.status === 'success') {
                        // Valid coupon
                        errorMessage.textContent = '';
                        errorMessage.classList.remove('text-danger');
                        errorMessage.classList.add('text-success');
                        applyButton.classList.remove('d-none');
                    } else {
                        // Invalid coupon
                        errorMessage.classList.remove('text-success');
                        errorMessage.classList.add('text-danger');
                        errorMessage.textContent = data.message || 'Invalid coupon code';
                        applyButton.classList.add('d-none');
                    }
                } catch (error) {
                    console.error('Error validating coupon:', error);
                    errorMessage.classList.remove('text-success');
                    errorMessage.classList.add('text-danger');
                    errorMessage.textContent = 'Error validating coupon. Please try again.';
                    applyButton.classList.add('d-none');
                }
            }, 500);

            couponInput.addEventListener('input', validateCoupon);

            applyButton.addEventListener('click', function(e) {
                e.stopPropagation();
                const couponCode = couponInput.value.trim();

                if (couponCode) {
                    applyCouponCode(couponCode); // call the main coupon function directly
                }
            });

            // Also handle Enter key press
            couponInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && couponInput.value.trim() !== '') {
                    const couponCode = couponInput.value.trim();

                }
            });
        });
    </script>


    <!-- Hidden confirm booking button for SweetAlert2 -->
    <button id="confirmBooking" style="display:none" data-swal-title="{{ __('frontend.confirm_booking') }}"
        data-swal-text="{{ __('frontend.are_you_sure_you_want_to_book_this_appointment') }}" data-swal-icon=""
        data-swal-image="{{ asset('img/frontend/correct-icon.png') }}"
        data-swal-CancelButton="{{ __('frontend.cancel') }}" data-swal-button="{{ __('frontend.view_all') }}">
        Shows
    </button>

    <!-- Debug: Test login modal button (remove in production) -->
    @if (config('app.debug'))
        <script>
            function testLoginModal() {


                if (typeof $ !== 'undefined' && $('#loginModal').length) {
                    $('#loginModal').modal('show');

                } else if (window.showLoginModal) {
                    window.showLoginModal();

                } else {
                    console.error('No login modal found');
                }
            }
        </script>
    @endif

    @include('components.login_modal')
    @if ($errors->any())
        <script>
            $(document).ready(function() {
                $('#loginModal').modal('show');
                $('#modal_login_error_message').removeClass('d-none').text("{{ $errors->first() }}");
            });
        </script>
    @endif
@endsection
