<?php

namespace App\Http\Middleware;

use App\Trait\Menu;
use Illuminate\Support\Arr;
use Modules\MenuBuilder\Models\MenuBuilder;
use Spatie\Permission\Models\Permission;

class GenerateMenus
{
    use Menu;
    
    /**
     * Safely check if user has permission, handling cases where permission doesn't exist
     * 
     * @param \App\Models\User $user
     * @param string $permissionName
     * @return bool
     */
    protected function safeCanCheck($user, $permissionName)
    {
        try {
            // First check if permission exists in database
            $permissionExists = Permission::where('name', $permissionName)
                ->where('guard_name', 'web')
                ->exists();
            
            if (!$permissionExists) {
                return false;
            }
            
            // If permission exists, check if user has it
            return $user->can($permissionName);
        } catch (\Exception $e) {
            \Log::warning('Error checking permission', [
                'permission' => $permissionName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($menuname, $type, $arraymenu)
    {
        \Menu::make($menuname, function ($menu) use ($type, $arraymenu) {
            $menuArray = MenuBuilder::getAllMenu()->where('menu_type', $type);

            if (count($menuArray) == 0) {
                $arr = [];
                foreach (config('menubuilder.'.$arraymenu) as $key => $value) {
                    // code...
                    $arr[] = array_merge(config('menubuilder.MENU'), $value);
                }
                foreach ($arr as $key => $value) {
                    $this->saveMenu($value);
                }

                $menuArray = MenuBuilder::getAllMenu()->where('menu_type', $type);
            }

            \Log::info('Starting menu generation', [
                'total_menus' => count($menuArray),
                'user' => auth()->check() ? auth()->user()->email : 'guest',
                'user_roles' => auth()->check() ? auth()->user()->roles->pluck('name')->toArray() : []
            ]);
            
            foreach ($menuArray as $key => $value) {
                // Force-correct permissions for specific menu items that have been
                // updated in config but may still have old permissions in DB.
                // Reviews menu should only depend on `view_review`.
                if ($value->route === 'backend.employees.review') {
                    $value->permission = ['view_review'];
                }
                
                // Force Location menu to require view_location permission
                $isLocationMenu = stripos($value->title ?? '', 'location') !== false || 
                    $value->nickname === 'sidebarlocation' ||
                    stripos($value->title ?? '', 'sidebar.location') !== false;
                
                if ($isLocationMenu) {
                    \Log::info('Location menu found in menuArray', [
                        'title' => $value->title,
                        'nickname' => $value->nickname,
                        'current_permission' => $value->permission,
                        'status' => $value->status,
                        'has_children' => isset($value->children) && is_countable($value->children) && count($value->children) > 0,
                        'children_count' => isset($value->children) && is_countable($value->children) ? count($value->children) : 0
                    ]);
                    
                    // Ensure permission is set
                    if (empty($value->permission) || !is_array($value->permission) || !in_array('view_location', $value->permission ?? [])) {
                        $value->permission = ['view_location'];
                        \Log::info('Location menu permission set to view_location');
                    }
                    
                    // Also update Location child menus (City, State, Country)
                    if (isset($value->children) && is_countable($value->children) && count($value->children) > 0) {
                        foreach ($value->children as $child) {
                            if (stripos($child->title ?? '', 'city') !== false ||
                                stripos($child->title ?? '', 'state') !== false ||
                                stripos($child->title ?? '', 'country') !== false ||
                                stripos($child->route ?? '', 'city') !== false ||
                                stripos($child->route ?? '', 'state') !== false ||
                                stripos($child->route ?? '', 'country') !== false) {
                                if (empty($child->permission) || !is_array($child->permission) || !in_array('view_location', $child->permission ?? [])) {
                                    $child->permission = ['view_location'];
                                }
                            }
                        }
                    }
                }

                if ($value->status) {
                    if ($isLocationMenu) {
                        \Log::info('Creating Location menu item via makeMenu', [
                            'title' => $value->title,
                            'permission' => $value->permission,
                            'menu_item_type' => $value->menu_item_type ?? 'unknown'
                        ]);
                    }
                    $this->makeMenu($menu, $value);
                } else if ($isLocationMenu) {
                    \Log::warning('Location menu is INACTIVE, not creating');
                }
            }
            
            \Log::info('Menu items created, starting filter', [
                'total_items_before_filter' => count($menu->roots())
            ]);

            // Access Permission Check
            $menu->filter(function ($item) {
                $permissions = $item->data('permission');
                $title = $item->data('title') ?? '';
                $nickname = $item->nickname ?? '';
                $itemTitle = $item->title ?? '';
                $route = $item->data('route') ?? '';
                // Also check the actual route from the link if available
                $itemRoute = $item->route ?? '';
                $itemUrl = $item->url() ?? '';
                
                // Check if this is Service List menu item (check both route sources and title)
                $isServiceListItem = (stripos($route, 'services.index') !== false || stripos($itemRoute, 'services.index') !== false || 
                    stripos($itemUrl, '/services') !== false) 
                    && stripos($route, 'categories') === false 
                    && stripos($itemRoute, 'categories') === false
                    && (stripos(strtolower($itemTitle), 'list') !== false || stripos(strtolower($title), 'list') !== false);
                
                // Check if this is Product Categories or Product Subcategories FIRST (before Service Categories)
                // This must be checked first because product categories also contain "categories.index" in the route
                $isProductCategoriesItem = (stripos($route, 'products-categories.index') !== false || stripos($itemRoute, 'products-categories.index') !== false)
                    && stripos($route, 'index_nested') === false 
                    && stripos($itemRoute, 'index_nested') === false;
                $isProductSubcategoriesItem = stripos($route, 'products-categories.index_nested') !== false || stripos($itemRoute, 'products-categories.index_nested') !== false;
                
                // Check if this is Service Categories or Subcategories menu item (exclude product categories)
                $isCategoriesItem = (stripos($route, 'categories.index') !== false || stripos($itemRoute, 'categories.index') !== false) 
                    && stripos($route, 'index_nested') === false 
                    && stripos($itemRoute, 'index_nested') === false
                    && !$isProductCategoriesItem; // Exclude product categories
                $isSubcategoriesItem = (stripos($route, 'categories.index_nested') !== false || stripos($itemRoute, 'categories.index_nested') !== false)
                    && !$isProductSubcategoriesItem; // Exclude product subcategories
                
                // Hide Service List if user doesn't have view_service permission
                // For managers, check if manager role specifically has the permission
                if ($isServiceListItem && auth()->check()) {
                    $user = auth()->user();
                    if (!$user->hasRole('admin')) {
                        $hasViewService = false;
                        $isManager = $user->hasRole('manager');
                        
                        if ($isManager) {
                            // For managers, check if manager role specifically has view_service permission
                            $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                            if ($managerRole && $managerRole->hasPermissionTo('view_service')) {
                                $hasViewService = true;
                            }
                        } else {
                            // For non-managers, use normal permission check
                            $hasViewService = $user->can('view_service');
                        }
                        
                        if (!$hasViewService) {
                            \Log::info('❌ Hiding Service List - user does NOT have view_service permission', [
                                'route' => $route ?? 'N/A',
                                'itemRoute' => $itemRoute ?? 'N/A',
                                'itemUrl' => $itemUrl ?? 'N/A',
                                'itemTitle' => $itemTitle ?? 'N/A',
                                'title' => $title ?? 'N/A',
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'is_manager' => $isManager,
                                'has_view_service' => $hasViewService,
                                'user_can_view_service' => $user->can('view_service')
                            ]);
                            return false; // Hide Service List if user doesn't have view_service permission
                        }
                    }
                }
                
                // If this is Product Categories or Product Subcategories, check if modules exist and user has permission FIRST
                // This must be checked BEFORE service categories because product categories also contain "categories.index"
                if ($isProductCategoriesItem || $isProductSubcategoriesItem) {
                    $modules = config('constant.MODULES', []);
                    $productCategoryModuleExists = false;
                    $productSubcategoryModuleExists = false;
                    
                    foreach ($modules as $module) {
                        if (isset($module['module_name'])) {
                            $moduleName = strtolower(trim($module['module_name']));
                            // Check for "Product Category"
                            if ($moduleName === 'product category') {
                                $productCategoryModuleExists = true;
                            }
                            // Check for "Product Subcategory"
                            if ($moduleName === 'product subcategory') {
                                $productSubcategoryModuleExists = true;
                            }
                        }
                    }
                    
                    // Hide Product Categories menu item if Product Category module doesn't exist in config
                    if ($isProductCategoriesItem && !$productCategoryModuleExists) {
                        return false;
                    }
                    
                    // Hide Product Subcategories menu item if Product Subcategory module doesn't exist in config
                    if ($isProductSubcategoriesItem && !$productSubcategoryModuleExists) {
                        return false;
                    }
                    
                    // Check permissions for Product Categories
                    if ($isProductCategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $user->can('view_product_category');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo('view_product_category')) {
                                    $hasPermission = true;
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Product Categories menu - user does NOT have view_product_category permission', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'is_manager' => $isManager,
                                    'has_permission' => $hasPermission
                                ]);
                                return false; // Hide if user doesn't have view_product_category permission
                            }
                        }
                    }
                    
                    // Check permissions for Product Subcategories
                    if ($isProductSubcategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $user->can('view_product_subcategory');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo('view_product_subcategory')) {
                                    $hasPermission = true;
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Product Subcategories menu - user does NOT have view_product_subcategory permission', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'is_manager' => $isManager,
                                    'has_permission' => $hasPermission
                                ]);
                                return false; // Hide if user doesn't have view_product_subcategory permission
                            }
                        }
                    }
                }
                
                // If this is Service Categories or Subcategories, check if modules exist and user has permission
                if ($isCategoriesItem || $isSubcategoriesItem) {
                    $modules = config('constant.MODULES', []);
                    $categoryModuleExists = false;
                    $subcategoryModuleExists = false;
                    
                    foreach ($modules as $module) {
                        if (isset($module['module_name'])) {
                            $moduleName = strtolower(trim($module['module_name']));
                            // Check for "Service Category" or "Category"
                            if ($moduleName === 'service category' || $moduleName === 'category') {
                                $categoryModuleExists = true;
                            }
                            // Check for "Service Subcategory" or "Subcategory"
                            if ($moduleName === 'service subcategory' || $moduleName === 'subcategory') {
                                $subcategoryModuleExists = true;
                            }
                        }
                    }
                    
                    // Hide Service Categories menu item if Service Category/Category module doesn't exist in config
                    if ($isCategoriesItem && !$categoryModuleExists) {
                        return false;
                    }
                    
                    // Hide Service Subcategories menu item if Service Subcategory/Subcategory module doesn't exist in config
                    if ($isSubcategoriesItem && !$subcategoryModuleExists) {
                        return false;
                    }
                    
                    // Check permissions for Service Categories
                    if ($isCategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $this->safeCanCheck($user, 'view_service_category');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        $hasPermission = $managerRole->hasPermissionTo('view_service_category');
                                    } catch (\Exception $e) {
                                        $hasPermission = false;
                                    }
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Service Categories menu - user does NOT have view_service_category permission', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'is_manager' => $isManager,
                                    'has_permission' => $hasPermission
                                ]);
                                return false; // Hide if user doesn't have view_service_category permission
                            }
                        }
                    }
                    
                    // Check permissions for Service Subcategories
                    if ($isSubcategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $this->safeCanCheck($user, 'view_service_subcategory');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        $hasPermission = $managerRole->hasPermissionTo('view_service_subcategory');
                                    } catch (\Exception $e) {
                                        $hasPermission = false;
                                    }
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Service Subcategories menu - user does NOT have view_service_subcategory permission', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'is_manager' => $isManager,
                                    'has_permission' => $hasPermission
                                ]);
                                return false; // Hide if user doesn't have view_service_subcategory permission
                            }
                        }
                    }
                }
                
                // Check if this is a static menu item (section title like "COMPANY", "SHOP")
                $isStaticItem = $item->link->attr['class'] ?? '';
                $isStaticItem = strpos($isStaticItem, 'static-item') !== false || 
                    strpos($itemTitle, '<span class="default-icon">') !== false;
                
                $isLocationItem = stripos($title, 'location') !== false || 
                    $nickname == 'sidebarlocation' || 
                    stripos($itemTitle, 'location') !== false;
                
                // Check if this is the Reviews menu item - check multiple sources
                $isReviewItem = $route === 'backend.employees.review' || 
                    $itemRoute === 'backend.employees.review' ||
                    stripos($itemUrl, 'employees-review') !== false ||
                    stripos($title, 'review') !== false || 
                    stripos($itemTitle, 'review') !== false ||
                    stripos($itemTitle, 'sidebar.reviews') !== false ||
                    stripos(strtolower($itemTitle), 'reviews') !== false;
                
                // Log all menu items to debug Reviews menu detection
                if ($isReviewItem && auth()->check()) {
                    \Log::info('Reviews menu item detected', [
                        'user_id' => auth()->user()->id,
                        'user_email' => auth()->user()->email,
                        'route' => $route,
                        'itemRoute' => $itemRoute,
                        'itemUrl' => $itemUrl,
                        'itemTitle' => $itemTitle,
                        'title' => $title,
                        'nickname' => $nickname,
                        'permissions' => $permissions,
                        'has_view_review' => auth()->user()->can('view_review'),
                        'user_roles' => auth()->user()->roles->pluck('name')->toArray()
                    ]);
                }
                
                // Force Reviews menu to require view_review permission - check this FIRST before general permission check
                if ($isReviewItem && auth()->check()) {
                    $user = auth()->user();
                    $hasViewReview = $user->can('view_review');
                    $isManager = $user->hasRole('manager');
                    
                    // Check which roles have the permission
                    $rolesWithPermission = [];
                    $managerRoleHasPermission = false;
                    foreach ($user->roles as $role) {
                        if ($role->hasPermissionTo('view_review')) {
                            $rolesWithPermission[] = $role->name;
                            if ($role->name === 'manager') {
                                $managerRoleHasPermission = true;
                            }
                        }
                    }
                    
                    // If user is a manager, require that the manager role specifically has the permission
                    // This ensures that turning off the permission for manager role will hide the menu
                    // even if the user also has other roles (like employee) with the permission
                    if ($isManager && !$managerRoleHasPermission) {
                        \Log::info('Hiding Reviews menu - Manager role does NOT have view_review permission', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'user_roles' => $user->roles->pluck('name')->toArray(),
                            'is_manager' => $isManager,
                            'manager_role_has_permission' => $managerRoleHasPermission,
                            'has_view_review' => $hasViewReview,
                            'roles_with_permission' => $rolesWithPermission,
                            'route' => $route,
                            'itemRoute' => $itemRoute,
                            'itemUrl' => $itemUrl,
                            'itemTitle' => $itemTitle,
                            'title' => $title,
                            'permissions' => $permissions
                        ]);
                        return false; // Hide Reviews menu if manager role doesn't have permission
                    }
                    
                    // For non-managers, check general permission
                    if (!$isManager && !$hasViewReview) {
                        \Log::info('Hiding Reviews menu - user does NOT have view_review permission', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'user_roles' => $user->roles->pluck('name')->toArray(),
                            'is_admin' => $user->hasRole('admin'),
                            'has_view_review' => $hasViewReview,
                            'route' => $route,
                            'itemRoute' => $itemRoute,
                            'itemUrl' => $itemUrl,
                            'itemTitle' => $itemTitle,
                            'title' => $title,
                            'permissions' => $permissions
                        ]);
                        return false; // Hide Reviews menu if user doesn't have permission
                    }
                    
                    // Log when showing the menu
                    \Log::info('Showing Reviews menu - user HAS view_review permission', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'has_view_review' => $hasViewReview,
                        'is_manager' => $isManager,
                        'manager_role_has_permission' => $managerRoleHasPermission,
                        'roles_with_permission' => $rolesWithPermission,
                        'all_user_roles' => $user->roles->pluck('name')->toArray()
                    ]);
                }
                
                if ($isLocationItem && auth()->check()) {
                    \Log::info('Filtering Location menu item', [
                        'data_title' => $title,
                        'item_title' => $itemTitle,
                        'nickname' => $nickname,
                        'permissions' => $permissions,
                        'permissions_type' => gettype($permissions),
                        'permissions_count' => is_array($permissions) ? count($permissions) : 0,
                        'user_role' => auth()->user()->roles->pluck('name')->toArray(),
                        'has_view_location' => auth()->user()->can('view_location'),
                        'hasPermissionTo_view_location' => auth()->user()->hasPermissionTo('view_location'),
                        'has_children' => $item->hasChildren()
                    ]);
                }
                
                // Hide Location menu for employees/staff only (but NOT if they're also a manager)
                // Priority: Manager > Employee, so managers can see Location even if they also have employee role
                if (auth()->check() && 
                    (auth()->user()->hasRole('employee') || auth()->user()->hasRole('staff')) &&
                    !auth()->user()->hasRole('manager')) { // Only hide if NOT a manager
                    $route = $item->data('route') ?? '';
                    // Check if this is Location menu item or any Location-related routes
                    if ($isLocationItem ||
                        stripos($route, 'city') !== false ||
                        stripos($route, 'state') !== false ||
                        stripos($route, 'country') !== false ||
                        stripos($route, 'locations') !== false) {
                        if ($isLocationItem) {
                            \Log::info('Hiding Location menu for employee/staff (not manager)');
                        }
                        return false; // Hide Location for employees/staff (but not managers)
                    }
                }
                
                // Check if this is a static menu item (section title like "COMPANY", "SHOP")
                $isStaticItem = false;
                $linkClass = $item->link->attr['class'] ?? '';
                if (strpos($linkClass, 'static-item') !== false || 
                    strpos($itemTitle, '<span class="default-icon">') !== false ||
                    strpos($itemTitle, 'static-item') !== false) {
                    $isStaticItem = true;
                }
                
                // Check if this is a dashboard menu item
                $isDashboardItem = (stripos($title ?? '', 'dashboard') !== false) || 
                    (stripos($itemTitle ?? '', 'dashboard') !== false) ||
                    $route === 'backend.home';

                // Check if this is a booking or service menu item
                $isBookingItem = stripos($title ?? '', 'booking') !== false || 
                    stripos($itemTitle ?? '', 'booking') !== false ||
                    stripos($route ?? '', 'booking') !== false ||
                    stripos($route ?? '', 'datatable_view') !== false; // Also check for datatable_view route
                $isServiceItem = stripos($title ?? '', 'service') !== false ||
                    stripos($itemTitle ?? '', 'service') !== false ||
                    stripos($route ?? '', 'service') !== false ||
                    $nickname === 'service';

                // Check if this is an earning menu item
                $isEarningItem = $route === 'backend.earnings.index' ||
                    $itemRoute === 'backend.earnings.index' ||
                    stripos($route ?? '', 'earnings') !== false ||
                    stripos($itemRoute ?? '', 'earnings') !== false ||
                    stripos($title ?? '', 'earning') !== false ||
                    stripos($itemTitle ?? '', 'earning') !== false ||
                    stripos($title ?? '', 'staff_earnings') !== false ||
                    stripos($itemTitle ?? '', 'staff_earnings') !== false;
                
                // Check if this is Staff Service Report or Staff Payout Report menu item
                $isStaffReportItem = $route === 'backend.reports.staff-report' || 
                                     $itemRoute === 'backend.reports.staff-report' ||
                                     stripos($itemUrl ?? '', 'staff-report') !== false ||
                                     stripos($title ?? '', 'staffs_services') !== false || 
                                     stripos($itemTitle ?? '', 'staffs_services') !== false;
                $isPayoutReportItem = $route === 'backend.reports.payout-report' || 
                                      $itemRoute === 'backend.reports.payout-report' ||
                                      stripos($itemUrl ?? '', 'payout-report') !== false ||
                                      stripos($title ?? '', 'staffs_payouts') !== false || 
                                      stripos($itemTitle ?? '', 'staffs_payouts') !== false;
                
                // Check if this is the Order Report menu item
                $isOrderReportItem = $route === 'backend.reports.order-report' || 
                    $itemRoute === 'backend.reports.order-report' ||
                    stripos($itemUrl ?? '', 'order-report') !== false ||
                    stripos($title ?? '', 'orders_report') !== false || 
                    stripos($itemTitle ?? '', 'orders_report') !== false ||
                    stripos($itemTitle ?? '', 'order') !== false && stripos($itemTitle ?? '', 'report') !== false;
                
                // Log when Order Report menu item is detected
                if ($isOrderReportItem && auth()->check()) {
                    \Log::info('🔍 Order Report menu item detected', [
                        'user_id' => auth()->user()->id,
                        'user_email' => auth()->user()->email,
                        'user_roles' => auth()->user()->roles->pluck('name')->toArray(),
                        'route' => $route,
                        'itemRoute' => $itemRoute,
                        'itemUrl' => $itemUrl,
                        'itemTitle' => $itemTitle,
                        'title' => $title,
                        'nickname' => $nickname,
                        'permissions' => $permissions,
                        'permissions_type' => gettype($permissions),
                        'permissions_count' => is_array($permissions) ? count($permissions) : 0,
                        'has_view_product_orders_report' => auth()->user()->can('view_product_orders_report'),
                        'hasPermissionTo_view_product_orders_report' => auth()->user()->hasPermissionTo('view_product_orders_report'),
                        'user_all_permissions' => auth()->user()->getAllPermissions()->pluck('name')->toArray()
                    ]);
                }
                
                // Check if this is the COMPANY static menu item
                // Check both translation key (sidebar.company) and translated value (Company)
                $isCompanyItem = stripos($title ?? '', 'company') !== false || 
                    stripos($itemTitle ?? '', 'company') !== false ||
                    stripos($nickname ?? '', 'company') !== false ||
                    (is_array($permissions) && count($permissions) === 3 && 
                     in_array('view_booking', $permissions) && 
                     in_array('view_branch', $permissions) && 
                     in_array('view_service', $permissions));
                
                // Check if this is the USERS static menu item
                // Check both translation key (sidebar.users) and translated value (Users)
                $isUsersItem = stripos($title ?? '', 'users') !== false || 
                    stripos($itemTitle ?? '', 'users') !== false ||
                    stripos($nickname ?? '', 'users') !== false ||
                    (is_array($permissions) && count($permissions) >= 2 && 
                     (in_array('view_customer', $permissions) || 
                      in_array('view_staff', $permissions) || 
                      in_array('view_review', $permissions)));

                // For static menu items, check permissions and visible children
                if ($isStaticItem) {
                    if (!auth()->check()) {
                        return false; // Hide static items for non-authenticated users
                    }
                    
                    $user = auth()->user();
                    $isManager = $user->hasRole('manager');
                    $staticHasPermission = false;
                    $hasVisibleChildren = false;
                    
                    // First check if static item itself has permission requirements
                    if ($permissions && is_array($permissions) && count($permissions) > 0) {
                        if ($user->hasRole('admin')) {
                            $staticHasPermission = true;
                        } else {
                            foreach ($permissions as $permission) {
                                // For managers checking booking/service/branch/order report/reports/users related permissions, check via manager role
                                if ($isManager && (
                                    stripos($permission, 'booking') !== false || 
                                    stripos($permission, 'service') !== false || 
                                    stripos($permission, 'branch') !== false || 
                                    stripos($permission, 'product_orders_report') !== false ||
                                    stripos($permission, 'reports_') !== false ||
                                    stripos($permission, 'view_reports') !== false ||
                                    stripos($permission, 'view_user') !== false ||
                                    stripos($permission, 'view_employee') !== false ||
                                    stripos($permission, 'view_customer') !== false ||
                                    stripos($permission, 'view_staff') !== false ||
                                    stripos($permission, 'view_review') !== false
                                )) {
                                    $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                    if ($managerRole) {
                                        try {
                                            if ($managerRole->hasPermissionTo($permission)) {
                                                $staticHasPermission = true;
                                                break;
                                            }
                                        } catch (\Exception $e) {
                                            // Permission doesn't exist, continue checking
                                        }
                                    }
                                } else {
                                    if ($user->can($permission)) {
                                        $staticHasPermission = true;
                                        break;
                                    }
                                }
                            }
                        }
                    } else {
                        // No permission requirement for static item itself
                        $staticHasPermission = true;
                    }
                    
                    // Then check if it has any visible children
                    if ($item->hasChildren()) {
                        foreach ($item->children() as $child) {
                            $childPermissions = $child->data('permission');
                            
                            // Child with no permission requirement is always visible
                            if (empty($childPermissions) || !is_array($childPermissions) || count($childPermissions) === 0) {
                                $hasVisibleChildren = true;
                                break;
                            }
                            
                            // Check child permissions
                            if ($user->hasRole('admin')) {
                                $hasVisibleChildren = true;
                                break;
                            }
                            
                            foreach ($childPermissions as $childPermission) {
                                $childHasPermission = false;
                                
                                // For managers checking booking/service/branch/order report/reports/users permissions, check via manager role
                                if ($isManager && (
                                    stripos($childPermission, 'booking') !== false || 
                                    stripos($childPermission, 'service') !== false || 
                                    stripos($childPermission, 'branch') !== false || 
                                    stripos($childPermission, 'product_orders_report') !== false ||
                                    stripos($childPermission, 'reports_') !== false ||
                                    stripos($childPermission, 'view_reports') !== false ||
                                    stripos($childPermission, 'view_user') !== false ||
                                    stripos($childPermission, 'view_employee') !== false ||
                                    stripos($childPermission, 'view_customer') !== false ||
                                    stripos($childPermission, 'view_staff') !== false ||
                                    stripos($childPermission, 'view_review') !== false
                                )) {
                                    $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                    if ($managerRole) {
                                        try {
                                            $childHasPermission = $managerRole->hasPermissionTo($childPermission);
                                        } catch (\Exception $e) {
                                            // Permission doesn't exist, user doesn't have it
                                            $childHasPermission = false;
                                        }
                                    }
                                } else {
                                    $childHasPermission = $user->can($childPermission);
                                }
                                
                                if ($childHasPermission) {
                                    $hasVisibleChildren = true;
                                    break 2; // Break both loops
                                }
                            }
                        }
                    } else {
                        // Static item with no children - only check its own permissions
                        $hasVisibleChildren = true;
                    }
                    
                    // Special handling for COMPANY static item: show if user has ANY of view_booking, view_branch, or view_service
                    if ($isCompanyItem) {
                        // For COMPANY, check if user has ANY of the three permissions
                        $hasCompanyPermission = false;
                        $companyPermissions = ['view_booking', 'view_branch', 'view_service'];
                        
                        foreach ($companyPermissions as $companyPermission) {
                            if ($user->hasRole('admin')) {
                                $hasCompanyPermission = true;
                                break;
                            }
                            
                            // For managers, check if manager role has the permission
                            if ($isManager && (stripos($companyPermission, 'booking') !== false || stripos($companyPermission, 'service') !== false || stripos($companyPermission, 'branch') !== false)) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo($companyPermission)) {
                                    $hasCompanyPermission = true;
                                    break;
                                }
                            } else {
                                if ($user->can($companyPermission)) {
                                    $hasCompanyPermission = true;
                                    break;
                                }
                            }
                        }
                        
                        // For Company section: only show if user has permissions AND there are visible children
                        if ($hasCompanyPermission) {
                            // Check if Company has visible children - if not, hide it
                            if ($item->hasChildren() && !$hasVisibleChildren) {
                                \Log::info('❌ Hiding COMPANY static menu - user has permissions but no visible children', [
                                    'title' => strip_tags($itemTitle),
                                    'title_data' => $title,
                                    'nickname' => $nickname,
                                    'has_company_permission' => $hasCompanyPermission,
                                    'has_visible_children' => $hasVisibleChildren,
                                    'children_count' => count($item->children()),
                                    'required_permissions' => $permissions ?? [],
                                    'user_roles' => $user->roles->pluck('name')->toArray()
                                ]);
                                return false; // Hide Company if no visible children
                            }
                            
                            \Log::info('✅ Showing COMPANY static menu - user has permissions and visible children', [
                                'title' => strip_tags($itemTitle),
                                'title_data' => $title,
                                'nickname' => $nickname,
                                'has_company_permission' => $hasCompanyPermission,
                                'static_has_permission' => $staticHasPermission,
                                'has_visible_children' => $hasVisibleChildren,
                                'children_count' => $item->hasChildren() ? count($item->children()) : 0,
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray(),
                                'user_has_view_booking' => $user->can('view_booking'),
                                'user_has_view_branch' => $user->can('view_branch'),
                                'user_has_view_service' => $user->can('view_service')
                            ]);
                            // Continue to general static item check below
                        } else {
                            \Log::info('❌ Hiding COMPANY static menu - user does NOT have any required permission', [
                                'title' => strip_tags($itemTitle),
                                'title_data' => $title,
                                'nickname' => $nickname,
                                'has_company_permission' => $hasCompanyPermission,
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray(),
                                'user_has_view_booking' => $user->can('view_booking'),
                                'user_has_view_branch' => $user->can('view_branch'),
                                'user_has_view_service' => $user->can('view_service')
                            ]);
                            return false; // Hide Company if no permissions
                        }
                    }
                    
                    // Special handling for USERS static item: show if user has ANY of view_customer, view_staff, or view_review
                    // BUT only if there are visible children
                    if ($isUsersItem) {
                        // For USERS, check if user has ANY of the three permissions
                        $hasUsersPermission = false;
                        $usersPermissions = ['view_customer', 'view_staff', 'view_review'];
                        
                        foreach ($usersPermissions as $usersPermission) {
                            if ($user->hasRole('admin')) {
                                $hasUsersPermission = true;
                                break;
                            }
                            
                            // For managers, check if manager role has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        if ($managerRole->hasPermissionTo($usersPermission)) {
                                            $hasUsersPermission = true;
                                            break;
                                        }
                                    } catch (\Exception $e) {
                                        // Permission doesn't exist, continue checking
                                    }
                                }
                            } else {
                                if ($user->can($usersPermission)) {
                                    $hasUsersPermission = true;
                                    break;
                                }
                            }
                        }
                        
                        // For Users section: only show if user has permissions AND there are visible children
                        if ($hasUsersPermission) {
                            // Check if Users has visible children - if not, hide it
                            if ($item->hasChildren() && !$hasVisibleChildren) {
                                \Log::info('❌ Hiding USERS static menu - user has permissions but no visible children', [
                                    'title' => strip_tags($itemTitle),
                                    'title_data' => $title,
                                    'nickname' => $nickname,
                                    'has_users_permission' => $hasUsersPermission,
                                    'has_visible_children' => $hasVisibleChildren,
                                    'children_count' => count($item->children()),
                                    'required_permissions' => $permissions ?? [],
                                    'user_roles' => $user->roles->pluck('name')->toArray()
                                ]);
                                return false; // Hide Users if no visible children
                            }
                            
                            \Log::info('✅ Showing USERS static menu - user has permissions and visible children', [
                                'title' => strip_tags($itemTitle),
                                'title_data' => $title,
                                'nickname' => $nickname,
                                'has_users_permission' => $hasUsersPermission,
                                'static_has_permission' => $staticHasPermission,
                                'has_visible_children' => $hasVisibleChildren,
                                'children_count' => $item->hasChildren() ? count($item->children()) : 0,
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray()
                            ]);
                            // Continue to general static item check below
                        } else {
                            \Log::info('❌ Hiding USERS static menu - user does NOT have any required permission', [
                                'title' => strip_tags($itemTitle),
                                'title_data' => $title,
                                'nickname' => $nickname,
                                'has_users_permission' => $hasUsersPermission,
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray()
                            ]);
                            return false; // Hide Users if no permissions
                        }
                    }
                    
                    // For ALL static items with children: show only if at least one child is visible
                    // For static items without children: show only if user has permissions for the static item
                    if ($item->hasChildren()) {
                        // Static item with children - hide if no children are visible
                        if (!$hasVisibleChildren) {
                            \Log::info('❌ Hiding static menu - no visible children', [
                                'title' => strip_tags($itemTitle),
                                'has_visible_children' => $hasVisibleChildren,
                                'children_count' => count($item->children()),
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray()
                            ]);
                            return false; // Hide static menu if no visible children
                        }
                    } else {
                        // Static item without children - hide if user doesn't have permissions
                        if (!$staticHasPermission) {
                            \Log::info('❌ Hiding static menu - no permissions (no children)', [
                                'title' => strip_tags($itemTitle),
                                'static_has_permission' => $staticHasPermission,
                                'required_permissions' => $permissions ?? [],
                                'user_roles' => $user->roles->pluck('name')->toArray()
                            ]);
                            return false; // Hide static menu if no permissions
                        }
                    }
                    
                    \Log::info('✅ Showing static menu', [
                        'title' => strip_tags($itemTitle),
                        'static_has_permission' => $staticHasPermission,
                        'has_visible_children' => $hasVisibleChildren,
                        'has_children' => $item->hasChildren()
                    ]);
                }

                // If permissions are set, check them
                if ($permissions && is_array($permissions) && count($permissions) > 0) {
                    if (auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            if ($isLocationItem || $isBookingItem || $isServiceItem || $isDashboardItem) {
                                \Log::info('Showing menu - user is admin', [
                                    'menu_type' => $isDashboardItem ? 'dashboard' : ($isBookingItem ? 'booking' : ($isServiceItem ? 'service' : ($isLocationItem ? 'location' : 'other'))),
                                    'title' => $title ?? $itemTitle
                                ]);
                            }
                            return true;
                        }
                        
                        // Log permission check for booking, service, and order report menus
                        if ($isBookingItem || $isServiceItem || $isOrderReportItem || $isDashboardItem) {
                            \Log::info('🔍 Checking permissions for menu item', [
                                'menu_type' => $isDashboardItem ? 'dashboard' : ($isBookingItem ? 'booking' : ($isServiceItem ? 'service' : ($isOrderReportItem ? 'order_report' : 'other'))),
                                'title' => $title ?? $itemTitle,
                                'route' => $route ?? 'N/A',
                                'required_permissions' => $permissions,
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'user_roles' => $user->roles->pluck('name')->toArray(),
                                'user_all_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                            ]);
                        }
                        
                        // Check if user has any of the required permissions
                        // For managers, check if they have permission via manager role specifically
                        // (not via employee role, to respect manager role permission settings)
                        $hasPermission = false;
                        $matchedPermission = null;
                        $isManager = $user->hasRole('manager');
                        
                        foreach ($permissions as $permission) {
                            $canCheck = false;
                            
                            // 1. Strict Permission Check for ALL strict roles and ALL sensitive items
                            $isSensitiveItem = $isDashboardItem || $isBookingItem || $isServiceItem || $isOrderReportItem || $isStaffReportItem || $isPayoutReportItem || $isLocationItem || $isReviewItem || $isEarningItem;
                            
                            if ($isSensitiveItem && !$user->hasRole('admin')) {
                                $canCheck = true;
                                
                                // First check if user generally has the permission
                                if (!$user->can($permission)) {
                                    $canCheck = false;
                                } else {
                                    // Veto Logic: If ANY assigned strict role lacks the permission, deny it.
                                    // BUT: For managers with order report, check manager role specifically
                                    $strictRoles = ['manager', 'employee', 'expert', 'user'];
                                    $isManager = $user->hasRole('manager');
                                    
                                    // Special handling for Order Report permission for managers
                                    if ($isOrderReportItem && $isManager && $permission === 'view_product_orders_report') {
                                        $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                        if ($managerRole) {
                                            try {
                                                if ($managerRole->hasPermissionTo('view_product_orders_report')) {
                                                    $canCheck = true;
                                                    \Log::info("  ✓ Order Report access granted - Manager role has view_product_orders_report", [
                                                        'permission' => $permission,
                                                        'menu_item' => $title ?? $itemTitle
                                                    ]);
                                                } else {
                                                    $canCheck = false;
                                                    \Log::info("  ✗ Order Report access denied - Manager role does NOT have view_product_orders_report", [
                                                        'permission' => $permission,
                                                        'menu_item' => $title ?? $itemTitle
                                                    ]);
                                                }
                                            } catch (\Exception $e) {
                                                $canCheck = false;
                                            }
                                        } else {
                                            $canCheck = false;
                                        }
                                    } else {
                                        // For other permissions, use standard strict check
                                        foreach ($strictRoles as $roleName) {
                                            if ($user->hasRole($roleName)) {
                                                $roleModel = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                                                if ($roleModel) {
                                                    try {
                                                        if (!$roleModel->hasPermissionTo($permission)) {
                                                            $canCheck = false;
                                                            \Log::info("  ✗ Menu access denied by strict role check", [
                                                                'role' => $roleName,
                                                                'permission' => $permission,
                                                                'menu_item' => $title ?? $itemTitle
                                                            ]);
                                                            break;
                                                        }
                                                    } catch (\Exception $e) {
                                                        // Permission doesn't exist, deny access
                                                        $canCheck = false;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if ($canCheck) {
                                    \Log::info("  ✓ Menu access granted (Strict Check passed)", [
                                        'permission' => $permission,
                                        'menu_item' => $title ?? $itemTitle
                                    ]);
                                }
                            } 
                            // 2. Normal check for non-sensitive items (or fallback)
                            else {
                                // For non-sensitive items, use normal permission check
                                $canCheck = $user->can($permission);
                                if ($isSensitiveItem) { // Should not happen if matches block 1, but safe fallback
                                    \Log::info('  ✓ Checking permission (normal check)', [
                                        'permission' => $permission,
                                        'can() result' => $canCheck,
                                        'menu_item' => $title ?? $itemTitle
                                    ]);
                                }
                            }
                            
                            if ($canCheck) {
                                $hasPermission = true;
                                $matchedPermission = $permission;
                                break;
                            }
                        }
                        
                        // Special handling for Order Report: check BEFORE other checks to ensure it works correctly
                        // This ensures that when order report permission is enabled for manager, the menu is shown
                        if ($isOrderReportItem) {
                            $isManager = $user->hasRole('manager');
                            
                            if ($isManager) {
                                // For managers, check if manager role specifically has view_product_orders_report permission
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        $managerHasPermission = $managerRole->hasPermissionTo('view_product_orders_report');
                                        
                                        if ($managerHasPermission) {
                                            \Log::info('✅ Showing Order Report menu - Manager role has view_product_orders_report permission', [
                                                'route' => $route ?? 'N/A',
                                                'itemRoute' => $itemRoute ?? 'N/A',
                                                'itemUrl' => $itemUrl ?? 'N/A',
                                                'itemTitle' => $itemTitle ?? 'N/A',
                                                'title' => $title ?? 'N/A',
                                                'required_permissions' => $permissions,
                                                'user_roles' => $user->roles->pluck('name')->toArray(),
                                                'manager_has_permission' => $managerHasPermission,
                                                'user_can_view_product_orders_report' => $user->can('view_product_orders_report')
                                            ]);
                                            return true; // Show Order Report if manager role has permission
                                        } else {
                                            \Log::info('❌ Hiding Order Report menu - Manager role does NOT have view_product_orders_report permission', [
                                                'route' => $route ?? 'N/A',
                                                'itemRoute' => $itemRoute ?? 'N/A',
                                                'itemUrl' => $itemUrl ?? 'N/A',
                                                'itemTitle' => $itemTitle ?? 'N/A',
                                                'title' => $title ?? 'N/A',
                                                'required_permissions' => $permissions,
                                                'user_roles' => $user->roles->pluck('name')->toArray(),
                                                'manager_has_permission' => $managerHasPermission
                                            ]);
                                            return false; // Hide Order Report if manager role doesn't have permission
                                        }
                                    } catch (\Exception $e) {
                                        \Log::info('❌ Hiding Order Report menu - Error checking manager permission', [
                                            'error' => $e->getMessage(),
                                            'route' => $route ?? 'N/A'
                                        ]);
                                        return false;
                                    }
                                } else {
                                    \Log::info('❌ Hiding Order Report menu - Manager role not found');
                                    return false;
                                }
                            } else {
                                // For non-managers (staff/employee), check if any of their roles have the permission
                                $userRoles = $user->roles;
                                $hasPermission = false;
                                foreach ($userRoles as $userRole) {
                                    try {
                                        if ($userRole->hasPermissionTo('view_product_orders_report')) {
                                            $hasPermission = true;
                                            break;
                                        }
                                    } catch (\Exception $e) {
                                        // Permission doesn't exist, continue checking
                                    }
                                }
                                
                                if ($hasPermission) {
                                    \Log::info('✅ Showing Order Report menu - User role has view_product_orders_report permission', [
                                        'route' => $route ?? 'N/A',
                                        'user_roles' => $user->roles->pluck('name')->toArray()
                                    ]);
                                    return true;
                                } else {
                                    \Log::info('❌ Hiding Order Report menu - No user role has view_product_orders_report permission', [
                                        'route' => $route ?? 'N/A',
                                        'user_roles' => $user->roles->pluck('name')->toArray()
                                    ]);
                                    return false;
                                }
                            }
                        }
                        
                        // Special handling for Service List: must have view_service permission
                        // For managers, check if manager role specifically has the permission
                        if ($isServiceListItem && !$hasPermission) {
                            $isManager = $user->hasRole('manager');
                            $managerHasViewService = false;
                            
                            if ($isManager) {
                                // For managers, check if manager role specifically has view_service permission
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo('view_service')) {
                                    $managerHasViewService = true;
                                }
                            }
                            
                            // If manager doesn't have view_service via manager role, hide Service List
                            if ($isManager && !$managerHasViewService) {
                                \Log::info('❌ Hiding Service List - Manager role does NOT have view_service permission', [
                                    'route' => $route ?? 'N/A',
                                    'itemRoute' => $itemRoute ?? 'N/A',
                                    'itemUrl' => $itemUrl ?? 'N/A',
                                    'itemTitle' => $itemTitle ?? 'N/A',
                                    'title' => $title ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'has_view_service' => $user->can('view_service'),
                                    'manager_role_has_view_service' => $managerHasViewService
                                ]);
                                return false; // Hide Service List if manager role doesn't have view_service permission
                            }
                            
                            // For non-managers, hide if they don't have view_service
                            if (!$isManager) {
                                \Log::info('❌ Hiding Service List - user does NOT have view_service permission (in permission check)', [
                                    'route' => $route ?? 'N/A',
                                    'itemRoute' => $itemRoute ?? 'N/A',
                                    'itemUrl' => $itemUrl ?? 'N/A',
                                    'itemTitle' => $itemTitle ?? 'N/A',
                                    'title' => $title ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'has_view_service' => $user->can('view_service')
                                ]);
                                return false; // Hide Service List if user doesn't have view_service permission
                            }
                        }
                        
                        // Special handling for Service parent menu: show if user has view_service_category or view_service_subcategory
                        // even if they don't have view_service permission
                        if ($isServiceItem && $item->hasChildren() && !$hasPermission) {
                            $hasCategoryPermission = $this->safeCanCheck($user, 'view_service_category');
                            $hasSubcategoryPermission = $this->safeCanCheck($user, 'view_service_subcategory');
                            
                            if ($hasCategoryPermission || $hasSubcategoryPermission) {
                                \Log::info('✅ Showing Service menu - user has category/subcategory permissions', [
                                    'has_view_service_category' => $hasCategoryPermission,
                                    'has_view_service_subcategory' => $hasSubcategoryPermission
                                ]);
                                return true; // Show Service parent menu if user has category/subcategory permissions
                            }
                        }
                        
                        if ($hasPermission) {
                            if ($isLocationItem || $isBookingItem || $isServiceItem || $isOrderReportItem || $isStaffReportItem || $isPayoutReportItem || $isDashboardItem) {
                                \Log::info('✅ Showing menu - user has permission', [
                                    'menu_type' => $isDashboardItem ? 'dashboard' : ($isBookingItem ? 'booking' : ($isServiceItem ? 'service' : ($isOrderReportItem ? 'order_report' : ($isStaffReportItem ? 'staff_report' : ($isPayoutReportItem ? 'payout_report' : ($isLocationItem ? 'location' : 'other')))))),
                                    'title' => $title ?? $itemTitle,
                                    'matched_permission' => $matchedPermission
                                ]);
                            }
                            return true;
                        } else {
                            if ($isLocationItem) {
                                \Log::warning('Hiding Location menu - user does NOT have permission', [
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                                ]);
                            }
                            if ($isReviewItem) {
                                \Log::warning('Hiding Reviews menu - user does NOT have required permission', [
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'has_view_review' => $user->can('view_review')
                                ]);
                            }
                            if ($isOrderReportItem) {
                                \Log::warning('❌ Hiding Order Report menu - user does NOT have required permission', [
                                    'title' => $title ?? $itemTitle,
                                    'route' => $route ?? 'N/A',
                                    'itemRoute' => $itemRoute ?? 'N/A',
                                    'itemUrl' => $itemUrl ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'user_roles' => $user->roles->pluck('name')->toArray(),
                                    'has_view_product_orders_report' => $user->can('view_product_orders_report'),
                                    'hasPermissionTo_view_product_orders_report' => $user->hasPermissionTo('view_product_orders_report')
                                ]);
                            }
                            if ($isStaffReportItem) {
                                \Log::warning('❌ Hiding Staff Service Report menu - user does NOT have required permission', [
                                    'title' => $title ?? $itemTitle,
                                    'route' => $route ?? 'N/A',
                                    'itemRoute' => $itemRoute ?? 'N/A',
                                    'itemUrl' => $itemUrl ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'user_roles' => $user->roles->pluck('name')->toArray(),
                                    'has_reports_staff_report' => $user->can('reports_staff_report'),
                                    'hasPermissionTo_reports_staff_report' => $user->hasPermissionTo('reports_staff_report')
                                ]);
                            }
                            if ($isPayoutReportItem) {
                                \Log::warning('❌ Hiding Staff Payout Report menu - user does NOT have required permission', [
                                    'title' => $title ?? $itemTitle,
                                    'route' => $route ?? 'N/A',
                                    'itemRoute' => $itemRoute ?? 'N/A',
                                    'itemUrl' => $itemUrl ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'user_roles' => $user->roles->pluck('name')->toArray(),
                                    'has_reports_payout_report' => $user->can('reports_payout_report'),
                                    'hasPermissionTo_reports_payout_report' => $user->hasPermissionTo('reports_payout_report')
                                ]);
                            }
                            if ($isBookingItem || $isServiceItem || $isDashboardItem) {
                                \Log::warning('❌ Hiding menu - user does NOT have required permission', [
                                    'menu_type' => $isDashboardItem ? 'dashboard' : ($isBookingItem ? 'booking' : 'service'),
                                    'title' => $title ?? $itemTitle,
                                    'route' => $route ?? 'N/A',
                                    'required_permissions' => $permissions,
                                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                                    'user_roles' => $user->roles->pluck('name')->toArray()
                                ]);
                            }
                        }
                    }
                    // User doesn't have required permissions
                    return false;
                }
                // If no permissions are set, show the item (backward compatibility)
                // This allows old menu items without permissions to still work
                if ($isLocationItem) {
                    \Log::info('Showing Location menu - no permissions set (backward compatibility)');
                }
                return true;
            });
            
            // Second filter: Ensure parent menus with permissions show even if children are filtered
            // This is needed because some menu libraries hide parents when all children are filtered
            $menu->filter(function ($item) {
                // DEBUG: Log parent menu filtering
                if ($item->hasChildren() && $item->data("permission")) {
                    $permissions = $item->data("permission");
                    $title = $item->data("title") ?? "";
                    $permissions = $item->data('permission');
                    if (is_array($permissions) && count($permissions) > 0 && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            return true;
                        }

                        // Use strict permission checking for parent menus, especially those requiring multiple permissions
                        $hasAllPermissions = true;
                        $isSensitiveParent = count($permissions) > 1; // Parent menus requiring multiple permissions are sensitive

                        if ($isSensitiveParent && !$user->hasRole('admin')) {
                            // For sensitive parent menus, use strict role checking
                            foreach ($permissions as $permission) {
                                $canCheck = false;

                                // First check if user generally has the permission
                                if (!$user->can($permission)) {
                                    $canCheck = false;
                                } else {
                                    // Veto Logic: If ANY assigned strict role lacks the permission, deny it.
                                    $strictRoles = ['manager', 'employee', 'expert', 'user'];
                                    $strictRoleCheckPassed = true;
                                    foreach ($strictRoles as $roleName) {
                                        if ($user->hasRole($roleName)) {
                                            $roleModel = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                                            if ($roleModel && !$roleModel->hasPermissionTo($permission)) {
                                                $strictRoleCheckPassed = false;
                                                break;
                                            }
                                        }
                                    }
                                    $canCheck = $strictRoleCheckPassed;
                                }

                                if (!$canCheck) {
                                    $hasAllPermissions = false;
                                    break;
                                }
                            }
                        } else {
                            // For non-sensitive parent menus, check if user has all required permissions
                            foreach ($permissions as $permission) {
                                if (!$user->can($permission)) {
                                    $hasAllPermissions = false;
                                    break;
                                }
                            }
                        }

                        if ($hasAllPermissions) {
                            return true; // Keep parent if user has ALL required permissions
                        } else {
                            return false; // Hide parent if user lacks ANY required permission
                        }
                    }
                }
                return true; // Keep all other items
            });
            // Set Active Menu
            $menu->filter(function ($item) {
                if ($item->activematches) {
                    $activematches = (is_string($item->activematches)) ? [$item->activematches] : $item->activematches;
                    foreach ($activematches as $pattern) {
                        if (request()->is($pattern)) {
                            $item->active();
                            $item->link->active();
                            if ($item->hasParent() && $item->parent()) {
                                $item->parent()->active();
                            }
                        }
                    }
                }

                return true;
            });
        })->sortBy('order');

        return \Menu::get($menuname);
    }

    protected function saveMenu($menu)
    {
        $menuChildren = $menu['children'] ?? null;
        $menu = Arr::except($menu, ['children']);
        $savedMenu = MenuBuilder::create($menu);
        if (isset($menuChildren) && count($menuChildren) > 0) {
            foreach ($menuChildren as $key => $value) {
                $value['parent_id'] = $savedMenu->id;
                $this->saveMenu($value);
            }
        }
    }

    protected function makeMenu($menu, $value)
    {
        if ($value->menu_item_type == 'static') {
            $this->staticMenu($menu, ['title' => __($value->title), 'order' => $value->order, 'permission' => $value->permission]);
        } else {
            if (count($value->children) > 0) {
                $parentMenuArr = [
                    'icon' => $value->start_icon,
                    'title' => __($value->title),
                    'active' => $value->active,
                    'nickname' => $value->nickname ?? \Str::slug($value->title),
                    'order' => $value->order,
                    'permission' => $value->permission,
                ];
                if (isset($value->parent)) {
                    $parentMenuArr['parent'] = $value->parent->nickname;
                }
                $parentMenu = $this->parentMenu($menu, $parentMenuArr);
                foreach ($value->children as $key => $childValue) {
                    // Check if this is Product Categories or Product Subcategories FIRST (before Service Categories)
                    // This must be checked first because product categories also contain "categories.index" in the route
                    $isProductCategoriesItem = stripos($childValue->route ?? '', 'products-categories.index') !== false && stripos($childValue->route ?? '', 'index_nested') === false;
                    $isProductSubcategoriesItem = stripos($childValue->route ?? '', 'products-categories.index_nested') !== false;
                    
                    // Check if this is Service Categories or Subcategories menu item (exclude product categories)
                    $isCategoriesItem = stripos($childValue->route ?? '', 'categories.index') !== false 
                        && stripos($childValue->route ?? '', 'index_nested') === false
                        && !$isProductCategoriesItem; // Exclude product categories
                    $isSubcategoriesItem = stripos($childValue->route ?? '', 'categories.index_nested') !== false
                        && !$isProductSubcategoriesItem; // Exclude product subcategories
                    
                    // Check if Service Category and Service Subcategory modules exist in config
                    $modules = config('constant.MODULES', []);
                    $categoryModuleExists = false;
                    $subcategoryModuleExists = false;
                    $productCategoryModuleExists = false;
                    $productSubcategoryModuleExists = false;
                    
                    foreach ($modules as $module) {
                        if (isset($module['module_name'])) {
                            $moduleName = strtolower(trim($module['module_name']));
                            // Check for "Service Category" or "Category"
                            if ($moduleName === 'service category' || $moduleName === 'category') {
                                $categoryModuleExists = true;
                            }
                            // Check for "Service Subcategory" or "Subcategory"
                            if ($moduleName === 'service subcategory' || $moduleName === 'subcategory') {
                                $subcategoryModuleExists = true;
                            }
                            // Check for "Product Category"
                            if ($moduleName === 'product category') {
                                $productCategoryModuleExists = true;
                            }
                            // Check for "Product Subcategory"
                            if ($moduleName === 'product subcategory') {
                                $productSubcategoryModuleExists = true;
                            }
                        }
                    }
                    
                    // Skip Categories menu item if Service Category/Category module doesn't exist in config
                    if ($isCategoriesItem && !$categoryModuleExists) {
                        continue;
                    }
                    
                    // Skip Subcategories menu item if Service Subcategory/Subcategory module doesn't exist in config
                    if ($isSubcategoriesItem && !$subcategoryModuleExists) {
                        continue;
                    }
                    
                    // Skip Product Categories menu item if Product Category module doesn't exist in config
                    if ($isProductCategoriesItem && !$productCategoryModuleExists) {
                        continue;
                    }
                    
                    // Skip Product Subcategories menu item if Product Subcategory module doesn't exist in config
                    if ($isProductSubcategoriesItem && !$productSubcategoryModuleExists) {
                        continue;
                    }
                    
                    // For Product Categories, check if user has view_product_category permission FIRST
                    // This must be checked BEFORE service categories to avoid conflicts
                    if ($isProductCategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        \Log::info('🔍 Checking Product Categories permission', [
                            'route' => $childValue->route ?? 'N/A',
                            'isProductCategoriesItem' => $isProductCategoriesItem,
                            'user_id' => $user->id,
                            'has_view_product_category' => $user->can('view_product_category')
                        ]);
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $user->can('view_product_category');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo('view_product_category')) {
                                    $hasPermission = true;
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Product Categories - no view_product_category permission');
                                continue; // Skip if user doesn't have view_product_category permission
                            }
                        }
                    }
                    
                    // For Product Subcategories, check if user has view_product_subcategory permission
                    if ($isProductSubcategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $user->can('view_product_subcategory');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole && $managerRole->hasPermissionTo('view_product_subcategory')) {
                                    $hasPermission = true;
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                continue; // Skip if user doesn't have view_product_subcategory permission
                            }
                        }
                    }
                    
                    // For Service Categories, check if user has view_service_category permission
                    // Note: This only applies to service categories, NOT product categories
                    if ($isCategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        \Log::info('🔍 Checking Service Categories permission', [
                            'route' => $childValue->route ?? 'N/A',
                            'isCategoriesItem' => $isCategoriesItem,
                            'isProductCategoriesItem' => $isProductCategoriesItem,
                            'user_id' => $user->id,
                            'has_view_service_category' => $this->safeCanCheck($user, 'view_service_category')
                        ]);
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $this->safeCanCheck($user, 'view_service_category');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        $hasPermission = $managerRole->hasPermissionTo('view_service_category');
                                    } catch (\Exception $e) {
                                        $hasPermission = false;
                                    }
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                \Log::info('❌ Hiding Service Categories - no view_service_category permission');
                                continue; // Skip if user doesn't have view_service_category permission
                            }
                        }
                    }
                    
                    // For Service Subcategories, check if user has view_service_subcategory permission
                    // Note: This only applies to service subcategories, NOT product subcategories
                    if ($isSubcategoriesItem && auth()->check()) {
                        $user = auth()->user();
                        if ($user->hasRole('admin')) {
                            // Admin always has access
                        } else {
                            $hasPermission = $this->safeCanCheck($user, 'view_service_subcategory');
                            $isManager = $user->hasRole('manager');
                            
                            // For managers, check if manager role specifically has the permission
                            if ($isManager) {
                                $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
                                if ($managerRole) {
                                    try {
                                        $hasPermission = $managerRole->hasPermissionTo('view_service_subcategory');
                                    } catch (\Exception $e) {
                                        $hasPermission = false;
                                    }
                                } else {
                                    $hasPermission = false;
                                }
                            }
                            
                            if (!$hasPermission) {
                                continue; // Skip if user doesn't have view_service_subcategory permission
                            }
                        }
                    }
                    
                    $childArr = [
                        'title' => __($childValue->title),
                        'active' => $childValue->active,
                        'order' => $childValue->order,
                    ];

                    if (isset($childValue->start_icon)) {
                        $childArr['icon'] = $childValue->start_icon;
                    }

                    if ($childValue->is_route) {
                        if (isset($childValue->route)) {
                            $childArr['route'] = $childValue->route;
                        }
                    } else {
                        if (isset($childValue->url)) {
                            $childArr['url'] = $childValue->url;
                        }
                    }
                    if (isset($childValue->permission) && is_array($childValue->permission) && count($childValue->permission) > 0) {
                        $childArr['permission'] = $childValue->permission;
                    } else {
                        // If child is Location-related, set view_location permission
                        if (stripos($childValue->title ?? '', 'city') !== false ||
                            stripos($childValue->title ?? '', 'state') !== false ||
                            stripos($childValue->title ?? '', 'country') !== false ||
                            stripos($childValue->route ?? '', 'city') !== false ||
                            stripos($childValue->route ?? '', 'state') !== false ||
                            stripos($childValue->route ?? '', 'country') !== false) {
                            $childArr['permission'] = ['view_location'];
                        }
                    }
                    if (isset($childValue['target_type'])) {
                        $childArr['target'] = $childValue->target_type;
                    }
                    if (isset($childValue->children) && count($childValue->children) > 0) {
                        $this->makeMenu($parentMenu, $childValue);
                    } else {
                        switch ($childValue->menu_item_type) {
                            case 'static':
                                $this->staticMenu($parentMenu, ['title' => __($childValue->title), 'order' => $childValue->order, 'permission' => $childValue->permission]);
                                break;

                            case 'parent':
                                $this->makeMenu($parentMenu, $childValue);
                                break;
                            default:
                                $this->childMain($parentMenu, $childArr);
                                break;
                        }
                    }
                }
            } else {
                $arr = [
                    'icon' => $value->start_icon,
                    'title' => __($value->title),
                    'active' => $value->active,
                    'order' => $value->order,
                ];
                if ($value->is_route) {
                    if (isset($value->route)) {
                        $arr['route'] = $value->route;
                    }
                } else {
                    if (isset($value->url)) {
                        $arr['url'] = $value->url;
                    }
                }
                if (isset($value->permission) && is_array($value->permission) && count($value->permission) > 0) {
                    $arr['permission'] = $value->permission;
                }
                if (isset($value['target_type'])) {
                    $arr['target'] = $value->target_type;
                }
                $this->mainRoute($menu, $arr);
            }
        }
    }
}
