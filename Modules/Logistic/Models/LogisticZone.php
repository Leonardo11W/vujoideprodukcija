<?php

namespace Modules\Logistic\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\World\Models\City;

class LogisticZone extends BaseModel
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'logistic_zones';

    const CUSTOM_FIELD_MODEL = 'Modules\Logistic\Models\Logistic';

    protected $fillable = ['name','description','mobile', 'logistic_id', 'standard_delivery_charge', 'express_delivery_charge', 'standard_delivery_time', 'express_delivery_time', 'country_id', 'state_id'];

    protected $casts = [

        'logistic_id' => 'integer',
        'standard_delivery_charge' => 'double',
        'express_delivery_charge' => 'double',
        'country_id' => 'integer',
        'state_id' => 'integer',

    ];

    protected static function newFactory()
    {
        return \Modules\Logistic\Database\factories\LogisticZoneFactory::new();
    }

    public function logistic()
    {
        return $this->belongsTo(Logistic::class, 'logistic_id');
    }

    public function cities()
    {
        return $this->belongsToMany(City::class, 'logistic_zone_city', 'logistic_zone_id', 'city_id');
    }

    /**
     * Get the orders that use this logistic zone.
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
            'id', // Local key on logistic_zones table
            'product_id' // Local key on order_items table
        )->distinct();
    }
}
