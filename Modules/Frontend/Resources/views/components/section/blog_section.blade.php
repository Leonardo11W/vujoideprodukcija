@props(['blogs'])

@if ($blogs->isNotEmpty())
    <div class="blog-section section-spacing-bottom">
        <div class="container">
            <div class="section-title">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-lg-3 gap-2">
                    <div>
                        <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.blog')}}</span>
                        <h4 class="title">{{__('frontend.daily_tips_to_remember')}}</h4>
                    </div>
                    <a href="{{ route('blogs') }}" class="btn btn-secondary">{{__('frontend.view_all')}}</a>
                </div>
            </div>
            <div class="blog-slider slick-general" data-spacing="10" data-items="3" data-items-desktop="3" data-items-laptop="3" data-items-tablet="2" data-items-mobile-sm="2" data-items-mobile="1" data-speed="500" data-autoplay="true" data-infinite="true" data-navigation="false" data-pagination="true" data-center="true" data-centerpadding="">
                @foreach ($blogs as $blog)
                    <div class="slick-item">
                        <x-frontend::card.blog_card :blog="$blog" />
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
