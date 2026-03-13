<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Booking\Models\Booking;
use Modules\Constant\Models\Constant;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookingsExport implements FromCollection, WithHeadings,WithStyles
{
    public array $columns;

    public array $dateRange;

    public function __construct($columns, $dateRange)
    {
        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');

        // If an employee (not manager) is logged in, do not include the "employee" column in export
        if ($authUser && $isEmployee && ! $isManager) {
            $columns = array_values(array_filter($columns, function ($col) {
                return $col !== 'employee';
            }));
        }

        $this->columns = $columns;
        $this->dateRange = $dateRange;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map column names to translation keys
        $headingMap = [
            'date' => __('export.columns.date'),
            'customer' => __('export.columns.customer'),
            'employee' => __('export.columns.staff'),
            'service_amount' => __('export.columns.service_amount'),
            'service_duration' => __('export.columns.service_duration'),
            'services' => __('export.columns.services'),
            'status' => __('export.columns.status'),
            'updated_at' => __('export.columns.updated_at'),
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
        $query = Booking::query()->branch()->with('user', 'services', 'mainServices');

        // Match index_data behavior: if an employee is logged in or a manager is in My Work mode,
        // only export bookings assigned to that logged-in user.
        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee && $authUser ? $authUser->id : null;

        if ($employeeId) {
            $query->where(function ($q) use ($employeeId) {
                $q->whereHas('services', function ($sub) use ($employeeId) {
                    $sub->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($sub) use ($employeeId) {
                        $sub->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($sub) use ($employeeId) {
                        $sub->where('employee_id', $employeeId);
                    });
            });
        }

        $query->whereDate('bookings.start_date_time', '>=', $this->dateRange[0]);

        $query->whereDate('bookings.start_date_time', '<=', $this->dateRange[1]);

        $query = $query->get();

        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');

        $newQuery = $query->map(function ($row) use ($booking_status) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'date':
                        $selectedData[$column] = customDate($row->start_date_time);
                        break;

                    case 'customer':
                        $selectedData[$column] = $row->user->full_name ?? default_user_name();
                        break;

                    case 'employee':
                        $selectedData[$column] = $row->services->first()->employee?->full_name ?? '-';
                        break;

                    case 'service_amount':
                        $selectedData[$column] = \Currency::format($row->services->sum('service_price'));
                        break;

                    case 'service_duration':
                        $selectedData[$column] = $row->services->sum('duration_min').' Min';
                        break;

                    case 'services':
                        $selectedData[$column] = implode(', ', $row->services->pluck('service_name')->toArray());
                        break;

                    case 'status':
                        $statusName = $row->status;
                        // Translate booking status
                        $statusTranslations = [
                            'pending' => __('booking.pending'),
                            'confirmed' => __('booking.confirmed'),
                            'check_in' => __('messages.check_in') ?? 'Check In',
                            'checkout' => __('messages.checkout') ?? 'Checkout',
                            'cancelled' => __('booking.cancelled'),
                            'completed' => __('booking.completed'),
                        ];
                        $selectedData[$column] = $statusTranslations[$statusName] ?? ($booking_status->where('name', $statusName)->first()->value ?? $statusName);
                        break;

                    case 'updated_at':
                        $diff = timeAgoInt($row->updated_at);

                        if ($diff < 25) {
                            $selectedData[$column] = timeAgo($row->updated_at);
                        } else {
                            $selectedData[$column] = customDate($row->updated_at);
                        }
                        break;

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
