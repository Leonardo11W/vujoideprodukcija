@props(['products'])

<div class="product-section section-spacing-bottom vujo-section vujo-product-section">
    <div class="container">
        <div class="section-title text-center mb-4">
            <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.online_store')}}</span>
            <h4 class="title">{{__('frontend.best_seller_products')}}</h4>
        </div>
    </div>
    @if(isset($products) && count($products))
    <div class="product-slider slick-general product-slider-centered" data-spacing="10" data-items="4" data-items-desktop="4" data-items-laptop="4" data-items-tablet="3" data-items-mobile-sm="2" data-items-mobile="1" data-speed="500" data-autoplay="true" data-infinite="true" data-navigation="true" data-pagination="false" data-center="true" data-centerpadding="10%">
            @foreach($products as $product)
                <div class="slick-item">
                    <x-product_card :product="$product" />
                </div>
            @endforeach
        </div>
        <div class="text-center mt-5 pt-lg-3 pt-0">
            <a href="{{ route('shop') }}" class="btn btn-secondary">{{__('frontend.view_all')}}</a>
        </div>
    @else
        <div class="text-center py-4">
            <p>{{__('frontend.no_products_available')}}</p>
        </div>
    @endif
</div>

@include('components.login_modal')

<style>
.product-slider-centered {
  .slick-list {
    .slick-track {
      margin-left: 0;
    }
  }

  &:not(.slick-initialized) {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    justify-content: center;

    .slick-item {
      &:first-child {
        flex: 0 0 auto;
        width: 160px;
      }
    }
  }

  .slick-item {
    height: 100%;
    padding: 0 var(--spacing);
  }
}
</style>