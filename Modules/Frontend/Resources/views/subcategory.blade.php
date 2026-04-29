@extends('frontend::layouts.master')

@section('content')


<div class="section-spacing-inner-pages vujo-page-inner vujo-subcategory-page">
    <div class="container">
        <div class="section-title d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="">
                <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.our_subcategories') }}</span>
                <h4 class="title mb-0">{{ __('messages.subcategory') }}</h4>
            </div>
            <div class="">
                <div class="input-group mb-0">
                    <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="text" class="form-control" placeholder="{{ __('frontend.subcategory_search_placeholder') }}">
                </div>
            </div>
        </div>
        <div class="select-category-service-list-tabs">
            <nav class="select-category-service-list">
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <div class="nav-link select-catgory-service-box active" data-bs-toggle="tab" data-bs-target="#subcategory-1" type="button" role="tab" aria-controls="subcategory-1" aria-selected="true">
                        <x-subcategory_card/> 
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>  
                    </div>
                    <div class="nav-link select-catgory-service-box"  data-bs-toggle="tab" data-bs-target="#subcategory-2" type="button" role="tab" aria-controls="subcategory-2" aria-selected="true">
                        <x-subcategory_card/> 
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>  
                    </div>
                    <div class="nav-link select-catgory-service-box" data-bs-toggle="tab" data-bs-target="#subcategory-3" type="button" role="tab" aria-controls="subcategory-3" aria-selected="true">
                        <x-subcategory_card/>    
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="nav-link select-catgory-service-box" data-bs-toggle="tab" data-bs-target="#subcategory-4" type="button" role="tab" aria-controls="subcategory-4" aria-selected="true">
                        <x-subcategory_card/> 
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>   
                    </div>
                    <div class="nav-link select-catgory-service-box" data-bs-toggle="tab" data-bs-target="#subcategory-5" type="button" role="tab" aria-controls="subcategory-5" aria-selected="true">
                        <x-subcategory_card/> 
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>   
                    </div>
                    <div class="nav-link select-catgory-service-box" data-bs-toggle="tab" data-bs-target="#subcategory-6" type="button" role="tab" aria-controls="subcategory-6" aria-selected="true">
                        <x-subcategory_card/>
                        <span class="active-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M17.25 6.75H6.75V17.25H17.25V6.75Z" fill="white"></path>
                                <path d="M12 2.25C10.0716 2.25 8.18657 2.82183 6.58319 3.89317C4.97982 4.96452 3.73013 6.48726 2.99218 8.26884C2.25422 10.0504 2.06114 12.0108 2.43735 13.9021C2.81355 15.7934 3.74215 17.5307 5.10571 18.8943C6.46928 20.2579 8.20656 21.1865 10.0979 21.5627C11.9892 21.9389 13.9496 21.7458 15.7312 21.0078C17.5127 20.2699 19.0355 19.0202 20.1068 17.4168C21.1782 15.8134 21.75 13.9284 21.75 12C21.7473 9.41498 20.7192 6.93661 18.8913 5.10872C17.0634 3.28084 14.585 2.25273 12 2.25ZM16.2806 10.2806L11.0306 15.5306C10.961 15.6004 10.8783 15.6557 10.7872 15.6934C10.6962 15.7312 10.5986 15.7506 10.5 15.7506C10.4014 15.7506 10.3038 15.7312 10.2128 15.6934C10.1218 15.6557 10.039 15.6004 9.96938 15.5306L7.71938 13.2806C7.57865 13.1399 7.49959 12.949 7.49959 12.75C7.49959 12.551 7.57865 12.3601 7.71938 12.2194C7.86011 12.0786 8.05098 11.9996 8.25 11.9996C8.44903 11.9996 8.6399 12.0786 8.78063 12.2194L10.5 13.9397L15.2194 9.21938C15.2891 9.14969 15.3718 9.09442 15.4628 9.05671C15.5539 9.01899 15.6515 8.99958 15.75 8.99958C15.8486 8.99958 15.9461 9.01899 16.0372 9.05671C16.1282 9.09442 16.2109 9.14969 16.2806 9.21938C16.3503 9.28906 16.4056 9.37178 16.4433 9.46283C16.481 9.55387 16.5004 9.65145 16.5004 9.75C16.5004 9.84855 16.481 9.94613 16.4433 10.0372C16.4056 10.1282 16.3503 10.2109 16.2806 10.2806Z" fill="#09954D"></path>
                            </svg>
                        </span>    
                    </div>
                </div>
            </nav>
            <div class="tab-content">
                <div class="tab-pane fade active show" id="subcategory-1" role="tabpanel" aria-labelledby="subcategory-1-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="subcategory-2" role="tabpanel" aria-labelledby="subcategory-2-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="subcategory-3" role="tabpanel" aria-labelledby="subcategory-3-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="subcategory-4" role="tabpanel" aria-labelledby="subcategory-4-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="subcategory-5" role="tabpanel" aria-labelledby="subcategory-5-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="subcategory-6" role="tabpanel" aria-labelledby="subcategory-6-tab" tabindex="0">
                    <div class="service-data">
                        <div class="section-title">
                            <h5>Hair Cut</h5>
                        </div>
                        <div class="row row-cols-1 row-cols-lg-2 gy-4">
                            @foreach($services as $service)
                                <div class="col">
                                    <x-service_card :service="$service" />
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-center mt-5">
                            <button class="btn btn-secondary mt-4">Load More</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





@endsection