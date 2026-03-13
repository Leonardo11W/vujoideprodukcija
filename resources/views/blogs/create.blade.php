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
            <div class="d-flex justify-content-between gap-4 flex-wrap mb-4">
                <h4 id="form-offcanvasLabel">{{ isset($blog) ? __('frontend.edit_blog') : __('frontend.create_blog') }}</h4>
                <a href="{{ route('blog.index') }}" class="btn btn-primary">{{ __('frontend.back') }}</a>
            </div>
            <form id="blog-form" enctype="multipart/form-data" method="POST" action="{{ route('blog.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ isset($blog) ? $blog->id : null }}">

                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="title">{{ __('frontend.title') }} <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control"
                                placeholder="{{ __('frontend.enter_title') }}"
                                value="{{ isset($blog) ? $blog->title : '' }}">
                            <span class="error text-danger"></span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="user_id">{{ __('frontend.select_author') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="user_id" name="user_id">
                                <option value="" disabled selected>{{ __('frontend.select_author') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ (isset($blog) && $blog->user_id == $user->id) ? 'selected' : '' }}>
                                        {{ $user->first_name }} {{ $user->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="error text-danger"></span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="image">{{ __('frontend.image') }}</label>
                            <input type="file" id="image" name="image" class="form-control">
                            <span class="error text-danger"></span>
                        </div>
                    </div>

                    <div class="col-md-6 form-group">
                        <label class="form-label">{{ __('frontend.status') }}</label>
                        <div class="form-control form-check form-switch">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-label">{{ __('frontend.status') }}</label>
                                <input class="form-check-input" name="status" type="checkbox"
                                    {{ isset($blog) && $blog->status == 0 ? '-' : 'checked' }}>
                            </div>
                            <span class="error text-danger"></span>
                        </div>
                    </div>

                    <div class="form-group col-12">
                        <label class="form-label" for="description">{{ __('frontend.description') }}</label>
                        <textarea id="descriptiontextarea" name="description">{{ isset($blog) ? $blog->description : '' }}</textarea>
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
    <script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#descriptiontextarea',
            height: 400,
            menubar: true,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table code help wordcount',
                'emoticons',
            ],
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | emoticons | code fullscreen',
            toolbar_mode: 'sliding',
            branding: false,
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
            image_title: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            file_picker_callback: function (cb, value, meta) {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function () {
                    var file = this.files[0];
                    var reader = new FileReader();
                    reader.onload = function () {
                        cb(reader.result, { title: file.name });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            }
        });
    </script>
    <script src="{{ asset('js/jquery.validate.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $.validator.addMethod("imageExtension", function(value, element) {
                if (element.files.length === 0) {
                    return true;
                }
                var allowedExtensions = ["jpg", "jpeg", "png", "gif", "webp","avif"];
                var fileExtension = value.split(".").pop().toLowerCase();
                return allowedExtensions.includes(fileExtension);
            }, "Only image files (jpg, jpeg, png, gif, webp,avif) are allowed.");

            $("#blog-form").validate({
                rules: {
                    title: {
                        required: true,
                    },
                    user_id: {
                        required: true,
                    },
                    image: {
                        imageExtension: true
                    }
                },
                errorElement: "span",
                errorClass: "error text-danger",
                highlight: function(element) {
                    $(element).addClass('is-invalid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('is-invalid');
                },
                submitHandler: function(form) {
                    $(form).find('.error').remove();
                    $(form).trigger("submit");
                },
            });
        });
    </script>
@endpush
