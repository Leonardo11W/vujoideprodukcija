<?php

namespace App\Exports;

use App\Models\User;
use Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class StaffServiceReportExport implements FromCollection, WithHeadings,WithStyles
{
    public array $columns;

    public array $dateRange;

    public ?int $branchId;

    public ?int $employeeId;

    public function __construct($columns, $dateRange, $branchId = null, $employeeId = null)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->branchId = $branchId;
        $this->employeeId = $employeeId;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map column names to translation keys
        $headingMap = [
            'employee' => __('export.columns.staff'),
            'total_services' => __('export.columns.total_services'),
            'total_service_amount' => __('export.columns.total_amount'),
            'total_commission_earn' => __('export.columns.commission_earn'),
            'total_tip_earn' => __('export.columns.tips_earn'),
            'total_earning' => __('export.columns.total_earning'),
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
        // User::staffReport() filters booking data by branch, but we also need to filter staff by branch assignment
        $query = User::staffReport($this->branchId);

        // If employeeId is provided, filter to only show that employee's data
        if ($this->employeeId) {
            $query->where('users.id', $this->employeeId);
        } else {
        // Filter to only include staff (employees and managers) assigned to the specified branch
        if ($this->branchId) {
            $query->whereHas('branches', function ($q) {
                $q->where('branch_id', $this->branchId);
            });
            }
        }

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'employee':
                        $selectedData[$column] = $row->full_name ?? '-';
                        break;

                    case 'total_services':
                        $selectedData[$column] = $row->employee_booking_count > 0 ? $row->employee_booking_count : '0';
                        break;

                    case 'total_service_amount':
                        $selectedData[$column] = Currency::format($row->employee_booking_sum_service_price ?? 0);
                        break;

                    case 'total_commission_earn':
                        $selectedData[$column] = Currency::format($row->commission_earning_sum_commission_amount ?? 0);
                        break;

                    case 'total_tip_earn':
                        $selectedData[$column] = Currency::format($row->tip_earning_sum_tip_amount ?? 0);
                        break;

                    case 'total_earning':
                        $selectedData[$column] = Currency::format($row->commission_earning_sum_commission_amount + $row->tip_earning_sum_tip_amount);
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
