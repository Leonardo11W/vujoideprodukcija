<div class="section-spacing-inner-pages">
    <div class="container">
        <style>
            /* Hide horizontal scrollbar on orders page */
            html,
            body {
                overflow-x: hidden;
            }

            #orderCardContainer {
                overflow-x: hidden;
            }
        </style>
        <div class="myorder-box d-flex align-items-start flex-wrap flex-md-nowrap justify-content-between">
            <div class="d-flex flex-column w-100">
                <div
                    class="heading-box mb-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <h5 class="font-size-21-3 mb-0">{{ __('frontend.my_orders') }}</h5>
                    <div class="feature-box d-flex flex-sm-nowrap flex-wrap gap-3">
                        <div class="input-group mb-0">
                            <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                            <input type="search" class="form-control p-2" id="orderSearchInput"
                                placeholder="{{ __('frontend.search_orders') }}"
                                aria-label="{{ __('frontend.search_orders') }}">
                        </div>
                        <div class="filter-container-box position-relative flex-shrink-0 max-content">
                            <select class="form-select select2 font-size-12 border-0" id="orderStatusFilter">
                                <option value="">{{ __('frontend.all_orders') }}</option>
                                <option value="order_placed">{{ __('frontend.order_placed') }}</option>
                                <option value="processing">{{ __('frontend.processings') }}</option>
                                <option value="delivered">{{ __('frontend.delivered') }}</option>
                                <option value="cancelled">{{ __('frontend.cancelled') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="order-card-container table-responsive d-flex flex-column gap-5" id="orderCardContainer">

                    <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader  list-inline">
                        @for ($i = 0; $i < 3; $i++)
                            @include('frontend::components.card.shimmer_order_card')
                        @endfor
                    </div>


                    <table id="orders-table" class="table">
                        <thead style="display:none;">
                            <tr>
                                <th>Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade rating-modal" id="review-product" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content bg-gray-900 rounded">
            <div class="modal-body modal-body-inner rate-us-modal">
                <form id="reviewForm">
                    <div class="rate-box">
                        <h5 class="font-size-21-3 mb-0 text-center">{{ __('frontend.rate_our_product_now') }}</h5>
                        <p class="mb-0 mt-2 font-size-14 text-center">
                            {{ __('frontend.your_honest_feedback_helps_us_improve_and_serve_you_better') }}</p>

                        <div class="mt-5 pt-2">
                            <div class="form-group mb-4">
                                <label for="" class="form-label">{{ __('frontend.your_rating') }}</label>
                                <div class="bg-gray-800 form-control">
                                    <ul
                                        class="list-inline m-0 p-0 d-flex align-items-center justify-content-start gap-1 rating-list">
                                        <li data-value="1" class="star selected">
                                            <span class="text-warning icon">
                                                <i class="ph-fill ph-star icon-fill"></i>
                                                <i class="ph ph-star icon-normal"></i>
                                            </span>
                                        </li>
                                        <li data-value="2" class="star selected">
                                            <span class="text-warning icon">
                                                <i class="ph-fill ph-star icon-fill"></i>
                                                <i class="ph ph-star icon-normal"></i>
                                            </span>
                                        </li>
                                        <li data-value="3" class="star selected">
                                            <span class="text-warning icon">
                                                <i class="ph-fill ph-star icon-fill"></i>
                                                <i class="ph ph-star icon-normal"></i>
                                            </span>
                                        </li>
                                        <li data-value="4" class="star">
                                            <span class="text-warning icon">
                                                <i class="ph-fill ph-star icon-fill"></i>
                                                <i class="ph ph-star icon-normal"></i>
                                            </span>
                                        </li>
                                        <li data-value="5" class="star">
                                            <span class="text-warning icon">
                                                <i class="ph-fill ph-star icon-fill"></i>
                                                <i class="ph ph-star icon-normal"></i>
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for=""
                                    class="form-label">{{ __('frontend.enter_your_feedback') }}</label>
                                <textarea class="form-control bg-gray-800"
                                    placeholder="{{ __('frontend.Share_your_experience!_Your_feedback_helps_others_make_informed_decisions_about_their_healthcare') }}"
                                    rows="3" id="reviewTextarea"></textarea>
                            </div>

                            <div
                                class="mt-5 pt-3 d-flex align-items-center justify-content-center row-gap-3 column-gap-4 flex-wrap">
                                <button class="btn btn-secondary"
                                    data-bs-dismiss="modal">{{ __('frontend.cancel') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('frontend.submit') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function() {
            const shimmerLoader = document.querySelector('.shimmer-loader');
            const table = $('#orders-table').DataTable({
                processing: false,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: "{{ route('myorder.data') }}",
                    data: function(d) {
                        d.status = $('#orderStatusFilter').val();
                    }
                },
                columns: [{
                    data: 'card',
                    name: 'card',
                    orderable: false,
                    searchable: false
                }],
                pageLength: 12,
                searching: true,
                lengthChange: true,
                pagingType: 'simple_numbers',
                autoWidth: false,
                dom: 'rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
                language: {
                    searchPlaceholder: 'Search orders...',
                    search: '',
                    emptyTable: "<div class='text-center p-4'>{{ __('frontend.no_orders_found') }}</div>",
                    zeroRecords: "<div class='text-center p-4'>{{ __('frontend.no_matching_orders_found') }}</div>",

                },
                drawCallback: function(settings) {
                    try {
                        const data = this.api().rows().data();
                        const $container = $('#orderCardContainer');
                        $container.empty();

                        console.log('DrawCallback - Data received:', data.length, 'items');

                        if (data.length === 0) {
                            shimmerLoader.classList.add('d-none');
                            $container.html(
                                '<div class="text-center p-4">{{ __('frontend.no_matching_orders_found') }}</div>'
                            );
                            return;
                        }

                        // Create full page layout - 1 order per row
                        for (let i = 0; i < data.length; i++) {
                            const row = $(
                                '<div class="row mb-4"></div>'
                            );
                            if (data[i] && data[i].card) {
                                console.log('Rendering card:', i, data[i].card.substring(0, 100) +
                                    '...');
                                row.append(`<div class="col-12">${data[i].card}</div>`);
                            }
                            $container.append(row);
                        }

                        shimmerLoader.classList.add('d-none');
                        console.log('DrawCallback - Completed rendering');
                    } catch (error) {
                        console.error('DrawCallback Error:', error);
                        $('#orderCardContainer').html(
                            '<div class="text-center p-4 text-danger">Error displaying orders</div>'
                        );
                        shimmerLoader.classList.add('d-none');
                    }
                }
            });

            table.on('preXhr.dt', function() {
                $('#orderCardContainer').empty();
                shimmerLoader.classList.remove('d-none');

            });

            // // Hide loader after data loads
            table.on('xhr.dt', function() {
                shimmerLoader.classList.add('d-none');

            });



            // Handle search input
            $('#orderSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            // Handle status filter
            $('#orderStatusFilter').on('change', function() {
                table.ajax.reload();
            });

            // Reset datatable when search is empty
            $('#orderSearchInput').on('input', function() {
                if (this.value === '') {
                    // Clear search and reset datatable
                    table.search('').draw();
                    table.ajax.reload();
                }
            });

            // Handle search clear (X button)
            $('#orderSearchInput').on('search', function() {
                table.search('').draw();
                table.ajax.reload();
            });
        });
    </script>
@endpush
