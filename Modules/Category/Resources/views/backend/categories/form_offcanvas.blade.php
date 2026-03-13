<form id="category-form" enctype="multipart/form-data">
  @csrf
  <div id="category-form-root"
       data-store-url="{{ route('backend.categories.store') }}"
       data-update-url-base="{{ url('app/categories') }}"
       data-edit-url-base="{{ url('app/categories') }}"
       data-category-list-url="{{ route('backend.categories.index_list') }}"
       data-default-image="{{ $defaultImage ?? (function_exists('default_feature_image') ? default_feature_image() : '') }}"
       data-create-title="{{ $createTitle ?? __('messages.new').' '.__('category.singular_title') }}"
       data-edit-title="{{ $editTitle ?? __('messages.edit').' '.__('category.singular_title') }}"
       data-is-subcategory="{{ isset($isSubCategory) && $isSubCategory ? '1' : '0' }}"
       data-parent-default="{{ isset($parentDefault) ? (int)$parentDefault : '' }}"
       data-i18n-select="{{ __('messages.select') }}"
       data-i18n-required="{{ __('validation.required', ['attribute' => __('messages.field')]) }}">

    <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="form-offcanvasLabel" style="width: 360px; max-width: 85vw;">
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="form-offcanvasLabel">
          <span id="category-form-title-create"></span>
          <span id="category-form-title-edit" style="display:none;"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>

      <div class="offcanvas-body">
        <div class="row g-4">
          <div class="col-12 d-flex justify-content-center">
            <div class="text-center">
              <img id="category-image-preview" src="" alt="feature-image" class="img-fluid mb-3 avatar-140 avatar-rounded border"/>
              <div class="text-danger mb-2" id="category-image-error"></div>
              <div class="d-flex align-items-center justify-content-center gap-2">
                <input type="file" class="form-control d-none" id="feature_image" name="feature_image" accept=".jpeg, .jpg, .png, .gif" />
                <label class="btn btn-info" for="feature_image">{{ __('messages.upload') }}</label>
                <input type="hidden" name="remove_image" id="remove_image" value="0" />
                <button type="button" class="btn btn-danger" id="remove-category-image-btn" style="display:none;">{{ __('messages.remove') }}</button>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group mb-3">
              <label class="form-label fw-bold">{{ __('category.lbl_name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('category.placeholder_name') }}" />
              <span class="text-danger" data-error="name"></span>
            </div>

            <div class="form-group mb-3" id="parent-category-wrap" style="display:none;">
              <label class="form-label fw-bold" for="parent_id">{{ __('category.lbl_parent_category') }} <span class="text-danger">*</span></label>
              <select class="form-control" id="parent_id" name="parent_id" data-placeholder="{{ __('category.lbl_parent_category') }}"></select>
              <span class="text-danger" data-error="parent_id"></span>
            </div>

            <div class="form-group">
              <div class="d-flex justify-content-between align-items-center">
                <label class="form-label m-0 fw-bold" for="status">{{ __('category.lbl_status') }}</label>
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="status" name="status" value="1" checked>
                </div>
              </div>
            </div>

            <input type="hidden" id="custom_fields_json" name="custom_fields_data" value="{}">
          </div>
        </div>
      </div>

      <div class="offcanvas-footer border-top">
        <div class="d-grid d-md-flex gap-3 p-3">
          <button type="submit" class="btn btn-primary" id="category-submit-btn"><i class="fa-solid fa-floppy-disk mx-2"></i>{{ __('messages.save') }}</button>
          <button class="btn btn-outline-primary" type="button" data-bs-dismiss="offcanvas"><i class="fa-solid fa-angles-left"></i>{{ __('messages.close') }}</button>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
(function() {
  const root = document.getElementById('category-form-root');
  if (!root) return;
  const form = document.getElementById('category-form');
  const submitBtn = document.getElementById('category-submit-btn');
  const maxSizeBytes = 2 * 1024 * 1024;
  let currentId = 0;

  const el = (id) => document.getElementById(id);
  const setError = (name, msg) => {
    const span = document.querySelector(`[data-error="${name}"]`);
    if (span) span.textContent = msg || '';
  };
  const clearErrors = () => document.querySelectorAll('#form-offcanvas [data-error]').forEach(s => s.textContent = '');

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    return res.json();
  }

  async function populateSelect(select, list, valueKey = 'id', labelKey = 'name') {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
      console.warn('jQuery or Select2 not loaded');
      return;
    }
    
    const $select = $(select);
    
    // Completely destroy select2 if already initialized
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
      // Remove any leftover select2 containers
      $select.next('.select2-container').remove();
    }
    
    // Clear and populate options
    select.innerHTML = '';
    const optBlank = document.createElement('option');
    optBlank.value = '';
    const placeholder = select.id === 'parent_id' ? (select.dataset.placeholder || 'Select Category') : (root.dataset.i18nSelect || 'Select');
    optBlank.textContent = placeholder;
    select.appendChild(optBlank);
    
    list.forEach(item => {
      const opt = document.createElement('option');
      opt.value = item[valueKey];
      opt.textContent = item[labelKey];
      select.appendChild(opt);
    });
    
    // Re-initialize select2 after populating options
    try {
      $select.select2({ 
        width: '100%', 
        dropdownParent: $('#form-offcanvas'), 
        placeholder: placeholder,
        allowClear: false
      });
    } catch (error) {
      console.error('Select2 initialization error:', error);
    }
  }

  async function loadParentCategories(selectedId = null) {
    if (root.dataset.isSubcategory !== '1') return;
    const wrap = document.getElementById('parent-category-wrap');
    if (wrap) wrap.style.display = '';
    const list = await fetchJSON(root.dataset.categoryListUrl);
    await populateSelect(el('parent_id'), list, 'id', 'name');
    const def = root.dataset.parentDefault ? Number(root.dataset.parentDefault) : null;
    const toSelect = selectedId ?? def;
    if (toSelect) {
      // Set value using select2 (it's initialized after populateSelect)
      setTimeout(() => {
        $('#parent_id').val(String(toSelect)).trigger('change');
      }, 100);
    }
  }

  function resetForm() {
    form.reset();
    clearErrors();
    currentId = 0;
    el('category-form-title-create').textContent = root.dataset.createTitle || '';
    el('category-form-title-edit').textContent = root.dataset.editTitle || '';
    el('category-form-title-create').style.display = '';
    el('category-form-title-edit').style.display = 'none';
    el('category-image-preview').src = root.dataset.defaultImage || '';
    document.getElementById('remove-category-image-btn').style.display = 'none';

    // Clean up any existing select2 before reloading
    const $parent = $('#parent_id');
    if ($parent.hasClass('select2-hidden-accessible')) {
      $parent.select2('destroy');
      $parent.next('.select2-container').remove();
    }

    if (root.dataset.isSubcategory === '1') loadParentCategories(null);
  }

  function fillForm(data) {
    el('name').value = data.name || '';

    form.elements['status'].checked = !!data.status;
    if (root.dataset.isSubcategory === '1') {
      loadParentCategories(data.parent_id || null);
    }
    el('category-image-preview').src = data.feature_image || root.dataset.defaultImage || '';
    // Only show remove button if an actual image exists (not the default image)
    const hasImage = data.feature_image && data.feature_image !== root.dataset.defaultImage;
    document.getElementById('remove-category-image-btn').style.display = hasImage ? '' : 'none';
    el('category-form-title-create').style.display = 'none';
    el('category-form-title-edit').style.display = '';
  }



  // Image preview & size validation
  el('feature_image').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > maxSizeBytes) {
      document.getElementById('category-image-error').textContent = `File size exceeds 2 MB.`;
      e.target.value = '';
      return;
    }
    document.getElementById('category-image-error').textContent = '';
    const reader = new FileReader();
    reader.onload = () => {
      el('category-image-preview').src = reader.result;
      document.getElementById('remove-category-image-btn').style.display = '';
    };
    reader.readAsDataURL(file);
  });
  document.getElementById('remove-category-image-btn').addEventListener('click', () => {
    el('feature_image').value = '';
    el('remove_image').value = '1';
    el('category-image-preview').src = root.dataset.defaultImage || '';
    document.getElementById('remove-category-image-btn').style.display = 'none';
  });

  // Create/Edit trigger via global custom event
  document.addEventListener('crud_change_id', async (e) => {
    const rawId = e.detail?.form_id;
    const id = Number(rawId) || 0;
    if (!id) {
      resetForm();
      return;
    }
    currentId = id;
    clearErrors();
    const url = `${root.dataset.editUrlBase}/${id}/edit`;
    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const json = await res.json();
      if (json.status && json.data) {
        fillForm(json.data);
      }
    } catch (err) {
      console.error(err);
    }
  });

  function validate() {
    let ok = true;
    clearErrors();
    const requiredMsg = root.dataset.i18nRequired || 'This field is required.';
    if (!el('name').value.trim()) { setError('name', requiredMsg); ok = false; }
    
    // Validate parent category for subcategories
    if (root.dataset.isSubcategory === '1') {
      const parentSelect = el('parent_id');
      if (!parentSelect.value || parentSelect.value === '') {
        setError('parent_id', requiredMsg);
        ok = false;
      }
    }
    
    return ok;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validate()) return;
    submitBtn.disabled = true;
    try {
      const fd = new FormData(form);
      // Ensure remove_image resets to 0 if user did not click remove
      if (!el('feature_image').files.length && el('remove_image').value !== '1') {
        fd.set('remove_image', '0');
      }
      // Ensure status is always sent (0 if unchecked, 1 if checked)
      fd.set('status', form.elements['status'].checked ? '1' : '0');
      const url = currentId ? `${root.dataset.updateUrlBase}/${currentId}` : root.dataset.storeUrl;
      if (currentId) fd.append('_method', 'PUT');
      const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd });
      const json = await res.json();
      if (json.status) {
        if (window.successSnackbar) window.successSnackbar(json.message || 'Saved');
        if (window.renderedDataTable) renderedDataTable.ajax.reload(null, false);
        const inst = bootstrap.Offcanvas.getInstance(document.getElementById('form-offcanvas'));
        if (inst) inst.hide();
        resetForm();
      } else {
        if (window.errorSnackbar) window.errorSnackbar(json.message || 'Error');
        if (json.all_message) {
          Object.entries(json.all_message).forEach(([k, v]) => setError(k, Array.isArray(v) ? v[0] : v));
        }
      }
    } catch (err) {
      console.error(err);
    } finally {
      submitBtn.disabled = false;
    }
  });

  document.addEventListener('DOMContentLoaded', resetForm);
  
  const off = document.getElementById('form-offcanvas');
  off && off.addEventListener('hidden.bs.offcanvas', resetForm);

  // Prevent multiple backdrops from stacking
  function dedupeBackdrops() {
    try {
      const oc = document.querySelectorAll('.offcanvas-backdrop');
      if (oc.length > 1) {
        oc.forEach((el, idx) => { if (idx > 0) el.remove(); });
      }
      const mb = document.querySelectorAll('.modal-backdrop');
      if (mb.length > 1) {
        mb.forEach((el, idx) => { if (idx > 0) el.remove(); });
      }
    } catch (_) { /* ignore */ }
  }
  
  // Clean up backdrops when offcanvas is shown or hidden
  document.addEventListener('shown.bs.offcanvas', () => setTimeout(dedupeBackdrops, 50));
  document.addEventListener('hidden.bs.offcanvas', () => setTimeout(dedupeBackdrops, 50));
})();
</script>


