@props(['booking'])

@php
    use Carbon\Carbon;
    use Modules\Employee\Models\EmployeeRating;

    // Status color logic
    $status = strtolower($booking->status);
    $statusColor = match ($status) {
        'pending' => 'text-warning',
        'confirmed' => 'text-primary',
        'cancelled' => 'text-danger',
        'complete', 'completed' => 'text-success',
        default => 'text-secondary',
    };
    // Payment status logic
    $bookingTransaction = is_iterable($booking->bookingTransaction ?? null)
        ? collect($booking->bookingTransaction)->sortByDesc('id')->first()
        : $booking->bookingTransaction ?? null;
    $rawPaymentStatus = $bookingTransaction->payment_status ?? '';
    $transactionType = $bookingTransaction->transaction_type ?? '';
    $bookingDate = $booking->start_date_time ?? null;
    $paymentStatus = $rawPaymentStatus;
    $isCash = $transactionType === 'cash' || strtolower($transactionType) === 'cash';
    $isCompleted = $status === 'complete' || $status === 'completed';
    if ($isCash && $isCompleted) {
        $paymentStatus = 'Paid';
    } elseif ($isCash && !$isCompleted) {
        $paymentStatus = 'Unpaid';
    } elseif ($rawPaymentStatus === 1 || $rawPaymentStatus === '1' || strtolower($rawPaymentStatus) === 'paid') {
        $paymentStatus = 'Paid';
    } elseif ($rawPaymentStatus === 0 || $rawPaymentStatus === '0') {
        $paymentStatus = 'Unpaid';
    } else {
        $paymentStatus = 'Unpaid';
    }
    $paymentStatusLower = strtolower($paymentStatus);
    $paymentColor = match ($paymentStatusLower) {
        'paid' => 'text-success',
        'unpaid' => 'text-danger',
        default => 'text-secondary',
    };
    $latestTransaction = is_iterable($booking->bookingTransaction ?? null)
        ? collect($booking->bookingTransaction)->sortByDesc('id')->first()
        : $booking->bookingTransaction ?? null;
    
    // Calculate total using branch-specific prices
    $branchId = $booking->branch_id ?? null;
    $serviceTotal = 0;
    foreach ($booking->booking_service as $service) {
        $servicePrice = $service->service_price ?? 0;
        // Get branch-specific price if branch exists
        if ($branchId && $service->service_id) {
            $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                ->where('branch_id', $branchId)
                ->first();
            if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                $servicePrice = $branchService->service_price;
            }
        }
        // Final fallback to default_price if service_price is still 0
        if ($servicePrice == 0 && $service->service && $service->service->default_price) {
            $servicePrice = $service->service->default_price;
        }
        $serviceTotal += $servicePrice;
    }
    // Add products and packages if they exist
    $productTotal = isset($booking->products) ? collect($booking->products)->sum('discounted_price') : 0;
    $packageTotal = isset($booking->packages) ? collect($booking->packages)->sum('package_price') : 0;
    $totalAmount = $serviceTotal + $productTotal + $packageTotal;

    // Check if user can rate this booking
    $canRate = false;
    if ($isCompleted && ($booking->can_rate ?? true)) {
        $employeeId = $booking->booking_service->first()->employee_id ?? null;

        if ($employeeId && auth()->check()) {
            $existingRating = EmployeeRating::where('employee_id', $employeeId)
                ->where('user_id', auth()->id())
                ->first();
            $canRate = !$existingRating;
        }
    }
@endphp


