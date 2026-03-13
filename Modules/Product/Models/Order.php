<?php

namespace Modules\Product\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Logistic\Models\LogisticZone;

class Order extends Model
{
    use HasFactory;

    protected $casts = [
        'order_group_id' => 'integer',
        'user_id' => 'integer',
        'location_id' => 'integer',
        'coupon_discount_amount' => 'double',
        'admin_earning_percentage' => 'double',
        'total_admin_earnings' => 'double',
        'total_vendor_earnings' => 'double',
        'logistic_id' => 'integer',
        'pickup_hub_id' => 'integer',
        'shipping_cost' => 'double',
        'tips_amount' => 'double',
    ];

    protected $fillable = [
        'order_group_id',
        'user_id',
        'guest_user_id',
        'location_id',
        'delivery_status',
        'payment_status',
        'applied_coupon_code',
        'coupon_discount_amount',
        'admin_earning_percentage',
        'total_admin_earnings',
        'logistic_id',
        'logistic_name',
        'pickup_or_delivery',
        'pickup_hub_id',
        'shipping_cost',
        'tips_amount',
        'reward_points',
        'created_at',
        'updated_at',
    ];

    protected static function newFactory()
    {
        return \Modules\Product\Database\factories\OrderFactory::new();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function logistic()
    {
        return $this->belongsTo(LogisticZone::class);
    }

    public function orderGroup()
    {
        return $this->belongsTo(OrderGroup::class);
    }

    public function orderItems()
    {
        return $this->hasMany(\Modules\Product\Models\OrderItem::class, 'order_id')->with('product_variation.product');
    }

    public function orderUpdates()
    {
        return $this->hasMany(OrderUpdate::class)->latest();
    }

    public function location()
    {
        return $this->belongsTo(\Modules\Location\Models\Location::class, 'location_id');
    }

    public function bookingProduct()
    {
        return $this->hasOne(\Modules\Booking\Models\BookingProduct::class, 'order_id');
    }

    public function bookingProducts()
    {
        return $this->hasMany(\Modules\Booking\Models\BookingProduct::class, 'order_id');
    }
}
