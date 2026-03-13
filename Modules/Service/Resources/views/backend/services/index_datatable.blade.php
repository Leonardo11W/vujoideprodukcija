@extends('backend.layouts.app', ['isNoUISlider' => true])

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection


@push('after-styles')
    <link rel="stylesheet" href="{{ mix('modules/service/style.css') }}">
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
                        <x-backend.section-header>
                <div class="d-flex flex-wrap gap-3">
                    @if(auth()->user()->can('edit_service') || auth()->user()->can('delete_service'))
                    <x-backend.quick-action url="{{ route('backend.services.bulk_action') }}">
                        <div class="">
                            <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                                style="width:100%">
                                <option value="">{{ __('messages.no_action') }}</option>
                                @can('edit_service')
                                <option value="change-status">{{ __('messages.status') }}</option>
                                @endcan
                                @can('delete_service')
                                <option value="delete">{{ __('messages.delete') }}</option>
                                @endcan
                            </select>
                        </div>
                        <div class="select-status d-none quick-action-field" id="change-status-action">
                            <select name="status" class="form-control select2" id="status" style="width:100%">
                                <option value="1" selected>{{ __('messages.active') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                            </select>
                        </div>
                    </x-backend.quick-action>
                    @endif
                    <div>
                      <button type="button" class="btn btn-secondary" data-modal="export">
                        <i class="fa-solid fa-upload"></i> {{ __('messages.export') }}
                      </button>
                    </div>
                </div>
                <x-slot name="toolbar">

                    <div>
                        <div class="datatable-filter">
                            <select name="column_status" id="column_status" class="select2 form-control" data-filter="select" style="width: 100%">
                                <option value="">{{__('messages.all')}}</option>
                                <option value="1" {{ $filter['status'] == '1' ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                                <option value="0" {{ $filter['status'] == '0' ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group flex-nowrap">
                        <span class="input-group-text" id="addon-wrapping"><i
                                class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..." aria-label="Search"
                            aria-describedby="addon-wrapping">
                    </div>
                    @hasPermission('add_service')
                        <x-buttons.offcanvas target='#form-offcanvas' title="">
                        {{ __('messages.new') }}</x-buttons.offcanvas>
                    @endhasPermission
                    <button class="btn btn-outline-primary btn-group" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasExample" aria-controls="offcanvasExample"><i
                            class="fa-solid fa-filter"></i>{{__('messages.advance_filter')}}</button>
                </x-slot>
            </x-backend.section-header>
            <table id="datatable" class="table table-striped border table-responsive">
            </table>
        </div>
    </div>
    <div data-render="app">
        @include('service::backend.services.form_offcanvas', [
            'categories' => $categories,
            'subcategories' => $subcategories,
            'customefield' => $customefield,
            'service' => $service
        ])
        <!-- <assign-employee-form-offcanvas></assign-employee-form-offcanvas> -->
        <div id="employee-offcanvas-container"></div>
        @include('service::backend.services.assign_branch_offcanvas', [
            'branches' => $branches ?? []
        ])
        @include('service::backend.services.gallery_form_offcanvas')
    </div>
    <div id="service-page-config"
        data-has-edit="{{ auth()->user()->can('edit_service') ? 'true' : 'false' }}"
        data-has-delete="{{ auth()->user()->can('delete_service') ? 'true' : 'false' }}"
        data-has-status="{{ auth()->user()->can('edit_service') ? 'true' : 'false' }}"
        data-has-gallery="{{ auth()->user()->can('service_gallery') ? 'true' : 'false' }}">
    </div>
    <x-backend.advance-filter>
        <x-slot name="title">
            <h4>{{ __('service.lbl_advanced_filter') }}</h4>
        </x-slot>
        <div class="form-group datatable-filter">
            <label class="form-label" for="column_category">{{ __('service.lbl_category') }}</label>
            <select name="column_category" id="column_category" class="form-control select2" data-filter="select">
                <option value="">{{ __('service.all_categories') }}</option>
                @foreach ($categories as $category)
                    @if ($category->status == 1)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group datatable-filter">
            <label class="form-label" for="column_subcategory">{{ __('service.lbl_sub_category') }}</label>
            <select name="column_subcategory" id="column_subcategory" class="form-control select2" data-filter="select">
                <option value="">{{ __('service.all_sub_categories') }}</option>
                @foreach ($subcategories as $subcategory)
                    @if ($subcategory->status == 1)
                        <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <button type="reset" class="btn btn-danger" id="reset-filter">{{ __('messages.reset') }}</button>
        <div class="form-group custom-range">
            <div class="filter-slider slider-secondary"></div>
        </div>
    </x-backend.advance-filter>


    
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    {{-- <script src="{{ mix('modules/service/script.js') }}"></script> --}}
    <script src="{{ asset('js/form-offcanvas/index.js') }}" defer></script>
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>
    <script src="{{ asset('modules/service/service-form.js') }}" defer></script>
    <!-- DataTables Core and Extensions -->
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

    <script>
        // Translations for advanced filter
        const allCategoriesText = @json(__('service.all_categories'));
        const allSubCategoriesText = @json(__('service.all_sub_categories'));
        
        const columns = [
            {
                data: 'name',
                name: 'name',
                title: "{{ __('service.lbl_name') }}"
            },
            {
                data: 'default_price',
                name: 'default_price',
                title: "{{ __('service.lbl_default_price') }}"
            },
            {
                data: 'duration_min',
                name: 'duration_min',
                title: "{{ __('service.lbl_duration') }}"
            },
            {
                data: 'category_id',
                name: 'category_id',
                title: "{{ __('service.lbl_category_id') }}"
            },
            @if (!$is_single_branch)
                @if(auth()->user()->hasRole('admin'))
                {
                    data: 'branches_count',
                    name: 'branches_count',
                    title: "{{ __('service.lbl_branches') }}",
                    orderable: true,
                    searchable: false,
                },
                @endif
            @endif
            @if(auth()->user()->hasRole('admin'))
            {   
                data: 'employee_count', 
                name: 'employee_count',  
                title: "{{ __('service.lbl_staffs') }}", 
                orderable: true, 
                searchable: false,  
            },
            @endif
            {
                data: 'status',
                name: 'status',
                orderable: true,
                searchable: true,
                title: "{{ __('service.lbl_status') }}",
                width: '5%'
            },
            {
              data: 'updated_at',
              name: 'updated_at',
              title: "{{ __('service.lbl_update_at') }}",
              orderable: true,
              visible: false,
           },
        ];

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('service.lbl_action') }}",
            width: '10%'
        }];

        const pageCfg = document.getElementById('service-page-config');
        const hasEditPermission   = pageCfg?.dataset.hasEdit === 'true';
        const hasDeletePermission = pageCfg?.dataset.hasDelete === 'true';
        const hasStatusPermission = pageCfg?.dataset.hasStatus === 'true';
        const hasGalleryPermission = pageCfg?.dataset.hasGallery === 'true';
        const canShowCheckbox = hasEditPermission || hasDeletePermission || hasStatusPermission;
        const customFieldColumns = {!! $columns !!};

        let finalColumns = [];

        if (canShowCheckbox) {
            finalColumns.push({
                name: 'check',
                data: 'check',
                title: '<input type="checkbox" class="form-check-input" id="select-all-table" onclick="selectAllTable(this)">',
                width: '2%',
                exportable: false,
                orderable: false,
                searchable: false,
            });
        }

        finalColumns = [
            ...finalColumns,
            ...columns,
            ...customFieldColumns,
            ...(hasEditPermission || hasDeletePermission || hasGalleryPermission ? actionColumn : [])
        ];

        document.addEventListener('DOMContentLoaded', (event) => {
            const offcanvasElem = document.querySelector('#offcanvasExample');
            let offcanvasInstance = null;
            
            // Get or create offcanvas instance - ensure it's always available
            function getOffcanvasInstance() {
                if (!offcanvasElem) return null;
                if (!offcanvasInstance) {
                    offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(offcanvasElem);
                }
                return offcanvasInstance;
            }

            // Initialize Datatable
            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                orderColumn: [[finalColumns.findIndex(c => c.data === 'updated_at'), 'desc']],
                advanceFilter: () => {
                    return {
                        category_id: $('#column_category').val(),
                        sub_category_id: $('#column_subcategory').val(),
                    }
                }
            });

            if (offcanvasElem) {
                // Initialize instance
                offcanvasInstance = getOffcanvasInstance();
                
                // Function to clean up leftover backdrops (only remove duplicates)
                function cleanupDuplicateBackdrops() {
                    const backdrops = document.querySelectorAll('.offcanvas-backdrop');
                    // If more than one backdrop exists, remove extras (keep the last one)
                    if (backdrops.length > 1) {
                        for (let i = 0; i < backdrops.length - 1; i++) {
                            if (backdrops[i] && backdrops[i].parentNode) {
                                backdrops[i].parentNode.removeChild(backdrops[i]);
                            }
                        }
                    }
                }
                
                // Function to fully clean up after offcanvas is hidden
                function fullCleanup() {
                    // Only clean up if offcanvas is actually hidden
                    if (!offcanvasElem.classList.contains('show')) {
                        // Remove all backdrops
                        const backdrops = document.querySelectorAll('.offcanvas-backdrop');
                        backdrops.forEach(function(backdrop) {
                            if (backdrop && backdrop.parentNode) {
                                backdrop.parentNode.removeChild(backdrop);
                            }
                        });
                        
                        // Reset body classes and styles
                        document.body.classList.remove('offcanvas-open', 'modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }
                }
                
                // Handle backdrop clicks to close offcanvas
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('offcanvas-backdrop')) {
                        const inst = getOffcanvasInstance();
                        if (inst) {
                            inst.hide();
                        }
                    }
                });
                
                // Before showing, clean up any duplicate backdrops but don't interfere with Bootstrap
                offcanvasElem.addEventListener('show.bs.offcanvas', function(e) {
                    // Ensure instance is available
                    offcanvasInstance = getOffcanvasInstance();
                    // Only clean up duplicates, don't interfere with the current show operation
                    cleanupDuplicateBackdrops();
                    // Ensure offcanvas element is ready
                    if (offcanvasElem) {
                        offcanvasElem.style.display = 'block';
                    }
                });
                
                // Initialize Select2 and ensure visibility when offcanvas is shown
                offcanvasElem.addEventListener('shown.bs.offcanvas', function() {
                    // Clean up any duplicate backdrops that might have been created
                    cleanupDuplicateBackdrops();
                    
                    // Reinitialize Select2 with proper dropdown parent for offcanvas
                    // Destroy existing Select2 instances to prevent conflicts
                    if ($('#column_category').hasClass('select2-hidden-accessible')) {
                        $('#column_category').select2('destroy');
                    }
                    if ($('#column_subcategory').hasClass('select2-hidden-accessible')) {
                        $('#column_subcategory').select2('destroy');
                    }
                    
                    // Initialize Select2 with dropdownParent set to offcanvas
                    $('#column_category, #column_subcategory').select2({
                        dropdownParent: $('#offcanvasExample'),
                        width: '100%'
                    });
                });
                
                // Ensure offcanvas is properly reset when hidden
                offcanvasElem.addEventListener('hidden.bs.offcanvas', function() {
                    // Clean up backdrop and reset state after animation completes
                    setTimeout(function() {
                        fullCleanup();
                        // Ensure instance is still available for next open
                        offcanvasInstance = getOffcanvasInstance();
                    }, 200);
                });
                
                // Ensure offcanvas can be toggled - handle button clicks to ensure instance exists
                document.addEventListener('click', function(e) {
                    const trigger = e.target.closest('[data-bs-target="#offcanvasExample"], [data-bs-toggle="offcanvas"][data-bs-target="#offcanvasExample"]');
                    if (trigger && offcanvasElem) {
                        // Ensure instance exists before Bootstrap tries to toggle
                        offcanvasInstance = getOffcanvasInstance();
                    }
                }, true);
            }

            // Category filter logic
            $('#column_category').on('change', function() {
                const categoryId = $(this).val();
                updateSubcategoryOptions(categoryId);
                window.renderedDataTable.ajax.reload(null, false);
            });

            // Subcategory filter logic
            $('#column_subcategory').on('change', function() {
                window.renderedDataTable.ajax.reload(null, false);
            });

            // Reset filter logic
            $('#reset-filter').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Reset filter values
                $('#column_category').val('').trigger('change.select2');
                $('#column_subcategory').val('').trigger('change.select2');
                updateSubcategoryOptions('');
                window.renderedDataTable.ajax.reload(null, false);
                
                // Properly hide offcanvas - Bootstrap will handle the cleanup
                const inst = getOffcanvasInstance();
                if (inst) {
                    inst.hide();
                }
            });

            function updateSubcategoryOptions(categoryId) {
                const $subSelect = $('#column_subcategory');
                $subSelect.empty().append('<option value="">' + allSubCategoriesText + '</option>');

                let subcategories = @json($subcategories);
                if (categoryId) {
                    subcategories = subcategories.filter(s => s.parent_id == categoryId);
                }

                subcategories.forEach(s => {
                    $subSelect.append(`<option value="${s.id}">${s.name}</option>`);
                });
                
                $subSelect.trigger('change.select2');
            }

            // Employee Assignment Offcanvas
            $(document).on('click', '[data-assign-event="employee_assign"]', function() {
                const serviceId = $(this).data('assign-module');
                const url = '{{ route("backend.services.assign_employee_offcanvas", ["id" => "::id::"]) }}'.replace('::id::', serviceId);
                
                $('#employee-offcanvas-container').html('<div class="text-center p-4"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');
                
                $.get(url, function(html) {
                    $('#employee-offcanvas-container').html(html);
                    const offcanvas = new bootstrap.Offcanvas(document.getElementById('service-employee-assign-form'));
                    offcanvas.show();
                }).fail(() => {
                    $('#employee-offcanvas-container').html('<div class="text-center p-4 text-danger">Error loading data</div>');
                });
            });

            // Gallery Offcanvas
            $(document).on('click', '[data-gallery-event="service_gallery"]', function() {
                const serviceId = $(this).data('gallery-module');
                const serviceName = $(this).closest('tr').find('td:eq(1)').text().trim();
                if (typeof openGalleryOffcanvas === 'function') {
                    openGalleryOffcanvas(serviceId, serviceName);
                }
            });
        });

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            const $applyBtn = $('#quick-action-apply');
            
            if (actionValue) {
                $applyBtn.removeAttr('disabled');
                $('.quick-action-field').addClass('d-none');
                if (actionValue === 'change-status') {
                    $('#change-status-action').removeClass('d-none');
                }
            } else {
                $applyBtn.attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').on('change', resetQuickAction);
    </script>
@endpush
