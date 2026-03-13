<nav id="navbar_main" class="offcanvas mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
    <div class="container-fluid p-0">
        <div class="offcanvas-header">
            <div class="">
                <x-logo />
            </div>
            <button type="button" class="btn-close p-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <!-- Select Branch -->
        @php
            $branches = get_active_branch();
            $selectedBranchId = session('selected_branch_id');
            
            // Debug: Log the selected branch ID and branches
            \Log::info('Selected Branch ID: ' . $selectedBranchId);
            \Log::info('Branches count: ' . $branches->count());
            if ($selectedBranchId) {
                $selectedBranch = $branches->firstWhere('id', $selectedBranchId);
                \Log::info('Selected Branch: ' . ($selectedBranch ? $selectedBranch['name'] : 'Not found'));
            }
        @endphp


        @if (
            $branches->count() > 0 &&
                isset($headerMenuSettingDecoded['selectbranch']) &&
                $headerMenuSettingDecoded['selectbranch'] == 1)
            <div class="select-branch">
                <a class="branch-panel d-flex align-items-center justify-content-between gap-lg-3 gap-2"
                    data-bs-toggle="dropdown" href="#" aria-expanded="false">
                    <div>
                        <i class="ph ph-git-branch"></i>
                        @if ($selectedBranchId)
                            @php
                                $selectedBranch = $branches->firstWhere('id', $selectedBranchId);
                                // Fallback: Get branch name directly from database if collection method fails
                                if (!$selectedBranch) {
                                    $selectedBranch = \App\Models\Branch::find($selectedBranchId);
                                    $selectedBranch = $selectedBranch ? ['name' => $selectedBranch->name] : null;
                                }
                            @endphp
                            <span class="font-size-14">
                                {{ $selectedBranch ? $selectedBranch['name'] : __('frontend.select_branch') }}
                            </span>
                        @else
                            <span class="font-size-14">{{ __('frontend.select_branch') }}</span>
                        @endif
                    </div>
                    <i class="ph ph-caret-down branch-icons"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-start dropdown-branch-panel shadow">
                    @php $branchArray = $branches->toArray(request()); @endphp
                    @foreach (array_slice($branchArray, 0, 3) as $branch)
                        <div class="branch-panel-card d-flex flex-column flex-md-row align-items-stretch position-relative"
                            @php
