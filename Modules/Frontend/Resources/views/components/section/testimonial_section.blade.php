<div class="testimonial-section section-spacing-bottom vujo-section vujo-testimonials">
    <div class="container">
        <div class="section-title text-center">
            <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.happy_customers')}}</span>
            <h4 class="title mb-0">{{__('frontend.1000+_happy_customers_from_frezka',['app' => setting('app_name')])}}</h4>
        </div>
    </div>

    @if($ratings->count())
    <div class="testimonial-slider-wrapper">
        <div class="testimonial-slider slick-general"
            data-spacing="10"
            data-items="3"
            data-items-desktop="3"
            data-items-laptop="3"
            data-items-tablet="2"
            data-items-mobile-sm="1"
            data-items-mobile="1"
            data-speed="500"
            data-autoplay="true"
            data-infinite="true"
            data-navigation="true"
            data-pagination="false"
            data-center="true"
            data-centerpadding="10%"
        >
            @foreach($ratings as $rating)
                <div class="slick-item">
                    <x-testimonial_card :rating="$rating" />
                </div>
            @endforeach
        </div>
    </div>
    @else
        <div class="text-center py-5">
            <p class="text-body">{{ __('frontend.no_reviews_available_yet') }}</p>
        </div>
    @endif
</div>