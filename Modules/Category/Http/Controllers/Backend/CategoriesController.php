<?php

namespace Modules\Category\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Category\Http\Requests\CategoryRequest;
use Modules\Category\Models\Category;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Yajra\DataTables\DataTables;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class CategoriesController extends Controller
{
    use Authorizable {
        getAbility as traitGetAbility;
    }

    protected string $exportClass = '\App\Exports\CategoryExport';

    public function __construct()
    {
        // Page Title
        $this->module_title = 'category.title';

        // module name
        $this->module_name = 'categories';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => 'fa-regular fa-sun',
            'module_name' => $this->module_name,
        ]);
        
        // Ensure Service Category and Service Subcategory permissions exist
        $this->ensureCategoryPermissionsExist();
        
        // Check for view_service_category permission and if Service Category module exists in config
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            // Check if Service Category module exists in constant.php
            $modules = config('constant.MODULES', []);
            $categoryModuleExists = false;
            
            foreach ($modules as $module) {
                if (isset($module['module_name'])) {
                    $moduleName = strtolower(trim($module['module_name']));
                    if ($moduleName === 'service category' || $moduleName === 'category') {
                        $categoryModuleExists = true;
                        break;
                    }
                }
            }
            
            // If module doesn't exist in config, redirect to dashboard
            if (!$categoryModuleExists) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                return $next($request);
            }
            
            // Check view_service_category permission
            if (!$user->can('view_service_category')) {
                return redirect()->route('backend.home');
            }
            
            return $next($request);
        })->only('index', 'index_data');
        
        // Check for view_service_subcategory permission and if Service Subcategory module exists in config
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            // Check if Service Subcategory module exists in constant.php
            $modules = config('constant.MODULES', []);
            $subcategoryModuleExists = false;
            
            foreach ($modules as $module) {
                if (isset($module['module_name'])) {
                    $moduleName = strtolower(trim($module['module_name']));
                    if ($moduleName === 'service subcategory' || $moduleName === 'subcategory') {
                        $subcategoryModuleExists = true;
                        break;
                    }
                }
            }
            
            // If module doesn't exist in config, redirect to dashboard
            if (!$subcategoryModuleExists) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                return $next($request);
            }
            
            // Check view_service_subcategory permission
            if (!$user->can('view_service_subcategory')) {
                return redirect()->route('backend.home');
            }
            
            return $next($request);
        })->only('index_nested', 'index_nested_data');
        
        // Check for delete_service_category permission when deleting a category (not subcategory)
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                return $next($request);
            }
            
            // Get the category being deleted
            $categoryId = $request->route('id') ?? $request->route('category') ?? null;
            if ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    // If parent_id is null, it's a category (not subcategory)
                    if (is_null($category->parent_id)) {
                        // Check delete_service_category permission
                        if (!$user->can('delete_service_category')) {
                            return response()->json([
                                'message' => __('messages.permission_denied'),
                                'status' => false
                            ], 403);
                        }
                    }
                }
            }
            
            return $next($request);
        })->only('destroy');
        
        // Check for add_service_category or add_service_subcategory permission when creating
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                return $next($request);
            }
            
            // Check if parent_id is present (means it's a subcategory)
            if ($request->has('parent_id') && !empty($request->parent_id)) {
                // Check add_service_subcategory permission
                if (!$user->can('add_service_subcategory')) {
                    return response()->json([
                        'message' => __('messages.permission_denied'),
                        'status' => false
                    ], 403);
                }
            } else {
                // It's a category, check add_service_category permission
                if (!$user->can('add_service_category')) {
                    return response()->json([
                        'message' => __('messages.permission_denied'),
                        'status' => false
                    ], 403);
                }
            }
            
            return $next($request);
        })->only('store');
        
        // Check for edit_service_category or edit_service_subcategory permission when updating
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                    return $next($request);
                }
            
            // Get the category being updated
            $categoryId = $request->route('id') ?? $request->route('category') ?? null;
            if ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    // If parent_id is null, it's a category
                    if (is_null($category->parent_id)) {
                        // Check edit_service_category permission
                        if (!$user->can('edit_service_category')) {
                            return response()->json([
                                'message' => __('messages.permission_denied'),
                                'status' => false
                            ], 403);
                        }
                    } else {
                        // It's a subcategory, check edit_service_subcategory permission
                        if (!$user->can('edit_service_subcategory')) {
                            return response()->json([
                                'message' => __('messages.permission_denied'),
                                'status' => false
                            ], 403);
                        }
                    }
                }
            }
            
            return $next($request);
        })->only('update');
        
        // Check for delete_service_subcategory permission when deleting a subcategory
        $this->middleware(function ($request, $next) {
            if (!auth()->check()) {
                return redirect()->route('backend.home');
            }
            
            $user = auth()->user();
            
            // Admin always has access
            if ($user->hasRole('admin')) {
                    return $next($request);
                }
            
            // Get the category being deleted
            $categoryId = $request->route('id') ?? $request->route('category') ?? null;
            if ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    // If parent_id is not null, it's a subcategory
                    if (!is_null($category->parent_id)) {
                        // Check delete_service_subcategory permission
                        if (!$user->can('delete_service_subcategory')) {
                            return response()->json([
                                'message' => __('messages.permission_denied'),
                                'status' => false
                            ], 403);
                        }
                    }
                }
            }
            
            return $next($request);
        })->only('destroy');
    }
    
    /**
     * Ensure Service Category and Service Subcategory permissions exist
     */
    private function ensureCategoryPermissionsExist()
    {
        $categoryPermissions = [
            'view_service_category',
            'add_service_category',
            'edit_service_category',
            'delete_service_category',
        ];
        
        $subcategoryPermissions = [
            'view_service_subcategory',
            'add_service_subcategory',
            'edit_service_subcategory',
            'delete_service_subcategory',
        ];
        
        $allPermissions = array_merge($categoryPermissions, $subcategoryPermissions);
        
        foreach ($allPermissions as $permissionName) {
            try {
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web'],
                    ['is_fixed' => false]
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to ensure category permission exists', [
                    'permission' => $permissionName,
                    'error' => $e->getMessage()
                ]);
            }
        }
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
        $module_name = $this->module_name;
        $module_action = __('messages.list');
        $columns = CustomFieldGroup::columnJsonValues(new Category());
        $customefield = CustomField::exportCustomFields(new Category());

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => 'Name',
                'translationKey' => 'export.columns.name',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
                'translationKey' => 'export.columns.status',
            ],
            [
                'value' => 'Date',
                'text' => 'Created Date',
                'translationKey' => 'export.columns.created_date',
            ],
        ];
        $export_url = route('backend.categories.export');

        return view('category::backend.categories.index_datatable', compact('module_name', 'filter', 'module_action', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    /**
     * Select Options for Select 2 Request/ Response.
     *
     * @return Response
     */
    public function index_list(Request $request)
    {
        $term = trim($request->q);
        $parentID = $request->parent_id;

        $query_data = Category::where(function ($q) use ($parentID) {
            if (! empty($term)) {
                $q->orWhere('name', 'LIKE', "%$term%");
            }
            if (isset($parentID) && $parentID != 0) {
                $q->where('parent_id', $parentID);
            } else {
                $q->whereNull('parent_id');
            }
        })
            ->where('status', 1) // Add this line to filter by status
            ->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'name' => $row->name,
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
                $branches = Category::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_category_update');
                break;

            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                
                // Check permissions for bulk delete
                $user = auth()->user();
                if (!$user->hasRole('admin')) {
                    $categories = Category::whereIn('id', $ids)->get();
                    $hasCategory = false;
                    $hasSubcategory = false;
                    
                    foreach ($categories as $category) {
                        if (is_null($category->parent_id)) {
                            $hasCategory = true;
                        } else {
                            $hasSubcategory = true;
                        }
                    }
                    
                    // Check delete_service_category permission if deleting categories
                    if ($hasCategory && !$user->can('delete_service_category')) {
                        return response()->json([
                            'message' => __('messages.permission_denied'),
                            'status' => false
                        ], 403);
                    }
                    
                    // Check delete_service_subcategory permission if deleting subcategories
                    if ($hasSubcategory && !$user->can('delete_service_subcategory')) {
                        return response()->json([
                            'message' => __('messages.permission_denied'),
                            'status' => false
                        ], 403);
                    }
                }

                Category::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_category_update');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
                break;
        }

        return response()->json(['status' => true, 'message' => __('messages.bulk_update')]);
    }

    public function update_status(Request $request, Category $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('branch.status_update')]);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;
        $query = Category::query()->with('media')->whereNull('parent_id')->orderBy('updated_at', 'desc');

        $filter = $request->filter;

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
                    $user->can('edit_service_category') ||
                    $user->can('delete_service_category');

                // If NO permission → return empty (no checkbox)
                if (!$hasActionPermission) {
                    return '';
                }

                // If branch status is inactive AND user cannot change status → hide checkbox
                if (!$row->status && !$user->can('edit_service_category')) {
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
            // ->editColumn('name', function ($row) use ($module_name) {
            //     return "<a href='".route('backend.'.$module_name.'.index_nested', ['category_id' => $row->id])."'>$row->name</a>";
            // })
            ->editColumn('name', function ($row) use ($module_name) {
                $link = route('backend.' . $module_name . '.index_nested', ['category_id' => $row->id]);
                $data = $row;
                $image = optional($data)->feature_image ?? default_user_avatar();
                $name = optional($data)->name ?? default_user_name();
                return view('product::backend.category.category_id', compact('image', 'link', 'name'));
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('category::backend.categories.action_column', compact('module_name', 'data'));
            })
            ->addColumn('image', function ($data) {
                return "<img src='" . $data->feature_image . "' class='avatar avatar-50 rounded-pill'>";
            })
            ->editColumn('status', function ($row) {
                $canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_service_category');
                $checked = $row->status ? 'checked' : '';
                $disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.categories.update_status', $row->id) . '"
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
            ->editColumn('created_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->created_at);

                if ($diff < 25) {
                    return $data->created_at->diffForHumans();
                } else {
                    return $data->created_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1');

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, Category::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['action', 'status', 'image', 'check', 'name'], $customFieldColumns))
            ->toJson();
    }

    public function index_nested(Request $request)
    {
        $categories = Category::with('mainCategory')->whereNull('parent_id')->get();

        $filter = [
            'status' => $request->status,
        ];
        $parentID = $request->category_id ?? null;

        $module_action = __('messages.list');

        $module_title = 'category.sub_categories';
        $columns = CustomFieldGroup::columnJsonValues(new Category());
        $customefield = CustomField::exportCustomFields(new Category());

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => 'Name',
                'translationKey' => 'export.columns.name',
            ],
            [
                'value' => 'category_name',
                'text' => 'Category Name',
                'translationKey' => 'export.columns.category_name',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
                'translationKey' => 'export.columns.status',
            ],
            [
                'value' => 'Date',
                'text' => 'Created Date',
                'translationKey' => 'export.columns.created_date',
            ],
        ];
        $export_url = route('backend.sub-categories.export');

        return view('category::backend.categories.index_nested_datatable', compact('parentID', 'module_action', 'filter', 'categories', 'module_title', 'columns', 'customefield', 'export_import', 'export_columns', 'export_url'));
    }

    public function index_nested_data(Request $request, Datatables $datatable)
    {
        $module_name = $this->module_name;
        $query = Category::query()
            ->select('categories.*', 'mainCategory.name as mainCategory_name')
            ->leftJoin('categories as mainCategory', 'mainCategory.id', '=', 'categories.parent_id')
            ->whereNotNull('categories.parent_id')
            ->whereNull('categories.deleted_at')
            ->orderBy('categories.updated_at', 'desc');

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('categories.status', $filter['column_status']);
            }
            if (isset($filter['column_category'])) {
                $query->where('categories.parent_id', $filter['column_category']);
            }
        }

        $datatable = $datatable->eloquent($query)
            ->addColumn('check', function ($row) {

                $user = auth()->user();

                // Permissions that allow bulk actions
                $hasBulkPermission =
                    $user->can('edit_service_subcategory') ||
                    $user->can('delete_service_subcategory');

                // No permission → hide checkbox
                if (!$hasBulkPermission) {
                    return '';
                }

                // Inactive row + no status permission → hide checkbox
                if (!$row->status && !$user->can('edit_service_subcategory')) {
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
                return view('category::backend.categories.sub_action_column', compact('module_name', 'data'));
            })

            // ->addColumn('image', function ($data) {
            //     return '<img src='.$data->feature_image." class='avatar avatar-50 rounded-pill'>";
            // })
            ->editColumn('name', function ($data) {
                return view('backend.branch.branch_id', compact('data'));
            })
            ->editColumn('mainCategory.name', function ($data) {
                return $data->mainCategory->name ?? '-';
            })
            ->filterColumn('mainCategory.name', function ($query, $keyword) {
                $query->where('mainCategory.name', 'like', "%{$keyword}%");
            })
            ->orderColumn('mainCategory_name', function ($query, $order) {
                $query->orderBy('mainCategory_name', $order);
            })
            ->editColumn('status', function ($row) {
                // Match behavior with categories index: always render a switch, but disable it
                // for users who cannot edit. Admins and users with 'edit_service_subcategory' can change it.
                $canChangeStatus = auth()->user()->hasRole('admin') || auth()->user()->can('edit_service_subcategory');
                $checked = $row->status ? 'checked' : '';
                $disabled = $canChangeStatus ? '' : 'disabled';

                return '
                    <div class="form-check form-switch d-flex justify-content-center">
                        <input 
                            type="checkbox"
                            class="form-check-input switch-status-change"
                            data-url="' . route('backend.categories.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '"
                            ' . $checked . '
                            ' . $disabled . '
                        >
                    </div>
                ';
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
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

        // Custom Fields For export
        $customFieldColumns = CustomField::customFieldData($datatable, Category::CUSTOM_FIELD_MODEL, null);

        return $datatable->rawColumns(array_merge(['action', 'status', 'image', 'check'], $customFieldColumns))
            ->toJson();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(CategoryRequest $request)
    {
        $data = $request->except('feature_image');

        $query = Category::create($data);

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        storeMediaFile($query, $request->file('feature_image'));

        $message = __('messages.create_form', ['form' => __('category.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
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

        $data = Category::with('mainCategory')->findOrFail($id);

        return view('category::backend.categories.show', compact('module_action', "$data"));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $data = Category::with('mainCategory')->findOrFail($id);

        if (! is_null($data)) {
            $custom_field_data = $data->withCustomFields();
            $data['custom_field_data'] = collect($custom_field_data->custom_fields_data)
                ->filter(function ($value) {
                    return $value !== null;
                })
                ->toArray();
        }

        $data['feature_image'] = $data->feature_image;
        $data['category_name'] = $data->mainCategory->name ?? null;

        return response()->json(['data' => $data, 'status' => true]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(CategoryRequest $request, $id)
    {
        $query = Category::findOrFail($id);
        // dd($request->all());    

        $data = $request->except('feature_image');

        $query->update($data);

        if ($request->custom_fields_data) {
            $query->updateCustomFieldData(json_decode($request->custom_fields_data));
        }

        if ($request->hasFile('feature_image')) {
            storeMediaFile($query, $request->file('feature_image'), 'feature_image');
        } elseif ($request->boolean('remove_image')) {
            $query->clearMediaCollection('feature_image');
        }

        $message = __('messages.update_form', ['form' => __('category.singular_title')]);

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

        $data = Category::findOrFail($id);

        $data->delete();

        $message = __('messages.delete_form', ['form' => __('category.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function subCategoryExport(Request $request)
    {
        $this->exportClass = '\App\Exports\SubCategoryExport';

        return $this->export($request);
    }

    /**
     * Override getAbility to handle service_category and service_subcategory permissions
     * for Staff role instead of view_category and view_subcategory
     */
    public function getAbility($method)
    {
        $ability = $this->traitGetAbility($method);
        
        if (!empty($ability) && auth()->check()) {
            $user = auth()->user();
            
            // If user is admin, use default ability
            if ($user->hasRole('admin')) {
                return $ability;
            }
            
            // For index and index_data methods, check if user has view_service_category
            if (in_array($method, ['index', 'index_data']) && ($ability === 'view_categories' || $ability === 'view_category')) {
                if ($user->can('view_service_category')) {
                    // Return view_service_category so authorize() will check that permission
                    return 'view_service_category';
                }
            }
            
            // For index_nested and index_nested_data methods, check if user has view_service_subcategory
            if (in_array($method, ['index_nested', 'index_nested_data']) && ($ability === 'view_subcategories' || $ability === 'view_subcategory')) {
                if ($user->can('view_service_subcategory')) {
                    // Return view_service_subcategory so authorize() will check that permission
                    return 'view_service_subcategory';
                }
            }
            
            // For destroy method, check if user has delete_service_category (for categories) or delete_service_subcategory (for subcategories)
            if ($method === 'destroy' && ($ability === 'delete_category' || $ability === 'delete_categories')) {
                // We'll check this in middleware based on parent_id
                // But we can return the appropriate permission here
                return 'delete_service_category'; // Default to category, middleware will handle subcategory check
            }
            
            // For store method, check if user has add_service_category or add_service_subcategory
            if ($method === 'store' && ($ability === 'add_category' || $ability === 'add_categories')) {
                // We'll check this in middleware based on parent_id
                // But we can return the appropriate permission here
                return 'add_service_category'; // Default to category, middleware will handle subcategory check
            }
        }
        
        return $ability;
    }
}
