@php
    $__dashLocale = in_array(app()->getLocale(), ['bs', 'sr'], true) ? 'hr' : app()->getLocale();
    $timezone = setting('default_time_zone') ?? config('app.timezone');
    $currentDateTime = \Carbon\Carbon::now($timezone);
    $dateTime = \Carbon\Carbon::parse($booking->start_date_time)->timezone($timezone);
@endphp
<li class="list-group-item">
  <div class="d-flex justify-content-between align-items-center">
    <div class="d-flex">
      <img src="{{ $booking->user->profile_image ?? default_user_avatar() }}" alt=""
        class="rounded-pill avatar avatar-60" loading="lazy">
      <div class="ms-3">
        <h5 class="mb-2">{{ $booking->user->full_name ?? default_user_name() }}</h5>
        <p class="mb-0 col-md-8">{{ $dateTime->locale($__dashLocale)->translatedFormat('d.m.Y.') }} | {{ $dateTime->format('H:i') }} | {{ $booking->branch->name }}</p>
      </div>
    </div>
    <div class="d-flex align-items-center text-info col-5">
      <i class="fa-regular fa-clock me-2"></i>
      {{ $dateTime->locale($__dashLocale)->diffForHumans($currentDateTime) }}
    </div>
    <div class="dropdown">
      <a href="{{ route('backend.bookings.index', ['booking_id' => $booking->id]) }}" class="text-primary">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>
  </div>
</li>
