<div class="faq-section section-spacing-bottom">
    <div class="container">
        <div class="section-title text-center">
            <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{__('frontend.faq')}}</span>
            <h4 class="title">{{__('frontend.frequently_asked_questions')}}</h4>
            <p class="mb-0 mt-2">{{__('frontend.ask_anythings_related_to_frezka_and_we_will_provide_you_your_query_with_a_solution')}}</p>
        </div>
        <div class="row">
            <div class="col-xl-1 d-xl-block d-none"></div>
            <div class="col-xl-10">
                <div class="faq-wrapper">
                    <div class="accordion" id="faqparent">
                        @if(!empty($faqs) && $faqs->count() > 0)
                            @foreach($faqs->sortBy('id') as $key => $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq{{ $faq->id }}" aria-expanded="{{ $key == 0 ? 'true' : 'false' }}" aria-controls="collapseFaq{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h2>
                                    <div id="collapseFaq{{ $faq->id }}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#faqparent">
                                        <div class="accordion-body">
                                            {{ $faq->answer }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <p class="text-body">{{__('frontend.no_faqs_found_if_you_have_a_question_please_dont_hesitate_to_contact_us')}}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-1 d-xl-block d-none"></div>
        </div>
        @if(!empty($faqs) && $faqs->count() > 0)
        <div class="text-center">
            <a href="{{ route('faq') }}" class="btn btn-secondary">{{__('frontend.view_all')}}</a>
        </div>
        @endif
    </div>
</div>

