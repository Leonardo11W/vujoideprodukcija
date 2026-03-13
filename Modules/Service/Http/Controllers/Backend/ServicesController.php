<?php

namespace Modules\Service\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Category\Models\Category;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Modules\Employee\Models\BranchEmployee;
use Modules\Service\Http\Requests\ServiceRequest;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;
use Modules\Service\Models\ServiceGallery;
use Yajra\DataTables\DataTables;

class ServicesController extends Controller
{
    use Authorizable;
    protected string $exportClass = '\App\Exports\ServicesExport';

    public function __construct()
    {
        // Page Title
        $this->module_title = 'service.title';
        // module name
        $this->module_name = 'services';

        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
        // Check for view_service permission and redirect to dashboard if not authorized
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                return $next($request);
            }
            
            // For managers, check if manager role specifically has view_service permission
            $isManager = $user->hasRole('manager');
            if ($isManager) {
                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                if ($managerRole && $managerRole->hasPermissionTo('view_service')) {
                    return $next($request);
                } else {
                    // Manager role doesn't have view_service, redirect to dashboard
                    return redirect()->route('backend.home');
                }
            }
            
            // For non-managers, check if user has view_service permission
            if (!$user->can('view_service')) {
                return redirect()->route('backend.home');
            }
            
            return $next($request);
        })->only('index', 'index_data');
        $this->middleware(['permission:edit_service'])->only('edit', 'update');
        $this->middleware(['permission:add_service'])->only('store', 'create');
        $this->middleware(['permission:delete_service'])->only('destroy');
        
        // Check for service_gallery or edit_service permission for gallery routes
        $this->middleware(function ($request, $next) {
            if (auth()->check()) {
                if (auth()->user()->hasRole('admin')) {
                    return $next($request);
                }
                // Allow both service_gallery (view) and edit_service (add/delete)
                if (auth()->user()->can('service_gallery') || auth()->user()->can('edit_service')) {
                    return $next($request);
                }
            }
            abort(403, 'This action is unauthorized.');
        })->only('getGalleryImages', 'uploadGalleryImages');
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
        $module_action = __('messages.list');
        $columns = CustomFieldGroup::columnJsonValues(new Service());
        $customefield = CustomField::exportCustomFields(new Service());

        $categories = Category::whereNull('parent_id')->get();
        $subcategories = Category::whereNotNull('parent_id')->get();
        $branches = \App\Models\Branch::where('status', 1)->get();

        $export_import = true;
        $isStaff = auth()->user() && (auth()->user()->hasRole('employee') || auth()->user()->hasRole('manager')) && !auth()->user()->hasRole('admin');
        
        $export_columns = [
            [
                'value' => 'name',
                'text' => ' Name',
                'translationKey' => 'export.columns.name',
            ],
            [
                'value' => 'default_price',
                'text' => 'Default Price',
                'translationKey' => 'export.columns.default_price',
            ],
            [
                'value' => 'duration_min',
                'text' => 'Duration Min',
                'translationKey' => 'export.columns.duration_min',
            ],
            [
                'value' => 'category',
                'text' => 'Category',
                'translationKey' => 'export.columns.category',
            ],
        ];
        
        // Hide branches and employees columns for staff users
        if (!$isStaff) {
            $export_columns[] = [
                'value' => 'branches',
                'text' => 'Branches Count',
                'translationKey' => 'export.columns.branches',
            ];
            $export_columns[] = [
                'value' => 'employees',
                'text' => 'Employee Count',
                'translationKey' => 'export.columns.employees',
            ];
        }
        
        $export_columns[] = [
            'value' => 'status',
            'text' => 'Status',
            'translationKey' => 'export.columns.status',
        ];
        $export_url = route('backend.services.export');

        // Get employees for the assign employee offcanvas
        $employees = User::role('employee')->whereNull('deleted_at')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->first_name,
                'avatar' => $emp->profile_image,
            ];
        });

        $service = null;

        return view('service::backend.services.index_datatable', compact('module_action', 'filter', 'categories', 'subcategories', 'branches', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url', 'employees', 'service'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $employee_id = $request->employee_id;
        $category_id = $request->category_id;
        $branch_id = $request->branch_id;
        $exclude_assigned = (bool) $request->get('exclude_assigned', false);
        $term = trim($request->get('q', ''));
        $data = Service::with('employee', 'branches');

        // Only filter by employee_id if it's a valid positive integer
        if (isset($employee_id) && !empty($employee_id) && is_numeric($employee_id) && intval($employee_id) > 0) {
            $employee_id = intval($employee_id);
            if ($exclude_assigned) {
                // Return services NOT already assigned to this employee
                $data = $data->whereDoesntHave('employee', function ($q) use ($employee_id) {
                    $q->where('employee_id', $employee_id);
                });
            } else {
                // Return only services already assigned to this employee
                $data = $data->whereHas('employee', function ($q) use ($employee_id) {
                    $q->where('employee_id', $employee_id);
                });
            }
        }

        if (isset($category_id)) {
            $data->where('category_id', $category_id);
        }

        if (isset($branch_id) && !empty($branch_id) && is_numeric($branch_id) && intval($branch_id) > 0) {
            $branch_id = intval($branch_id);
            $data = $data->whereHas('branches', function ($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            });
        }

        // Filter by search term if provided
        if (!empty($term)) {
            $data = $data->where('name', 'LIKE', "%{$term}%");
        }

        // Only return active services
        $data = $data->where('status', 1)->orderBy('name', 'asc')->get();

        return response()->json($data);
    }

    /* category wise service list */
    public function categort_services_list(Request $request)
    {
        $category = $request->category_id;
        $categoryService = Service::where('category_id', $category)->get();

        return $categoryService;
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'change-status':
                $services = Service::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_service_update');
                break;

            case 'delete':

                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }

                Service::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_service_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function update_status(Request $request, Service $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $authUser = auth()->user();
        $userId = $authUser->id;
        $module_name = $this->module_name;
        $query = Service::query()
            ->with(['category', 'sub_category'])
            ->withCount(['branches', 'employee']);

        $isManager = $authUser->hasRole('manager');
        $isEmployee = $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        // Managers in My Work or employees see only their assigned services
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $filter = $request->filter;

        // Branch selection is ignored in My Work
        $selectedBranchId = $isManagerMyWork ? null : ($request->selected_session_branch_id ?? null);
        
        // Eager load selected branch data to get price/duration
        if ($selectedBranchId) {
            $query->with(['branches' => function($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            }]);
        }


        // Apply employee/manager-my-work scoping
        if ($filterByEmployee) {
            $query->whereHas('employee', function ($q) use ($userId) {
                $q->where('employee_id', $userId);
            });
        }

        // Filter by selected branch from session (set by middleware) unless My Work
        if (! $isManagerMyWork && !empty($selectedBranchId)) {
            $query->whereHas('branches', function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            });
        }

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (isset($filter)) {
            if (isset($filter['category_id'])) {
                $query->where('category_id', $filter['category_id']);
            }
            if (isset($filter['sub_category_id'])) {
                $query->where('sub_category_id', $filter['sub_category_id']);
            }
            // Branch filter from UI still applies when not in My Work
            if (!$isManagerMyWork && isset($filter['branch_id']) && $filter['branch_id'] !== '') {
                $query->whereHas('branches', function ($q) use ($filter) {
                    $q->where('branch_id', $filter['branch_id']);
                });
            }
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {

                $user = auth()->user();

                // Permissions that allow bulk actions
                $hasActionPermission =
                    $user->can('edit_service') ||
                    $user->can('delete_service');

                // If NO permission → return empty (no checkbox)
                if (!$hasActionPermission) {
                    return '';
                }

                // If branch status is inactive AND user cannot change status → hide checkbox
                if (!$row->status && !$user->can('edit_service')) {
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
            ->editColumn('name', function ($data) {
                return view('backend.branch.branch_id', compact('data'));
            })
            // ->addColumn('image', function ($data) {
            //     return '<img src='.$data->feature_image." class='avatar avatar-50 rounded-pill'>";
            // })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('service::backend.services.action_column', compact('module_name', 'data'));
            })
            ->editColumn('employee_count', function ($data) {
				// Hide employee_count column for staff users (employees/managers without admin role)
				$isStaff = auth()->user() && (auth()->user()->hasRole('employee') || auth()->user()->hasRole('manager')) && !auth()->user()->hasRole('admin');
				if ($isStaff) {
					return '-';
				}
				
				$canEdit = auth()->user()->hasRole('admin') || auth()->user()->can('edit_service') || auth()->user()->can('add_service');
				$disabled = $canEdit ? '' : 'disabled';
				return "<b>$data->employee_count</b>  <button type='button' data-assign-module='{$data->id}' data-assign-target='#service-employee-assign-form' data-assign-event='employee_assign' class='btn btn-primary btn-sm rounded text-nowrap ' data-bs-toggle='tooltip' title='" . __('service.assign_staff_to_service') . "' {$disabled}><i class='fa-solid fa-plus p-0'></i></button>";
			})
            ->editColumn('default_price', function ($data) use ($selectedBranchId) {
                $data->resolveBranchSpecificData($selectedBranchId);
                return \Currency::format($data->default_price);
            })
            ->editColumn('duration_min', function ($data) use ($selectedBranchId) {
                $data->resolveBranchSpecificData($selectedBranchId);
                return $data->duration_min . ' Min';
            })
            ->editColumn('status', function ($row) {
				$canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_service');
				$checked = $row->status ? 'checked' : '';
				$disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.services.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '"
                            ' . $checked . '
                            ' . $disabled . '
                        >
                    </div>
                ';
            })

            ->editColumn('category_id', function ($data) {
                $category = isset($data->category->name) ? $data->category->name : '-';
                if (isset($data->sub_category->name)) {
                    $category = $category . ' > ' . $data->sub_category->name;
                }

                return $category;
            })
            ->filterColumn('category_id', function ($query, $keyword) {
                $query->where(function($q) use ($keyword) {
                    $q->whereHas('category', function ($subQ) use ($keyword) {
                        $subQ->where('name', 'LIKE', '%' . $keyword . '%');
                    })
                    ->orWhereHas('sub_category', function ($subQ) use ($keyword) {
                        $subQ->where('name', 'LIKE', '%' . $keyword . '%');
                    });
                });
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
        if (!request()->is_single_branch) {
            $datatable->editColumn('branches_count', function ($data) {
				// Hide branches_count column for staff users (employees/managers without admin role)
				$isStaff = auth()->user() && (auth()->user()->hasRole('employee') || auth()->user()->hasRole('manager')) && !auth()->user()->hasRole('admin');
				if ($isStaff) {
					return '-';
				}
				
				$canEdit = auth()->user()->hasRole('admin') || auth()->user()->can('edit_service') || auth()->user()->can('add_service');
				$disabled = $canEdit ? '' : 'disabled';
				return "<b>$data->branches_count</b>  <button type='button' data-assign-module='{$data->id}' data-assign-target='#service-branch-assign-form' data-assign-event='branch_assign' class='btn btn-primary btn-sm rounded text-nowrap ' data-bs-toggle='tooltip' title='" . __('branch.assign_branch_to_service') . "' {$disabled}><i class='fa-solid fa-plus p-0'></i></button>";
			});
        }

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, Service::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['action', 'image', 'status', 'check', 'branches_count', 'employee_count'], $customFieldColumns))
            ->toJson();
    }

    public function index_list_data(Request $request)
    {
        $term = trim($request->q);

        $query_data = User::role('employee')->where(function ($q) {
            if (!empty($term)) {
                $q->orWhere('name', 'LIKE', "%$term%");
            }
        })->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->first_name . $row->last_name,
                'avatar' => $row->profile_image,
            ];
        }

        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $module_action = 'Create';
        $categories = Category::whereNull('parent_id')->get();
        $subcategories = Category::whereNotNull('parent_id')->get();
        $customefield = CustomField::exportCustomFields(new Service());
        return view('service::backend.services.form_offcanvas', compact('module_action', 'categories', 'subcategories', 'customefield'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(ServiceRequest $request)
    {
        $data = $request->except('feature_image');
        $userId = Auth()->user()->id;

        $query = Service::create($data);
        if (auth()->user()->hasAnyRole(['manager'])) {
            $branchRelation = auth()->user()->branch;
            $branchIdFromRelation = optional($branchRelation)->branch_id ?? optional(optional($branchRelation)->getBranch)->id;
            $sessionBranchId = $request->selected_session_branch_id;
            $branch_id = $sessionBranchId ?: $branchIdFromRelation;

            if ($branch_id) {
                ServiceBranches::create([
                    'service_id' => $query->id,
                    'branch_id' => $branch_id,
                    'service_price' => $query->default_price ?? 0,
                    'duration_min' => $query->duration_min,
                ]);
            }

            ServiceEmployee::firstOrCreate(
                [
                    'employee_id' => Auth()->user()->id,
                    'service_id' => $query->id,
                ],
                []
            );
        }

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'));
        }

        $message = __('messages.create_form', ['form' => __('service.singular_title')]);

        // Return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $query
            ], 200);
        }

        // Otherwise, redirect as before
        return redirect()->route('backend.services.index')->with('success', __('messages.service_data'));
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

        $data = Service::findOrFail($id);

        return view('service::backend.services.show', compact('module_action', "$data"));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Service::findOrFail($id);

        if (!is_null($data)) {
            $authUser = auth()->user();
            if (($authUser->hasRole('manager') || $authUser->hasRole('employee')) && ! $authUser->hasRole('admin')) {
                $selectedBranchId = session()->get('selected_branch');
                if ($selectedBranchId) {
                    $branchService = ServiceBranches::where('service_id', $id)
                        ->where('branch_id', $selectedBranchId)
                        ->first();

                    if ($branchService) {
                        if (isset($branchService->service_price) && $branchService->service_price > 0) {
                            $data->default_price = $branchService->service_price;
                        }
                        if (isset($branchService->duration_min) && $branchService->duration_min > 0) {
                            $data->duration_min = $branchService->duration_min;
                        }
                    }
                }
            }

            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = collect($custom_field_data->custom_fields_data)
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->toArray();
        }

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(ServiceRequest $request, $id)
    {
        try {
            $data = Service::findOrFail($id);

            $request_data = $request->except('feature_image');
            $authUser = auth()->user();

            if (($authUser->hasRole('manager') || $authUser->hasRole('employee')) && ! $authUser->hasRole('admin')) {
                $selectedBranchId = session()->get('selected_branch');
                if ($selectedBranchId) {
                    ServiceBranches::updateOrCreate(
                        ['service_id' => $id, 'branch_id' => $selectedBranchId],
                        [
                            'service_price' => floatval($request_data['default_price']),
                            'duration_min' => $request_data['duration_min']
                        ]
                    );

                    unset($request_data['default_price']);
                    unset($request_data['duration_min']);
                }
            } else {
                // Only update branch prices that match the old default price (haven't been customized)
                if ($data->default_price !== floatval($request_data['default_price'])) {
                    ServiceBranches::where('service_id', $id)
                        ->where('service_price', $data->default_price)
                        ->update(['service_price' => floatval($request_data['default_price'])]);
                }
                // Only update branch durations that match the old default duration (haven't been customized)
                if ($data->duration_min !== $request_data['duration_min']) {
                    ServiceBranches::where('service_id', $id)
                        ->where('duration_min', $data->duration_min)
                        ->update(['duration_min' => $request_data['duration_min']]);
                }
            }

            $data->update($request_data);

            if ($request->custom_fields_data) {
                $data->updateCustomFieldData(json_decode($request->custom_fields_data));
            }

            // Handle feature image
            if ($request->hasFile('feature_image')) {
                // New image uploaded - store it
                storeMediaFile($data, $request->file('feature_image'), 'feature_image');
            } elseif ($request->has('existing_feature_image') && empty($request->existing_feature_image)) {
                // User explicitly removed the image (existing_feature_image is empty)
                $data->clearMediaCollection('feature_image');
            }
            // If neither condition is met, keep the existing image

            $message = __('messages.update_form', ['form' => __('service.singular_title')]);

            // Check if request is AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => $data
                ], 200);
            }

            // Return redirect for non-AJAX requests
            return redirect()->route('backend.services.index')->with('success', __('messages.service_data'));
        } catch (\Exception $e) {
            $errorMessage = __('messages.something_went_wrong');

            // Check if request is AJAX
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => $e->getMessage()
                ], 500);
            }

            // Return redirect for non-AJAX requests
            return redirect()->back()->with('error', $errorMessage);
        }
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

        $data = Service::findOrFail($id);

        $data->branches()->delete();

        $data->employee()->delete();

        $data->delete();

        $message = __('messages.delete_form', ['form' => __('service.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    /**
     * List of trashed ertries
     * works if the softdelete is enabled.
     *
     * @return Response
     */
    public function trashed()
    {
        $module_name_singular = Str::singular($this->module_name);

        $module_action = 'Trash List';

        $data = Service::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate();

        return view('service::backend.services.trash', compact("$data", 'module_name_singular', 'module_action'));
    }

    /**
     * Restore a soft deleted entry.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function restore($id)
    {
        $data = Service::withTrashed()->find($id);
        $data->restore();

        $message = __('messages.service_data');

        return response()->json(['message' => $message, 'status' => true]);
    }

    public function assign_employee_list($id)
    {
        $service_user = ServiceEmployee::whereHas('employee', function ($q) {
            return $q->whereNull('deleted_at');
        })->with('employee')->where('service_id', $id)->get();

        $service_user = $service_user->each(function ($data) {
            $data['name'] = $data->employee->full_name;
            $data['avatar'] = $data->employee->profile_image;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $service_user]);
    }

    public function assign_employee_update($id, Request $request)
    {
        ServiceEmployee::where('service_id', $id)->delete();

        if ($request->has('employees') && is_array($request->employees)) {
            foreach ($request->employees as $employeeId) {
                ServiceEmployee::create([
                    'service_id' => $id,
                    'employee_id' => $employeeId,
                ]);
            }
        }

        return response()->json(['status' => true, 'message' => __('messages.service_staff_update')]);
    }

    /**
     * Render the assign employee offcanvas Blade with all required variables.
     */
    public function assign_employee_offcanvas($id)
    {
        $service = Service::findOrFail($id);
        $employees = User::role('employee')->whereNull('deleted_at')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'avatar' => $emp->profile_image,
            ];
        });
        $assignedEmployees = ServiceEmployee::where('service_id', $id)
            ->with('employee')
            ->get()
            ->map(function ($data) {
                return [
                    'employee_id' => $data->employee_id,
                    'name' => $data->employee->full_name,
                    'avatar' => $data->employee->profile_image,
                ];
            });
        return view('service::backend.services.assign_employee_offcanvas', compact('service', 'employees', 'assignedEmployees'));
    }

    // =========Service Staff Assign list and Assign update ======= //

    public function assign_branch_list($id)
    {
        $service_branch = ServiceBranches::with('branch')->where('service_id', $id)->get();
        $service_branch = $service_branch->each(function ($data) {
            $data['name'] = $data->branch->name;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $service_branch]);
    }

    public function assign_branch_update($id, Request $request)
    {
        $branches = $request->branches;
        if (is_string($branches)) {
            $branches = json_decode($branches, true);
        }
        \Log::info('Assigning branches:', ['service_id' => $id, 'branches' => $branches]);

        // Defensive: Validate branches is array
        if (!is_array($branches)) {
            return response()->json(['status' => false, 'message' => 'Invalid branches data.'], 400);
        }
        // If associative array (object), convert to array of values with branch_id
        if (array_keys($branches) !== range(0, count($branches) - 1)) {
            $branches = array_map(function ($branchId, $data) {
                $data['branch_id'] = $branchId;
                return $data;
            }, array_keys($branches), $branches);
        }

        // Use transaction to prevent data loss
        \DB::transaction(function () use ($id, $branches) {
            ServiceBranches::where('service_id', $id)->delete();

            if (is_array($branches)) {
                foreach ($branches as $key => $value) {
                    if (!isset($value['branch_id'])) {
                        // \Log::error('Missing branch_id in branch assignment', ['branch' => $value]);
                        continue;
                    }
                    
                    // Validate and sanitize price
                    $servicePrice = isset($value['service_price']) ? max(0, floatval($value['service_price'])) : 0;
                    $durationMin = isset($value['duration_min']) ? max(0, floatval($value['duration_min'])) : 0;
                    
                    ServiceBranches::create([
                        'service_id' => $id,
                        'branch_id' => $value['branch_id'],
                        'service_price' => $servicePrice,
                        'duration_min' => $durationMin,
                    ]);
                }
            }
        });

        return response()->json(['status' => true, 'message' => __('messages.service_branch_update')]);
    }

    public function getGalleryImages($id)
    {
        $service = Service::findOrFail($id);

        $data = ServiceGallery::where('service_id', $id)->get();

        return response()->json(['data' => $data, 'service' => $service, 'status' => true]);
    }

    public function uploadGalleryImages(Request $request, $id)
    {
        // Check if user has edit_service permission (not just service_gallery for view-only)
        if (!auth()->user()->can('edit_service')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        $gallery = collect($request->gallery, true);

        $images = ServiceGallery::where('service_id', $id)->whereNotIn('id', $gallery->pluck('id'))->get();

        foreach ($images as $key => $value) {
            $value->clearMediaCollection('gallery_images');
            $value->delete();
        }

        foreach ($gallery as $key => $value) {
            if ($value['id'] == 'null') {
                $serviceGallery = ServiceGallery::create([
                    'service_id' => $id,
                ]);

                $serviceGallery->addMedia($value['file'])->toMediaCollection('gallery_images');

                $serviceGallery->full_url = $serviceGallery->getFirstMediaUrl('gallery_images');
                $serviceGallery->save();
            }
        }

        return response()->json(['message' => __('messages.service_gallery_update'), 'status' => true]);
    }

    public function uniqueServices(Request $request)
    {
        $service = $request->input('service');
        $serviceId = $request->input('service_id');
        $isUnique = true;
        if (!$serviceId) {
            $isUnique = Service::where('name', $service)
                ->doesntExist();
        }
        return response()->json(['isUnique' => $isUnique]);
    }

    public function getSubcategories(Request $request)
    {
        $categoryId = $request->input('category_id');

        if (!$categoryId) {
            // Return all subcategories when no category is selected
            $subcategories = Category::whereNotNull('parent_id')
                ->where('status', 1)
                ->select('id', 'name')
                ->get();
            return response()->json($subcategories);
        }

        $subcategories = Category::where('parent_id', $categoryId)
            ->where('status', 1)
            ->select('id', 'name')
            ->get();

        return response()->json($subcategories);
    }

    public function getEditForm($id)
    {
        $service = Service::findOrFail($id);

        $authUser = auth()->user();
        if (($authUser->hasRole('manager') || $authUser->hasRole('employee')) && ! $authUser->hasRole('admin')) {
            $selectedBranchId = session()->get('selected_branch');
            if ($selectedBranchId) {
                $branchService = ServiceBranches::where('service_id', $id)
                    ->where('branch_id', $selectedBranchId)
                    ->first();

                if ($branchService) {
                    if (isset($branchService->service_price) && $branchService->service_price > 0) {
                        $service->default_price = $branchService->service_price;
                    }
                    if (isset($branchService->duration_min) && $branchService->duration_min > 0) {
                        $service->duration_min = $branchService->duration_min;
                    }
                }
            }
        }

        $categories = Category::whereNull('parent_id')->get();
        $subcategories = Category::whereNotNull('parent_id')->get();
        $customefield = CustomField::exportCustomFields(new Service());

        return view('service::backend.services.edit_form', compact('service', 'categories', 'subcategories', 'customefield'));
    }

    public function getServiceData($id)
    {
        $service = Service::findOrFail($id);

        $authUser = auth()->user();
        if (($authUser->hasRole('manager') || $authUser->hasRole('employee')) && ! $authUser->hasRole('admin')) {
            $selectedBranchId = session()->get('selected_branch');
            if ($selectedBranchId) {
                $branchService = ServiceBranches::where('service_id', $id)
                    ->where('branch_id', $selectedBranchId)
                    ->first();

                if ($branchService) {
                    if (isset($branchService->service_price) && $branchService->service_price > 0) {
                        $service->default_price = $branchService->service_price;
                    }
                    if (isset($branchService->duration_min) && $branchService->duration_min > 0) {
                        $service->duration_min = $branchService->duration_min;
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'default_price' => $service->default_price,
                'duration_min' => $service->duration_min,
                'description' => $service->description,
                'category_id' => $service->category_id,
                'sub_category_id' => $service->sub_category_id,
                'status' => $service->status
            ]
        ]);
    }
}
