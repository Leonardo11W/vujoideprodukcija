<!-- Branch Gallery Form Offcanvas (Blade version) -->
<form id="branch-gallery-form-inner" enctype="multipart/form-data">
<div class="offcanvas offcanvas-end" tabindex="-1" id="branch-gallery-form" aria-labelledby="form-offcanvasLabel">
  <div class="offcanvas-header border-bottom">
    <h6 class="m-0 h5">
      {{ __('branch.singular_title') }}: <span id="gallery-branch-name"></span>
    </h6>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
    @csrf
    @php
        $user = auth()->user();
        $canEditBranch = $user->hasPermissionTo('edit_branch');
        $hasBranchGallery = $user->hasPermissionTo('branch_gallery');
        
        \Log::info('🔍 Branch Gallery Form Check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'can_edit_branch' => $canEditBranch,
            'has_branch_gallery' => $hasBranchGallery,
            'mode' => $canEditBranch ? 'Edit mode' : ($hasBranchGallery ? 'View-only mode' : 'No access'),
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ]);
    @endphp
    @if($canEditBranch)
    <div class="d-flex flex-column border-bottom p-3" id="branch-gallery-upload-section">
      <div>
        <label class="form-label btn btn-info d-block my-0" for="branch_feature_image">{{ __('messages.upload_images') }}</label>
        <input type="file" class="form-control d-none" id="branch_feature_image" accept=".jpeg, .jpg, .png, .gif" multiple />
      </div>
    </div>
    @endif
    <div class="offcanvas-body">
      <div id="branch-gallery-no-images" class="text-center mb-0">{{ __('messages.data_not_available') }}</div>
      <div id="branch-gallery-images-wrapper" style="display:none;">
        <div class="gallery-images" id="branch-gallery-images-list">
          <!-- Images will be rendered here -->
        </div>
      </div>
    </div>
    <div class="offcanvas-footer">
      <p class="text-center mb-0"><small>{{ __('messages.gallery_for_branch') }}</small></p>
      <div class="d-grid gap-3 p-3">
        @if($canEditBranch)
        <button type="submit" class="btn btn-primary d-block" id="branch-gallery-save-btn"><i class="fa-solid fa-floppy-disk me-2"></i>{{ __('messages.update') }}</button>
        @endif
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
  top: 0px;
  right: 0px;
  z-index: 1;
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
let branchGalleryImages = [];
let currentBranchId = null;
// Resolve correct backend base URL for gallery endpoints (web routes are under /app prefix)
const BRANCH_GALLERY_BASE_URL = "{{ url('app/branch/gallery-images') }}";

