<?php

namespace Modules\Promotion\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        // dd($this);
        return [
            'id' => $this->id,
            'image_url' => $this->feature_image,
            'description' => $this->description,
            'coupon_code' => optional($this->coupon->first())->coupon_code,
            'is_expired' => optional($this->coupon->first())->is_expired,
            'discount_type' => optional($this->coupon->first())->discount_type,
            'discount_percentage' =>optional($this->coupon->first())->discount_percentage,
            'discount_amount' => optional($this->coupon->first())->discount_amount,
            'use_limit' => optional($this->coupon->first())->use_limit,
            'used_by' => optional($this->coupon->first())->used_by,
            'promotion_id' => optional($this->coupon->first())->promotion_id,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'valid_until' => optional($this->coupon->first())->end_date_time ? \Carbon\Carbon::parse(optional($this->coupon->first())->end_date_time)->format(setting('date_format') ?? 'Y-m-d') : null,        
        ];
    }

}