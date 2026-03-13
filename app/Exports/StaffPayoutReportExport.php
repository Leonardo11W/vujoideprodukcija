<?php

namespace App\Exports;

use Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Earning\Models\EmployeeEarning;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class StaffPayoutReportExport implements FromCollection, WithHeadings,  WithStyles
{
    public array $columns;

    public array $dateRange;

    public ?int $employeeId;

    public ?int $branchId;

    public ?int $excludeManagerId;

    public function __construct($columns, $dateRange, $employeeId = null, $branchId = null, $excludeManagerId = null)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->employeeId = $employeeId;
        $this->branchId = $branchId;
        $this->excludeManagerId = $excludeManagerId;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map column names to translation keys
        $headingMap = [
            'date' => __('export.columns.payment_date'),
            'employee' => __('export.columns.staff'),
            'commission_amount' => __('export.columns.commission_amount'),
            'tip_amount' => __('export.columns.tips_amount'),
            'payment_type' => __('export.columns.payment_type'),
            'total_pay' => __('export.columns.total_pay'),
        ];

        foreach ($this->columns as $column) {
            $modifiedHeadings[] = $headingMap[$column] ?? ucwords(str_replace('_', ' ', $column));
        }

        return $modifiedHeadings;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = EmployeeEarning::with('employee');

        // If employeeId is provided, filter to only show that employee's data
        if ($this->employeeId) {
            $query->where('employee_id', $this->employeeId);
        }

        // If branchId is provided, filter by branch
        if ($this->branchId) {
            $branchId = $this->branchId;
            $query->whereHas('employee', function ($q) use ($branchId) {
                $q->whereHas('branch', function ($b) use ($branchId) {
                    $b->where('branch_id', $branchId);
                });
            });
        }

        // If manager is logged in and branch is selected, exclude manager's data
        if ($this->branchId && $this->excludeManagerId) {
            $query->where('employee_id', '!=', $this->excludeManagerId);
        }

        $query->whereDate('payment_date', '>=', $this->dateRange[0]);

        $query->whereDate('payment_date', '<=', $this->dateRange[1]);

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'date':
                        $selectedData[$column] = customDate($row->payment_date);
                        break;

                    case 'employee':
                        $selectedData[$column] = $row->employee->full_name ?? '-';
                        break;

                    case 'commission_amount':
                        $selectedData[$column] = Currency::format($row->commission_amount ?? 0);
                        break;

                    case 'tip_amount':
                        $selectedData[$column] = Currency::format($row->tip_amount ?? 0);
                        break;

                    case 'payment_type':
                        $selectedData[$column] = $row->payment_type ?? '-';
                        break;

                    case 'total_pay':
                        $selectedData[$column] = Currency::format($row->total_amount ?? 0);
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
