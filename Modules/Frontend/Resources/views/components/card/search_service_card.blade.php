
<div class="service-card bg-gray-800 p-4 rounded mb-3 cursor-pointer position-relative" 
     id="service-card-{{ $service->id ?? rand() }}" 
     data-service-id="{{ $service->id ?? '' }}"
     onclick="document.getElementById('checkDefault_{{ $service->id ?? rand() }}').click(); event.stopPropagation();">
        @if($service->is_platinum_member ?? false)
            <div class="sevice-card-top-content">
                <span class="font-size-12 fw-semibold bg-orange-subtle border border-orange heading-color px-2 py-1 rounded-pill d-inline-block">
                    <span class="d-flex align-items-center gap-2">
                        <span class="flex-shrink-0">
                            <img src="{{ asset('img/frontend/diamond-premium.svg') }}" alt="diamond-icon" class="img-fluid">
                        </span>
                        <span>{{ $service->platinum_member_text ?? __('frontend.free_for_platinum_members') }}</span>
                    </span>
                </span>
            </div>
        @endif
        @if($service->payment_type_text ?? false)
            <div class="sevice-card-top-content">
                <span class="font-size-12 fw-semibold bg-purple border text-body px-2 py-1 rounded-pill d-inline-block">
                    <span class="d-flex align-items-center gap-2">
                        <span>{{ $service->payment_type_text }}</span>
                    </span>
                </span>
            </div>
        @endif
        @if($service->is_trending ?? false)
            <div class="sevice-card-top-content">
                <span class="product-best-seller badge bg-primary ">{{__('frontend.trending')}}</span>
            </div>
        @endif
        @if($service->is_newest ?? false)
            <div class="sevice-card-top-content">
                <span class="product-new badge bg-success">{{__('frontend.new')}}</span>
            </div>
        @endif
    <div class="service-card-inner cursor-pointer">
        <div class="service-card-image">
            <img src="{{ $service->feature_image ?? asset('img/frontend/hair-wash-service.png') }}" alt="{{ $service->name ?? 'Service Image' }}" class="img-fluid rounded avatar-70">
        </div>
        <div class="service-card-content">
            <div class="d-flex align-items-baseline flex-sm-row flex-column column-gap-2 row-gap-3">
                <div class="d-flex align-items-sm-abseline gap-3 flex-wrap flex-sm-row flex-grow-1">
                    @if($service->category_name ?? ($service->category->name ?? false))
                    <span class="font-size-10 bg-success-subtle  fw-bold rounded-pill px-2 py-1 flex-shrink-0 lh-1">{{ strtoupper($service->category_name ?? $service->category->name) }}</span>
                    @endif
                    <h5 class="mb-0 flex-grow-1 lh-1">{{ $service->name ?? __('frontend.service_name') }}</h5>
                </div>
                <div class="d-flex align-items-baseline justify-content-end row-gap-2 column-gap-4 flex-grow-1">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fw-medium text-primary font-size-21-3 lh-1">{{ Currency::format($service->default_price) }}</span>
                      
                    </div>
                    <div class="form-check" onclick="event.stopPropagation()">
                        <input class="form-check-input form-check-secondary service-checkbox border-1" 
                               type="checkbox" 
                               value="{{ $service->id ?? '' }}" 
                               id="checkDefault_{{ $service->id ?? rand() }}" 
                               data-service-id="{{ $service->id ?? '' }}">
                    </div>
                </div>
            </div>
            <ul class="list-inline mx-0 mb-0 p-0 service-card-info-list mt-sm-2 mt-3">
                @if($service->duration_min ?? false)
                <li>
                    <span class="d-flex align-items-center gap-2">
                        <i class="ph ph-clock"></i>
                        <span class="font-size-14">{{ $service->duration_min }} {{__('frontend.min')}}</span>
                    </span>
                </li>
                @endif
              @if(isset($service->staff_count) && $service->staff_count>0)
             <li>
       
            <span class="d-flex align-items-center gap-2">
                <i class="ph ph-users"></i>
                <span class="font-size-14">{{ $service->staff_count }} {{__('frontend.staff')}}</span>
            </span>
       
            </li>
          @endif

                 @if(!session()->has('selected_branch_id') && isset($service->branches) && !empty($service->branches))
                <li>
                    <span class="d-flex align-items-center gap-2">
                        <i class="ph ph-git-branch"></i>
                        <span class="font-size-14">{{ count($service->branches) }} 

                            @if(count($service->branches) > 1)
                            Branches
                            @else
                            Branch
                            @endif
                    
                    </span>
                    </span>
                </li>
                @endif
            </ul>
        </div>
    </div>
    @if(isset($service->addons) && $service->addons->isNotEmpty())
    <div class="service-card-addons">
        <div class="bg-gray-900 rounded py-2 px-3">
            <a class="d-flex justify-content-between align-items-center gap-3 service-card-addons-collapse collapsed" href="#addons-Collapse-{{ $service->id ?? rand() }}" data-bs-toggle="collapse" aria-expanded="false">
                <span class="flex-shrink-0 fw-medium heading-color">{{__('frontend.add_ons')}}</span>
                <i class="ph ph-caret-down"></i>
            </a>
        </div>
        <div class="bg-gray-900 rounded-bottom px-3 pb-3 service-card-addons-collapse-list collapse" id="addons-Collapse-{{ $service->id ?? rand() }}">
            <ul class="list-inline m-0 p-0 service-addon-data-list">
                @foreach($service->addons as $addon)
                <li>
                    <div class="service-card-inner cursor-pointer" onclick="document.getElementById('checkAddon_{{ $service->id ?? rand() }}_{{ $addon->id ?? rand() }}').click()">
                        <div class="service-card-image">
                            <img src="{{ asset($addon->image_path ?? 'img/frontend/hair-wash-service.png') }}" alt="{{ $addon->name ?? 'Addon Image' }}" class="img-fluid rounded avatar-50">
                        </div>
                        <div class="service-card-content">
                            <div class="d-flex align-items-baseline flex-sm-row flex-column column-gap-2 row-gap-3">
                                <div class="d-flex align-items-sm-abseline gap-3 flex-wrap flex-sm-row flex-grow-1">
                                    <h6 class="mb-0 flex-grow-1 lh-1">{{ $addon->name ?? __('frontend.addon_name') }}</h6>
                                </div>
                                <div class="d-flex align-items-baseline justify-content-end row-gap-2 column-gap-4 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-medium text-primary font-size-18 lh-1">{{ \Currency::format($addon->price ?? 0) }}</span>
                                        @if(isset($addon->original_price) && $addon->original_price > ($addon->price ?? 0))
                                                                                  <span class="fw-medium text-decoration-line-through font-size-14 lh-1">{{ \Currency::format($addon->original_price) }}</span>
                                        @endif
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input form-check-secondary addon-checkbox" type="checkbox" value="{{ $addon->id ?? '' }}" id="checkAddon_{{ $service->id ?? rand() }}_{{ $addon->id ?? rand() }}" data-addon-id="{{ $addon->id ?? '' }}">
                                    </div>
                                </div>
                            </div>
                            <ul class="list-inline mx-0 mb-0 p-0 service-card-info-list mt-sm-2 mt-3">
                                @if($addon->duration_text ?? false)
                                <li>
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="ph ph-clock font-size-14"></i>
                                        <span class="font-size-12">{{ $addon->duration_text }}</span>
                                    </span>
                                </li>
                                @endif
                                @if($addon->staff_info ?? false)
                                <li>
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="ph ph-users font-size-14"></i>
                                        <span class="font-size-12">{{ $addon->staff_info }}</span>
                                    </span>
                                </li>
                                @endif
                                @if($addon->branch_info ?? false)
                                <li>
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="ph ph-git-branch font-size-14"></i>
                                        <span class="font-size-12">{{ $addon->branch_info }}</span>
                                    </span>
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>