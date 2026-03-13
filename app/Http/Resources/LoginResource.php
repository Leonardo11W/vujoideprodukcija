<?php

namespace App\Http\Resources;

use App\Models\Branch;
use Modules\Employee\Models\BranchEmployee;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Check if user is a manager from database role
        $isManager = $this->hasRole('manager');
        
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'mobile' => $this->country_code ? ($this->mobile && strpos($this->mobile, $this->country_code) === 0 ? substr_replace($this->mobile, ' ', strlen($this->country_code), 0) : $this->country_code . ' ' . $this->mobile) : ($this->mobile && preg_match('/^(\+\d{1,3})(\d+)$/', $this->mobile, $matches) ? $matches[1] . ' ' . $matches[2] : $this->mobile),
            'email' => $this->email,
            'gender' => $this->gender,
            'user_role' => $this->getRoleNames() ?? [],
            'is_manager' => $isManager,
            'api_token' => $this->api_token,
            'profile_image' => $this->media->pluck('original_url')->first() ?? $this->avatar,
            'login_type' => $this->login_type,
            // 'selected_branch_id' => session('selected_branch_id'),
        ];

        // If user is a manager, include assigned branches
        if ($isManager) {
            $assignedBranches = $this->getAssignedBranches();
            $data['assigned_branch_data'] = $assignedBranches;
        }

        return $data;
    }

    /**
     * Get all branches assigned to the manager
     *
     * @return array
     */
    protected function getAssignedBranches()
    {
        // Get branches where manager_id matches (owned/managed branches)
        $managedBranchIds = Branch::where('manager_id', $this->id)->pluck('id')->toArray();
        
        // Get branches through branch_employee table (assigned as employee)
        $assignedBranchIds = BranchEmployee::where('employee_id', $this->id)->pluck('branch_id')->toArray();
        
        // Merge and get unique branch IDs
        $allBranchIds = array_unique(array_merge($managedBranchIds, $assignedBranchIds));
        
        if (empty($allBranchIds)) {
            return [];
        }

        // Load branches with address relationship (address relationship already includes city_data, state_data, country_data)
        $branches = Branch::with('address.city_data', 'address.state_data', 'address.country_data')
            ->whereIn('id', $allBranchIds)
            ->get();
        
        return $branches->map(function ($branch) {
            $address = $branch->address;
            $addressString = '';
            
            if ($address) {
                // Build address string from available parts
                $addressParts = array_filter([
                    $address->address_line_1,
                    $address->address_line_2,
                    optional($address->city_data)->name ?? (is_numeric($address->city) ? null : $address->city),
                    optional($address->state_data)->name ?? (is_numeric($address->state) ? null : $address->state),
                    optional($address->country_data)->name ?? (is_numeric($address->country) ? null : $address->country),
                    $address->postal_code,
                ]);
                $addressString = implode(', ', $addressParts);
            }
            
            return [
                'branch_id' => $branch->id,
                'brand_name' => $branch->name,
                'address' => $addressString ?: ($address ? ($address->address_line_1 ?? '') : ''),
            ];
        })->toArray();
    }
}
