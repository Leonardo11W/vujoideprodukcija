@extends('frontend::layouts.master')
@section('title')
{{ __('messages.lbl_faq') }}
@endsection

@section('content')
<x-breadcrumb title="{{ __('frontend.faq') }}" />
<div class="faq-section section-spacing">
    <div class="container">
        <div class="faq-wrapper">
            <div class="accordion" id="faqparent">
                @if(!empty($data['faqs']) && $data['faqs']->count() > 0)
                    @foreach($data['faqs']->sortBy('id') as $key => $value)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $key == 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFaq{{ $value->id }}" aria-expanded="{{ $key == 0 ? 'true' : 'false' }}" aria-controls="collapseFaq{{ $value->id }}">
                                    {{ $value->question }}
                                </button>
                            </h2>
                            <div id="collapseFaq{{ $value->id }}" class="accordion-collapse collapse {{ $key == 0 ? 'show' : '' }}" data-bs-parent="#faqparent">
                                <div class="accordion-body">
                                    {{ $value->answer }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <p class="text-body">{{__('messages.no_FAQs_found')}}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<br><br>
{{-- <x-frontend::section.get_started_section /> --}}
@endsection
