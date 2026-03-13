<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class WalletResource extends JsonResource
{
    public function toArray($request)
    {
        $timezone = setting('default_time_zone') ?? 'UTC';
        $activityData = $this->activity_data ?? null;

        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'datetime'         => Carbon::parse($this->datetime)->setTimezone($timezone)->toDateTimeString(),
            'activity_type'    => $this->activity_type,
            'activity_message' => $this->activity_message,
            'activity_data'    => $activityData,
            'created_at'       => $this->created_at ? $this->created_at->timezone($timezone)->toDateTimeString() : null,
            'updated_at'       => $this->updated_at ? $this->updated_at->timezone($timezone)->toDateTimeString() : null,
        ];
    }
}
