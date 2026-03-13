@props(['categories' => []])

@if (count($categories))
    <div class="category-section-wrapper section-spacing">
        <div class="container">
            <div class="section-title text-center">
                <span
                    class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.top_category') }}</span>
                <h4 class="title mb-0">{{ __('frontend.our_permium_category') }}</h4>
            </div>
        </div>
        <div class="category-slider-wrapper">
            <div class="category-slider slick-general" data-spacing="10" data-items="6" data-items-desktop="5"
                data-items-laptop="4" data-items-tablet="3" data-items-mobile-sm="2" data-items-mobile="1"
                data-speed="500" data-autoplay="true" data-infinite="true" data-navigation="true"
                data-pagination="false" data-center="true" data-centerpadding="8%">
                @foreach ($categories as $category)
                    <div class="slick-item">
                        <x-category_card :category="$category" />
                    </div>
                @endforeach
            </div>
        </div>
        <div class="text-center mt-5">
            <a class="btn btn-secondary" href="{{ route('category') }}">{{ __('frontend.view_all') }}</a>
        </div>
    </div>
@endif
