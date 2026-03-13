<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationStock extends Model
{
    use HasFactory;

    protected $fillable = ['product_variation_id', 'location_id', 'stock_qty'];

    protected $casts = [

        'product_variation_id' => 'integer',
        'location_id' => 'integer',
        'stock_qty' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\ProductVariationStockFactory::new();
    }

    public function product_variation()
    {
        return $this->belongsTo(ProductVariation::class);
    }
}
