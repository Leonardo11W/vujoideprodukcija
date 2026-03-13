<?php

namespace Modules\Booking\Trait;

use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingTransaction;
use Modules\Commission\Models\CommissionEarning;
use Modules\Currency\Models\Currency;
use Modules\Tip\Models\TipEarning;
use Modules\Booking\Transformers\BookingResource;
use Modules\Booking\Services\BookingService;
use Modules\Booking\Services\PaymentService;
use Modules\Booking\Services\CommissionService;
use Modules\Booking\Services\CouponService;

trait PaymentTrait
{
    /**
     * Get Payment Method (Main Entry)
     */
    public function getpayment_method($data, $booking_id)
    {
        $data['booking_id'] = $booking_id;
        $data['transaction_type'] = $data['payment_method'];
        $data['tip_amount'] = $data['tip'] ?? 0;
        $data['tax_percentage'] = $data['taxes'];
        $data['coupon_code'] = $data['coupon_code'];

        $booking_transaction_data = BookingTransaction::where('booking_id', $booking_id)->first();
        $commissionService = new CommissionService();

        if (!empty($booking_transaction_data)) {
            $booking_transaction_data->update($data);
            $booking_transaction = $booking_transaction_data;
            $booking = Booking::find($booking_id);

            $earning_data = $commissionService->calculateCommission($booking_transaction, $data['coupondiscount'] ?? null);

            if (isset($earning_data['commission_data'])) {
                $existing_commission = $booking->commission;
                if ($existing_commission) {
                    if ($existing_commission->commission_status === 'unpaid') {
                        $existing_commission->update([
                            'commission_amount' => $earning_data['commission_data']['commission_amount'],
                        ]);
                    }
                } else if (isset($earning_data['employee_id'])) {
                    $commission_data = $earning_data['commission_data'];
                    $commission_data['employee_id'] = $earning_data['employee_id'];
                    $booking->commission()->save(new CommissionEarning($commission_data));
                }
            }

            if ($data['tip_amount'] > 0) {
                $existing_tip = $booking->tip;
                if ($existing_tip) {
                    if ($existing_tip->tip_status === 'unpaid') {
                        $existing_tip->update(['tip_amount' => $data['tip_amount']]);
                    }
                } else if (isset($earning_data['employee_id'])) {
                    $booking->tip()->save(new TipEarning([
                        'employee_id' => $earning_data['employee_id'],
                        'tip_amount' => $data['tip_amount'],
                        'tip_status' => 'unpaid',
                        'payment_date' => null,
                    ]));
                }
            }
        } else {
            $booking_transaction = BookingTransaction::create($data);
            $earning_data = $commissionService->calculateCommission($booking_transaction, $data['coupondiscount'] ?? null);
            $booking = Booking::find($booking_id);

            if (isset($earning_data['commission_data'])) {
                $booking->commission()->save(new CommissionEarning($earning_data['commission_data']));
            }

            if ($data['tip_amount'] > 0) {
                $booking->tip()->save(new TipEarning([
                    'employee_id' => $earning_data['employee_id'],
                    'tip_amount' => $data['tip_amount'],
                    'tip_status' => 'unpaid',
                    'payment_date' => null,
                ]));
            }
        }

        $bookingService = new BookingService();
        $couponService = new CouponService();
        
        $total_amount = $bookingService->getTotalAmount($booking_id, $data['taxes'], $data['tip']);
        $tipAmountInput = $data['tip'] ?? $data['tip_amount'] ?? 0;

        $couponDiscountamount = $data['coupondiscount'] ?? $couponService->getDiscount($total_amount, $data['coupon_code'], $booking_id, $tipAmountInput);

        $data['couponDiscountamount'] = $couponDiscountamount;
        $total_amount = max(0, $total_amount - $couponDiscountamount);
        $data['$total_amount'] = $total_amount;

        $currency = Currency::where('is_primary', 1)->first();

        // Switch Logic
        switch ($data['transaction_type']) {
            case 'razorpay':
                $keys = (new \Modules\Booking\Services\Gateways\RazorpayGateway())->getKeys();
                $responseData = [
                    'status' => true,
                    'booking_transaction_id' => $booking_transaction['id'],
                    'total_amount' => $total_amount,
                    'currency' => $currency['currency_code'] ?? 'USD',
                    'payment_method' => $data['payment_method'],
                    'public_key' => $keys['razorpay_publickey'] ?? '',
                ];
                break;

            case 'stripe':
                $responseData = [
                    'booking_transaction_id' => $booking_transaction['id'],
                    'total_amount' => $total_amount,
                    'currency' => $currency['currency_code'] ?? 'USD',
                    'payment_method' => $data['payment_method'],
                    'public_key' => (new \Modules\Booking\Services\Gateways\StripeGateway())->getKeys()['stripe_secretkey'] ?? '',
                ];
                break;

            default:
                $responseData = $this->getcashpayments($data, $booking_transaction['id']);
                break;
        }

        return $responseData;
    }

