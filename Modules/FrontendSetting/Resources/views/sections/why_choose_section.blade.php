<!-- Choose Us Section -->
<div class="row mb-4">
    <div class="col-md-12">

          <div class="row">
                <div class="form-group">
                    <div class="form-check form-switch d-flex justify-content-between align-items-center p-2 border rounded">
                        <div>
                        <h4 class="mb-3 text-start">{{ __('frontend.why_choose') }}</h4>       
                      </div>
                </div>
            </div>
        <div class="settings-box bg-body rounded p-3">
           
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @php
                use Modules\FrontendSetting\Models\WhyChoose;
                // Fetch the latest WhyChoose record
                $whyChoose = \Modules\FrontendSetting\Models\WhyChoose::latest()->first();
                // Fallback seeder data
                $fallback = [
                    'title' => 'Why Choose Frezka',
                    'subtitle' => 'why frezka',
                    'description' => 'With an intuitive booking system, expert selection, & exclusive offers, our all-in-one platform ensures seamless operations while enhancing customer loyalty.',
                    'image' => null,
                    'features' => [
                        [
                            'title' => 'Quick & Easy Booking',
                            'subtitle' => 'Book in seconds',
                            'image' => null,
                        ],
                        [
                            'title' => 'Enhance Client Satisfaction',
                            'subtitle' => 'Delight your clients',
                            'image' => null,
                        ],
                        [
                            'title' => 'Discover trends with analytics',
                            'subtitle' => 'Grow your business',
                            'image' => null,
                        ],
                    ],
                ];
                $data = $whyChoose ? $whyChoose->toArray() : $fallback;
                $features = \Modules\FrontendSetting\Models\WhyChooseFeature::where('why_choose_id', $whyChoose->id)->get();
            @endphp
            <form method="POST" action="{{ route('why_choose_setting.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label d-block ms-5 text-start" for="chooseUs_image">{{ __('frontend.select_image') }}</label>
                            <div class="d-flex flex-column align-items-center">
                                <div class="image-box">
                                    <img class="image-preview img-fluid"
                                        src="{{ isset($data['image']) && $data['image'] ? asset('storage/'.$data['image']) : asset(product_feature_image()) }}"
                                        data-default="{{ asset(product_feature_image()) }}"
                                        style="width: 250px; height: 150px; object-fit: cover;">
                                    <div class="d-flex justify-content-center gap-3 mt-3">
                                        <button type="button" class="btn btn-sm btn-primary upload-image">{{ __('frontend.upload') }}</button>
                                        <!-- <button type="button" class="btn btn-sm btn-danger remove-image">{{ __('frontend.remove') }}</button> -->
                                        <input type="file" class="file-input form-control" name="chooseUs_image" accept="image/*" style="display: none;">                                        <input type="hidden" name="existing_image" value="{{ $data['image'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row gy-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label d-block text-start" for="chooseUs_title">{{ __('frontend.choose_us_title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="chooseUs_title" class="form-control" placeholder="{{ __('frontend.choose_title') }}" value="{{ $data['title'] ?? '' }}">
                                </div>
                            </div>
                            <!-- <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label d-block text-start" for="chooseUs_subtitle">{{ __('frontend.choose_us_subtitle') }}</label>
                                    <input type="text" name="chooseUs_subtitle" class="form-control" placeholder="{{ __('frontend.choose_subtitle') }}" value="{{ $data['subtitle'] ?? '' }}">
                                </div>
                            </div> -->
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label text-start d-block" for="chooseUs_description">{{ __('frontend.choose_us_description') }} <span class="text-danger">*</span></label>
                                    <textarea name="chooseUs_description" class="form-control" rows="3">{{ $data['description'] ?? '' }}</textarea>
                                </div>
                            </div>
                        </div>
                        @if($features && $features->count())
                            <div class="row mt-4">
                                @foreach($features as $feature)
                                    @php
                                        $title = is_array($feature) ? ($feature['title'] ?? '') : ($feature->title ?? '');
                                        $subtitle = is_array($feature) ? ($feature['subtitle'] ?? '') : ($feature->subtitle ?? '');
                                        $image = is_array($feature) ? ($feature['image'] ?? null) : ($feature->image ?? null);
                                    @endphp
                                    <div class="col-md-4 mb-3">
                                        <div class="card card-body text-center feature-card" style="position: relative;" data-feature-id="{{ $feature->id }}">
                                            <button type="button" class="btn btn-danger btn-sm delete-feature-btn" data-feature-id="{{ $feature->id }}" title="Delete" style="position:absolute;top:10px;right:10px;">&times;</button>
                                            @if($image)
                                                <img src="{{ asset('storage/' . $image) }}" alt="{{ $title }}" style="width:120px; height:120px; object-fit:cover; margin-bottom:10px;">
                                            @else
                                                <img src="{{ asset(product_feature_image()) }}" alt="feature image" style="width:120px; height:120px; object-fit:cover; margin-bottom:10px; opacity:0.5;">
                                            @endif
                                            <div class="fw-bold">{{ $title }}</div>
                                            <div class="text-muted">{{ $subtitle }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="mt-3 text-end">
                            <button type="button" class="btn btn-primary" id="add-more-btn">+ Add More</button>
                        </div>
                        <div id="add-more-forms" class="mt-3"></div>
                    </div>
                    
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
            @foreach($features as $feature)
                <form method="POST" action="{{ route('why_choose_feature.delete', $feature->id) }}" class="d-none delete-feature-form" id="delete-feature-form-{{ $feature->id }}">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function() {
    document.querySelectorAll('.upload-image').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            this.closest('.image-box').querySelector('.file-input').click();
        });
    });
    document.querySelectorAll('.file-input').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    input.closest('.image-box').querySelector('.image-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    });
    document.querySelectorAll('.remove-image').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const box = this.closest('.image-box');
            box.querySelector('.image-preview').src = box.querySelector('.image-preview').getAttribute('data-default');
            box.querySelector('.file-input').value = '';
            box.querySelector('input[name="existing_image"]').value = '';
        });
    });
    var addMoreBtn = document.getElementById('add-more-btn');
    var addMoreFormsContainer = document.getElementById('add-more-forms');
    var addMoreCount = 0;
    var maxAddMore = 3;

    addMoreBtn.addEventListener('click', function() {
        if (addMoreCount < maxAddMore) {
            addMoreCount++;
            var formHtml = `
                <div class="card card-body mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" class="form-control" name="add_more_title[]" placeholder="Enter title">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Subtitle</label>
                                <input type="text" class="form-control" name="add_more_subtitle[]" placeholder="Enter subtitle">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Image</label>
                                <input type="file" class="form-control" name="add_more_image[]" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            addMoreFormsContainer.insertAdjacentHTML('beforeend', formHtml);
            if (addMoreCount === maxAddMore) {
                addMoreBtn.disabled = true;
            }
        }
    });
})();

// Robust feature delete, no nested forms
document.querySelectorAll('.delete-feature-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var featureId = this.getAttribute('data-feature-id');
        var delForm = document.getElementById('delete-feature-form-' + featureId);
        Swal.fire({
            title: 'Are you sure?',
            text: 'This feature will be permanently deleted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "{{ __('frontend.yes_delete_it') }}",
            cancelButtonText: "{{ __('frontend.cancel') }}"
        }).then((result) => {
            if (result.isConfirmed && delForm) {
                delForm.submit();
            }
        });
    });
});
</script> 