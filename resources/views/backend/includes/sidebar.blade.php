@php
$canRenderSidebar = auth()->check() && (auth()->user()->can('menu_builder_sidebar') || auth()->user()->hasRole('manager') || auth()->user()->hasRole('employee'));
$hideFaqAndLocation = auth()->check() && (auth()->user()->hasRole('manager') || auth()->user()->hasRole('employee'));
@endphp
@if ($canRenderSidebar)
<div class="sidebar-base pr-hide
            {{ getCustomizationSetting('sidebar_show') == 'sidebar-none' ? 'sidebar-none' : 'sidebar' }}
            {{ !empty(getCustomizationSetting('sidebar_menu_style')) ? getCustomizationSetting('sidebar_menu_style') : 'left-bordered' }}
            {{ getCustomizationSetting('sidebar_color') }}
            {{ !empty(getCustomizationSetting('sidebar_type')) ? implode(' ',getCustomizationSetting('sidebar_type')) : '' }}
            "
    data-toggle="main-sidebar" id="sidebar" data-sidebar="responsive">
    <div class="d-flex align-items-center justify-content-start">
        <div class="logo-main">
            @can('view_dashboard')
            <a href="{{route('backend.dashboard')}}" class="navbar-brand">
                <img class="logo-normal img-fluid" src="{{asset(setting('logo'))}}" height="30" alt="{{ app_name() }}">
                <img class="logo-normal dark-normal img-fluid" src="{{asset(setting('dark_logo'))}}" height="30" alt="{{ app_name() }}">
                <img class="logo-mini img-fluid" src="{{asset(setting('mini_logo'))}}" height="30" alt="{{ app_name() }}">
                <img class="logo-mini dark-mini img-fluid" src="{{asset(setting('dark_mini_logo'))}}" height="30" alt="{{ app_name() }}">
            </a>
            @endcan
        </div>
        <div class="sidebar-toggle" data-toggle="sidebar" data-active="true">
            <i class="icon">
                <svg class="icon-20" width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4.25 12.2744L19.25 12.2744" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </i>
        </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
        <div class="sidebar-list" id="sidebar">
            <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
                @php
                // Ensure common setting permissions exist before menu generation
                $commonSettingPermissions = [
                    'setting_general',
                    'setting_misc',
                    'setting_quick_booking',
                    'setting_custom_code',
                    'setting_customization',
                    'setting_mail',
                    'setting_notification',
                    'setting_intigrations',
                    'setting_custom_fields',
                    'setting_currency',
                    'setting_commission',
                    'setting_holiday',
                    'setting_bussiness_hours',
                    'setting_payment_method',
                    'setting_language',
                    'setting_menu_builder',
                ];
                
                $permissionsCreated = false;
                foreach ($commonSettingPermissions as $permName) {
                    try {
                        $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                            ['name' => $permName, 'guard_name' => 'web'],
                            ['is_fixed' => false]
                        );
                        if ($permission->wasRecentlyCreated) {
                            $permissionsCreated = true;
                        }
                    } catch (\Exception $e) {
                        // Silently continue if permission creation fails
                    }
                }
                
                // Clear permission cache if new permissions were created
                if ($permissionsCreated) {
                    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
                }
                
                $menu = new \App\Http\Middleware\GenerateMenus();
                $menu = $menu->handle('menu', 'vertical', 'ARRAY_MENU');
                
                // System-related permissions from the System static item config
                $systemPermissions = [
                    'system_settings', 'view_page', 'view_app_banner', 
                    'view_role_permissions', 'view_notification_list', 
                    'view_notification_template', 'setting_general', 
                    'setting_misc', 'setting_quick_booking', 'setting_custom_code',
                    'setting_customization', 'setting_mail', 'setting_notification',
                    'setting_intigrations', 'setting_custom_fields', 'setting_currency',
                    'setting_commission', 'setting_holiday', 'setting_bussiness_hours',
                    'setting_payment_method', 'setting_language', 'setting_menu_builder'
                ];
                
                $systemOrder = 22;
                
                // First, check if there are any visible system items (order > 22)
                $hasVisibleSystemItems = false;
                foreach ($menu->roots() as $item) {
                    $itemOrder = $item->data('order') ?? 0;
                    if ($itemOrder > $systemOrder) {
                        $permissions = $item->data('permission') ?? [];
                        if (!empty(array_intersect($permissions, $systemPermissions)) || $item->nickname == 'sidebarlocation') {
                            $hasVisibleSystemItems = true;
                            break;
                        }
                    }
                }
                
                // Filter out System static item if no visible system items exist
                if (!$hasVisibleSystemItems) {
                    $menu->filter(function ($item) use ($systemOrder, $systemPermissions) {
                        $itemOrder = $item->data('order') ?? 0;
                        // If this is the System static item (order 22 with system permissions), hide it
                        if ($itemOrder == $systemOrder) {
                            $itemPermissions = $item->data('permission') ?? [];
                            if (!empty(array_intersect($itemPermissions, $systemPermissions))) {
                                return false; // Hide this item
                            }
                        }
                        return true; // Keep all other items
                    });
                }

                @endphp
                @include(config('laravel-menu.views.bootstrap-items'), ['items' => $menu->roots()])
                @can('view_faq')
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('faq.index') }}">
                        <i class="icon fa-solid fa-question-circle"></i>
                        <span class="item-name">{{ __('sidebar.faqs') }}</span>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </div>
    <div class="sidebar-footer"></div>
</div>
@endif