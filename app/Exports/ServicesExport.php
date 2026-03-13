<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Service\Models\Service;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesExport implements FromCollection, WithHeadings, WithStyles
{
    public array $columns;

    public array $dateRange;

    public function __construct($columns, $dateRange)
    {
        // Filter out branches and employees columns for staff users (employees/managers without admin role)
        $authUser = auth()->user();
        $isStaff = $authUser && ($authUser->hasRole('employee') || $authUser->hasRole('manager')) && !$authUser->hasRole('admin');
        
        if ($isStaff) {
            // Remove branches and employees from columns array for staff users
            $this->columns = array_values(array_filter($columns, function($column) {
                return $column !== 'branches' && $column !== 'employees';
            }));
        } else {
            $this->columns = $columns;
        }
        
        $this->dateRange = $dateRange;
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
        $query = Service::query()
            ->with(['category', 'sub_category'])
            ->withCount(['branches', 'employee']);

        $query->whereDate('created_at', '>=', $this->dateRange[0]);

        $query->whereDate('created_at', '<=', $this->dateRange[1]);

        // Apply filtering logic matching the datatable: filter by employee assignment in "my work" mode,
        // or by branch for managers when not in "my work" mode.
        $authUser = auth()->user();
        $userId = $authUser->id;
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // Managers in My Work or employees see only their assigned services (matching datatable logic)
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;

        // Branch selection is ignored in My Work (matching datatable logic)
        $selectedBranchId = $isManagerMyWork ? null : (request()->selected_session_branch_id ?? null);

        // Apply employee/manager-my-work scoping (matching datatable logic)
        if ($filterByEmployee) {
            $query->whereHas('employee', function ($q) use ($userId) {
                $q->where('employee_id', $userId);
            });
        }

        // Filter by selected branch from session unless My Work (matching datatable logic)
        if ($isManager && ! $isManagerMyWork) {
            if (! empty($selectedBranchId)) {
                $query->whereHas('branches', function ($q) use ($selectedBranchId) {
                    $q->where('branch_id', $selectedBranchId);
                });
            } else {
                // fallback: limit to branches the manager owns or is assigned to
                $managerBranchIds = \App\Models\Branch::where('manager_id', $authUser->id)->pluck('id')->toArray();
                if (empty($managerBranchIds)) {
                    $managerBranchIds = \Modules\Employee\Models\BranchEmployee::where('employee_id', $authUser->id)->pluck('branch_id')->toArray();
                }
                if (! empty($managerBranchIds)) {
                    $query->whereHas('branches', function ($q) use ($managerBranchIds) {
                        $q->whereIn('branch_id', $managerBranchIds);
                    });
                }
            }
        } elseif (! $isManagerMyWork && !empty($selectedBranchId)) {
            // For non-managers, filter by selected branch if not in my work mode
            $query->whereHas('branches', function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            });
        }

        $query = $query->orderBy('updated_at', 'desc');

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];
            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'status':
                        $selectedData[$column] = 'Inactive';
                        if ($row[$column]) {
                            $selectedData[$column] = 'Active';
                        }
                        break;

                    case 'default_price':
                        $selectedData[$column] = \Currency::format($row->default_price);
                        break;

                    case 'duration_min':
                        $selectedData[$column] = $row->duration_min . ' Min';
                        break;

                    case 'branches':
                        $selectedData[$column] = $row->branches_count ?? 0;
                        break;

                    case 'employees':
                        $selectedData[$column] = $row->employee_count ?? 0;
                        break;

                    case 'category':
                        $category = isset($row->category->name) ? $row->category->name : '-';
                        if (isset($row->sub_category->name)) {
                            $category = $category . ' > ' . $row->sub_category->name;
                        }
                        $selectedData[$column] = $category;
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
