<!-- History Section -->
<div class="history-section">
    <h6 class="mb-3 title-text font-size-21-3">{{ __('frontend.history') }}</h6>

    <!-- Shimmer Loader -->

    <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader list-inline">
        @for ($i = 0; $i < 3; $i++)
            @include('frontend::components.card.shimmer_history_card')
        @endfor
    </div>


    <!-- Table -->
    <div class="table-responsive">
        <table id="wallet-history-table" class="table mb-0 custom-table rounded">
            <thead>
                <tr>
                    <th>{{ __('frontend.date_and_time') }}</th>
                    <th>{{ __('frontend.description') }}</th>
                    <th>{{ __('frontend.amount') }}</th>
                    <th>{{ __('frontend.status') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@push('scripts')
    <script>
        const $walletTable = $('#wallet-history-table');
        const shimmerLoaderWallet = document.querySelector('.shimmer-loader');

        const walletTable = $walletTable.DataTable({
            processing: false,
            serverSide: true,
            ajax: "{{ route('wallet.history.data') }}", // Update with correct route
            columns: [{
                    data: 'date_time',
                    name: 'date_time'
                },
                {
                    data: 'transaction_type',
                    name: 'transaction_type'
                },
                {
                    data: 'amount',
                    name: 'amount'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                }
            ],
            ordering: false,
            pageLength: 6,
            searching: true,
            lengthChange: false,
            autoWidth: false,
            responsive: true,
            pagingType: 'simple_numbers',
            dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center"ip>>',
            language: {
                searchPlaceholder: '{{ __("frontend.search_transactions") }}',
                search: '',
                emptyTable: "{{ __('frontend.no_transactions_found') }}",
                zeroRecords: "{{ __('frontend.no_matching_transactions') }}",
            }
        });

        $('#walletSearchInput').on('keyup', function() {
            walletTable.search(this.value).draw();
        });

        walletTable.on('preXhr.dt', function() {

            shimmerLoaderWallet.classList.remove('d-none');
        });

        walletTable.on('xhr.dt', function() {
            shimmerLoaderWallet.classList.add('d-none');
        });
    </script>
@endpush
