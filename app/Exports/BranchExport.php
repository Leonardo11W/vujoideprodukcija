<?php

namespace App\Exports;

use App\Models\Branch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Constant\Models\Constant;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BranchExport implements FromCollection, WithHeadings, WithStyles
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
        $headingMap = [
            'name' => __('branch.lbl_name'),
            'contact_number' => __('branch.lbl_contact_number'),
            'manager' => __('branch.lbl_manager_name'),
            'city' => __('branch.lbl_city'),
            'postal_code' => __('branch.lbl_postal_code'),
            'branch_for' => __('branch.lbl_branch_for'),
            'status' => __('branch.lbl_status'),
            'updated_at' => __('messages.updated_at'),
        ];
        
        foreach ($this->columns as $column) {
            $modifiedHeadings[] = $headingMap[$column] ?? ucwords(str_replace('_', ' ', $column));
        }
        return $modifiedHeadings;
    }

    public function collection(): Collection
    {
        $query = Branch::query()->with(['address', 'employee']);

        // Filter by branch_id if provided (for manager login)
        if ($this->branchId) {
            $query->where('id', $this->branchId);
        }

        $query->whereDate('created_at', '>=', $this->dateRange[0]);
        $query->whereDate('created_at', '<=', $this->dateRange[1]);
        $rows = $query->orderBy('updated_at', 'desc')->get();

        // Get branch_for translations
        $branch_for_list = Constant::getTypeDataKeyValue('BRANCH_SERVICE_GENDER');

        $mapped = $rows->map(function ($row) use ($branch_for_list) {
            $selectedData = [];
            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'status':
                        $selectedData[$column] = $row->status ? __('messages.active') : __('messages.inactive');
                        break;
                    case 'manager':
                        $selectedData[$column] = optional($row->employee)->full_name ?? '-';
                        break;
                    case 'city':
                        $selectedData[$column] = optional(optional($row->address)->city_data)->name
                            ?? optional($row->address)->city ?? '-';
                        break;
                    case 'postal_code':
                        $selectedData[$column] = optional($row->address)->postal_code ?? '-';
                        break;
                    case 'branch_for':
                        $selectedData[$column] = $branch_for_list[$row->branch_for] ?? $row->branch_for ?? '-';
                        break;
                    default:
                        $selectedData[$column] = $row[$column] ?? '-';
                        break;
                }
            }
            return $selectedData;
        });

        return collect($mapped);
    }

    public function styles(Worksheet $sheet)
    {
        applyExcelStyles($sheet);
    }
}



