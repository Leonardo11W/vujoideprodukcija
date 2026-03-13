<?php

namespace Modules\Booking\Services;

use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Booking\Models\Booking;
use Modules\Promotion\Models\Promotion;

class CouponService
{
    /**
     * Calculate coupon discount
     */
    public function getDiscount($total_amount, $coupon_code, $booking_id, $tip_amount = 0)
    {
        $coupon = Coupon::where('coupon_code', $coupon_code)->first();
        if (!$coupon) return 0;

        if ($coupon->discount_type == 'percent') {
            $discountableBase = max($total_amount - $tip_amount, 0);
            return $discountableBase * ($coupon->discount_percentage / 100);
        }

        return $coupon->discount_amount;
    }

    /**
     * Mark coupon as used/expired
     */
    public function redeemCoupon($coupon_code, $discount, $booking_id)
    {
        $coupon = Coupon::where('coupon_code', $coupon_code)->first();
        $booking = Booking::find($booking_id);
        
        if (!$coupon || !$booking) return;

        UserCouponRedeem::create([
            'coupon_code' => $coupon_code,
            'discount' => $discount,
            'user_id' => $booking->user_id,
            'coupon_id' => $coupon->id,
            'booking_id' => $booking_id,
        ]);

        if (UserCouponRedeem::where('coupon_code', $coupon_code)->count() >= $coupon->use_limit) {
            $coupon->update(['is_expired' => 1]);

            $allCouponsExpired = Coupon::where('promotion_id', $coupon->promotion_id)
                ->where('is_expired', 0)
                ->doesntExist();

            if ($allCouponsExpired) {
                Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
            }
        }
    }
}
