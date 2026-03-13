@extends('frontend::layouts.master')
@section('title')
    {{ __('messages.expert') }}
@endsection

@section('content')
    <x-breadcrumb title="Experts" />

    {{-- @php
    dd(auth()->user());
@endphp --}}

<div class="export-section section-spacing">
    <div class="container">
        <div class="section-title d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__("frontend.top_experts")}}</span>
                <h4 class="title mb-0">{{__("frontend.meet_our_experts")}}</h4>
            </div>
            <div>
                <div class="input-group mb-0">
                    <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="text" class="form-control p-2" id="searchInput" placeholder='{{__("frontend.example_expert_service_branch")}}'>
                </div>
            </div>
        </div>
        <div id="expertCardContainer"></div>

        <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader">
            @for ($i = 0; $i < 4; $i++)
                @include('frontend::components.card.shimmer_employee_card')
            @endfor
        </div>

        <table id="experts-cards-table" class="table d-none w-100">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Name</th> {{-- hidden column for search --}}
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    const $table = $('#experts-cards-table');
    const $container = $('#expertCardContainer');

      const shimmerLoader = document.querySelector('.shimmer-loader');

    const table = $table.DataTable({
        processing: false,
        serverSide: true,
        ajax: "{{ route('frontend.experts.data') }}",
        columns: [
            { data: 'card', name: 'card', orderable: false, searchable: false },
            { data: 'name', name: 'name', visible: false } // hidden but searchable
        ],
        pageLength: 10,
        searching: true,
        lengthChange: false,
        pagingType: 'simple_numbers',
        dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
        language: {
            searchPlaceholder: 'Search experts...',
            search: '',
            emptyTable: "<div class='text-center p-4'>{{__('frontend.no_experts_available_at_the_moment')}}</div>",
            zeroRecords: "<div class='text-center p-4'>{{__('frontend.no_matching_experts_found')}}</div>",

        },
        drawCallback: function (settings) {
            const data = table.rows().data();
            $container.empty();
            if (data.length === 0) {

               $container.append(`<div class="text-center p-4">{{__("frontend.no_data_available")}}</div>`);

            }else{

            const row = $('<div class="row row-cols-2 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 gy-4 mb-4"></div>');
            for (let i = 0; i < data.length; i += 6) {

                for (let j = i; j < i + 6 && j < data.length; j++) {
                    const cardHtml = data[j].card;
                    row.append(`<div class="col">${cardHtml}</div>`);
                }
                $container.append(row);
              }


            }

        }
    });

    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    table.on('xhr.dt', function () {
    shimmerLoader.classList.add('d-none');
});
});
</script>
@endpush
