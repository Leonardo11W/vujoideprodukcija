<?php

namespace Modules\Product\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class WishListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $product = $this->product;
        if (! $product) {
            return [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'product_id' => $this->product_id,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }

        // Use ProductResource to get exactly same keys as Product API
        $productData = (new ProductResource($product))->toArray($request);

        // Add wishlist specific keys and legacy keys for backward compatibility
        $productData['wishlist_id'] = $this->id;
        $productData['user_id'] = $this->user_id;
        $productData['product_id'] = $this->product_id;
        $productData['product_name'] = $productData['name'];
        $productData['product_description'] = $productData['short_description'];
        
        // product_image key exists in both ProductResource and old WishListResource

        return $productData;
    }
}
