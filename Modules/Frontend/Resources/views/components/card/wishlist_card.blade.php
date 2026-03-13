@props(['wishlist'])

<div class="wishlist-card bg-gray-800 p-4 rounded mb-3">
    <div class="wishlist-card-inner">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-shrink-0">
                    title="{{ __('frontend.remove') }}">
                    <i class="ph ph-trash"></i>
                </button>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap flex-md-nowrap flex-grow-1">
                <div class="wishlist-card-image">
                    <img src="{{ $wishlist->product->media->pluck('original_url')->first() ?? asset('img/frontend/product.png') }}"
                        alt="{{ $wishlist->product->name }}" class="img-fluid rounded avatar-70">
                </div>
                <div class="wishlist-card-content">
                    <h5 class="mb-2">{{ $wishlist->product->name }}</h5>
                    <div class="d-flex align-items-center gap-2">
                        @if (
                            ($wishlist->product->min_price ?? 0) !=
                                getDiscountedProductPrice($wishlist->product->min_price ?? 0, $wishlist->product_id))
                            <del class="font-size-18">${{ $wishlist->product->min_price ?? '0.00' }}</del>
                        @endif
                        <span
                            class="text-primary font-size-18">${{ getDiscountedProductPrice($wishlist->product->min_price ?? 0, $wishlist->product_id) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex-shrink-0">
                <button class="btn btn-secondary add-to-cart"
                    data-product-id="{{ $wishlist->product_id }}">{{ __('frontend.add_to_cart') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- <tr>
    <td>
        <button type="button" class="btn btn-link border-0 p-0 icon-color" data-bs-toggle="tooltip" data-bs-placement="top" title="Remove">
           
        </button>
    </td>
    <td>
        <div class="d-flex align-items-center gap-3 flex-wrap flex-md-nowrap">
            <div class="bg-gray-900 avatar avatar-70 rounded">
                <img src="{{ asset('img/frontend/product.png') }}" alt="Electric Blue Gel Nail Paint" class="avatar avatar-70 object-fixt-cover">
            </div>
            <div>
                <p class="mb-2">{{__('frontend.electric_blue_gel_nail_paint')}}</p>
                <div class="d-flex align-items-center gap-2">
                    <del class="font-size-18">$99.02</del>
                    <span class="text-primary font-size-18">$99.02</span>
                </div>
            </div>
        </div>
    </td>
    <td class="text-end">
        <button class="btn btn-secondary">{{__('frontend.add_to_cart')}}</button>
    </td>
</tr> --}}

<script>
    $(document).on('click', '.remove-from-wishlist-btn', function(e) {


        try {
            e.preventDefault();
            const productId = $(this).data('product-id');

            Swal.fire({
                title: '{{ __('frontend.are_you_sure') }}',
                text: '{{ __('frontend.do_you_want_to_remove_this_product_from_your_wishlist') }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __('frontend.yes_delete_it') }}',
                cancelButtonText: '{{ __('frontend.cancel') }}',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('wishlist.remove') }}',
                        type: 'POST',
                        data: {
                            product_id: productId,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.status) {
                                Swal.fire('{{ __('frontend.removed') }}',
                                    '{{ __('frontend.product_removed_from_wishlist') }}',
                                    'success');
                                // Remove the card from DOM
                                $(e.target).closest('.wishlist-card').remove();
                            } else {
                                Swal.fire('{{ __('frontend.error') }}', response.message ||
                                    '{{ __('frontend.could_not_remove_from_wishlist') }}',
                                    'error');
                            }
                        },
                        error: function() {
                            Swal.fire('{{ __('frontend.error') }}',
                                '{{ __('frontend.failed_to_remove_from_wishlist') }}',
                                'error');
                        }
                    });
                }
            });
        } catch (err) {
            console.error('Error in remove-from-wishlist-btn handler:', err);
        }
    });
</script>
