<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Split flatpickr range from dashboard/reports ("YYYY-MM-DD to YYYY-MM-DD" or Croatian "do").
     *
     * @return string[] date fragments parseable by Carbon
     */
    protected function splitFlatpickrRange(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $normalized = preg_replace('/\x{00a0}/u', ' ', trim($raw));
        $parts = preg_split('/\s+(?:to|do)\s+/iu', $normalized);

        return array_values(array_filter(array_map('trim', $parts), function ($p) {
            return $p !== '';
        }));
    }

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
        $response = [
            'status' => true,
            'success' => true,
            'data' => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 200)
    {
        $response = [
            'status' => false,
            'message' => $error,
        ];

        if (! empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    protected string $exportClass = '';

    public function export(Request $request, $branchId = null, $employeeId = null, $excludeManagerId = null)
    {
        $columns = explode(',', $request->columns);
        $type = $request->file_type;
        $dateRange = $this->splitFlatpickrRange($request->date_range);
        if (count($dateRange) === 0) {
            $today = Carbon::now()->toDateString();
            $dateRange = [$today, $today];
        } elseif (count($dateRange) === 1) {
            $dateRange[1] = $dateRange[0];
        }

        if (! empty($this->exportClass)) {
            // Check if export class accepts branchId parameter (StaffServiceReportExport or BranchExport)
            $isStaffServiceReport = $this->exportClass === '\App\Exports\StaffServiceReportExport';
            $isBranchExport = $this->exportClass === '\App\Exports\BranchExport';
            $isOverallReport = $this->exportClass === '\App\Exports\OverallReportsExport';
            $isPayoutReport = $this->exportClass === '\App\Exports\StaffPayoutReportExport';
            $isDailyReport = $this->exportClass === '\App\Exports\DailyReportsExport';
            $isEarningExport = $this->exportClass === '\App\Exports\EarningExport';
            
            // Get branch ID from request or session if not provided
            if ($isEarningExport && $branchId === null) {
                $explicitBranchId = isset($request->branch_id) && $request->branch_id !== '' ? (int) $request->branch_id : null;
                $selectedBranchId = $request->selected_session_branch_id ?? request()->session()->get('selected_branch');
                $branchId = $explicitBranchId ?? ($selectedBranchId ? (int) $selectedBranchId : null);
            }
            
            if ($isStaffServiceReport) {
                // StaffServiceReportExport accepts branchId and employeeId
                $exportInstance = new $this->exportClass($columns, $dateRange, $branchId, $employeeId);
            } elseif ($isPayoutReport) {
                // StaffPayoutReportExport accepts employeeId, branchId, and excludeManagerId
                $exportInstance = new $this->exportClass($columns, $dateRange, $employeeId, $branchId, $excludeManagerId);
            } elseif ($isOverallReport || $isDailyReport) {
                // DailyReportsExport: columns, dateRange, employeeId, branchId, excludeManagerId
                // OverallReportsExport: columns, dateRange, employeeId, branchId, excludeManagerId
                $exportInstance = new $this->exportClass($columns, $dateRange, $employeeId, $branchId, $excludeManagerId);
            } elseif (($isBranchExport || $isEarningExport) && $branchId !== null) {
                // BranchExport and EarningExport accept branchId
                $exportInstance = new $this->exportClass($columns, $dateRange, $branchId);
            } else {
                if ($this->exportClass === '\App\Exports\SubCategoryExport') {
                    $exportInstance = new $this->exportClass($columns, $dateRange, $request->all());
                } else {
                    $exportInstance = new $this->exportClass($columns, $dateRange);
                }
            }

            switch ($type) {
                case 'csv':
                    return Excel::download($exportInstance, 'file.csv', \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'xlsx':
                    return Excel::download($exportInstance, 'file.xlsx', \Maatwebsite\Excel\Excel::XLSX);
                    break;
                case 'xls':
                    return Excel::download($exportInstance, 'file.xls', \Maatwebsite\Excel\Excel::XLS);
                    break;
                case 'ods':
                    return Excel::download($exportInstance, 'file.ods', \Maatwebsite\Excel\Excel::ODS);
                    break;
                case 'html':
                    return Excel::download($exportInstance, 'file.html', \Maatwebsite\Excel\Excel::HTML);
                    break;
                case 'pdf':
                    return Excel::download($exportInstance, 'file.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
                    break;
            }
        }

        return abort(500);
    }
}
