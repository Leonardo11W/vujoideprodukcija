@extends('backend.layouts.app', ['isBanner' => false])

@section('title') {{ __('dashboard.title') }} @endsection

@section('content')
<div class="row admin-dashboard">
  <div class="col-md-12">
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <h3 class="dashboard-section-title">{{ __('dashboard.lbl_performance') }}</h3>
      <form action="{{ route('backend.home') }}" class="d-flex align-items-center gap-2">
        <div class="form-group my-0">
          <input type="text" name="date_range" value="{{ $date_range }}" class="form-control form-control-sm dashboard-date-range"
            placeholder="{{ __('dashboard.date_range_placeholder') }}" readonly="readonly">
        </div>
        <button type="submit" name="action" value="filter" class="btn btn-primary btn-sm" data-bs-toggle="tooltip"
          data-bs-title="{{ __('messages.submit_date_filter') }}">{{ __('dashboard.lbl_submit') }}</button>
          {{-- <button type="submit" name="action" value="reset" class="btn btn-secondary btn-icon"
            data-bs-toggle="tooltip" data-bs-title="Reset Filter"><i class="fa-solid fa-clock-rotate-left"></i></button>
          --}}
      </form>
    </div>
    <div class="admin-dashboard-upcoming-wrap mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4 class="card-title mb-0">{{ __('dashboard.lbl_upcoming_appointment') }}</h4>
        <a href="{{ route('backend.bookings.index') }}">{{ __('messages.view_all') }}</a>
      </div>
      <div class="card">
        <div
          class="card-body py-0 upcoming-appointments admin-dashboard-upcoming {{ count($data['upcomming_appointments']) > 0 ? '' : 'iq-upcomming' }}">
          <ul class="list-group list-group-flush ">
            @forelse ($data['upcomming_appointments'] as $booking)
            @include('backend.partials.dashboard-upcoming-item', ['booking' => $booking])
            @empty
            <p class="text-center">{{ __('dashboard.lbl_upcoming_bookings') }}</p>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
    <div class="row g-3 mb-2">
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards services h-100"
          style="background-image: url({{ asset('img/dashboard/services.svg') }})">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('messages.total_revenue') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_revenue'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_tot_revenue') }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards appointments h-100"
          style="background-image: url({{ asset('img/dashboard/appointment.svg') }})">
          <a href="{{ route('backend.bookings.datatable_view') }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('messages.total_appointment_count') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_appointments'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_appointment') }}</p>
          </a>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards revenue h-100"
          style="background-image: url({{ asset('img/dashboard/revenue.svg') }})">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('dashboard.avg_revenue_per_booking_tooltip') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['avg_revenue_per_booking'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_avg_revenue_per_booking') }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards appointments h-100"
          style="background-image: url({{ asset('img/dashboard/appointment.svg') }})">
          <a href="{{ route('backend.bookings.datatable_view', ['status' => 'cancelled']) }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('dashboard.cancelled_bookings_tooltip') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['cancelled_bookings_count'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_cancelled_bookings') }}</p>
          </a>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards services h-100"
          style="background-image: url({{ asset('img/dashboard/services.svg') }})">
          <div class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('dashboard.total_discount_tooltip') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_discount_amount'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_total_discounts') }}</p>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards new-customer h-100"
          style="background-image: url({{ asset('img/dashboard/new-users.svg') }})">
          <a href="{{ route('backend.customers.index') }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('messages.total_new_customers') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_new_customers'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_customers') }}</p>
          </a>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards new-customer h-100"
          style="background-image: url({{ asset('img/dashboard/new-users.svg') }})">
          <a href="{{ route('backend.orders.index') }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('messages.total_new_sales') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_orders'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_orders') }}</p>
          </a>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards new-customer h-100"
          style="background-image: url({{ asset('img/dashboard/new-users.svg') }})">
          <a href="{{ route('backend.reports.order-report') }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('messages.total_product_revenue') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['product_sales'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_product_sales') }}</p>
          </a>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card dashboard-cards new-customer h-100"
          style="background-image: url({{ asset('img/dashboard/new-users.svg') }})">
          <a href="{{ route('backend.customers.index') }}" class="card-body">
            <div class="d-flex align-items-start justify-content-end mb-1">
              <i class="fa-solid fa-circle-info" data-bs-toggle="tooltip" data-bs-title="{{ __('dashboard.returning_customers_tooltip_all_time') }}"></i>
            </div>
            <h3 class="mb-2">{{ $data['total_returning_customers'] }}</h3>
            <p class="mb-0">{{ __('dashboard.lbl_returning_customers') }}</p>
          </a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div class="col-lg-12">
      <div class="card card-block card-stretch card-height">
        <div class="card-body">
          <div id="chart-01"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card card-block card-stretch card-height">
      <div class="card-body">
        <div class=" d-flex justify-content-between  flex-wrap">
          <h4 class="card-title">{{ __('dashboard.lbl_appointment_revenue') }} </h4>
        </div>
        <div id="chart-02"></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card card-block card-stretch card-height">
      <div class="card-header">
        <h4 class="card-title">{{ __('dashboard.lbl_top_services') }} </h4>
      </div>
      <div class="card-body">
        <div class="table-responsive border rounded admin-top-services-table">
          <table class="table table-lg m-0 table-striped">
            <thead>
              <tr class="text-white bg-primary">
                <th scope="col">{{ __('messages.service') }}</th>
                <th scope="col">{{ __('messages.total_count') }}</th>
                <th scope="col">{{ __('messages.total_amount') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($data['top_services'] as $service)
              <tr>
                <td>{{ $service->service->name ?? null }}</td>
                <td>{{ $service->total_service_count }}</td>
                <td>{{ Currency::format($service->total_service_price) }}</td>
              </tr>
              @empty
              <tr>
                <td class="text-center" colspan="3">{{ __('messages.top_service_notfound') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('after-styles')
    <style>
        #chart-01 {
            height: 28.5rem;
        }

        #chart-02 {
            height: 22.5rem;
        }

        .list-group {
            --bs-list-group-item-padding-y: 1.5rem;
            --bs-list-group-color: inherit !important;
        }

        .date-calender {
            display: flex;
            justify-content: space-between;
        }

        .date-calender .date {
            width: 12%;
            display: flex;
            align-items: center;
            flex-direction: column
        }

        .upcoming-appointments {
            min-height: 28rem;
            max-height: 28rem;
            overflow-y: scroll;


        }

        .admin-dashboard-upcoming-wrap .admin-dashboard-upcoming.upcoming-appointments {
            min-height: 12rem;
            max-height: 18rem;
        }

        .admin-dashboard-upcoming-wrap .iq-upcomming.admin-dashboard-upcoming {
            min-height: 8rem;
            max-height: 18rem;
        }

        .iq-upcomming {
            display: flex !important;
            justify-content: center;
            align-items: center;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.40.0/apexcharts.min.css"
        integrity="sha512-tJYqW5NWrT0JEkWYxrI4IK2jvT7PAiOwElIGTjALSyr8ZrilUQf+gjw2z6woWGSZqeXASyBXUr+WbtqiQgxUYg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush
@push('after-scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/hr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/apexcharts/3.40.0/apexcharts.min.js"
        integrity="sha512-Kr1p/vGF2i84dZQTkoYZ2do8xHRaiqIa7ysnDugwoOcG0SbIx98erNekP/qms/hBDiBxj336//77d0dv53Jmew=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        $(document).ready(function() {
            // Get currency decimal setting
            const DEFAULT_CURRENCY = JSON.parse(@json(json_encode(Currency::getDefaultCurrency(true))))
            const currencyDecimalPlaces = DEFAULT_CURRENCY.no_of_decimal || 2;

            /**
             * Format number as currency using the same rules
             * that are used on the backend (symbol, position,
             * thousand/decimal separators, decimals).
             */
            function formatCurrencyValue(val) {
                if (val === null || val === undefined || isNaN(val)) {
                    return val;
                }

                const noOfDecimal = parseInt(DEFAULT_CURRENCY.no_of_decimal ?? 2, 10);
                const decimalSeparator = DEFAULT_CURRENCY.decimal_separator || '.';
                const thousandSeparator = DEFAULT_CURRENCY.thousand_separator || ',';
                const currencyPosition = DEFAULT_CURRENCY.currency_position || 'left';
                const currencySymbol = DEFAULT_CURRENCY.currency_symbol || '';

                let number = parseFloat(val).toFixed(noOfDecimal);
                let [integerPart, decimalPart] = number.split('.');

                // Add thousand separators
                integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);

                let formattedNumber = integerPart;
                if (noOfDecimal > 0 && decimalPart) {
                    formattedNumber += decimalSeparator + decimalPart;
                }

                // Apply currency position
                if (currencyPosition === 'left' || currencyPosition === 'left_with_space') {
                    return currencySymbol + (currencyPosition === 'left_with_space' ? ' ' : '') + formattedNumber;
                }

                if (currencyPosition === 'right' || currencyPosition === 'right_with_space') {
                    return formattedNumber + (currencyPosition === 'right_with_space' ? ' ' : '') + currencySymbol;
                }

                return formattedNumber;
            }

            const upcomingEl = document.querySelector('.upcoming-appointments');
            if (upcomingEl && typeof Scrollbar !== 'undefined') {
                Scrollbar.init(upcomingEl, {
                    continuousScrolling: false,
                    alwaysShowTracks: false
                });
            }
            const range_flatpicker = document.querySelectorAll('.dashboard-date-range')
            const dashboardFpOpts = {
                mode: "range",
                dateFormat: "Y-m-d",
                altInput: true,
                altFormat: "d.m.Y",
            };
            @if(in_array(app()->getLocale(), ['bs', 'hr', 'sr'], true))
            if (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.hr) {
                dashboardFpOpts.locale = flatpickr.l10ns.hr;
            }
            @endif
            Array.from(range_flatpicker, (elem) => {
                if (typeof flatpickr !== typeof undefined) {
                    flatpickr(elem, dashboardFpOpts)
                }
            })
            if (document.querySelectorAll("#chart-01").length) {
                const variableColors = (typeof IQUtils !== 'undefined' && IQUtils.getVariableColor) ? IQUtils.getVariableColor() : { primary: '#862e6f', secondary: '#19235a' };
                const colors = [variableColors.primary, variableColors.secondary];
                const options = {
                    series: [{
                        name: @json(__('dashboard.chart_series_service_revenue')),
                        data: @json($data['revenue_chart']['total_price']),
                    }, ],
                    colors: colors,
                    chart: {
                        height: "100%",
                        type: "line",
                        toolbar: {
                            show: false,
                        },
                    },
                    stroke: {
                        width: 3,
                        curve: 'smooth',
                        lineCap: 'butt',
                    },
                    grid: {
                        show: true,
                        strokeDashArray: 7,
                    },
                    markers: {
                        size: 6,
                        colors: "#FFFFFF",
                        strokeColors: colors,
                        strokeWidth: 2,
                        strokeOpacity: 0.9,
                        strokeDashArray: 0,
                        fillOpacity: 0,
                        shape: "circle",
                        radius: 2,
                        offsetX: 0,
                        offsetY: 0,
                    },
                    xaxis: {
                        categories: @json($data['revenue_chart']['xaxis']),
                        labels: {
                            minHeight: 20,
                            maxHeight: 20,
                        },
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false,
                        },
                        tooltip: {
                            enabled: false,
                        },
                    },
                    yaxis: {
                        labels: {
                            minWidth: 19,
                            maxWidth: 19,
                            formatter: function (val) {
                                return formatCurrencyValue(val);
                            }
                        },
                        tickAmount: 3
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return formatCurrencyValue(val);
                            }
                        }
                    }
                };

                const chart = new ApexCharts(
                    document.querySelector("#chart-01"),
                    options
                );
                chart.render();
            }
            if (document.querySelectorAll('#chart-02').length) {
                const variableColors = (typeof IQUtils !== 'undefined' && IQUtils.getVariableColor) ? IQUtils.getVariableColor() : { primary: '#862e6f', secondary: '#19235a' };
                const colors = [variableColors.secondary, variableColors.primary];
                const options = {
                    series: [{
                            name: @json(__('dashboard.chart_series_service_revenue')),
                            type: 'line',
                            data: @json($data['revenue_chart']['total_price']),
                        },
                        {
                            name: @json(__('dashboard.chart_series_booking_count')),
                            type: 'column',
                            data: @json($data['revenue_chart']['total_bookings']),
                        }
                    ],
                    colors: colors,
                    chart: {
                        height: "75%",
                        type: "line",
                        toolbar: {
                            show: false,
                        },
                    },
                    dataLabels: {
                        enabled: true,
                        enabledOnSeries: [0],
                        formatter: function (val, opts) {
                            // Format only the Sales series (index 0) as amount with currency settings
                            if (opts && opts.seriesIndex === 0) {
                                return formatCurrencyValue(val);
                            }
                            return val;
                        }
                    },  
                    legend: {
                        show: false,
                    },
                    stroke: {
                        show: true,
                        curve: 'smooth',
                        lineCap: 'butt',
                        width: 3
                    },
                    grid: {
                        show: true,
                        strokeDashArray: 3,
                    },
                    xaxis: {
                        categories: @json($data['revenue_chart']['xaxis']),
                        labels: {
                            minHeight: 20,
                            maxHeight: 20,
                        },
                        axisBorder: {
                            show: false,

                        }
                    },
                    yaxis: [{
                        title: {
                            text: @json(__('dashboard.chart_yaxis_revenue')),
                        },
                        labels: {
                            minWidth: 19,
                            maxWidth: 19,
                            formatter: function (val) {
                                return formatCurrencyValue(val);
                            }
                        },
                        tickAmount: 3,
                        min: 0
                    }, {
                        title: {
                            text: @json(__('dashboard.chart_yaxis_appointments')),
                        },
                        opposite: true,
                        labels: {
                            formatter: function (value) {
                                return value.toFixed(0);
                            }
                        },
                        tickAmount: 3,
                        min: 0
                    }]
                };

                const chart = new ApexCharts(document.querySelector("#chart-02"), options);
                chart.render();
            }
        })
    </script>
@endpush
