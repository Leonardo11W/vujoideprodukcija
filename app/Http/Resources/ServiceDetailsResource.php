<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'service_image' => $this->media->pluck('original_url')->first(),
            'duration_min' => $this->duration_min,
            'service_duration' => $this->duration_min,
            'default_price' => $this->default_price,
            'category_name' => $this->category->name ?? null,
            'sub_category_name' => $this->sub_category->name ?? null,
            'branch_count' => $this->branch_count ?? $this->branches->count(),
            'status' => $this->status,
             'employee' => $this->employee ? EmployeeDetailsResource::collection($this->employee->pluck('employee')) : [],
             'branches'=> BranchListDetailsResource::collection($this->branches->pluck('branch')),
            'multi_image' => $this->gallery ? $this->gallery->pluck('full_url')->toArray() : [],

        ];
    }
}
