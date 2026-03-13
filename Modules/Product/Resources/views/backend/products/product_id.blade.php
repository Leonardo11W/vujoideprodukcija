<div class="d-flex gap-3 align-items-center">

  <img src="{{ $data->multiple_feature_images[0] ?? default_user_avatar() }}" alt="avatar" class="avatar avatar-40 rounded-pill">
  <div>
    <p class="m-0">{{ $data->name ?? default_user_name() }}</p>
  </div>
</div>