<div class="booking-card-container">
    <a href="{{ route('bookings.detail-page', $booking->id) }}" class="booking-card-link d-block text-reset ">
        <div class="booking-card-header">
            <div class="booking-card-image">
                <img src="{{ asset($booking->branch->feature_image ?? 'img/frontend/booking-salon.jpg') }}"
                    onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
                    alt="{{ $booking->branch->name ?? 'N/A' }}" class="avatar avatar-100 object-cover">
            </div>
            <div class="booking-card-content">
                <div class="booking-card-content-inner">
                    <span class="text-primary font-size-14">{{ setting('inv_booking_prefix') }} {{ $booking->id }}</span>
                    <h4 class="booking-card-title mb-0 mt-2">
                        {{ $booking->booking_service->first()->service->name ?? 'N/A' }}</h4>
                </div>
                <h4 class="booking-card-price text-primary m-0">
                    {{ \Currency::format($totalAmount ?? 0) }}</h4>
            </div>
        </div>
        <div class="booking-card-details">
            <div class="row">
                <div class="col-md-4">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg class="align-top" width="14" height="16" viewBox="0 0 14 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M1.0625 6.27029H12.9451" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9.96196 8.8738H9.96814" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M7.00102 8.8738H7.0072" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M4.04009 8.8738H4.04626" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9.96196 11.4656H9.96814" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M7.00102 11.4656H7.0072" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M4.04009 11.4656H4.04626" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9.69943 1.33398V3.52784" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M4.3088 1.33398V3.52784" stroke="currentColor" stroke-width="1.2"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M9.82551 2.38672H4.18064C2.22285 2.38672 1 3.47734 1 5.48207V11.5152C1 13.5514 2.22285 14.6673 4.18064 14.6673H9.81933C11.7833 14.6673 13 13.5703 13 11.5656V5.48207C13.0062 3.47734 11.7895 2.38672 9.82551 2.38672Z"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.date') }}</span>
                        </span>
                        <span id="booking-date-{{ $booking->id }}"
                            class="font-size-14">{{ \Carbon\Carbon::parse($booking->start_date_time)->format('M d, Y') }}</span>
                    </span>
                </div>
                <div class="col-md-4 mt-md-0 mt-3">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg class="align-top" width="14" height="14" viewBox="0 0 14 14" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M13.1693 7.00065C13.1693 10.4067 10.4086 13.1673 7.0026 13.1673C3.5966 13.1673 0.835938 10.4067 0.835938 7.00065C0.835938 3.59465 3.5966 0.833984 7.0026 0.833984C10.4086 0.833984 13.1693 3.59465 13.1693 7.00065Z"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M9.28677 8.96309L6.77344 7.46376V4.23242" stroke="currentColor"
                                        stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.time') }}</span>
                        </span>
                        <span id="booking-time-{{ $booking->id }}"
                            class="font-size-14">{{ \Carbon\Carbon::parse($booking->start_date_time)->format('h:i A') }}</span>
                    </span>
                </div>
                <div class="col-md-4 mt-md-0 mt-3">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg class="align-top" width="15" height="15" viewBox="0 0 15 15" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.82812 9.855L5.82355 7.26156L8.09967 9.04951L10.0524 6.5293"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <circle cx="12.3283" cy="2.801" r="1.28146" stroke="currentColor"
                                        stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    <path
                                        d="M8.9492 2.08008H4.10404C2.09641 2.08008 0.851562 3.50189 0.851562 5.50952V10.8978C0.851562 12.9054 2.072 14.3211 4.10404 14.3211H9.84012C11.8477 14.3211 13.0926 12.9054 13.0926 10.8978V6.20517"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.booking_status') }}</span>
                        </span>
                        <span id="booking-status-{{ $booking->id }}"
                            class="font-size-14 {{ $statusColor }}">{{ ucfirst($booking->status) }}</span>
                    </span>
                </div>
            </div>
            <span class="spacer"></span>
            <div class="row">
                <div class="col-md-4">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg class="align-top" width="12" height="16" viewBox="0 0 12 16"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M8.72727 9.65935C8.72727 9.80401 8.66981 9.94275 8.56751 10.045C8.46522 10.1473 8.32648 10.2048 8.18182 10.2048H3.81818C3.67352 10.2048 3.53478 10.1473 3.43249 10.045C3.33019 9.94275 3.27273 9.80401 3.27273 9.65935C3.27273 9.51468 3.33019 9.37595 3.43249 9.27365C3.53478 9.17136 3.67352 9.11389 3.81818 9.11389H8.18182C8.32648 9.11389 8.46522 9.17136 8.56751 9.27365C8.66981 9.37595 8.72727 9.51468 8.72727 9.65935ZM8.18182 6.93207H3.81818C3.67352 6.93207 3.53478 6.98954 3.43249 7.09183C3.33019 7.19413 3.27273 7.33287 3.27273 7.47753C3.27273 7.62219 3.33019 7.76093 3.43249 7.86322C3.53478 7.96552 3.67352 8.02298 3.81818 8.02298H8.18182C8.32648 8.02298 8.46522 7.96552 8.56751 7.86322C8.66981 7.76093 8.72727 7.62219 8.72727 7.47753C8.72727 7.33287 8.66981 7.19413 8.56751 7.09183C8.46522 6.98954 8.32648 6.93207 8.18182 6.93207ZM12 2.56844V14.023C12 14.3123 11.8851 14.5898 11.6805 14.7944C11.4759 14.999 11.1984 15.1139 10.9091 15.1139H1.09091C0.801582 15.1139 0.524105 14.999 0.31952 14.7944C0.114935 14.5898 0 14.3123 0 14.023V2.56844C0 2.27911 0.114935 2.00163 0.31952 1.79705C0.524105 1.59246 0.801582 1.47753 1.09091 1.47753H3.56318C3.86966 1.13439 4.24516 0.859841 4.66509 0.671864C5.08502 0.483888 5.53992 0.386719 6 0.386719C6.46008 0.386719 6.91498 0.483888 7.33491 0.671864C7.75484 0.859841 8.13034 1.13439 8.43682 1.47753H10.9091C11.1984 1.47753 11.4759 1.59246 11.6805 1.79705C11.8851 2.00163 12 2.27911 12 2.56844ZM3.81818 3.65935H8.18182C8.18182 3.08069 7.95195 2.52574 7.54278 2.11657C7.13361 1.7074 6.57865 1.47753 6 1.47753C5.42135 1.47753 4.86639 1.7074 4.45722 2.11657C4.04805 2.52574 3.81818 3.08069 3.81818 3.65935ZM10.9091 2.56844H9.08523C9.20931 2.91877 9.27271 3.28769 9.27273 3.65935V4.2048C9.27273 4.34947 9.21526 4.4882 9.11297 4.5905C9.01068 4.69279 8.87194 4.75026 8.72727 4.75026H3.27273C3.12806 4.75026 2.98933 4.69279 2.88703 4.5905C2.78474 4.4882 2.72727 4.34947 2.72727 4.2048V3.65935C2.72729 3.28769 2.79069 2.91877 2.91477 2.56844H1.09091V14.023H10.9091V2.56844Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.branch') }}</span>
                        </span>
                        <span class="font-size-14">
                            {{ $booking->branch->name ?? 'N/A' }}
                        </span>
                    </span>
                </div>
                <div class="col-md-4 mt-md-0 mt-3">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg class="align-top" width="12" height="14" viewBox="0 0 12 14"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M5.99125 9.23047C3.41284 9.23047 1.21094 9.62031 1.21094 11.1816C1.21094 12.7428 3.39887 13.1467 5.99125 13.1467C8.56967 13.1467 10.7709 12.7562 10.7709 11.1955C10.7709 9.63491 8.58364 9.23047 5.99125 9.23047Z"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M5.99383 7.00457C7.6859 7.00457 9.05732 5.63251 9.05732 3.94045C9.05732 2.24838 7.6859 0.876953 5.99383 0.876953C4.30177 0.876953 2.92971 2.24838 2.92971 3.94045C2.92399 5.62679 4.28653 6.99886 5.97225 7.00457H5.99383Z"
                                        stroke="currentColor" stroke-width="1.2" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.specialist') }}</span>
                        </span>
                        <span class="font-size-14">
                            @php
                                $specialists = $booking->booking_service
                                    ->map(function ($bs) {
                                        if (!empty($bs->employee->first_name) || !empty($bs->employee->last_name)) {
                                            return trim(
                                                ($bs->employee->first_name ?? '') .
                                                    ' ' .
                                                    ($bs->employee->last_name ?? ''),
                                            );
                                        }
                                        return $bs->employee->name ?? null;
                                    })
                                    ->filter()
                                    ->unique()
                                    ->implode(', ');
                                if (empty($specialists)) {
                                    // Fallback: get the first service's category name
    $firstService = $booking->booking_service->first();
    $categoryName =
        $firstService && $firstService->service && $firstService->service->category
            ? $firstService->service->category->name
            : null;
    $specialists = $categoryName ? $categoryName . ' Specialist' : 'N/A';
                                }
                            @endphp
                            {{ $specialists }}
                        </span>
                    </span>
                </div>
                <div class="col-md-4 mt-md-0 mt-3">
                    <span class="d-flex align-items-sm-baseline gap-sm-3 gap-2 flex-sm-row flex-column">
                        <span class="d-flex align-items-baseline gap-2 heading-color font-size-14">
                            <span class="icon d-inline-block lh-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 16 16" fill="none">
                                    <path
                                        d="M15.265 3.74861C15.193 3.70369 15.1108 3.67782 15.026 3.67345C14.9413 3.66907 14.8569 3.68634 14.7806 3.72361C12.0975 5.03611 10.1813 4.42111 8.15563 3.77236C6.03063 3.09174 3.8275 2.38861 0.78375 3.87486C0.698706 3.91566 0.626936 3.97967 0.576727 4.05952C0.526518 4.13937 0.499919 4.23179 0.5 4.32611V11.8217C0.499988 11.9066 0.521562 11.99 0.56269 12.0642C0.603819 12.1384 0.66315 12.2009 0.7351 12.2459C0.80705 12.2908 0.889253 12.3167 0.973972 12.3211C1.05869 12.3255 1.14314 12.3083 1.21938 12.2711C3.9025 10.9586 5.81875 11.5736 7.8475 12.2224C9.05 12.6067 10.275 12.9986 11.66 12.9986C12.7281 12.9986 13.8931 12.7661 15.2169 12.1199C15.301 12.0788 15.3718 12.015 15.4214 11.9357C15.471 11.8563 15.4974 11.7647 15.4975 11.6711V4.17549C15.4983 4.09043 15.4773 4.00659 15.4366 3.93189C15.3959 3.8572 15.3369 3.79411 15.265 3.74861ZM14.5 11.353C11.9625 12.4886 10.1094 11.8961 8.1525 11.2705C6.95 10.8861 5.725 10.4942 4.34 10.4942C3.36694 10.499 2.40378 10.6899 1.5025 11.0567V4.64424C4.04 3.50861 5.89313 4.10111 7.85 4.72674C9.80688 5.35236 11.8212 5.99861 14.5 4.94174V11.353ZM8 5.99861C7.60444 5.99861 7.21776 6.11591 6.88886 6.33567C6.55996 6.55544 6.30362 6.86779 6.15224 7.23325C6.00087 7.5987 5.96126 8.00083 6.03843 8.38879C6.1156 8.77675 6.30608 9.13312 6.58579 9.41283C6.86549 9.69253 7.22186 9.88301 7.60982 9.96018C7.99778 10.0374 8.39991 9.99775 8.76537 9.84637C9.13082 9.695 9.44318 9.43865 9.66294 9.10975C9.8827 8.78085 10 8.39418 10 7.99861C10 7.46818 9.78929 6.95947 9.41421 6.5844C9.03914 6.20933 8.53043 5.99861 8 5.99861ZM8 8.99861C7.80222 8.99861 7.60888 8.93996 7.44443 8.83008C7.27998 8.7202 7.15181 8.56402 7.07612 8.3813C7.00043 8.19857 6.98063 7.9975 7.01921 7.80352C7.0578 7.60954 7.15304 7.43136 7.29289 7.29151C7.43275 7.15165 7.61093 7.05641 7.80491 7.01783C7.99889 6.97924 8.19996 6.99905 8.38268 7.07473C8.56541 7.15042 8.72159 7.27859 8.83147 7.44304C8.94135 7.60749 9 7.80083 9 7.99861C9 8.26383 8.89464 8.51818 8.70711 8.70572C8.51957 8.89326 8.26522 8.99861 8 8.99861ZM3.5 5.99861V8.99861C3.5 9.13122 3.44732 9.2584 3.35355 9.35217C3.25979 9.44593 3.13261 9.49861 3 9.49861C2.86739 9.49861 2.74022 9.44593 2.64645 9.35217C2.55268 9.2584 2.5 9.13122 2.5 8.99861V5.99861C2.5 5.866 2.55268 5.73883 2.64645 5.64506C2.74022 5.55129 2.86739 5.49861 3 5.49861C3.13261 5.49861 3.25979 5.55129 3.35355 5.64506C3.44732 5.73883 3.5 5.866 3.5 5.99861ZM12.5 9.99861V6.99861C12.5 6.866 12.5527 6.73883 12.6464 6.64506C12.7402 6.55129 12.8674 6.49861 13 6.49861C13.1326 6.49861 13.2598 6.55129 13.3536 6.64506C13.4473 6.73883 13.5 6.866 13.5 6.99861V9.99861C13.5 10.1312 13.4473 10.2584 13.3536 10.3522C13.2598 10.4459 13.1326 10.4986 13 10.4986C12.8674 10.4986 12.7402 10.4459 12.6464 10.3522C12.5527 10.2584 12.5 10.1312 12.5 9.99861Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <span>{{ __('frontend.payment_status') }}</span>
                        </span>
                        <span class="font-size-14 {{ $paymentColor }}">{{ ucfirst($paymentStatus) ?: 'N/A' }}</span>
                    </span>
                </div>
            </div>
            @if ($booking->products && $booking->products->count())
                <div class="booking-card-products mt-2">
                    <strong>{{ __('frontend.products') }}:</strong>
                    <ul class="list-unstyled mb-0">
                        @foreach ($booking->products as $product)
                            <li>
                                {{ $product->product->name ?? 'N/A' }}
                                (x{{ $product->product_qty ?? 1 }})
                                - {{ \Currency::format($product->discounted_price ?? $product->product_price) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </a>
    <div class="booking-card-footer d-flex align-items-center row-gap-2 column-gap-3 flex-wrap">
        @if ($canRate)
            <button type="button" class="btn btn-primary rate-us-btn" data-booking-id="{{ $booking->id }}"
                data-bs-toggle="modal" data-bs-target="#rateUsModal">{{ __('frontend.rate_us') }}</button>
        @endif
        @if ($booking->status === 'pending' && ($booking->can_cancel ?? true))
            <button type="button" class="btn btn-secondary cancel-booking-btn"
                data-booking-id="{{ $booking->id }}">{{ __('frontend.cancel') }}</button>
        @endif
        @if ($booking->status === 'pending' && ($booking->can_reschedule ?? true))
            <button type="button" class="btn btn-orange text-secondary reschedule-booking-btn"
                data-booking-id="{{ $booking->id }}">{{ __('frontend.reschedule') }}</button>
        @endif
    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Use event delegation to handle all current and future booking card buttons
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.rate-us-btn, .cancel-booking-btn, .reschedule-booking-btn');
            if (btn && btn.closest('.booking-card-link')) {
                e.preventDefault();
                e.stopPropagation();
                // Let Bootstrap/modal logic continue
                return false;
            }
        }, true);
    });
</script>
