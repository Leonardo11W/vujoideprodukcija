import { mergeWith } from 'lodash'
import * as moment from 'moment'

export const XSRF_REQUEST_HEADER = () => {
  const csrfToken = document.head.querySelector('[name~=csrf-token][content]').content
  return {
    'X-CSRF-Token': csrfToken
  }
}
export const JSON_REQUEST_HEADER = () => {
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...XSRF_REQUEST_HEADER()
  }
}

export const createRequest = async (URL, header, bodyData = {}, options = {}) => {
  let headerMerged = mergeWith(JSON_REQUEST_HEADER(), header)
  let response
  switch (URL.method) {
    case 'GET':
      response = await fetch(URL.path, { headers: headerMerged })
      return response.json()

    case 'POST':
    case 'PUT':
    case 'PATCH':
      response = await fetch(URL.path, { method: URL.method, body: JSON.stringify(bodyData), headers: headerMerged, ...options })
      return response.json()

    case 'DELETE':
      response = await fetch(URL.path, { method: 'DELETE', headers: headerMerged })
      return response.json()

    default:
      break
  }
  return false
}

export const createRequestWithFormData = async (URL, header, bodyData, options = {}) => {
  let headerMerged = mergeWith(XSRF_REQUEST_HEADER(), header)
  let response

  switch (URL.method) {
    case 'POST':
    case 'PUT':
    case 'PATCH':
      response = await fetch(URL.path, {
        method: URL.method,
        headers: headerMerged,
        body: bodyData,
        ...options
      })
      return response.json()
      break
  }
}

export const readFile = (file, callback) => {
  let reader = new FileReader()
  reader.addEventListener('load', () => {
    callback(reader.result)
  })
  reader.readAsDataURL(file)
}

export const buildMultiSelectObject = (arr, { value, label }) => {
  return arr.map((item) => {
    return { value: item[value], label: item[label] }
  })
}

export const startTime = (value) => {
  return moment(value).format('YYYY-MM-DDTHH:mm')
}

export const endTime = (value, addTime) => {
  return moment(value).add(addTime, 'minutes').format('YYYY-MM-DDTHH:mm')
}

export const confirmSwal = async ({ title }) => {
  return await Swal.fire({
    title: title,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33', // Red for delete button
    cancelButtonColor: '#858482', 
    cancelButtonText: 'Cancel',
    confirmButtonText: 'Yes, delete it!',
    showClass: {
      popup: 'animate__animated animate__zoomIn'
    },
    hideClass: {
      popup: 'animate__animated animate__zoomOut'
    }
  }).then((result) => {
    return result
  })
}

export const confirmcancleSwal = async ({ title }) => {
  return await Swal.fire({
    title: title,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#858482',
    confirmButtonText: 'Yes, do it!',
    showClass: {
      popup: 'animate__animated animate__zoomIn'
    },
    hideClass: {
      popup: 'animate__animated animate__zoomOut'
    }
  }).then((result) => {
    return result
  })
}

// Utility function to properly close modal and remove backdrop
export const closeModalAndCleanup = (modalId = 'exampleModal') => {
  const modalElement = document.getElementById(modalId)
  const modalInstance = bootstrap.Modal.getInstance(modalElement)
  
  if (modalInstance) {
    modalInstance.hide()
  } else {
    // If no instance exists, create one and hide it
    const modal = new bootstrap.Modal(modalElement)
    modal.hide()
  }
  
  // Listen for the modal hidden event to ensure proper cleanup
  modalElement.addEventListener('hidden.bs.modal', function cleanup() {
    // If no other modals are visible, clean up backdrops and body state
    const anyVisibleModal = document.querySelector('.modal.show')
    if (!anyVisibleModal) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove())
      document.body.classList.remove('modal-open')
      document.body.style.overflow = ''
      document.body.style.paddingRight = ''
    }
    
    // Remove this event listener to prevent memory leaks
    modalElement.removeEventListener('hidden.bs.modal', cleanup)
  }, { once: true })
  
  // Fallback cleanup in case the event doesn't fire
  setTimeout(() => {
    // Only clean up if there are no visible modals
    const anyVisibleModal = document.querySelector('.modal.show')
    if (!anyVisibleModal) {
      document.querySelectorAll('.modal-backdrop').forEach(b => b.remove())
      document.body.classList.remove('modal-open')
      document.body.style.overflow = ''
      document.body.style.paddingRight = ''
    }
  }, 300)
}