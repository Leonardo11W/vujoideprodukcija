<?php

namespace Modules\Product\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [

            'id' => $this->id,
            'user_id' => $this->user_id,
            'delivery_status' => str_replace('order_', '', $this->delivery_status),
            'payment_status' => $this->payment_status,
            'total_amount' => $this->total_admin_earnings,
            'order_code' => optional($this->orderGroup)->formatted_order_code,
            'order_date' => $this->created_at,
            'product_details' => OrderItemResource::collection($this->orderItems),

        ];
    }
}
