@props(['branch'])

@php
    // Calculate actual branch status based on business hours
    $timezone = setting('default_time_zone') ?? setting('time_zone') ?? 'UTC';
    $today = \Carbon\Carbon::now($timezone)->format('l'); // Full day name (Monday, Tuesday, etc.)
    $now = \Carbon\Carbon::now($timezone);
    
    // TEST: Simulate time after 1 PM for Serene Styles to test CLOSED status
    if ($branch->name === 'Serene Styles') {
        $now = \Carbon\Carbon::now($timezone)->setTime(14, 0, 0); // Set to 2:00 PM
    }
    
    
    
    // Get today's business hours
    $hours = \Modules\BussinessHour\Models\BussinessHour::where('branch_id', $branch->id)
        ->whereRaw('LOWER(day) = ?', [strtolower($today)])
        ->first();
    
    $isOpen = false;
    
    if ($hours && $hours->is_holiday != 1 && $hours->start_time && $hours->end_time) {
        // Simple time comparison using string format
        $currentTime = $now->format('H:i:s');
        $startTime = $hours->start_time;
        $endTime = $hours->end_time;
        
        
        
        // Check if current time is within business hours
        $isOpen = ($currentTime >= $startTime && $currentTime <= $endTime);
        
        // Check breaks if branch is open
        if ($isOpen && !empty($hours->breaks)) {
            $breaks = is_array($hours->breaks) ? $hours->breaks : json_decode($hours->breaks, true);
            if (is_array($breaks)) {
                        foreach ($breaks as $break) {
                            if (!empty($break['start_break']) && !empty($break['end_break'])) {
                                if ($currentTime >= $break['start_break'] && $currentTime <= $break['end_break']) {
                                    $isOpen = false;
                                    break;
                                }
                            }
                        }
            }
        }
    }
    
    $selectedBranchId = session('selected_branch_id');
    $isSelected = $selectedBranchId == $branch->id;
@endphp

<div class="branch-card rounded position-relative overflow-hidden branch-select-badge-card" data-branch-id="{{ $branch->id }}">
    <span
        class="font-size-14 text-uppercase position-absolute top-50 start-50 translate-middle cursor-pointer z-2"
        data-branch-id="{{ $branch->id }}">
        @if ($isSelected)
        <i class="ph-fill ph-check-circle fs-4 text-success"></i>
        @endif
    </span>
    <div class=" position-relative">
        @php
            $image = $branch->media->pluck('original_url')->first() ?? asset('img/frontend/branch-image.jpg');
        @endphp
        <span class="badge {{ $isOpen ? 'bg-success' : 'bg-danger' }} text-white font-size-14 text-uppercase position-absolute top-0 end-0">
            {{ $isOpen ? __('frontend.open') : __('frontend.closed') }}
        </span>
        
        <img src="{{ $image }}" class="card-img-top" 
             onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
             alt="{{ $branch->name }}">
    </div>

    <a href="{{ route('branch-detail', $branch->id) }}" class="branch-info-box text-decoration-none text-reset d-block z-1">
            <span class="d-flex flex-wrap align-items-center gap-1 mb-2">
                <h5 class="mb-0 fw-medium line-count-1">{{ $branch->name }}</h5>
                @if($branch->branch_for)
                <span class="badge bg-purple text-body border rounded-pill text-uppercase">
                    {{ $branch->branch_for }}
                </span>
            @endif
            </span>
        @if($branch->address)
            <span class="d-flex gap-2">
                <i class="ph ph-map-pin align-middle"></i>
                <span class="font-size-14">
                    {{ $branch->address->address_line_1 ?? '' }}
                    @if($branch->address->city_data), {{ $branch->address->city_data->name }} @endif
                    @if($branch->address->state_data), {{ $branch->address->state_data->name }} @endif
                    @if($branch->address->country_data), {{ $branch->address->country_data->name }} @endif
                </span>
            </span>
        @endif
    </a>
</div>
