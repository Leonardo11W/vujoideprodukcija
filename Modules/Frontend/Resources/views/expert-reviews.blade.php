@extends('frontend::layouts.master')
@section('title') {{ $expert->full_name }} - {{__("frontend.all_reviews")}} @endsection

@section('content')

<x-breadcrumb title="{{ __('frontend.expert_reviews') }}" />

<div class="export-section-spacing section-spacing-bottom">
    <div class="container">
        <!-- Expert Summary Header -->
        <div class="expert-reviews-header">
            <div class="row align-items-center gy-3">
                <div class="col-md-8">
                    <h4 class="mb-2">{{ $expert->full_name }} - {{__("frontend.all_reviews")}}</h4>
                    <div class="d-flex align-items-center gap-lg-3 gap-2 flex-md-nowrap flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <i class="ph-solid ph-star text-warning"></i>
                            <h6 class="m-0">{{ $averageRating > 0 ? $averageRating : 'N/A' }} {{__("frontend.average_rating")}}</h6>
                        </div>
                        <span>•</span>
                        <span>{{ $totalRatings }} {{__("frontend.total_reviews")}}</span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('expert-detail', $expert->id) }}" class="btn btn-primary">
                        {{__("frontend.back_to_expert_profile")}}
                    </a>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <div class="experts-review-box">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-md-nowrap flex-wrap mb-4">
                <h5 class="m-0 flex-shrink-0">{{__("frontend.all_reviews")}} (<span id="reviewCount">{{ $totalRatings }}</span>)</h5>
                <div class="review-controls d-flex align-items-center gap-2 flex-sm-nowrap flex-wrap">
                    <select id="ratingFilter" class="form-select select2">
                        <option value="">{{__("frontend.all_ratings")}}</option>
                        <option value="5">5 {{__("frontend.stars")}}</option>
                        <option value="4">4 {{__("frontend.stars")}}</option>
                        <option value="3">3 {{__("frontend.stars")}}</option>
                        <option value="2">2 {{__("frontend.stars")}}</option>
                        <option value="1">1 {{__("frontend.stars")}}</option>
                    </select>
                    <select id="sortFilter" class="form-select select2 form-select-sm">
                        <option value="newest">{{__("frontend.newest")}}</option>
                        <option value="highest">{{__("frontend.highest")}}</option>
                        <option value="lowest">{{__("frontend.lowest")}}</option>
                    </select>
                    <input type="text" id="searchReviews" class="form-control" placeholder="{{__('frontend.search_reviews')}}">
                </div>
            </div>

            <div id="reviewsContainer">
                <!-- Reviews will be loaded here -->
            </div>

            <!-- DataTable-style Pagination Info and Controls -->
            <div class="pagination-wrapper" id="paginationWrapper" style="display: none;">
                <div class="entries-info">
                    <span id="entriesInfo">Showing 0 to 0 of 0 entries</span>
                </div>
                <nav aria-label="Reviews pagination">
                    <ul class="pagination" id="reviewsPagination">
                        <!-- Pagination will be generated here -->
                    </ul>
                </nav>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-body">{{__("frontend.loading_reviews")}}</p>
            </div>

            <!-- No Reviews State -->
            <div id="noReviewsState" class="text-center py-5" style="display: none;">
                <i class="ph ph-chat-circle h1 text-body"></i>
                <p class="mt-2 text-body">{{__("frontend.no_reviews_found")}}</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentRating = '';
    let currentSearch = '';
    let currentSort = 'newest';
    const itemsPerPage = 10;
    let allReviews = [];
    let filteredReviews = [];

    // Load all reviews initially
    loadReviews();

    // Filter by rating
    $('#ratingFilter').on('change', function() {
        currentRating = $(this).val();
        currentPage = 1;
        filterAndDisplayReviews();
    });

    // Sort filter
    $('#sortFilter').on('change', function() {
        currentSort = $(this).val();
        currentPage = 1;
        filterAndDisplayReviews();
    });

    // Search functionality with debounce
    let searchTimeout;
    $('#searchReviews').on('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = $(this).val();
        searchTimeout = setTimeout(function() {
            currentSearch = searchValue.toLowerCase().trim();
            currentPage = 1;
            filterAndDisplayReviews();
        }, 300);
    });

    function loadReviews() {
        showLoading();

        $.ajax({
            url: '{{ route("expert.reviews.data", $expert->id) }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.data) {
                    allReviews = response.data;
                    filterAndDisplayReviews();
                } else {
                    console.error('Invalid response format');
                    showNoReviews();
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load reviews:', error);
                showNoReviews();
            }
        });
    }

    function filterAndDisplayReviews() {
        filteredReviews = allReviews.slice(); // Create a copy

        // Filter by rating
        if (currentRating && currentRating !== '') {
            filteredReviews = filteredReviews.filter(review => {
                return parseInt(review.rating) === parseInt(currentRating);
            });
        }

        // Filter by search
        if (currentSearch && currentSearch !== '') {
            filteredReviews = filteredReviews.filter(review => {
                const userName = (review.user_name || '').toLowerCase();
                const reviewMsg = (review.review_msg || '').toLowerCase();
                return userName.includes(currentSearch) || reviewMsg.includes(currentSearch);
            });
        }

        // Sort by selected option
        if (currentSort === 'newest') {
            filteredReviews.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        } else if (currentSort === 'highest') {
            filteredReviews.sort((a, b) => parseFloat(b.rating) - parseFloat(a.rating));
        } else if (currentSort === 'lowest') {
            filteredReviews.sort((a, b) => parseFloat(a.rating) - parseFloat(b.rating));
        }

        // Update review count
        $('#reviewCount').text(filteredReviews.length);

        displayReviews(filteredReviews);
        generatePagination(filteredReviews);
    }

    function displayReviews(reviews) {
        const container = $('#reviewsContainer');

        if (reviews.length === 0) {
            showNoReviews();
            return;
        }

        hideStates();

        // Calculate pagination
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedReviews = reviews.slice(startIndex, endIndex);

        let html = '<ul class="list-unstyled mb-0">';

        paginatedReviews.forEach(function(review) {
            // Fallback for missing data
            const userName = review.user_name || '{{__("frontend.anonymous")}}';
            const userImage = review.user_image || '{{ asset("img/frontend/export-image.jpg") }}';
            const stars = review.stars || '';
            const reviewMsg = review.review_msg || '{{__("frontend.no_review_message_provided")}}';
            const createdAt = review.created_at || '';

            html += `
                <li class="expert-review-card">
                    <div class="d-flex column-gap-4 row-gap-2 flex-sm-row flex-column mb-4">
                        <!-- Profile Image Column -->
                        <div class="avatar-wrapper">
                            <img src="${userImage}" alt="review img" class="expert-review-img rounded-pill"
                                 onerror="this.src='{{ asset("img/frontend/export-image.jpg") }}'">
                        </div>
                        <!-- Info Column -->
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-1 flex-wrap">
                                <div>
                                    <div>
                                        <h5 class="mb-3">${userName}</h5>
                                        <div class="d-flex align-items-center gap-1">
                                            ${stars}
                                        </div>
                                    </div>
                                </div>
                                <span class="fw-medium font-size-14">${createdAt}</span>
                            </div>
                        </div>
                    </div>
                    <span>${reviewMsg}</span>
                </li>
            `;
        });

        html += '</ul>';
        container.html(html);
    }

    function generatePagination(reviews) {
        const totalPages = Math.ceil(reviews.length / itemsPerPage);
        const pagination = $('#reviewsPagination');
        const paginationWrapper = $('#paginationWrapper');
        const entriesInfo = $('#entriesInfo');

        if (totalPages <= 1) {
            paginationWrapper.hide();
            return;
        }

        paginationWrapper.show();

        // Update entries info (DataTable style)
        const startIndex = (currentPage - 1) * itemsPerPage + 1;
        const endIndex = Math.min(currentPage * itemsPerPage, reviews.length);
        entriesInfo.text(`Showing ${startIndex} to ${endIndex} of ${reviews.length} entries`);

        let html = '';

        // Previous button
        if (currentPage > 1) {
            html += `<li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
                     </li>`;
        } else {
            html += `<li class="page-item disabled">
                        <span class="page-link">Previous</span>
                     </li>`;
        }

        // Show only a few page numbers around current page
        const showPages = 5; // Maximum pages to show
        let startPage = Math.max(1, currentPage - Math.floor(showPages / 2));
        let endPage = Math.min(totalPages, startPage + showPages - 1);

        // Adjust start if we're near the end
        if (endPage - startPage + 1 < showPages) {
            startPage = Math.max(1, endPage - showPages + 1);
        }

        // Generate page numbers
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                html += `<li class="page-item active">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                         </li>`;
            } else {
                html += `<li class="page-item">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                         </li>`;
            }
        }

        // Next button
        if (currentPage < totalPages) {
            html += `<li class="page-item">
                        <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
                     </li>`;
        } else {
            html += `<li class="page-item disabled">
                        <span class="page-link">Next</span>
                     </li>`;
        }

        pagination.html(html);

        // Pagination click events
        pagination.find('a.page-link').on('click', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (page && page !== currentPage && page >= 1 && page <= totalPages) {
                currentPage = page;
                filterAndDisplayReviews();

                // Smooth scroll to reviews section
                $('html, body').animate({
                    scrollTop: $('.experts-review-box').offset().top - 100
                }, 500);
            }
        });
    }

    function showLoading() {
        $('#reviewsContainer').hide();
        $('#noReviewsState').hide();
        $('#paginationWrapper').hide();
        $('#loadingState').show();
    }

    function showNoReviews() {
        $('#reviewsContainer').hide();
        $('#loadingState').hide();
        $('#paginationWrapper').hide();
        $('#noReviewsState').show();
    }

    function hideStates() {
        $('#reviewsContainer').show();
        $('#loadingState').hide();
        $('#noReviewsState').hide();
        // paginationWrapper visibility is handled in generatePagination
    }
});
</script>
@endpush
