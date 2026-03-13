<?php

namespace Modules\Tag\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Modules\Tag\Models\Tag;
use Yajra\DataTables\DataTables;

class TagsController extends Controller
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'tags.title';
        // module name
        $this->module_name = 'tags';

        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);

        $this->middleware(['permission:view_tag'])->only('index', 'index_data');
        $this->middleware(['permission:edit_tag'])->only('edit', 'update');
        $this->middleware(['permission:add_tag'])->only('store');
        $this->middleware(['permission:delete_tag'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];

        $module_action = 'List';
        $columns = CustomFieldGroup::columnJsonValues(new Tag());
        $customefield = CustomField::exportCustomFields(new Tag());

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => ' Name',
            ],
        ];
        $export_url = route('backend.tags.export');

        return view('tag::backend.tags.index_datatable', compact('module_action', 'filter', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $term = trim($request->q);

        // Filter tags by products in the current branch
        $query = Tag::query();
        // Admin with no branch selected - show all tags

        if ($term) {
            $query->where('name', 'LIKE', '%' . $term . '%');
        }

        $query_data = $query->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
            ];
        }

        return response()->json($data);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $query = Tag::query();
        $filter = $request->filter;

        // Filter tags by products in the current branch
        if (auth()->user()->hasRole('admin')) {
            // Admin with no branch selected - show all tags
        }
        // Admin with no branch selected - show all tags

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        $datatable = $datatable->eloquent($query)
        ->addColumn('check', function ($row) {

            $user = auth()->user();

            // Permissions that allow bulk actions
            $hasActionPermission =
                $user->can('edit_tag') ||
                $user->can('delete_tag');

            // If NO permission → return empty (no checkbox)
            if (!$hasActionPermission) {
                return '';
            }

            // If branch status is inactive AND user cannot change status → hide checkbox
            if (!$row->status && !$user->can('edit_tag')) {
                return '';
            }

            return '<input
                type="checkbox"
                class="form-check-input select-table-row"
                id="datatable-row-' . $row->id . '"
                name="datatable_ids[]"
                value="' . $row->id . '"
                onclick="dataTableRowCheck(' . $row->id . ')"
            >';
        })
            ->addColumn('action', function ($data) {
                return view('tag::backend.tags.action_column', compact('data'));
            })
            ->editColumn('status', function ($row) {
                $canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_tag');
                $checked = $row->status ? 'checked' : '';
                $disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.tags.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '"
                            ' . $checked . '
                            ' . $disabled . '
                        >
                    </div>
                ';
            })
            
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        return $datatable->rawColumns(array_merge(['action', 'status', 'check']))
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $tagData = $request->all();
        // Set created_by to current user
        $tagData['created_by'] = auth()->id();
        
        $data = Tag::create($tagData);

        $message = 'New Tag Added';

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Tag::findOrFail($id);

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $data = Tag::findOrFail($id);

        $data->update($request->all());

        $message = 'Tags Updated Successfully';

        return response()->json(['message' => $message, 'status' => true], 200);
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
        $data = Tag::findOrFail($id);

        $data->delete();

        $message = 'Tags Deleted Successfully';

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');
        switch ($actionType) {
            case 'change-status':
                $customer = Tag::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_tag_update');
                break;

            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                if (! auth()->user()->can('delete_tag')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                Tag::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_tag_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function update_status(Request $request, Tag $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }
}
