@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.branch_details') }}
@endsection

@section('content')
    <x-breadcrumb :title="__('frontend.branch_details')" />

    <div class="section-spacing-inner-pages">
        <div class="container">
            <div class="row gy-4">
                <div class="col-xl-9 col-lg-8">
                    <div class="d-flex align-items-center gap-4 mb-3 pb-1">
                        <h4 class="mb-0">{{ $branch->name }}</h4>
                        @if ($branch->branch_for)
                            <span
                                class="badge bg-purple text-body border rounded-pill text-uppercase">{{ $branch->branch_for }}</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="ph-fill ph-star text-warning"></i>
                        <span>
                            <span class="fw-medium heading-color">{{ $branch->rating ?? '0.0' }}</span>
                            <span>({{ __('frontend.based_on_reviews', ['count' => $branch->total_review ?? '0']) }})</span>
                        </span>
                    </div>

                    <ul class="nav nav-pills row-gap-2 column-gap-3 branch-tab-content mt-5 m-0" role="tablist">
                        @if (!empty($branch->description) && trim($branch->description) !== '')
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" data-bs-toggle="tab" href="#about-us-branch" aria-selected="true"
                                    role="tab">
                                    <span>{{ __('frontend.about_us') }}</span></a>
                            </li>
                        @endif
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ empty($branch->description) || trim($branch->description) === '' ? 'active' : '' }}"
                                data-bs-toggle="tab" href="#service-branch"
                                aria-selected="{{ empty($branch->description) || trim($branch->description) === '' ? 'true' : 'false' }}"
                                role="tab"
                                tabindex="{{ empty($branch->description) || trim($branch->description) === '' ? '0' : '-1' }}"><span>{{ __('frontend.services') }}</span></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#review-branch" aria-selected="false"
                                role="tab" tabindex="-1"><span>{{ __('frontend.reviews') }}</span></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" data-bs-toggle="tab" href="#staff-branch" aria-selected="false"
                                role="tab" tabindex="-1"><span>{{ __('frontend.staff') }}</span></a>
                        </li>
                        @if ($branch->gallerys->count() > 0)
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" data-bs-toggle="tab" href="#gallery-branch" aria-selected="false"
                                    role="tab" tabindex="-1"><span>{{ __('frontend.gallery') }}</span></a>
                            </li>
                        @endif
                    </ul>
                    <div class="tab-content mt-5">
                        @if (!empty($branch->description) && trim($branch->description) !== '')
                            <div class="tab-pane p-0 fade show active" id="about-us-branch" role="tabpanel">
                                <div class="about-us-section">
                                    <h4>{{ __('frontend.about_us') }}</h4>
                                    <p>{{ $branch->description }}</p>
                                </div>
                            </div>
                        @endif
                        <div class="tab-pane p-0 fade {{ empty($branch->description) || trim($branch->description) === '' ? 'show active' : '' }}"
                            id="service-branch" role="tabpanel">
                            @if ($services->count() > 0)
                                <div class="row row-cols-1 row-cols-xl-2 gy-4" id="service-cards-container">
                                    @foreach ($services as $service)
                                        <div class="col">
                                            <x-service_card :service="$service" />
                                        </div>
                                    @endforeach
                                </div>
                                @if ($services->hasMorePages())
                                    <div class="d-flex align-items-center justify-content-center mt-5">
                                        <a href="{{ $services->nextPageUrl() }}#service-branch"
                                            class="btn btn-secondary mt-4" id="load-more-services-btn"
                                            onclick="event.preventDefault(); loadMoreServices('{{ $services->nextPageUrl() }}'); return false;">
                                            {{ __('frontend.load_more') }}
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">{{ __('frontend.no_services_available_for_this_branch') }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane p-0 fade" id="review-branch" role="tabpanel">
                            @if ($reviews->count() > 0)
                                <div class="row gy-4" id="reviews-container">
                                    @foreach ($reviews as $review)
                                        <div class="col-12 display: none;">
                                            <div class="review-card p-4 border rounded"
                                                data-review-id="{{ $review->id }}">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="reviewer-avatar">
                                                        <img src="{{ $review->user ? $review->user->getFirstMediaUrl('profile_image') : asset('img/frontend/user-avatar.png') }}"
                                                            alt="{{ $review->user ? $review->user->full_name : 'Anonymous' }}"
                                                            class="rounded-circle"
                                                            style="width: 50px; height: 50px; object-fit: cover;">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                                            <h6 class="mb-0">
                                                                {{ $review->user ? $review->user->full_name : 'Anonymous' }}
                                                            </h6>
                                                            <div class="d-flex align-items-center gap-1">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    @if ($i <= $review->rating)
                                                                        <i class="ph-fill ph-star text-warning"></i>
                                                                    @else
                                                                        <i class="ph ph-star text-muted"></i>
                                                                    @endif
                                                                @endfor
                                                                <span
                                                                    class="ms-2 text-muted">{{ $review->rating }}/5</span>
                                                            </div>
                                                        </div>
                                                        @if ($review->review_msg)
                                                            <p class="text-muted mb-2">{{ $review->review_msg }}</p>
                                                        @endif
                                                        <small
                                                            class="text-muted">{{ $review->updated_at->format('M d, Y') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($reviews->hasMorePages())
                                    <div class="d-flex align-items-center justify-content-center mt-5">
                                        <a href="{{ $reviews->nextPageUrl() }}#review-branch"
                                            class="btn btn-secondary mt-4" id="load-more-reviews-btn"
                                            onclick="event.preventDefault(); loadMoreReviews('{{ $reviews->nextPageUrl() }}'); return false;">
                                            {{ __('frontend.load_more_reviews') }}
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">{{ __('frontend.no_reviews_available_for_this_branch') }}</p>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane p-0 fade" id="staff-branch" role="tabpanel">
                            @if ($branch->branchEmployee->count() > 0)
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 gy-4">
                                    @foreach ($branch->branchEmployee as $branchEmployee)
                                        @if ($branchEmployee->employee)
                                            @php
                                                $employee = $branchEmployee->employee;
                                                $employeeRating = \Modules\Employee\Models\EmployeeRating::where(
                                                    'employee_id',
                                                    $employee->id,
                                                )->avg('rating');
                                                $employeeReviewCount = \Modules\Employee\Models\EmployeeRating::where(
                                                    'employee_id',
                                                    $employee->id,
                                                )->count();
                                            @endphp
                                            <div class="col">
                                                <a href="{{ route('expert-detail', $employee->id) }}"
                                                    class="text-decoration-none">
                                                    <div class="text-center branch-staff-card staff-card-clickable">
                                                        <div class="avatar-wrapper">
                                                            <img src="{{ $employee->getFirstMediaUrl('profile_image') ?: asset(default_user_avatar()) }}"
                                                                alt="{{ $employee->full_name ?? 'Staff Member' }}"
                                                                class="branch-staff-img">
                                                        </div>
                                                        <div class="staff-info">
                                                            <h5>{{ $employee->full_name ?? 'N/A' }}</h5>
                                                            <span
                                                                class="font-size-14">{{ $employee->designation ?? __('frontend.staff_member') }}</span>
                                                        </div>
                                                        <div class="staff-ratting-info">
                                                            <span class="badge bg-white text-secondary font-size-14">
                                                                <i class="ph-fill ph-star text-warning"></i>
                                                                {{ $employeeRating ? round($employeeRating, 1) : '0.0' }}
                                                                @if ($employeeReviewCount > 0)
                                                                    <small>({{ $employeeReviewCount }}
                                                                        {{ __('frontend.reviews') }})</small>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">{{ __('frontend.no_staff_members_available_for_this_branch') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="tab-pane p-0 fade" id="gallery-branch" role="tabpanel">
                            @if ($branch->gallerys->count() > 0)
                                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 gy-4"
                                    id="gallery-container">
                                    @foreach ($branch->gallerys as $gallery)
                                        <div class="col">
                                            <div class="gallery-item position-relative">
                                                <div class="gallery-shimmer"></div>
                                                <img src="{{ $gallery->getFirstMediaUrl('gallery_images') ?? ($gallery->full_url ?? asset('img/frontend/branch-image.jpg')) }}"
                                                    onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
                                                    alt="Branch Gallery" class="img-fluid rounded gallery-image"
                                                    style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                                                    data-bs-toggle="modal" data-bs-target="#galleryModal"
                                                    data-bs-src="{{ $gallery->getFirstMediaUrl('gallery_images') ?? ($gallery->full_url ?? asset('img/frontend/branch-image.jpg')) }}"
                                                    onload="hideGalleryShimmer(this)" onerror="hideGalleryShimmer(this)">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">{{ __('frontend.no_gallery_images_available_for_this_branch') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
                <div class="col-xl-3 col-lg-4 sticky">
                    <div class="branch-details-box rounded">
                        <div>
                            <img src="{{ $branch->media->pluck('original_url')->first() ?? asset('img/frontend/branch-image.jpg') }}"
                                onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'" alt="branch-detail"
                                class="w-100 branch-details-img rounded-top position-relative">
                            @php
                                // Calculate actual branch status based on business hours
                                $timezone = setting('default_time_zone') ?? (setting('time_zone') ?? 'UTC');
                                $today = \Carbon\Carbon::now($timezone)->format('l'); // Full day name (Monday, Tuesday, etc.)
                                $now = \Carbon\Carbon::now($timezone);

                                // Get today's business hours
$hours = \Modules\BussinessHour\Models\BussinessHour::where('branch_id', $branch->id)
    ->whereRaw('LOWER(day) = ?', [strtolower($today)])
    ->first();

$isOpen = false;

if ($hours && $hours->is_holiday != 1 && $hours->start_time && $hours->end_time) {
    // Get current time and business hours
    $currentTime = $now->format('H:i:s');
    $startTime = $hours->start_time;
    $endTime = $hours->end_time;

    // Check if current time is within business hours
    $isOpen = $currentTime >= $startTime && $currentTime <= $endTime;

    // Check breaks if branch is open
    if ($isOpen && !empty($hours->breaks)) {
        $breaks = is_array($hours->breaks)
            ? $hours->breaks
            : json_decode($hours->breaks, true);
        if (is_array($breaks)) {
            foreach ($breaks as $break) {
                if (!empty($break['start_break']) && !empty($break['end_break'])) {
                    if (
                        $currentTime >= $break['start_break'] &&
                        $currentTime <= $break['end_break']
                                                    ) {
                                                        $isOpen = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            @endphp
                            <div class="d-flex position-absolute gap-3 align-content-center branch-meta">
                                <span class="badge {{ $isOpen ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                    {{ $isOpen ? __('frontend.open') : __('frontend.closed') }}
                                </span>
                            </div>

                        </div>
                        <div class="branch-details-content">
                            @if ($branch->address)
                                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                                    <i class="ph ph-map-pin"></i>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($branch->address->address_line_1 . ($branch->address->city_data ? ', ' . $branch->address->city_data->name : '') . ($branch->address->state_data ? ', ' . $branch->address->state_data->name : '') . ($branch->address->country_data ? ', ' . $branch->address->country_data->name : '')) }}"
                                        target="_blank"
                                        class="font-size-14 heading-color text-decoration-none d-flex align-items-center gap-2 address-link"
                                        title="{{ __('frontend.view_on_google_maps') }}">
                                        <span>
                                            {{ $branch->address->address_line_1 ?? __('frontend.address_not_available') }}
                                            @if ($branch->address->city_data)
                                                , {{ $branch->address->city_data->name }}
                                            @endif
                                            @if ($branch->address->state_data)
                                                , {{ $branch->address->state_data->name }}
                                            @endif
                                            @if ($branch->address->country_data)
                                                , {{ $branch->address->country_data->name }}
                                            @endif
                                        </span>
                                        <i class="ph ph-arrow-square-out text-primary" style="font-size: 12px;"></i>
                                    </a>
                                </div>
                            @endif

                            @if ($branch->contact_number)
                                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                                    <i class="ph ph-phone"></i>
                                    <span class="font-size-14 heading-color">{{ $branch->contact_number }}</span>
                                </div>
                            @endif

                            @if ($branch->contact_email)
                                <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                                    <i class="ph ph-envelope-simple"></i>
                                    <span class="font-size-14 heading-color">{{ $branch->contact_email }}</span>
                                </div>
                            @endif

                            @if ($branch->bussinesshours->count() > 0)
                                <div class="mb-5">
                                    <h6 class="mb-3 d-flex align-items-center gap-2">
                                        <i class="ph ph-clock text-primary"></i>
                                        {{ __('frontend.business_hours') }}
                                    </h6>
                                    <div class="business-hours-simple">
                                        @foreach ($branch->bussinesshours as $hour)
                                            <div class="business-hour-row d-flex align-items-center py-2 border-bottom">
                                                <div class="d-flex align-items-center gap-3">
                                                    <i class="ph ph-clock text-primary"
                                                        style="font-size: 16px; width: 20px;"></i>
                                                    <span class="font-weight-600 text-dark"
                                                        style="font-size: 14px; min-width: 40px;">
                                                        {{ ucfirst(substr($hour->day, 0, 3)) }}
                                                    </span>
                                                </div>
                                                <div class="ms-3">
                                                    @if ($hour->is_holiday)
                                                        <span class="badge bg-danger text-white px-2 py-1"
                                                            style="font-size: 11px;">{{ __('frontend.closed') }}</span>
                                                    @else
                                                        <span class="text-dark font-weight-500" style="font-size: 13px;">
                                                            {{ date('h:i A', strtotime($hour->start_time)) }} -
                                                            {{ date('h:i A', strtotime($hour->end_time)) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if (!$hour->is_holiday && !empty($hour->breaks))
                                                @php
                                                    $breaks = is_array($hour->breaks)
                                                        ? $hour->breaks
                                                        : json_decode($hour->breaks, true);
                                                @endphp
                                                @if (is_array($breaks) && count($breaks) > 0)
                                                    <div class="break-row d-flex align-items-center py-1"
                                                        style="margin-left: 85px;">
                                                        <small class="text-muted d-flex align-items-center gap-1"
                                                            style="font-size: 12px;">
                                                            <i class="ph ph-coffee" style="font-size: 10px;"></i>
                                                            {{ __('frontend.break') }}:
                                                            @foreach ($breaks as $break)
                                                                @if (!empty($break['start_break']) && !empty($break['end_break']))
                                                                    {{ date('h:i A', strtotime($break['start_break'])) }} -
                                                                    {{ date('h:i A', strtotime($break['end_break'])) }}
                                                                    @if (!$loop->last)
                                                                        ,
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                        </small>
                                                    </div>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <button class="btn btn-secondary w-100"
                                onclick="shareBranch()">{{ __('frontend.share') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Booking Action Bar -->
    <div class="onclick-page-redirect bg-orange p-3 d-none" id="service-action-bar">
        <div class="container">
            <div class="d-flex justify-content-end align-items-center">
                @if (session()->has('selected_branch_id'))
                    <form id="service-selection-form" action="{{ route('choose-expert') }}" method="POST"
                        style="display:inline;">
                    @else
                        <form id="service-selection-form" action="{{ route('select-branch') }}" method="POST"
                            style="display:inline;">
                @endif
                @csrf
                <input type="hidden" id="selected-services" name="selected_services">
                <button type="submit" class="btn btn-secondary px-5" id="next-button"
                    disabled>{{ __('frontend.next') }}</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Gallery Modal -->
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalLabel">{{ __('frontend.branch_gallery') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Gallery Image" class="img-fluid rounded">
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const galleryModal = document.getElementById('galleryModal');
            const modalImage = document.getElementById('modalImage');

            if (galleryModal) {
                galleryModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const imageSrc = button.getAttribute('data-bs-src');
                    modalImage.src = imageSrc;
                });
            }

            // Service selection functionality (same as service.blade.php)
            const nextButton = document.getElementById('next-button');
            const hiddenInput = document.getElementById('selected-services');
            const serviceSection = document.getElementById('service-cards-container');
            const actionBar = document.getElementById('service-action-bar');

            // Function to update the selection state
            function updateSelection(event) {
                try {
                    const changedCheckbox = event ? event.target : null;
                    if (changedCheckbox) {
                        const card = changedCheckbox.closest('.service-card');
                        if (card) {
                            card.classList.toggle('selected', changedCheckbox.checked);
                        }
                    }

                    const checkboxes = document.querySelectorAll('.service-checkbox:checked');
                    let selectedIds = Array.from(checkboxes).map(cb => cb.value).filter(Boolean);

                    if (nextButton) {
                        nextButton.disabled = selectedIds.length === 0;
                    }
                    if (hiddenInput) {
                        hiddenInput.value = selectedIds.join(',');
                    }

                    if (actionBar) {
                        if (selectedIds.length > 0) {
                            nextButton.disabled = false;
                            actionBar.classList.remove('d-none');
                        } else {
                            nextButton.disabled = true;
                            actionBar.classList.add('d-none');
                        }
                    }
                } catch (error) {
                    console.error('Error in updateSelection:', error);
                }
            }

            // Add click event listeners to service cards
            document.addEventListener('click', function(event) {
                const serviceCard = event.target.closest('.service-card');
                if (serviceCard && !event.target.matches(
                        '.service-checkbox, .addon-checkbox, .service-card-addons-collapse, .service-card-addons-collapse *'
                    )) {
                    const checkbox = serviceCard.querySelector('.service-checkbox');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        updateSelection({
                            target: checkbox
                        });
                    }
                }
            });

            // Handle checkbox changes
            document.addEventListener('change', function(event) {
                if (event.target && event.target.matches('.service-checkbox')) {
                    updateSelection(event);
                }
            });

            // Initialize selection state
            updateSelection();

            // Gallery shimmer functionality
            window.hideGalleryShimmer = function(img) {
                const shimmer = img.parentElement.querySelector('.gallery-shimmer');
                if (shimmer) {
                    // Fade out shimmer
                    shimmer.style.opacity = '0';
                    setTimeout(() => {
                        shimmer.style.display = 'none';
                    }, 300);
                }
                // Fade in image
                img.style.opacity = '1';
            };

            // Show shimmer when gallery tab is activated
            document.addEventListener('shown.bs.tab', function(event) {
                if (event.target.getAttribute('data-bs-target') === '#gallery-branch') {
                    const galleryImages = document.querySelectorAll('.gallery-image');
                    galleryImages.forEach(function(img) {
                        if (!img.complete || img.naturalHeight === 0) {
                            const shimmer = img.parentElement.querySelector('.gallery-shimmer');
                            if (shimmer) {
                                shimmer.style.display = 'block';
                                shimmer.style.opacity = '1';
                            }
                            img.style.opacity = '0';
                        } else {
                            // Image already loaded
                            const shimmer = img.parentElement.querySelector('.gallery-shimmer');
                            if (shimmer) {
                                shimmer.style.display = 'none';
                            }
                            img.style.opacity = '1';
                        }
                    });
                }
            });

            // Initialize gallery shimmer on page load
            document.addEventListener('DOMContentLoaded', function() {
                const galleryImages = document.querySelectorAll('.gallery-image');
                galleryImages.forEach(function(img) {
                    if (!img.complete || img.naturalHeight === 0) {
                        const shimmer = img.parentElement.querySelector('.gallery-shimmer');
                        if (shimmer) {
                            shimmer.style.display = 'block';
                            shimmer.style.opacity = '1';
                        }
                        img.style.opacity = '0';
                    } else {
                        // Image already loaded
                        const shimmer = img.parentElement.querySelector('.gallery-shimmer');
                        if (shimmer) {
                            shimmer.style.display = 'none';
                        }
                        img.style.opacity = '1';
                    }
                });
            });

            // Track services state
            let servicesState = {
                allServicesLoaded: false,
                currentPage: 1,
                totalLoaded: 0,
                isViewingLess: false,
                originalOnclick: null
            };

            // Store original onclick handler when page loads
            document.addEventListener('DOMContentLoaded', function() {
                const loadMoreBtn = document.getElementById('load-more-services-btn');
                if (loadMoreBtn) {
                    servicesState.originalOnclick = loadMoreBtn.getAttribute('onclick');
                }
            });

            // Load More Services function
            window.loadMoreServices = function(url) {
                console.log('Loading more services from:', url);

                // Show loading state
                const loadMoreBtn = document.getElementById('load-more-services-btn');
                if (!loadMoreBtn) {
                    console.error('Load more services button not found');
                    return;
                }

                // Check if this is a "View Less" action
                if (loadMoreBtn.textContent.trim() === '{{ __('frontend.view_less') }}' ||
                    loadMoreBtn.textContent.trim() === '{{ __('frontend.load_less') }}' ||
                    servicesState.isViewingLess) {
                    // If we're viewing less and all services are loaded, show all services
                    if (servicesState.isViewingLess && servicesState.allServicesLoaded) {
                        showAllServices();
                        return;
                    }
                    // Otherwise, load less services
                    loadLessServices();
                    return;
                }

                const originalText = loadMoreBtn.textContent;
                loadMoreBtn.textContent = 'Loading...';
                loadMoreBtn.disabled = true;

                // Make AJAX request
                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        console.log('Services response received, length:', html.length);

                        // Create a temporary container to parse the response
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;

                        // Find the new services in the response
                        const newServicesContainer = tempDiv.querySelector('#service-cards-container');
                        if (newServicesContainer) {
                            console.log('Found services container in response');

                            // Get current services to avoid duplicates
                            const currentContainer = document.getElementById('service-cards-container');
                            if (!currentContainer) {
                                console.error('Current services container not found');
                                return;
                            }

                            const currentServiceIds = Array.from(currentContainer.querySelectorAll(
                                '.service-card')).map(card => {
                                const checkbox = card.querySelector('.service-checkbox');
                                return checkbox ? checkbox.value : null;
                            }).filter(Boolean);

                            console.log('Current service IDs:', currentServiceIds);

                            // Get new services and filter out duplicates
                            const newServices = Array.from(newServicesContainer.querySelectorAll(
                                '.service-card'));
                            const uniqueNewServices = newServices.filter(serviceCard => {
                                const checkbox = serviceCard.querySelector('.service-checkbox');
                                const serviceId = checkbox ? checkbox.value : null;
                                return serviceId && !currentServiceIds.includes(serviceId);
                            });

                            console.log('New services found:', newServices.length, 'Unique new services:',
                                uniqueNewServices.length);

                            // Append only unique new services
                            if (uniqueNewServices.length > 0) {
                                uniqueNewServices.forEach(serviceCard => {
                                    currentContainer.appendChild(serviceCard);
                                });
                                console.log('Added', uniqueNewServices.length, 'new services');
                            }

                            // Update state
                            servicesState.totalLoaded += uniqueNewServices.length;
                            servicesState.currentPage++;

                            // Check if there are more pages by looking for the load more button in the response
                            const newLoadMoreLink = tempDiv.querySelector('a[onclick*="loadMoreServices"]');
                            if (newLoadMoreLink && newLoadMoreLink.href && newLoadMoreLink.href !== url) {
                                // Update the load more button with new URL
                                loadMoreBtn.setAttribute('onclick', newLoadMoreLink.getAttribute(
                                'onclick'));
                                loadMoreBtn.href = newLoadMoreLink.href;
                                loadMoreBtn.textContent = '{{ __('frontend.load_more') }}';
                                servicesState.allServicesLoaded = false;
                                console.log('Updated load more button with new URL:', newLoadMoreLink.href);
                            } else {
                                // No more pages, change button to "View Less"
                                loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                                loadMoreBtn.setAttribute('onclick',
                                    'event.preventDefault(); loadMoreServices(); return false;');
                                loadMoreBtn.href = '#';
                                servicesState.allServicesLoaded = true;
                                console.log('No more pages, changed button to View Less');
                            }
                        } else {
                            // No services found, change button to "Load Less"
                            loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                            loadMoreBtn.setAttribute('onclick',
                                'event.preventDefault(); loadMoreServices(); return false;');
                            loadMoreBtn.href = '#';
                            console.log('No services container found, changed button to Load Less');
                        }

                        // Re-initialize service selection for new services
                        updateSelection();
                    })
                    .catch(error => {
                        console.error('Error loading more services:', error);

                        // Check if we have many services already loaded
                        const serviceContainer = document.getElementById('service-cards-container');
                        const allServiceCards = serviceContainer ? serviceContainer.querySelectorAll(
                            '.service-card') : [];

                        if (allServiceCards.length > 6) {
                            // If we have more than 6 services, change to "Load Less" instead of showing error
                            loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                            loadMoreBtn.setAttribute('onclick',
                                'event.preventDefault(); loadMoreServices(); return false;');
                            loadMoreBtn.href = '#';
                            console.log(
                                'Changed button to Load Less due to error (services already loaded)');
                        } else {
                            // Only show error if we don't have enough services
                            alert('Error loading more services. Please try again.');
                        }
                    })
                    .finally(() => {
                        // Reset button state only if we're not changing to "Load Less"
                        if (loadMoreBtn.textContent !== '{{ __('frontend.view_less') }}') {
                            loadMoreBtn.textContent = originalText;
                        }
                        loadMoreBtn.disabled = false;
                    });
            };

            // Load Less Services function
            window.loadLessServices = function() {
                const loadMoreBtn = document.getElementById('load-more-services-btn');
                const serviceContainer = document.getElementById('service-cards-container');

                if (!loadMoreBtn || !serviceContainer) {
                    console.error('Required elements not found');
                    return;
                }

                // Get all service cards
                const allServiceCards = serviceContainer.querySelectorAll('.service-card');
                const totalServices = allServiceCards.length;

                // Show only the first 6 services (or adjust as needed)
                const servicesToShow = 6;

                if (totalServices > servicesToShow) {
                    // Hide services beyond the first 6
                    allServiceCards.forEach((card, index) => {
                        if (index >= servicesToShow) {
                            card.style.display = 'none';
                        }
                    });

                    // Update state
                    servicesState.isViewingLess = true;

                    // After clicking "View Less", always show "Load More" (like reviews)
                    loadMoreBtn.textContent = '{{ __('frontend.load_more') }}';
                    loadMoreBtn.setAttribute('onclick',
                        'event.preventDefault(); loadMoreServices(); return false;');

                    // Ensure the button maintains the exact same CSS classes as original
                    loadMoreBtn.className = 'btn btn-secondary mt-2';
                    loadMoreBtn.href = '#';

                    // Ensure the button container maintains proper spacing
                    const buttonContainer = loadMoreBtn.closest('.d-flex');
                    if (buttonContainer) {
                        buttonContainer.className = 'd-flex align-items-center justify-content-center mt-2';
                    }

                    console.log(
                        `Hidden ${totalServices - servicesToShow} services, showing only ${servicesToShow}`);
                }
            };

            // Show All Services function
            window.showAllServices = function() {
                const loadMoreBtn = document.getElementById('load-more-services-btn');
                const serviceContainer = document.getElementById('service-cards-container');

                if (!loadMoreBtn || !serviceContainer) {
                    console.error('Required elements not found');
                    return;
                }

                // Show all service cards
                const allServiceCards = serviceContainer.querySelectorAll('.service-card');
                allServiceCards.forEach(card => {
                    card.style.display = '';
                });

                // Update state
                servicesState.isViewingLess = false;

                // Change button text
                loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                loadMoreBtn.setAttribute('onclick',
                'event.preventDefault(); loadMoreServices(); return false;');
                loadMoreBtn.href = '#';

                console.log('Showing all services');
            };

            // Share Branch functionality
            window.shareBranch = function() {
                const branchName = '{{ $branch->name }}';
                const branchUrl = window.location.href;
                const shareText = `Check out {{ $branch->name }} branch!`;

                // Check if Web Share API is supported
                if (navigator.share) {
                    navigator.share({
                        title: branchName,
                        text: shareText,
                        url: branchUrl
                    }).then(() => {
                        console.log('Branch shared successfully');
                    }).catch((error) => {
                        console.error('Error sharing branch:', error);
                        fallbackShare(branchName, branchUrl, shareText);
                    });
                } else {
                    // Fallback for browsers that don't support Web Share API
                    fallbackShare(branchName, branchUrl, shareText);
                }
            };

            // Fallback share function
            function fallbackShare(branchName, branchUrl, shareText) {
                // Create a modal or use a simple prompt
                const shareOptions = `
Share ${branchName}:
${branchUrl}

You can copy this link and share it on your preferred platform.
        `;

                // Show a modal with sharing options
                showShareModal(branchName, branchUrl, shareText);
            }

            // Show share modal with different sharing options
            function showShareModal(branchName, branchUrl, shareText) {
                // Create modal HTML
                const modalHtml = `
            <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="shareModalLabel">{{ __('frontend.share') }} {{ $branch->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary w-100" onclick="shareToWhatsApp('${branchUrl}', '${shareText}')">
                                        <i class="ph ph-whatsapp-logo me-2"></i>WhatsApp
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info w-100" onclick="shareToFacebook('${branchUrl}', '${shareText}')">
                                        <i class="ph ph-facebook-logo me-2"></i>Facebook
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info w-100" onclick="shareToTwitter('${branchUrl}', '${shareText}')">
                                        <i class="ph ph-x-logo me-2"></i>X
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-secondary w-100" onclick="copyToClipboard('${branchUrl}')">
                                        <i class="ph ph-copy me-2"></i>Copy Link
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="shareUrl" class="form-label">Or copy this link:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="shareUrl" value="${branchUrl}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('${branchUrl}')">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

                // Remove existing modal if any
                const existingModal = document.getElementById('shareModal');
                if (existingModal) {
                    existingModal.remove();
                }

                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Show modal
                const shareModal = new bootstrap.Modal(document.getElementById('shareModal'));
                shareModal.show();
            }

            // Share to WhatsApp
            window.shareToWhatsApp = function(url, text) {
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
                window.open(whatsappUrl, '_blank');
            };

            // Share to Facebook
            window.shareToFacebook = function(url, text) {
                const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                window.open(facebookUrl, '_blank', 'width=600,height=400');
            };

            // Share to Twitter
            window.shareToTwitter = function(url, text) {
                const twitterUrl =
                    `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
                window.open(twitterUrl, '_blank', 'width=600,height=400');
            };

            // Copy to clipboard
            window.copyToClipboard = function(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(() => {
                        showToast('Link copied to clipboard!', 'success');
                    }).catch(() => {
                        fallbackCopyToClipboard(text);
                    });
                } else {
                    fallbackCopyToClipboard(text);
                }
            };

            // Fallback copy to clipboard
            function fallbackCopyToClipboard(text) {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    document.execCommand('copy');
                    showToast('Link copied to clipboard!', 'success');
                } catch (err) {
                    showToast('Failed to copy link. Please copy manually.', 'error');
                }

                document.body.removeChild(textArea);
            }

            // Show toast notification
            function showToast(message, type = 'info') {
                // Create toast element
                const toastHtml = `
            <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

                // Add toast to body
                document.body.insertAdjacentHTML('beforeend', toastHtml);

                // Show toast
                const toastElement = document.querySelector('.toast:last-child');
                const toast = new bootstrap.Toast(toastElement);
                toast.show();

                // Remove toast after it's hidden
                toastElement.addEventListener('hidden.bs.toast', () => {
                    toastElement.remove();
                });
            }

            // Track reviews state
            let reviewsState = {
                allReviewsLoaded: false,
                currentPage: 1,
                totalLoaded: 0,
                isViewingLess: false
            };

            // Load More Reviews function
            window.loadMoreReviews = function(url) {
                console.log('Loading more reviews from:', url);

                // Show loading state
                const loadMoreBtn = document.getElementById('load-more-reviews-btn');
                if (!loadMoreBtn) {
                    console.error('Load more reviews button not found');
                    return;
                }

                // Check if this is a "Load Less" or "View Less" action
                if (loadMoreBtn.textContent.trim() === '{{ __('frontend.view_less') }}' ||
                    loadMoreBtn.textContent.trim() === '{{ __('frontend.view_less') }}' ||
                    reviewsState.isViewingLess) {
                    // If we're viewing less and all reviews are loaded, show all reviews
                    if (reviewsState.isViewingLess && reviewsState.allReviewsLoaded) {
                        showAllReviews();
                        return;
                    }
                    // Otherwise, load less reviews
                    loadLessReviews();
                    return;
                }

                const originalText = loadMoreBtn.textContent;
                loadMoreBtn.textContent = 'Loading...';
                loadMoreBtn.disabled = true;

                // Make AJAX request
                fetch(url, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.text();
                    })
                    .then(html => {
                        console.log('Reviews response received, length:', html.length);

                        // Create a temporary container to parse the response
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;

                        // Find the new reviews in the response
                        const newReviewsContainer = tempDiv.querySelector('#reviews-container');
                        console.log('Load more reviews response:', {
                            hasContainer: !!newReviewsContainer,
                            containerReviews: newReviewsContainer ? newReviewsContainer
                                .querySelectorAll('.review-card').length : 0
                        });

                        if (newReviewsContainer) {
                            console.log('Found reviews container in response');

                            // Get current reviews to avoid duplicates
                            const currentContainer = document.getElementById('reviews-container');
                            if (!currentContainer) {
                                console.error('Current reviews container not found');
                                return;
                            }

                            const currentReviewIds = Array.from(currentContainer.querySelectorAll(
                                '.review-card')).map(card => {
                                return card.getAttribute('data-review-id');
                            }).filter(Boolean);

                            console.log('Current review IDs:', currentReviewIds);

                            // Get new reviews and filter out duplicates
                            const newReviews = Array.from(newReviewsContainer.querySelectorAll(
                                '.review-card'));
                            const uniqueNewReviews = newReviews.filter(reviewCard => {
                                const reviewId = reviewCard.getAttribute('data-review-id');
                                return reviewId && !currentReviewIds.includes(reviewId);
                            });

                            console.log('Review filtering:', {
                                currentReviewIds: currentReviewIds,
                                newReviewsCount: newReviews.length,
                                uniqueNewReviewsCount: uniqueNewReviews.length,
                                newReviewIds: newReviews.map(card => card.getAttribute(
                                    'data-review-id'))
                            });

                            // Append only unique new reviews
                            if (uniqueNewReviews.length > 0) {
                                uniqueNewReviews.forEach(reviewCard => {
                                    // Create a wrapper div for the review
                                    const wrapper = document.createElement('div');
                                    wrapper.className = 'col-12';
                                    wrapper.appendChild(reviewCard);
                                    currentContainer.appendChild(wrapper);
                                });
                                console.log('Added', uniqueNewReviews.length, 'new reviews');

                                // Update state
                                reviewsState.totalLoaded += uniqueNewReviews.length;
                                reviewsState.currentPage++;
                            }

                            // Check if there are more pages by looking for the load more button in the response
                            const newLoadMoreLink = tempDiv.querySelector('a[onclick*="loadMoreReviews"]');
                            if (newLoadMoreLink && newLoadMoreLink.href && newLoadMoreLink.href !== url) {
                                // Update the load more button with new URL
                                loadMoreBtn.setAttribute('onclick', newLoadMoreLink.getAttribute(
                                'onclick'));
                                loadMoreBtn.href = newLoadMoreLink.href;
                                loadMoreBtn.textContent = '{{ __('frontend.load_more_reviews') }}';
                                reviewsState.allReviewsLoaded = false;
                                console.log('Updated load more button with new URL:', newLoadMoreLink.href);
                            } else {
                                // No more pages, change button to "Load Less"
                                loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                                loadMoreBtn.setAttribute('onclick',
                                    'event.preventDefault(); loadMoreReviews(); return false;');
                                loadMoreBtn.href = '#';
                                reviewsState.allReviewsLoaded = true;

                                // Ensure the button maintains the exact same CSS classes as original
                                loadMoreBtn.className = 'btn btn-secondary mt-4';

                                console.log('No more pages, changed button to Load Less');
                            }
                        } else {
                            // No reviews found, change button to "Load Less"
                            loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                            loadMoreBtn.setAttribute('onclick',
                                'event.preventDefault(); loadMoreReviews(); return false;');
                            loadMoreBtn.href = '#';
                            reviewsState.allReviewsLoaded = true;

                            // Ensure the button maintains the exact same CSS classes as original
                            loadMoreBtn.className = 'btn btn-secondary mt-4';

                            console.log('No reviews container found, changed button to Load Less');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading more reviews:', error);

                        // Check if we have many reviews already loaded
                        const reviewContainer = document.getElementById('reviews-container');
                        const allReviewCards = reviewContainer ? reviewContainer.querySelectorAll(
                            '.review-card') : [];

                        if (allReviewCards.length > 6) {
                            // If we have more than 6 reviews, change to "Load Less" instead of showing error
                            loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                            loadMoreBtn.setAttribute('onclick',
                                'event.preventDefault(); loadMoreReviews(); return false;');
                            loadMoreBtn.href = '#';
                            reviewsState.allReviewsLoaded = true;

                            // Ensure the button maintains the exact same CSS classes as original
                            loadMoreBtn.className = 'btn btn-secondary mt-4';

                            console.log(
                            'Changed button to Load Less due to error (reviews already loaded)');
                        } else {
                            // Only show error if we don't have enough reviews
                            alert('Error loading more reviews. Please try again.');
                        }
                    })
                    .finally(() => {
                        // Reset button state only if we're not changing to "Load Less"
                        if (loadMoreBtn.textContent !== '{{ __('frontend.view_less') }}') {
                            loadMoreBtn.textContent = originalText;
                        }
                        loadMoreBtn.disabled = false;
                    });
            };

            // Load Less Reviews function
            window.loadLessReviews = function() {
                const loadMoreBtn = document.getElementById('load-more-reviews-btn');
                const reviewContainer = document.getElementById('reviews-container');

                if (!loadMoreBtn || !reviewContainer) {
                    console.error('Required elements not found');
                    return;
                }

                // Get all review wrapper divs (col-12)
                const allReviewWrappers = reviewContainer.querySelectorAll('.col-12');
                const totalReviews = allReviewWrappers.length;

                // Show only the first 6 reviews (or adjust as needed)
                const reviewsToShow = 6;

                if (totalReviews > reviewsToShow) {
                    // Hide review wrappers beyond the first 6
                    allReviewWrappers.forEach((wrapper, index) => {
                        if (index >= reviewsToShow) {
                            wrapper.style.display = 'none';
                        }
                    });

                    // Update state
                    reviewsState.isViewingLess = true;

                    // After clicking "Load Less", always show "Load More Reviews"
                    loadMoreBtn.textContent = '{{ __('frontend.load_more_reviews') }}';
                    loadMoreBtn.setAttribute('onclick',
                        'event.preventDefault(); loadMoreReviews(); return false;');

                    // Ensure the button maintains the exact same CSS classes as original
                    loadMoreBtn.className = 'btn btn-secondary mt-2';
                    loadMoreBtn.href = '#';

                    // Ensure the button container maintains proper spacing
                    const buttonContainer = loadMoreBtn.closest('.d-flex');
                    if (buttonContainer) {
                        buttonContainer.className = 'd-flex align-items-center justify-content-center mt-2';
                    }

                    console.log(
                    `Hidden ${totalReviews - reviewsToShow} reviews, showing only ${reviewsToShow}`);
                }
            };

            // Show All Reviews function
            window.showAllReviews = function() {
                const loadMoreBtn = document.getElementById('load-more-reviews-btn');
                const reviewContainer = document.getElementById('reviews-container');

                if (!loadMoreBtn || !reviewContainer) {
                    console.error('Required elements not found');
                    return;
                }

                // Get all review wrapper divs (col-12)
                const allReviewWrappers = reviewContainer.querySelectorAll('.col-12');

                // Show all review wrappers
                allReviewWrappers.forEach((wrapper) => {
                    wrapper.style.display = 'block';
                });

                // Update state
                reviewsState.isViewingLess = false;

                // Change button to "View Less" since all reviews are now visible
                loadMoreBtn.textContent = '{{ __('frontend.view_less') }}';
                loadMoreBtn.setAttribute('onclick', 'event.preventDefault(); loadMoreReviews(); return false;');
                loadMoreBtn.href = '#';

                // Ensure the button maintains the exact same CSS classes as original
                loadMoreBtn.className = 'btn btn-secondary mt-4';

                console.log('Showing all reviews');
            };
        });
    </script>

    <style>
        /* Gallery Shimmer Animation */
        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .gallery-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 8px;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 2;
            transition: opacity 0.3s ease-in-out;
        }

        .gallery-image {
            transition: opacity 0.3s ease-in-out;
        }

        .gallery-item {
            min-height: 200px;
        }

        /* Enhanced shimmer for better visual effect */
        .gallery-shimmer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
            animation: shimmer-overlay 2s infinite;
        }

        @keyframes shimmer-overlay {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Responsive shimmer */
        @media (max-width: 768px) {
            .gallery-shimmer {
                height: 150px;
            }

            .gallery-item {
                min-height: 150px;
            }
        }

        /* Business Hours Simple Styling */
        .business-hours-simple {
            /* Removed max-height and overflow to eliminate scrollbar */
        }

        .business-hour-row {
            transition: background-color 0.2s ease;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        }

        .business-hour-row:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }

        .business-hour-row:last-child {
            border-bottom: none !important;
        }

        .break-row {
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 4px;
            margin-top: 2px;
        }

        /* Mobile responsiveness */
        @media (max-width: 576px) {
            .business-hour-row {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
                padding: 12px 0 !important;
            }

            .business-hour-row .text-end {
                text-align: left !important;
                width: 100%;
            }

            .break-row {
                margin-left: 0 !important;
                margin-top: 8px;
                padding: 8px 12px;
            }
        }

        /* Reviews Load More Button Spacing - Match Services Pattern */
    </style>

@endsection
