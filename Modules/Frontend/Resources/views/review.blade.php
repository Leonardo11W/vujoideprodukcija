@extends('frontend::layouts.master')

@section('content')


<div class="section-spacing-inner-page">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-4">
            <h4 class="m-0">Reviews</h5>
            <select class="form-select select2" aria-label="Default select example">
                <option selected>Sort</option>
                <option value="1">One</option>
                <option value="2">Two</option>
                <option value="3">Three</option>
            </select>
        </div>
        <ul class="list-unstyled mb-0 mt-5">
            <li class="expert-review-card">
                <div class="d-flex column-gap-4 row-gap-2 flex-sm-row flex-column mb-4">
                    <!-- Profile Image Column -->
                    <div class="avatar-wrapper">
                        <img src="{{asset('img/frontend/export-image.jpg')}}" alt="review img" class="expert-review-img rounded-pill">
                    </div>
                    <!-- Info Column -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-1 flex-wrap">
                            <div>
                                <div>
                                    <h5 class="mb-3">Eruv Jonas</h5>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph ph-star text-warning"></i>
                                    </div>
                                </div>
                            </div>
                            <span class="fw-medium font-size-14">Apr 28,2024</span>
                        </div>
                    </div>
                </div>
                <span>“ It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using  Content here.”</span>
            </li>
            <li class="expert-review-card">
                <div class="d-flex column-gap-4 row-gap-2 flex-sm-row flex-column mb-4">
                    <!-- Profile Image Column -->
                    <div class="avatar-wrapper">
                        <img src="{{asset('img/frontend/export-image.jpg')}}" alt="review img" class="expert-review-img rounded-pill">
                    </div>
                    <!-- Info Column -->
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-start justify-content-between gap-1 flex-wrap">
                            <div>
                                <div>
                                    <h5 class="mb-3">Eruv Jonas</h5>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph-fill ph-star text-warning"></i>
                                        <i class="ph ph-star text-warning"></i>
                                    </div>
                                </div>
                            </div>
                            <span class="fw-medium font-size-14">Apr 28,2024</span>
                        </div>
                    </div>
                </div>
                <span>Integer luctus vulputate mauris. Maecenas faucibus sed euismod rutrum. Integer facilisis pulvinar ultrices. erat orci, pellentesque id eros ut vulputate rutrum augue.</span>
            </li>
        </ul>
    </div>
</div>

@endsection