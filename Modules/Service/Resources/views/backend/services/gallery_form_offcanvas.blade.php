<!-- Gallery Form Offcanvas (Blade version) -->
<form id="gallery-form" enctype="multipart/form-data">
<div class="offcanvas offcanvas-end" tabindex="-1" id="service-gallery-form" aria-labelledby="form-offcanvasLabel">
  <div class="offcanvas-header border-bottom">
    <h6 class="m-0 h5">
      {{ __('service.singular_title') }}: <span id="gallery-service-name"></span>
    </h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    @csrf
    @can('edit_service')
    <div class="d-flex flex-column border-bottom p-3" id="gallery-upload-section">
      <div>
        <label class="form-label btn btn-info d-block my-0" for="service_feature_image">{{ __('messages.upload_images') }}</label>
        <input type="file" class="form-control d-none" id="service_feature_image" accept=".jpeg, .jpg, .png, .gif" multiple />
      </div>
    </div>
    @endcan
      <div id="gallery-no-images" class="text-center mb-0">{{ __('messages.data_not_available') }}</div>
      <div id="gallery-images-wrapper" style="display:none;">
        <div class="gallery-images" id="gallery-images-list">
          <!-- Images will be rendered here -->
        </div>
      </div>
    </div>
    <div class="offcanvas-footer">
        <p class="text-center mb-0"><small>{{ __('messages.gallery_for_service') }}</small></p>
        <div class="d-grid gap-3 p-3">
          @can('edit_service')
          <button type="submit" class="btn btn-primary d-block" id="gallery-save-btn"><i class="fa-solid fa-floppy-disk me-2"></i>{{ __('messages.update') }}</button>
          @endcan
          <button class="btn btn-outline-primary d-block" type="button" data-bs-dismiss="offcanvas"><i class="fa-solid fa-angles-left"></i>{{ __('messages.close') }}</button>
        </div>
      </div>
  </div>
</form>

<style>
.gallery-images {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(104px, 1fr));
  grid-gap: 1rem;
  align-items: stretch;
}
.image-container {
  position: relative;
  max-width: 100%;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.delete-button {
  position: absolute;
  top: 0;
  right: 0;
  z-index: 10;
  color: var(--bs-white);
  background-color: var(--bs-danger);
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}
.delete-button:hover {
  background-color: #dc3545;
  transform: scale(1.1);
}
.selected-image {
  object-fit: cover;
  height: 100px;
  width: 100%;
  display: block;
}
</style>

<script>
let galleryImages = [];
let currentServiceId = null;

// Open offcanvas and load images for a service
function openGalleryOffcanvas(serviceId, serviceName) {
  currentServiceId = serviceId;
  document.getElementById('gallery-service-name').textContent = serviceName || '';
  galleryImages = [];
  document.getElementById('gallery-images-list').innerHTML = '';
  document.getElementById('gallery-no-images').style.display = 'block';
  document.getElementById('gallery-images-wrapper').style.display = 'none';
  const fileInput = document.getElementById('service_feature_image');
  if (fileInput) {
    fileInput.value = '';
  }

  // Fetch images from backend
  const currentPath = window.location.pathname;
  const basePath = currentPath.includes('/app/') ? currentPath.split('/app/')[0] : '';
  const listUrl = window.location.origin + basePath + `/app/services/gallery-images/${serviceId}?t=${Date.now()}`;
  fetch(listUrl, { cache: 'no-store' })
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.json();
    })
    .then(data => {
      if (data.status && data.data.length > 0) {
        galleryImages = data.data.map(img => ({
          id: img.id,
          full_url: img.full_url,
          file: null
        }));
        renderGalleryImages();
      } else {
        galleryImages = [];
        renderGalleryImages();
      }
    })
    .catch(error => {
      console.error('Error fetching gallery images:', error);
      galleryImages = [];
      renderGalleryImages();
    });

  // Show the offcanvas (reuse instance and avoid duplicate backdrops)
  try {
    // Hide any other open offcanvas first
    document.querySelectorAll('.offcanvas.show').forEach(function(el) {
      const inst = bootstrap.Offcanvas.getInstance(el);
      if (inst) inst.hide();
    });
    // Remove any extra existing backdrops (keep at most one)
    const backdrops = document.querySelectorAll('.offcanvas-backdrop');
    backdrops.forEach(function(bd, idx) { if (idx > 0 && bd && bd.parentNode) bd.parentNode.removeChild(bd); });
    const offcanvasEl = document.getElementById('service-gallery-form');
    const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl, { backdrop: true, scroll: false });
    offcanvas.show();
  } catch(e) {
    console.warn('Offcanvas open warning:', e);
  }
}

// Reset gallery when offcanvas is hidden to avoid showing previous data
document.addEventListener('hidden.bs.offcanvas', function(e) {
  if (e.target && e.target.id === 'service-gallery-form') {
    galleryImages = [];
    currentServiceId = null;
    const list = document.getElementById('gallery-images-list');
    if (list) list.innerHTML = '';
    const noImages = document.getElementById('gallery-no-images');
    const wrapper = document.getElementById('gallery-images-wrapper');
    if (noImages) noImages.style.display = 'block';
    if (wrapper) wrapper.style.display = 'none';
    const input = document.getElementById('service_feature_image');
    if (input) {
      input.value = '';
    }
    const nameEl = document.getElementById('gallery-service-name');
    if (nameEl) nameEl.textContent = '';

    // Ensure any lingering Bootstrap offcanvas backdrops are removed
    try {
      const backdrops = document.querySelectorAll('.offcanvas-backdrop');
      backdrops.forEach(function(backdropEl) {
        if (backdropEl && backdropEl.parentNode) {
          backdropEl.parentNode.removeChild(backdropEl);
        }
      });
      document.body.classList.remove('offcanvas-backdrop');
      document.body.style.removeProperty('overflow');
      document.body.style.removeProperty('paddingRight');
    } catch (cleanupError) {
      console.warn('Offcanvas backdrop cleanup warning:', cleanupError);
    }
  }
});

