<div class="iq-navbar-header navs-bg-color admin-sub-header">
    <div class="container-fluid iq-container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="px-4">
                        <h2 class="admin-page-title">{{ __($module_title ?? '') }}</h2>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                      @if (!isset($global_booking))
                        @hasPermission('add_booking')
                        @if(auth()->user()->hasRole('admin')||auth()->user()->hasRole('manager'))
                        <a href="javascript:void(0)" class="btn btn-light btn-admin-cta" id="appointment-button"><i class="fa-solid fa-plus me-1"></i>{{ __('messages.appointment') }}</a>
                        @endif
                        @endhasPermission
                        @endif
                      @yield('banner-button')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="iq-header-img"></div>
</div>
