<div class="location-section">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                @if (setting('bussiness_address_line_1') || setting('bussiness_address_line_2'))
                    <div class="contact-info bg-gray-800 p-3 rounded">

                        <h4>{{ setting('site_name') }} {{ __('frontend.location') }}</h4>
                        <p> {{ __('frontend.find_locations') }}</p>
                        <h6 class="mt-5 pt-lg-3 pt-0">
                            {{ setting('bussiness_address_city') }}
                            @if(setting('bussiness_address_state'))
                                , {{ setting('bussiness_address_state') }}
                            @endif
                            @if(setting('bussiness_address_postal_code'))
                                {{ setting('bussiness_address_postal_code') }}
                            @endif
                            @if(setting('bussiness_address_country'))
                                , {{ setting('bussiness_address_country') }}
                            @endif
                        </h6>
                        @if (setting('bussiness_address_line_1') || setting('bussiness_address_line_2'))
                            <p class="mt-2 mb-0"><span class="heading-color">Address:</span>
                                {{ setting('bussiness_address_line_1') }} {{ setting('bussiness_address_line_2') }}
                                @if(setting('bussiness_address_city'))
                                    , {{ setting('bussiness_address_city') }}
                                @endif
                                @if(setting('bussiness_address_state'))
                                    , {{ setting('bussiness_address_state') }}
                                @endif
                                @if(setting('bussiness_address_postal_code'))
                                    {{ setting('bussiness_address_postal_code') }}
                                @endif
                                @if(setting('bussiness_address_country'))
                                    , {{ setting('bussiness_address_country') }}
                                @endif
                            </p>
                        @endif
                        @if (setting('inquriy_email'))
                            <p class="mt-2 mb-0"><span class="heading-color">Email:</span> {{ setting('inquriy_email') }}
                            </p>
                        @endif
                        @if (setting('helpline_number'))
                            <p class="mt-2 mb-0"><span class="heading-color">Phone:</span>
                                {{ setting('helpline_number') }}</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="col-lg-8 col-md-6">
                @php
                    $lat = setting('bussiness_address_latitude');
                    $lng = setting('bussiness_address_longitude');
                    
                    // If latitude and longitude are available, use them for precise map positioning
                    if (!empty($lat) && !empty($lng)) {
                        $mapQuery = $lat . ',' . $lng;
                    } else {
                        // Fallback to address if coordinates are not available
                        $addressParts = array_filter([
                            setting('bussiness_address_line_1'),
                            setting('bussiness_address_line_2'),
                            setting('bussiness_address_city'),
                            setting('bussiness_address_state'),
                            setting('bussiness_address_postal_code'),
                            setting('bussiness_address_country'),
                        ]);
                        $fullAddress = trim(implode(', ', $addressParts));
                        $mapQuery = $fullAddress !== '' ? $fullAddress : '51.5033,-0.1195'; // Default: London Eye
                    }
                @endphp

                <iframe loading="lazy" width="100%" height="350" class="iframe-map"
                    src="https://maps.google.com/maps?q={{ urlencode($mapQuery) }}&amp;t=m&amp;z=14&amp;output=embed&amp;iwloc=near"
                    title="Business Location" aria-label="Business Location"></iframe>

            </div>
        </div>
    </div>
</div>
