<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class BranchDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        $is_festival_holiday = 0;
        $current_date = Carbon::now()->format('Y-m-d');


        if ($this->holidays->contains(function ($holiday) use ($current_date) {
            return $holiday->date === $current_date;
        })) {
            $is_festival_holiday = 1;
        }

        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $holidayDaysInWeek = $this->holidays->filter(function ($holiday) use ($startOfWeek, $endOfWeek) {
            return Carbon::parse($holiday->date)->between($startOfWeek, $endOfWeek);
        })->map(function ($holiday) {
            return Carbon::parse($holiday->date)->dayOfWeek;
        })->unique()->values();

        $workingDays = $this->businessHours->map(function ($hour) use ($holidayDaysInWeek, $current_date) {

            $dayAsInt = is_numeric($hour['day']) ? intval($hour['day']) : Carbon::parse($hour['day'])->dayOfWeek;

            $dayDate = Carbon::now()->startOfWeek()->addDays($dayAsInt - 1)->format('Y-m-d');

            $isFestivalHoliday = $this->holidays->contains(function ($holiday) use ($dayDate) {
                return $holiday->date === $dayDate;
            }) ? 1 : 0;

            return [
                'day' => $hour['day'],
                'start_time' => $hour['start_time'],
                'end_time' => $hour['end_time'],
                'is_holiday' => $hour['is_holiday'],
                'is_festival_holiday' => $isFestivalHoliday,
                'breaks' => $hour['breaks'],
            ];
        });

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'address_line_1' => $this->address->address_line_1,
            'latitude' => $this->address->latitude,
            'longitude' => $this->address->longitude,
            'city' => optional($this->address)->city_data->name ?? null,
            'state' => optional($this->address)->state_data->name  ?? null,
            'country' => optional($this->address)->country_data->name ?? null,
            'contact_email' => $this->contact_email,
            'contact_number' => $this->contact_number,
            'description' => $this->description,
            'payment_method' => $this->payment_method,
            'manager_id' => $this->manager_id,
            'branch_for' => $this->branch_for,
            'branch_image' => $this->media->pluck('original_url')->first(),
            'gallery' => $this->gallerys->pluck('full_url'),
            'rating_star' => round(($this->average_rating), 1),
            'is_festival_holiday' => $is_festival_holiday,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'deleted_by' => $this->deleted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'working_days' => $workingDays,
        ];
    }
}
