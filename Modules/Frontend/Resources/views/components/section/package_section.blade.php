<div class="package-section-wrapper section-spacing">
    <div class="container">
        <div class="row gy-4 flex-wrap-reverse">
            <div class="col-lg-4">
                <div class="branch-card rounded position-relative overflow-hidden">
                    <div class="branch-image position-relative">
                        <img src="{{ asset('img/frontend/branch-image.jpg') }}" class="card-img-top" alt="Bliss & Beauty">
                    </div>
                    <div class="branch-info-box">
                        <h6 class="font-size-18 mb-2 fw-medium">{{__('frontend.download_the_app_from_the_option_below')}}</h6>
                        <div class="d-flex flex-wrap align-items-center gap-3 justify-content-center">
                            <a href="https://play.google.com/store/search?q=frezka&c=apps&hl=en_IN" target="_blank" rel="noopener">
                                <img src="{{ asset('img/frontend/googleplay.png') }}" class="img-fluid position-relative z-9" alt="Google Play">
                            </a>
                            <a href="https://apps.apple.com/us/app/frezka-beauty-salons/id6450424262" target="_blank" rel="noopener">
                                <img src="{{ asset('img/frontend/appstore.png') }}" class="img-fluid position-relative z-9" alt="App Store">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="package-card bg-purple rounded">
                    <div class="section-title text-center">
                        <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.our_packages')}}</span>
                        <h4 class="title mb-0">{{__('frontend.explore_our_exclusive_package_options')}}</h4>
                    </div>
                    @if($packages->count())
                        @php
                            $package = $packages->first();
                            $totalServicePrice = $package->serviceItems->sum(function($service) {
                                return $service->pivot->service_price * $service->pivot->qty;
                            });
                        @endphp
                        <div class="card radient-card">
                            <div class="card-body p-0">
                                <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                    <span class="badge bg-purple text-body border rounded-pill text-uppercase">{{__('frontend.trendy_trims')}}</span>
                                    <h6 class="mb-0 font-size-18">{{ $package->name }}</h6>
                                </div>
                                <p class="font-size-14">{{ $package->description }}</p>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <h5 class="mb-0 text-primary">{{ \Currency::format($package->package_price) }}</h5>
                                        @if($totalServicePrice > $package->package_price)
                                                                                          <h6 class="mb-0 font-size-18 text-body text-decoration-line-through">{{ \Currency::format($totalServicePrice) }}</h6>
                                        @endif
                                        <span>/ {{ $package->package_validity ?? 1 }} month{{ $package->package_validity > 1 ? 's' : '' }}</span>
                                    </div>
                                    @if($totalServicePrice > $package->package_price)
                                        <span class="badge bg-success text-white rounded-pill">{{ round((($totalServicePrice - $package->package_price) / $totalServicePrice) * 100) }}% OFF</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="text-center ">                        
                        <a href="{{ route('package') }}" class="btn btn-secondary" id="search-button">{{__('frontend.view_all')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>