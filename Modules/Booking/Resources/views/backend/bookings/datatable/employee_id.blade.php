@if($id)
  <a href="{{ route('backend.employees.show', $id) }}" class="d-flex gap-3 align-items-center text-decoration-none">
    <img src="{{  $Profile_image }}" alt="avatar" class="avatar avatar-40 rounded-pill">
    <div>
        <h6 class="m-0">{{ $name  }}</h6>
        <small class="text-muted">{{ $email  }}</small>
    </div>
  </a>
@else
  <div class="d-flex gap-3 align-items-center">
    <img src="{{  $Profile_image }}" alt="avatar" class="avatar avatar-40 rounded-pill">
    <div>
        <h6 class="m-0">{{ $name  }}</h6>
        <small>{{ $email  }}</small>
    </div>
  </div>
@endif
