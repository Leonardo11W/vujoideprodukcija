@extends('backend.layouts.app')

@section('title')
    {{ __($module_action) }} {{ __($module_title) }}
@endsection


@push('after-styles')
    <link rel="stylesheet" href="{{ mix('modules/constant/style.css') }}">
@endpush
@section('content')
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-11">
                <h4 id="form-offcanvasLabel">{{ isset($faq) ? __('frontend.edit_faq') : __('frontend.create_faq') }}</h4>
            </div>
            <div class="col-1">
                <a href="{{ route('faq.index')}}" class="btn btn-primary">{{ __('frontend.back') }}</a>
            </div>
        </div>
        <form id="faq-form" enctype="multipart/form-data" method="POST" action="{{ route('faq.store')}}">
            @csrf
            <input type="hidden" name="id" value="{{ isset($faq) ? $faq->id : null }}">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="question">{{ __('frontend.question') }} <span class="text-danger">*</span></label>
                        <input type="text" name="question" id="question" class="form-control" placeholder="{{ __('frontend.enter_question') }}" value="{{ isset($faq) ? $faq->question : '' }}">
                        <span class="error text-danger"></span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label" for="answer">{{ __('frontend.answer') }} <span class="text-danger">*</span></label>
                        <input type="text" name="answer" id="answer" class="form-control" placeholder="{{ __('frontend.enter_answer') }}" value="{{ isset($faq) ? $faq->answer : '' }}">
                        <span class="error text-danger"></span>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6 form-check form-switch">
                <div class="form-group">
                    <label class="form-label">{{ __('frontend.status') }}</label>
                    <input class="form-check-input" name="status" type="checkbox" {{ (isset($faq) && $faq->status == 0) ? '-' : 'checked' }}>
                    <span class="error text-danger"></span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">{{ __('frontend.submit') }}</button>
        </form>
    </div>
</div>

@endsection

@push('after-styles')
    <!-- DataTables Core and Extensions -->
    <link rel="stylesheet" href="{{ asset('vendor/datatable/datatables.min.css') }}">
@endpush

@push('after-scripts')
<script src="{{ asset('js/jquery.validate.min.js') }}"></script>

<script>
$(document).ready(function () {
    $("#faq-form").validate({
        rules: {
            question: {
                required: true,
            },
            answer: {
                required: true,
            },
        },
        errorElement: "span",
        errorClass: "error text-danger",
        highlight: function (element) {
            $(element).addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid');
        },
        submitHandler: function (form) {
            $(form).find('.error').remove();
            $(form).trigger("submit");

        },
    });
});

</script>

@endpush
