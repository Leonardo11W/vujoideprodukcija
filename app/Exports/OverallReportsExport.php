<?php

namespace App\Exports;

use Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Booking\Models\Booking;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class OverallReportsExport implements FromCollection, WithHeadings,WithStyles
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
            'date' => __('export.columns.date'),
            'inv_id' => __('export.columns.inv_id'),
            'employee' => __('export.columns.staff'),
            'total_service' => __('export.columns.total_service'),
            'total_service_amount' => __('export.columns.total_service_amount'),
            'total_tax_amount' => __('export.columns.taxes'),
            'total_tip_amount' => __('export.columns.tips'),
            'total_amount' => __('export.columns.final_amount'),
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
        $query = Booking::overallReport()->with('userCouponRedeem');

        // If employeeId is provided (manager in my work mode or employee), filter to only that user's bookings
        if ($this->employeeId) {
            $query->where(function ($q) {
                $q->whereHas('services', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                })
                ->orWhereHas('bookingPackages', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                })
                ->orWhereHas('products', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                });
            });
        }

        // If branchId is provided (branch selected), filter to that branch only
        if ($this->branchId) {
            $query->where('bookings.branch_id', $this->branchId);
        }

        // If excludeManagerId is set (branch selected + manager): exclude manager's data → staff data only
        if ($this->excludeManagerId) {
            $query->whereDoesntHave('services', function ($sub) {
                $sub->where('employee_id', $this->excludeManagerId);
            })
            ->whereDoesntHave('bookingPackages', function ($sub) {
                $sub->where('employee_id', $this->excludeManagerId);
            })
            ->whereDoesntHave('products', function ($sub) {
                $sub->where('employee_id', $this->excludeManagerId);
            });
        }

        $query->whereDate('bookings.start_date_time', '>=', $this->dateRange[0]);

        $query->whereDate('bookings.start_date_time', '<=', $this->dateRange[1]);

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'date':
                        $selectedData[$column] = customDate($row->start_date_time);
                        break;

                    case 'inv_id':
                        $selectedData[$column] = setting('booking_invoice_prifix').$row->id;
                        break;

                    case 'employee':
                        $selectedData[$column] = $row->services->first()->employee?->full_name ?? '-';
                        break;

                    case 'total_service_amount':
                        // Calculate service amount after discount (subtotal)
                        $serviceAmount = $row->total_service_amount ?? 0;
                        $discount = optional($row->userCouponRedeem)->discount ?? 0;
                        $serviceAmountAfterDiscount = max(0, $serviceAmount - $discount);
                        $selectedData[$column] = Currency::format($serviceAmountAfterDiscount);
                        break;

                    case 'total_tax_amount':
                        $selectedData[$column] = Currency::format($row->total_tax_amount ?? 0);
                        break;

                    case 'total_tip_amount':
                        $selectedData[$column] = Currency::format($row->total_tip_amount ?? 0);
                        break;

                    case 'total_amount':
                        $serviceAmount = $row->total_service_amount ?? 0;
                        $discount = optional($row->userCouponRedeem)->discount ?? 0;
                        $serviceAfterDiscount = max(0, $serviceAmount - $discount);
                        $totalTaxAmount = $row->total_tax_amount ?? 0;
                        $totalTipAmount = $row->total_tip_amount ?? 0;

                        $selectedData[$column] = Currency::format($serviceAfterDiscount + $totalTaxAmount + $totalTipAmount);
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
