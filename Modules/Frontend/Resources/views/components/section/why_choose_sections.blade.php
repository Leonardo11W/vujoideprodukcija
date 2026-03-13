<div class="export-section-spacing section-spacing-bottom">
    <div class="container-fluid">
        <div class="why-choose-section-wrapper bg-orange-subtle p-5 rounded">
            <div class="row align-items-center gy-4">
                <div class="col-xl-5 col-lg-6">
                    <div class="section-title">
                        <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">
                            {{ $whyChoose?->subtitle ?? 'why frezka' }}
                        </span>
                        <h4 class="title">{{ $whyChoose?->title ?? 'Why Choose Frezka' }}</h4>
                        <p class="mb-0 mt-2 text-body">{{ $whyChoose?->description ?? 'With an intuitive booking system, expert selection, & exclusive offers, our all-in-one platform ensures seamless operations while enhancing customer loyalty.' }}</p>
                    </div>
                    @php
                        use Modules\FrontendSetting\Models\WhyChooseFeature;
                        $features = [];
                        if (!empty($whyChoose)) {
                            $features = WhyChooseFeature::where('why_choose_id', $whyChoose->id)->get();
                        }
                    @endphp
                    <div class="row gy-3">
                        @if(!empty($features) && $features->count())
                            @foreach($features as $idx => $feature)
                                <div class="col-xxl-4 col-lg-6">
                                    <div class="choose-us-card bg-gray-900 rounded">
                                        @if($feature->image)
                                            <img src="{{ asset('storage/' . $feature->image) }}" alt="feature image" class="rounded img-fluid object-cover choose-img">

                                        @else
                                            @if($idx === 0)
                                                <i class="ph ph-alarm fs-3 mb-0 icon-color"></i>
                                            @elseif($idx === 1)
                                                <i class="ph ph-user-check fs-3 mb-0 icon-color"></i>
                                            @elseif($idx === 2)
                                                <i class="ph ph-trend-up fs-3 mb-0 icon-color"></i>
                                            @endif
                                        @endif
                                        <h6 class="title-text mb-0">{{ $feature->title }}</h6>
                                        <!-- @if($feature->subtitle)
                                            <p class="mb-0 mt-1 small text-body">{{ $feature->subtitle }}</p>
                                        @endif -->
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="col-xxl-4 col-lg-6">
                                <div class="choose-us-card bg-gray-900 rounded">
                                    <i class="ph ph-alarm h3 mb-0 icon-color"></i>
                                    <h6 class="mt-lg-5 mt-3 mb-0">Quick & Easy Booking</h6>
                                </div>
                            </div>
                            <div class="col-xxl-4 col-lg-6">
                                <div class="choose-us-card bg-gray-900 rounded">
                                    <i class="ph ph-user-check h3 mb-0 icon-color"></i>
                                    <h6 class="mt-lg-5 mt-3 mb-0">Enhance Client Satisfaction</h6>
                                </div>
                            </div>
                            <div class="col-xxl-4 col-lg-12">
                                <div class="choose-us-card bg-gray-900 rounded">
                                    <i class="ph ph-trend-up h3 mb-0 icon-color"></i>
                                    <h6 class="mt-lg-5 mt-3 mb-0">Discover trends with analytics</h6>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-xl-1 d-xl-block d-none"></div>
                <div class="col-xl-6 col-lg-6">
                    @if(!empty($whyChoose?->image))
                    <img src="{{ asset('storage/' . $whyChoose->image) }}"
                        alt="why choose us"
                        class="why-choose-us-img rounded object-fit-cover w-100">
                    @else
                        <img src="{{ asset('img/frontend/why-choose-us.png') }}" alt="why choose us" class="why-choose-us-img rounded object-fit-cover w-100">
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@if($whyChoose && $whyChoose->features && $whyChoose->features->count())
    <div class="row">
        @foreach($whyChoose->features as $feature)
            <div class="col-md-4 mb-4">
                <div class="feature-card text-center">
                    @if(!empty($feature->image))
                        <img src="{{ asset('storage/' . $feature->image) }}"
                             alt="{{ $feature->title ?? 'Feature' }}"
                             class="feature-img rounded object-fit-cover avatar-100">
                    @else
                        <img src="{{ asset('img/frontend/why-choose-feature.png') }}"
                             alt="Default Feature"
                             class="feature-img rounded object-fit-cover avatar-100">
                    @endif
                    <h5 class="mt-2">{{ $feature->title ?? '' }}</h5>
                    <p>{{ $feature->description ?? '' }}</p>
                </div>
            </div>
        @endforeach
    </div>
@endif