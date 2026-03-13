@props(['showBannerSection' => null,'enable_search' => 1, 'sliders' => [], 'title' => null, 'description' => null])


@if($sliders->count() > 0)
<div class="banner-section-wrapper">
    <div class="main-banner">
        <div class="slick-banner">

            @foreach ($sliders as $slider)
            <div class="slick-item">
                <img class="main-banner-image"
                    src="{{ asset(!empty($slider->image_path) ? $slider->image_path : 'img/frontend/main-banner.png') }}"
                    alt="{{ $slider->name }}">
            </div>
            @endforeach
        </div>
    </div>
    <div class="banner-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-9 col-lg-8 col-xl-6">
                    <div class="banner-content-wrapper">
                        @if (isset($sliders) && count($sliders))
                        @foreach ($sliders as $index => $slider)
                        <div class="banner-content-slide {{ $index !== 0 ? 'd-none' : '' }}"
                            data-index="{{ $index }}">
                            <h2 class="text-dark banner-content-title mb-3 mb-lg-5 line-count-2">
                                {{ $title }}
                            </h2>
                            <p class="col-lg-10 p-0 line-count-2">
                                {{ $description }}
                            </p>
                        </div>
                        @endforeach
                        @endif

                        @if($enable_search==1)
                        <div class="col-md-8 p-0 mt-md-5 mt-4">
                            <div class="banner-content-search-box">
                                <form id="search-form" method="get" action="{{ url('search') }}">
                                    <div class="d-flex align-items-center p-2 rounded bg-white gap-2">
                                        <div class="icon">
                                            <i class="ph ph-magnifying-glass align-middle"></i>
                                        </div>
                                        <input type="text" id="search-query" name="query"
                                            class="form-control px-2 py-2 h-auto" placeholder="{{__('frontend.search')}}" value="">
                                        <button type="submit" class="btn btn-secondary"
                                            id="search-button">{{__('frontend.search')}}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endif

<script>
    $(document).ready(function() {
        // Get RTL direction from document - check multiple sources
        const isRTL = document.documentElement.getAttribute('dir') === 'rtl' || 
                     document.documentElement.getAttribute('lang') === 'ar' ||
                     $('html').attr('dir') === 'rtl' ||
                     $('html').attr('lang') === 'ar';
        
        // Destroy existing slider if it exists
        if ($('.slick-banner').hasClass('slick-initialized')) {
            $('.slick-banner').slick('unslick');
        }
        
        $('.slick-banner').slick({
            autoplay: true,
            autoplaySpeed: 3000,
            dots: false,
            arrows: true,
            infinite: true,
            slidesToShow: 1,
            slidesToScroll: 1,
            rtl: isRTL, // Add RTL support
            prevArrow: '<span class="slick-prev"></span>',
            nextArrow: '<span class="slick-next"></span>',
            responsive: [{
                breakpoint: 767,
                settings: {
                    arrows: false,
                    dots: true,
                }
            }]
        });

        function doGlobalSearch() {
            var query = $('#search-query').val().trim();
            if (query.length > 0) {
                window.location.href = 'search?query=' + encodeURIComponent(query);
            }
        }

        // Intercept search form submit
        $('#search-form').on('submit', function(e) {
           
            doGlobalSearch();
          


            // var $msg = $('#search-no-data-msg');
            // if ($msg.length === 0) {
            //     $msg = $('<div id="search-no-data-msg" class="text-danger mt-2"></div>').insertAfter($(
            //         this));
            // }
            // $msg.text('');
            // if (!query) {
            //     $msg.text('Please enter a search term.');
            //     return;
            // }
            // $.get($(this).attr('action'), {
            //     query: query
            // }, function(data) {
            //     // Assume backend returns an array or {results: []}
            //     var hasResults = false;
            //     if (Array.isArray(data) && data.length > 0) hasResults = true;
            //     if (data && data.results && data.results.length > 0) hasResults = true;
            //     if (!hasResults) {
            //         $msg.text('No data found.');
            //     } else {
            //         // Optionally redirect or handle results
            //         window.location.href = $('#search-form').attr('action') + '?query=' +
            //             encodeURIComponent(query);
            //     }
            // }).fail(function() {
            //     $msg.text('No data found.');
            // });
        });
        // Clear the message when the user starts typing
        $('#search-query').on('input', function() {
            $('#search-no-data-msg').text('');
        });
    });
</script>