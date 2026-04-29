<?php

namespace Modules\Booking\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Promotion\Models\UserCouponRedeem;
use Carbon\Carbon;
class BookingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $employee_id=optional($this->booking_service->first())->employee_id?? optional($this->bookingPackages->first())->employee_id;
        $primaryAmount = ($this->booking_service ? $this->booking_service->sum('service_price') : 0) + ($this->bookingPackages ? $this->bookingPackages->sum('package_price') : 0);
        $couponRaw = UserCouponRedeem::where('booking_id', $this->id)->value('discount');
        $couponAmount = $couponRaw !== null && $couponRaw !== '' ? (float) $couponRaw : 0;
        $couponcut_amount = $primaryAmount - $couponAmount;
        $tax_details = getBookingTaxamount(
            $primaryAmount + ($this->products ? $this->products->sum('discounted_price') : 0),
            $couponAmount,
            $this->payment ? $this->payment->tax_percentage : null
        );

        try {
            $formattedStart = $this->start_date_time
                ? Carbon::parse($this->start_date_time)->format('j F Y \a\t H:i')
                : '-';
        } catch (\Throwable $e) {
            $formattedStart = '-';
        }
  return [
            'id' => $this->id,
            'booking_id' => get_formatted_booking_id($this->id),
            'note' => $this->note,
            'start_date_time' => $formattedStart,
            'branch_id' => $this->branch_id,
            'branch_name' => optional($this->branch)->name ?? '-',
            'address_line_1' => optional(optional($this->branch)->address)->address_line_1 ?? '-',
            'address_line_2' => optional(optional($this->branch)->address)->address_line_2 ?? '-',
            'phone' => optional($this->branch)->contact_number ?? '-',
            'employee_id' => optional($this->booking_service->first())->employee_id?? optional($this->bookingPackages->first())->employee_id?? '-',
           'employee_name' => optional($this->booking_service->first()?->employee)->full_name
                ?? optional($this->bookingPackages->first()?->employee)->full_name
                ?? '-',

            'employee_image' => optional($this->booking_service->first()?->employee)->profile_image
                ?? optional($this->bookingPackages->first()?->employee)->profile_image
                ?? '-',
            'services' => $this->booking_service->isNotEmpty()
            ? $this->booking_service->map(function ($booking_service) {
                unset($booking_service['employee']);
                $svc = $booking_service->service;
                $booking_service['service_name'] = optional($svc)->name ?? '-';
                $booking_service['service_image'] = optional($svc)->feature_image ?? '-';
                unset($booking_service['service']);
                return $booking_service;
            })
            :($this->bookingPackages->isNotEmpty()
            ? $this->bookingPackages->flatMap(function ($bookingPackage) {
                try {
                    $payload = (new BookingPackageResource($bookingPackage))->toArray(request());

                    return $payload['services'] ?? [];
                } catch (\Throwable $e) {
                    return [];
                }
            })
            : []),
            'user_id' => $this->user_id,
            'user_name' => optional($this->user)->full_name ?? default_user_name(),
            'user_profile_image' => optional($this->user)->profile_image ?? default_user_avatar(),
            'user_created' => optional($this->user)->created_at,
            'status' => $this->status,
            'created_by_name' => optional($this->createdUser)->full_name ?? default_user_name(),
            'updated_by_name' => optional($this->updatedUser)->full_name ?? default_user_name(),
            'created_at' => $this->created_at ? date('D, M Y', strtotime((string) $this->created_at)) : '-',
            'updated_at' => $this->updated_at ? date('D, M Y', strtotime((string) $this->updated_at)) : '-',
            'payment' => $this->payment,
            'sumOfServicePrices' => $this->booking_service ? $this->booking_service->sum('service_price') : 0,
            'sumOfProductPrices' => $this->products ? $this->products->sum('discounted_price') : 0,
            'tax_amount' => $tax_details['total_tax_amount'],
            'total_amount' => ( ($primaryAmount + ($this->products ? $this->products->sum('discounted_price') : 0) + $tax_details['total_tax_amount'] + ($this->payment ? $this->payment->tip_amount : 0)))-$couponAmount,
            'coupon_amount' => $couponAmount,
            'sumOfPackagesPrices'=>$this->bookingPackages ? $this->bookingPackages->sum('package_price') : 0,
            'packages' => $this->bookingPackages->isNotEmpty()
                ? BookingPackageResource::collection($this->bookingPackages)
                : [],
        ];
    }
}
