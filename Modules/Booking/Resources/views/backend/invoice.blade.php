@extends('backend.layouts.app')

@section('title')
    {{ __($module_title) }}
@endsection

@section('content')
    <style>
        .alternate-list {
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
        }

        .alternate-list li:not(:last-child) {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--bs-border-color);
        }
    </style>

    <style type="text/css" media="print">
        @page :footer {
            display: none !important;
        }

        @page :header {
            display: none !important;
        }

        @page {
            size: landscape;
        }

        /* @page { margin: 0; } */

        .pr-hide {
            display: none;
        }

        button {
            display: none !important;
        }

        * {
            -webkit-print-color-adjust: none !important;
            /* Chrome, Safari 6 – 15.3, Edge */
            color-adjust: none !important;
            /* Firefox 48 – 96 */
            print-color-adjust: none !important;
            /* Firefox 97+, Safari 15.4+ */
        }

        .badge {
            font-size: 1rem;
            padding: 0;
        }
    </style>
    <div class="row pr-hide">
        <div class="col-12">
            <div class="card ">
                <div class="card-header border-bottom-0">
                    <div class="row pr-hide">
                        <div class="col-auto col-lg-12 mb-4 text-center text-lg-end">
                            <a class="btn btn-primary"
                                href="{{ route('backend.bookings.downloadinvoice', ['id' => request()->id]) }}">
                                <i class="fa-solid fa-download"></i>
                                {{ __('booking.download_invoice') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!--Main Invoice-->
        <div class="col-xl-12 order-2 order-md-2 order-lg-2 order-xl-1">
            <div class="card mb-4" id="section-1">
                <div class="card-body">
                    <!--Order Detail-->
                    <div class="row justify-content-between align-items-center g-3 mb-4">
                        <div class="col-auto flex-grow-1">
                            <img src="{{ asset(setting('logo')) }}" alt="logo" class="img-fluid" width="200">
                        </div>
                        <div class="col-auto text-end">
                            <h5 class="mb-0">{{ __('booking.download_invoice') }}
                                <span class="text-accent">
                                    @php
                                        // Decide prefix source
                                        $rawPrefix = $data->booking
                                            ? setting('inv_booking_prefix')
                                            : setting('inv_prefix');

                                        // Normalize for checks
                                        $prefixLower = strtolower(trim($rawPrefix));

                                        // Translate prefix text
                                        if (str_contains($prefixLower, 'booking')) {
                                            $finalPrefix = str_ireplace(
                                                'booking',
                                                __('booking.singular_title'),
                                                $rawPrefix,
                                            );
                                        } elseif (
                                            str_contains($prefixLower, 'inv') ||
                                            str_contains($prefixLower, 'invoice')
                                        ) {
                                            $finalPrefix = str_ireplace(
                                                ['inv', 'invoice'],
                                                __('booking.download_invoice'),
                                                $rawPrefix,
                                            );
                                        } else {
                                            $finalPrefix = $rawPrefix;
                                        }

                                        // 🔧 Fix: allow only one "# -"
                                        $finalPrefix = preg_replace('/(#\s*-\s*)+/', '# - ', trim($finalPrefix));
                                    @endphp

                                    {{ $finalPrefix . ' ' . $data->booking->resource->id }}

                                </span>
                            </h5>
                            <span class="text-muted">{{ __('booking.lbl_booking_date') }}:
                                {{ date('d M, Y', strtotime($data->booking->resource->start_date_time)) }}
                            </span>
                            <br>
                            <span class="text-muted">
                                {{ __('booking.lbl_booking_time') }}:
                                {{ date('H:i:s', strtotime($data->booking->resource->start_date_time)) }}
                            </span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-md-between justify-content-center g-3">
                        <div class="col-auto">
                            <!--Customer Detail-->
                            <div class="welcome-message">
                                <h5 class="mb-2">{{ __('booking.customer_info') }}</h6>
                                    @php
                                        $user = optional(optional($data->booking)->resource)->user;
                                        $fullName =
                                            isset($user->full_name) && trim($user->full_name) !== ''
                                                ? $user->full_name
                                                : '-';
                                        $email = isset($user->email) && trim($user->email) !== '' ? $user->email : '-';
                                        $mobile =
                                            isset($user->mobile) && trim($user->mobile) !== '' ? $user->mobile : '-';
                                    @endphp

                                    <p class="mb-0">{{ __('booking.name') }}: <strong>{{ $fullName }}</strong></p>
                                    <p class="mb-0">{{ __('booking.email') }}: <strong>{{ $email }}</strong></p>
                                    <p class="mb-0">{{ __('booking.phone') }}: <strong>{{ $mobile }}</strong></p>

                                    <!-- Payment Details Section -->
                                    @if ($data->booking_transaction)
                                        <div class="payment-details mt-4">
                                            <h6>{{ __('booking.payment_details') }}</h6>
                                            {{-- <div class="d-flex justify-content-between">
                                    <span>{{ __('Payment ID:') }}</span>
                                    <span class="fw-bold">{{ optional($data->booking_transaction)->external_transaction_id }}</span>
                                </div> --}}
                                            <div class="d-flex justify-content-between">
                                                <span>{{ __('booking.payment_method') }}:</span>
                                                <span class="fw-bold">
                                                    <span class="badge bg-primary">
                                                        @php
                                                            $transactionType = optional($data->booking_transaction)
                                                                ->transaction_type;
                                                            $paymentMethodLabel = $transactionType;

                                                            // Translate common payment methods
                                                            if ($transactionType === 'upi') {
                                                                $paymentMethodLabel = __('booking.payment_method_upi');
                                                            } elseif ($transactionType === 'cash') {
                                                                $paymentMethodLabel = __('booking.payment_method_cash');
                                                            } elseif ($transactionType === 'card') {
                                                                $paymentMethodLabel = __('booking.payment_method_card');
                                                            } elseif ($transactionType === 'stripe') {
                                                                $paymentMethodLabel = __(
                                                                    'booking.payment_method_stripe',
                                                                );
                                                            } elseif ($transactionType === 'razorpay') {
                                                                $paymentMethodLabel = __(
                                                                    'booking.payment_method_razorpay',
                                                                );
                                                            } else {
                                                                $paymentMethodLabel = ucwords(
                                                                    str_replace('_', ' ', $transactionType),
                                                                );
                                                            }
                                                        @endphp
                                                        {{ $paymentMethodLabel }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    @endif

                            </div>
                        </div>
                        <div class="col-auto">
                            <!-- Employee Detail -->
                            @php
                                $employee = optional(optional($data->booking->resource)->services->first())->employee;
                                $employeeName =
                                    isset($employee->full_name) && trim($employee->full_name) !== ''
                                        ? $employee->full_name
                                        : '-';
                            @endphp
                            <div class="employee-details">
                                <h5 class="mb-2">{{ __('booking.lbl_staff_name') }}</h5>
                                <p class="mb-0">{{ __('booking.name') }}: <strong>{{ $employeeName }}</strong></p>
                            </div>
                            @php
                                $branch = optional($data->booking->resource->branch);
                            @endphp
                            @if ($branch)
                                <div class="branch-details mt-3">
                                    <h5 class="mb-2">{{ __('booking.branch_details') }}</h5>
                                    <p class="mb-0">{{ __('booking.branch_name') }}:
                                        <strong>{{ $branch->name }}</strong></p>
                                    @if ($branch->contact_number)
                                        <p class="mb-0">{{ __('booking.contact_number') }}:
                                            <strong>{{ $branch->contact_number }}</strong></p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="col-auto">
                            @php
                                $branch = optional($data->booking->resource->branch);
                                $billingAddress = $branch->address;
                                $productPrice = 0;
                                $package_price = 0;
                            @endphp
                            @if ($branch)
                                <div class="shipping-address d-flex gap-3 mb-3" style="min-width: 500px">
                                    <!-- <div class="w-25">
                                            <h5 class="mb-2">{{ __('booking.branch_name') }}</h5>
                                            <p class="mb-0">{{ $branch->name }}</p>
                                        </div> -->
                                    <div class="w-25">
                                        <h5 class="mb-2">{{ __('booking.billing_address') }}</h5>
                                        <p class="mb-0 text-wrap">
                                            {{ optional($billingAddress)->address_line_1 }},
                                            {{ optional($billingAddress->city_data)->name }},
                                            {{ optional($billingAddress->state_data)->name }},
                                            {{ optional($billingAddress->country_data)->name }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!--order details-->
                <table class="table table-bordered border-top" data-use-parent-width="true">
                    <thead>
                        <tr class="text-center">
                            <th>{{ __('booking.services') }}/{{ __('booking.products') }}</th>
                            <th>{{ __('booking.unit_price') }}</th>
                            <th>{{ __('booking.qty') }}</th>
                            <th class="text-end">{{ __('booking.total_price') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($data->booking->resource->services as $key => $value)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <h6 class="fs-sm mb-0">
                                                {{ $value->service_name }}
                                            </h6>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <span class="fw-bold">{{ \Currency::format($value->service_price) }}
                                    </span>
                                </td>

                                <td class="text-end">1</td>

                                <td class=" text-end">
                                    <span class="text-accent fw-bold">{{ \Currency::format($value->service_price) }}
                                    </span>

                                </td>

                            </tr>
                        @endforeach
                        @foreach ($data->booking->resource->products as $key => $value)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <h6 class="fs-sm mb-0">
                                                {{ $value->product_name }}
                                            </h6>
                                        </div>
                                    </div>
                                </td>
                                @php
                                    $price = $value->product_price;
                                    $delPrice = false;
                                    $discountType = $value->discount_type;
                                    $discountValue = $value->discount_value . ($discountType == 'percent' ? '%' : '');
                                    if ($price != $value->discounted_price) {
                                        $delPrice = $price;
                                        $price = $value->discounted_price;
                                    }
                                    $productPrice = $price * $value->product_qty + $productPrice;
                                @endphp
                                <td class="text-end">
                                    <span class="fw-bold">{{ \Currency::format($price) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ $value->product_qty }}</td>

                                <td class=" text-end">
                                    <span class="text-accent fw-bold">{{ \Currency::format($price * $value->product_qty) }}
                                    </span>
                                </td>

                            </tr>
                        @endforeach
                        @foreach ($data->booking->resource->packages as $key => $value)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ms-2">
                                            <h6 class="fs-sm mb-0">
                                                {{ $value->name }}
                                            </h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="">
                                    <span class="fw-bold">{{ \Currency::format($value->package_price) }}
                                    </span>
                                </td>

                                <td class="fw-bold">1</td>

                                <td class=" text-end">
                                    <span class="text-accent fw-bold">{{ \Currency::format($value->package_price) }}
                                    </span>

                                </td>

                            </tr>
                        @endforeach


                    </tbody>
                    <tfoot class="text-end">
                        @if ($data->coupon_discount > 0)
                            <tr>
                                <td colspan="3">
                                    <h6 class="d-inline-block me-3">{{ __('booking.coupondiscount') }}: </h6>
                                </td>
                                <td width="10%" class="text-end">
                                    <strong>{{ \Currency::format($data->coupon_discount) }}</strong>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td colspan="3">
                                <h6 class="d-inline-block me-3">{{ __('booking.sub_total') }}: </h6>
                            </td>
                            <td width="10%">
                                <strong>{{ \Currency::format($data->services_total_amount + $productPrice + $data->package_amount - $data->coupon_discount) }}</strong>
                            </td>
                        </tr>
                        <tr>

                            <td colspan="3">
                                <h6 class="d-inline-block me-3">{{ __('booking.tips') }}: </h6>
                            </td>
                            <td width="10%" class="text-end">
                                <strong>
                                    {{ \Currency::format(optional($data->booking_transaction)->tip_amount) }}
                                    ({{ optional($data->booking_transaction)->tip_type === 'percent' ? __('messages.percent') : __('messages.fixed') }})
                                </strong> @php
                                    // Decode the tax_percentage field
                                    $taxDetails = $data->booking_transaction['tax_percentage'] ?? [];
                                    $serviceTotalAmount =
                                        $data->services_total_amount +
                                        $productPrice +
                                        $data->package_amount -
                                        $data->coupon_discount;
                                @endphp

                                @foreach ($taxDetails as $tax)
                                    @php
                                        $taxAmount = 0;
                                        if ($tax['type'] == 'percent') {
                                            $taxAmount = ($serviceTotalAmount * $tax['percent']) / 100;
                                        } else {
                                            $taxAmount = $tax['tax_amount'] ?? $tax['amount'];
                                        }
                                    @endphp
                        <tr>
                            <td colspan="3">
                                <h6 class="d-inline-block me-3">
                                    @php
                                        $taxName = strtolower(trim($tax['name']));
                                        $translatedTaxName = $tax['name'];

                                        // Map common tax names to translations
                                        if ($taxName === 'gst' || $taxName === 'g.s.t.' || $taxName === 'g s t') {
                                            $translatedTaxName = __('frontend.gst');
                                        } elseif (
                                            $taxName === 'service tax' ||
                                            $taxName === 'service_tax' ||
                                            $taxName === 'servicetax'
                                        ) {
                                            $translatedTaxName = __('frontend.service_tax');
                                        } elseif (
                                            $taxName === 'tax' ||
                                            $taxName === 'vat' ||
                                            $taxName === 'v.a.t.' ||
                                            $taxName === 'v a t'
                                        ) {
                                            $translatedTaxName = __('booking.tax');
                                        }
                                    @endphp
                                    {{ $translatedTaxName }}:
                                </h6>
                            </td>
                            <td width="10%" class="text-end">
                                <strong>
                                    {{ \Currency::format($taxAmount) }}
                                    ({{ $tax['type'] === 'percent' ? __('messages.percent') : __('messages.fixed') }})
                                </strong>
                            </td>
                        </tr>
                        @endforeach

                        <tr>
                        <tr>
                            <td colspan="3">
                                <h6 class="d-inline-block me-3">{{ __('booking.grand_total') }} </h6>
                            </td>
                            <td width="10%" class="text-end"><strong
                                    class="text-accent">{{ \Currency::format($data->grand_total) }}</strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!--Note-->
                <div class="card-body">
                    <div class="card-footer border-top-0 px-4 py-4 rounded bg-soft-gray border border-2">
                        <p class="mb-0">{{ setting('spacial_note') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('after-scripts')
    <script>
        function invoicePrint() {
            window.print()
        }

        function updateStatusAjax(__this, url) {
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    order_id: {{ $data->booking->resource->id }},
                    status: __this.val(),
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if (res.status) {
                        window.successSnackbar(res.message)
                        setTimeout(() => {
                            location.reload()
                        }, 100);
                    }
                }
            });
        }
        $('[name="payment_status"]').on('change', function() {
            if ($(this).val() !== '') {
                updateStatusAjax($(this), "{{ route('backend.orders.update_payment_status') }}")
            }
        })

        $('[name="delivery_status"]').on('change', function() {
            if ($(this).val() !== '') {
                updateStatusAjax($(this), "{{ route('backend.orders.update_delivery_status') }}")
            }
        })
    </script>
@endpush
