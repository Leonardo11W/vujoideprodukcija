<?php

namespace Modules\Product\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Variations extends BaseModel
{
    use HasFactory;

    protected $table = 'variations';

    const CUSTOM_FIELD_MODEL = 'Modules\Product\Models\Product';

    protected $fillable = ['name', 'status', 'type', 'is_fixed'];

    protected $casts = [
        'is_fixed' => 'integer',
    ];

    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\VariationsFactory::new();
    }

    public function values()
    {
        return $this->hasMany(VariationValue::class, 'variation_id');
    }

    /**
     * Get the products that use this variation through ProductVariationCombination.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_variation_combinations', 'variation_id', 'product_id')
            ->distinct();
    }
}
