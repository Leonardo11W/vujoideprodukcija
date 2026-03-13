<?php

namespace Modules\Booking\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProduct;
use Modules\Booking\Models\BookingService as BookingServiceModel;
use Modules\Booking\Models\BookingTransaction;
use Modules\Package\Models\UserPackage;
use Modules\Promotion\Models\UserCouponRedeem;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackageServices;
use Modules\Package\Models\BookingPackageService;

class BookingService
{
    /**
     * Calculate total amount for a booking
     */
    public function getTotalAmount($booking_id, $tax = [], $tip_amount = 0)
    {
        $booking_services = BookingServiceModel::where('booking_id', $booking_id)->get();
        $total_service_amount = $booking_services->sum('service_price');
        
        $booking_products = BookingProduct::where('booking_id', $booking_id)->with('product')->get();
        $total_product_amount = BookingProduct::where('booking_id', $booking_id)->sum(DB::raw('product_qty * product_price'));
        $discounted_product_amount = getproductDiscountAmount($booking_products);
        $product_amount = $total_product_amount - $discounted_product_amount;

        $booking_packages = UserPackage::where('booking_id', $booking_id)->get();
        $total_package_amount = $booking_packages->sum('package_price');

        // Check if coupon was applied
        $coupon_discount = UserCouponRedeem::where('booking_id', $booking_id)->value('discount');
        if ($coupon_discount != null) {
            $total_service_amount = max(0, $total_service_amount - $coupon_discount);
        }

        $tax_amount = 0;
        if (!empty($tax) && is_array($tax)) {
            foreach ($tax as $tax_value) {
                if ($tax_value['type'] == 'percent') {
                    $tax_amount += (($total_service_amount + $product_amount + $total_package_amount) * $tax_value['percent'] / 100);
                } elseif ($tax_value['type'] == 'fixed') {
                    $tax_amount += ($tax_value['tax_amount'] ?? $tax_value['amount'] ?? 0);
                }
            }
        }

        $total_amount = $total_service_amount + $tax_amount + $tip_amount + $product_amount + $total_package_amount;
        return number_format($total_amount, 2, '.', '');
    }

    /**
     * Get booking details for display or invoice
     */
    public function getBookingDetail($booking)
    {
        $bookingTransaction = BookingTransaction::where('booking_id', $booking->id)->where('payment_status', 1)->first();
        $booking_product = BookingProduct::where('booking_id', $booking->id);

        $coupon_discount = $booking->userCouponRedeem['discount'] ?? 0;
        $total_product_amount = $booking_product ? $booking_product->sum(DB::raw('product_qty * product_price')) : 0;
        
        $serviceAmount = $booking->services->sum('service_price');
        $packageAmount = $booking->packages->sum('package_price');

        $tax_amount = 0;
        $tip_amount = 0;
        if (!empty($bookingTransaction)) {
            $taxes = is_array($bookingTransaction->tax_percentage) ? $bookingTransaction->tax_percentage : [];
            foreach ($taxes as $tax) {
                if ($tax['type'] == 'percent') {
                    $tax_amount += ((($serviceAmount - $coupon_discount) + $total_product_amount + $packageAmount) * $tax['percent']) / 100;
                } else {
                    $tax_amount += $tax['tax_amount'] ?? $tax['amount'] ?? 0;
                }
            }
            $tip_amount = $bookingTransaction->tip_amount;
        }

        return [
            'serviceAmount' => $serviceAmount,
            'bookingTransaction' => $bookingTransaction,
            'sumDiscountedPrice' => $total_product_amount,
            'tax_amount' => $tax_amount,
            'coupon_discount' => $coupon_discount,
            'packageAmount' => $packageAmount,
            'grand_total' => ($tax_amount + $total_product_amount + $serviceAmount + $tip_amount + $packageAmount) - $coupon_discount,
        ];
    }

    /**
     * Store package information for a booking
     */
    public function storeUserPackage($booking_id)
    {
        $packages = BookingPackages::where('booking_id', $booking_id)->get();
        foreach ($packages as $key => $value) {
            $existingUserPackage = UserPackage::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->exists();
            if (!$existingUserPackage) {
                $userPackage = UserPackage::create([
                    'sequance' => $key,
                    'booking_id' => $booking_id,
                    'package_id' => $value['package_id'],
                    'purchase_date' => Carbon::now(),
                    'employee_id' => $value['employee_id'],
                    'package_price' => $value['package_price'],
                    'user_id' => $value['user_id'],
                ]);

                $packageServices = PackageService::where('package_id', $value['package_id'])->get();
                BookingPackageService::where('booking_package_id', $value['id'])->delete();
                foreach ($packageServices as $service) {
                    $userPackageService = UserPackageServices::create([
                        'package_service_id' => $service->id,
                        'package_id' => $service->package_id,
                        'user_id' => $value['user_id'],
                        'qty' => $service->qty - 1,
                        'service_name' => $service->service_name,
                        'user_package_id' => $userPackage->id,
                    ]);

                    BookingPackageService::create([
                        'booking_id' => $booking_id,
                        'package_id' => $userPackageService->package_id,
                        'package_service_id' => $userPackageService->package_service_id,
                        'user_id' => $userPackageService->user_id,
                        'service_name' => $userPackageService->service_name,
                        'booking_package_id' => $value['id'],
                        'qty' => $userPackageService->qty,
                        'service_id' => $service['service_id'],
                    ]);
                    
                    if ($userPackageService && $userPackageService->qty == 0) {
                        $userPackageService->delete();
                    }
                }
            } else if ($value['is_reclaim'] === 1) {
                BookingPackageService::where('booking_package_id', $value['id'])->delete();
                $userPackage = UserPackage::where('user_id', $value['user_id'])->where('package_id', $value['package_id'])->first();
                $userPackageServices = UserPackageServices::where('user_package_id', $userPackage->id)->get();
                
                foreach ($userPackageServices as $service) {
                    if ($service && $service->qty >= 1) {
                        BookingPackageService::create([
                            'booking_id' => $booking_id,
                            'package_id' => $service->package_id,
                            'package_service_id' => $service->package_service_id,
                            'user_id' => $service->user_id,
                            'service_name' => $service->service_name,
                            'booking_package_id' => $value['id'],
                            'qty' => $service->qty - 1,
                            'service_id' => $service->packageService->service_id,
                        ]);
                        $service->qty -= 1;
                        $service->save();
                    }
                    if ($service->qty == 0) {
                        $service->delete();
                    }
                }
                $userPackage->type = 'reclaimed';
                $userPackage->save();
                
                if (UserPackageServices::where('user_package_id', $userPackage->id)->count() == 0) {
                    $userPackage->delete();
                }
            }
        }
    }
}
