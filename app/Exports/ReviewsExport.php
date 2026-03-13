<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Employee\Models\EmployeeRating;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class ReviewsExport implements FromCollection, WithHeadings,WithStyles
{
    public array $columns;

    public array $dateRange;

    public function __construct($columns, $dateRange)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map technical keys to translation keys for headings
        $labelMap = [
            'user_id' => __('export.columns.customer'),
            'employee_id' => __('export.columns.staff'),
            'review_msg' => __('export.columns.review_message'),
            'rating' => __('export.columns.rating'),
            'updated_at' => __('export.columns.updated_date'),
        ];

        foreach ($this->columns as $column) {
            // Use mapped label if available; otherwise, humanize the key
            $modifiedHeadings[] = $labelMap[$column] ?? ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = EmployeeRating::with('user', 'employee');

        $query->whereDate('created_at', '>=', $this->dateRange[0]);

        $query->whereDate('created_at', '<=', $this->dateRange[1]);

        // Filter by employee if employee is logged in
        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        
        // Filter by employee if employee is logged in (but not if they're also a manager in my work mode - handled below)
        if ($authUser && $isEmployee && !$isManager) {
            $query->where('employee_id', $authUser->id);
        }
        
        // Filter by branch if manager is logged in (including managers who are also admins)
        if ($isManager) {
            // If "my work" mode is active, show only reviews where manager is the employee
            if ($isManagerMyWork) {
                $query->where('employee_id', $authUser->id);
            } else {
                // Get branch ID from request or session (ignored in my work mode)
                $selectedBranchId = request()->selected_session_branch_id ?? session('selected_branch');
                
                if ($selectedBranchId) {
                    // Filter reviews through BranchEmployee relationship
                    $query->whereHas('employee', function ($empQuery) use ($selectedBranchId) {
                        $empQuery->whereHas('branches', function ($branchQuery) use ($selectedBranchId) {
                            $branchQuery->where('branch_id', $selectedBranchId);
                        });
                    });
                }
            }
        }

        $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'user_id':
                        $selectedData[$column] = isset($row->user->full_name) ? $row->user->full_name : '-';
                        break;

                    case 'employee_id':
                        $selectedData[$column] = isset($row->employee->full_name) ? $row->employee->full_name : '-';
                        break;

                    case 'updated_at':
                        // Always show the updated date in export, regardless of time difference
                        $selectedData[$column] = customDate($row->updated_at);
                        break;
                    default:
                        $selectedData[$column] = $row[$column];
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
