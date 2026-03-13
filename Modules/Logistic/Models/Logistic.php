<?php

namespace Modules\Logistic\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Logistic extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'logistics';

    public function scopeIsActive($query)
    {
        return $query->where('status', 1);
    }

    const CUSTOM_FIELD_MODEL = 'Modules\Logistic\Models\Logistic';

    protected $appends = ['feature_image'];

    protected $casts = [

        'status' => 'integer',

    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return \Modules\Logistic\database\factories\LogisticFactory::new();
    }

    protected function getFeatureImageAttribute()
    {
        $media = $this->getFirstMediaUrl('feature_image');

        return isset($media) && ! empty($media) ? $media : default_feature_image();
    }

    /**
     * Get the orders that use this logistic.
     */
    public function orders()
    {
        return $this->hasMany(\Modules\Product\Models\Order::class, 'logistic_id');
    }

    /**
     * Get the products through orders.
     */
    public function products()
    {
        return $this->hasManyThrough(
            \Modules\Product\Models\Product::class,
            \Modules\Product\Models\OrderItem::class,
            'order_id', // Foreign key on order_items table
            'id', // Foreign key on products table
            'id', // Local key on logistics table
            'product_id' // Local key on order_items table
        )->distinct();
    }
}
