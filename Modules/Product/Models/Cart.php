<?php

namespace Modules\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'guest_user_id',
        'location_id',
        'product_id',
        'product_variation_id',
        'qty'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'guest_user_id' => 'integer',
        'location_id' => 'integer',
        'product_id' => 'integer',
        'product_variation_id' => 'integer',
        'qty' => 'integer'
    ];

    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\CartFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product_variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id')->with('product_variation_stock', 'combinations');
    }
}
