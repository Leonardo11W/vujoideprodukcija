<?php

namespace App\Exports;

use App\Models\User;
use App\Currency\CurrencyFacades as Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class EarningExport implements FromCollection, WithHeadings, WithStyles
{
    public array $columns;

    public array $dateRange;

    public ?int $branchId;

    public function __construct($columns, $dateRange, $branchId = null)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->branchId = $branchId;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        foreach ($this->columns as $column) {
            // Capitalize each word and replace underscores with spaces
            $modifiedHeadings[] = ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $authUser = auth()->user();
        $isManager = $authUser ? $authUser->hasRole('manager') : false;
        $isEmployee = $authUser ? $authUser->hasRole('employee') : false;
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // Managers in My Work, or employees, see only their own earnings
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee && $authUser ? $authUser->id : null;

        // Get branch ID from property or request/session
        $selectedBranchId = $this->branchId ?? (request()->selected_session_branch_id ?? session('selected_branch'));
        $limitByBranch = $selectedBranchId && $isManager && !$isManagerMyWork;

        $query = User::select(
                'users.id',
                DB::raw('MAX(users.first_name) as first_name'),
                DB::raw('MAX(users.last_name) as last_name'),
                DB::raw('MAX(users.email) as email'),
                DB::raw('(SELECT COUNT(*) FROM commission_earnings WHERE commission_earnings.employee_id = users.id AND commission_earnings.commission_status = "unpaid") as totalBookings'),
                DB::raw('COALESCE(SUM(booking_services.service_price), 0) + COALESCE(SUM(booking_packages.package_price), 0) as total_service_amount')
            )
            ->leftJoin('commission_earnings', 'users.id', '=', 'commission_earnings.employee_id')
            ->leftJoin('bookings', 'bookings.id', '=', 'commission_earnings.commissionable_id')
            ->leftJoin('booking_services', 'booking_services.booking_id', '=', 'commission_earnings.commissionable_id')
            ->leftJoin('booking_packages', 'booking_packages.booking_id', '=', 'commission_earnings.commissionable_id')
            ->where('commission_earnings.commission_status', 'unpaid')
            ->where('bookings.status', 'completed')
            ->whereNull('users.deleted_at')
            ->when(
                $filterByEmployee,
                function ($query) use ($employeeId) {
                    $query->where('users.id', $employeeId);
                }
            )
            ->when(
                $limitByBranch,
                function ($query) use ($selectedBranchId) {
                    // Filter to only include staff assigned to the specified branch
                    $query->join('branch_employee as be', function ($join) use ($selectedBranchId) {
                        $join->on('be.employee_id', '=', 'users.id')
                            ->where('be.branch_id', $selectedBranchId)
                            ->whereNull('be.deleted_at');
                    })->where('bookings.branch_id', $selectedBranchId);
                }
            )
            // Managers viewing with a branch selected should see only staff (exclude themselves)
            ->when(
                $isManager && !$isManagerMyWork && $selectedBranchId,
                function ($query) use ($authUser) {
                    $query->where('users.id', '!=', $authUser->id);
                }
            )
            ->groupBy('users.id')
            ->orderBy('users.updated_at', 'desc');

        $query->whereDate('users.created_at', '>=', $this->dateRange[0]);
        $query->whereDate('users.created_at', '<=', $this->dateRange[1]);

        $query = $query->with(['commission_earning', 'tip_earning'])->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'name':
                        $selectedData[$column] = $row->first_name . ' ' . $row->last_name;
                        break;

                    case 'email':
                        $selectedData[$column] = $row->email ?? '-';
                        break;

                    case 'total_booking':
                        $selectedData[$column] = ($row->totalBookings ?? 0) + ($row->totalPackageBookings ?? 0);
                        break;

                    case 'total_service_amount':
                        $selectedData[$column] = Currency::format($row->total_service_amount ?? 0);
                        break;

                    case 'total_commission_earn':
                        $commissionAmount = $row->commission_earning
                            ->where('commission_status', 'unpaid')
                            ->filter(function($c){ return optional($c->getbooking)->status === 'completed'; })
                            ->sum('commission_amount');
                        $selectedData[$column] = Currency::format($commissionAmount);
                        break;

                    case 'total_tips_earn':
                        $tipAmount = $row->tip_earning->where('tip_status', 'unpaid')->sum('tip_amount');
                        $selectedData[$column] = Currency::format($tipAmount);
                        break;

                    case 'total_pay':
                        $commissionAmount = $row->commission_earning
                            ->where('commission_status', 'unpaid')
                            ->filter(function($c){ return optional($c->getbooking)->status === 'completed'; })
                            ->sum('commission_amount');
                        $tipAmount = $row->tip_earning->where('tip_status', 'unpaid')->sum('tip_amount');
                        $selectedData[$column] = Currency::format($commissionAmount + $tipAmount);
                        break;

                    default:
                        $selectedData[$column] = $row[$column] ?? '-';
                        break;
                }
            }

            return $selectedData;
        });

        return $newQuery;
    }

    public function styles(Worksheet $sheet)
    {
        applyExcelStyles($sheet);
    }
}
