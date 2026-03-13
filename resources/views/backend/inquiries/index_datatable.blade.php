@extends('backend.layouts.app')

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection

@push('after-styles')
@endpush

@section('content')
    <div class="card">
        <div class="card-body">
            <x-backend.section-header>
                <div>
                    @if (auth()->user()->can('delete_inquiry'))
                        <x-backend.quick-action url="{{ route('backend.inquiries.bulk_action') }}">
                            <div class="">
                                <select name="action_type" class="form-control select2 col-12" id="quick-action-type"
                                    style="width:100%">
                                    <option value="">{{ __('messages.no_action') }}</option>
                                    @can('delete_inquiry')
                                        <option value="delete">{{ __('messages.delete') }}</option>
                                    @endcan
                                </select>
                            </div>
                        </x-backend.quick-action>
                    @endif
                </div>
                <x-slot name="toolbar">
                    <div class="input-group flex-nowrap">
                        <span class="input-group-text" id="addon-wrapping"><i
                                class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control dt-search" placeholder="{{ __('messages.search') }}"
                            aria-label="Search" aria-describedby="addon-wrapping">
                    </div>


                </x-slot>
            </x-backend.section-header>
            <table id="datatable" class="table table-striped border table-responsive">
            </table>
        </div>
    </div>
@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
    <!-- DataTables Core and Extensions -->
    <script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>

    <script type="text/javascript" defer>
        const columns = [{
                name: 'check',
                data: 'check',
                title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                width: '0%',
                exportable: false,
                orderable: false,
                searchable: false,
            },
            {
                data: 'name',
                name: 'name',
                title: "{{ __('inquiry.lbl_name') }}",
                width: '15%',
            },
            {
                data: 'email',
                name: 'email',
                title: "{{ __('inquiry.lbl_email') }}",
                width: '20%',
            },
            {
                data: 'subject',
                name: 'subject',
                title: "{{ __('inquiry.lbl_subject') }}",
                width: '25%',
            },
            {
                data: 'message',
                name: 'message',
                title: "{{ __('inquiry.lbl_message') }}",
                width: '25%',
            },
            {
                data: 'created_at',
                name: 'created_at',
                title: "{{ __('inquiry.lbl_created_at') }}",
                orderable: true,
                width: '15%',
            },
        ]

        const actionColumn = [{
            data: 'action',
            name: 'action',
            orderable: false,
            searchable: false,
            title: "{{ __('inquiry.lbl_action') }}",
            width: '10%'
        }]

        // Check permissions
        const hasViewPermission = @json(auth()->user()->can('view_inquiry'));
        const hasDeletePermission = @json(auth()->user()->can('delete_inquiry'));

        // Add the action column only if the user has view or delete permission
        let finalColumns = [...columns];
        if (hasViewPermission || hasDeletePermission) {
            finalColumns = [...finalColumns, ...actionColumn];
        }

        document.addEventListener('DOMContentLoaded', (event) => {
            initDatatable({
                url: '{{ route("backend.$module_name.index_data") }}',
                finalColumns,
                orderColumn: [
                    [5, "desc"]
                ],
            })
        })

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');
            } else {
                $('#quick-action-apply').attr('disabled', true);
            }
        }

        $('#quick-action-type').change(function() {
            resetQuickAction()
        });
    </script>
@endpush
