@extends('backend.layouts.app')

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ mix('modules/earning/style.css') }}">
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <x-backend.section-header>
                <div class="d-flex flex-wrap gap-3">
                    <div>
                        <button type="button" class="btn btn-secondary" data-modal="export">
                            <i class="fa-solid fa-upload"></i> {{ __('messages.export') }}
                        </button>
                        {{--          <button type="button" class="btn btn-secondary" data-modal="import"> --}}
                        {{--            <i class="fa-solid fa-upload"></i> Import --}}
                        {{--          </button> --}}
                    </div>
                </div>
                <x-slot name="toolbar">
                    <div id="earning-page-config"
                         data-has-action="{{ ((auth()->user()->hasRole('employee') && !auth()->user()->hasRole('manager') && !auth()->user()->hasRole('admin')) || (auth()->user()->hasRole('manager') && session('my_work_mode'))) ? 'false' : (auth()->user()->can('view_earning') ? 'true' : 'false') }}">
                    </div>
                    <div class="input-group flex-nowrap">
                      <span class="input-group-text" id="addon-wrapping"><i class="fa-solid fa-magnifying-glass"></i></span>
                      <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}..." aria-label="Search" aria-describedby="addon-wrapping">
                    </div>
                  </x-slot>
                </x-backend.section-header>
            <table id="datatable" class="table border table-responsive">
            </table>
        </div>
    </div>
    <div data-render="app">
        <earning-form-offcanvas create-title="{{ __('messages.create') }} {{ __('messages.new') }} {{ __($module_title) }}"
            edit-title="{{ __('messages.create') }} {{ __('messages.create') }} {{ __('Staff Payout') }} "></earning-form-offcanvas>
    </div>
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <script src="{{ mix('modules/earning/script.js') }}"></script>
    <script src="{{ asset('js/form-offcanvas/index.js') }}" defer></script>
    <script src="{{ asset('js/form-modal/index.js') }}" defer></script>

    <!-- DataTables Core and Extensions -->
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', (event) => {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                language: {
                    processing: window.translations.processing,
                    search: window.translations.search,
                    lengthMenu: window.translations.lengthMenu,
                    info: window.translations.info,
                    infoEmpty: window.translations.infoEmpty,
                    infoFiltered: window.translations.infoFiltered,
                    loadingRecords: window.translations.loadingRecords,
                    zeroRecords: window.translations.zeroRecords,
                    emptyTable: window.translations.emptyTable,
                    paginate: {
                        first: window.translations.paginate.first,
                        last: window.translations.paginate.last,
                        next: window.translations.paginate.next,
                        previous: window.translations.paginate.previous
                    }
                },
                dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" l><"col-md-6" p>><"clear">',
                ajax: {
                    "type"   : "GET",
                    "url"    : '{{ route("backend.$module_name.index_data") }}',
                    "data"   : function( d ) {
                    d.search = {
                        value: $('.dt-search').val()
                    };
                    d.filter = {
                        column_status: $('#column_status').val()
                    }
                    },
                },
                columns: (function() {
                    const baseColumns = [
                        { data: 'first_name',name: 'first_name', title: "{{ __('messages.name') }}" ,  orderable: true },
                        { data: 'total_booking', name: 'total_booking', title: "{{ __('earning.lbl_tot_booking') }}",  orderable: false, searchable: false },
                        { data: 'total_service_amount', name: 'total_service_amount', title: "{{ __('earning.lbl_total_earning') }}",  orderable: true , searchable: false},
                        { data: 'total_commission_earn', name: 'total_commission_earn', title: "{{ __('earning.lbl_total_commission') }}", orderable: true, searchable: false},
                        { data: 'total_tips_earn', name: 'total_tips_earn', title: "{{ __('earning.lbl_total_tip') }}", orderable: true, searchable: false},
                        { data: 'total_pay', name: 'total_pay', title: "{{ __('earning.lbl_staff_earning') }}", orderable: false, searchable: false },
                    ];
                    const actionColumn = [{
                        data: 'action',
                        name: 'action',
                        title: "{{ __('earning.lbl_action') }}",
                        orderable: false,
                        searchable: false
                    }];
                    const cfg = document.getElementById('earning-page-config');
                    const hasAction = cfg && cfg.dataset.hasAction === 'true';
                    return hasAction ? [...baseColumns, ...actionColumn] : baseColumns;
                })()
            });
        })


        const formOffcanvas = document.getElementById('form-offcanvas')

        const instance = bootstrap.Offcanvas.getOrCreateInstance(formOffcanvas)

        $(document).on('click', '[data-crud-id]', function() {
            setEditID($(this).attr('data-crud-id'), $(this).attr('data-parent-id'))
        })

        function setEditID(id, parent_id) {
            if (id !== '' || parent_id !== '') {
                const idEvent = new CustomEvent('crud_change_id', {
                    detail: {
                        form_id: id,
                        parent_id: parent_id
                    }
                })
                document.dispatchEvent(idEvent)
            } else {
                removeEditID()
            }
            instance.show()
        }

        function removeEditID() {
            const idEvent = new CustomEvent('crud_change_id', {
                detail: {
                    form_id: 0,
                    parent_id: null
                }
            })
            document.dispatchEvent(idEvent)
        }

        formOffcanvas?.addEventListener('hidden.bs.offcanvas', event => {
            removeEditID()
        })

        // Test export button functionality
        $(document).on('click', '[data-modal="export"]', function() {
            console.log('Export button clicked in earnings page');
        });

        // Additional debugging for export functionality
        $(document).ready(function() {
            console.log('Earnings page loaded');
            console.log('Export button count:', $('[data-modal="export"]').length);
            console.log('Import-export render elements:', $('[data-render="import-export"]').length);
            
            // Check if the export modal is properly initialized
            setTimeout(function() {
                console.log('Export modal elements after timeout:', $('[data-render="import-export"]').length);
                if ($('[data-render="import-export"]').length > 0) {
                    console.log('Export modal HTML:', $('[data-render="import-export"]').html());
                } else {
                    console.log('No export modal found - this might be the issue');
                }
            }, 2000);
        });
    </script>
@endpush