// Handle file input change
document.addEventListener('DOMContentLoaded', function() {
  const fileInput = document.getElementById('service_feature_image');
  const canEdit = @json(auth()->user()->can('edit_service'));
  
  if (fileInput && canEdit) {
    fileInput.addEventListener('change', function (event) {
      const files = event.target.files;
      console.log('Files selected:', files.length);
      
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        console.log('Processing file:', file.name, file.type);
        
        if (["image/jpeg", "image/jpg", "image/png", "image/gif"].includes(file.type)) {
          const reader = new FileReader();
          reader.onload = function (e) {
            console.log('File loaded, adding to gallery');
            galleryImages.push({ file: file, full_url: e.target.result, id: null });
            console.log('Gallery images count:', galleryImages.length);
            renderGalleryImages();
          };
          reader.readAsDataURL(file);
        } else {
          if (typeof window.errorSnackbar === 'function') {
            window.errorSnackbar('Only JPEG, JPG, PNG, and GIF files are allowed.');
          } else {
            alert('Only JPEG, JPG, PNG, and GIF files are allowed.');
          }
        }
      }
      // Reset file input
      event.target.value = '';
    });
  }
});

// Render images in the gallery
function renderGalleryImages() {
  const list = document.getElementById('gallery-images-list');
  list.innerHTML = '';
  console.log('Rendering gallery images, count:', galleryImages.length);
  
  if (galleryImages.length === 0) {
    document.getElementById('gallery-no-images').style.display = 'block';
    document.getElementById('gallery-images-wrapper').style.display = 'none';
    return;
  }
  
  document.getElementById('gallery-no-images').style.display = 'none';
  document.getElementById('gallery-images-wrapper').style.display = 'block';
  
  const canEdit = @json(auth()->user()->can('edit_service'));
  galleryImages.forEach((img, idx) => {
    console.log('Rendering image:', idx, img.full_url);
    const div = document.createElement('div');
    div.className = 'image-container';
    const deleteButton = canEdit ? `
      <button class="delete-button" type="button" onclick="removeGalleryImage(${idx})">
        <i class="fa-solid fa-xmark"></i>
      </button>
    ` : '';
    div.innerHTML = `
      ${deleteButton}
      <img src="${img.full_url}" alt="Selected Image" class="img-fluid selected-image" />
    `;
    list.appendChild(div);
  });
}

// Remove image from gallery
function removeGalleryImage(index) {
  galleryImages.splice(index, 1);
  renderGalleryImages();
}

// Handle form submit
document.addEventListener('DOMContentLoaded', function() {
  const galleryForm = document.getElementById('gallery-form');
  const canEdit = @json(auth()->user()->can('edit_service'));
  
  if (galleryForm) {
    galleryForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!canEdit) {
        if (typeof window.errorSnackbar === 'function') {
          window.errorSnackbar('{{ __("messages.permission_denied") }}');
        } else {
          alert('{{ __("messages.permission_denied") }}');
        }
        return;
      }
      if (!currentServiceId) return;
      const formData = new FormData();
      galleryImages.forEach((img, idx) => {
        if (img.file) {
          formData.append(`gallery[${idx}][file]`, img.file);
          formData.append(`gallery[${idx}][id]`, 'null');
        } else {
          formData.append(`gallery[${idx}][id]`, img.id);
          formData.append(`gallery[${idx}][file]`, '');
        }
      });
      // AJAX submit
      const saveBtn = document.getElementById('gallery-save-btn');
      saveBtn.disabled = true;
      
      // Get CSRF token
      const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                       document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      
      const currentPath = window.location.pathname;
      const basePath = currentPath.includes('/app/') ? currentPath.split('/app/')[0] : '';
      const postUrl = window.location.origin + basePath + `/app/services/gallery-images/${currentServiceId}?t=${Date.now()}`;
      
      fetch(postUrl, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData,
        cache: 'no-store'
      })
        .then(res => res.json())
        .then(data => {
          if (data.status) {
            if (typeof window.successSnackbar === 'function') {
              window.successSnackbar(data.message);
            } else {
              alert(data.message);
            }
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('service-gallery-form'));
            if (offcanvas) offcanvas.hide();
          } else {
            if (typeof window.errorSnackbar === 'function') {
              window.errorSnackbar(data.message);
            } else {
              alert(data.message);
            }
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          if (typeof window.errorSnackbar === 'function') {
            window.errorSnackbar('Something went wrong.');
          } else {
            alert('Something went wrong.');
          }
        })
        .finally(() => {
          saveBtn.disabled = false;
        });
    });
  }
});

// Expose openGalleryOffcanvas globally for use in datatable or elsewhere
window.openGalleryOffcanvas = openGalleryOffcanvas;
window.removeGalleryImage = removeGalleryImage;
</script>