// Function to fetch gallery images
function fetchBranchGalleryImages(branchId) {
  const canEdit = @json(auth()->user()->hasPermissionTo('edit_branch'));
  const hasBranchGallery = @json(auth()->user()->hasPermissionTo('branch_gallery'));
  
  console.log('🔍 About to fetch gallery images', {
    branchId: branchId,
    canEdit: canEdit,
    hasBranchGallery: hasBranchGallery,
    url: `${BRANCH_GALLERY_BASE_URL}/${branchId}`,
    baseUrl: BRANCH_GALLERY_BASE_URL
  });
  
  fetch(`${BRANCH_GALLERY_BASE_URL}/${branchId}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'Accept': 'application/json'
    }
  })
    .then(res => {
      console.log('🔍 Gallery fetch response', {
        status: res.status,
        ok: res.ok,
        statusText: res.statusText
      });
      
      if (!res.ok) {
        if (res.status === 403) {
          console.error('❌ Permission denied - user does not have branch_gallery or edit_branch permission');
          if (typeof window.errorSnackbar === 'function') {
            window.errorSnackbar('You do not have permission to view this gallery.');
          }
          branchGalleryImages = [];
          renderBranchGalleryImages();
          return;
        }
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.json();
    })
    .then(data => {
      console.log('🔍 Gallery fetch data', {
        status: data?.status,
        imageCount: data?.data?.length || 0,
        hasData: !!data?.data
      });
      
      if (data && data.status && data.data && data.data.length > 0) {
        branchGalleryImages = data.data.map(img => ({
          id: img.id,
          full_url: img.full_url,
          file: null
        }));
        renderBranchGalleryImages();
      } else {
        branchGalleryImages = [];
        renderBranchGalleryImages();
      }
    })
    .catch(error => {
      console.error('❌ Error fetching branch gallery images:', error);
      branchGalleryImages = [];
      renderBranchGalleryImages();
    });
}

// Open offcanvas and load images for a branch
function openBranchGalleryOffcanvas(branchId, branchName) {
  console.log('🚀 openBranchGalleryOffcanvas called', {
    branchId: branchId,
    branchName: branchName,
    timestamp: new Date().toISOString()
  });
  
  currentBranchId = branchId;
  
  // Extract only the name part if branchName contains email or other info
  let displayName = branchName || '';
  // If branchName contains email (has @ symbol), extract only the name part
  if (displayName.includes('@')) {
    // Find the last space before the email and take everything before it
    const lastSpaceIndex = displayName.lastIndexOf(' ');
    if (lastSpaceIndex !== -1) {
      displayName = displayName.substring(0, lastSpaceIndex).trim();
    } else {
      // If no space found, take everything before @
      displayName = displayName.split('@')[0].trim();
    }
  }
  // Also remove any extra spaces or special characters that might be after the name
  displayName = displayName.replace(/\s+$/, ''); // Remove trailing spaces
  
  document.getElementById('gallery-branch-name').textContent = displayName;
  branchGalleryImages = [];
  document.getElementById('branch-gallery-images-list').innerHTML = '';
  document.getElementById('branch-gallery-no-images').style.display = 'block';
  document.getElementById('branch-gallery-images-wrapper').style.display = 'none';
  document.getElementById('branch_feature_image').value = '';

  // Fetch images from backend (correct prefixed URL)
  fetch(`${BRANCH_GALLERY_BASE_URL}/${branchId}`)
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return res.json();
    })
    .then(data => {
      if (data.status && data.data.length > 0) {
        branchGalleryImages = data.data.map(img => ({
          id: img.id,
          full_url: img.full_url,
          file: null
        }));
        renderBranchGalleryImages();
      } else {
        branchGalleryImages = [];
        renderBranchGalleryImages();
      }
    })
    .catch(error => {
      console.error('Error fetching branch gallery images:', error);
      branchGalleryImages = [];
      renderBranchGalleryImages();
    });

  // Offcanvas is shown by the global handler in public/js/form-offcanvas/index.js.
  // Avoid calling show() again here to prevent duplicate backdrops.
}

// Handle file input change
document.addEventListener('DOMContentLoaded', function() {
  const fileInput = document.getElementById('branch_feature_image');
  const canEdit = @json(auth()->user()->hasPermissionTo('edit_branch'));
  const hasBranchGallery = @json(auth()->user()->hasPermissionTo('branch_gallery'));
  
  console.log('🔍 Branch Gallery JS Check', {
    canEdit: canEdit,
    hasBranchGallery: hasBranchGallery,
    fileInputExists: !!fileInput,
    mode: canEdit ? 'Edit mode' : (hasBranchGallery ? 'View-only mode' : 'No access')
  });
  
  // Only enable file input if user has edit permission
  if (fileInput && canEdit) {
    fileInput.addEventListener('change', function (event) {
      const files = event.target.files;
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        if (["image/jpeg", "image/jpg", "image/png", "image/gif"].includes(file.type)) {
          const reader = new FileReader();
          reader.onload = function (e) {
            branchGalleryImages.push({ file: file, full_url: e.target.result, id: null });
            renderBranchGalleryImages();
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
function renderBranchGalleryImages() {
  const list = document.getElementById('branch-gallery-images-list');
  list.innerHTML = '';
  if (branchGalleryImages.length === 0) {
    document.getElementById('branch-gallery-no-images').style.display = 'block';
    document.getElementById('branch-gallery-images-wrapper').style.display = 'none';
    return;
  }
  document.getElementById('branch-gallery-no-images').style.display = 'none';
  document.getElementById('branch-gallery-images-wrapper').style.display = 'block';
  const canEdit = @json(auth()->user()->hasPermissionTo('edit_branch'));
  const hasBranchGallery = @json(auth()->user()->hasPermissionTo('branch_gallery'));
  
  console.log('🔍 Rendering Branch Gallery Images', {
    canEdit: canEdit,
    hasBranchGallery: hasBranchGallery,
    imageCount: branchGalleryImages.length,
    mode: canEdit ? 'Edit mode - delete buttons will show' : 'View-only mode - no delete buttons'
  });
  
  branchGalleryImages.forEach((img, idx) => {
    const div = document.createElement('div');
    div.className = 'image-container';
    // Only show delete button if user has edit_branch permission (not just branch_gallery)
    // branch_gallery permission alone is VIEW-ONLY
    const deleteButton = canEdit ? `
      <button class="delete-button" type="button" onclick="removeBranchGalleryImage(${idx})">
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
function removeBranchGalleryImage(index) {
  branchGalleryImages.splice(index, 1);
  renderBranchGalleryImages();
}

// Handle form submit
document.addEventListener('DOMContentLoaded', function() {
  const galleryForm = document.getElementById('branch-gallery-form-inner');
  const canEdit = @json(auth()->user()->hasPermissionTo('edit_branch'));
  
  if (galleryForm) {
    galleryForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!currentBranchId) return;
      const formData = new FormData();
      branchGalleryImages.forEach((img, idx) => {
        if (img.file) {
          formData.append(`gallery[${idx}][file]`, img.file);
          formData.append(`gallery[${idx}][id]`, 'null');
        } else {
          formData.append(`gallery[${idx}][id]`, img.id);
          formData.append(`gallery[${idx}][file]`, '');
        }
      });
      // AJAX submit
      const saveBtn = document.getElementById('branch-gallery-save-btn');
      saveBtn.disabled = true;
      // Get CSRF token
      const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                       document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      fetch(`${BRANCH_GALLERY_BASE_URL}/${currentBranchId}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.status) {
            if (typeof window.successSnackbar === 'function') {
              window.successSnackbar(data.message);
            } else {
              alert(data.message);
            }
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('branch-gallery-form'));
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

// Expose openBranchGalleryOffcanvas globally for use in datatable or elsewhere
window.openBranchGalleryOffcanvas = openBranchGalleryOffcanvas;
window.removeBranchGalleryImage = removeBranchGalleryImage;

// Listen for the custom event to open the gallery from controller/JS

document.addEventListener('branch_gallery', function(e) {
    var branchId = e.detail.form_id;
    // Try to get the branch name from the datatable row
    var row = document.querySelector(`[data-gallery-module="${branchId}"]`).closest('tr');
    var branchName = '';
    if(row) {
        // Try to find the branch name cell (adjust index if needed)
        var nameCell = row.querySelector('td[data-name="name"]') || row.querySelector('td:nth-child(2)');
        if(nameCell) {
            branchName = nameCell.textContent.trim();
            // Extract only the name part if it contains email or other info
            if (branchName.includes('@')) {
                // Find the last space before the email and take everything before it
                const lastSpaceIndex = branchName.lastIndexOf(' ');
                if (lastSpaceIndex !== -1) {
                    branchName = branchName.substring(0, lastSpaceIndex).trim();
                } else {
                    // If no space found, take everything before @
                    branchName = branchName.split('@')[0].trim();
                }
            }
            // Also remove any extra spaces or special characters that might be after the name
            branchName = branchName.replace(/\s+$/, ''); // Remove trailing spaces
        }
    }
    openBranchGalleryOffcanvas(branchId, branchName);
});
</script>
