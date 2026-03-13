    <?php

use App\Models\Branch;
use App\Http\Resources\BranchResource;

if (!function_exists('get_active_branch')) {

    function get_active_branch()
    {
        $branches = Branch::where('status', 1)->get();
        return BranchResource::collection($branches);
    }
}


if (!function_exists('checkBranchStatus')) {
    function checkBranchStatus($branch)
    {
        $currentDay = strtolower(now()->format('l'));
        $currentTime = now()->format('H:i:s');

        // Find today's schedule
        $todaySchedule = collect($branch['working_days'])->first(function($day) use ($currentDay) {
            return strtolower($day['day']) === $currentDay && !$day['is_holiday'];
        });

        // If today is a holiday or no schedule for today
        if (!$todaySchedule) {
            return 'CLOSED';
        }

        // Check breaks
       if (!empty($todaySchedule['breaks'])) {
    foreach ($todaySchedule['breaks'] as $break) {
        if (
            isset($break['start_time'], $break['end_time']) &&
            $currentTime >= $break['start_time'] && $currentTime <= $break['end_time']
        ) {
            return 'CLOSED';
        }
    }
}

        // Check working hours
        if ($currentTime >= $todaySchedule['start_time'] && $currentTime <= $todaySchedule['end_time']) {
            return 'OPEN';
        }

        return 'CLOSED';
    }
}

if (!function_exists('get_distance_from_location')) {
    /**
     * Calculate distance between two points in miles or kilometers
     *
     * @param float $lat2 Branch's latitude
     * @param float $lon2 Branch's longitude
     * @param string $unit 'M' for miles (default), 'K' for kilometers
     * @return string HTML with distance or empty string if location not available
     */
    function get_distance_from_location($lat2, $lon2, $unit = 'M') {
        $html = '<span class="distance-container" data-lat="' . e($lat2) . '" data-lng="' . e($lon2) . '" data-unit="' . e($unit) . '">';
        $html .= '<i class="ph ph-arrow-arc-right"></i> ';
        $html .= '<span class="distance-text">Calculating distance...</span>';
        $html .= '</span>';

        // Add the JavaScript to handle the geolocation
        static $jsAdded = false;
        if (!$jsAdded) {
            $js = "
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const userLat = position.coords.latitude;
                            const userLng = position.coords.longitude;

                            document.querySelectorAll('.distance-container').forEach(container => {
                                const branchLat = parseFloat(container.dataset.lat);
                                const branchLng = parseFloat(container.dataset.lng);
                                const unit = container.dataset.unit || 'M';

                                const distance = calculateDistance(userLat, userLng, branchLat, branchLng, unit);
                                const distanceText = unit === 'K' ? distance + ' km from your location' : distance + ' miles from your location';
                                container.querySelector('.distance-text').textContent = distanceText;
                            });
                        },
                        function(error) {
                            console.error('Error getting location:', error);
                            document.querySelectorAll('.distance-text').forEach(el => {
                                el.textContent = 'Enable location to see distance';
                            });
                        }
                    );
                } else {
                    document.querySelectorAll('.distance-text').forEach(el => {
                        el.textContent = 'Geolocation not supported';
                    });
                }

                function calculateDistance(lat1, lon1, lat2, lon2, unit) {
                    if ((lat1 == lat2) && (lon1 == lon2)) return 0;

                    const radlat1 = Math.PI * lat1/180;
                    const radlat2 = Math.PI * lat2/180;
                    const theta = lon1-lon2;
                    const radtheta = Math.PI * theta/180;

                    let dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
                    dist = Math.acos(dist);
                    dist = dist * 180/Math.PI;
                    dist = dist * 60 * 1.1515;

                    if (unit === 'K') dist = dist * 1.609344;

                    return dist.toFixed(1);
                }
            });
            </script>";

            $html .= $js;
            $jsAdded = true;
        }

        return $html;
    }
}
