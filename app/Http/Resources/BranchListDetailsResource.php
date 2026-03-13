<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
class BranchListDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
    $timezone = setting('time_zone') ?? 'UTC'; // Change this to your local timezone if needed
    $today = \Carbon\Carbon::now($timezone)->format('l');
    $now = \Carbon\Carbon::now($timezone);
    $hours = \Modules\BussinessHour\Models\BussinessHour::where('branch_id', $this->id)
        ->whereRaw('LOWER(day) = ?', [strtolower($today)])
        ->first();
    $isOpen = false;
    if ($hours && $hours->is_holiday != 1 && $hours->start_time && $hours->end_time) {
        $start = \Carbon\Carbon::parse($hours->start_time, $timezone);
        $end = \Carbon\Carbon::parse($hours->end_time, $timezone);
        $isOpen = $now->between($start, $end);
        // Check breaks
        if ($isOpen && !empty($hours->breaks)) {
            $breaks = is_array($hours->breaks) ? $hours->breaks : json_decode($hours->breaks, true);
            foreach ($breaks as $break) {
                if (!empty($break['start']) && !empty($break['end'])) {
                    $breakStart = \Carbon\Carbon::parse($break['start'], $timezone);
                    $breakEnd = \Carbon\Carbon::parse($break['end'], $timezone);
                    if ($now->between($breakStart, $breakEnd)) {
                        $isOpen = false;
                        break;
                    }
                }
            }
        }
    }

        $employeeIds = BranchEmployee::where('branch_id', $this->id)
            ->distinct()
            ->pluck('employee_id');

        $averageRating = EmployeeRating::whereIn('employee_id', $employeeIds)->avg('rating');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->when($this->address, function () {
                        return collect([
                            $this->address?->address_line_1,
                            $this->address?->city_data?->name,
                            $this->address?->state_data?->name,
                            $this->address?->country_data?->name,
                        ])->filter()->implode(', ');
                    }),
            'email' => $this->contact_email,
            'branch_for' => $this->branch_for,
            'status' => $this->status,
            'is_open' => $isOpen,
            'rating_star' => round(($averageRating), 1) ?? 0,
            'image'=> $this->media->pluck('original_url')->first(),
            'latitude' => $this->address?->latitude,
            'longitude' => $this->address?->longitude,
        ];
    }
}
