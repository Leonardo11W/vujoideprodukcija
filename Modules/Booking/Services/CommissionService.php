<?php

namespace Modules\Booking\Services;

use App\Models\User;
use Modules\Booking\Models\BookingService;
use Modules\Package\Models\BookingPackages;
use Modules\Promotion\Models\UserCouponRedeem;

class CommissionService
{
    /**
     * Calculate commission for a booking transaction
     */
    public function calculateCommission($data, $manualCouponDiscount = null)
    {
        $booking_id = $data['booking_id'];
        $booking_service = BookingService::where('booking_id', $booking_id)->first();
        $booking_packages = BookingPackages::where('booking_id', $booking_id)->first();

        $employee_id = $booking_packages ? $booking_packages['employee_id'] : ($booking_service ? $booking_service['employee_id'] : null);
        
        if (!$employee_id) {
            return ['commission_data' => null, 'employee_id' => null];
        }

        $employee = User::role('employee')->where('id', $employee_id)->with('commissions')->first();
        if (!$employee || !isset($employee->commissions)) {
            return ['commission_data' => null, 'employee_id' => $employee_id];
        }

        if ($booking_packages) {
            $total_service_amount = BookingPackages::where('booking_id', $booking_id)->sum('package_price');
        } else {
            $total_service_amount = BookingService::where('booking_id', $booking_id)->sum('service_price');
        }

        // Determine coupon discount
        $coupon_discount = $manualCouponDiscount
            ?? ($data['couponDiscountamount'] ?? $data['coupondiscount'] ?? $data['discount_amount'] ?? null);

        if ($coupon_discount === null || $coupon_discount == 0) {
            $coupon_discount = UserCouponRedeem::where('booking_id', $booking_id)->value('discount');
        }

        $discount_percentage = $data['discount_percentage'] ?? null;
        if (($coupon_discount === null || $coupon_discount == 0) && !empty($discount_percentage)) {
            $coupon_discount = ($total_service_amount * $discount_percentage) / 100;
        }

        if ($coupon_discount != null && $coupon_discount > 0) {
            $coupon_discount = min($coupon_discount, $total_service_amount);
            $total_service_amount -= $coupon_discount;
        }

        $finalCommissionAmount = 0;
        foreach ($employee->commissions as $comm) {
            if (isset($comm->mainCommission)) {
                $type = $comm->mainCommission->commission_type;
                $value = $comm->mainCommission->commission_value;
                
                if ($type == 'fixed') {
                    $finalCommissionAmount += $value;
                } else {
                    $finalCommissionAmount += ($value * $total_service_amount / 100);
                }
            }
        }

        return [
            'commission_data' => [
                'employee_id' => $employee_id,
                'commission_amount' => $finalCommissionAmount,
                'commission_status' => 'unpaid',
                'payment_date' => null,
            ],
            'employee_id' => $employee_id,
        ];
    }
}
