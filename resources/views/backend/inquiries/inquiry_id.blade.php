<div class="d-flex gap-3 align-items-center">
    <div class="avatar avatar-40 rounded-pill bg-primary d-flex align-items-center justify-content-center">
        <i class="fa-solid fa-user text-white"></i>
    </div>
    <div>
        <p class="m-0 fw-medium">{{ $data->name ?? 'N/A' }}</p>
        <small class="text-muted">{{ $data->email ?? 'N/A' }}</small>
    </div>
</div>
