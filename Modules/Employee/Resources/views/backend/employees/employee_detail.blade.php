@extends('backend.layouts.app')
@section('title')
  {{ __('employee.employee_detail') }}
@endsection

@push('after-styles')
<link rel="stylesheet" href="{{ mix('modules/constant/style.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
<style>
  .card-light-bg {
    background: #fcfcff;
  }
  .dark .card-light-bg,
  [data-bs-theme="dark"] .card-light-bg {
    background: #080808;
    border-color: #313131;
  }
  .badge-light-bg {
    background: white;
  }
  .dark .badge-light-bg,
  [data-bs-theme="dark"] .badge-light-bg {
    background: #181818;
  }
  .badge-price {
    background: #ffe7b0;
    color: #222;
    font-weight: 600;
    font-size: 1rem;
  }
  .dark .badge-price,
  [data-bs-theme="dark"] .badge-price {
    background: #4a3a1a;
    color: #f0e6d2;
  }
  .link-view {
    color: #b13cff;
  }
  .dark .link-view,
  [data-bs-theme="dark"] .link-view {
    color: #c966ff;
  }
</style>
@endpush

@section('content')
<div class="container-fluid">
  <div class="card p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">{{ $data['employee']->full_name }}</h4>
      <a href="{{ route('backend.employees.index') }}" class="btn btn-primary">{{ __('messages.back') }}</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="staffTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">{{ __('messages.overview') }}</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">{{ __('booking.bookings') }}</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="earnings-tab" data-bs-toggle="tab" data-bs-target="#earnings" type="button" role="tab">{{ __('earning.title') ?? 'Earnings' }}</button>
      </li>
    </ul>

    <div class="tab-content" id="staffTabContent">
      <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row gy-3 mb-5 pb-2">
          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.total_bookings') ?? 'Total Bookings' }}</h6>
                  <h3 class="text-primary mb-0">{{ $data['totalBookings'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-list-check text-info fs-3"></i>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.completed_bookings') ?? 'Completed' }}</h6>
                  <h3 class="text-primary mb-0">{{ $data['completedBookings'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-square-check text-success fs-3"></i>
                </div>
              </div>
            </div>
          </div>
          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.cancelled_bookings') ?? 'Cancelled' }}</h6>
                  <h3 class="text-primary mb-0">{{ $data['cancelledBookings'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-calendar-xmark text-danger fs-3"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.pending_bookings') ?? 'Pending' }}</h6>
                  <h3 class="text-primary mb-0">{{ $data['pendingBookings'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-clock text-warning fs-3"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('earning.title') ?? 'Total Earnings' }}</h6>
                  <h3 class="text-primary mb-0">{{ Currency::format($data['totalEarnings']) }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-dollar-sign text-primary fs-3"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- <h4 class="mb-3">{{ __('messages.basic_information') }}</h4> --}}

        <div class="d-flex gap-3 align-items-center p-4 rounded flex-md-nowrap flex-wrap card-light-bg">
          <div>
            <img src="{{ $data['employee']->profile_image ?? default_user_avatar() }}" alt="Profile Image" class="avatar avatar-80 rounded-pill">
          </div>
          <div class="flex-grow-1">
            <h4 class="m-0">{{ $data['employee']->full_name }}</h4>
            <div class="d-flex align-items-center column-gap-3 row-gap-2 mt-3 flex-md-nowrap flex-wrap">
              <div class="d-flex align-items-center gap-2 text-break">
                <i class="fa-solid fa-envelope text-heading"></i>
                {{ $data['employee']->email }}
              </div>
              <div class="d-flex align-items-center gap-2">
                <i class="ph ph-phone text-heading"></i>
                <a href="#" class="text-primary text-decoration-underline font-size-16">
                  {{ $data['employee']->contact ?? $data['employee']->phone ?? '-' }}
                </a>
              </div>
              <div class="d-flex align-items-center gap-2">

                @php
                  $gender = strtolower($data['employee']->gender ?? '');
                @endphp
                @if($gender === 'male')
                  <i class="fa-solid fa-mars text-primary"></i>
                @elseif($gender === 'female')
                  <i class="fa-solid fa-venus text-danger"></i>
                @elseif($gender === 'intersex' || $gender === 'other')
                  <i class="fa-solid fa-venus-mars text-warning"></i>
                @else
                  <i class="fa-solid fa-genderless text-secondary"></i>
                @endif
                @php
                  $genderKey = in_array($gender, ['male', 'female', 'intersex', 'unisex', 'other']) ? $gender : null;
                @endphp
                <span class="font-size-16">{{ $genderKey ? __('messages.' . $genderKey) : '--' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="bookings" role="tabpanel">
        <h5 class="mb-3">{{ __('booking.bookings') }}</h5>
        <div class="row g-3">
          @foreach($data['recentBookings'] as $booking)
            @php
              $services = $booking->booking_service;
              $servicePrice = 0;
              foreach($services as $service) {
                  $servicePrice += $service->membership_price > 0
                      ? $service->membership_price
                      : $service->service_price;
              }
              $branchImage = $booking->branch && $booking->branch->media && $booking->branch->media->first()
                ? $booking->branch->media->first()->getUrl()
                : asset('default-image.png');
            @endphp
            <div class="col-md-6 col-xl-4 col-xxl-3">
              <div class="card shadow-sm border-0 mb-3 card-light-bg">
                <div class="card-body p-3">
                  <div class="d-flex align-items-start">
                    <img src="{{ $branchImage }}" alt="Branch" class="rounded me-3" width="64" height="64" style="object-fit:cover;">
                    <div class="flex-grow-1">
                      <h5 class="mb-1">{{ $booking->branch->name ?? '' }}</h5>
                      <div class="text-muted small mb-1">
                        {{
                          ($booking->branch && $booking->branch->address ? $booking->branch->address->address_line_1 : '')
                          .
                          (isset($booking->branch->address->country_data->name) ? ', ' . $booking->branch->address->country_data->name : '')
                        }}
                      </div>
                    </div>
                    <div>
                      <span class="badge rounded-pill px-3 py-2 badge-price">
                        {{ Currency::format($servicePrice) }}
                      </span>
                    </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">
                      <i class="ph ph-calendar"></i>
                      {{ $booking->start_date_time ? \Carbon\Carbon::parse($booking->start_date_time)->format('d/m/Y') : '--' }}
                    </span>
                    <span class="text-muted">
                      <i class="ph ph-clock"></i>
                      {{ $booking->start_date_time ? \Carbon\Carbon::parse($booking->start_date_time)->format('h:i A') : '--' }}
                    </span>
                    <span class="badge bg-{{ $booking->status == 'completed' ? 'success' : ($booking->status == 'cancelled' ? 'danger' : 'warning') }}">
                      {{ $data['bookingStatuses'][$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                    </span>
                  </div>
                  <div>{{ __('booking.services_name') }}: {{ $services->pluck('service.name')->filter()->implode(', ') }}</div>
                  <div>{{ __('booking.booking_id') }}: {{ $booking->id }}</div>
                  <a href="{{ route('backend.bookings.index', ['booking_id' => $booking->id]) }}" class="fw-semibold link-view">{{ __('messages.view') }}</a>
                </div>
              </div>
            </div>
            @endforeach
            @if($data['recentBookings']->isEmpty())
              <div class="col-12">
                <div class="card border-0 shadow-sm rounded card-light-bg">
                  <div class="card-body">
                    <div class="text-center">{{ __('booking.not_found') }}</div>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>

      <div class="tab-pane fade" id="earnings" role="tabpanel">
        <h5 class="mb-3">{{ __('booking.earning') ?? 'Earnings' }}</h5>

        <div class="row g-3">
          {{-- Total Earnings --}}
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.earning') ?? 'Total Earnings' }}</h6>
                  <h3 class="text-primary mb-0">
                    {{ Currency::format($data['totalEarnings']) }}
                  </h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-sack-dollar text-primary fs-3"></i>
                </div>
              </div>
            </div>
          </div>

          {{-- Commission --}}
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.commission') ?? 'Commission' }}</h6>
                  <h3 class="text-primary mb-0">
                    {{ Currency::format($data['totalCommission']) }}
                  </h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-percent text-primary fs-3"></i>
                </div>
              </div>
            </div>
          </div>

          {{-- Tips --}}
          <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="mb-0">{{ __('booking.tip') ?? 'Tips' }}</h6>
                  <h3 class="text-primary mb-0">
                    {{ Currency::format($data['totalTips']) }}
                  </h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-hand-holding-dollar text-primary fs-3"></i>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

        {{-- <div class="card border-0 shadow-sm rounded mt-4 card-light-bg">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>{{ __('booking.date') ?? 'Date' }}</th>
                    <th>{{ __('booking.earning') ?? 'Earning' }}</th>
                    <th>{{ __('booking.commission') ?? 'Commission' }}</th>
                    <th>{{ __('booking.tip') ?? 'Tip' }}</th>
                    <!-- <th>{{ __('messages.description') ?? 'Description' }}</th> -->
                  </tr>
                </thead>
                <tbody>
                  @forelse($data['recentEarnings'] as $e)
                    <tr>
                      <td>{{ $e->date ? \Carbon\Carbon::parse($e->date)->format('d/m/Y') : '--' }}</td>
                      <td>{{ Currency::format($e->total_amount) }}</td>
                      <td>{{ Currency::format($e->commission_amount) }}</td>
                      <td>{{ Currency::format($e->tip_amount) }}</td>
                      <!-- <td>{{ $e->description ?? '-' }}</td> -->
                    </tr>
                  @empty
                    <tr><td colspan="5" class="text-center">{{ __('booking.not_found') }}</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div> --}}
      </div>
    </div>
  </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ mix('modules/employee/script.js') }}"></script>
@endpush


