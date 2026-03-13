<?php

namespace Modules\Employee\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        $expert = $this->expert;
        $commission = $this->commissions->first();
        $commissionValue = $commission ? ($commission->mainCommission->id ?? null) : null;

        $joinedDate = \Carbon\Carbon::parse($this->created_at);
        $diff = $joinedDate->diff(\Carbon\Carbon::now());

        $experienceText = '';
        if ($diff->y > 0) {
            $experienceText = $diff->y . ' ' . ($diff->y > 1 ? __('frontend.years') : __('frontend.year'));
        } elseif ($diff->m > 0) {
            $experienceText = $diff->m . ' ' . ($diff->m > 1 ? __('frontend.months') : __('frontend.Month'));
        } else {
            $experienceText = $diff->d . ' ' . ($diff->d > 1 ? __('frontend.days') : __('frontend.day'));
        }

        return [
            'id' => $this->id,
            'staff_number' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'expert' => $expert,
            'expert_experience' => $experienceText,
            'date_of_birth' => $this->date_of_birth,
            'email_verified_at' => $this->email_verified_at,
            'profile_image' => $this->media->pluck('original_url')->first(),
            'status' => $this->status == 1 ? true : false,
            'is_banned' => $this->is_banned == 1 ? true : false,
            'is_manager' => $this->is_manager == 1 ? true : false,
            'commission' => $commissionValue,
            'show_in_calender' => (bool) ($this->show_in_calender ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'rating_star' => count($this->rating) > 0 ? (float) number_format(max($this->rating->avg('rating'), 0), 2) : 0,
            'service_count' => $this->services_count ?? 0,
            'about_self' => $this->profile->about_self ?? null,
            'facebook_link' => $this->profile->facebook_link ?? null,
            'instagram_link' => $this->profile->instagram_link ?? null,
            'twitter_link' => $this->profile->twitter_link ?? null,
            'dribbble_link' => $this->profile->dribbble_link ?? null,
        ];
    }
}
