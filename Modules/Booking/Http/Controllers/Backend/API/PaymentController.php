<?php

namespace Modules\Booking\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Commission\Models\CommissionEarning;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackage;
use Modules\Tip\Models\TipEarning;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;
use Illuminate\Support\Facades\Log;


class PaymentController extends Controller
{
    use PaymentTrait;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Payment';
    }

    public function savePayment(Request $request)
    {
        try {
            $data = $request->all();
            $data['tip_amount'] = $data['tip'] ?? 0;

            $booking = Booking::with(['services', 'bookingPackages', 'products', 'userCouponRedeem'])
                ->where('id', $data['booking_id'])
                ->first();

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found.'
                ], 404); // Not Found
            }

            $incomingTaxes = $data['tax_percentage'] ?? [];
            if (is_string($incomingTaxes)) {
                $decoded = json_decode($incomingTaxes, true);
                $incomingTaxes = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($incomingTaxes)) {
                $incomingTaxes = [];
            }

            $serviceAmount = $booking->services ? $booking->services->sum('service_price') : 0;

            $productAmount = 0;
            if ($booking->products) {
                $totalProductAmount = $booking->products->sum(function ($product) {
                    return ($product->product_qty ?? 0) * ($product->product_price ?? 0);
                });
                $discountedProductAmount = getproductDiscountAmount($booking->products);
                $productAmount = max($totalProductAmount - $discountedProductAmount, 0);
            }
            $packageAmount = 0;
            if ($booking->bookingPackages) {
                $packageAmount = $booking->bookingPackages
                    ->filter(function ($package) {
                        return ($package->is_reclaim ?? 0) == 0;
                    })
                    ->sum('package_price');
            }

            $baseAmount = max($serviceAmount + $productAmount + $packageAmount, 0);
            $couponDiscount = optional($booking->userCouponRedeem)->discount ?? 0;

            $taxData = empty($incomingTaxes) ? null : $incomingTaxes;
            $taxCalculation = getBookingTaxamount($baseAmount, $couponDiscount, $taxData);
            $resolvedTaxes = collect($taxCalculation['tax_details'] ?? [])->map(function ($tax) {
                $type = strtolower($tax['tax_type'] ?? $tax['type'] ?? 'percent');
                $percent = $type === 'percent' ? (float) ($tax['tax_value'] ?? $tax['percent'] ?? 0) : 0;
                $amount = (float) ($tax['tax_amount'] ?? $tax['amount'] ?? 0);

                return [
                    'name' => $tax['tax_name'] ?? $tax['name'] ?? 'Tax',
                    'type' => $type,
                    'percent' => $percent,
                    'amount' => $amount,
                    'tax_amount' => $amount,
                ];
            })->values()->toArray();

            $data['tax_percentage'] = $resolvedTaxes;

            $payment = BookingTransaction::create($data);

            if ($data['transaction_type'] == 'wallet') {

                $tax_percentage = $data['tax_percentage'] ?? [];

                $total_amount = $this->getTotalAmount($data['booking_id'], $tax_percentage, $data['tip']);
                $user_id = Booking::find($data['booking_id'])->user_id;

                if (!$user_id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'User not found.'
                    ], 404); // Not Found
                }

                $wallet = Wallet::where('user_id', $user_id)->first();

                if (!$wallet) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Wallet not found.'
                    ], 404); // Not Found
                }

                if ($wallet->amount >= $total_amount) {
                    $wallet->amount -= $total_amount;
                    if ($wallet->save()) {
                        // $activity_message = 'Wallet payment of ' . $total_amount;
                        $activity_message = __('messages.wallet_paid') . $data['booking_id'];

                        $activity_data = [
                            'title' => $wallet->title,
                            'amount' => $wallet->amount,
                            'transaction_id' => $data['external_transaction_id'],
                            'transaction_type' => 'wallet',
                            'credit_debit_amount' => (float) $total_amount,
                            'transaction_type' => 'debit',
                        ];
                        $walletHistoryData = [
                            'user_id' => $wallet->user_id,
                            'datetime' => now(),
                            'activity_type' => 'debit',
                            'activity_message' => $activity_message,
                            'activity_data' => json_encode($activity_data),
                        ];
                        WalletHistory::create($walletHistoryData);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Failed to update wallet balance.'
                        ], 500); // Internal Server Error
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient wallet balance.'
                    ], 400); // Bad Request
                }
            }

            $earning_data = $this->commissionData($payment);
            $this->storeApiUserPackage($data['booking_id']);

            if (isset($earning_data['commission_data']) && $earning_data['commission_data'] != null) {
                $booking->commission()->save(new CommissionEarning($earning_data['commission_data']));
            }

            if ($data['tip_amount'] > 0) {
                $booking->tip()->save(new TipEarning([
                    'employee_id' => $earning_data['employee_id'],
                    'tip_amount' => number_format($data['tip_amount'], 2),
                    'tip_status' => 'unpaid',
                    'payment_date' => null,
                ]));
            }

            return response()->json([
                'message' => __('booking.payment_done'),
                'status' => true
            ], 200); // OK
        } catch (\Exception $e) {
            // Log the exception for debugging purposes

            return response()->json([
                'status' => false,
                'message' => 'An error occurred while processing the payment.'
            ], 500); // Internal Server Error
        }
    }
}
