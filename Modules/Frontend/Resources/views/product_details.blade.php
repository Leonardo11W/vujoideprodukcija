@extends('frontend::layouts.master')
@section('title')
    {{ $product->name }}
@endsection

@section('content')


    <div class="section-spacing-inner-pages">
        <div class="container">

            <div class="section-spacing-bottom">
                <div class="row gy-4">
                    <div class="col-lg-6">
                        <div class="product-details-slider">
                            <div class="bg-gray-800 rounded img-thmbnail-product position-relative">
                                @php
                                    $now = time();
                                    $startTs = isset($product->discount_start_date) && $product->discount_start_date !== '' ? (int) $product->discount_start_date : null;
                                    $endTs = isset($product->discount_end_date) && $product->discount_end_date !== '' ? (int) $product->discount_end_date : null;
                                    $withinDiscountDates = true;
                                    if ($startTs !== null && $now < $startTs) { $withinDiscountDates = false; }
                                    if ($endTs !== null && $now > $endTs) { $withinDiscountDates = false; }
                                    $showSaleBadge = ($product->discount_value ?? 0) > 0 && $withinDiscountDates && isActiveProductDiscount($product);
                                @endphp
                            @if ($showSaleBadge)
                                    <span class="product-meta badge bg-primary">{{ __('frontend.sale') }}</span>
                                @endif
                                <span class="product-wishlist cursor-pointer" data-product-id="{{ $product->id }}">
                                    <i
                                        class="ph {{ $product->in_wishlist ? 'ph-heart-fill text-danger' : 'ph-heart' }}"></i>
                                </span>
                                <div class="slider slider-for">
                                    @php
                                        $hasImages = false;
                                    @endphp

                                    {{-- Display product media images --}}
                                    @if (isset($product->media) && $product->media->count() > 0)
                                        @foreach ($product->media as $media)
                                            @php $hasImages = true; @endphp
                                            <div class="slick-item">
                                                <img src="{{ $media->getFullUrl() }}" alt="{{ $product->name }}"
                                                    class="w-100 object-cover image-for-slide">
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Display gallery images --}}
                                    @if (isset($product->gallery) && $product->gallery->count() > 0)
                                        @foreach ($product->gallery as $galleryItem)
                                            @if ($galleryItem->getFirstMediaUrl('gallery_images'))
                                                @php $hasImages = true; @endphp
                                                <div class="slick-item">
                                                    <img src="{{ $galleryItem->getFirstMediaUrl('gallery_images') }}"
                                                        alt="{{ $product->name }}"
                                                        class="w-100 object-cover image-for-slide">
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif

                                    {{-- Display default image if no images exist --}}
                                    @if (!$hasImages)
                                        <div class="slick-item">
                                            <img src="{{ asset('img/frontend/product.png') }}" alt="{{ $product->name }}"
                                                class="w-100 object-cover image-for-slide">
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @php
                                $totalImagesCount = isset($product->media) ? $product->media->count() : 0;
                                if (isset($product->gallery)) {
                                    foreach ($product->gallery as $galleryItem) {
                                        if ($galleryItem->getFirstMediaUrl('gallery_images')) {
                                            $totalImagesCount++;
                                        }
                                    }
                                }
                            @endphp

                            @if ($totalImagesCount > 1)
                                <div class="slider slider-nav" data-spacing="10">
                                    @php
                                        $hasThumbs = false;
                                    @endphp

                                    {{-- Display product media thumbnails --}}
                                    @if (isset($product->media) && $product->media->count() > 0)
                                        @foreach ($product->media as $media)
                                            @php $hasThumbs = true; @endphp
                                            <div class="slick-item">
                                                <div class="bg-gray-800 thumb-image">
                                                    <img src="{{ $media->getFullUrl() }}" alt="{{ $product->name }}"
                                                        class="avatat avatar-70 object-cover">
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Display gallery thumbnails --}}
                                    @if (isset($product->gallery) && $product->gallery->count() > 0)
                                        @foreach ($product->gallery as $galleryItem)
                                            @if ($galleryItem->getFirstMediaUrl('gallery_images'))
                                                @php $hasThumbs = true; @endphp
                                                <div class="slick-item">
                                                    <div class="bg-gray-800 thumb-image">
                                                        <img src="{{ $galleryItem->getFirstMediaUrl('gallery_images') }}"
                                                            alt="{{ $product->name }}"
                                                            class="avatat avatar-70 object-cover">
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif

                                    {{-- Display default thumbnail if no images exist --}}
                                    @if (!$hasThumbs)
                                        <div class="slick-item">
                                            <div class="bg-gray-800 thumb-image">
                                                <img src="{{ asset('img/frontend/product.png') }}"
                                                    alt="{{ $product->name }}" class="avatat avatar-70 object-cover">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h4>{{ $product->name }}</h4>
                        <span>{!! $product->description ?: e(__('frontend.no_description_available')) !!}</span>
                        <span>{!! $product->short_description ?? null !!}</span>

                        @php
                            $selectedPrice = $product->price ?? 0; // Fallback to product's main price if no variations
                            $selectedStock = $product->stock_qty ?? 0; // Fallback to product's main stock

                            $firstVariation = $product->product_variations->first();
                            if ($firstVariation) {
                                $selectedPrice = $firstVariation->price;
                                $selectedStock = $firstVariation->stock_qty;
                            }

                            $originalProductPrice = $product->max_price ?? ($product->price ?? 0);
                            $rawDiscountValue = (float) ($product->discount_value ?? 0);
                            $now = time();
                            $startTs = isset($product->discount_start_date) && $product->discount_start_date !== '' ? (int) $product->discount_start_date : null;
                            $endTs = isset($product->discount_end_date) && $product->discount_end_date !== '' ? (int) $product->discount_end_date : null;
                            $withinDiscountDates = true;
                            if ($startTs !== null && $now < $startTs) { $withinDiscountDates = false; }
                            if ($endTs !== null && $now > $endTs) { $withinDiscountDates = false; }
                            $isActiveDiscount = $rawDiscountValue > 0 && $withinDiscountDates && isActiveProductDiscount($product);
                            $discountValue = $isActiveDiscount ? $rawDiscountValue : 0;
                            $discountType = $product->discount_type ?? '';
                            $discountedProductPrice =
                                $discountType === 'percent'
                                    ? $originalProductPrice - ($originalProductPrice * $discountValue) / 100
                                    : $originalProductPrice - $discountValue;

                            $discountLabel = null;
                            if ($discountValue > 0) {
                                if ($discountType === 'percent') {
                                    $discountLabel = '(' . rtrim(rtrim(number_format($discountValue, 2), '0'), '.') . '% off)';
                                } else {
                                    $discountLabel = '(' . \Currency::format($discountValue) . ' off)';
                                }
                            }
                        @endphp

                        <div class="d-flex align-items-center gap-lg-3 gap-2 mb-1 pb-2 mt-3">
                            @if ($discountValue > 0)
                            <span class="text-primary font-size-21-3"
                                id="display-price">{{ \Currency::format($discountedProductPrice) }}</span>
                                <del class="text-body font-size-21-3"
                                    id="original-price">{{ \Currency::format($originalProductPrice) }}</del>
                            @else
                                <span class="text-primary font-size-21-3"
                                    id="display-price">{{ \Currency::format($selectedPrice) }}</span>
                            @endif
                            @if ($discountLabel)
                                <div class="mb-2">
                                    <span class="text-success fw-medium font-size-14">{{ $discountLabel }}</span>
                                </div>
                            @endif
                        </div>


                        @php
                            $avgRating = $product->product_review->avg('rating') ?? 0;
                            $fullStars = floor($avgRating);
                            $hasHalfStar = $avgRating - $fullStars >= 0.5;
                        @endphp

                        <div class="d-flex align-items-center gap-2 mb-4">
                            <div class="d-flex align-items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= $fullStars)
                                        <i class="ph-fill ph-star text-warning"></i>
                                    @elseif($i == $fullStars + 1 && $hasHalfStar)
                                        <i class="ph-fill ph-star-half text-warning"></i>
                                    @else
                                        <i class="ph ph-star text-warning"></i>
                                    @endif
                                @endfor
                            </div>
                            <span class="fw-medium font-size-14">{{ number_format($avgRating, 1) }}</span>
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span class="fw-medium font-size-14">{{ __('frontend.Availability') }}</span>
                            <span class="fw-medium font-size-14" id="stock-status">
                                @if ($product->stock_qty > 0)
                                    <span class="text-success">{{ __('frontend.In_Stock') }}</span>
                                @else
                                    <span class="text-danger">{{ __('frontend.Out_of_Stock') }}</span>
                                @endif
                            </span>
                            <span class="fw-medium font-size-14" id="stock-qty-display">

                            </span>
                        </div>

                        <div class="mb-4">
                            <!-- <span class="fw-medium font-size-14 heading-color">{{ __('frontend.size') }}</span> -->
                            <div class="select-size d-flex align-items-center flex-wrap gap-3 mt-2">
                                @php
                                    $variations = $product->product_variations
                                        ? $product->product_variations->filter(fn($v) => !empty($v->id))->values()
                                        : collect();
                                @endphp

                                @foreach ($variations as $variation)
                                    @php
                                        $labelParts = $variation->combinations
                                            ? $variation->combinations->map(function ($c) {
                                                return $c->variation_combination_value?->name
                                                    ?? $c->variation_combination_value?->value
                                                    ?? null;
                                            })->filter()->values()
                                            : collect();
                                        $variationLabel = $labelParts->isNotEmpty()
                                            ? $labelParts->join(' / ')
                                            : ((string) ($variation->variation_key ?? __('frontend.variation')));
                                        $variationLabel = trim($variationLabel) !== '' ? $variationLabel : __('frontend.variation');
                                    @endphp

                                    <div class="form-check">
                                        <label class="form-check-label" for="variation_{{ $variation->id }}">
                                            <input class="form-check-input variation-radio" type="radio"
                                                value="{{ $variation->id }}" name="variation"
                                                id="variation_{{ $variation->id }}" data-product-id="{{ $product->id }}"
                                                data-variation-key="{{ $variation->variation_key ?? '' }}"
                                                data-variation-id="{{ $variation->id }}"
                                                data-price="{{ $variation->price ?? $product->price }}"
                                                {{ $loop->first ? 'checked' : '' }}>
                                            {{ $variationLabel }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="btn-group iq-qty-btn" data-qty="btn" role="group">
                                <button type="button" class="btn btn-link border-0 iq-quantity-minus heading-color"
                                    onclick="decrementQuantity()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="6" height="3" viewBox="0 0 6 3"
                                        fill="none">
                                        <path d="M5.22727 0.886364H0.136364V2.13636H5.22727V0.886364Z" fill="currentColor">
                                        </path>
                                    </svg>
                                </button>
                                <input type="number" class="btn btn-link border-0 input-display" data-qty="input"
                                    min="1" max="{{ $product->stock_qty }}" value="1" title="{{ __('frontend.qty') }}"
                                    id="quantity-input" autocomplete="off">
                                <button type="button" class="btn btn-link border-0 iq-quantity-plus heading-color"
                                    onclick="incrementQuantity()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="9" height="8"
                                        viewBox="0 0 9 8" fill="none">
                                        <path
                                            d="M3.63636 7.70455H4.90909V4.59091H8.02273V3.31818H4.90909V0.204545H3.63636V3.31818H0.522727V4.59091H3.63636V7.70455Z"
                                            fill="currentColor"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-md-nowrap flex-wrap">
                                <button class="btn btn-secondary" id="add-to-cart-btn"
                                    data-product-id="{{ $product->id }}"
                                    data-product-variation-id="{{ $firstVariation?->id ?? '' }}"
                                    style="display: {{ $product->in_cart ? 'none' : 'inline-block' }};"
                                    {{ $product->stock_qty <= 0 || !$firstVariation ? 'disabled' : '' }}>{{ __('frontend.add_to_cart') }}</button>
                                <button class="btn btn-danger" id="remove-from-cart-btn"
                                    data-product-id="{{ $product->id }}"
                                    data-product-variation-id="{{ $firstVariation?->id ?? '' }}"
                                    style="display: {{ $product->in_cart ? 'inline-block' : 'none' }};">{{ __('frontend.remove_from_cart') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if (isset($relatedProducts) && count($relatedProducts) > 0)
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-4 gap-2 flex-wrap">
                        <h4 class="m-0">{{ __('frontend.related_products') }}</h4>
                        <a href="{{ route('shop') }}" class="btn btn-secondary">{{ __('frontend.view_all') }}</a>
                    </div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            <div class="col">
                                <x-product_card :product="$relatedProduct" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.getElementById('quantity-input');
        const displayPrice = document.getElementById('display-price');
        const originalPrice = document.getElementById('original-price');
        const stockStatus = document.getElementById('stock-status');
        const stockQtyDisplay = document.getElementById('stock-qty-display');
        const addToCartBtn = document.getElementById('add-to-cart-btn');
        var removeFromCartBtn = document.getElementById('remove-from-cart-btn');

        let currentMaxStock = parseInt(quantityInput.max, 10);

        function formatCurrency(value) {
            return '$' + parseFloat(value).toFixed(2);
        }

        function clampQuantity() {
            let value = parseInt(quantityInput.value, 10);
            if (isNaN(value) || value < 1) {
                quantityInput.value = 1;
            } else if (value > currentMaxStock) {
                quantityInput.value = currentMaxStock;
            }
        }

        window.incrementQuantity = function() {
            let currentValue = parseInt(quantityInput.value, 10);
            if (isNaN(currentValue) || currentValue < 1) {
                currentValue = 1;
            }
            // Only increment by 1
            if (currentValue <= currentMaxStock) {
                quantityInput.value = currentValue;
            } else {
                quantityInput.value = currentMaxStock;
            }
        }

        window.decrementQuantity = function() {
            let currentValue = parseInt(quantityInput.value, 10);
            if (isNaN(currentValue) || currentValue < 2) {
                quantityInput.value = 1;
            } else {
                quantityInput.value = currentValue;
            }
        }

        quantityInput.addEventListener('input', clampQuantity);
        quantityInput.addEventListener('blur', clampQuantity);

        // Handle variation selection
        document.querySelectorAll('.variation-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                const newPrice = parseFloat(this.dataset.price);
                const newVariationId = this.dataset.variationId || '';

                // Keep add/remove cart buttons in sync with selected variation
                if (addToCartBtn && newVariationId) {
                    addToCartBtn.setAttribute('data-product-variation-id', newVariationId);
                }
                if (removeFromCartBtn && newVariationId) {
                    removeFromCartBtn.setAttribute('data-product-variation-id', newVariationId);
                }

                // Update displayed price
                if (originalPrice && !isNaN(parseFloat(originalPrice.textContent.replace('$',
                        '')))) {
                    const productDiscountValue = {{ $discountValue }};
                    const productDiscountType = '{{ $product->discount_type ?? '' }}';
                    let priceToDisplay = newPrice;

                    if (productDiscountValue > 0) {
                        priceToDisplay = productDiscountType === 'percent' ?
                            newPrice - (newPrice * productDiscountValue / 100) :
                            newPrice - productDiscountValue;
                    }
                    displayPrice.textContent = formatCurrency(priceToDisplay);
                    originalPrice.textContent = formatCurrency(newPrice);
                    originalPrice.style.display = (productDiscountValue > 0) ? '' : 'none';
                } else {
                    displayPrice.textContent = formatCurrency(newPrice);
                    if (originalPrice) originalPrice.style.display = 'none';
                }
            });
        });

        // Trigger change for the initially checked radio button
        const initialCheckedRadio = document.querySelector('.variation-radio:checked');
        if (initialCheckedRadio) {
            initialCheckedRadio.dispatchEvent(new Event('change'));
        }

        // Add to Cart button handler
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                var productId = this.getAttribute('data-product-id');
                var productVariationId = this.getAttribute('data-product-variation-id');
                var qty = parseInt(document.getElementById('quantity-input').value) || 1;
                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                addToCartBtn.disabled = true;
                fetch("{{ route('cart.add') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            qty: qty,
                            _token: token,
                            product_variation_id: productVariationId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {

                        addToCartBtn.disabled = false;
                        if (data.status) {
                            // Store cart state in localStorage
                            var cartItems = JSON.parse(localStorage.getItem('cartItems') || '{}');
                            cartItems[productId] = true;
                            localStorage.setItem('cartItems', JSON.stringify(cartItems));

                            // Dispatch custom event to update all product cards on other pages
                            window.dispatchEvent(new CustomEvent('cartUpdated', {
                                detail: {
                                    productId: productId,
                                    inCart: true
                                }
                            }));

                            if (window.toastr) toastr.success(
                                '{{ __('frontend.product_added_to_cart_successfully') }}');
                            if (typeof data.cart_count !== 'undefined') {
                                var cartCount = document.getElementById('cartCount');
                                var cartItemCount = document.getElementById('cartItemCount');
                                if (cartCount) cartCount.textContent = data.cart_count;
                                if (cartItemCount) cartItemCount.textContent = data.cart_count;
                            }
                            addToCartBtn.style.display = 'none';
                            if (removeFromCartBtn) removeFromCartBtn.style.display = 'inline-block';
                        } else {
                            // Force show login modal for guests or if backend returns unauthenticated error
                            if (
                                (window.isLoggedIn === false && window.$ && $('#loginModal')
                                    .length) ||
                                (data && data.error === 'Unauthenticated' && window.$ && $(
                                    '#loginModal').length) ||
                                (window.$ && $('#loginModal').length && (data.message ===
                                    'Unauthenticated.' || data.message === 'Unauthorized'))
                            ) {
                                $('#loginModal').modal('show');
                            } else if (window.toastr) {
                                toastr.error((data && (data.message || data.error)) ||
                                    '{{ __('frontend.failed_to_add_item_to_cart_please_try_again') }}');
                            }
                        }
                    })
                    .catch((err) => {

                        addToCartBtn.disabled = false;
                        // Force show login modal for guests on any error
                        if (window.isLoggedIn === false && window.$ && $('#loginModal').length) {
                            $('#loginModal').modal('show');
                        } else if (err && err.status === 401) {
                            if (window.$ && $('#loginModal').length) {
                                $('#loginModal').modal('show');
                            }
                        } else if (typeof $ !== 'undefined' && $('#loginModal').length) {
                            $('#loginModal').modal('show');
                        } else if (window.toastr) {
                            toastr.error('{{ __('frontend.failed_to_add_item_to_cart_please_try_again') }}');
                        }
                    });
            });
        }
        // Remove from Cart button handler
        if (removeFromCartBtn) {
            removeFromCartBtn.addEventListener('click', function() {
                var productId = this.getAttribute('data-product-id');
                var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                removeFromCartBtn.disabled = true;
                fetch("{{ route('cart.remove') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            _token: token
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        removeFromCartBtn.disabled = false;
                        if (data.status) {
                            // Update localStorage
                            var cartItems = JSON.parse(localStorage.getItem('cartItems') || '{}');
                            delete cartItems[productId];
                            localStorage.setItem('cartItems', JSON.stringify(cartItems));

                            // Dispatch custom event to update all product cards on other pages
                            window.dispatchEvent(new CustomEvent('cartUpdated', {
                                detail: {
                                    productId: productId,
                                    inCart: false
                                }
                            }));

                            if (window.toastr) toastr.success(
                                '{{ __('frontend.product_removed_from_cart_successfully') }}');
                            if (typeof data.cart_count !== 'undefined') {
                                var cartCount = document.getElementById('cartCount');
                                var cartItemCount = document.getElementById('cartItemCount');
                                if (cartCount) cartCount.textContent = data.cart_count;
                                if (cartItemCount) cartItemCount.textContent = data.cart_count;
                            }
                            removeFromCartBtn.style.display = 'none';
                            if (addToCartBtn) addToCartBtn.style.display = 'inline-block';
                        } else {
                            if (window.toastr) toastr.error(data.message ||
                                '{{ __('frontend.remove_from_cart_failed') }}');
                        }
                    })
                    .catch(() => {
                        removeFromCartBtn.disabled = false;
                        if (window.toastr) toastr.error(
                            '{{ __('frontend.failed_to_remove_item_from_cart_please_try_again') }}');
                    });
            });
        }
    });
</script>