$timezone = setting('default_time_zone') ?? setting('time_zone') ?? 'UTC';
                $today = \Carbon\Carbon::now($timezone)->format('l');
                $now = \Carbon\Carbon::now($timezone);
                
                // TEST: Simulate time after 1 PM for Serene Styles to test CLOSED status
                if ($branch['name'] === 'Serene Styles') {
                    $now = \Carbon\Carbon::now($timezone)->setTime(14, 0, 0); // Set to 2:00 PM
                }

                $hours = \Modules\BussinessHour\Models\BussinessHour::where('branch_id', $branch['id'])
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
                } @endphp
                            <div
                            class="branch-panel-card d-flex flex-row align-items-stretch position-relative cursor-pointer"
                            data-branch-id="{{ $branch['id'] }}">
                            @if ($branch['id'] == $selectedBranchId)
                                <i class="ph-fill ph-check-circle font-size-21-3 text-primary branch-panel-active"></i>
                            @else
                                <i class="ph-fill ph-check-circle font-size-21-3 text-primary branch-panel-active"
                                    style="display: none;"></i>
                            @endif
                            <div class="position-relative cursor-pointer" onclick="selectBranch('{{ $branch['id'] }}')">
                                <span
                                    class="badge {{ $isOpen ? 'bg-success' : 'bg-danger' }} text-white">{{ $isOpen ? 'Open' : 'Closed' }}</span>
                                <img src="{{ $branch['branch_image'] }}" 
                                    onerror="this.src='{{ asset('img/frontend/branch-image.jpg') }}'"
                                    class="card-img rounded-start"
                                    alt="Salon Image">
                            </div>
                            <div class="panel-desc">
                                <div class="d-flex flex-wrap gap-2 gap-md-3 gap-lg-5 mb-2">
                                    <h6 class="mb-0">{{ $branch['name'] }}</h6>
                                    <div>
                                        <span
                                            class="badge bg-purple text-body border rounded-pill">{{ ucfirst($branch['branch_for']) }}</span>
                                    </div>
                                </div>
                                <ul class="list-inline m-0 p-0">
                                    <li class="mb-2 small"><i class="ph ph-map-pin"></i>{{ $branch['address_line_1'] }}
                                        @if ($branch['city'] && $branch['country'])
                                            <span class="mb-2 small">,{{ $branch['city'] }},
                                                {{ $branch['country'] }}</span>
                                        @endif
                                    </li>
                                    <li class="mb-2 small">{!! get_distance_from_location($branch['latitude'], $branch['longitude'], 'K') !!}</li>
                                    <li class="text-warning fw-semibold small">
                                        <span class="text-warning">★</span><span class="heading-color">
                                            {{ $branch['rating_star'] }} </span> <span class="text-body">(Based on
                                            {{ $branch['total_review'] }} reviews)</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endforeach
                    @if (count($branchArray) > 3)
                        <div class="dropdown-divider"></div>
                        <a href="{{ route('branch') }}"
                            class="dropdown-item text-primary fw-bold">{{ __('frontend.view_all_branches') }}</a>
                    @endif
                </div>
            </div>
        @endif
        <!-- menu -->
        <ul class="navbar-nav iq-nav-menu  list-unstyled" id="header-menu">
            <li class="nav-item">
                <a class="nav-link" href="{{ route('index') }}">
                    <span class="item-name">{{ __('frontend.home') }}</span>
                </a>
            </li>
            @if (isset($headerMenuSettingDecoded['category']) && $headerMenuSettingDecoded['category'] == 1)
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('category') }}">
                        <span class="item-name">{{ __('frontend.category') }}</span>
                    </a>
                </li>
            @endif
            @if (isset($headerMenuSettingDecoded['service']) && $headerMenuSettingDecoded['service'] == 1)
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('service') }}">
                        <span class="item-name">{{ __('frontend.service') }}</span>
                    </a>
                </li>
            @endif
            @if (isset($headerMenuSettingDecoded['shop']) && $headerMenuSettingDecoded['shop'] == 1)
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('shop') }}">
                        <span class="item-name">{{ __('frontend.shop') }}</span>
                    </a>
                </li>
            @endif
            @if (auth()->check() &&  isset($headerMenuSettingDecoded['mybooking']) && $headerMenuSettingDecoded['mybooking'] == 1)
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('bookings') }}">
                        <span class="item-name">{{ __('frontend.booking') }}</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</nav>


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide scrollbar for branch dropdown
            const branchDropdown = document.querySelector('.dropdown-branch-panel');
            if (branchDropdown) {
                branchDropdown.style.scrollbarWidth = 'none';
                branchDropdown.style.msOverflowStyle = 'none';
                // Add custom CSS to hide webkit scrollbar
                const style = document.createElement('style');
                style.textContent = `
                    .dropdown-branch-panel::-webkit-scrollbar {
                        display: none !important;
                        width: 0 !important;
                        height: 0 !important;
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Handle branch selection
            document.querySelectorAll('.branch-panel-card').forEach(card => {
                card.addEventListener('click', function() {
                    const branchId = this.getAttribute('data-branch-id');

                    // Show loading state
                    const checkIcons = document.querySelectorAll('.branch-panel-active');
                    checkIcons.forEach(icon => icon.style.display = 'none');

                    // Send AJAX request
                    fetch('{{ route('branch.select') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                branch_id: branchId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show checkmark on selected branch
                                const selectedIcon = document.querySelector(
                                    `.branch-panel-card[data-branch-id="${data.branch_id}"] .branch-panel-active`
                                );
                                if (selectedIcon) {
                                    selectedIcon.style.display = 'block';
                                }

                                // Update branch name in the header
                                const branchName = this.querySelector('h6').textContent;
                                const branchSpan = document.querySelector(
                                    '.branch-panel .font-size-14');
                                if (branchSpan) {
                                    branchSpan.textContent = branchName;
                                }

                                // Dispatch custom event for branch selection
                                const branchSelectedEvent = new CustomEvent('branchSelected', {
                                    detail: {
                                        branchId: data.branch_id,
                                        branchName: branchName
                                    }
                                });
                                document.dispatchEvent(branchSelectedEvent);
                                
                                // Close the dropdown
                                const dropdown = document.querySelector('.dropdown-menu');
                                if (dropdown) {
                                    dropdown.classList.remove('show');
                                }
                                
                                // Show success message
                                if (window.toastr) {
                                    toastr.success('Branch selected successfully!');
                                }
                                
                                // Auto-refresh the page after a short delay
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert(
                                '{{ __('frontend.error_occurred_while_selecting_branch') }}'
                            );
                        });
                });
            });
        });
    </script>
@endpush
