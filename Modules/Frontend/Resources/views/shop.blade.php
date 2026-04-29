@extends('frontend::layouts.master')
@section('title')
    {{ __('messages.shop') }}
@endsection

@section('content')
    <x-breadcrumb title="{{ __('frontend.shop') }}" />
    <div class="shop-section section-spacing-inner-pages vujo-page-inner vujo-shop-page">
        <div class="container">
            <div class="section-title d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="">
                    <span
                        class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.our_shop') }}</span>
                    <h4 class="title mb-0">{{ __('frontend.our_amazing_products') }}</h4>
                </div>
                <div class="">
                    <div class="d-flex flex-sm-nowrap flex-wrap column-gap-3 row-gap-2">
                        <div class="input-group mb-0">
                            <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                            <input type="search" id="searchInput" class="form-control p-2"
                                placeholder="{{ __('frontend.eg._Dryer_Nail_Polish_Mosturizer') }}">
                        </div>
                        <div class="flex-shrink-0">
                            <div class="position-relative">
                                <select id="productFilter" class="form-select select2">
                                    <option value="">{{ __('frontend.short_by') }}</option>
                                    <option value="newest">{{ __('frontend.newest') }}</option>
                                    <option value="trending">{{ __('frontend.best_selling') }}</option>
                                </select>
                                <span id="filterCloseIcon"
                                    class="position-absolute top-50 end-0 translate-middle-y pe-5 d-none"
                                    style="cursor: pointer; z-index: 10;">
                                    <i class="ph ph-x text-primary fs-5"></i>
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button id="resetProductFilters" class="btn btn-secondary">{{ __('frontend.reset') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row gy-4">
                <div class="col-md-4 col-lg-3">
                    <div class="shop-sidebar">
                        <div class="widget widget-category-filter">
                            <div class="sidebar-collapse-btn d-flex align-items-center justify-content-between gap-3">
                                <h5 class="mb-0">
                                    {{ __('frontend.categories') }}
                                </h5>
                                <span class="arrow-icon" data-bs-toggle="collapse" data-bs-target="#collapseCategory"
                                    aria-expanded="true" aria-controls="collapseCategory"></span>
                            </div>
                            <div class="collapse collapse-data show" id="collapseCategory">
                                <ul class="list-unstyled m-0">
                                    @foreach ($mainCategories as $mainCategory)
                                        <li>
                                            @if ($mainCategory->children->count())
                                                <div class="d-flex align-items-center justify-content-between gap-3 widget-category-filter-collpase"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#collapseCategory{{ $mainCategory->id }}"
                                                    aria-expanded="false"
                                                    aria-controls="collapseCategory{{ $mainCategory->id }}">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            value="{{ $mainCategory->id }}"
                                                            id="category{{ $mainCategory->id }}">
                                                        <div class="d-flex">
                                                            <label class="form-check-label"
                                                                for="category{{ $mainCategory->id }}">
                                                                {{ $mainCategory->name }}

                                                                [{{ $mainCategory->product_mappings_count }}]
                                                            </label>


                                                        </div>
                                                    </div>
                                                    <span class="arrow-icon"><i class="ph ph-caret-right"></i></span>
                                                </div>
                                                <div class="collapse" id="collapseCategory{{ $mainCategory->id }}">
                                                    <ul class="list-unstyled m-0">
                                                        @foreach ($mainCategory->children as $childCategory)
                                                            <li>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        value="{{ $childCategory->id }}"
                                                                        id="category{{ $childCategory->id }}">
                                                                    <label class="form-check-label"
                                                                        for="category{{ $childCategory->id }}">
                                                                        {{ $childCategory->name }}
                                                                    </label>
                                                                </div>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @else
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        value="{{ $mainCategory->id }}"
                                                        id="category{{ $mainCategory->id }}">
                                                    <label class="form-check-label" for="category{{ $mainCategory->id }}">
                                                        {{ $mainCategory->name }}
                                                        [{{ $mainCategory->product_mappings_count }}]
                                                    </label>
                                                </div>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                        </div>
                        <div class="widget widget-price-filter">
                            <div class="sidebar-collapse-btn d-flex align-items-center justify-content-between gap-3 mb-4">
                                <h5 class="mb-0">{{ __('frontend.filter_by_price') }}</h5>

                            </div>
                            <div class="collapse collapse-data show" id="collapseRange">
                                <div class="price-range-wrapper">
                                    <!-- Range Slider -->
                                    <div class="range-slider mb-2">
                                        <div class="range-track"></div>
                                        <div class="range-fill" id="range-fill"></div>
                                        <input type="range" id="min-range" min="{{ floor($priceStats->min_price) }}"
                                            max="{{ ceil($priceStats->max_price) }}"
                                            value="{{ floor($priceStats->min_price) }}" step="1"
                                            class="range-input min">
                                        <input type="range" id="max-range" min="{{ floor($priceStats->min_price) }}"
                                            max="{{ ceil($priceStats->max_price) }}"
                                            value="{{ ceil($priceStats->max_price) }}" step="1"
                                            class="range-input max">
                                    </div>

                                    <!-- Range Display -->
                                    <div class="range-display mb-4">
                                        <span class="range-label">{{ __('frontend.range') }}</span>
                                        <span id="rangeValue" class="range-value">${{ floor($priceStats->min_price) }} -
                                            ${{ ceil($priceStats->max_price) }}</span>
                                    </div>

                                    <!-- Price Brackets -->
                                    <div class="price-brackets">
                                        @foreach ($priceBrackets as $bracket)
                                            <div class="price-badge" data-min="{{ $bracket['lower'] }}"
                                                data-max="{{ $bracket['upper'] }}">
                                                @if ($loop->first)
                                                    Under {{ \Currency::format($bracket['upper']) }}
                                                @elseif($loop->last)
                                                    {{ \Currency::format($bracket['lower']) }} & Above
                                                @else
                                                    {{ \Currency::format($bracket['lower']) }} -
                                                    {{ \Currency::format($bracket['upper']) }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="widget widget-rating-filter">
                            <div class="sidebar-collapse-btn d-flex align-items-center justify-content-between gap-3">
                                <h5 class="mb-0">
                                    {{ __('frontend.filter_by_rating') }}
                                </h5>

                            </div>
                            <div class="collapse collapse-data show" id="collapseRatting">
                                <ul class="list-unstyled m-0">
                                    @php
                                        $ratingRanges = [
                                            ['min' => 4, 'max' => 5.0001, 'label' => '4.0 and above'],
                                            ['min' => 2, 'max' => 4, 'label' => '2.0 - 4.0'],
                                            ['min' => 0, 'max' => 2, 'label' => '0.0 - 2.0'],
                                        ];

                                        // Get actual counts from database for each range
                                        $productCounts = [];
                                        foreach ($ratingRanges as $range) {
                                            $count = DB::table('product_review')
                                                ->select('product_id', DB::raw('AVG(rating) as avg_rating'))
                                                ->groupBy('product_id')
                                                ->havingRaw('AVG(rating) >= ?', [$range['min']])
                                                ->havingRaw('AVG(rating) < ?', [$range['max']])
                                                ->count();
                                            $productCounts[] = $count;
                                        }
                                    @endphp

                                    @foreach ($ratingRanges as $index => $range)
                                        <li>
                                            <div
                                                class="form-check d-flex justify-content-between align-items-center gap-3">
                                                <div class="">
                                                    <input class="form-check-input rating-filter" type="checkbox"
                                                        name="rating_range"
                                                        value="{{ $range['min'] }}-{{ $range['max'] }}"
                                                        data-min="{{ $range['min'] }}" data-max="{{ $range['max'] }}"
                                                        data-count="{{ $productCounts[$index] }}">
                                                    <label class="form-check-label">
                                                        <i class="ph-fill ph-star align-middle text-warning"></i>
                                                        {{ $range['label'] }}
                                                    </label>
                                                </div>
                                                <span>{{ $productCounts[$index] }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="widget widget-discount-filter">
                            <div class="sidebar-collapse-btn d-flex align-items-center justify-content-between gap-3">
                                <h5 class="mb-0">{{ __('frontend.discount') }}</h5>

                            </div>
                            <div class="collapse collapse-data show" id="collapsediscount">
                                <ul class="list-unstyled m-0">
                                    @php
                                        $discountRanges = [
                                            ['min' => 0, 'max' => 20, 'label' => '0-20% off'],
                                            ['min' => 20, 'max' => 40, 'label' => '20-40% off'],
                                            ['min' => 40, 'max' => 60, 'label' => '40-60% off'],
                                            ['min' => 60, 'max' => 100, 'label' => '60% and above'],
                                        ];
                                        $discountCounts = [];
                                        foreach ($discountRanges as $range) {
                                            $now = time();
                                            $query = \Modules\Product\Models\Product::where('status', 1)
                                                ->where(function($q) use ($now) {
                                                    $q->whereNull('discount_start_date')
                                                      ->orWhere('discount_start_date', '<=', $now);
                                                })->where(function($q) use ($now) {
                                                    $q->whereNull('discount_end_date')
                                                      ->orWhere('discount_end_date', '>=', $now);
                                                })
                                                ->where('discount_type', 'percent')
                                                ->where('discount_value', '>', 0); // Only products with actual discounts

                                            if (is_array($range)) {
                                                $min = $range['min'] ?? null;
                                                $max = $range['max'] ?? null;
                                            } else {
                                                // If it's a string like "0-20"
        [$min, $max] = explode('-', $range);
        $min = (float) $min;
        $max = (float) $max;
    }

    if ($max < 100) {
        // For ranges like 0-20, 20-40, 40-60
        $query
            ->where('discount_value', '>=', $min)
            ->where('discount_value', '<', $max);
    } else {
        // For 60% and above
        $query->where('discount_value', '>=', $min);
                                            }
                                            $discountCounts[] = $query->count();
                                        }
                                    @endphp
                                    @foreach ($discountRanges as $i => $range)
                                        <li>
                                            <div
                                                class="form-check d-flex justify-content-between align-items-center gap-3">
                                                <div>
                                                    <input class="form-check-input discount-filter" type="checkbox"
                                                        name="discount_range"
                                                        value="{{ $range['min'] }}-{{ $range['max'] }}"
                                                        data-min="{{ $range['min'] }}" data-max="{{ $range['max'] }}"
                                                        data-count="{{ $discountCounts[$i] }}">
                                                    <label class="form-check-label">{{ $range['label'] }}</label>
                                                </div>
                                                <span>{{ $discountCounts[$i] }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">

                    <div id="productCardContainer"></div>

                    <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader">
                        @for ($i = 0; $i < 9; $i++)
                            @include('frontend::components.card.shimmer_shop_card')
                        @endfor
                    </div>

                    <table id="product-cards-table" class="table" style="display:none;">
                        <thead>
                            <tr>
                                <th>Card</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                    </table>


                </div>
            </div>
        </div>
    </div>

    {{-- <button onclick="$('#loginModal').modal('show')">Test Login Modal</button> --}}
    @include('components.login_modal')
    <!-- Branch Selection Modal -->
    <div class="modal fade" id="selectBranchModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="selectBranchModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Select Branch</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 gy-4">
                        @foreach ($branches ?? [] as $branch)
                            <div class="col">
                                <div class="branch-card rounded position-relative overflow-hidden{{ $loop->first ? ' selected' : '' }}"
                                    data-branch-id="{{ $branch->id }}">
                                    <div class="branch-image position-relative">
                                        <span
                                            class="badge bg-success text-white font-size-14 text-uppercase position-absolute top-0 end-0">{{ checkBranchStatus($branch) }}</span>
                                        <img src="{{ $branch->media->pluck('original_url')->first() }}"
                                            onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
                                            class="card-img-top" alt="{{ $branch->name }}">
                                    </div>
                                    <div class="branch-info-box">
                                        <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                            <h5 class="mb-0 fw-medium line-count-1"> <a
                                                    href="{{ route('branch-detail', $branch->id) }}">{{ $branch->name }}
                                                </a></h5>
                                            <span
                                                class="badge bg-purple text-body border rounded-pill text-uppercase">{{ $branch->type }}</span>
                                        </div>
                                        <span class="d-flex gap-2">
                                            <i class="ph ph-map-pin align-middle"></i>
                                            <span
                                                class="font-size-14">{{ $branch->address->address_line_1 . ' ' . $branch->address->address_line_2 }}</span>
                                        </span>
                                    </div>
                                    <span class="select-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none">
                                            <rect width="24" height="24" rx="12" fill="currentColor">
                                            </rect>
                                            <g>
                                                <path d="M7.375 12.75L10 15.375L16 9.375" stroke="white" stroke-width="3"
                                                    stroke-linecap="round" stroke-linejoin="round"></path>
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
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary"
                        id="select-branch-btn">{{ __('frontend.next') }}</button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.branch-card').forEach(function(card) {
                    card.addEventListener('click', function() {
                        document.querySelectorAll('.branch-card').forEach(function(c) {
                            c.classList.remove('selected');
                        });
                        this.classList.add('selected');
                    });
                });
            });
            document.getElementById('select-branch-btn').addEventListener('click', async function() {
                const selectedBranch = document.querySelector('.branch-card.selected');
                if (selectedBranch) {
                    const branchId = selectedBranch.getAttribute('data-branch-id');
                    try {
                        const response = await fetch("{{ route('branch.select') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                branch_id: branchId
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            window.location.href = data.redirect; // Typically route('index')
                        }
                    } catch (err) {
                        console.error(err);
                        alert('{{ __('frontend.a_network_error_accured') }}');
                    }
                } else {
                    alert('{{ __('frontend.please_select_a_branch_before_proceeding') }}');
                }
            });
        </script>
        <script>
            window.branchSelected = {{ session()->has('selected_branch_id') ? 'true' : 'false' }};
        </script>
        <script>
            window.addEventListener('loginSuccess', function() {
                // If no branch is selected, show the branch modal
                if (!window.branchSelected || window.branchSelected === false || window.branchSelected === 'false') {
                    if ($('#selectBranchModal').length) {
                        $('#selectBranchModal').modal('show');
                    }
                }
            });
        </script>
        <script>
            // Show branch modal if URL hash is #branch-select and no branch is selected
            document.addEventListener('DOMContentLoaded', function() {
                if ((window.location.hash === '#branch-select' || window.location.hash === '#select-branch') &&
                    (!window.branchSelected || window.branchSelected === false || window.branchSelected === 'false')) {
                    if ($('#selectBranchModal').length) {
                        $('#selectBranchModal').modal('show');
                    }
                }
            });
        </script>
    @endsection
    @push('scripts')
        <script>
            $(document).ready(function() {
                // Handle parent category checkbox change
                $('.widget-category-filter-collpase .form-check-input').on('change', function() {
                    const parentId = $(this).val();
                    const isChecked = $(this).is(':checked');

                    // Toggle all child checkboxes
                    $(`#collapseCategory${parentId} .form-check-input`).prop('checked', isChecked);

                    // Trigger change event on child checkboxes to update any dependent logic
                    $(`#collapseCategory${parentId} .form-check-input`).trigger('change');
                });

                // Handle child category checkbox change
                $('.collapse .form-check-input').on('change', function() {
                    const parentCheckbox = $(this).closest('.collapse')
                        .siblings('.widget-category-filter-collpase')
                        .find('.form-check-input');

                    const parentId = parentCheckbox.val();
                    const childCheckboxes = $(`#collapseCategory${parentId} .form-check-input`);
                    const checkedCount = childCheckboxes.filter(':checked').length;

                    // Update parent checkbox state based on child checkboxes
                    if (checkedCount === 0) {
                        parentCheckbox.prop('checked', false);
                        parentCheckbox.prop('indeterminate', false);
                    } else if (checkedCount === childCheckboxes.length) {
                        parentCheckbox.prop('checked', true);
                        parentCheckbox.prop('indeterminate', false);
                    } else {
                        parentCheckbox.prop('indeterminate', true);
                    }
                });
                const $container = $('#productCardContainer');
                const shimmerLoader = document.querySelector('.shimmer-loader');
                const $noProductsMessage = $('#noProductsMessage');
                const noRecordsHtml = `
        <div class="col-12">
            <div class="no-record-found text-center">
                <img src="{{ asset('img/frontend/no-record.png') }}" alt="No Record Found">
                <h4 class="mt-3">{{ __('frontend.no_records_found') }}</h4>
            </div>
        </div>
    `;



                // Initialize DataTable with proper configuration

                const table = $('#product-cards-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: "{{ route('frontend.products.data') }}",
                        type: 'GET',
                        beforeSend: function(xhr) {

                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || ''
                        },
                        data: function(d) {
                            const selectedCategories = [];
                            $('.widget-category-filter .form-check-input:checked').each(function() {
                                selectedCategories.push($(this).val());
                            });

                            d.min_price = parseFloat($('#min-range').val()) || 0;
                            d.max_price = parseFloat($('#max-range').val()) || 999999;
                            d.categories = selectedCategories;
                            d.filter = $('#productFilter').val();

                            const selectedDiscounts = [];
                            $('.discount-filter:checked').each(function() {
                                const [min, max] = $(this).val().split('-').map(Number);
                                selectedDiscounts.push({
                                    min,
                                    max
                                });
                            });
                            d.discount_ranges = selectedDiscounts;

                            const selectedRatings = [];
                            $('.rating-filter:checked').each(function() {
                                const [min, max] = $(this).val().split('-').map(Number);
                                selectedRatings.push({
                                    min,
                                    max
                                });
                            });
                            d.rating_ranges = selectedRatings;

                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTables AJAX Error:', error);
                            console.error('Response:', xhr.responseText);
                            $('#productCardContainer').html(
                                '<div class="text-center p-4 text-danger">{{ __('frontend.error_loading_products_please_try_again') }}</div>'
                            );
                        }
                    },
                    columns: [{
                            data: 'card',
                            name: 'card',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name',
                            visible: false
                        }
                    ],

                    pageLength: 9,
                    searching: true,
                    lengthChange: false,
                    processing: false,
                    pagingType: 'simple_numbers',
                    dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
                    language: {
                        searchPlaceholder: 'Search products...',
                        search: '',
                        emptyTable: "<div class='text-center p-4'>{{ __('frontend.no_products_available_at_the_moment') }}</div>",
                        zeroRecords: "<div class='text-center p-4'>{{ __('frontend.no_matching_products_found') }}</div>",

                    },
                    drawCallback: function(settings) {
                        try {
                            const data = this.api().rows().data();
                            $container.empty();
                            if (data.length === 0) {
                                $('#productCardContainer').empty();
                                shimmerLoader.classList.add('d-none');
                                $('#productCardContainer').html(
                                    '<div class="text-center p-4">{{ __('frontend.no_matching_products_found') }}</div>'
                                );

                                return;
                            }
                            // Create rows of 3 products each
                            for (let i = 0; i < data.length; i += 3) {
                                const row = $(
                                    '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 gy-4 mb-4"></div>'
                                );
                                for (let j = i; j < Math.min(i + 3, data.length); j++) {
                                    if (data[j] && data[j].card) {
                                        row.append(`<div class="col">${data[j].card}</div>`);
                                    }
                                }

                                $container.append(row);
                            }
                        } catch (error) {
                            console.error('DrawCallback Error:', error);
                            $container.html(
                                '<div class="text-center p-4 text-danger">{{ __('frontend.error_displaying_products') }}</div>'
                            );
                        }
                    }
                });

                // Handle category checkbox changes
                $(document).on('change', '.form-check-input', function() {
                    table.ajax.reload();
                });

                // Handle filter dropdown changes
                $('#productFilter').on('change', function() {
                    // Show/hide close icon based on selection
                    if ($(this).val()) {
                        $('#filterCloseIcon').removeClass('d-none');
                    } else {
                        $('#filterCloseIcon').addClass('d-none');
                    }

                    table.ajax.reload();
                });

                // Handle close icon click
                $('#filterCloseIcon').on('click', function() {
                    $('#productFilter').val('').trigger('change');
                });

                // Show loader before AJAX
                table.on('preXhr.dt', function() {
                    $('#productCardContainer').empty();
                    shimmerLoader.classList.remove('d-none');
                });

                // Hide loader after data loads
                table.on('xhr.dt', function() {
                    shimmerLoader.classList.add('d-none');
                });

                // Handle AJAX errors
                table.on('error.dt', function(e, settings, techNote, message) {
                    console.error('DataTables Error:', message);
                    shimmerLoader.classList.add('d-none');
                    $('#productCardContainer').html(
                        '<div class="text-center p-4 text-danger">{{ __('frontend.error_loading_products_please_try_again') }}</div>'
                    );
                });


                // Handle search input
                $('#searchInput').on('keyup', function() {
                    table.search(this.value).draw();
                });

                // Reset datatable when search is empty
                $('#searchInput').on('input', function() {
                    if (this.value === '') {
                        // Clear search and reset datatable
                        table.search('').draw();

                    }
                });

                const minRange = $('#min-range');
                const maxRange = $('#max-range');
                const rangeFill = $('#range-fill');
                const rangeValue = $('#rangeValue');
                const priceBadges = $('.price-badge');

                // Format currency without decimal places
                function formatCurrency(value) {
                    return '$' + Math.round(value).toLocaleString('en-US');
                }

                // Initialize price range slider
                function updateRangeSlider(triggerReload = true) {
                    const min = parseFloat(minRange.val());
                    const max = parseFloat(maxRange.val());
                    const minPossible = parseFloat(minRange.attr('min'));
                    const maxPossible = parseFloat(maxRange.attr('max'));
                    const totalRange = maxPossible - minPossible;

                    // Update fill position
                    const leftPercent = ((min - minPossible) / totalRange) * 100;
                    const rightPercent = ((max - minPossible) / totalRange) * 100;
                    rangeFill.css({
                        'left': leftPercent + '%',
                        'width': (rightPercent - leftPercent) + '%'
                    });

                    // Update range value with formatted currency (no decimals)
                    const formattedRange = formatCurrency(min) + ' - ' + formatCurrency(max);
                    rangeValue.text(formattedRange);

                    // Update active state of price badges
                    priceBadges.removeClass('active');
                    priceBadges.each(function() {
                        const badgeMin = parseFloat($(this).data('min'));
                        const badgeMax = parseFloat($(this).data('max'));
                        if (min === badgeMin && max === badgeMax) {
                            $(this).addClass('active');
                        }
                    });

                    // Trigger DataTable reload if needed
                    if (triggerReload && typeof table !== 'undefined') {
                        table.ajax.reload();
                    }
                }

                // Handle range input events with debounce
                let debounceTimer;

                function debounceRangeUpdate(callback) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(callback, 300);
                }

                minRange.on('input', function() {
                    const min = parseFloat(this.value);
                    const max = parseFloat(maxRange.val());
                    if (min > max) {
                        this.value = max;
                    }
                    updateRangeSlider(false);
                });

                minRange.on('change', function() {
                    debounceRangeUpdate(() => updateRangeSlider(true));
                });

                maxRange.on('input', function() {
                    const min = parseFloat(minRange.val());
                    const max = parseFloat(this.value);
                    if (max < min) {
                        this.value = min;
                    }
                    updateRangeSlider(false);
                });

                maxRange.on('change', function() {
                    debounceRangeUpdate(() => updateRangeSlider(true));
                });

                // Handle price badge clicks
                priceBadges.on('click', function() {
                    const min = parseFloat($(this).data('min'));
                    const max = parseFloat($(this).data('max'));

                    minRange.val(min);
                    maxRange.val(max);

                    priceBadges.removeClass('active');
                    $(this).addClass('active');

                    updateRangeSlider(true);
                });

                // Initialize range slider
                updateRangeSlider(false);

                // Ensure styles are applied after any dynamic content loads
                function applyRangeStyles() {
                    $('.range-input').each(function() {
                        $(this).css({
                            'background': 'none',
                            '-webkit-appearance': 'none',
                            '-moz-appearance': 'none'
                        });
                    });
                }


                // Apply styles initially
                applyRangeStyles();

                // Apply styles again after any AJAX updates
                $(document).ajaxComplete(function() {
                    applyRangeStyles();
                });

                // Initialize select2 if you're using it
                if ($.fn.select2) {
                    $('.select2').select2({
                        minimumResultsForSearch: Infinity // Disable search if not needed
                    });
                }

                function showNoRecords() {
                    $('.no-record-found').parent().remove();
                    $('.product-card').closest('.col').hide();
                    $container.html(noRecordsHtml);
                }

                function showAllProducts() {
                    $('.no-record-found').parent().remove();
                    $('.product-card').closest('.col').show();
                }

                // Handle rating filter changes
                $('.rating-filter').on('change', function() {
                    const checkedFilters = $('.rating-filter:checked');

                    // If no filters are checked, show all products
                    if (checkedFilters.length === 0) {
                        showAllProducts();
                        return;
                    }

                    // Check if any selected range has products
                    let hasProductsInSelectedRanges = false;
                    checkedFilters.each(function() {
                        if (parseInt($(this).data('count')) > 0) {
                            hasProductsInSelectedRanges = true;
                            return false; // Break the loop
                        }
                    });

                    // If none of the selected ranges have products, show no records
                    if (!hasProductsInSelectedRanges) {
                        showNoRecords();
                        return;
                    }

                    // Hide all products initially
                    $('.product-card').closest('.col').hide();
                    $('.no-record-found').parent().remove();

                    let anyProductsShown = false;

                    checkedFilters.each(function() {
                        const minRating = parseFloat($(this).data('min'));
                        const maxRating = parseFloat($(this).data('max'));

                        $('.product-card').each(function() {
                            const ratingAttr = $(this).data('rating');
                            if (ratingAttr === 'no-rating' || ratingAttr === '0' || !
                                ratingAttr) {
                                return;
                            }

                            const rating = parseFloat(ratingAttr);
                            if (rating >= minRating && rating < maxRating) {
                                $(this).closest('.col').show();
                                anyProductsShown = true;
                            }
                        });
                    });

                    if (!anyProductsShown) {
                        showNoRecords();
                    }
                });

                // Handle discount filter changes
                $('.discount-filter').on('change', function() {
                    table.ajax.reload();
                });

                // Handle reset filters button click
                $('#resetProductFilters').on('click', function() {
                    // Clear search input
                    $('#searchInput').val('').trigger('keyup'); // Trigger keyup to clear search from DataTable

                    // Reset sort filter
                    $('#productFilter').val('').trigger('change');

                    // Uncheck all category checkboxes
                    $('.widget-category-filter .form-check-input:checked').prop('checked', false).trigger(
                        'change');

                    // Reset price range sliders to their initial min/max values
                    const initialMinPrice = parseFloat($('#min-range').attr('min'));
                    const initialMaxPrice = parseFloat($('#max-range').attr('max'));
                    $('#min-range').val(initialMinPrice);
                    $('#max-range').val(initialMaxPrice);
                    updateRangeSlider(true); // Update slider display and trigger reload

                    // Uncheck all rating filter checkboxes
                    $('.rating-filter:checked').prop('checked', false).trigger('change');

                    // Uncheck all discount filter checkboxes
                    $('.discount-filter:checked').prop('checked', false).trigger('change');

                    // DataTables reload will be triggered by the change events above or updateRangeSlider
                });

                // Auto-close subcategory collapse when parent is unchecked
                $(document).on('change', '.widget-category-filter-collpase .form-check-input', function() {
                    var $parentRow = $(this).closest('.widget-category-filter-collpase');
                    var collapseId = $parentRow.attr('data-bs-target');
                    if (!$(this).is(':checked') && collapseId) {
                        var collapseEl = document.querySelector(collapseId);
                        if (collapseEl && collapseEl.classList.contains('show')) {
                            var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, {
                                toggle: false
                            });
                            bsCollapse.hide();
                        }
                    }
                });
            });
        </script>
    @endpush

    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/shop-filters.css') }}">
    @endpush