    public function getTotalAmount($booking_id, $tax = [], $tip_amount = 0)
    {
        return (new BookingService())->getTotalAmount($booking_id, $tax, $tip_amount);
    }

    public function couponDiscount($total_amount, $coupon_code, $booking_id, $tip_amount = 0)
    {
        return (new CouponService())->getDiscount($total_amount, $coupon_code, $booking_id, $tip_amount);
    }

    public function couponExpired($coupon_code, $couponDiscountamount, $booking_id)
    {
        return (new CouponService())->redeemCoupon($coupon_code, $couponDiscountamount, $booking_id);
    }

    public function storeApiUserPackage($booking_id)
    {
        return (new BookingService())->storeUserPackage($booking_id);
    }

    public function storeUserPackage($booking_id)
    {
        return (new BookingService())->storeUserPackage($booking_id);
    }

    public function getcashpayments($data, $booking_transaction_id)
    {
        (new CouponService())->redeemCoupon($data['coupon_code'], $data['couponDiscountamount'], $data['booking_id']);
        
        BookingTransaction::where('id', $booking_transaction_id)->update(['external_transaction_id' => '', 'payment_status' => 1]);
        Booking::where('id', $data['booking_id'])->update(['status' => 'completed']);
        
        $queryData = Booking::with('services', 'products', 'user')->findOrFail($data['booking_id']);
        $queryData['detail'] = (new BookingService())->getBookingDetail($queryData);
        
        $notify_message = str_replace('[[booking_id]]', $data['booking_id'], 'New booking #[[booking_id]] has been booked.');
        $this->sendNotificationOnBookingUpdate('complete_booking', $notify_message, $queryData);

        return [
            'message' => __('booking.payment_successfull'),
            'payment_method' => $data['payment_method'],
            'data' => new BookingResource($queryData),
            'status' => true,
        ];
    }

    public function getrazorpaypayments($data, $booking_transaction_id)
    {
        $gateway = new \Modules\Booking\Services\Gateways\RazorpayGateway();
        $result = $gateway->capture($data);

        if ($result['status']) {
            BookingTransaction::where('id', $booking_transaction_id)->update(['external_transaction_id' => $result['payment_id'], 'payment_status' => 1]);
            Booking::where('id', BookingTransaction::find($booking_transaction_id)->booking_id)->update(['status' => 'completed']);
            $queryData = Booking::with('services', 'user')->findOrFail(BookingTransaction::find($booking_transaction_id)->booking_id);
            
            return [
                'message' => __('booking.payment_successfull'),
                'booking' => new BookingResource($queryData),
                'status' => true,
            ];
        }

        return $result;
    }

    public function getstripepayments($data)
    {
        return (new \Modules\Booking\Services\Gateways\StripeGateway())->process($data);
    }

    public function getstripePaymnetId($request_token)
    {
        return (new \Modules\Booking\Services\Gateways\StripeGateway())->verify($request_token);
    }

    public function getrazorpaykey()
    {
        return (new \Modules\Booking\Services\Gateways\RazorpayGateway())->getKeys();
    }

    public function getstripekey()
    {
        return (new \Modules\Booking\Services\Gateways\StripeGateway())->getKeys();
    }

    public function commissionData($data, $manualCouponDiscount = null)
    {
        return (new CommissionService())->calculateCommission($data, $manualCouponDiscount);
    }

    protected function bookingDetail($booking)
    {
        return (new BookingService())->getBookingDetail($booking);
    }

    public function ExpireCoupon($data)
    {
        // This method seems to be used for API verification/redeem
        // Keeping it mostly as is but calling service if needed
        $couponService = new CouponService();
        $couponService->redeemCoupon($data['coupon_code'], $data['couponDiscountamount'], $data['booking_id']);
        return response()->json(['message' => 'Coupon redeemed successfully', 'status' => true], 200);
    }
}
