@extends('frontend::layouts.master')

@section('content')

<x-breadcrumb title="{{ __('frontend.packages') }}" />
<div class="package-section section-spacing">
    <div class="container">
        <div class="section-title">
           <div class="text-center">
                <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__("frontend.top_packages")}}</span>
                <h4 class="title mb-0">{{__("frontend.explore_our_exclusive_package_options")}}</h4>
           </div>
        </div>
        <div id="packageCardContainer"></div>
        <table id="package-cards-table" class="table" style="display:none;">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Name</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const $container = $('#packageCardContainer');
    const noRecordsHtml = `
        <div class="col-12">
            <div class="no-record-found text-center">
                <img src="{{ asset('img/frontend/no-record.png') }}" alt="No Record Found">
                <h4 class="mt-3">No Record Found</h4>
            </div>
        </div>
    `;

    // Initialize DataTable with proper configuration
    const table = $('#package-cards-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('frontend.packages.data') }}",
            type: 'GET'
        },
        columns: [
            { data: 'card', name: 'card', orderable: false, searchable: false },
            { data: 'name', name: 'name', visible: false }
        ],
        pageLength: 3,
        searching: true,
        lengthChange: false,
        pagingType: 'simple_numbers',
        dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
        language: {
            searchPlaceholder: 'Search packages...',
            search: '',
            emptyTable: "<div class='text-center p-4'>{{__('frontend.no_packages_available_at_the_moment')}}</div>",
            zeroRecords: "<div class='text-center p-4'>{{__('frontend.no_matching_packages_found')}}</div>",
            processing: '<div class="d-flex justify-content-center align-items-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>'
        },
        drawCallback: function(settings) {
            const data = this.api().rows().data();
            $container.empty();

            // Render each card as a single column in a single row
            const row = $('<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4"></div>');
            for (let i = 0; i < data.length; i++) {
                row.append(`<div class="col">${data[i].card}</div>`);
            }
            $container.append(row);

            // Initialize package description functionality
            $('.package-description').each(function() {
                var $paragraph = $(this);
                var $container = $paragraph.parent();
                var $toggle = $container.find('.read-more-toggle');
                var lineHeight = parseFloat($paragraph.css('line-height'));
                var maxHeight = 3 * lineHeight;

                if ($paragraph[0].scrollHeight > maxHeight) {
                    $paragraph.css({
                        'max-height': maxHeight + 'px',
                        'overflow': 'hidden',
                        'text-overflow': 'ellipsis',
                        'display': '-webkit-box',
                        '-webkit-line-clamp': '3',
                        '-webkit-box-orient': 'vertical'
                    });
                    $toggle.show();
                } else {
                    $toggle.hide();
                }
            });

            // Add click handlers for read more/less
            $('.read-more-toggle').on('click', function() {
                var $paragraph = $(this).prev('.package-description');
                if ($paragraph.css('overflow') === 'hidden') {
                    $paragraph.css({
                        'max-height': 'none',
                        'overflow': 'visible',
                        'text-overflow': 'clip',
                        'display': 'block',
                        '-webkit-line-clamp': 'unset'
                    });
                    $(this).text('Show Less');
                } else {
                    $paragraph.css({
                        'max-height': '3em',
                        'overflow': 'hidden',
                        'text-overflow': 'ellipsis',
                        'display': '-webkit-box',
                        '-webkit-line-clamp': '3',
                        '-webkit-box-orient': 'vertical'
                    });
                    $(this).text('Read More');
                }
            });
        }
    });
});
</script>
@endpush