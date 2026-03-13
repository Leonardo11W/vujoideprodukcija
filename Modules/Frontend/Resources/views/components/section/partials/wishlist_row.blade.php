<tr>
    <td>
        <button type="button" class="btn btn-link border-0 p-0 icon-color" data-bs-toggle="tooltip" data-bs-placement="top" title="Remove">
            <i class="ph ph-trash font-size-18"></i>
        </button>
    </td>
    <td>
        <div class="d-flex align-items-center gap-3 flex-wrap flex-md-nowrap">
            <div class="bg-gray-900 avatar avatar-70 rounded">
                <img src="{{ $product->media->first() ? $product->media->first()->getFullUrl() : asset('img/frontend/product.png') }}" alt="{{ $product->name }}" class="avatar avatar-70 object-fixt-cover">
            </div>
            <div>
                <p class="mb-2">{{ $product->name }}</p>
                <div class="d-flex align-items-center gap-2">
                    @php
                        $isActiveDiscount = isActiveProductDiscount($product);
                        $discountValue = $isActiveDiscount ? $product->discount_value : 0;
                    @endphp
                    @if($discountValue > 0)
                        <del class="font-size-18">{{ \Currency::format($product->max_price) }}</del>
                        <span class="text-primary font-size-18">
                                                          {{ \Currency::format($product->discount_type === 'percent' ? $product->max_price - ($product->max_price * $discountValue / 100) : $product->max_price - $discountValue) }}
                        </span>
                    @else
                        <span class="font-size-18">{{ \Currency::format($product->max_price) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </td>
    <td class="text-end">
        <button class="btn btn-secondary">{{__('frontend.add_to_cart')}}</button>
    </td>
</tr> 