@extends('backend.layouts.app')
@section('title')
  {{ __('customer.title') }}
@endsection

@push('after-styles')
<link rel="stylesheet" href="{{ mix('modules/constant/style.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-omWbV5gkqwa9N5JGnQ1rA5AviTR+mzlsYvfwGeSbZruk3AEInternal92fqunSBAl3cRi6QwKBgGvjsUabdlmsbKQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    <!-- Title and Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="mb-0">{{ $data['customerInfo']->full_name ?? ($data['customerInfo']->first_name . ' ' . $data['customerInfo']->last_name) }}</h4>
      <a href="{{ route('backend.customers.index') }}" class="btn btn-primary">{{ __('messages.back') }}</a>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="customerTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">{{ __('messages.overview') }}</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">{{ __('booking.title') }}</button>
      </li>
    </ul>

    <div class="tab-content" id="customerTabContent">
      <!-- Overview Tab -->
      <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <div class="row gy-3 mb-5 pb-2">
          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="mb-0">{{ __('booking.total_bookings') }}</h5>
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
                  <h5 class="mb-0">{{ __('booking.cancelled_bookings') }}</h5>
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
                  <h5 class="mb-0">{{ __('booking.completed_bookings') }}</h5>
                  <h3 class="text-primary mb-0">{{ $data['completedBookings'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-square-check text-success fs-3"></i>
                </div>
              </div>
            </div>
          </div>
          @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('manager'))
          <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded card-light-bg">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="mb-0">{{ __('messages.products') }}</h5>
                  <h3 class="text-primary mb-0">{{ $data['purchasedProducts'] }}</h3>
                </div>
                <div class="avatar-60 badge rounded-circle badge-light-bg d-flex align-items-center justify-content-center">
                  <i class="fa-solid fa-cube text-warning fs-3"></i>
                </div>
              </div>
            </div>
          </div>
          @endif
        </div>

        <h4 class="mb-3">{{ __('booking.basic_information') }}</h4>
        <div class="d-flex gap-3 align-items-center p-4 rounded flex-md-nowrap flex-wrap card-light-bg">
          <div>
            <img src="{{ $data['customerInfo']->profile_image ?? default_user_avatar() }}" alt="Profile Image" class="avatar avatar-80 rounded-pill">
          </div>
          <div class="flex-grow-1">
            <h4 class="m-0">{{ $data['customerInfo']->full_name ?? ($data['customerInfo']->first_name . ' ' . $data['customerInfo']->last_name) }}</h4>
            <div class="d-flex align-items-center column-gap-3 row-gap-2 mt-3 flex-md-nowrap flex-wrap">
              <div class="d-flex align-items-center gap-2 text-break">
                <i class="fa-solid fa-envelope"></i>
                {{ $data['customerInfo']->email }}
              </div>
              <div class="d-flex align-items-center gap-2">
                <i class="ph ph-phone text-heading"></i>
                <a href="#" class="text-primary text-decoration-underline font-size-16">
                  {{ $data['customerInfo']->contact ?? $data['customerInfo']->phone ?? '-' }}
                </a>
              </div>
              <div class="d-flex align-items-center gap-2">
                <i class="ph ph-cake text-heading"></i>
                <span class="font-size-16">
                  @if(!empty($data['customerInfo']->dob))
                    {{ \Carbon\Carbon::parse($data['customerInfo']->dob)->format('d-m-Y') }}
              
                    
                  @endif
                </span>
              </div>
              <div class="d-flex align-items-center gap-2">
                @php
                  $gender = strtolower($data['customerInfo']->gender ?? '');
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
      <!-- Bookings Tab -->
      <div class="tab-pane fade" id="bookings" role="tabpanel">
        <h5 class="mb-3">{{ __('booking.title') }}</h5>
        <div class="row g-3">
          @forelse($data['bookings'] as $booking)
            @php
              $services = $booking->booking_service;
              $servicePrice = 0;
              foreach($services as $service) {
                  $servicePrice += $service->membership_price > 0
                      ? $service->membership_price
                      : $service->service_price;
              }
              $firstService = ($services instanceof \Illuminate\Support\Collection) ? $services->first() : null;
              $employee = $firstService?->employee;
              $specialist = $employee?->full_name ?? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
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
                      {{-- <span class="badge bg-{{ $booking->status == 'completed' ? 'primary' : ($booking->status == 'cancelled' ? 'danger' : ($booking->status == 'pending' ? 'warning' : 'secondary')) }} mb-2">
                        {{ ucfirst($booking->status) }}
                      </span> --}}
                      <h5 class="mb-1">{{ $booking->branch->name ?? 'Scissors Salon' }}</h5>
                      <div class="text-muted small mb-1">
                        {{
                          ($booking->branch && $booking->branch->address ? $booking->branch->address->address_line_1 : '')
                          .
                          (isset($booking->branch->address->country_data->name) ? ', ' . $booking->branch->address->country_data->name : '')
                        }}
                      </div>
                      <div class="fw-semibold mb-1">
                        <span class="text-muted">{{ __('booking.employee') }}:</span>
                        <span>{{ $specialist }}</span>
                      </div>
                      <div>{{ __('booking.services_name') }}: {{ $services->pluck('service.name')->filter()->implode(', ') }}</div>
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
                      {{ $booking->start_date_time ? \Carbon\Carbon::parse($booking->start_date_time)->format('H:i') : '--' }}
                    </span>
                    <span class="badge bg-{{ $booking->status == 'completed' ? 'success' : ($booking->status == 'cancelled' ? 'danger' : 'warning') }}">
                      {{ $data['bookingStatuses'][$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                    </span>
                  </div>
                    <a href="{{ route('backend.bookings.index', ['booking_id' => $booking->id]) }}" class="fw-semibold link-view">{{ __('messages.view') }}</a>
                    <div>{{ __('booking.booking_id') }} #{{ $booking->id }}</div>
                </div>
              </div>
            </div>
          @empty
            <div class="col-12">
              <div class="text-center">{{ __('messages.not_found') }}</div>
            </div>
          @endforelse
        </div>
      </div>
      
    </div>
  </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ mix('modules/customer/script.js') }}"></script>
<script src="{{ asset('js/form-offcanvas/index.js') }}" defer></script>
<script src="{{ asset('js/form-modal/index.js') }}" defer></script>
<script type="text/javascript" src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/css/phosphor.css">

@endpush
