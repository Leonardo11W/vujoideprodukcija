<div id="service-global-loader" class="d-none">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        @php

            $selected_service = session('selected_service');
            $selected_category = session('selected_category');

            session()->forget('selected_service');
            session()->forget('selected_category');

        @endphp

        <input type="hidden" id="category_slug" name="category_slug" value={{ $category }}></input>
        <input type="hidden" id="selected_service" name="selected_service" value=""></input>
        <div class="select-category-box-wrapper bg-gray-800">
            <h5 class="mb-5">{{ __('frontend.select_category') }}</h5>
            <nav class="select-category-list-tabs">
                <div class="nav nav-tabs pt-2" id="nav-tab" role="tablist">
                    {{-- All Category Tab --}}
                    <button class="nav-link select-catgory-box active" data-category-id="all" data-category-slug="all"
                        type="button" role="tab">
                        <span class="select-catgory-box-image flex-shrink-0">
                            <img src="{{ asset('img/frontend/nail.svg') }}" alt="Service Icon" class="img-fluid">
                        </span>
                        <span
                            class="select-catgory-box-content d-flex align-items-start justify-content-between flex-grow-1 gap-2">
                            <span class="title flex-grow-1" style="word-wrap: break-word; line-height: 1.2; text-align: left;">All</span>
                            <span class="text-body flex-shrink-0" style="white-space: nowrap;">{{ $allServicesCount }} {{ __('frontend.service') }}</span>
                        </span>
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white" />
                                <path
                                    d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z"
                                    fill="#09954D" />
                            </svg>
                        </span>
                    </button>
                    {{-- Dynamic Category Tabs --}}
                    @foreach ($categories as $category)
                        <button class="nav-link select-catgory-box" data-category-id="{{ $category->id }}"
                            data-category-slug="{{ $category->slug }}" type="button" role="tab">
                            <span class="select-catgory-box-image flex-shrink-0">
                                <img src="{{ $category->feature_image ?? asset('img/frontend/nail.svg') }}"
                                    alt="Service Icon" class="img-fluid">
                            </span>
                            <span
                                class="select-catgory-box-content d-flex align-items-start justify-content-between flex-grow-1 gap-2">
                                <span class="title flex-grow-1" style="word-wrap: break-word; line-height: 1.2; text-align: left;">{{ $category->name }}</span>
                                <span class="text-body flex-shrink-0" style="white-space: nowrap;">{{ $category->services_count ?? 0 }}
                                    {{ __('frontend.service') }}</span>
                            </span>
                            <span class="active-icon d-none">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                    viewBox="0 0 24 24" fill="none">
                                    <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white" />
                                    <path
                                        d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z"
                                        fill="#09954D" />
                                </svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </nav>
        </div>
    </div>
    <div class="col-lg-8 mt-lg-0 mt-5">
        <div class="section-title">
            <div class="">
                <span
                    class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.our_services') }}</span>
                <h4 class="title mb-0">{{ __('frontend.our_amazing_services') }}</h4>
            </div>
        </div>
        <div class="d-flex flex-sm-nowrap flex-wrap gap-3 justify-content-between gap-2 flex-wrap mb-3">
            <div class="input-group mb-0 w-auto">
                <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                <input type="search" id="serviceSearch" class="form-control p-2"
                    placeholder="{{ __('frontend.eg_facial_airbrush_makeup,') }}">
            </div>
            <div
                class="d-flex align-items-center justify-content-between column-gap-2 row-gap-1 flex-md-nowrap flex-wrap">
                <select id="sortFilter" class="form-select select2">
                    <option value="">{{ __('frontend.short_by') }}</option>
                    <option value="newest">{{ __('frontend.newest') }}</option>
                    <option value="trending">{{ __('frontend.trending') }}</option>
                </select>
                <button id="resetServiceFilters" class="btn btn-secondary">{{ __('frontend.reset') }}</button>
            </div>
        </div>
        <div class="tab-content" id="select-category-list-tabContent">
            <div id="serviceCardContainer">
                {{-- <div id="service-loader" class="d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div> --}}
                <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader list-inline">
                    @for ($i = 0; $i < 6; $i++)
                        @include('frontend::components.card.shimmer_service_card')
                    @endfor
                </div>
                <table id="service-cards-table" class="table table-responsive custom-card-table w-100">
                    <thead>
                        <tr>
                            <th style="display: none;"></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const shimmerLoader = document.querySelector('.shimmer-loader');
            // Helper to get URL parameter
            // function getUrlParameter(name) {
            //     name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            //     var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            //     var results = regex.exec(window.location.search);
            //     return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
            // }

            // On page load, check for 'category' parameter
            let categorySlug = document.getElementById('category_slug').value;
            if (categorySlug) {
                // Find the button with the matching category slug
                var found = false;
                $('#nav-tab .nav-link').each(function() {
                    var btn = $(this);
                    var btnSlug = btn.data('category-slug');
                    if (btnSlug && btnSlug.toLowerCase() === categorySlug.toLowerCase()) {
                        $('#nav-tab .nav-link').removeClass('active').find('.active-icon').addClass(
                            'd-none');
                        btn.addClass('active').find('.active-icon').removeClass('d-none');
                        found = true;
                        return false; // break loop
                    }
                });
                // If not found, default to 'All'
                if (!found) {
                    $('#nav-tab .nav-link[data-category-id="all"]').addClass('active').find('.active-icon')
                        .removeClass('d-none');
                }
            }

            // var $serviceLoader = $('#service-loader');
            var serviceTable = $('#service-cards-table').DataTable({
                processing: false,
                serverSide: true,
                ajax: {
                    url: "{{ route('frontend.services.data') }}",
                    type: "GET",
                    data: function(d) {
                        var activeTab = $('#nav-tab .nav-link.active');
                        var categoryId = activeTab.data('category-id');
                        if (categoryId && categoryId !== 'all') {
                            d.category_id = categoryId;
                        }
                        d.search = $('#serviceSearch').val();
                        d.sort_filter = $('#sortFilter').val();
                    }
                },
                columns: [{
                    data: 'card',
                    name: 'card',
                    orderable: false,
                    searchable: false
                }],
                searching: true,
                lengthChange: true,
                pageLength: 6,
                info: true,
                pagingType: 'simple_numbers',
                dom: '<"row"<"col-sm-12"t>><"row mt-2 align-items-center"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search services...",
                    paginate: {
                        next: 'Next &raquo;',
                        previous: '&laquo; Previous'
                    },
                    emptyTable: "<div class='text-center p-4'>No data available.</div>",
                    zeroRecords: "<div class='text-center p-4'>No search results found.</div>",

                },
                createdRow: function(row, data, dataIndex) {
                    $(row).children('td').addClass('p-0');
                }
            });

            // Show/hide loader during DataTables processing
            // serviceTable.on('processing.dt', function (e, settings, processing) {
            //     if (processing) {
            //         $serviceLoader.removeClass('d-none');
            //     } else {
            //         $serviceLoader.addClass('d-none');
            //     }
            // });

            // Clear any preselected checkboxes on each draw
            serviceTable.on('draw', function() {
                document.querySelectorAll('#service-cards-table input[type="checkbox"]').forEach(cb => cb.checked = false);
            });

            // Handle category tab changes
            $('#nav-tab').on('click', '.nav-link', function() {
                $('#nav-tab .nav-link').removeClass('active').find('.active-icon').addClass('d-none');
                $(this).addClass('active').find('.active-icon').removeClass('d-none');
                serviceTable.ajax.reload();
            });

            // Handle search input
            $('#serviceSearch').on('keyup', function() {
                serviceTable.ajax.reload();
            });

            // Reset datatable when search is empty
            $('#serviceSearch').on('input', function() {
                if (this.value === '') {
                    // Clear search and reset datatable
                    serviceTable.search('').draw();
                    serviceTable.ajax.reload();
                }
            });

            // Handle search clear (X button)
            $('#serviceSearch').on('search', function() {

                serviceTable.search('').draw();

            });

            // Handle sort filter changes
            $('#sortFilter').on('change', function() {
                serviceTable.ajax.reload();
            });




            // Show loader before AJAX
            serviceTable.on('preXhr.dt', function() {

                shimmerLoader.classList.remove('d-none');

            });

            // // Hide loader after data loads
            serviceTable.on('xhr.dt', function() {
                shimmerLoader.classList.add('d-none');

            });




            // Handle reset filters button click
            $('#resetServiceFilters').on('click', function() {
                // Clear search input
                $('#serviceSearch').val('');

                // Reset sort filter
                $('#sortFilter').val('').trigger('change'); // Trigger change to update select2 if used

                // Activate the 'All' category tab
                $('#nav-tab .nav-link').removeClass('active').find('.active-icon').addClass('d-none');
                $('#nav-tab .nav-link[data-category-id="all"]').addClass('active').find('.active-icon')
                    .removeClass('d-none');

                // Reload DataTable
                serviceTable.ajax.reload();
            });

            // Ensure no previous selections persist on initial load
            document.getElementById('selected_service').value = '';
            document.querySelectorAll('#service-cards-table input[type="checkbox"]').forEach(cb => cb.checked = false);
        });
    </script>
@endpush

<style>
/* Reduce spacing for search box and dropdown */
#serviceSearch {
    padding: 4px 8px !important;
    margin: 0 !important;
}

.input-group-text {
    padding: 4px 8px !important;
    margin: 0 !important;
}

.input-group {
    margin: 0 !important;
    gap: 0 !important;
}

/* Reduce Select2 dropdown spacing */
.select2-search--dropdown .select2-search__field {
    padding: 4px 8px !important;
    margin: 0 !important;
}

.select2-search--dropdown {
    padding: 8px !important;
    margin: 0 !important;
}

.select2-dropdown {
    margin: 2px 0 !important;
}

.select2-results__option {
    padding: 6px 12px !important;
    margin: 0 !important;
}

/* Reduce overall container spacing */
.d-flex.flex-sm-nowrap.flex-wrap.gap-3 {
    gap: 8px !important;
}
</style>
