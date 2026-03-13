@extends('frontend::layouts.master')

@section('content')
    <x-breadcrumb :title="__('frontend.wishlist')" />

    <div class="section-spacing-inner-pages">
        <div class="container">
            <div class="section-title d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="font-size-21-3 mb-3">{{ __('frontend.My_Wishlist') }}</h5>
                </div>
                <div>
                    <div class="input-group mb-0">
                        <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="search" class="form-control p-2" id="wishlistSearchInput"
                            placeholder='{{ __('frontend.search_wishlist') }}'>
                    </div>
                </div>
            </div>

            <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader list-inline ">
                @for ($i = 0; $i < 5; $i++)
                    @include('frontend::components.card.shimmer_whishlist_card')
                @endfor
            </div>
            <div class="table-responsive">
                <table id="wishlist-table" class="table custom-table-bg w-100">
                    <thead>
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">{{ __('frontend.product_name') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Add to Cart function (from product_card)
        function addToCart(productId, qty = 1) {
            const token = $('meta[name="csrf-token"]').attr('content');
            if (!token) {
                toastr.error('Security token missing. Please refresh the page.');
                return;
            }
            $.ajax({
                url: '{{ route('cart.add') }}',
                type: 'POST',
                data: {
                    product_id: productId,
                    qty: qty,
                    _token: token
                },
                success: function(response) {
                    if (response.status) {
                        $('#cartCount').text(response.cart_count);
                        toastr.success('{{ __('frontend.product_added_to_cart_successfully') }}');
                        $(`#addToCartBtn_${productId}`).hide();
                        $(`#removeFromCartBtn_${productId}`).show();
                    } else if (response.redirect) {
                        $('#loginModal').modal('show');
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(jqXHR) {
                    if (jqXHR.status === 401) {
                        $('#loginModal').modal('show');
                    } else {
                        toastr.error('{{ __('frontend.failed_to_add_item_to_cart_please_try_again') }}');
                    }
                }
            });
        }

        // Remove from Cart function (from product_card)
        function removeFromCart(productId) {
            const token = $('meta[name="csrf-token"]').attr('content');
            if (!token) {
                toastr.error('Security token missing. Please refresh the page.');
                return;
            }
            $.ajax({
                url: '{{ route('cart.remove') }}',
                type: 'POST',
                data: {
                    product_id: productId,
                    _token: token
                },
                success: function(response) {
                    if (response.status) {
                        $('#cartCount').text(response.cart_count);
                        toastr.success('{{ __('frontend.product_removed_from_cart_successfully') }}');
                        $(`#removeFromCartBtn_${productId}`).hide();
                        $(`#addToCartBtn_${productId}`).show();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(jqXHR) {
                    toastr.error('{{ __('frontend.failed_to_remove_item_from_cart_please_try_again') }}');
                }
            });
        }

        $(document).ready(function() {
            const $table = $('#wishlist-table');
            const shimmerLoader = document.querySelector('.shimmer-loader');

            // Show the table before initializing DataTables
            $table.removeClass('d-none');

            const table = $table.DataTable({
                processing: false,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: "{{ route('wishlist.data') }}",
                    dataSrc: function(json) {
                        // Hide shimmer loader when data is loaded
                        shimmerLoader.classList.add('d-none');
                        return json.data;
                    },
                    error: function() {
                        // Hide shimmer loader on error
                        shimmerLoader.classList.add('d-none');
                    }
                },
                columns: [{
                        data: 'remove',
                        name: 'remove',
                        orderable: false,
                        searchable: false,
                        width: '2%',
                    },
                    {
                        data: 'product_name',
                        name: 'product_name',

                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '20%',
                    }
                ],
                ordering: false, // Disable sorting on all columns
                pageLength: 8,
                searching: true,
                lengthChange: false,
                pagingType: 'simple_numbers',
                dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center"ip>>',
                language: {
                    searchPlaceholder: '{{ __("frontend.search_wishlist_placeholder") }}',
                    search: '',
                    emptyTable: '<div class="text-center p-4"><p class="mb-0">{{ __('frontend.no_wishlist_items_found') }}</p></div>',
                    zeroRecords: '<div class="text-center p-4"><p class="mb-0">{{ __('frontend.no_matching_wishlist_items') }}</p></div>',
                },
                drawCallback: function(settings) {
                    // Hide the table if there's no data
                    if (this.api().data().count() === 0) {
                        $table.removeClass('d-none');
                    }
                }
            });

            $('#wishlistSearchInput').on('keyup', function() {
                table.search(this.value).draw();
            });

            table.on('preXhr.dt', function() {

                shimmerLoader.classList.remove('d-none');

            });

            // // Hide loader after data loads
            table.on('xhr.dt', function() {
                shimmerLoader.classList.add('d-none');

            });

            $('#wishlistSearchInput').on('input', function() {
                if (this.value === '') {
                    // Clear search and reset datatable
                    table.search('').draw();

                }
            });

            // SweetAlert2 confirmation for removing from wishlist
            $(document).on('click', '.remove-from-wishlist-btn', function(e) {


                try {
                    e.preventDefault();
                    const productId = $(this).data('product-id');

                    Swal.fire({
                        title: '{{ __('frontend.are_you_sure') }}',
                        text: '{{ __('frontend.do_you_want_to_remove_this_product_from_your_wishlist') }}',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '{{ __('frontend.yes_delete_it') }}',
                        cancelButtonText: '{{ __('frontend.cancel') }}',
                        reverseButtons: true,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '{{ route('wishlist.remove') }}',
                                type: 'POST',
                                data: {
                                    product_id: productId,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(response) {
                                    if (response.status) {
                                        Swal.fire({
                                            title: '{{ __('frontend.removed') }}',
                                            text: '{{ __('frontend.product_removed_from_wishlist') }}',
                                            icon: 'success',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            buttonsStyling: false,
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            }
                                        });
                                        table.ajax.reload();
                                    } else {
                                        Swal.fire({
                                            title: '{{ __('frontend.error') }}',
                                            text: response.message ||
                                                '{{ __('frontend.could_not_remove_from_wishlist') }}',
                                            icon: 'error',
                                            confirmButtonText: '{{ __('frontend.ok') }}',
                                            buttonsStyling: false,
                                            customClass: {
                                                confirmButton: 'btn btn-primary'
                                            }
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: '{{ __('frontend.error') }}',
                                        text: '{{ __('frontend.failed_to_remove_from_wishlist_please_try_again') }}',
                                        icon: 'error',
                                        confirmButtonText: '{{ __('frontend.ok') }}',
                                        buttonsStyling: false,
                                        customClass: {
                                            confirmButton: 'btn btn-primary'
                                        }
                                    });
                                }
                            });
                        }
                    });
                } catch (err) {
                    console.error('Error in remove-from-wishlist-btn handler:', err);
                }
            });
        });
    </script>
@endpush
