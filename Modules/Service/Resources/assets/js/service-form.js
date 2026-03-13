// Service Form JavaScript (robust Offcanvas handling)
document.addEventListener('DOMContentLoaded', function () {
  console.log('service-form.js loaded');

  // ====== GLOBAL STATE ======
  const __appBasePathPrefix = (() => {
    try {
      const pathname = window.location && window.location.pathname ? window.location.pathname : '';
      const idx = pathname.indexOf('/app/');
      return idx !== -1 ? pathname.substring(0, idx) : '';
    } catch (e) {
      return '';
    }
  })();

  function getOffcanvasEl() {
    return document.getElementById('form-offcanvas');
  }

  // Capture BOTH inner & outer at boot so we can fully reconstruct if needed
  const __bootOffcanvasEl = getOffcanvasEl();
  const __blankOffcanvasInnerHTML = __bootOffcanvasEl ? __bootOffcanvasEl.innerHTML : '';
  const __blankOffcanvasOuterHTML = __bootOffcanvasEl ? __bootOffcanvasEl.outerHTML : '';

  // Fallback minimal offcanvas (only used if nothing to restore from)
  function fallbackOffcanvasOuter() {
    return `
      <div class="offcanvas offcanvas-end" tabindex="-1" id="form-offcanvas" aria-labelledby="formOffcanvasLabel">
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="formOffcanvasLabel">Form</h5>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
          <!-- content will be injected -->
        </div>
      </div>`;
  }

  // ====== OFFCANVAS HELPERS ======
  function removeAllOffcanvasBackdrops() {
    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
  }

  function destroyOffcanvasInstance(el) {
    if (!el) return;
    const inst = bootstrap.Offcanvas.getInstance(el);
    if (inst) {
      try { inst.hide(); } catch (e) { }
      try { inst.dispose(); } catch (e) { }
    }
  }

  function ensureOffcanvasExists() {
    let el = getOffcanvasEl();
    if (!el) {
      // Recreate full element from boot snapshot if possible; else fallback
      const html = __blankOffcanvasOuterHTML || fallbackOffcanvasOuter();
      document.body.insertAdjacentHTML('beforeend', html);
      el = getOffcanvasEl();
    }
    return el;
  }

  function resetOffcanvasToBlank() {
    let el = ensureOffcanvasExists();
    // if replaced by server HTML (outer), we still have correct id; just reset inner
    destroyOffcanvasInstance(el);
    el.innerHTML = __blankOffcanvasInnerHTML || `
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body">
        <!-- blank -->
      </div>`;
    return el;
  }

  function hasFormOffcanvasIdInHTML(html) {
    return /id\s*=\s*["']form-offcanvas["']/.test(html);
  }

  function renderOffcanvasHTML(html) {
    // Show the offcanvas from server-rendered HTML safely
    let current = getOffcanvasEl();
    if (!current) {
      // No element? append the server HTML directly if it contains #form-offcanvas
      if (hasFormOffcanvasIdInHTML(html)) {
        document.body.insertAdjacentHTML('beforeend', html);
      } else {
        // Create fallback element and stuff body
        document.body.insertAdjacentHTML('beforeend', __blankOffcanvasOuterHTML || fallbackOffcanvasOuter());
        const el = getOffcanvasEl();
        el.querySelector('.offcanvas-body')?.insertAdjacentHTML('afterbegin', html);
      }
    } else {
      // We do have an element—destroy the instance, then either replace the whole node or set inner
      destroyOffcanvasInstance(current);
      if (hasFormOffcanvasIdInHTML(html)) {
        // Replace entire node to honor server's structure
        $(current).replaceWith(html);
      } else {
        // Only body came—just drop it into existing container body
        current.innerHTML = html;
      }
    }

    // Now show the new element
    const newEl = getOffcanvasEl();
    // Initialize image remove buttons for newly injected content
    try { initOffcanvasImageRemoveButtons(newEl); } catch (_) { }
    removeAllOffcanvasBackdrops();
    const inst = bootstrap.Offcanvas.getOrCreateInstance(newEl);
    // Deduplicate accidental multiple backdrops
    setTimeout(() => {
      dedupeOffcanvasBackdrop();
      inst.show();
    }, 10);
  }

  function dedupeOffcanvasBackdrop() {
    const backdrops = document.querySelectorAll('.offcanvas-backdrop');
    if (backdrops.length > 1) {
      backdrops.forEach((el, idx) => { if (idx < backdrops.length - 1) el.remove(); });
    }
  }

  function showOffcanvasNow(el) {
    removeAllOffcanvasBackdrops();
    const inst = bootstrap.Offcanvas.getOrCreateInstance(el);
    setTimeout(() => { dedupeOffcanvasBackdrop(); inst.show(); }, 10);
  }

  // ====== STATE FLAGS / GUARDS ======
  let offcanvasLoadToken = 0;
  let reopenBlockUntilMs = 0;
  let lastCrudId = null;
  let isClosing = false;

  // Intercept clicks on any trigger that targets the new form offcanvas
  document.addEventListener('click', function (e) {
    // Don't do anything if we're in the middle of closing or in block period
    if (isClosing || Date.now() < reopenBlockUntilMs) {
      return;
    }

    // Ignore clicks on close button or backdrop
    if (e.target.classList.contains('btn-close') ||
      e.target.classList.contains('offcanvas-backdrop') ||
      e.target.hasAttribute('data-bs-dismiss')) {
      return;
    }

    const trigger = e.target && e.target.closest('[data-bs-target="#form-offcanvas"], [data-target="#form-offcanvas"], button[aria-controls="form-offcanvas"]');
    if (trigger) {
      // Prevent the default Bootstrap toggler from running first
      e.preventDefault();
      e.stopPropagation();
      // Reset to blank/new state and show
      const el = resetOffcanvasToBlank();
      showOffcanvasNow(el);
    }
  }, true);

  document.addEventListener('show.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'form-offcanvas') {
      isClosing = false; // Reset closing flag when opening
      dedupeOffcanvasBackdrop();
    }
  });

  document.addEventListener('shown.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'form-offcanvas') {
      try { initOffcanvasImageRemoveButtons(e.target); } catch (_) { }
    }
  });

  document.addEventListener('hide.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'form-offcanvas') {
      isClosing = true; // Set closing flag when starting to close
    }
  });

  document.addEventListener('hidden.bs.offcanvas', function (e) {
    if (e.target && e.target.id === 'form-offcanvas') {
      dedupeOffcanvasBackdrop();
      removeAllOffcanvasBackdrops();

      // Ensure body styles are reset
      document.body.classList.remove('modal-open', 'offcanvas-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';

      // Set block period and reset closing flag after a delay
      reopenBlockUntilMs = Date.now() + 500;
      setTimeout(() => {
        isClosing = false;
      }, 500);
    }
  });

  // ====== EDIT BUTTON HANDLER (AJAX) ======
  $(document).off('click', '[data-service-id]');
  $(document).on('click', '.edit-service-btn', function (e) {
    e.preventDefault();
    const serviceId = $(this).data('id');

    const requestToken = ++offcanvasLoadToken;

    // Derive URL
    const $container = $(this).closest('div');
    const destroyHref = $container.find('a[id^="delete-services-"]').attr('href');
    let url;
    if (destroyHref) {
      url = (destroyHref.replace(/\/?$/, '')) + '/edit-form';
    } else {
      const dynamicUrl = $(this).data('edit-url');
      url = dynamicUrl || `${__appBasePathPrefix}/app/services/${serviceId}/edit-form`;
    }

    // Fetch server form
    $.get(url, function (html) {
      if (requestToken !== offcanvasLoadToken) return;
      renderOffcanvasHTML(html);
    }).fail(function () {
      // Keep offcanvas, show error
      const live = getOffcanvasEl();
      if (live) {
        live.innerHTML = `
          <div class="offcanvas-header">
            <h5 class="offcanvas-title text-danger">Error</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
          </div>
          <div class="offcanvas-body">
            <div class="alert alert-danger">Failed to load edit form.</div>
          </div>`;
        showOffcanvasNow(live);
      } else {
        alert('Failed to load edit form.');
      }
    });
  });

  // ====== NEW / EDIT PROGRAMMATIC (crud_change_id) ======
  document.addEventListener('crud_change_id', function (e) {
    const formId = e.detail.form_id;

    // Don't do anything if we're closing or in block period
    if (isClosing || Date.now() < reopenBlockUntilMs) return;

    // Set a new block period to prevent spam
    reopenBlockUntilMs = Date.now() + 250;

    // Prevent duplicated triggers
    if (formId === lastCrudId) return;
    lastCrudId = formId;

    if (formId === 0 || formId === '0') {
      // NEW
      const el = resetOffcanvasToBlank();
      showOffcanvasNow(el);
    } else {
      // EDIT via programmatic call (uses fetch)
      loadEditForm(formId);
    }
  });

  // ====== EDIT (programmatic helper, uses fetch) ======
  function loadEditForm(serviceId) {
    const requestToken = ++offcanvasLoadToken;

    const el = ensureOffcanvasExists();
    destroyOffcanvasInstance(el);
    el.innerHTML = `
      <div class="offcanvas-header">
        <h5 class="offcanvas-title">Loading...</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
      </div>
      <div class="offcanvas-body text-center">
        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
      </div>`;
    showOffcanvasNow(el);

    fetch(`${__appBasePathPrefix}/app/services/edit-form/${serviceId}`)
      .then(r => r.text())
      .then(html => {
        if (requestToken !== offcanvasLoadToken) return;
        renderOffcanvasHTML(html);
      })
      .catch(err => {
        console.error('Error loading edit form:', err);
        const live = getOffcanvasEl();
        if (live) {
          live.innerHTML = `
            <div class="offcanvas-header">
              <h5 class="offcanvas-title text-danger">Error</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
              <div class="alert alert-danger">Error loading form. Please try again.</div>
            </div>`;
          showOffcanvasNow(live);
        }
      });
  }

  // ====== INSTANT VALIDATION ======
  // Duration field instant validation
  document.addEventListener('input', function (e) {
    if (e.target.name === 'duration_min') {
      validateDurationField(e.target);
    }
  });

  function validateDurationField(input) {
    const value = input.value.trim();
    const form = input.closest('form');

    // Clear previous validation
    input.classList.remove('is-invalid');
    let feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
      feedback.remove();
    }

    // Skip validation if empty (will be caught on submit)
    if (!value) {
      return;
    }

    // Check if it's a valid positive integer
    const numValue = parseFloat(value);
    const isValid = !isNaN(numValue) && numValue > 0 && Number.isInteger(numValue);

    if (!isValid) {
      input.classList.add('is-invalid');
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback d-block';
      feedback.innerText = `Invalid time format. Duration must be a positive whole number (minutes). Reference: http://192.168.1.139:8000/app/branch`;
      input.parentNode.appendChild(feedback);
    }
  }

  // ====== FORM SUBMISSION (unchanged core, with minor safety) ======
  document.addEventListener('submit', function (e) {
    if (e.target.id === 'service-form' || e.target.id === 'service-edit-form') {
      handleFormSubmission(e);
    }
  });

  function handleFormSubmission(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

    e.preventDefault();

    // Clear prev errors
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    // Clear image validation box (we show feature image errors here, not beside buttons)
    const imgErr = form.querySelector('#image-validation-error, #edit-image-validation-error');
    if (imgErr) { imgErr.textContent = ''; imgErr.style.display = 'none'; }

    let ok = true;
    function setError(input, msg) {
      if (!input) return;
      // Special-case the hidden file input: render the message in the dedicated box,
      // not inside the flex row that contains Upload/Remove buttons.
      if ((input.getAttribute('name') || '') === 'feature_image' || input.type === 'file') {
        const imgErr = form.querySelector('#image-validation-error, #edit-image-validation-error');
        if (imgErr) {
          imgErr.innerText = msg;
          imgErr.style.display = 'block';
        }
        return;
      }
      input.classList.add('is-invalid');
      let feedback = input.parentNode.querySelector('.invalid-feedback');
      if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block';
        input.parentNode.appendChild(feedback);
      }
      feedback.innerText = msg;
    }

    const name = form.querySelector('[name="name"]');
    if (!name || !name.value.trim()) { setError(name, 'Name field is required.'); ok = false; }

    const duration = form.querySelector('[name="duration_min"]');
    if (!duration || !duration.value.trim()) {
      setError(duration, 'Duration field is required.');
      ok = false;
    } else {
      // Additional format validation
      const numValue = parseFloat(duration.value.trim());
      const isValid = !isNaN(numValue) && numValue > 0 && Number.isInteger(numValue);
      if (!isValid) {
        setError(duration, `Invalid time format. Duration must be a positive whole number (minutes). Reference: http://192.168.1.139:8000/app/branch`);
        ok = false;
      }
    }

    const price = form.querySelector('[name="default_price"]');
    if (!price || !price.value.trim()) { setError(price, 'Price field is required.'); ok = false; }

    const category = form.querySelector('[name="category_id"]');
    if (!category || !category.value.trim()) { setError(category, 'Category field is required.'); ok = false; }

    form.querySelectorAll('[id^="custom_"]').forEach(input => {
      if (input.hasAttribute('required') && !String(input.value || '').trim()) {
        setError(input, 'This field is required.'); ok = false;
      }
    });

    if (!ok) {
      if (submitBtn) submitBtn.disabled = false;
      if (spinner) spinner.classList.add('d-none');
      return;
    }

    if (submitBtn) submitBtn.disabled = true;
    if (spinner) spinner.classList.remove('d-none');

    const url = form.action;
    const method = form.querySelector('input[name="_method"]')?.value || form.method;
    const formData = new FormData(form);

    fetch(url, {
      method: method.toUpperCase(),
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || ''
      },
      body: formData
    })
      .then(async response => {
        if (submitBtn) submitBtn.disabled = false;
        if (spinner) spinner.classList.add('d-none');

        if (response.ok) {
          // Refresh table / close offcanvas
          if (window.$ && window.renderedDataTable) {
            window.renderedDataTable.ajax.reload(null, false);
          }
          const el = getOffcanvasEl();
          if (el) {
            const inst = bootstrap.Offcanvas.getOrCreateInstance(el);
            inst.hide();
          } else {
            location.reload();
          }
        } else if (response.status === 422) {
          const data = await response.json();
          if (data.errors) {
            for (const [field, messages] of Object.entries(data.errors)) {
              let input = form.querySelector(`[name="${field}"]`);
              if (!input && field.includes('.')) {
                const base = field.replace(/\./g, '_');
                input = form.querySelector(`#${base}`);
              }
              setError(input, messages[0]);
            }
          }
        } else {
          // no summary box; rely on inline field errors
        }
      })
      .catch(() => {
        if (submitBtn) submitBtn.disabled = false;
        if (spinner) spinner.classList.add('d-none');
        // no summary box; rely on inline field errors
      });
  }

  // ====== IMAGE PREVIEW ======

  window.removeImage = function () {
    const evt = window.event || arguments[0];
    const formGroup = evt && evt.target ? evt.target.closest('.form-group') : null;
    const preview = formGroup?.querySelector('img');
    const fileInput = formGroup?.querySelector('input[type="file"]');
    const removeBtn = evt && evt.target ? evt.target : null;
    if (preview) {
      // Prefer explicit default provided by markup
      const defaultSrc = preview.getAttribute('data-default-src') || preview.dataset.defaultSrc || '';
      if (defaultSrc) {
        // Resolve relative to current origin
        try {
          const resolved = new URL(defaultSrc, window.location.origin).href;
          preview.src = resolved;
        } catch (_) {
          preview.src = defaultSrc;
        }
      }
    }
    if (fileInput) fileInput.value = '';
    if (removeBtn) removeBtn.style.display = 'none';
  };

  function shouldShowRemoveForImage(img) {
    if (!img) return false;
    const currentSrc = img.currentSrc || img.src || '';
    const defaultSrc = img.getAttribute('data-default-src') || img.dataset.defaultSrc || '';
    if (!currentSrc) return false;
    if (!defaultSrc) {
      // If no explicit default is provided, only show when server indicates an existing image
      const explicitHasImage = (img.getAttribute('data-has-image') || img.dataset.hasImage || '').toString();
      const group = img.closest('.form-group');
      const groupHasImage = group ? (group.getAttribute('data-has-image') || group?.dataset?.hasImage || '').toString() : '';
      return explicitHasImage === '1' || explicitHasImage.toLowerCase() === 'true' || groupHasImage === '1' || groupHasImage.toLowerCase() === 'true';
    }
    try {
      const cur = new URL(currentSrc, window.location.origin).href;
      const def = new URL(defaultSrc, window.location.origin).href;
      return cur !== def;
    } catch (_) {
      return currentSrc !== defaultSrc;
    }
  }

  function initOffcanvasImageRemoveButtons(context) {
    const root = context || document;
    const groups = root.querySelectorAll('.form-group');
    groups.forEach(group => {
      const img = group.querySelector('img');
      const removeBtn = group.querySelector('[data-role="remove-image"]') || group.querySelector('.btn-danger');
      if (removeBtn) {
        // Strategy 1: Edit form specific ids using hidden existing image value
        const existingInput = root.querySelector('#existing_feature_image');
        const specificRemoveBtn = root.querySelector('#remove-edit-image-btn');
        const specificImg = root.querySelector('#edit-feature-image-preview');
        if (specificRemoveBtn && removeBtn === specificRemoveBtn) {
          const hasExisting = existingInput && existingInput.value && existingInput.value.trim() !== '';
          specificRemoveBtn.style.display = hasExisting ? 'inline-block' : 'none';
          return;
        }

        // Strategy 2: Generic comparison with default or has-image flags
        const show = shouldShowRemoveForImage(img);
        removeBtn.style.display = show ? 'inline-block' : 'none';
      }
    });
  }

  // ====== CATEGORY CHANGE (subcategories) ======
  window.changeCategory = function (selectElement) {
    const categoryId = selectElement.value;
    const formGroup = selectElement.closest('.form-group');
    const subCategoryGroup = formGroup.parentNode.querySelector('#sub-category-group') ||
      formGroup.parentNode.querySelector('#edit-sub-category-group');
    const subCategorySelect = formGroup.parentNode.querySelector('#sub_category_id') ||
      formGroup.parentNode.querySelector('#edit_sub_category_id');

    if (!subCategoryGroup || !subCategorySelect) return;

    if (categoryId) {
      fetch(`${__appBasePathPrefix}/app/services/get-subcategories?category_id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
          subCategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
          if (Array.isArray(data) && data.length > 0) {
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
        .catch(() => {
          // Still show the sub category group even if there's an error
          subCategoryGroup.style.display = 'block';
        });
    } else {
      // Show all subcategories when no category is selected
      fetch(`${__appBasePathPrefix}/app/services/get-subcategories`)
        .then(response => response.json())
        .then(data => {
          subCategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(subcategory => {
              const option = document.createElement('option');
              option.value = subcategory.id;
              option.textContent = subcategory.name;
              subCategorySelect.appendChild(option);
            });
          }
          subCategoryGroup.style.display = 'block';
        })
        .catch(() => {
          subCategoryGroup.style.display = 'block';
        });
    }
  };
});
