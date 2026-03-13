<?php

namespace Modules\Product\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Models\Cart;

class ProductVariationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $userId = $request->input('user_id') ?? auth()->id();
        $guestUserId = $request->input('guest_user_id');

        $inCart = false;
        if ($userId) {
            $inCart = Cart::query()
                ->where('user_id', $userId)
                ->where('product_variation_id', $this->id)
                ->exists();
        } elseif ($guestUserId) {
            $inCart = Cart::query()
                ->where('guest_user_id', $guestUserId)
                ->where('product_variation_id', $this->id)
                ->exists();
        }

        return [

            'id' => $this->id,
            'variation_key' => $this->id,
            'sku' => $this->sku,
            'code' => $this->code,
            'location_id' => optional($this->product_variation_stock)->location_id,
            'product_stock_qty' => optional($this->product_variation_stock)->stock_qty,
            'is_stock_avaible' => optional($this->product_variation_stock)->stock_qty > 0 ? 1 : 0,
            'combination' => $this->combinations,
            'combination' => ProductCombinationResource::collection($this->combinations),
            'product_amount' => $this->price,
            'in_cart' => $inCart ? 1 : 0,
            'tax_include_product_price' => $this->price,
            'discounted_product_price' => getDiscountedProductPrice($this->price, $this->product_id),

        ];
    }
}
