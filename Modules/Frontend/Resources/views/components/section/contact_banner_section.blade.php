<div class="section-spacing">
    <div class="contact-working-banner section-spacing" style="background-image: url({{ asset('img/frontend/contact-banner.jpg') }})">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-md-7 col-lg-8 col-xl-9">
                    
                </div>
                @if(isset($branch) && $branch && $branch->bussinesshours->count() > 0)

                <div class="col-sm-6 col-md-5 col-lg-4 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4">Working Hours</h4>
                            @php
                                $days = [
                                    'monday' => 'Monday',
                                    'tuesday' => 'Tuesday',
                                    'wednesday' => 'Wednesday',
                                    'thursday' => 'Thursday',
                                    'friday' => 'Friday',
                                    'saturday' => 'Saturday',
                                    'sunday' => 'Sunday'
                                ];
                                
                                // Get all business hours
                                $businessHours = [];
                                $weekdays = [];
                                $saturday = null;
                                $sunday = null;
                                
                                foreach ($days as $dayKey => $dayName) {
                                    $hours = $branch->bussinesshours->firstWhere('day', $dayKey);
                                    if ($hours) {
                                        if (in_array($dayKey, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'])) {
                                            $weekdays[$dayKey] = $hours;
                                        } elseif ($dayKey === 'sunday') {
                                            $sunday = $hours;
                                        }
                                    }
                                }
                                
                                // Check if all weekdays (Mon-Sat) have the same hours AND same breaks
                                $weekdayHours = reset($weekdays);
                                $allWeekdaysSame = array_reduce($weekdays, function($carry, $item) use ($weekdayHours) {
                                    return $carry && 
                                           $item->start_time === $weekdayHours->start_time && 
                                           $item->end_time === $weekdayHours->end_time &&
                                           $item->is_holiday === $weekdayHours->is_holiday &&
                                           json_encode($item->breaks) === json_encode($weekdayHours->breaks);
                                }, true);
                            @endphp
                            
                            {{-- Show Monday to Saturday --}}
                            @if($allWeekdaysSame && count($weekdays) > 0)
                                <div class="mb-3 mb-lg-5">
                                    <p class="mb-1">Monday to Saturday:</p>
                                    @if($weekdayHours->is_holiday)
                                        <h5 class="mb-0 text-danger">Closed</h5>
                                    @else
                                        <h5 class="mb-0">
                                            {{ \Carbon\Carbon::parse($weekdayHours->start_time)->format('h:i A') }} - 
                                            {{ \Carbon\Carbon::parse($weekdayHours->end_time)->format('h:i A') }}
                                        </h5>
                                        @if(isset($weekdayHours->breaks) && count($weekdayHours->breaks) > 0)
                                            <div class="mt-2">
                                            <p class="mb-1">Break Time:</p>
                                                @foreach($weekdayHours->breaks as $break)
                                                <h5 class="mb-0">
                                                        {{ \Carbon\Carbon::parse($break['start_break'] ?? $break->start_break ?? '00:00:00')->format('h:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($break['end_break'] ?? $break->end_break ?? '00:00:00')->format('h:i A') }}
                                                        </h5>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @else
                                @foreach($weekdays as $dayKey => $hours)
                                    <div class="mb-3 mb-lg-5">
                                        <p class="mb-1">{{ $days[$dayKey] }}:</p>
                                        @if($hours->is_holiday)
                                            <h5 class="mb-0 text-danger">Closed</h5>
                                        @else
                                            <h5 class="mb-0">
                                                {{ \Carbon\Carbon::parse($hours->start_time)->format('h:i A') }} - 
                                                {{ \Carbon\Carbon::parse($hours->end_time)->format('h:i A') }}
                                            </h5>
                                            @if(isset($hours->breaks) && count($hours->breaks) > 0)
                                            <div class="mt-2">
                                            <p class="mb-1">Break Time:</p>
                                                @foreach($hours->breaks as $break)
                                                <h5 class="mb-0">
                                                        {{ \Carbon\Carbon::parse($break['start_break'] ?? $break->start_break ?? '00:00:00')->format('h:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($break['end_break'] ?? $break->end_break ?? '00:00:00')->format('h:i A') }}
                                                        </h5>
                                                @endforeach
                                            </div>
                                        @endif
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                            
                            {{-- Show Sunday separately --}}
                            @if($sunday)
                                <div class="mb-0">
                                    <p class="mb-1">Sunday:</p>
                                    @if($sunday->is_holiday)
                                        <h5 class="mb-0 text-danger">Closed</h5>
                                    @else
                                        <h5 class="mb-0">
                                            {{ \Carbon\Carbon::parse($sunday->start_time)->format('h:i A') }} - 
                                            {{ \Carbon\Carbon::parse($sunday->end_time)->format('h:i A') }}
                                        </h5>
                                        @if(isset($sunday->breaks) && count($sunday->breaks) > 0)
                                            <div class="mt-2">
                                            <p class="mb-1">Break Time:</p>
                                                @foreach($sunday->breaks as $break)
                                                <h5 class="mb-0">
                                                        {{ \Carbon\Carbon::parse($break['start_break'] ?? $break->start_break ?? '00:00:00')->format('h:i A') }} - 
                                                        {{ \Carbon\Carbon::parse($break['end_break'] ?? $break->end_break ?? '00:00:00')->format('h:i A') }}
                                                        </h5>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                            
                          
                            
                          
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
