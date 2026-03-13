<!-- Service Edit Form -->
<form action="{{ route('backend.services.update', $service->id) }}" method="POST" enctype="multipart/form-data" id="service-edit-form">
<div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="offcanvasLabel">
        @csrf
        @method('PUT')
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasLabel">{{ __('messages.edit') }} {{ __('service.singular_title') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
        <!-- Feature Image Upload -->
        <div class="form-group">
            <div class="text-center">
                <img src="{{ old('feature_image', $service->feature_image ?? default_feature_image()) }}" alt="feature-image" class="img-fluid mb-2 avatar-140 avatar-rounded" id="edit-feature-image-preview" data-default-src="{{ default_feature_image() }}" data-has-image="{{ $service->feature_image ? '1' : '0' }}" />
                @if($errors->has('feature_image'))
                    <div class="text-danger mb-2">{{ $errors->first('feature_image') }}</div>
                @endif
                <div id="edit-image-validation-error" class="text-danger mb-2" style="display:none;"></div>
                <div class="d-flex align-items-center justify-content-center gap-2">
                    <input type="file" class="form-control d-none" id="edit_feature_image" name="feature_image" accept=".jpeg, .jpg, .png, .gif, image/jpeg, image/jpg, image/png, image/gif" onchange="previewEditImage(event)" />
                    <input type="hidden" name="existing_feature_image" value="{{ $service->feature_image ?? '' }}" id="existing_feature_image" />
                    <label class="btn btn-info" for="edit_feature_image">{{ __('messages.upload') }}</label>
                    <button type="button" class="btn btn-danger" data-role="remove-image" onclick="removeEditImage()" id="remove-edit-image-btn" style="display:none;">{{ __('messages.remove') }}</button>
                </div>
            </div>
        </div>

        <!-- Name -->
        <div class="form-group col-md-12">
            <label for="edit_name">{{ __('service.lbl_name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" id="edit_name" value="{{ old('name', $service->name) }}" placeholder="{{ __('service.enter_name') }}" >
            @error('name')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Duration (min) -->
        <div class="form-group col-md-12">
            <label for="edit_duration_min">{{ __('service.lbl_duration_min') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="duration_min" id="edit_duration_min" value="{{ old('duration_min', $service->duration_min) }}" placeholder="{{ __('service.service_duration') }}" inputmode="numeric" autocomplete="off">
            @error('duration_min')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Default Price -->
        <div class="form-group col-md-12">
            <label for="edit_default_price">{{ __('service.lbl_default_price') }} ({{ config('app.currency_symbol', '$') }}) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="default_price" id="edit_default_price" value="{{ old('default_price', $service->default_price) }}" placeholder="{{ __('service.enter_price') }}" inputmode="decimal" autocomplete="off">
            @error('default_price')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Category -->
        <div class="form-group">
            <label for="edit_category_id">{{ __('service.lbl_category') }} <span class="text-danger">*</span></label>
            <select class="form-control" name="category_id" id="edit_category_id" required onchange="changeEditCategory(this.value)">
                <option value="">{{ __('service.select_category') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $service->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Sub Category -->
        <div class="form-group" id="edit-sub-category-group">
            <label for="edit_sub_category_id">{{ __('service.lbl_sub_category') }}</label>
            <select class="form-control" name="sub_category_id" id="edit_sub_category_id">
                <option value="">{{ __('service.select_subcategory') }}</option>
                @if($service->category_id)
                    @foreach($subcategories as $subcategory)
                        @if($subcategory->parent_id == $service->category_id)
                            <option value="{{ $subcategory->id }}" {{ $service->sub_category_id == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endif
                    @endforeach
                @endif
            </select>
            @error('sub_category_id')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Custom Fields -->
        @if(!empty($customefield))
            @foreach($customefield as $field)
                <div class="form-group">
                    <label for="edit_custom_{{ $field->id }}">{{ $field->label }}{{ $field->required ? ' *' : '' }}</label>
                    @if($field->type === 'text')
                        <input type="text" class="form-control" name="custom_fields[{{ $field->id }}]" id="edit_custom_{{ $field->id }}" value="{{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') }}" {{ $field->required ? 'required' : '' }}>
                    @elseif($field->type === 'select')
                        <select class="form-control" name="custom_fields[{{ $field->id }}]" id="edit_custom_{{ $field->id }}" {{ $field->required ? 'required' : '' }}>
                            <option value="">{{ __('messages.select') }}</option>
                            @if($field->value)
                                @foreach(json_decode($field->value) as $option)
                                    <option value="{{ $option }}" {{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            @endif
                        </select>
                    @elseif($field->type === 'textarea')
                        <textarea class="form-control" name="custom_fields[{{ $field->id }}]" id="edit_custom_{{ $field->id }}" {{ $field->required ? 'required' : '' }}>{{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') }}</textarea>
                    @endif
                    @error('custom_fields.'.$field->id)
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach
        @endif

        <!-- Description -->
        <div class="form-group col-md-12">
            <label for="edit_description">{{ __('service.lbl_description') }}</label>
            <textarea class="form-control" name="description" id="edit_description" placeholder="{{ __('service.description') }}" maxlength="250" oninput="limitCharsAndCount(this, 250)">{{ old('description', $service->description) }}</textarea>
            <small id="description-count" class="form-text text-muted">Characters: 0/250</small>
            @error('description')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <!-- Status -->
        <div class="form-group">
            <div class="d-flex justify-content-between align-items-center">
                <label for="edit_status">{{ __('service.lbl_status') }}</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="status" id="edit_status" value="1" {{ old('status', $service->status) ? 'checked' : '' }}>
                </div>
            </div>
        </div>
    </div>
    
    <div class="offcanvas-footer p-3 border-top">
        <div class="d-grid d-md-flex gap-3 p-3">
            <button type="submit" class="btn btn-primary" id="edit-submit-btn">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                {{ __('messages.update') }}
            </button>
            <button type="button" class="btn btn-outline-primary d-block" data-bs-dismiss="offcanvas">
                <i class="fa-solid fa-angles-left"></i> {{ __('messages.close') }}</button>
        </div>
    </div>
</div>
</form>

<script>
function previewEditImage(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    const errorDiv = document.getElementById('edit-image-validation-error');
    const imagePreview = document.getElementById('edit-feature-image-preview');
    const removeBtn = document.getElementById('remove-edit-image-btn');
    const unifiedImageErrorMsg = '{{ __("messages.only_jpeg_jpg_png_gif_files_are_allowed_maximum_size_2_mb") }}';
    
    // Hide error message initially
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';
    
    // Check if file is selected
    if (!file) {
        return;
    }
    
    // Check if file is an image by MIME type
    const validImageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    const fileType = file.type.toLowerCase();
    
    if (!validImageTypes.includes(fileType)) {
        // Non-image or unsupported image format => show unified message
        errorDiv.textContent = unifiedImageErrorMsg;
        errorDiv.style.display = 'block';
        fileInput.value = ''; // Clear the input
        return;
    }
    
    // Check file size (2MB limit)
    const maxSize = 2 * 1024 * 1024; // 2MB in bytes
    if (file.size > maxSize) {
        errorDiv.textContent = unifiedImageErrorMsg;
        errorDiv.style.display = 'block';
        fileInput.value = ''; // Clear the input
        return;
    }
    
    // Validate file extension
    const fileName = file.name.toLowerCase();
    const validExtensions = ['.jpeg', '.jpg', '.png', '.gif'];
    const hasValidExtension = validExtensions.some(ext => fileName.endsWith(ext));
    
    if (!hasValidExtension) {
        errorDiv.textContent = unifiedImageErrorMsg;
        errorDiv.style.display = 'block';
        fileInput.value = ''; // Clear the input
        return;
    }
    
    // If validation passes, preview the image
    const reader = new FileReader();
    reader.onload = function(){
        imagePreview.src = reader.result;
        removeBtn.style.display = 'inline-block';
        errorDiv.style.display = 'none';
    };
    reader.onerror = function() {
        errorDiv.textContent = '{{ __("messages.error_reading_file") ?? "Error reading file. Please try again." }}';
        errorDiv.style.display = 'block';
        fileInput.value = ''; // Clear the input
    };
    reader.readAsDataURL(file);
}

function removeEditImage() {
    const imagePreview = document.getElementById('edit-feature-image-preview');
    const fileInput = document.getElementById('edit_feature_image');
    const removeBtn = document.getElementById('remove-edit-image-btn');
    const existingImageInput = document.getElementById('existing_feature_image');
    const errorDiv = document.getElementById('edit-image-validation-error');
    
    fileInput.value = '';
    
    if (existingImageInput) {
        existingImageInput.value = '';
    }
    
    imagePreview.src = '{{ default_feature_image() }}';
    
    removeBtn.style.display = 'none';
    
    // Hide any validation errors
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
}

function changeEditCategory(categoryId) {
    const subCategoryGroup = document.getElementById('edit-sub-category-group');
    const subCategorySelect = document.getElementById('edit_sub_category_id');
    
    if (categoryId) {
        // Fetch subcategories for the selected category
        fetch(`{{ route('backend.services.get_subcategories') }}?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                subCategorySelect.innerHTML = '<option value="">{{ __('service.select_subcategory') }}</option>';
                
                if (data.length > 0) {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subCategorySelect.appendChild(option);
                    });
                }
                // Always show the sub category group
                subCategoryGroup.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching subcategories:', error);
                // Still show the sub category group even if there's an error
                subCategoryGroup.style.display = 'block';
            });
    } else {
        // Show all subcategories when no category is selected
        fetch(`{{ route('backend.services.get_subcategories') }}`)
            .then(response => response.json())
            .then(data => {
                subCategorySelect.innerHTML = '<option value="">{{ __('service.select_subcategory') }}</option>';
                
                if (data.length > 0) {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subCategorySelect.appendChild(option);
                    });
                }
                subCategoryGroup.style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching subcategories:', error);
                subCategoryGroup.style.display = 'block';
            });
    }
}

// Edit form submission handler
document.getElementById('service-edit-form').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('edit-submit-btn');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    // Show loading state
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
});

// Character limit and count for description
function limitCharsAndCount(textarea, maxChars) {
    if (textarea.value.length > maxChars) {
        textarea.value = textarea.value.substring(0, maxChars);
    }
    document.getElementById('description-count').textContent = `Characters: ${textarea.value.length}/${maxChars}`;
}

// Initialize counter on page load for edit form
window.addEventListener('DOMContentLoaded', function() {
    const desc = document.getElementById('edit_description');
    if (desc) limitCharsAndCount(desc, 250);
    
    // Initialize image preview and remove button visibility
    const imagePreview = document.getElementById('edit-feature-image-preview');
    const removeBtn = document.getElementById('remove-edit-image-btn');
    const fileInput = document.getElementById('edit_feature_image');
    
    // Show remove button if there's an existing image (not the default image)
    if (imagePreview && removeBtn) {
        const currentSrc = imagePreview.src;
        const defaultSrc = '{{ default_feature_image() }}';
        const hasImage = imagePreview.dataset.hasImage === '1';
        
        // Show remove button only if there's an actual uploaded image
        // Check both the data attribute and the URL
        const isDefaultImage = currentSrc === defaultSrc || 
                              currentSrc.includes('default_feature_image') || 
                              currentSrc.includes('dummyimage.com') ||
                              currentSrc.includes('placeholder') ||
                              !hasImage;
        
        if (currentSrc && !isDefaultImage) {
            removeBtn.style.display = 'inline-block';
        } else {
            removeBtn.style.display = 'none';
        }
    }
    
    // Always show the sub category group on load
    const subCategoryGroup = document.getElementById('edit-sub-category-group');
    if (subCategoryGroup) {
        subCategoryGroup.style.display = 'block';
    }
    
    // Load subcategories based on current category
    const categorySelect = document.getElementById('edit_category_id');
    if (categorySelect && categorySelect.value) {
        changeEditCategory(categorySelect.value);
    } else {
        // Load all subcategories if no category is selected
        changeEditCategory('');
    }
});

// Numeric-only enforcement for duration/price (edit form)
(function(){
    function attachOnce(inputEl, key, fn){
        if(!inputEl) return;
        const flag = `__numGuard_${key}`;
        if(inputEl[flag]) return;
        fn(inputEl);
        inputEl[flag] = true;
    }

    function onlyDigits(inputEl){
        if(!inputEl) return;
        const onKeyDown = (e) => {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            const allowed = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
            if (allowed.includes(e.key)) return;
            if (/^\d$/.test(e.key)) return;
            e.preventDefault();
        };
        const sanitize = () => {
            const v = String(inputEl.value || '');
            const cleaned = v.replace(/[^\d]/g, '');
            if (v !== cleaned) inputEl.value = cleaned;
        };
        inputEl.addEventListener('keydown', onKeyDown);
        inputEl.addEventListener('input', sanitize);
        inputEl.addEventListener('paste', function(){ setTimeout(sanitize, 0); });
        sanitize();
    }

    function onlyDecimal(inputEl){
        if(!inputEl) return;
        const onKeyDown = (e) => {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            const allowed = ['Backspace','Delete','Tab','Escape','Enter','ArrowLeft','ArrowRight','Home','End'];
            if (allowed.includes(e.key)) return;
            if (/^\d$/.test(e.key)) return;
            if (e.key === '.' && !String(inputEl.value || '').includes('.')) return;
            e.preventDefault();
        };
        const sanitize = () => {
            let v = String(inputEl.value || '');
            v = v.replace(/[^\d.]/g, '');
            const parts = v.split('.');
            const cleaned = parts.length <= 1 ? v : (parts[0] + '.' + parts.slice(1).join(''));
            if (inputEl.value !== cleaned) inputEl.value = cleaned;
        };
        inputEl.addEventListener('keydown', onKeyDown);
        inputEl.addEventListener('input', sanitize);
        inputEl.addEventListener('paste', function(){ setTimeout(sanitize, 0); });
        sanitize();
    }

    function attachNumericGuards(){
        attachOnce(document.getElementById('edit_duration_min'), 'digits', onlyDigits);
        attachOnce(document.getElementById('edit_default_price'), 'decimal', onlyDecimal);
    }

    attachNumericGuards();
    document.addEventListener('DOMContentLoaded', attachNumericGuards);

    const oc = document.getElementById('form-offcanvas');
    if (oc) {
        oc.addEventListener('shown.bs.offcanvas', function(){
            setTimeout(attachNumericGuards, 0);
        });
    }
})();
</script> 