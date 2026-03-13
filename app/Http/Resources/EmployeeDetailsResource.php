<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Employee\Models\EmployeeRating;

class EmployeeDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rate = EmployeeRating::where('employee_id', $this->id)->get();
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'profile_image' => $this->getFirstMediaUrl('profile_image'),
            'rating' => $rate->avg('rating'),
            'expert'=> $this->expert,

        ];
    }
}
