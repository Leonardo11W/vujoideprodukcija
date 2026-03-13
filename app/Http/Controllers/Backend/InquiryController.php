<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Frontend\Models\Inquiry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use Yajra\DataTables\DataTables;

class InquiryController extends Controller
{
    protected $module_title;
    protected $module_name;
    protected $module_icon;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'inquiry.title';

        // module name
        $this->module_name = 'inquiries';

        // module icon
        $this->module_icon = 'fa-solid fa-envelope';

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
        ]);

        $this->middleware(['permission:view_inquiry'])->only('index');
        $this->middleware(['permission:delete_inquiry'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $module_action = 'List';

        $filter = [
            'status' => $request->status,
        ];

        $assets = ['select-picker'];

        return view('backend.inquiries.index_datatable', compact('module_action', 'filter', 'assets'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $query = Inquiry::orderBy('created_at', 'desc')->get();

        return response()->json($query);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                $inquiries = Inquiry::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_status_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('inquiry.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;

        $query = Inquiry::query();

        $filter = $request->filter;

        if (isset($filter)) {
            // Add any additional filters here if needed
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row "  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('backend.inquiries.action_column', compact('module_name', 'data'));
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                }
            })
            ->filterColumn('email', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('email', 'like', '%' . $keyword . '%');
                }
            })
            ->filterColumn('subject', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('subject', 'like', '%' . $keyword . '%');
                }
            })
            ->editColumn('name', function ($data) {
                return view('backend.inquiries.inquiry_id', compact('data'));
            })
            ->editColumn('message', function ($data) {
                return Str::limit($data->message, 50);
            })
            ->editColumn('created_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->created_at);

                if ($diff < 25) {
                    return $data->created_at->diffForHumans();
                } else {
                    return $data->created_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        return $datatable->rawColumns(['action', 'check'])
            ->toJson();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $module_action = 'Show';

        $inquiry = Inquiry::findOrFail($id);

        Log::info(label_case($this->module_title . ' ' . $module_action) . ' | User:' . Auth::user()->name . '(ID:' . Auth::user()->id . ')');

        return view('backend.inquiries.show', compact('inquiry', 'module_action'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $data = Inquiry::findOrFail($id);

        $data->delete();

        $message = __('messages.delete_form', ['form' => __('inquiry.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * Delete All the Inquiries.
     *
     * @return Response
     */
    public function deleteAll()
    {
        $module_action = 'Delete All';

        Inquiry::truncate();

        Flash::success("<i class='fas fa-check'></i> All Inquiries Deleted")->important();

        Log::info(label_case($this->module_title . ' ' . $module_action) . ' | User:' . Auth::user()->name . '(ID:' . Auth::user()->id . ')');

        return back();
    }
}
