@props(['package'])

<div class="pricing-card rounded-3">
    {{-- @if($package->discount)
        <span class="badge text-bg-success package-discount flex-shrink-0 font-size-14 py-1 lh-base px-3">
            {{ $package->discount }}% OFF
        </span>
    @endif --}}
     <span class="badge text-bg-success package-discount flex-shrink-0 font-size-14 py-1 lh-base px-3">{{__('frontend.10_off')}}</span>

    <div class="d-flex flex-wrap align-items-center column-gap-3 row-gap-2 package-wrap">
        <span class="badge rounded-pill package-badge fw-bold font-size-10 px-3 py-1 lh-base">
            {{ strtoupper($package->tagline ?? __('frontend.trendy_trims')) }}
        </span>
        <h5 class="package-title m-0">{{ $package->name }}</h5>
    </div>

    <p class="mb-5">{{ $package->description }}</p>

    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div class="flex-grow-1">
            <span class="d-flex align-items-center gap-3 mb-2">
                <h4 class="package-price m-0 text-primary">${{ $package->package_price }}</h4>
                {{-- @if($package->original_price)
                    <del class="fw-semibold">${{ $package->original_price }}</del>
                @endif --}}
                <del class="fw-semibold">$3150</del>
            </span>
        </div>
        <span class="package-duration">{{__('frontend.3_months')}}</span>
    </div>

    <a href="{{ route('package-checkout', $package->id) }}" class="btn btn-secondary w-100 buy-btn">{{__('frontend.purchase_now')}}</a>

    <div>
        <h6 class="package-included-title">{{__('frontend.whats_included')}}:</h6>
        <ul class="list-unstyled m-0 package-included-list">
            @foreach($package->items as $item)
                <li>
                    <span class="package-check"><i class="ph ph-check icon-color"></i></span>
                    <span class="flex-grow-1">{{ $item->service_name }}</span>
                    <span>Qty:<span class="package-qty">{{ $item->qty }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
