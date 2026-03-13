<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Address;
use App\Models\Branch;
use App\Models\BranchGallery;
use App\Models\Setting;
use App\Models\User;
use App\Traits\PaymentMethodTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Constant\Models\Constant;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Modules\Employee\Models\BranchEmployee;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Yajra\DataTables\DataTables;

class BranchController extends Controller
{
    use PaymentMethodTrait;

    use Authorizable;
    protected string $exportClass = '\\App\\Exports\\BranchExport';

    public function __construct()
    {
        // Page Title
        $this->module_title = 'branch.title';

        // module name
        $this->module_name = 'branch';

        // module icon
        $this->module_icon = 'fa-solid fa-building';

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
        ]);

        $this->middleware(['permission:view_branch'])->only('index');
        $this->middleware(['permission:edit_branch'])->only('edit', 'update');
        $this->middleware(['permission:add_branch'])->only('store');
        $this->middleware(['permission:delete_branch'])->only('destroy');
    }

    /**
     * Override callAction to allow index_list without view_branch permission
     * (needed for booking form and other features)
     */
    public function callAction($method, $parameters)
    {
        // For index_list method, bypass authorization as it handles its own access control
        if ($method === 'index_list') {
            return $this->{$method}(...array_values($parameters));
        }

        // For other methods, use the trait's default behavior
        return parent::callAction($method, $parameters);
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

        $select_data = [
            'BRANCH_FOR' => Constant::getTypeDataObject('BRANCH_SERVICE_GENDER'),
            'PAYMENT_METHODS' => $this->getFilteredPaymentMethods(),
        ];

        $assets = ['select-picker'];
        $columns = CustomFieldGroup::columnJsonValues(new Branch());
        $customefield = CustomField::exportCustomFields(new Branch());

        // Export config
        $export_import = true;
        $export_columns = [
            ['value' => 'name', 'translationKey' => 'export.columns.name'],
            ['value' => 'contact_number', 'translationKey' => 'export.columns.contact_number'],
            ['value' => 'manager', 'translationKey' => 'export.columns.manager'],
            ['value' => 'city', 'translationKey' => 'export.columns.city'],
            ['value' => 'postal_code', 'translationKey' => 'export.columns.postal_code'],
            ['value' => 'branch_for', 'translationKey' => 'export.columns.branch_for'],
            ['value' => 'status', 'translationKey' => 'export.columns.status'],
            ['value' => 'updated_at', 'translationKey' => 'export.columns.updated_at'],
        ];
        $export_url = route('backend.branch.export');

        return view('backend.branch.index_datatable', compact('module_action', 'filter', 'select_data', 'assets', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $query = Branch::with('media')->active();

        $selectedBranchId = $request->selected_session_branch_id;
        $authUser = Auth::user();

        if (! empty($selectedBranchId) && $authUser && ($authUser->hasRole('manager') || $authUser->hasRole('employee'))) {
            $query->where('id', $selectedBranchId);
        }

        return response()->json($query->get());
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        // dd($actionType, $ids, $request->status);
        switch ($actionType) {
            case 'change-status':
                $branches = Branch::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_status_update');
                break;

            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                $branches = Branch::with('bookings')->whereIn('id', $ids)->get();

                foreach ($branches as $branch) {
                    $branch->bookings()->delete();
                    $branch->branchServices()->delete();
                    $branch->delete();
                }
                $message = __('messages.bulk_status_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function update_status(Request $request, Branch $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }

    public function update_select(Request $request, Branch $id)
    {
        $actionType = $request->action_type;
        switch ($actionType) {
            case 'update-branch-for':
                $id->update(['branch_for' => $request->value]);

                return response()->json(['status' => true, 'message' => __('branch.branch_update')]);
                break;
        }
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;

        $query = Branch::withCount('branchEmployee')->with('media', 'address', 'employee');

        // Scope by selected branch if present in session (set via middleware)
        $selectedBranchId = $request->selected_session_branch_id;
        if (!empty($selectedBranchId)) {
            $query->where('branches.id', $selectedBranchId);
        }

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        $branch_for_list = Constant::getTypeDataKeyValue('BRANCH_SERVICE_GENDER');

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {

                $user = auth()->user();

                // Permissions that allow bulk actions
                $hasActionPermission =
                    $user->can('edit_branch') ||
                    $user->can('delete_branch');

                // If NO permission → return empty (no checkbox)
                if (!$hasActionPermission) {
                    return '';
                }

                // If branch status is inactive AND user cannot change status → hide checkbox
                if (!$row->status && !$user->can('edit_branch')) {
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

            ->addColumn('action', function ($data) use ($module_name) {
                return view('backend.branch.action_column', compact('module_name', 'data'));
            })
            ->filterColumn('address.city', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('address', function ($q) use ($keyword) {
                        $q->where('city', 'like', '%' . $keyword . '%')
                          ->orWhereHas('city_data', function ($cityQuery) use ($keyword) {
                              $cityQuery->where('name', 'like', '%' . $keyword . '%');
                          });
                    });
                }
            })
            ->filterColumn('address.postal_code', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('address', function ($q) use ($keyword) {
                        $q->where('postal_code', 'like', '%' . $keyword . '%');
                    });
                }
            })

            ->filterColumn('manager_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('employee', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderColumn('address.city', function ($query, $order) {
                $query->leftJoin('addresses as sort_city', function ($join) {
                    $join->on('sort_city.addressable_id', '=', 'branches.id')
                        ->where('sort_city.addressable_type', '=', 'App\\Models\\Branch');
                })->orderBy('sort_city.city', $order)->select('branches.*');
            })
            ->orderColumn('address.postal_code', function ($query, $order) {
                $query->leftJoin('addresses as sort_postal', function ($join) {
                    $join->on('sort_postal.addressable_id', '=', 'branches.id')
                        ->where('sort_postal.addressable_type', '=', 'App\\Models\\Branch');
                })->orderBy('sort_postal.postal_code', $order)->select('branches.*');
            })
            ->orderColumn('manager_id', function ($query, $order) {
                $query->leftJoin('users', 'users.id', '=', 'branches.manager_id')
                    ->orderBy('users.first_name', $order)
                    ->select('branches.*');
            })
            ->filterColumn('branch_for', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('branch_for', 'like', $keyword . '%');
                }
            })
            ->filterColumn('name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%')
                          ->orWhere('contact_email', 'like', '%' . $keyword . '%')
                          ->orWhere('contact_number', 'like', '%' . $keyword . '%')
                          ->orWhereHas('address', function ($addressQuery) use ($keyword) {
                              $addressQuery->where('city', 'like', '%' . $keyword . '%')
                                         ->orWhereHas('city_data', function ($cityQuery) use ($keyword) {
                                             $cityQuery->where('name', 'like', '%' . $keyword . '%');
                                         });
                          });
                    });
                }
            })
            ->editColumn('status', function ($row) {
                $canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_branch');
                $checked = $row->status ? 'checked' : '';
                $disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.branch.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '"
                            ' . $checked . '
                            ' . $disabled . '
                        >
                    </div>
                ';
            })

            ->editColumn('name', function ($data) {
                $email = optional($data)->contact_email ?? '--';
                return view('backend.branch.branch_id', compact('data', 'email'));
            })
            ->editColumn('address.city', function ($data) {
                return optional(optional($data->address)->city_data)->name ?? '-';
            })
            ->editColumn('address.postal_code', function ($data) {
                return optional($data->address)->postal_code ?? '-';
            })
            ->editColumn('manager_id', function ($data) {
                $Profile_image = optional($data->employee)->profile_image ?? default_user_avatar();
                $name = optional($data->employee)->full_name ?? default_user_name();
                $email = optional($data->employee)->email ?? '--';
                $id = optional($data->employee)->id ?? null;
                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->editColumn('branch_for', function ($data) use ($branch_for_list) {
                return view('backend.branch.select_column', compact('data', 'branch_for_list'));
            })
            ->addColumn('assign', function ($data) {
                // By default use the precomputed count from the query
                $count = (int) ($data->branch_employee_count ?? 0);

                // If a manager is logged in, only count non‑manager staff for this branch
                if (auth()->user() && auth()->user()->hasRole('manager')) {
                    $count = BranchEmployee::where('branch_id', $data->id)
                        ->whereHas('employee', function ($q) {
                            $q->where('is_manager', 0)
                                ->orWhereNull('is_manager')
                                ->orWhere('users.id', auth()->id());
                        })
                        ->count();
                }

                $canAssign = auth()->user()->hasRole('admin') || auth()->user()->can('edit_branch');
                $disabled = $canAssign ? '' : 'disabled';

                return "<div class='d-flex align-items-center'>
                <div>
                    <button type='button' data-assign-module='{$data->id}' data-assign-target='#staff-assign-form' data-assign-event='staff_assign' class='btn btn-primary btn-sm rounded btn-icon' {$disabled}>
                        <b>{$count}</b>
                    </button>
                </div>
                 </div>";
            })

            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, Branch::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['action', 'status', 'branch_for', 'check', 'assign'], $customFieldColumns))
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(BranchRequest $request)
    {
        $data = $request->except('feature_image');
        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $query = Branch::create($data);

        $days = [
            ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'sunday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => true, 'breaks' => []],
        ];

        foreach ($days as $key => $val) {
            $val['branch_id'] = $query->id;
            BussinessHour::create($val);
        }

        $addressPayload = $request->input('address');
        if (!empty($addressPayload)) {
            $addressArray = is_string($addressPayload) ? json_decode($addressPayload, true) : (is_array($addressPayload) ? $addressPayload : null);
            if (is_array($addressArray)) {
                $addressArray['user_id'] = $addressArray['user_id'] ?? 1; // Default user for branch addresses
                $addressArray['addressable_type'] = 'App\\Models\\Branch';
                $addressArray['addressable_id'] = $query->id;
                $addressModel = $query->address()->save(new Address($addressArray));
                if (!$addressModel) {
                    \Log::error('Failed to save branch address', ['branch_id' => $query->id, 'address' => $addressArray]);
                }
            }
        }

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        $featureImageUrl = null;
        if ($request->hasFile('feature_image')) {
            $media = storeMediaFile($query, $request->file('feature_image'));
            if ($media && method_exists($query, 'getFirstMediaUrl')) {
                $featureImageUrl = $query->getFirstMediaUrl('feature_image');
            }
        }

        $branch_id = $query->id;

        $manager_id = $request->manager_id;

        BranchEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);

        // $user->syncRoles(['employee', 'manager']);

        \Artisan::call('cache:clear');

        BranchEmployee::create([
            'branch_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;

        $this->assign_service_branch($service_id, $branch_id);

        $message = __('messages.create_form', ['form' => __('branch.singular_title')]);

        return response()->json([
            'message' => $message,
            'status' => true,
            'feature_image' => $featureImageUrl,
            'branch_id' => $branch_id
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Branch::with('address')->findOrFail($id);
        // dd($data->status);

        $service_id = ServiceBranches::where('branch_id', $data->id)->get()->pluck('service_id');

        $data['service_id'] = $service_id;

        if (!is_null($data)) {
            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = $custom_field_data->custom_fields_data->toArray();
        }
        $data['status'] = $data->status ? true : false;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(BranchRequest $request, $id)
    {
        // dd('here');
        $query = Branch::findOrFail($id);

        $data = $request->except('feature_image'); // Initialize data

        // Normalize boolean status (unchecked checkbox does not submit a value)
        $data['status'] = $request->has('status') ? 1 : 0;

        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $old_manager_id = $query->manager_id;
        $query->update($data);

        $addressPayload = $request->input('address');
        if (!empty($addressPayload)) {
            $addressArray = is_string($addressPayload) ? json_decode($addressPayload, true) : (is_array($addressPayload) ? $addressPayload : null);
            if (is_array($addressArray)) {
                if ($query->address()->exists()) {
                    $query->address()->update($addressArray);
                } else {
                    $addressArray['user_id'] = $addressArray['user_id'] ?? 1;
                    // addressable_* are set automatically by morph relation when using save(new Address(...))
                    $query->address()->save(new Address($addressArray));
                }
            }
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        } elseif ($request->has('remove_feature_image') && $request->get('remove_feature_image') == '1') {
            // User explicitly clicked remove button - clear the image
            $query->clearMediaCollection('feature_image');
        }

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data, true));
        }

        $manager_id = $request->manager_id;
        if ($old_manager_id && $old_manager_id != $manager_id) {
            BranchEmployee::where('employee_id', $old_manager_id)
                ->where('branch_id', $query->id)
                ->delete();
        }
        BranchEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);
        // if ($user) {
        //     $user->syncRoles(['employee', 'manager']);
        // }

        BranchEmployee::create([
            'branch_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;
        $this->assign_service_branch($service_id, $query->id);

        $message = __('messages.update_form', ['form' => __('branch.singular_title')]);

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
        $data = Branch::findOrFail($id);

        $data->bookings()->delete();

        $data->branchServices()->delete();

        $data->branchEmployee()->delete();

        $data->delete();

        $message = __('messages.delete_form', ['form' => __('branch.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function assign_list($id)
{
    // Get the branch to access the manager
    $branch = Branch::with('employee')->find($id);
    
    $branch_user = BranchEmployee::with('employee', 'getBranch')->where('branch_id', $id)->get();

    $branch_user = $branch_user->filter(function($data) {
        return $data->employee !== null && $data->getBranch !== null;
    })->map(function ($data) {
        $data['branch_name'] = $data->getBranch->name;
        $data['name'] = $data->employee->full_name;
        $data['avatar'] = $data->employee->profile_image;
        $data['id'] = $data->employee_id;

        return $data;
    });

    // Add the branch manager if exists and not already in the list
    if ($branch && $branch->employee) {
        $managerAlreadyInList = $branch_user->contains(function ($item) use ($branch) {
            return $item->id === $branch->manager_id;
        });

        if (!$managerAlreadyInList) {
            // Create a pseudo BranchEmployee object for the manager
            $managerData = (object) [
                'id' => $branch->manager_id,
                'branch_id' => $branch->id,
                'employee_id' => $branch->manager_id,
                'branch_name' => $branch->name,
                'name' => $branch->employee->full_name,
                'avatar' => $branch->employee->profile_image,
                'employee' => $branch->employee,
                'getBranch' => $branch,
            ];
            
            // Prepend manager to the beginning of the collection
            $branch_user = $branch_user->prepend($managerData);
        }
    }

    return response()->json(['status' => true, 'data' => $branch_user]);
}

    public function assign_update(Branch $id, Request $request)
    {
        $id->branchEmployee()->delete();

        $employees = [];

        if ($request->has('users') && is_array($request->users)) {
            foreach ($request->users as $emp_id) {
                $branchEmployee = BranchEmployee::where('employee_id', $emp_id)->get();
                if (count($branchEmployee) > 0) {
                    BranchEmployee::where('employee_id', $emp_id)->delete();
                } else {
                    $branchEmployee = BranchEmployee::where('employee_id', $emp_id)->first();
                    if (isset($branchEmployee)) {
                        $branchEmployee->update(['branch_id' => $id->id]);

                        continue;
                    }
                }
                $employees[] = ['employee_id' => $emp_id];
            }
        }

        if (count($employees) > 0) {
            $id->branchEmployee()->createMany($employees);
        }

        return response()->json(['status' => true, 'message' => __('branch.branch_successfull')]);
    }

    public function branch_list(Request $request)
    {
        $term = $request->q;
        $role = $request->role;
        $query_data = BranchEmployee::select('*', 'id as employee_id')->where(function ($q) use ($term, $role) {
            if (!empty($term)) {
                $q->orWhere('name', 'LIKE', "%$term%");
            }
            if (!empty($role)) {
                $q->role($role);
            }
        })->get();

        return response()->json($query_data);
    }

    public function getGalleryImages($id)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                \Log::warning('🔒 Gallery access - no authenticated user', ['branch_id' => $id]);
                return response()->json([
                    'status' => false,
                    'message' => __('messages.permission_denied'),
                    'data' => []
                ], 401);
            }
            
            // Check if user has branch_gallery permission (view-only) OR edit_branch permission
            $hasBranchGallery = $user->hasPermissionTo('branch_gallery');
            $hasEditBranch = $user->hasPermissionTo('edit_branch');
            
            \Log::info('🔍 Gallery access check', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'branch_id' => $id,
                'has_branch_gallery' => $hasBranchGallery,
                'has_edit_branch' => $hasEditBranch,
                'request_url' => request()->fullUrl(),
                'request_method' => request()->method()
            ]);
            
            if (!$hasBranchGallery && !$hasEditBranch) {
                \Log::warning('🔒 Blocked gallery access - no permission', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'branch_id' => $id,
                    'has_branch_gallery' => $hasBranchGallery,
                    'has_edit_branch' => $hasEditBranch
                ]);
                return response()->json([
                    'status' => false,
                    'message' => __('messages.permission_denied'),
                    'data' => []
                ], 403);
            }
            
            \Log::info('✅ Fetching gallery images - permission granted', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'branch_id' => $id,
                'has_branch_gallery' => $hasBranchGallery,
                'has_edit_branch' => $hasEditBranch,
                'mode' => $hasEditBranch ? 'Edit mode' : 'View-only mode'
            ]);
            
            $branch = Branch::findOrFail($id);
        $data = BranchGallery::where('branch_id', $id)->get();
            
            \Log::info('✅ Gallery images fetched successfully', [
                'branch_id' => $id,
                'image_count' => $data->count()
            ]);

        return response()->json(['data' => $data, 'branch' => $branch, 'status' => true]);
        } catch (\Exception $e) {
            \Log::error('❌ Error fetching gallery images', [
                'branch_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Error fetching gallery images: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function uploadGalleryImages(Request $request, $id)
    {
        $gallery = collect($request->gallery, true);

        $images = BranchGallery::where('branch_id', $id)->whereNotIn('id', $gallery->pluck('id'))->get();

        foreach ($images as $key => $value) {
            $value->clearMediaCollection('gallery_images');
            $value->delete();
        }

        foreach ($gallery as $key => $value) {
            if ($value['id'] == 'null') {
                $branchGallery = BranchGallery::create([
                    'branch_id' => $id,
                ]);

                $branchGallery->addMedia($value['file'])->toMediaCollection('gallery_images');

                $branchGallery->full_url = $branchGallery->getFirstMediaUrl('gallery_images');
                $branchGallery->save();
            }
        }

        return response()->json(['message' => __('branch.update_branch_gallery'), 'status' => true]);
    }

    protected function assign_service_branch($service_id, $branch_id)
    {
        $service_id = is_string($service_id) ? explode(',', $service_id) : $service_id;
        if (isset($service_id) && count($service_id)) {
            $services = Service::whereIn('id', $service_id)->get();
            ServiceBranches::where('branch_id', $branch_id)->delete();
            foreach ($service_id as $key => $value) {
                $service = $services->where('id', $value)->first();
                ServiceBranches::create([
                    'service_id' => $value,
                    'branch_id' => $branch_id,
                    'service_price' => $service->default_price ?? 0,
                    'duration_min' => $service->duration_min,
                ]);
            }
        }
    }

    public function branchData(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasRole('manager')) {
            return response()->json(['message' => 'You are not authorized to access this data.', 'status' => false], 403);
        }

        // Gather all branches the manager can access (primary + assigned)
        $managedBranchIds = Branch::where('manager_id', $user->id)->pluck('id')->toArray();
        $assignedBranchIds = BranchEmployee::where('employee_id', $user->id)->pluck('branch_id')->toArray();
        $primaryBranchId = optional($user->branch)->branch_id;

        $accessibleBranchIds = collect(array_filter(array_merge(
            $managedBranchIds,
            $assignedBranchIds,
            [$primaryBranchId]
        )))->unique()->values();

        // Determine which branch should be loaded
        $requestedBranchId = $request->input('branch_id');
        $sessionBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');

        $activeBranchId = $requestedBranchId ?? $sessionBranchId ?? $primaryBranchId;
        if ($accessibleBranchIds->isNotEmpty()) {
            if (! $activeBranchId || ! $accessibleBranchIds->contains($activeBranchId)) {
                $activeBranchId = $accessibleBranchIds->first();
            }
        }

        if (! $activeBranchId) {
            return response()->json(['message' => 'No branch assigned to your account.', 'status' => false], 404);
        }

        $data = Branch::with('address')->find($activeBranchId);
        if (! $data) {
            return response()->json(['message' => 'Branch not found or inaccessible.', 'status' => false], 404);
        }

        $service_id = ServiceBranches::where('branch_id', $data->id)->pluck('service_id');
        $data['service_id'] = $service_id;

        $custom_field_data = $data->withCustomFields();
        if ($custom_field_data && isset($custom_field_data->custom_fields_data)) {
            $data['custom_field_data'] = $custom_field_data->custom_fields_data->toArray();
        }

        // Payment methods filtered based on Settings → Payment Method integration
        $paymentMethods = $this->getFilteredPaymentMethods();

        return response()->json([
            'data' => $data,
            'PAYMENT_METHODS' => $paymentMethods,
            'status' => true,
        ]);
    }

    public function UpdateBranchSetting(Request $request)
    {
        $user = Auth::user();

        // For managers that can have multiple branches, make sure we update
        // the *currently active* / selected branch, not always the primary one.
        if (! $user->hasRole('manager')) {
            return response()->json(['message' => 'You are not authorized to update this branch.', 'status' => false], 403);
        }

        // Gather all branches the manager can access (primary + assigned)
        $managedBranchIds = Branch::where('manager_id', $user->id)->pluck('id')->toArray();
        $assignedBranchIds = BranchEmployee::where('employee_id', $user->id)->pluck('branch_id')->toArray();
        $primaryBranchId = optional($user->branch)->branch_id;

        $accessibleBranchIds = collect(array_filter(array_merge(
            $managedBranchIds,
            $assignedBranchIds,
            [$primaryBranchId]
        )))->unique()->values();

        // Determine which branch should be updated – mirror logic from branchData()
        $requestedBranchId = $request->input('branch_id');
        $sessionBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');

        $activeBranchId = $requestedBranchId ?? $sessionBranchId ?? $primaryBranchId;
        if ($accessibleBranchIds->isNotEmpty()) {
            if (! $activeBranchId || ! $accessibleBranchIds->contains($activeBranchId)) {
                $activeBranchId = $accessibleBranchIds->first();
            }
        }

        if (! $activeBranchId) {
            return response()->json(['message' => 'No branch assigned to your account.', 'status' => false], 404);
        }

        $query = Branch::findOrFail($activeBranchId);

        $data = $request->except('feature_image');
        if (is_string($request->payment_method)) {
            $data['payment_method'] = explode(',', $request->payment_method);
        }

        $query->update($data);

        if (!empty($request->address) && is_string($request['address'])) {
            $request->address = json_decode($request['address'], true);
            $query->address()->update($request->address);
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        }

        $branch_id = $query->id;

        $manager_id = $request->manager_id;

        BranchEmployee::where('employee_id', $manager_id)->delete();

        $user = User::find($manager_id);

        if ($user) {
            $user->syncRoles(['employee', 'manager']);
        }

        \Artisan::call('cache:clear');

        BranchEmployee::create([
            'branch_id' => $query->id,
            'employee_id' => $manager_id,
            'is_primary' => true,
        ]);

        $service_id = $request->service_id;

        $this->assign_service_branch($service_id, $branch_id);

        $message = __('messages.update_form', ['form' => __('branch.branch_setting')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * Export branches with manager branch filtering
     *
     * @param  Request  $request
     * @param  int|null  $branchId
     * @param  int|null  $employeeId
     * @param  int|null  $excludeManagerId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request, $branchId = null, $employeeId = null, $excludeManagerId = null)
    {
        // Get branch_id for manager filtering
        $explicitBranchId = isset($request->branch_id) && $request->branch_id !== '' ? (int) $request->branch_id : null;
        $selectedBranchId = $request->selected_session_branch_id ?? request()->session()->get('selected_branch');
        $activeBranchId = $explicitBranchId ?? ($selectedBranchId ? (int) $selectedBranchId : null);

        $authUser = auth()->user();
        $isManagerOnly = $authUser && $authUser->hasRole('manager') && ! $authUser->hasRole('admin');
        $managerBranchIds = [];

        if ($isManagerOnly) {
            $managerBranchIds = Branch::where('manager_id', $authUser->id)->pluck('id')->toArray();
            if (empty($managerBranchIds)) {
                $managerBranchIds = BranchEmployee::where('employee_id', $authUser->id)->pluck('branch_id')->toArray();
            }
            $managerBranchIds = array_values(array_unique($managerBranchIds));

            if (!empty($managerBranchIds)) {
                if ($activeBranchId && ! in_array($activeBranchId, $managerBranchIds)) {
                    $activeBranchId = $managerBranchIds[0];
                } elseif (! $activeBranchId) {
                    $activeBranchId = $managerBranchIds[0];
                }
            } else {
                // No branches assigned; ensure we don't unintentionally expose other managers
                $activeBranchId = null;
            }
        }

        return parent::export($request, $activeBranchId);
    }
}
