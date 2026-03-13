<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\Category\Models\Category;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubCategoryExport implements FromCollection, WithHeadings,WithStyles
{
    public array $columns;

    public array $dateRange;

    public array $filters;

    public function __construct($columns, $dateRange, $filters = [])
    {
        $this->columns = $columns;
        $this->dateRange = $dateRange;
        $this->filters = $filters;
    }

    public function headings(): array
    {
        $modifiedHeadings = [];

        // Map column names to translation keys
        $headingMap = [
            'name' => __('export.columns.name'),
            'category_name' => __('export.columns.category_name'),
            'status' => __('export.columns.status'),
            'Date' => __('export.columns.created_date'),
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
        $query = Category::query()->with('media', 'mainCategory')->whereNotNull('parent_id');

        $query->whereDate('created_at', '>=', $this->dateRange[0]);

        $query->whereDate('created_at', '<=', $this->dateRange[1]);

        if (isset($this->filters['column_status']) && $this->filters['column_status'] !== '') {
            $query->where('status', $this->filters['column_status']);
        }

        if (isset($this->filters['column_category']) && $this->filters['column_category'] !== '') {
            $query->where('parent_id', $this->filters['column_category']);
        }

        if (isset($this->filters['search']) && $this->filters['search'] !== '') {
            $query->where('name', 'LIKE', '%' . $this->filters['search'] . '%');
        }

        $query = $query->get();

        $newQuery = $query->map(function ($row) {
            $selectedData = [];

            foreach ($this->columns as $column) {
                switch ($column) {
                    case 'category_name':
                        $selectedData[$column] = $row->mainCategory->name ?? '-';
                        break;
                    case 'Date':
                        $selectedData[$column] = customDate($row->created_at) ?? '-';
                        break;

                    case 'status':
                        $selectedData[$column] = $row[$column] ? __('messages.active') : __('messages.inactive');
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
