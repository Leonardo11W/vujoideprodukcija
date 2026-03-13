@props(['rating'])

<div class="testimonial-card position-relative">
    <div class="testimonial-info">
        <p class="font-size-18">
            <i>
                {{ Str::limit($rating->review_msg, 120) }}
                <span class="text-secondary">– {{ $rating->user->full_name ?? __('frontend.happy_customer') }}</span>
            </i>
        </p>

        <div class="testimonial-ratting d-flex align-items-center gap-2">
            <ul class="list-inline p-0 m-0 d-flex align-items-center gap-1 text-warning">
                @for ($i = 1; $i <= 5; $i++)
                    <li>
                        <i class="{{ $i <= $rating->rating ? 'ph-fill ph-star' : 'ph ph-star' }} align-middle"></i>
                    </li>
                @endfor
            </ul>
            <h6 class="mb-0">{{ number_format($rating->rating, 1) }}</h6>
        </div>
    </div>

    <div class="testimonial-user d-flex gap-3">
        <div class="iq-testimonial-avtar">
            <img src="{{ $rating->user ? $rating->user->getFirstMediaUrl('profile_image') : asset('img/frontend/user.png') }}"
                 alt="{{ $rating->user->full_name ?? 'User' }}"
                 class="img-fluid rounded-circle avatar avatar-60"
                 loading="lazy"
                 onerror="this.src='{{ asset('img/frontend/user.png') }}'">
        </div>
        <div class="avtar-name">
            <h5 class="iq-lead mb-0">{{ $rating->user->full_name ?? __('frontend.guest_user') }}</h5>
            <span class="iq-post-meta font-size-14">{{__('frontend.happy_customer')}}</span>
        </div>
    </div>

    <div class="testimonial-quote">
        <img src="{{ asset('img/frontend/testimonial-quote.png') }}" alt="Testimonial" class="img-fluid" loading="lazy">
    </div>
</div>
