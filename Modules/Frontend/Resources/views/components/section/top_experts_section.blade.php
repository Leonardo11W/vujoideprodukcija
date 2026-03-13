<div class="section-spacing-bottom">
    <div class="container-fluid">
        <div class="section-title text-center">
            <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.why_frezka')}}</span>
            <h4 class="title">{{__('frontend.quick_go_through_about_frezka',['app' => setting('app_name')])}}</h4>
        </div>
        <div class="video-wrapper">
            @if(isset($videoSection) && ($videoSection->video_type === 'youtube' && $videoSection->video_url))
                <!-- YouTube Video Embed -->
                @php
                    $embedUrl = $videoSection->video_url;
                    // Convert watch?v= to embed/ if needed
                    if (strpos($embedUrl, 'watch?v=') !== false) {
                        // Extract the video ID
                        $parts = parse_url($embedUrl);
                        parse_str($parts['query'] ?? '', $query);
                        $videoId = $query['v'] ?? null;
                        if ($videoId) {
                            $embedUrl = 'https://www.youtube.com/embed/' . $videoId;
                        }
                    }
                @endphp
                <div class="position-relative video-wrapper-section">
                    <iframe width="100%" height="auto" src="{{ $embedUrl }}" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            @elseif(isset($videoSection) && $videoSection->video_type === 'mp4' && $videoSection->video_url)
                <!-- MP4 Video -->
                <div class="position-relative video-wrapper-section">
                    <video width="100%" height="auto" controls >
                        <source src="{{ $videoSection->video_url }}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @else
                <!-- Fallback: Show image with play button -->
                <div class="position-relative video-wrapper-section">
                    <img src="{{ isset($videoSection) && $videoSection->video_img ? asset('storage/'.$videoSection->video_img) : asset('img/frontend/video-image.png') }}" class="rounded w-100 object-fit-cover" onerror="this.onerror=null;this.src='{{ asset('img/frontend/video-image.png') }}';">
                    <div class="expert-popup-video">
                        <div class="expert-video-icon position-absolute ">
                            <div class="play-button-outer avatar-76">
                                <div class="bg-gray-900 d-inline-block export-video position-absolute text-center avatar-60 rounded-pill">
                                    <a class="d-block" href="https://www.youtube.com/watch?v=VeDdpy4CdeMhttps://www.youtube.com/watch?v=urPq7Qq0lXk">
                                        <i class="ph-fill ph-play text-primary exports-icon"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>