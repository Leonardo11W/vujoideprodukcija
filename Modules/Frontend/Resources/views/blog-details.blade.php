@extends('frontend::layouts.master')

@section('title', $blog->title)

@section('content')
    <x-breadcrumb title="{{ __('frontend.blog_details') }}" />
    <div class="blog-details section-spacing-inner-pages">
        <div class="container">
            <div class="blog-card blod-single-card">
                <div class="blog-image">
                    <img src="{{ $blog->image ? asset($blog->image) : asset('img/frontend/blog.jpg') }}" alt="blog-details" class="img-fluid w-100 blog-details-img">
                </div>
                <div class="blog-card-content">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <ul class="blog-card-meta d-flex flex-wrap align-items-center gap-3 m-0">
                            <li class="blog-category">
                                @if(is_object($blog->category))
                                    {{ $blog->category->name ?? 'Wellness & Relaxation' }}
                                @elseif(is_array($blog->category))
                                    {{ $blog->category['name'] ?? 'Wellness & Relaxation' }}
                                @else
                                    {{ 'Wellness & Relaxation' }}
                                @endif
                            </li>
                            <li class="blog-date">{{ $blog->created_at ? $blog->created_at->format('jS M Y') : '' }}</li>
                        </ul>
                        <div class="blog-card-excerpt d-flex flex-wrap align-items-center gap-2">
                            <span>{{__("frontend.words_by")}}</span>
                            <h6 class="m-0 text-primary text-uppercase text-decoration-underline">
                                {{ optional($blog->author)->first_name . ' ' . optional($blog->author)->last_name ?? 'Unknown' }}
                            </h6>
                        </div>
                    </div>
                    <div class="blog-card-title">
                        <h3 class="mb-0 line-count-2">{{ $blog->title }}</h3>
                    </div>
                    <div class="extra-data">
                        {!! $blog->description !!}
                    </div>
                    <div class="blog-pagination">
                        <div class="row justify-content-between">
                            @if($previous_blog)
                                <div class="col-lg-4 col-md-6 blog-pagination-btn">
                                    <a href="{{ route('blog-details', $previous_blog->id) }}" class="link">
                                        <h6 class="link-text">
                                            <i class="ph ph-arrow-left align-middle"></i>
                                            <span>{{__("frontend.previous_post")}}</span>
                                        </h6>
                                        <span class="d-flex align-items-center gap-3">
                                            <img src="{{ $previous_blog->image ? asset($previous_blog->image) : asset('img/frontend/blog.jpg') }}" alt="previous blog image" class="img-fluid rounded avatar avatar-70">
                                            <span class="mb-0 h6 blog-pagination-link line-count-2">{{ $previous_blog->title }}</span>
                                        </span>
                                    </a>
                                </div>
                            @endif
                            @if($next_blog)
                                <div class="col-lg-4 col-md-6 blog-pagination-btn blog-pagination-right active @if(!$previous_blog) ms-auto text-end @endif">
                                    <a href="{{ route('blog-details', $next_blog->id) }}" class="link">
                                        <h6 class="link-text">
                                            <span>{{__("frontend.next_post")}}</span>
                                            <i class="ph ph-arrow-right align-middle"></i>
                                        </h6>
                                        <span class="d-flex align-items-center gap-3">
                                            <img src="{{ $next_blog->image ? asset($next_blog->image) : asset('img/frontend/blog.jpg') }}" alt="next blog image" class="img-fluid rounded avatar avatar-70">
                                            <span class="h6 mb-0 blog-pagination-link line-count-2">{{ $next_blog->title }}</span>
                                        </span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-spacing-top">
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 gy-4">
                    @foreach ($related_blogs->take(3) as $related_blog)
                        <div class="col">
                            @include('frontend::components.card.blog_card', ['blog' => $related_blog])
                        </div>
                    @endforeach
                </div>
                <!-- <div class="mt-5 text-center">
                    <a href="{{ route('blogs') }}" class="btn btn-secondary">View All</a>
                </div> -->
            </div>
        </div>
    </div>
@endsection
