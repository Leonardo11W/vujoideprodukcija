@extends('frontend::layouts.master')

@section('title')
    {{ __('frontend.search') }}
@endsection

@section('content')

    <div class="search-box">
        <div class="gradient pink-gradient"></div>
        <div class="gradient blue-gradient"></div>
        <div class="container">
            <div class="row">
                <div class="col-lg-2 d-lg-block d-none"></div>
                <div class="col-lg-8 search-box-inner text-center">
                    <!-- <form action="{{ url('search') }}" method="GET">
                                            <div class="input-group custom-search-group position-relative mb-3">
                                                <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                                                <input type="text" id="search-query" name="query" class="form-control search-input"
                                                    placeholder="Search..." value="{{ request('query') }}">
                                                <span class="clear-icon" id="remove-search">
                                                    <div class="d-flex align-items-center gap-lg-4 gap-1">
                                                        <button type="submit" class="btn btn btn-secondary">Search</button>
                                                    </div>
                                                </span>
                                            </div>
                                        </form> -->
                    <div class="input-group custom-search-group position-relative mb-3">
                        <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="text" id="search-query" name="query" class="form-control search-input"
                            placeholder="{{ __('frontend.search') }}" value="{{ request('query') }}">
                        @if (request('query'))
                            <!-- <span class="position-absolute end-0 top-50 translate-middle-y me-5 cursor-pointer"
                                  id="clear-search" style="z-index: 10;">
                                <i class="ph ph-x-circle text-muted"></i>
                            </span> -->
                        @endif
                        <span class="clear-icon" id="remove-search">
                            <div class="d-flex align-items-center gap-lg-4 gap-1">
                                <button type="submit" class="btn btn-secondary">{{ __('frontend.search') }}</button>
                            </div>
                        </span>
                    </div>
                    @if ($categories_data->count() > 0)
                        <div class="d-flex align-items-center justify-content-center flex-wrap gap-lg-4 gap-2 mt-4">
                            <span class="heading-color font-size-14 fw-medium">{{ __('frontend.quick_links') }}</span>
                            <ul class="list-inline d-inline-flex align-items-center gap-lg-4 gap-2 flex-wrap mb-0">
                                @foreach ($categories_data as $category)
                                    <li><a href="{{ route('service', ['category' => $category->slug]) }}"
                                            class="text-decoration-underline font-size-14 text-body">{{ $category->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif  
                </div>
                <div class="col-lg-2 d-lg-block d-none"></div>
            </div>
        </div>
    </div>

    <div class="search-results-container">
        @if (empty(request('query')))
            <div class="section-spacing-inner-pages">
                <div class="container">
                    <div class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
                        <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image" class="img-fluid">
                        <div>
                            <h4 class="mb-3">{{ __('frontend.please_enter_a_search_term') }}</h4>
                            <p class="m-0">{{ __('frontend.try_something_new') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif(isset($query) && $query)
            @php
                $hasResults =
                    (isset($categories) && count($categories)) ||
                    (isset($services) && count($services)) ||
                    (isset($products) && count($products));
            @endphp
            @if ($hasResults)
                <div class="section-spacing-inner-pages">
                    <div class="container">

                        @if (isset($categories) && count($categories))
                            <h5 class="mt-4">{{ __('frontend.categories') }}</h5>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-6 g-4 mb-4" id="categories-section">
                                @foreach ($categories as $i => $category)
                                    <div class="col category-item" style="{{ $i > 5 ? 'display:none;' : '' }}">
                                        <x-category_card :category="$category" />
                                    </div>
                                @endforeach
                            </div>
                            @if (count($categories) > 6)
                                <div class="d-flex align-items-center justify-content-center mt-3">
                                    <button class="btn btn-secondary"
                                        id="loadMoreCategories">{{ __('frontend.load_more') }}</button>
                                </div>
                            @endif
                        @endif
                        @if (isset($services) && count($services))
                            <h5 class="mt-4">{{ __('frontend.services') }}</h5>
                            <div class="row row-cols-1 row-cols-md-3 g-4 mb-4" id="services-section">
                                @foreach ($services as $i => $service)
                                    <div class="col service-item" style="{{ $i > 5 ? 'display:none;' : '' }}">
                                        <x-search_service_card :service="$service" />
                                    </div>
                                @endforeach
                            </div>
                            @if (count($services) > 6)
                                <div class="d-flex align-items-center justify-content-center mt-3">
                                    <button class="btn btn-secondary"
                                        id="loadMoreServices">{{ __('frontend.load_more') }}</button>
                                </div>
                            @endif
                        @endif
                        @if (isset($products) && count($products))
                            <h5 class="mt-4">{{ __('frontend.products') }}</h5>
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mb-4" id="products-section">
                                @foreach ($products as $i => $product)
                                    <div class="col product-item" style="{{ $i > 3 ? 'display:none;' : '' }}">
                                        <x-product_card :product="$product" />
                                    </div>
                                @endforeach
                            </div>
                            @if (count($products) > 3)
                                <div class="d-flex align-items-center justify-content-center mt-3">
                                    <button class="btn btn-secondary"
                                        id="loadMoreProducts">{{ __('frontend.load_more') }}</button>
                                </div>
                            @endif
                        @endif
                        @if (isset($experts) && count($experts))
                            <h5 class="mt-4">{{ __('frontend.experts') }}</h5>
                            <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 gy-4" id="experts-section">
                                @foreach ($experts as $i => $expert)
                                    <div class="col expert-item" style="{{ $i > 6 ? 'display:none;' : '' }}">
                                        <div class="export-card text-center">
                                            <div class="export-image position-relative">
                                                <img src="{{ $expert->profile_image }}" alt="{{ $expert->full_name }}"
                                                    class="img-fluid rounded-circle avatar-200 object-fit-cover">
                                                <div
                                                    class="rating-badge d-flex align-items-center justify-content-center gap-3">
                                                    <i class="ph-fill ph-star text-warning"></i>
                                                    <span class="fw-semibold heading-color font-size-14">
                                                        {{ $expert->rating->avg('rating') ? number_format($expert->rating->avg('rating'), 1) : '4.2' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="export-info mt-3">
                                                <h5 class="mb-1">
                                                    <a href="{{ route('expert-detail', $expert->id) }}"
                                                        class="text-dark">{{ $expert->full_name }}</a>
                                                </h5>
                                                <p class="font-size-14 mb-0">
                                                    {{ optional($expert->profile)->expert ?? 'Makeup specialist' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if (count($experts) > 4)
                                <div class="d-flex align-items-center justify-content-center mt-3">
                                    <button class="btn btn-secondary"
                                        id="loadMoreExperts">{{ __('frontend.load_more') }}</button>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                <div class="section-spacing-inner-pages">
                    <div class="container">
                        <div
                            class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
                            <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image"
                                class="img-fluid">
                            <div>
                                <h4 class="mb-3">{{ __('frontend.sorry_we_couldnt_find_your_search') }}</h4>
                                <p class="m-0">{{ __('frontend.try_something_new') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

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


    @push('scripts')
        <script>
            $(document).ready(function() {
                let selectedServices = new Set();

                // Handle service selection
                $(document).on('change', '.service-checkbox', function() {
                    const serviceId = $(this).val();

                    if ($(this).is(':checked')) {
                        selectedServices.add(serviceId);
                    } else {
                        selectedServices.delete(serviceId);
                    }

                    // Update the hidden input with selected service IDs
                    $('#selected-services').val(Array.from(selectedServices).join(','));

                    // Toggle action bar visibility
                    if (selectedServices.size > 0) {
                        $('#service-action-bar').removeClass('d-none');
                        $('#next-button').prop('disabled', false);
                    } else {
                        $('#service-action-bar').addClass('d-none');
                        $('#next-button').prop('disabled', true);
                    }
                });

            });
            // Debug: Check if jQuery is loaded


            // Debug: Check if search container exists

            $(document).ready(function() {
                let timer;
                const searchInput = $('#search-query');
                const searchForm = $('form[action="{{ url('search') }}"]');
                const searchResults = $('.search-results-container');

                // Debounce function to prevent too many requests
                function debounce(func, delay) {
                    clearTimeout(timer);
                    timer = setTimeout(func, delay);
                }

                // Handle search on keyup
                searchInput.on('keyup', function() {
                    const query = $(this).val().trim();

                    // if (query.length < 2) {
                    //     searchResults.html(`
            //         <div class="container search-not-found">
            //             <div class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
            //                 <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image" class="img-fluid">
            //                 <div>
            //                     <h4 class="mb-3">Please enter at least 2 characters to search.</h4>
            //                 </div>
            //             </div>
            //         </div>
            //     `);
                    //     return;
                    // }

                    debounce(function() {
                        performSearch(query);
                    }, 500);
                });

                // Perform AJAX search
                function performSearch(query) {
                    const searchUrl = '{{ route('search') }}';

                    // Show loading indicator
                    const loadingHtml =
                        '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    $('.search-results-container').html(loadingHtml);

                    // Make the AJAX request
                    $.ajax({
                        url: searchUrl,
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json, text/javascript, */*; q=0.01'
                        },
                        data: {
                            query: query
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            // Show loading indicator
                            searchResults.html(
                                '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
                            );
                        },
                        success: function(response) {

                            // Debug: Check response structure
                            if (response && typeof response === 'object') {

                                if (response.html) {

                                    $('.search-results-container').html(response.html);
                                } else {

                                    $('.search-results-container').html(
                                        '<div class="alert alert-warning">{{ __('frontend.no_results_found') }}</div>'
                                    );
                                }
                            } else {

                                $('.search-results-container').html(
                                    '<div class="alert alert-danger">{{ __('frontend.error_invalid_response_format') }}</div>'
                                );
                            }

                            // Initialize any load more buttons
                            try {
                                initializeLoadMoreButtons();
                            } catch (e) {
                                console.error('Error initializing load more buttons:', e);
                            }

                            // Update browser URL without reloading
                            try {
                                const url = new URL(window.location);
                                url.searchParams.set('query', query);
                                window.history.pushState({}, '', url);
                            } catch (e) {
                                console.error('Error updating URL:', e);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText,
                                statusCode: xhr.status
                            });

                            let errorMsg = 'Error loading search results. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            } else if (xhr.statusText) {
                                errorMsg = `Error: ${xhr.statusText}`;
                            }

                            $('.search-results-container').html(`
                                <div class="alert alert-danger">
                                    <strong>Error:</strong> ${errorMsg}
                                    ${xhr.status ? `<br><small>Status: ${xhr.status}</small>` : ''}
                                </div>
                            `);
                        }
                    });
                }

                // Handle form submission
                searchForm.on('submit', function(e) {
                    const query = searchInput.val().trim();
                    // if (query.length < 2) {
                    //     e.preventDefault();
                    //     searchResults.html(`
            //         <div class="container search-not-found">
            //             <div class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
            //                 <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image" class="img-fluid">
            //                 <div>
            //                     <h4 class="mb-3">Please enter at least 2 characters to search.</h4>
            //                 </div>
            //             </div>
            //         </div>
            //     `);
                    //     return false;
                    // }
                    return true;
                });

                // Initialize load more buttons
                function initializeLoadMoreButtons() {
                    // Load more categories
                    $('#loadMoreCategories').off('click').on('click', function() {
                        $('.category-item:hidden').slice(0, 6).show();
                        if ($('.category-item:hidden').length === 0) {
                            $('#loadMoreCategories').hide();
                        }
                    });

                    // Load more services
                    $('#loadMoreServices').off('click').on('click', function() {
                        $('.service-item:hidden').slice(0, 4).show();
                        if ($('.service-item:hidden').length === 0) {
                            $('#loadMoreServices').hide();
                        }
                    });

                    // // Load more products
                    // $('#loadMoreProducts').off('click').on('click', function() {
                    //     alert('load more products');
                    //     $('.product-item:hidden').slice(0, 3).show();
                    //     if ($('.product-item:hidden').length === 0) {
                    //         $('#loadMoreProducts').hide();
                    //     }
                    // });

                    $('#loadMoreProducts').off('click').on('click', function() {
                        const $hiddenProducts = $('.product-item:hidden').slice(0, 4);
                        $hiddenProducts.fadeIn(300, function() {
                            $(this).find('img').each(function() {
                                const $img = $(this);
                                const dataSrc = $img.attr('data-src');
                                if (dataSrc) {
                                    $img.attr('src', dataSrc).removeAttr('data-src');
                                }
                                $img[0].src = $img[0].src;
                            });
                            $(this).find('.slick-slider').slick('setPosition');
                        });
                        if ($('.product-item:hidden').length === 0) {
                            $('#loadMoreProducts').hide();
                        }
                    });
                    // Load more experts
                    $('#loadMoreExperts').off('click').on('click', function() {
                        $('.expert-item:hidden').slice(0, 6).show();
                        if ($('.expert-item:hidden').length === 0) {
                            $('#loadMoreExperts').hide();
                        }
                    });
                }

                // Initialize load more buttons on page load
                initializeLoadMoreButtons();
            });
        </script>
    @endpush

@endsection
