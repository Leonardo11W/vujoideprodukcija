<?php

namespace Modules\Product\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductSchemaResource extends JsonResource
{
    /**
     * Transform the resource into an array aligned with the requested schema.
     */
    public function toArray($request)
    {
        // Images: feature + gallery
        $images = [];
        if (!empty($this->feature_image)) {
            $images[] = $this->feature_image;
        }
        $gallery = method_exists($this, 'gallery') ? $this->gallery()->pluck('full_url')->toArray() : [];
        $images = array_values(array_filter(array_unique(array_merge($images, $gallery))));

        // Sizes: use variation name/sku/id
        $sizes = [];
        if ($this->relationLoaded('product_variations')) {
            $sizes = $this->product_variations->map(function ($variation) {
                return $variation->name ?? $variation->sku ?? (string) $variation->id;
            })->filter()->values()->toArray();
        }

        // Colors: not modeled distinctly; return empty array to respect schema
        $colors = [];

        // Rating summary
        $ratingCount = $this->product_review?->count() ?? 0;
        $ratingAvg = $ratingCount > 0 ? round(($this->product_review->avg('rating')), 1) : 0;

        // Reviews
        $reviews = [];
        if ($this->relationLoaded('product_review')) {
            $reviews = $this->product_review->map(function ($review) {
                $images = method_exists($review, 'gallery') ? $review->gallery->pluck('full_url')->filter()->values()->toArray() : [];
                return [
                    'review_id' => (string) $review->id,
                    'author_name' => optional($review->user)->name ?? 'Anonymous',
                    'rating' => (float) $review->rating,
                    'date' => optional($review->created_at)->toIso8601String(),
                    'text' => $review->review_msg ?? '',
                    'images' => $images,
                ];
            })->toArray();
        }

        return [
            'product_id' => (string) $this->id,
            'product_brand_name' => optional($this->brand)->name,
            'product_name' => $this->name,
            'product_short_description' => $this->short_description ?? '',
            'product_images' => $images,
            'product_actual_price' => (float) ($this->max_price ?? 0),
            'product_price' => (float) ($this->min_price ?? 0),
            'product_sizes' => $sizes,
            'product_available_quantity' => (int) ($this->stock_qty ?? 0),
            'product_selected_size' => null,
            'product_colors' => $colors,
            'product_description' => $this->description ?? '',
            'product_warranty' => $this->has_warranty ? 'Available' : null,
            'product_rating' => [
                'average' => $ratingAvg,
                'count' => $ratingCount,
            ],
            'product_reviews' => $reviews,
            // related_products will be injected from controller to avoid extra keys here
        ];
    }
}

