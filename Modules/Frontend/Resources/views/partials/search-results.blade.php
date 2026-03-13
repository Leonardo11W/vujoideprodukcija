@if(empty($query))
<div class="section-spacing">
    <div class="container">
        <div class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
            <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image" class="img-fluid">
            <div>
                <h4 class="mb-3">Please enter a search term.</h4>
                <p class="m-0">Try something new</p>
            </div>
        </div>
    </div>
</div>
@else
    @php
        $hasResults = count($categories) || count($services) || count($products) || count($experts);
    @endphp
    
    @if($hasResults)
    <div class="section-spacing-inner-pages">
        <div class="container">
            @if(count($categories))
                <h5 class="mt-4">Categories</h5>
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4" id="categories-section">
                    @foreach($categories as $i => $category)
                        <div class="col category-item" style="{{ $i > 5 ? 'display:none;' : '' }}">
                            <x-category_card :category="$category" />
                        </div>
                    @endforeach
                </div>
                @if(count($categories) > 6)
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <button class="btn btn-secondary" id="loadMoreCategories">Load More</button>
                    </div>
                @endif
            @endif

            @if(count($services))
                <h5 class="mt-4">Services</h5>
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4" id="services-section">
                    @foreach($services as $i => $service)
                        <div class="col service-item" style="{{ $i > 5 ? 'display:none;' : '' }}">
                            <x-service_card :service="$service" />
                        </div>
                    @endforeach
                </div>
                @if(count($services) > 6)
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <button class="btn btn-secondary" id="loadMoreServices">Load More</button>
                    </div>
                @endif
            @endif
            @if(count($products))
                <h5 class="mt-4">Products</h5>
                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4" id="products-section">
                    @foreach($products as $i => $product)
                        <div class="col product-item" style="{{ $i > 3 ? 'display:none;' : '' }}">
                            <x-product_card :product="$product" />
                        </div>
                    @endforeach
                </div>
                @if(count($products) > 4)
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <button class="btn btn-secondary" id="loadMoreProducts">Load More</button>
                    </div>
                @endif
            @endif

            @if(count($experts))
                <h5 class="mt-4">Experts</h5>
                <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-3 row-cols-xl-6 gy-4" id="experts-section">
                    @foreach($experts as $i => $expert)
                        <div class="col expert-item" style="{{ $i > 6 ? 'display:none;' : '' }}">
                            <div class="export-card text-center">
                                <div class="export-image position-relative">
                                    <img src="{{ $expert->profile_image }}" alt="{{ $expert->full_name }}"
                                        class="img-fluid rounded-circle avatar-200 object-fit-cover">
                                    <div class="rating-badge d-flex align-items-center justify-content-center gap-3">
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
                @if(count($experts) > 5)
                    <div class="d-flex align-items-center justify-content-center mt-3">
                        <button class="btn btn-secondary" id="loadMoreExperts">Load More</button>
                    </div>
                @endif
            @endif
        </div>
    </div>
    @else
        <div class="section-spacing-inner-pages">
            <div class="container">
                <div class="d-flex justify-content-center align-items-center flex-sm-nowrap flex-wrap gap-lg-5 gap-3">
                    <img src="{{ asset('img/frontend/search-not-found.png') }}" alt="image" class="img-fluid">
                    <div>
                        <h4 class="mb-3">Sorry, we couldn't find your search!</h4>
                        <p class="m-0">Try something new</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
