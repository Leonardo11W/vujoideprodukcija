@props(['expert'])

<a href="{{ route('expert-detail', $expert->id) }}" class="text-decoration-none">
    <div class="export-card text-center cursor-pointer">
        <div class="export-image position-relative">
            <img src="{{ $expert->profile_image ?? asset('img/frontend/export-image.jpg') }}" alt="{{ $expert->full_name }}" class="avatar-128 rounded-circle object-fit-cover">
        </div>
        <div class="export-info mt-3">
            <div class="d-flex align-items-center justify-content-center gap-lg-3 gap-2 mb-1">
                <div class="d-inline-flex align-items-center gap-1">
                    @php
                        $rating = isset($expert->avg_rating) ? $expert->avg_rating : 4.2;
                        $fullStars = floor($rating);
                        $decimalPart = $rating - $fullStars;
                        $nextStarFill = $decimalPart;
                        $emptyStars = 5 - $fullStars - 1;
                        // Debug output
                        echo "<!-- Rating: $rating, Full: $fullStars, Next: $nextStarFill, Empty: $emptyStars -->";
                    @endphp
                    
                    {{-- Full stars --}}
                    @for($i = 0; $i < $fullStars; $i++)
                        <i class="ph-fill ph-star text-warning"></i>
                    @endfor
                    
                    {{-- Partial star with background gradient --}}
                    <div class="position-relative d-inline-block">
                        <i class="ph ph-star text-body"></i>
                        <div class="position-absolute top-0 start-0" style="width: {{ $nextStarFill * 100 }}%; height: 100%; overflow: hidden;">
                            <i class="ph-fill ph-star text-warning"></i>
                        </div>
                    </div>
                    
                    {{-- Empty stars --}}
                    @for($i = 0; $i < $emptyStars; $i++)
                        <i class="ph ph-star text-body"></i>
                    @endfor
                </div>
                <span class="fw-semibold heading-color font-size-14">{{ number_format($rating, 1) }}</span>
            </div>
            <h5 class="mb-1 title-export">{{ $expert->full_name }}</h5>
            <p class="font-size-14 mb-0">{{ $expert->specialty ?? 'Makeup specialist' }}</p>
        </div>
    </div>
</a>