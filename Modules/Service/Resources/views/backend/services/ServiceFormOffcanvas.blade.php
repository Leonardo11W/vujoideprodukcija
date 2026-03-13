<!-- resources/views/service_form.blade.php -->
<form action="{{ isset($service) ? route('services.update', $service->id) : route('services.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($service))
        @method('PUT')
    @endif

    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel">
        <!-- Form Header (replace with your own header if needed) -->
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="form-offcanvasLabel">
                {{ isset($service) ? __('Edit Service') : __('Create Service') }}
            </h5>
        </div>
        <div class="offcanvas-body">
            <!-- Feature Image Upload -->
            <div class="form-group">
                <div class="text-center">
                    <img src="{{ old('feature_image', $service->feature_image ?? 'https://dummyimage.com/600x300/cfcfcf/000000.png') }}" alt="feature-image" class="img-fluid mb-2 avatar-140 avatar-rounded" id="feature-image-preview" />
                    @if($errors->has('feature_image'))
                        <div class="text-danger mb-2">{{ $errors->first('feature_image') }}</div>
                    @endif
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <input type="file" class="form-control d-none" id="feature_image" name="feature_image" accept=".jpeg, .jpg, .png, .gif" onchange="previewImage(event)" />
                        <label class="btn btn-info" for="feature_image">{{ __('Upload') }}</label>
                        <button type="button" class="btn btn-danger" onclick="removeImage()" id="remove-image-btn" style="display:none;">{{ __('Remove') }}</button>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div class="form-group col-md-12">
                <label for="name">{{ __('Service Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $service->name ?? '') }}" required>
                @error('name')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Duration (min) -->
            <div class="form-group col-md-12">
                <label for="duration_min">{{ __('Duration (min)') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="duration_min" id="duration_min" value="{{ old('duration_min', $service->duration_min ?? '') }}" required>
                @error('duration_min')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Default Price -->
            <div class="form-group col-md-12">
                <label for="default_price">{{ __('Default Price') }} ({{ config('app.currency_symbol', '$') }}) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="default_price" id="default_price" value="{{ old('default_price', $service->default_price ?? '') }}" required>
                @error('default_price')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Category -->
            <div class="form-group">
                <label for="category_id">{{ __('Category') }} <span class="text-danger">*</span></label>
                <select class="form-control" name="category_id" id="category_id" required>
                    <option value="">{{ __('Select Category') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $service->category_id ?? '') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Sub Category (optional, show if available) -->
            @if(!empty($subCategories))
            <div class="form-group">
                <label for="sub_category_id">{{ __('Sub Category') }}</label>
                <select class="form-control" name="sub_category_id" id="sub_category_id">
                    <option value="">{{ __('Select Subcategory') }}</option>
                    @foreach($subCategories as $subCategory)
                        <option value="{{ $subCategory->id }}" {{ old('sub_category_id', $service->sub_category_id ?? '') == $subCategory->id ? 'selected' : '' }}>
                            {{ $subCategory->name }}
                        </option>
                    @endforeach
                </select>
                @error('sub_category_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            @endif

            <!-- Custom Fields (if any) -->
            @if(!empty($customFields))
                @foreach($customFields as $field)
                    <div class="form-group">
                        <label for="custom_{{ $field->id }}">{{ $field->label }}{{ $field->required ? ' *' : '' }}</label>
                        @if($field->type === 'text')
                            <input type="text" class="form-control" name="custom_fields[{{ $field->id }}]" id="custom_{{ $field->id }}" value="{{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') }}" {{ $field->required ? 'required' : '' }}>
                        @elseif($field->type === 'select')
                            <select class="form-control" name="custom_fields[{{ $field->id }}]" id="custom_{{ $field->id }}" {{ $field->required ? 'required' : '' }}>
                                <option value="">{{ __('Select') }}</option>
                                @foreach($field->options as $option)
                                    <option value="{{ $option }}" {{ old('custom_fields.'.$field->id, $service->custom_fields[$field->id] ?? '') == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        @endif
                        @error('custom_fields.'.$field->id)
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                @endforeach
            @endif

            <!-- Description -->
            <div class="form-group col-md-12">
                <label for="description">{{ __('Description') }}</label>
                <textarea class="form-control" name="description" id="description">{{ old('description', $service->description ?? '') }}</textarea>
                @error('description')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            <!-- Status -->
            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="status">{{ __('Status') }}</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="status" id="status" value="1" {{ old('status', $service->status ?? 1) ? 'checked' : '' }}>
                    </div>
                </div>
            </div>
        </div>
        <!-- Form Footer (replace with your own footer if needed) -->
        <div class="offcanvas-footer">
            <button type="submit" class="btn btn-primary">{{ isset($service) ? __('Update') : __('Create') }}</button>
        </div>
    </div>
</form>

<script>
function previewImage(event) {
    const reader = new FileReader();
    reader.onload = function(){
        document.getElementById('feature-image-preview').src = reader.result;
        document.getElementById('remove-image-btn').style.display = 'inline-block';
    };
    reader.readAsDataURL(event.target.files[0]);
}
function removeImage() {
    document.getElementById('feature-image-preview').src = 'https://dummyimage.com/600x300/cfcfcf/000000.png';
    document.getElementById('feature_image').value = '';
    document.getElementById('remove-image-btn').style.display = 'none';
}
</script>