@extends('frontend::layouts.master')
@section('title'){{__('messages.Notifications')}} @endsection

@section('content')
<x-breadcrumb title="{{ __('frontend.notifications') }}" />

<div class="list-page section-spacing-bottom px-0">
    <div class="page-title" id="page_title">
        <div class="container">
            <div class="tab-content mt-5">
                <div class="tab-pane active p-0 all-appointments" id="all-appointments">
                    <ul class="list-inline m-0">
                        <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader">
                            @for ($i = 0; $i < 8; $i++)
                                @include('frontend::components.card.shimmer_appointment_card')
                            @endfor
                        </div>

                        <!-- Correct table ID -->
                        <table id="datatable" class="table table-responsive custom-card-table"></table>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@endpush

@push('scripts')
<script src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const shimmerLoader = document.getElementById('shimmer-loader');
    const dataTableElement = document.getElementById('datatable');

    let finalColumns = [
        { data: 'card', name: 'card', orderable: false, searchable: true }
    ];

    const notificationTable = $('#datatable').DataTable({
        processing: false,
        serverSide: true,
        // ordering: true by default
        ajax: {
            url: "{{ route('user-notifications.index_data') }}",
            type: "GET",
            data: function (d) {
                d.search = $('#notificationSearch').val(); // optional input
                d.type_filter = $('#notificationTypeFilter').val(); // optional filter
            }
        },
        columns: finalColumns,
        searching: true,
        lengthChange: true,
        pageLength: 6,
        info: true,
        pagingType: 'simple_numbers',
        dom: '<"row"<"col-sm-12"t>><"row mt-2 align-items-center"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: "",
            searchPlaceholder: "Search notifications...",
            paginate: {
                next: 'Next &raquo;',
                previous: '&laquo; Previous'
            },
            emptyTable: "<div class='text-center p-4'>No notifications available.</div>",
            zeroRecords: "<div class='text-center p-4'>No matching notifications found.</div>",
            
        },
        createdRow: function (row, data, dataIndex) {
            $(row).children('td').addClass('p-0');
        },
        initComplete: function () {
            shimmerLoader.classList.add('d-none');
            dataTableElement.classList.remove('d-none');
        },
        drawCallback: function(settings) {
            var api = this.api();
            var dataCount = api.data().count();
            if (dataCount === 0) {
                // Hide sorting UI if no data
                $("#datatable thead").hide();
            } else {
                $("#datatable thead").show();
            }
        }
    });

    // Optional: auto reload on input/filter change
    $('#notificationSearch, #notificationTypeFilter').on('input change', function () {
        notificationTable.ajax.reload();
    });

    // Remove any sorting UI forcibly if injected by global settings or layout
    $("#datatable thead .sorting, #datatable thead .sorting_asc, #datatable thead .sorting_desc").removeClass("sorting sorting_asc sorting_desc");
});
</script>
@endpush

