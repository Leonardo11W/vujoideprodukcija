<!-- Service Form Offcanvas -->
<form action="{{ isset($service) ? route('backend.services.update', $service->id) : route('backend.services.store') }}" method="POST" enctype="multipart/form-data" id="service-form">
<div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="form-offcanvasLabel">
            {{ isset($service) ? __('messages.edit') . ' ' . __('service.singular_title') : __('messages.new') . ' ' . __('service.singular_title') }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
        @csrf
        @if(isset($service))
            @method('PUT')
        @endif
        <div class="offcanvas-body">
            <!-- Feature Image Upload -->
            <div class="form-group">
                <div class="text-center">
                    <img src="{{ old('feature_image', $service->feature_image ?? default_feature_image()) }}" alt="feature-image" class="img-fluid mb-2 avatar-140 avatar-rounded" id="feature-image-preview" />
                    <div id="image-validation-error" class="text-danger mb-2" style="display:none;"></div>
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <input type="file" class="form-control d-none" id="feature_image" name="feature_image" accept=".jpeg, .jpg, .png, .gif, image/jpeg, image/jpg, image/png, image/gif" onchange="previewImage(event)" />
                        @if(isset($service))
                            <input type="hidden" name="existing_feature_image" value="{{ $service->feature_image ?? '' }}" id="existing_feature_image" />
                        @endif
                        <label class="btn btn-info" for="feature_image">{{ __('messages.upload') }}</label>
                        <button type="button" class="btn btn-danger" onclick="removeImage()" id="remove-image-btn" style="display:none;">{{ __('messages.remove') }}</button>
                    </div>
                </div>
            </div>
            <!-- Name -->
            <div class="form-group col-md-12">
                <label for="name">{{ __('service.lbl_name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $service->name ?? '') }}" placeholder="{{ __('service.enter_name') }}">
                <span class="text-danger" data-error="name"></span>
            </div>
            <!-- Duration (min) -->
            <div class="form-group col-md-12">
                <label for="duration_min">{{ __('service.lbl_duration_min') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="duration_min" id="duration_min" value="{{ old('duration_min', $service->duration_min ?? '') }}" placeholder="{{ __('service.service_duration') }}" inputmode="numeric" autocomplete="off">
                <span class="text-danger" data-error="duration_min"></span>
            </div>
            <!-- Default Price -->
            <div class="form-group col-md-12">
                <label for="default_price">{{ __('service.lbl_default_price') }} ({{ config('app.currency_symbol', '$') }}) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="default_price" id="default_price" value="{{ old('default_price', $service->default_price ?? '') }}" placeholder="{{ __('service.enter_price') }}" inputmode="decimal" autocomplete="off">
                <span class="text-danger" data-error="default_price"></span>
            </div>
            <!-- Category -->
            <div class="form-group">
                <label for="category_id">{{ __('service.lbl_category') }} <span class="text-danger">*</span></label>
                <select class="form-control" name="category_id" id="category_id" onchange="changeCategory(this)">
                    <option value="">{{ __('service.select_category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $service->category_id ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <span class="text-danger" data-error="category_id"></span>
            </div>
            <!-- Sub Category -->
            <div class="form-group" id="sub-category-group">
                <label for="sub_category_id">{{ __('service.lbl_sub_category') }}</label>
                <select class="form-control" name="sub_category_id" id="sub_category_id">
                    <option value="">{{ __('service.select_subcategory') }}</option>
                </select>
            </div>
            <!-- Custom Fields -->
            @if(!empty($customefield))
                @foreach($customefield as $field)
                    <div class="form-group">
                        <label for="custom_{{ $field->id }}">{{ $field->label }}{{ $field->required ? ' *' : '' }}</label>
                        @if($field->type === 'text')
                            <input type="text" class="form-control" name="custom_fields[{{ $field->id }}]" id="custom_{{ $field->id }}" value="{{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') }}" {{ $field->required ? 'required' : '' }}>
                        @elseif($field->type === 'select')
                            <select class="form-control" name="custom_fields[{{ $field->id }}]" id="custom_{{ $field->id }}" {{ $field->required ? 'required' : '' }}>
                                <option value="">{{ __('messages.select') }}</option>
                                @if($field->value)
                                    @foreach(json_decode($field->value) as $option)
                                        <option value="{{ $option }}" {{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                @endif
                            </select>
                        @elseif($field->type === 'textarea')
                            <textarea class="form-control" name="custom_fields[{{ $field->id }}]" id="custom_{{ $field->id }}" {{ $field->required ? 'required' : '' }}>{{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') }}</textarea>
                        @endif
                    </div>
                @endforeach
            @endif
            <!-- Description -->
            <div class="form-group col-md-12">
                <label for="description">{{ __('service.lbl_description') }}</label>
                <textarea class="form-control" name="description" id="description" placeholder="{{ __('service.description') }}" maxlength="250" oninput="limitCharsAndCount(this, 250)">{{ old('description', $service->description ?? '') }}</textarea>
                <small id="description-count" class="form-text text-muted">Characters: 0/250</small>
            </div>
            <!-- Status -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="status">{{ __('service.lbl_status') }}</label>
                    <div class="form-check form-switch">
                        <!-- Hidden input ensures status is always sent (0 when unchecked, 1 when checked) -->
                        <input type="hidden" name="status" value="0">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', $service->status ?? 1) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-footer p-3 border-top">
            <div class="d-grid d-md-flex gap-3 p-3">
                <button type="submit" class="btn btn-primary" id="submit-btn">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    {{ isset($service) ? __('messages.update') : __('messages.create') }}
                </button>
                <button type="button" class="btn btn-outline-primary d-block" data-bs-dismiss="offcanvas">
                <i class="fa-solid fa-angles-left"></i> {{ __('messages.close') }}</button>
            </div>
        </div>
    </div>
</form>

<style>
/* Remove Bootstrap validation exclamation icons */
.form-control.is-invalid,
.form-select.is-invalid {
    background-image: none !important;
    padding-right: 0.75rem !important;
}

/* Make validation error messages bigger */
.invalid-feedback {
    font-size: 14px !important;
    margin-top: 0.5rem !important;
}

span[data-error] {
    font-size: 14px !important;
    display: block !important;
    margin-top: 0.5rem !important;
}
</style>

@push('after-scripts')
<!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->
@endpush

<script>
// Image preview functionality with validation
function previewImage(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    const errorDiv = document.getElementById('image-validation-error');
    const imagePreview = document.getElementById('feature-image-preview');
    const removeBtn = document.getElementById('remove-image-btn');
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

function removeImage() {
    const imagePreview = document.getElementById('feature-image-preview');
    const fileInput = document.getElementById('feature_image');
    const removeBtn = document.getElementById('remove-image-btn');
    const existingImageInput = document.getElementById('existing_feature_image');
    const errorDiv = document.getElementById('image-validation-error');
    
    // Clear the file input
    fileInput.value = '';
    
    // Clear the existing image input to indicate image should be removed (only if it exists)
    if (existingImageInput) {
        existingImageInput.value = '';
    }
    
    // Reset to default image
    imagePreview.src = '{{ default_feature_image() }}';
    
    // Hide the remove button
    removeBtn.style.display = 'none';
    
    // Hide any validation errors
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
}

// Category change handler
function changeCategory(element) {
    const categoryId = element.value;
    const subCategoryGroup = document.getElementById('sub-category-group');
    const subCategorySelect = document.getElementById('sub_category_id');
    
    if (categoryId) {
        // Fetch subcategories for the selected category
        fetch(`{{ route('backend.services.get_subcategories') }}?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                subCategorySelect.innerHTML = `<option value="">${SELECT_SUBCATEGORY_TEXT}</option>`;
                
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
                subCategorySelect.innerHTML = `<option value="">${SELECT_SUBCATEGORY_TEXT}</option>`;
                
                if (data.length > 0) {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subCategorySelect.appendChild(option);
                    });
                }
                subCategoryGroup.style.display = 'block';
                
                // Reinitialize Select2 after updating options
                reinitializeSubcategorySelect2();
            })
            .catch(error => {
                console.error('Error fetching subcategories:', error);
                subCategoryGroup.style.display = 'block';
            });
    }
}

// Form submission handler
document.getElementById('service-form').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submit-btn');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    // Show loading state
    submitBtn.disabled = true;
    spinner.classList.remove('d-none');
    
    // Form will submit normally
});

// Character limit and count for description
function limitCharsAndCount(textarea, maxChars) {
    if (textarea.value.length > maxChars) {
        textarea.value = textarea.value.substring(0, maxChars);
    }
    document.getElementById('description-count').textContent = `Characters: ${textarea.value.length}/${maxChars}`;
}
// Initialize counter on page load
window.addEventListener('DOMContentLoaded', function() {
    window.SELECT_SUBCATEGORY_TEXT = document.getElementById('i18n-select-subcategory')?.value || 'Select Subcategory';
    const desc = document.getElementById('description');
    if (desc) limitCharsAndCount(desc, 250);
    
    // Initialize image preview and remove button visibility
    const imagePreview = document.getElementById('feature-image-preview');
    const removeBtn = document.getElementById('remove-image-btn');
    
    // Show remove button if there's an existing image (not the default image)
    if (imagePreview && removeBtn) {
        const currentSrc = imagePreview.src;
        const defaultSrc = '{{ default_feature_image() }}';
        
        // Show remove button only if current image is not the default image
        // Check both the URL and if it contains default image indicators
        const isDefaultImage = currentSrc === defaultSrc || 
                              currentSrc.includes('default_feature_image') || 
                              currentSrc.includes('dummyimage.com') ||
                              currentSrc.includes('placeholder');
        
        if (currentSrc && !isDefaultImage) {
            removeBtn.style.display = 'inline-block';
        } else {
            removeBtn.style.display = 'none';
        }
    }
});

// Initialize Select2 on category and subcategory dropdowns
function initializeFormSelect2() {
    // Make sure jQuery and Select2 are available
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.select2 === 'undefined') {
        console.error('jQuery or Select2 is not loaded');
        return;
    }

    setTimeout(function() {
        // Initialize Select2 on category dropdown
        const $categorySelect = $('#category_id');
        if ($categorySelect.length && !$categorySelect.hasClass('select2-hidden-accessible')) {
            $categorySelect.select2({
                placeholder: '{{ __('service.select_category') }}',
                allowClear: true,
                dropdownParent: $('#form-offcanvas'),
                width: '100%'
            });

            // Handle Select2 change event for category
            $categorySelect.on('select2:select', function(e) {
                changeCategory(this);
            });

            $categorySelect.on('select2:clear', function(e) {
                this.value = '';
                changeCategory(this);
            });
        }

        // Initialize Select2 on subcategory dropdown
        const $subCategorySelect = $('#sub_category_id');
        if ($subCategorySelect.length && !$subCategorySelect.hasClass('select2-hidden-accessible')) {
            $subCategorySelect.select2({
                placeholder: '{{ __('service.select_subcategory') }}',
                allowClear: true,
                dropdownParent: $('#form-offcanvas'),
                width: '100%'
            });
        }
    }, 200); // Small delay to ensure DOM is ready
}

// Reinitialize Select2 on subcategory when options are updated
function reinitializeSubcategorySelect2() {
    setTimeout(function() {
        const $subCategorySelect = $('#sub_category_id');
        // Destroy existing Select2 instance if it exists
        if ($subCategorySelect.hasClass('select2-hidden-accessible')) {
            $subCategorySelect.select2('destroy');
        }
        // Reinitialize Select2
        $subCategorySelect.select2({
            placeholder: '{{ __('service.select_subcategory') }}',
            allowClear: true,
            dropdownParent: $('#form-offcanvas'),
            width: '100%'
        });
    }, 100);
}

// Initialize Select2 when offcanvas is shown
const formOffcanvas = document.getElementById('form-offcanvas');
if (formOffcanvas) {
    formOffcanvas.addEventListener('shown.bs.offcanvas', function() {
        initializeFormSelect2();
    });
}

// Initialize subcategory on page load
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category_id');
    const subCategoryGroup = document.getElementById('sub-category-group');
    const subCategorySelect = document.getElementById('sub_category_id');

    // Initialize Select2 dropdowns
    initializeFormSelect2();

    // Always show the sub category group
    subCategoryGroup.style.display = 'block';

    // Helper to populate subcategories
    function populateAllSubcategories() {
        fetch(`{{ route('backend.services.get_subcategories') }}`)
            .then(response => response.json())
            .then(data => {
                subCategorySelect.innerHTML = `<option value=\"\">${SELECT_SUBCATEGORY_TEXT}</option>`;
                if (data.length > 0) {
                    data.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.id;
                        option.textContent = subcategory.name;
                        subCategorySelect.appendChild(option);
                    });
                }
                
                // Reinitialize Select2 after populating options
                reinitializeSubcategorySelect2();
            });
    }

    if (categorySelect.value) {
        changeCategory(categorySelect);
    } else {
        // Load all subcategories if no category is selected
        populateAllSubcategories();
    }
    // Auto-open offcanvas when validation errors exist
    // const hasErrors = !!document.querySelector('.invalid-feedback.d-block') || Boolean(@json($errors->any()));
    // if (hasErrors) {
    //     const offcanvasEl = document.getElementById('form-offcanvas');
    //     if (offcanvasEl && window.bootstrap && bootstrap.Offcanvas) {
    //         const offcanvas = new bootstrap.Offcanvas(offcanvasEl);
    //         offcanvas.show();
    //     }
    // }
});

// Reset create form when offcanvas is fully closed
// so that the next "New" open starts clean.
document.addEventListener('hidden.bs.offcanvas', function(e) {
    if (!e.target || e.target.id !== 'form-offcanvas') return;

    const form = document.getElementById('service-form');
    if (!form) return;

    // Reset all native form fields to their initial values
    form.reset();

    // Clear validation styles and messages
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    form.querySelectorAll('span[data-error]').forEach(el => { el.textContent = ''; });

    // Reset category & subcategory selects
    const categorySelect = document.getElementById('category_id');
    const subCategorySelect = document.getElementById('sub_category_id');

    if (categorySelect) categorySelect.value = '';

    if (subCategorySelect) {
        const placeholder = (typeof SELECT_SUBCATEGORY_TEXT !== 'undefined')
            ? SELECT_SUBCATEGORY_TEXT
            : 'Select Subcategory';
        subCategorySelect.innerHTML = `<option value="">${placeholder}</option>`;
    }

    // If Select2 is active, also clear its UI
    if (window.jQuery && jQuery.fn && typeof jQuery.fn.select2 === 'function') {
        if (jQuery('#category_id').length) {
            jQuery('#category_id').val('').trigger('change.select2');
        }
        if (jQuery('#sub_category_id').length) {
            jQuery('#sub_category_id').val('').trigger('change.select2');
        }
    }

    // Reset description text and counter
    const desc = document.getElementById('description');
    if (desc) {
        desc.value = '';
        if (typeof limitCharsAndCount === 'function') {
            limitCharsAndCount(desc, 250);
        }
    }

    // Reset feature image, hidden existing image field, and errors
    const imagePreview = document.getElementById('feature-image-preview');
    const fileInput = document.getElementById('feature_image');
    const existingImageInput = document.getElementById('existing_feature_image');
    const removeBtn = document.getElementById('remove-image-btn');
    const errorDiv = document.getElementById('image-validation-error');

    if (imagePreview) {
        imagePreview.src = '{{ default_feature_image() }}';
    }
    if (fileInput) fileInput.value = '';
    if (existingImageInput) existingImageInput.value = '';
    if (removeBtn) removeBtn.style.display = 'none';
    if (errorDiv) {
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
    }
});

// Numeric-only enforcement for duration/price (create form)
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
            // allow control keys
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
            // keep digits and dot; allow only one dot
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
        attachOnce(document.getElementById('duration_min'), 'digits', onlyDigits);
        attachOnce(document.getElementById('default_price'), 'decimal', onlyDecimal);
    }

    // Run now (covers cases where this HTML is injected after DOMContentLoaded)
    attachNumericGuards();

    // Run on DOM ready (normal full page load)
    document.addEventListener('DOMContentLoaded', attachNumericGuards);

    // Run whenever the offcanvas is shown
    const oc = document.getElementById('form-offcanvas');
    if (oc) {
        oc.addEventListener('shown.bs.offcanvas', function(){
            setTimeout(attachNumericGuards, 0);
        });
    }
})();
</script> 