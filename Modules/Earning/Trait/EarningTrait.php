<?php

namespace Modules\Earning\Trait;

trait EarningTrait
{
    public function getUnpaidAmount($data, $type = null)
    {
        $classData = new \stdClass();
        $selectedBranchId = request()->selected_session_branch_id;
        $authUser = auth()->user();
        $limitByBranch = $selectedBranchId && $authUser && $authUser->hasRole('manager');

        $commissionQuery = $data->commission_earning()
            ->where('commission_status', 'unpaid')
            ->whereHas('getbooking', function($q) use ($limitByBranch, $selectedBranchId) {
                $q->where('status','completed');
                if ($limitByBranch) {
                    $q->where('branch_id', $selectedBranchId);
                }
            });

        switch ($type) {
            case 'tip':
                return $data->tip_earning()->where('tip_status', 'unpaid')->sum('tip_amount');
                break;
            case 'commission':
                // Only count commissions for completed bookings
                return $commissionQuery->sum('commission_amount');
                break;
            default:
                $classData->total_commission_earn = $commissionQuery->sum('commission_amount');
                $classData->total_tips_earn = $data->tip_earning()->where('tip_status', 'unpaid')->sum('tip_amount');
                $classData->total_pay = $classData->total_commission_earn + $classData->total_tips_earn;

                return $classData;
                break;
        }

        return 0;
    }
}
