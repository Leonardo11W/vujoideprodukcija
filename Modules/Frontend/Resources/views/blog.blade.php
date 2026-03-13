@extends('frontend::layouts.master')

@section('title', __('frontend.blogs'))

@section('content')
    <x-breadcrumb title="Blogs" />

    <div class="blog-section section-spacing-inner-pages">
        <div class="container">
            <div class="section-title d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span
                        class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.our_blog') }}</span>
                    <h4 class="title mb-0">{{ __('frontend.our_latest_blog_posts') }}</h4>
                </div>
                <div>
                    <div class="input-group mb-0">
                        <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="text" class="form-control p-2" id="searchInput"
                            placeholder="{{ __('frontend.example_blog_title_content_author') }}">
                    </div>
                </div>
            </div>

            {{-- Container where blog cards will be rendered --}}
            <div id="blogCardContainer"></div>

            {{-- Hidden table for DataTables structure --}}
            <table id="blog-cards-table" class="table d-none w-100">
                <thead>
                    <tr>
                        <th>Card</th>
                        <th>Title</th> {{-- hidden column for search --}}
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('after-styles')
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('scripts')
    <!-- <script src="{{ asset('vendor/datatable/datatables.min.js') }}"></script> -->
    <script>
        $(document).ready(function() {
            const $table = $('#blog-cards-table');
            const $container = $('#blogCardContainer');

            const table = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('blog.index_data') }}",
                columns: [{
                        data: 'card',
                        name: 'card',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title',
                        visible: false
                    } // hidden but searchable
                ],
                pageLength: 6, // Show 6 blogs per page
                searching: true,
                lengthChange: false,
                pagingType: 'simple_numbers',
                dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
                language: {
                    searchPlaceholder: 'Search blogs...',
                    search: '',
                    emptyTable: "<div class='text-center p-4'>{{ __('frontend.no_blogs_available_at_the_moment') }}</div>",
                    zeroRecords: "<div class='text-center p-4'>{{ __('frontend.no_matching_blogs_found') }}.</div>",
                    processing: '<div class="d-flex justify-content-center align-items-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">{{ __('frontend.loading') }}</span></div></div>'
                },
                drawCallback: function(settings) {
                    const data = table.rows().data();
                    $container.empty();

                    // Render two rows of 3 columns each (total 6 per page)
                    for (let i = 0; i < data.length; i += 3) {
                        const row = $('<div class="row row-cols-1 row-cols-md-3 g-4 mb-4"></div>');
                        for (let j = i; j < i + 3 && j < data.length; j++) {
                            const cardHtml = data[j].card;
                            row.append(`<div class="col">${cardHtml}</div>`);
                        }
                        $container.append(row);
                    }
                    // Log current page info for debugging
                    const pageInfo = table.page.info();

                }
            });

            $('#searchInput').on('keyup', function() {

                table.search(this.value).draw();
            });
        });
    </script>
@endpush
