@extends('frontend::layouts.master')
@section('title')
    {{ __('messages.category') }}
@endsection

@section('content')
    <x-breadcrumb title="{{ __('messages.category') }}" />

    {{-- @php
    dd(auth()->user());
@endphp --}}

    <div class="category-section section-spacing-inner-pages vujo-page-inner vujo-category-page">
        <div class="container">
            <div class="section-title d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span
                        class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.our_category') }}</span>
                    <h4 class="title mb-0">{{ __('frontend.our_premium_category') }}</h4>
                </div>
                <div>
                    <div class="input-group mb-0">
                        <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="search" class="form-control p-2" id="searchInput"
                            placeholder='{{ __('frontend.eg_spa_hair_massage') }}'>

                    </div>
                </div>
            </div>

            {{-- Container where rows will be rendered --}}
            <div id="categoryCardContainer"></div>

            <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 d-none">
                @for ($i = 0; $i < 8; $i++)
                    @include('frontend::components.card.shimmer_category_card')
                @endfor
            </div>

            {{-- Hidden table for DataTables structure --}}
            <table id="category-cards-table" class="table d-none w-100">
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
        $(document).ready(function() {
            const shimmerLoader = document.getElementById('shimmer-loader');
            const $table = $('#category-cards-table');
            const $container = $('#categoryCardContainer');

            // Show shimmer loader initially
            shimmerLoader.classList.remove('d-none');
            $container.addClass('d-none');

            const table = $table.DataTable({
                serverSide: true,
                ajax: "{{ route('frontend.categories.data') }}",
                columns: [{
                        data: 'card',
                        name: 'card',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name',
                        visible: false
                    } // hidden but searchable
                ],
                pageLength: 10,
                searching: true,
                lengthChange: false,
                processing: false,
                pagingType: 'simple_numbers',
                dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
                language: {
                    searchPlaceholder: 'Search categories...',
                    search: '',
                    emptyTable: "<div class='text-center p-4'>No data available.</div>",
                    zeroRecords: "<div class='text-center p-4'>No search results found.</div>",
                },
                drawCallback: function(settings) {
                    const data = table.rows().data();
                    $container.empty();

                    if (data.length === 0) {
                        $container.html('<div class="text-center p-4">No data available.</div>');
                        return;
                    }

                    for (let i = 0; i < data.length; i += 5) {
                        const row = $(
                            '<div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 gy-4 mb-4"></div>'
                        );
                        for (let j = i; j < i + 5 && j < data.length; j++) {
                            const cardHtml = data[j].card;
                            row.append(`<div class="col">${cardHtml}</div>`);
                        }
                        $container.append(row);
                    }
                }
            });

            // Show loader before AJAX
            $table.on('preXhr.dt', function() {
                $('#categoryCardContainer').empty();
                shimmerLoader.classList.remove('d-none');
                $container.addClass('d-none');
            });

            // Hide loader after data loads
            $table.on('xhr.dt', function() {
                shimmerLoader.classList.add('d-none');
                $container.removeClass('d-none');
            });

            $('#searchInput').on('keyup', function() {
                // Show shimmer loader during search
                shimmerLoader.classList.remove('d-none');
                $container.addClass('d-none');
                table.search(this.value).draw();
            });

            // Reload table data when search is cleared
            $('#searchInput').on('input', function() {
                if (this.value === '') {
                    // Show shimmer loader when clearing search
                    shimmerLoader.classList.remove('d-none');
                    $container.addClass('d-none');
                    // Clear search and reload data
                    table.search('').draw();
                }
            });

        });
    </script>
@endpush
