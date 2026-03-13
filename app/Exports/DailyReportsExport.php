<?php

namespace App\Exports;

use Carbon\Carbon;
use Currency;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Booking\Models\Booking;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class DailyReportsExport implements FromCollection, WithHeadings, WithStyles
{
    public array $columns;

    public array $dateRange;

    public ?int $employeeId;

    public ?int $branchId;

    public function __construct($columns, $dateRange, $employeeId = null, $branchId = null, $excludeManagerId = null)
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->employeeId = $employeeId;
        $this->branchId = $branchId;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map column names to translation keys
        $headingMap = [
            'date' => __('export.columns.date'),
            'total_booking' => __('export.columns.no_booking'),
            'total_service' => __('export.columns.no_services'),
            'total_service_amount' => __('export.columns.total_service_amount'),
            'total_tax_amount' => __('export.columns.tax_amount'),
            'total_tip_amount' => __('export.columns.tips_amount'),
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
        $startDate = $this->dateRange[0];
        $endDate = $this->dateRange[1];

        $authUser = auth()->user();
        $isAdmin = $authUser && $authUser->hasRole('admin');

        // Check for today only logic for non-admins (Step 616)
        $isManager = $authUser && $authUser->hasRole('manager');
        if (!$isAdmin && !$isManager) {
            $todayStr = Carbon::today()->format('Y-m-d');
            $isTodayInRange = ($todayStr >= $startDate && $todayStr <= $endDate);

            // If today is not in range, return empty collection (as per user requirement in Step 616)
            if (!$isTodayInRange) {
                return collect();
            }
        }

        // Replicate ReportsController query building logic
        $query = Booking::with(['services', 'packages', 'payment', 'userCouponRedeem'])
            ->where('status', 'completed')
            ->whereHas('payment', function($q) {
                $q->where('payment_status', 1);
            });

        if ($this->employeeId) {
            $query->where(function ($q) {
                $q->whereHas('services', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                })
                ->orWhereHas('packages', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                })
                ->orWhereHas('products', function ($sub) {
                    $sub->where('employee_id', $this->employeeId);
                });
            });
        }

        if ($this->branchId) {
            if (is_array($this->branchId)) {
                $query->whereIn('bookings.branch_id', $this->branchId);
            } else {
                $query->where('bookings.branch_id', $this->branchId);
            }
        }

        $query->whereBetween('bookings.start_date_time', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        ]);

        $bookings = $query->get();

        // Group bookings by local date exactly as in ReportsController::daily_booking_report_index_data
        $groupedBookings = $bookings->groupBy(fn($b) => formatDateOrTime($b->start_date_time, 'date'));

        $dataToReturn = collect();
        
        if ($isAdmin || $isManager) {
            // For admin and manager, return all dates in the range
            foreach ($groupedBookings as $date => $dailyBookings) {
                $dataToReturn->push($this->processDailyBookings($date, $dailyBookings));
            }
        } else {
            // Apply Today-Only filter for other roles (Step 616)
            $todayFormatted = formatDateOrTime(Carbon::today(), 'date');
            $todayGroup = $groupedBookings->get($todayFormatted);

            if ($todayGroup) {
                $dataToReturn->push($this->processDailyBookings($todayFormatted, $todayGroup));
            }
        }

        return $dataToReturn;
    }

    /**
     * Process daily bookings and format for export
     */
    protected function processDailyBookings($date, $dailyBookings)
    {
        $totalTaxAmount = $dailyBookings->sum(fn($b) => $b->total_tax_amount);
        
        $totalServiceAmountAfterDiscount = 0;
        $totalFinalAmountAfterDiscount = 0;
        foreach ($dailyBookings as $booking) {
            $discount = optional($booking->userCouponRedeem)->discount ?? 0;
            $serviceAmount = $booking->total_service_amount;
            $serviceAfterDiscount = max(0, $serviceAmount - $discount);

            $totalServiceAmountAfterDiscount += $serviceAfterDiscount;
            $totalFinalAmountAfterDiscount += $serviceAfterDiscount + $booking->total_tax_amount + $booking->total_tip_amount;
        }

        $row = (object)[
            'start_date_time'      => $date,
            'total_booking'        => $dailyBookings->count(),
            'total_service'        => $dailyBookings->sum(fn($b) => $b->services->count() + $b->packages->count()),
            'total_service_amount' => $totalServiceAmountAfterDiscount,
            'total_tax_amount'     => $totalTaxAmount,
            'total_tip_amount'     => $dailyBookings->sum(fn($b) => $b->total_tip_amount),
            'grand_total_amount'   => $totalFinalAmountAfterDiscount,
        ];

        $selectedData = [];
        foreach ($this->columns as $column) {
            switch ($column) {
                case 'date':
                    $selectedData[$column] = formatDateOrTime($row->start_date_time);
                    break;

                case 'total_service_amount':
                    $selectedData[$column] = Currency::format($row->total_service_amount);
                    break;

                case 'total_tax_amount':
                    $selectedData[$column] = Currency::format($row->total_tax_amount);
                    break;

                case 'total_tip_amount':
                    $selectedData[$column] = Currency::format($row->total_tip_amount);
                    break;

                case 'total_amount':
                    $selectedData[$column] = Currency::format($row->grand_total_amount);
                    break;

                default:
                    $selectedData[$column] = $row->$column;
                    break;
            }
        }
        
        return $selectedData;
    }
    public function styles(Worksheet $sheet)
    {
        applyExcelStyles($sheet);
    }
}
