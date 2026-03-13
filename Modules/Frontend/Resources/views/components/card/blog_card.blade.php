@props(['blog'])

<div class="blog-card">
    <div class="blog-card-image">
    <a href="{{ route('blog-details', ['id' => $blog->id]) }}" class="d-block" >
        <img src="{{ $blog->image ? asset($blog->image) : asset('img/frontend/blog.jpg') }}" alt="Blog" class="img-fluid">
    
    </a>
    </div>
    <div class="blog-card-content">
        <ul class="blog-card-meta d-flex flex-wrap align-items-center gap-3">
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
        <div class="blog-card-title">
            <h5 class="mb-0 line-count-2">
                <a href="{{ route('blog-details', ['id' => $blog->id]) }}">{{ $blog->title }}</a>
            </h5>
            <!-- <div class="blog-card-description">
                {{ \Illuminate\Support\Str::limit(strip_tags($blog->description), 150) }}
            </div> -->
            <br><div>
                <span>{{__('frontend.words_by')}}</span>
                            <h6 class="m-0 text-primary text-uppercase text-decoration-underline">
                                {{ optional($blog->author)->first_name . ' ' . optional($blog->author)->last_name ?? 'Unknown' }}
                            </h6>
</div>
        </div>
        <!-- <div class="blog-card-excerpt">
            <span>words by</span>
            <h6 class="m-0 text-primary text-uppercase text-decoration-underline">
                {{ optional($blog->author)->first_name . ' ' . optional($blog->author)->last_name ?? 'Unknown' }}
            </h6>
        </div> -->
        @if(isset($blog->tags))
            <div class="blog-card-tags">
                @if(is_array($blog->tags))
                    {{ implode(', ', $blog->tags) }}
                @else
                    {{ $blog->tags }}
                @endif
            </div>
        @endif
    </div>
</div>
