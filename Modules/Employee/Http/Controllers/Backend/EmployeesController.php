<?php

namespace Modules\Employee\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use App\Currency\CurrencyFacades as Currency;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Commission\Models\EmployeeCommission;
use Modules\Commission\Models\CommissionEarning;
use Modules\Tip\Models\TipEarning;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Modules\Employee\Http\Requests\EmployeeRequest;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Models\ManagerStaff;
use Modules\Service\Models\ServiceEmployee;
use Yajra\DataTables\DataTables;
use Modules\Wallet\Models\Wallet;

class EmployeesController extends Controller
{
    use Authorizable {
        getAbility as traitGetAbility;
    }

    protected string $exportClass = '\App\Exports\EmployeeExport';
    protected $module_title;
    protected $module_name;
    protected $module_path;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'employee.title';

        // module name
        $this->module_name = 'employees';

        // directory path of the module
        $this->module_path = 'employee::backend';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
            'module_path' => $this->module_path,
        ]);
        $this->middleware(['permission:view_staff'])->only('index');
        $this->middleware(['permission:edit_staff'])->only('edit', 'update');
        $this->middleware(['permission:add_staff'])->only('store');
        $this->middleware(['permission:delete_staff'])->only('destroy');
        $this->middleware(['permission:view_review'])->only('review', 'reviewExport', 'review_data');
        $this->middleware(['permission:delete_review'])->only('bulk_action_review', 'destroy_review');
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_action = 'List';
        $columns = CustomFieldGroup::columnJsonValues(new User());
        $customefield = CustomField::exportCustomFields(new User());

        $export_import = true;
        $export_columns = [
            [
                'value' => 'first_name',
                'text' => 'First Name',
            ],
            [
                'value' => 'last_name',
                'text' => 'Last Name',
            ],
            [
                'value' => 'email',
                'text' => 'E-mail',
            ],
            [
                'value' => 'branches',
                'text' => 'Branch',
            ],
            [
                'value' => 'role',
                'text' => 'Role',
            ],
            [
                'value' => 'varification_status',
                'text' => 'Verification Status',
            ],
            // [
            //     'value' => 'is_banned',
            //     'text' => 'Banned',
            // ],
            [
                'value' => 'status',
                'text' => 'Status',
            ],
            [
                'value' => 'wallet_balance',
                'text' => 'Wallet Balance',
            ]
        ];
        $export_url = route('backend.employees.export');

        return view('employee::backend.employees.index', compact('module_action', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $term = trim($request->q);

        $query_data = Branch::where('status', 1)
            ->where(function ($q) use ($term) {
                if (!empty($term)) {
                    $q->orWhere('name', 'LIKE', "%$term%");
                }
            })->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,

            ];
        }

        return response()->json($data);
    }

    public function managers_list(Request $request)
    {
        $term = trim($request->q);
        $branchId = $request->branch_id;
        $authUser = auth()->user();



        // Get ONLY managers (exclude staff/employees)
        // Must have manager role AND is_manager flag set to 1
        $query_data = User::role('manager')
            ->where('status', 1)
            ->where('is_manager', 1)
            ->where(function ($q) use ($term) {
                if (!empty($term)) {
                    $q->where(function ($query) use ($term) {
                        $query->where('first_name', 'LIKE', "%$term%")
                            ->orWhere('last_name', 'LIKE', "%$term%")
                            ->orWhere('email', 'LIKE', "%$term%");
                    });
                }
            });

        // Exclude managers who are already assigned to other branches
        // But if editing a specific branch, include its current manager
        if ($branchId) {
            // When editing a branch, include the current manager of that branch
            // and exclude managers assigned to OTHER branches
            $currentBranchManagerId = \App\Models\Branch::where('id', $branchId)
                ->value('manager_id');

            $query_data->where(function ($q) use ($branchId, $currentBranchManagerId) {
                // Always include the current manager of this branch (if exists)
                if ($currentBranchManagerId) {
                    $q->where('users.id', $currentBranchManagerId);
                }
                // Also include managers not assigned to ANY branch (including this one)
                $q->orWhereNotIn('users.id', function ($subQuery) {
                    // Exclude managers assigned to any branch
                    $subQuery->select('manager_id')
                        ->from('branches')
                        ->whereNotNull('manager_id');
                });
            });
        } else {
            // When creating a new branch, exclude managers already assigned to any branch
            $query_data->whereNotIn('users.id', function ($subQuery) {
                $subQuery->select('manager_id')
                    ->from('branches')
                    ->whereNotNull('manager_id');
            });
        }

        $data = [];
        foreach ($query_data->orderBy('id', 'asc')->get() as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->first_name . ' ' . $row->last_name . ' (' . $row->email . ')',
                'text' => $row->first_name . ' ' . $row->last_name . ' (' . $row->email . ')',
            ];
        }

        return response()->json($data);
    }

    public function employee_list(Request $request)
    {
        $authUser = auth()->user();
        $term = trim($request->q);

        $branchId = $request->branch_id;
        $selectedBranchId = $branchId ?: ($request->selected_session_branch_id ?? $request->session()->get('selected_branch'));
        $role = $request->role;
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // Need To Add Role Base
        // Ensure we select users.* explicitly — joining the pivot in the branch() scope
        // can cause the returned rows to contain pivot columns (like branch_employee.id)
        // which previously resulted in the frontend receiving pivot IDs instead of
        // actual user IDs. Selecting users.* guarantees we hydrate User models.

        // If role is explicitly set to 'manager', show ONLY managers (not employees/staff)
        // and exclude managers already assigned to branches
        if (!empty($role) && $role === 'manager') {
            $query_data = User::select('users.*')
                ->role('manager') // ONLY managers, not employees
                ->where('is_manager', 1) // Ensure is_manager flag is set
                ->with('media', 'branches')
                ->whereNotNull('email_verified_at')
                ->where('status', 1)
                ->where(function ($q) use ($term) {
                    if (!empty($term)) {
                        $q->orWhere('first_name', 'LIKE', "%$term%");
                        $q->orWhere('last_name', 'LIKE', "%$term%");
                    }
                });

            // Exclude managers already assigned to branches (unless editing current branch)
            if ($branchId) {
                // When editing a branch, include the current manager of that branch
                $currentBranchManagerId = \App\Models\Branch::where('id', $branchId)
                    ->value('manager_id');

                $query_data->where(function ($q) use ($branchId, $currentBranchManagerId) {
                    // Always include the current manager of this branch (if exists)
                    if ($currentBranchManagerId) {
                        $q->where('users.id', $currentBranchManagerId);
                    }
                    // Also include managers not assigned to ANY branch
                    $q->orWhereNotIn('users.id', function ($subQuery) {
                        // Exclude managers assigned to any branch
                        $subQuery->select('manager_id')
                            ->from('branches')
                            ->whereNotNull('manager_id')
                            ->whereNull('deleted_at');
                    });
                });
            } else {
                // When creating a new branch, exclude managers already assigned to any branch
                $query_data->whereNotIn('users.id', function ($subQuery) {
                    $subQuery->select('manager_id')
                        ->from('branches')
                        ->whereNotNull('manager_id')
                        ->whereNull('deleted_at');
                });
            }
        } else {
            // Default: show all staff including managers for branch assignment
            $query_data = User::select('users.*')
                ->with('media', 'branches')
                ->whereNotNull('email_verified_at')
                ->where('status', 1)
                ->where(function ($q) use ($term) {
                    if (!empty($term)) {
                        $q->orWhere('first_name', 'LIKE', "%$term%");
                        $q->orWhere('last_name', 'LIKE', "%$term%");
                    }
                });

            // If ignore_branch_filter is 1 (for branch assignment), 
            // exclude managers of OTHER branches
            if ($request->has('ignore_branch_filter')) {
                $query_data->where(function ($q) use ($branchId) {
                    $q->whereNotIn('users.id', function ($subQuery) use ($branchId) {
                        $subQuery->select('manager_id')
                            ->from('branches')
                            ->whereNotNull('manager_id');
                        if ($branchId) {
                            $subQuery->where('id', '!=', $branchId);
                        }
                    })->orWhere('users.id', function ($subQuery) use ($branchId) {
                        // explicitly allow the manager of THIS branch if they are also in the managers table
                        $subQuery->select('manager_id')
                            ->from('branches')
                            ->where('id', $branchId);
                    });
                });
            }
        }

        if ($request->show_in_calender) {
            $query_data->CalenderResource();
        }

        if (!empty($role) && $role !== 'manager') {
            $query_data->role($role);
        }

        if ($isManagerMyWork) {
            // My Work → only the logged-in manager
            $query_data->where('users.id', $authUser->id);
        } elseif (!empty($selectedBranchId) && !$request->has('ignore_branch_filter')) {
            // Branch filter → staff assigned to that branch
            $query_data->whereIn('users.id', function ($q) use ($selectedBranchId) {
                $q->select('employee_id')
                    ->from('branch_employee')
                    ->where('branch_id', $selectedBranchId)
                    ->whereNull('deleted_at');
            });
        } elseif ($request->has('ignore_branch_filter') && $isManager && !$authUser->hasRole('admin')) {
            // Managers see staff from all branches they manage or are assigned to
            $managedBranchIds = \App\Models\Branch::where('manager_id', $authUser->id)->pluck('id')->toArray();
            $assignedBranchIds = \Modules\Employee\Models\BranchEmployee::where('employee_id', $authUser->id)->pluck('branch_id')->toArray();
            $accessibleBranchIds = array_unique(array_merge($managedBranchIds, $assignedBranchIds));

            $query_data->whereIn('users.id', function ($q) use ($accessibleBranchIds) {
                $q->select('employee_id')
                    ->from('branch_employee')
                    ->whereIn('branch_id', $accessibleBranchIds)
                    ->whereNull('deleted_at');
            });
        }

        $query_data = $query_data->orderBy('first_name', 'asc')->orderBy('last_name', 'asc')->get();

        // Log the ids returned to the frontend so we can correlate with services requests
        try {
            $ids = $query_data->pluck('id')->toArray();
        } catch (\Exception $e) {
        }

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->full_name,
                'avatar' => $row->profile_image,
            ];
        }

        return response()->json($data);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'change-status':
                // Need To Add Role Base
                $employee = User::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_employee_update');
                break;

            case 'delete':

                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                ServiceEmployee::whereIn('employee_id', $ids)->delete();
                BranchEmployee::whereIn('employee_id', $ids)->delete();
                User::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_employee_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function index_data(Datatables $datatable, Request $request)
    {


        $module_name = $this->module_name;
        $query = User::select('users.*')->role(['employee', 'manager'])->with('media', 'mainBranch');

        /** @var \App\Models\User|null $authUser */
        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && $request->session()->get('my_work_mode', false);
        $filter = $request->filter;

        $explicitBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
        $selectedBranchId = $isManagerMyWork ? null : ($explicitBranchId ?? ($request->selected_session_branch_id ?? $request->session()->get('selected_branch')));

        // Managers in My Work see only themselves; otherwise honour branch filters
        if ($isManagerMyWork) {
            $query->where('users.id', $authUser->id);
        } elseif (!empty($selectedBranchId)) {
            $query->whereIn('users.id', function ($q) use ($selectedBranchId) {
                $q->select('employee_id')
                    ->from('branch_employee')
                    ->where('branch_id', $selectedBranchId)
                    ->whereNull('deleted_at');
            });
        }

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                $user = auth()->user();

                // Permissions that allow bulk actions on staff
                $hasActionPermission =
                    $user->can('edit_staff') ||
                    $user->can('delete_staff');

                // If NO permission → no checkbox
                if (! $hasActionPermission) {
                    return '';
                }

                // If employee is inactive and user cannot change status → hide checkbox
                if (! $row->status && ! $user->can('edit_staff')) {
                    return '';
                }

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($data) {
                return view('employee::backend.employees.action_column', compact('data'));
            })
            // ->editColumn('image', function ($data) {
            //     return "<img src='".$data->profile_image."'class='avatar avatar-50 rounded-pill'>";
            // })
            ->addColumn('employee_id', function ($data) {
                $Profile_image = $data->profile_image ?? default_user_avatar();
                $name = $data->full_name ?? default_user_name();
                $email = $data->email ?? '--';
                $id = $data->id;
                return view('employee::backend.employees.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })

            ->orderColumn('employee_id', function ($query, $order) {
                $query->orderBy('users.first_name', $order)
                    ->orderBy('users.last_name', $order);
            }, 1)

            ->filterColumn('employee_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where('first_name', 'like', '%' . $keyword . '%')
                            ->orWhere('last_name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })

            ->editColumn('email_verified_at', function ($data) {
                $checked = '';
                if ($data->email_verified_at) {
                    return '<span class="badge bg-soft-success"><i class="fa-solid fa-envelope" style="margin-right: 2px"></i>' . __('employee.msg_verified') . ' </span>';
                }

                return '<button  type="button" data-url="' . route('backend.employees.verify-employee', $data->id) . '" data-token="' . csrf_token() . '" class="button-status-change btn btn-text-danger btn-sm  bg-soft-danger"  id="datatable-row-' . $data->id . '"  name="is_verify" value="' . $data->id . '" ' . $checked . '>Verify</button>';
            })
            ->editColumn('service', function ($data) {
                $canEdit = auth()->user()->hasRole('admin') || auth()->user()->can('edit_staff');
                $disabled = $canEdit ? '' : 'disabled';
                return " <button type='button' data-custom-module='{$data->id}' data-assign-module='{$data->id}' data-assign-target='#package-service-form' data-custom-event='custom_form'  data-assign-event='package_service_form' class='btn btn-primary btn-sm rounded' {$disabled}>{$data->services->count()}</button>";
            })
            ->orderColumn('service', function ($query, $direction) {
                $query->select('packages.*')
                    ->leftJoin('package_services', 'package_services.package_id', '=', 'packages.id')
                    ->selectRaw('COUNT(package_services.id) as service_count')
                    ->groupBy('packages.id');
                $query->orderBy('service_count', $direction);
            })
            ->editColumn('is_manager', function ($data) {
                if ($data->is_manager) {
                    return '<span class="badge bg-soft-danger">Manager</span>';
                }

                return '<span class="badge bg-soft-info">Staff</span>';
            })
            ->addColumn('branch_id', function ($data) {
                $branches = [];

                // For managers, check if they are assigned as manager to any branch
                if ($data->is_manager) {
                    $managedBranch = Branch::where('manager_id', $data->id)->first();
                    if ($managedBranch) {
                        $branches[] = $managedBranch->name;
                    }
                }

                // Also check branch_employee relationship for assigned branches (for both managers and staff)
                if ($data->mainBranch && $data->mainBranch->isNotEmpty()) {
                    $branchNames = $data->mainBranch->pluck('name')->toArray();
                    $branches = array_merge($branches, $branchNames);
                }

                // Remove duplicates and return
                $branches = array_unique($branches);

                // If no branches found, return dash
                if (empty($branches)) {
                    return '-';
                }

                // Return comma-separated branch names
                return implode(', ', $branches);
            })
            ->addColumn('wallet_balance', function ($data) {
                return '<a href="' . route('wallet.history', ['id' => $data->id]) . '">' . Currency::format(optional($data->wallet)->amount) . '</a>';
            })
            ->editColumn('is_banned', function ($data) {
                $canEdit = auth()->user()->hasRole('admin') || auth()->user()->can('edit_staff');
                $checked = $data->is_banned ? 'checked="checked"' : '';
                $disabled = $canEdit ? '' : 'disabled';

                return '
                    <div class="form-check form-switch ">
                        <input type="checkbox" data-url="' . route('backend.employees.block-employee', $data->id) . '" data-token="' . csrf_token() . '" class="switch-status-change form-check-input"  id="datatable-row-' . $data->id . '"  name="is_banned" value="' . $data->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                 ';
            })

            ->editColumn('status', function ($row) {
                $canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_staff');
                $checked = $row->status ? 'checked' : '';
                $disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.employees.update_status', $row->id) . '"
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
            ->rawColumns(['service'])
            ->orderColumns(['id'], '-:column $1');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, User::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['employee_id', 'action', 'service', 'status', 'is_banned', 'email_verified_at', 'check', 'image', 'is_manager', 'wallet_balance'], $customFieldColumns))
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(EmployeeRequest $request)
    {
        $data = $request->all();

        $data['password'] = Hash::make($data['password']);

        // Always verify employee email by default when created from admin panel
        $data = Arr::add($data, 'email_verified_at', Carbon::now());

        $data['status'] = filter_var($request->input('status', 1), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $data = User::create($data);

        if ($data) {
            $wallet = [
                'title' => $data->first_name . ' ' . $data->last_name,
                'user_id' => $data->id,
                'amount' => 0,
            ];
            Wallet::create($wallet);
        }

        $profile = [
            'about_self' => $request->about_self,
            'expert' => $request->expert,
            'facebook_link' => $request->facebook_link,
            'instagram_link' => $request->instagram_link,
            'twitter_link' => $request->twitter_link,
            'dribbble_link' => $request->dribbble_link,
        ];

        $data->profile()->updateOrCreate([], $profile);

        if ($request->custom_fields_data) {
            $data->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->has('profile_image')) {
            $request->file('profile_image');

            storeMediaFile($data, $request->file('profile_image'), 'profile_image');
        }

        $employee_id = $data['id'];

        $roles = ['employee'];

        // Initialize message
        $message = 'Staff Details Added Successfully.';

        if ($request->is_manager) {
            array_push($roles, 'manager');
            if ($request->has('branch_id')) {
                $branch = Branch::where('id', $request->branch_id)->first();
                if ($branch) {
                    $old_manager_id = $branch->manager_id;
                    if ($old_manager_id && $old_manager_id != $employee_id) {
                    // Replacement happened - standard message will be used
                    // Remove old manager from this branch
                    BranchEmployee::where('employee_id', $old_manager_id)
                        ->where('branch_id', $request->branch_id)
                        ->delete();
                }
                    $branch->update(['manager_id' => $employee_id]);
                }
            }
        }

        $data->syncRoles($roles);

        // Ensure employee has default permissions
        if (in_array('employee', $roles)) {
            \App\Helpers\AuthHelper::ensureEmployeeDefaultPermissions($data);
        }

        Artisan::call('cache:clear');

        if ($request->has('branch_id')) {
            $branch_data = [
                'employee_id' => $employee_id,
                'branch_id' => $request->branch_id,
            ];
            BranchEmployee::create($branch_data);
        }

        if ($request->has('service_id')) {
            if ($request->service_id !== null) {
                $services = explode(',', $request->service_id);

                foreach ($services as $value) {
                    $service_data = [

                        'employee_id' => $employee_id,
                        'service_id' => $value,

                    ];
                    ServiceEmployee::create($service_data);
                }
            }
        }
        if (isset($request->commission_id) && $request->has('commission_id')) {
            $commission_data = [
                'employee_id' => $employee_id,
                'commission_id' => $request->commission_id,
            ];

            EmployeeCommission::updateOrCreate($commission_data, $commission_data);
        }

        // Handle manager assignments
        if ($request->has('manager_ids') && !empty($request->manager_ids)) {
            // Delete existing manager assignments for this staff
            ManagerStaff::where('staff_id', $employee_id)->delete();

            // Add new manager assignments
            $managerIds = is_array($request->manager_ids)
                ? $request->manager_ids
                : explode(',', $request->manager_ids);

            foreach ($managerIds as $managerId) {
                if (!empty($managerId)) {
                    ManagerStaff::create([
                        'manager_id' => $managerId,
                        'staff_id' => $employee_id,
                    ]);
                }
            }
        }

        return response()->json(['message' => $message, 'data' => $data, 'status' => true], 200);
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

        $employee = User::role('employee')->with(['wallet'])->findOrFail($id);

        $totalBookings = Booking::whereHas('booking_service', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->count();

        $completedBookings = Booking::whereHas('booking_service', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('status', 'completed')->count();

        $cancelledBookings = Booking::whereHas('booking_service', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('status', 'cancelled')->count();

        $recentBookings = Booking::with(['booking_service.employee', 'branch.address.country_data'])
            ->whereHas('booking_service', function ($q) use ($employee) {
                $q->where('employee_id', $employee->id);
            })
            ->orderByDesc('start_date_time')
            ->limit(12)
            ->get();

        $pendingBookings = Booking::whereHas('booking_service', function ($q) use ($employee) {
            $q->where('employee_id', $employee->id);
        })->where('status', 'pending')->count();
        $totalCommission = CommissionEarning::where('employee_id', $employee->id)
            ->whereHas('getbooking', function ($q) {
                $q->where('status', 'completed');
            })
            ->sum('commission_amount');

        $totalTips = TipEarning::where('employee_id', $employee->id)->sum('tip_amount');

        $totalEarnings = $totalCommission + $totalTips;
        $recentEarnings = collect();
        $commissionEarnings = CommissionEarning::where('employee_id', $employee->id)
            ->whereHas('getbooking', function ($q) {
                $q->where('status', 'completed');
            })
            ->selectRaw('DATE(created_at) as date, SUM(commission_amount) as total_commission, COUNT(*) as commission_count')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $tipEarnings = TipEarning::where('employee_id', $employee->id)
            ->selectRaw('DATE(created_at) as date, SUM(tip_amount) as total_tips, COUNT(*) as tip_count')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        $allDates = $commissionEarnings->pluck('date')->merge($tipEarnings->pluck('date'))->unique()->sortDesc();

        foreach ($allDates as $date) {
            $commission = $commissionEarnings->where('date', $date)->first();
            $tip = $tipEarnings->where('date', $date)->first();

            $recentEarnings->push((object) [
                'date' => $date,
                'created_at' => $date,
                'commission_amount' => $commission ? $commission->total_commission : 0,
                'tip_amount' => $tip ? $tip->total_tips : 0,
                'total_amount' => ($commission ? $commission->total_commission : 0) + ($tip ? $tip->total_tips : 0)
            ]);
        }

        $recentEarnings = $recentEarnings->sortByDesc('date')->take(10);

        // Get booking status constants for proper display
        $bookingStatuses = \Modules\Constant\Models\Constant::getAllConstant()
            ->where('type', 'BOOKING_STATUS')
            ->pluck('value', 'name');

        $data = [
            'employee' => $employee,
            'totalBookings' => $totalBookings,
            'completedBookings' => $completedBookings,
            'cancelledBookings' => $cancelledBookings,
            'recentBookings' => $recentBookings,
            'pendingBookings' => $pendingBookings,
            'totalEarnings' => $totalEarnings,
            'totalCommission' => $totalCommission,
            'totalTips' => $totalTips,
            'recentEarnings' => $recentEarnings,
            'bookingStatuses' => $bookingStatuses,
        ];

        return view('employee::backend.employees.employee_detail', compact('data', 'module_action'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = User::role('employee')->with('branches', 'services', 'commissions', 'profile', 'managers')->findOrFail($id);
        if (!is_null($data)) {
            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = collect($custom_field_data->custom_fields_data)
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->toArray();
        }

        $data['branch_id'] = $data->branch->branch_id ?? null;

        $data['service_id'] = $data->services->pluck('service_id') ?? [];

        $data['commission_id'] = $data->commissions()->first()->commission_id ?? null;

        $data['manager_ids'] = $data->managers()->pluck('manager_id')->toArray() ?? [];

        $data['profile_image'] = $data->profile_image;

        $data['about_self'] = $data->profile->about_self ?? null;

        $data['expert'] = $data->profile->expert ?? null;

        $data['facebook_link'] = $data->profile->facebook_link ?? null;

        $data['instagram_link'] = $data->profile->instagram_link ?? null;

        $data['twitter_link'] = $data->profile->twitter_link ?? null;

        $data['dribbble_link'] = $data->profile->dribbble_link ?? null;

        $data['status'] = $data->status ? true : false;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(EmployeeRequest $request, $id)
    {
        $data = User::role('employee')->findOrFail($id);

        $request_data = $request->except('profile_image');

        if (isset($request->password) && $request->password !== 'undefined' && !empty($request->password)) {
            $request_data['password'] = Hash::make($request_data['password']);
        } else {
            $request_data = $request->except('password');
        }

        $request_data['status'] = filter_var($request->input('status', $data->status), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

        $data->update($request_data);

        $profile = [
            'about_self' => $request->about_self,
            'expert' => $request->expert,
            'facebook_link' => $request->facebook_link,
            'instagram_link' => $request->instagram_link,
            'twitter_link' => $request->twitter_link,
            'dribbble_link' => $request->dribbble_link,
        ];

        $data->profile()->updateOrCreate([], $profile);

        if ($request->custom_fields_data) {
            $data->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->has('profile_image') && $request->file('profile_image')) {
            storeMediaFile($data, $request->file('profile_image'), 'profile_image');
        } elseif ($request->has('remove_profile_image') && $request->get('remove_profile_image') == '1') {
            // User explicitly clicked remove button - clear the image
            $data->clearMediaCollection('profile_image');
        }

        BranchEmployee::where('employee_id', $id)->delete();

        ServiceEmployee::where('employee_id', $id)->delete();

        EmployeeCommission::where('employee_id', $id)->delete();

        $roles = ['employee'];

        $employee_id = $data->id;

        if ($request->is_manager) {
            array_push($roles, 'manager');
            if ($request->has('branch_id')) {
                $branch = Branch::where('id', $request->branch_id)->first();
                if ($branch) {
                    $old_manager_id = $branch->manager_id;
                if ($old_manager_id && $old_manager_id != $employee_id) {
                    // Remove old manager from this branch
                    BranchEmployee::where('employee_id', $old_manager_id)
                        ->where('branch_id', $request->branch_id)
                        ->delete();
                }
                    $branch->update(['manager_id' => $employee_id]);
                }
            }
        }

        // $data->syncRoles($roles);

        Artisan::call('cache:clear');

        if ($request->has('branch_id')) {
            $branch_data = [
                'employee_id' => $id,
                'branch_id' => $request->branch_id,
            ];

            BranchEmployee::create($branch_data);
        }

        if ($request->has('service_id')) {
            if ($request->service_id !== null) {
                $services = explode(',', $request->service_id);

                foreach ($services as $value) {
                    $service_data = [

                        'employee_id' => $employee_id,
                        'service_id' => $value,

                    ];
                    ServiceEmployee::create($service_data);
                }
            }
        }

        if ($request->commission_id) {
            $commission_data = [

                'employee_id' => $id,
                'commission_id' => $request->commission_id,
            ];

            EmployeeCommission::updateOrCreate($commission_data, $commission_data);
        }

        // Handle manager assignments
        if ($request->has('manager_ids')) {
            // Delete existing manager assignments for this staff
            ManagerStaff::where('staff_id', $id)->delete();

            // Add new manager assignments if provided
            if (!empty($request->manager_ids)) {
                $managerIds = is_array($request->manager_ids)
                    ? $request->manager_ids
                    : explode(',', $request->manager_ids);

                foreach ($managerIds as $managerId) {
                    if (!empty($managerId)) {
                        ManagerStaff::create([
                            'manager_id' => $managerId,
                            'staff_id' => $id,
                        ]);
                    }
                }
            }
        }

        $message = 'Staff Details Updated Successfully.';

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

        // Find user by ID with role 'employee'
        $data = User::role('employee')->findOrFail($id);

        $bookingIds = BookingService::where('employee_id', $id)->pluck('booking_id');

        $statusUpdate = Booking::whereIn('id', $bookingIds)
            ->where('status', '!=', 'completed')
            ->update(['status' => 'cancelled']);

        $data->services()->forceDelete();
        $data->tokens()->delete();

        $data->forceDelete();

        $message = __('messages.delete_form', ['form' => __('employee.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }


    public function update_status(Request $request, $id)
    {
        $data = User::role('employee')->findOrFail($id);
        $data->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }

    public function change_password(Request $request)
    {
        $payload = $request->all();
        $employee_id = $payload['employee_id'] ?? null;
        $old_password = $payload['old_password'] ?? '';
        $new_password = $payload['password'] ?? '';
        $confirm_password = $payload['confirm_password'] ?? '';

        if (! $employee_id) {
            return response()->json(['message' => __('messages.validation_error'), 'status' => false], 422);
        }

        $user = User::role('employee')->findOrFail($employee_id);

        if (! Hash::check($old_password, $user->password)) {
            return response()->json(['message' => __('messages.old_password_mismatch'), 'errors' => ['old_password' => __('messages.old_password_mismatch')], 'status' => false], 403);
        }

        if ($old_password === $new_password) {
            return response()->json(['message' => __('messages.new_password_mismatch'), 'errors' => ['password' => __('messages.new_password_mismatch')], 'status' => false], 422);
        }

        if ($new_password !== $confirm_password) {
            return response()->json(['message' => __('messages.password_mismatch'), 'errors' => ['confirm_password' => __('messages.password_mismatch')], 'status' => false], 422);
        }

        $user->update(['password' => Hash::make($new_password)]);

        $message = __('messages.password_update');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function block_employee(Request $request, User $id)
    {
        $id->update(['is_banned' => $request->status]);

        if ($request->status == 1) {
            $message = __('messages.employee_block');
        } else {
            $message = __('messages.employee_unblock');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function verify_employee(Request $request, $id)
    {
        $data = User::role('employee')->findOrFail($id);

        $current_time = Carbon::now();

        $data->update(['email_verified_at' => $current_time]);

        return response()->json(['status' => true, 'message' => __('messages.employee_verify')]);
    }

    public function review(Request $request)
    {
        $module_title = __('employee.review_title');

        $module_name = 'review';

        $filter = $request->filter;
        $export_import = true;
        $export_columns = [
            [
                'value' => 'user_id',
                'text' => 'Customer Name',
                'translationKey' => 'export.columns.customer',
            ],
            [
                'value' => 'employee_id',
                'text' => 'Staff Name',
                'translationKey' => 'export.columns.staff',
            ],
            [
                'value' => 'review_msg',
                'text' => 'Review Message',
                'translationKey' => 'export.columns.review_message',
            ],
            [
                'value' => 'rating',
                'text' => 'Rating',
                'translationKey' => 'export.columns.rating',
            ],
            [
                'value' => 'updated_at',
                'text' => 'Updated Date',
                'translationKey' => 'export.columns.updated_date',
            ],
        ];
        $export_url = route('backend.employees.reviewExport');

        return view('employee::backend.employees.review', compact('module_title', 'module_name', 'filter', 'export_import', 'export_columns', 'export_url'));
    }

    public function reviewExport(Request $request)
    {
        $this->exportClass = '\App\Exports\ReviewsExport';

        return $this->export($request);
    }

    public function review_data(Datatables $datatable, Request $request)
    {
        // dd('test');
        $query = EmployeeRating::with('user', 'employee');

        // Filter by employee if employee is logged in
        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && $request->session()->get('my_work_mode', false);

        // Filter by employee if employee is logged in (but not if they're also a manager in my work mode - handled below)
        if ($authUser && $isEmployee && !$isManager) {
            $query->where('employee_id', $authUser->id);
        }

        $filter = $request->filter;

        // Filter by branch if manager is logged in (including managers who are also admins)
        if ($isManager) {
            // If "my work" mode is active, show only reviews where manager is the employee
            if ($isManagerMyWork) {
                $query->where('employee_id', $authUser->id);
            } else {
                // Get branch ID from multiple sources: filter, request, or session (ignored in my work mode)
                $explicitBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
                $selectedBranchId = $explicitBranchId ?? ($request->selected_session_branch_id ?? $request->session()->get('selected_branch'));

                if ($selectedBranchId) {
                    // Filter reviews through BranchEmployee relationship
                    $query->whereHas('employee', function ($empQuery) use ($selectedBranchId) {
                        $empQuery->whereHas('branches', function ($branchQuery) use ($selectedBranchId) {
                            $branchQuery->where('branch_id', $selectedBranchId);
                        });
                    });
                }
            }
        }
        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($data) {
                $user = auth()->user();

                // Permissions that allow bulk actions on variations
                $hasActionPermission =
                    $user->can('delete_review');


                // If NO permission → no checkbox
                if (! $hasActionPermission) {
                    return '';
                }

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $data->id . '"  name="datatable_ids[]" value="' . $data->id . '" onclick="dataTableRowCheck(' . $data->id . ')">';
            })
            ->addColumn('image', function ($data) {
                return '<img src=' . optional($data->user)->profile_image . " class='avatar avatar-50 rounded-pill'>";
            })
            ->addColumn('action', function ($data) {
                return view('employee::backend.employees.review_action_column', compact('data'));
            })

            // ->editColumn('employee_id', function ($data) {
            //     $employee_id = isset($data->employee->full_name) ? $data->employee->full_name : '-';

            //     return $employee_id;
            // })
            ->editColumn('employee_id', function ($data) {
                $Profile_image = optional($data->employee)->profile_image ?? default_user_avatar();
                $name = optional($data->employee)->full_name ?? default_user_name();
                $email = optional($data->employee)->email ?? '--';
                $id = optional($data->employee)->id ?? null;
                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->filterColumn('employee_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('employee', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderColumn('employee_id', function ($query, $direction) {
                $query->select('employee_rating.*')
                    ->leftJoin('users', 'users.id', '=', 'employee_rating.employee_id')
                    ->orderBy('users.first_name', $direction)
                    ->orderBy('users.last_name', $direction);
            })

            ->editColumn('user_id', function ($data) {
                $Profile_image = optional($data->user)->profile_image ?? default_user_avatar();
                $name = optional($data->user)->full_name ?? default_user_name();
                $email = optional($data->user)->email ?? '--';
                $id = optional($data->user)->id ?? null;
                return view('booking::backend.bookings.datatable.user_id', compact('Profile_image', 'name', 'email', 'id'));
                // return view('employee::backend.employees.review_user_id', compact('data'));
            })
            ->filterColumn('user_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderColumn('user_id', function ($query, $direction) {
                $query->select('employee_rating.*')
                    ->leftJoin('users', 'users.id', '=', 'employee_rating.user_id')
                    ->orderBy('users.first_name', $direction)
                    ->orderBy('users.last_name', $direction);
            })

            // ->editColumn('user_id', function ($data) {
            //     $user_id = isset($data->user->full_name) ? $data->user->full_name : '-';

            //     return $user_id;
            // })

            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->created_at->diffForHumans();
                } else {
                    return $data->created_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        return $datatable->rawColumns(array_merge(['action', 'image', 'check']))
            ->toJson();
    }

    public function bulk_action_review(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'delete':

                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                EmployeeRating::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_review_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function destroy_review($id)
    {
        $module_title = __('employee.review');

        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $data = EmployeeRating::findOrFail($id);

        $data->delete();

        $message = __('messages.delete_form', ['form' => __($module_title)]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }
    public function employeeServices($id)
    {
        // Find the user with the specified ID and role 'employee'
        $user = User::role('employee')->with('services.service')->findOrFail($id);

        $data = [];

        // Check if the user has any services
        if ($user->services->isEmpty()) {
            return response()->json(['data' => [], 'status' => false, 'message' => 'No services found for this employee.'], 404);
        }

        foreach ($user->services as $serviceEmployee) {
            // Assuming 'service' relationship exists on ServiceEmployee
            $data[] = [
                'service_id' => $serviceEmployee->service->id,
                'service_name' => $serviceEmployee->service->name,
                'duration_min' => $serviceEmployee->service->duration_min,
                'service_price' => $serviceEmployee->service->default_price,
            ];
        }

        return response()->json(['data' => $data, 'status' => true], 200);
    }

    public function destroyEmployeeService($employeeId, $serviceId)
    {
        // Remove service assignment for a given employee
        $deleted = ServiceEmployee::where('employee_id', $employeeId)
            ->where('service_id', $serviceId)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status' => true,
                'message' => __('messages.delete_form', ['form' => __('service.singular_title')]),
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => __('messages.something_went_wrong')
        ], 422);
    }

    public function addEmployeeService(Request $request, $employeeId)
    {
        $serviceIds = $request->input('service_ids');
        if (empty($serviceIds) || !is_array($serviceIds)) {
            return response()->json([
                'status' => false,
                'message' => __('messages.validation_error'),
                'errors' => ['service_ids' => __('validation.required', ['attribute' => __('service.plural_title') ?? 'services'])]
            ], 422);
        }

        $serviceIds = array_unique(array_map('intval', $serviceIds));
        $existing = ServiceEmployee::where('employee_id', $employeeId)
            ->whereIn('service_id', $serviceIds)
            ->pluck('service_id')
            ->toArray();

        $toInsert = array_diff($serviceIds, $existing);
        $payload = [];
        foreach ($toInsert as $sid) {
            $payload[] = ['employee_id' => $employeeId, 'service_id' => $sid];
        }
        if (!empty($payload)) {
            ServiceEmployee::insert($payload);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.create_form', ['form' => __('service.plural_title') ?? 'Services'])
        ]);
    }

    public function getAbility($method)
    {
        // Bypass automatic authorization for the 'show' method
        // This allows staff/employees to view the details page without needing the 'view_staff' permission
        if ($method === 'show') {
            return null;
        }

        $ability = $this->traitGetAbility($method);

        // Fix for Authorizable trait inferring 'view_employee' instead of 'view_staff'
        if (!empty($ability)) {
            $ability = str_replace('_employee', '_staff', $ability);
            $ability = str_replace('_employees', '_staff', $ability);
        }

        return $ability;
    }

    public function check_branch_availability(Request $request)
    {
        $branch_id = $request->branch_id;
        $employee_id = $request->employee_id ?? 0;

        if (!$branch_id) return response()->json(['status' => true]);

        $branch = Branch::find($branch_id);

        if ($branch && $branch->manager_id && $branch->manager_id != $employee_id) {
            return response()->json([
                'status' => false,
                'message' => 'This branch already has a manager. The new staff member will be assigned as the manager, and the existing manager will be automatically replaced.'
            ]);
        }

        return response()->json(['status' => true]);
    }
}
