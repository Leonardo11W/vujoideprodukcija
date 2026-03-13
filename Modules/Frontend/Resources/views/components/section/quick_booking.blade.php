    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="book-appointment-box bg-purple rounded px-5 py-4">
                    <div class="mt-4"></div>
                    <h5 class="mb-4">{{ __('frontend.quick_book_appointment') }}</h5>
                    <div class="row align-items-center book-appointment-content gy-4">
                        <div class="col-xl-3">
                            <div class="date-filter-input">
                                <div class="input-group custom-input-group">
                                    <input type="text" id="appointment_date" class="form-control date-picker"
                                        name="appointment_date" placeholder="{{ __('frontend.select_date') }}" 
                                        autocomplete="off" readonly>
                                    <span class="input-group-text" id="calendar-icon">
                                        <i class="ph ph-calendar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3">
                            <div class="select-service">
                                <a class="form-control dropdown-toggle" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <span id="selected_service">{{ __('frontend.select_service') }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-start dropdown-service-panel">
                                    <div class="service-lists" id="services-list">
                                        <div class="text-center p-3">
                                            <span
                                                class="text-body">{{ __('frontend.please_select_branch_first') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2">
                            <div class="select-expert">
                                <a class="form-control dropdown-toggle" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <span id="selected_expert">{{ __('frontend.select_expert') }}</span>
                                </a>
                                <div
                                    class="dropdown-menu dropdown-menu-start dropdown-service-panel dropdown-expert-panel">
                                    <div class="service-lists" id="experts-list">
                                        <!-- Experts will be loaded dynamically here -->
                                        <div class="text-center p-3">
                                            <span
                                                class="text-body">{{ __('frontend.please_select_branch_first') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2">
                            <div class="select-time">
                                <a class="form-control dropdown-toggle" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <span id="selected_time">{{ __('frontend.select_time') }}</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-start dropdown-time-panel"
                                    data-bs-auto-close="false">
                                    <ul class="list-unstyled m-0 p-0" id="time-slots-list">
                                        <li class="disabled">
                                            <span class="d-block text-center py-2 px-3" style="font-size: 0.875em;">
                                                {{ __('frontend.please_select_branch_service_and_expert') }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-2">
                            <button class="btn btn-secondary w-100"
                                id="bookNowButton">{{ __('frontend.book_now') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Add this hidden input just before the script tag -->
    <input type="hidden" id="quick-booking-service-id" name="quick_booking_service_id" value="">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear last selected expert on page load
            localStorage.removeItem('quickBookingSelectedExpert');
            localStorage.removeItem('quickBookingSelectedExpertName');
            // Always set window.selectedBranchId, even if session is empty
            window.selectedBranchId = '{{ session('selected_branch_id') ?? '' }}';

            // If a service is selected, set window.selectedServiceId
            document.addEventListener('click', function(e) {
                if (e.target.matches('input[name="service"]')) {
                    window.selectedServiceId = e.target.value;
                    loadTimeSlots();
                }
            });

            // If a branch is selected via nav event, update window.selectedBranchId
            document.addEventListener('branchSelected', function(event) {
                window.selectedBranchId = event.detail.branchId;
                loadTimeSlots();
            });

            // If a service is selected via custom dropdown, set window.selectedServiceId
            // (If you use a custom dropdown, add a similar event here)

            // Restore selected expert from localStorage
            const storedExpert = localStorage.getItem('quickBookingSelectedExpert');
            const storedExpertName = localStorage.getItem('quickBookingSelectedExpertName');
            if (storedExpert && storedExpertName) {
                document.getElementById('selected_expert').textContent = storedExpertName;
                window.selectedExpertId = storedExpert;
            }

            // Function to load experts based on selected branch and service
            function loadExpertsByBranch(branchId) {
                const expertsList = document.getElementById('experts-list');
                const serviceId = window.selectedServiceId || document.getElementById('quick-booking-service-id')
                    .value;

                if (!branchId) {
                    expertsList.innerHTML =
                        '<div class="text-center p-3"><span class="text-body">{{ __('frontend.please_select_branch_first') }}</span></div>';
                    return;
                }
                if (!serviceId) {
                    expertsList.innerHTML =
                        '<div class="text-center p-3"><span class="text-body">{{ __('frontend.please_select_service_first') }}</span></div>';
                    return;
                }

                // Show loading state
                expertsList.innerHTML =
                    '<div class="text-center p-3"><span class="text-body">Loading experts...</span></div>';

                const expertsApiUrl = "{{ url('/api/quick-booking/experts-list') }}";
                // Make AJAX request to get experts for the selected branch and service
                fetch(expertsApiUrl + '?branch_id=' + branchId + '&service_id=' + serviceId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.experts.length > 0) {
                            let expertsHtml = '';
                            data.experts.forEach(expert => {
                                expertsHtml += `
                            <label class="expert-item d-flex align-items-center gap-3 px-3 py-2 mb-2 cursor-pointer" data-expert-id="${expert.id}">
                                <div class="expert-info d-flex align-items-center gap-3">
                                    <img src="${expert.image_path || '/img/frontend/default-expert.png'}" alt="${expert.name}" class="img-fluid rounded-circle avatar avatar-60">
                                    <div>
                                        <h6 class="font-size-14 mb-1">${expert.name}</h6>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge bg-purple heading-color"><i class="ph-fill ph-star text-warning"></i> ${expert.rating}</span>
                                            <span class="font-size-12">${expert.speciality}</span>
                                        </div>
                                    </div>
                                </div>
                                <input class="form-check-input expert-radio ms-auto" type="radio" name="expert" value="${expert.id}">
                            </label>
                        `;
                            });
                            expertsList.innerHTML = expertsHtml;
                            // Add this after rendering experts:
                            expertsList.querySelectorAll('.expert-item').forEach(function(label) {
                                label.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    expertsList.querySelectorAll('.expert-item').forEach(
                                        function(l) {
                                            l.classList.remove('selected');
                                        });
                                    label.classList.add('selected');
                                    let radio = label.querySelector('input[type="radio"]');
                                    if (radio) {
                                        radio.checked = true;
                                        let expertName = label.querySelector('h6')
                                            ?.textContent || 'Expert';
                                        let expertId = radio.value;
                                        document.getElementById('selected_expert').textContent =
                                            expertName;
                                        window.selectedExpertId = expertId;
                                        // Persist selection
                                        localStorage.setItem('quickBookingSelectedExpert',
                                            expertId);
                                        localStorage.setItem('quickBookingSelectedExpertName',
                                            expertName);
                                        loadTimeSlots();
                                        // Close the expert dropdown after selection
                                        let expertDropdown = label.closest('.select-expert')
                                            ?.querySelector('.dropdown-toggle');
                                        if (expertDropdown && typeof bootstrap !==
                                            'undefined') {
                                            bootstrap.Dropdown.getOrCreateInstance(
                                                expertDropdown).hide();
                                        }
                                    }
                                });
                            });
                            // Restore selection visually if present
                            if (window.selectedExpertId) {
                                expertsList.querySelectorAll('.expert-item').forEach(function(label) {
                                    let radio = label.querySelector('input[type="radio"]');
                                    if (radio && radio.value === window.selectedExpertId) {
                                        label.classList.add('selected');
                                        radio.checked = true;
                                    }
                                });
                            }
                            // Prevent dropdown from closing on expert selection
                            var expertPanel = expertsList.closest('.dropdown-expert-panel');
                            if (expertPanel) {
                                expertPanel.addEventListener('click', function(e) {
                                    if (e.target.matches('input[name="expert"]') || e.target.closest(
                                            '.expert-item')) {
                                        e.stopPropagation();
                                    }
                                });
                            }
                        } else {
                            expertsList.innerHTML =
                                '<div class="text-center p-3"><span class="text-body">No experts available for this branch and service</span></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading experts:', error);
                        expertsList.innerHTML =
                            '<div class="text-center p-3"><span class="text-danger">Error loading experts</span></div>';
                    });
            }

            // Function to load services based on selected branch
            function loadServicesByBranch(branchId) {
                const servicesList = document.getElementById('services-list');
                if (!branchId) {
                    servicesList.innerHTML =
                        '<div class="text-center p-3"><span class="text-body">Please select a branch first</span></div>';
                    return;
                }
                servicesList.innerHTML =
                    '<div class="text-center p-3"><span class="text-body">Loading services...</span></div>';

                fetch("{{ url('/api/quick-booking/services-list') }}?branch_id=" + branchId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status && data.data.length > 0) {
                            let servicesHtml = '';
                            // Get the dynamic currency symbol from window.defaultCurrencySymbol
                            const currencySymbol = window.defaultCurrencySymbol || '$';
                            data.data.forEach(service => {
                                servicesHtml += `
                            <label class="service-item d-flex align-items-center gap-3 px-3 py-2 mb-2  cursor-pointer" data-service-id="${service.id}">
                                <div class="service-info d-flex align-items-center gap-3">
                                    <img src="${service.image_path || '/img/frontend/hair-wash-service.png'}" alt="${service.name}" class="img-fluid rounded avatar avatar-60">
                                    <div>
                                        <h6 class="font-size-14 mb-1">${service.name}</h6>
                                        <div class="text-primary fw-bold font-size-12">${window.currencyFormat(service.price)}</div>
                                    </div>
                                </div>
                                <input class="form-check-input service-radio ms-auto" type="radio" name="service" value="${service.id}" data-duration="${service.duration}">
                            </label>
                        `;
                            });
                            servicesList.innerHTML = servicesHtml;
                        } else {
                            servicesList.innerHTML =
                                '<div class="text-center p-3"><span class="text-body">No services available for this branch</span></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading services:', error);
                        servicesList.innerHTML =
                            '<div class="text-center p-3"><span class="text-danger">Error loading services</span></div>';
                    });
            }

            // Listen for branch selection changes
            document.addEventListener('branchSelected', function(event) {
                const branchId = event.detail.branchId;
                loadExpertsByBranch(branchId);
                loadServicesByBranch(branchId);
            });

            // Also check if there's already a selected branch in session
            const selectedBranchId = '{{ session('selected_branch_id') }}';
            if (selectedBranchId) {
                loadExpertsByBranch(selectedBranchId);
                loadServicesByBranch(selectedBranchId);
            }

            // Fallback: Listen for any change to a service checkbox and call selectService
            document.addEventListener('change', function(e) {
                if (e.target.matches('input[name="service"]')) {
                    // Try to get the label text for the service
                    let label = e.target.closest('.service-item')?.querySelector('h6')?.textContent ||
                        'Service';
                    // Set selected service
                    document.getElementById('quick-booking-service-id').value = e.target.value;
                    window.selectedServiceId = e.target.value;
                    selectService(label);
                    // Now call loadExpertsByBranch with updated serviceId
                    loadExpertsByBranch(window.selectedBranchId);
                    // Close the service dropdown after selection
                    let serviceDropdown = e.target.closest('.select-service')?.querySelector(
                        '.dropdown-toggle');
                    if (serviceDropdown && typeof bootstrap !== 'undefined') {
                        bootstrap.Dropdown.getOrCreateInstance(serviceDropdown).hide();
                    }
                }
            });

            // Prevent service dropdown from closing on item click
            document.querySelectorAll('.dropdown-service-panel').forEach(function(menu) {
                menu.addEventListener('click', function(e) {
                    // If the click is on a service radio or its label, prevent closing
                    if (e.target.matches('input[name="service"]') || e.target.closest(
                            '.service-item')) {
                        e.stopPropagation();
                    }
                });
            });

            document.querySelectorAll('.dropdown-time-panel, .dropdown-calendar-panel').forEach(function(menu) {
                menu.addEventListener('click', function(e) {
                    if (
                        e.target.matches('a') || e.target.closest('li') ||
                        e.target.matches('input[name="appointment_date"]') || e.target.closest(
                            '.date-filter-input')
                    ) {
                        e.stopPropagation();
                    }
                });
            });

            // --- Date Picker: Keep open until user closes and prevent past dates ---
            var today = new Date();
            var yyyy = today.getFullYear();
            var mm = String(today.getMonth() + 1).padStart(2, '0');
            var dd = String(today.getDate()).padStart(2, '0');
            var todayStr = yyyy + '-' + mm + '-' + dd;

            // Try Flatpickr
            if (window.flatpickr) {
                flatpickr('.date-picker', {
                    // closeOnSelect: false, // Remove or set to true so calendar closes
                    allowInput: false, // Disable manual input to prevent auto-complete
                    minDate: todayStr,
                    onChange: function(selectedDates, dateStr, instance) {
                        // No need to close a dropdown, just let Flatpickr close itself
                    }
                });
            }
            // Try Bootstrap Datepicker
            if (window.jQuery && typeof jQuery('.date-picker').datepicker === 'function') {
                jQuery('.date-picker').datepicker({
                    autoclose: false,
                    startDate: todayStr,
                    format: 'yyyy-mm-dd',
                    keyboardNavigation: false, // Disable keyboard navigation to prevent auto-complete
                    forceParse: false // Disable auto-parsing of input
                }).on('changeDate', function(e) {
                    // Close the date dropdown after selection
                    let dateDropdown = document.querySelector('.date-filter-input .dropdown-toggle');
                    if (dateDropdown && typeof bootstrap !== 'undefined') {
                        bootstrap.Dropdown.getOrCreateInstance(dateDropdown).hide();
                    }
                });
            }

            // Make calendar icon open the date picker
            document.getElementById('calendar-icon').addEventListener('click', function() {
                document.getElementById('appointment_date').focus();
            });

            // Helper to get Flatpickr instance
            function getFlatpickrInstance(input) {
                return input._flatpickr || (window.Flatpickr && window.Flatpickr.getInstance ? window.Flatpickr
                    .getInstance(input) : null);
            }
            const calendarIcon = document.getElementById('calendar-icon');
            const appointmentDateInput = document.getElementById('appointment_date');
            // Wait for Flatpickr to be initialized before attaching the open-only handler
            function attachCalendarIconHandler() {
                const fp = getFlatpickrInstance(appointmentDateInput);
                if (fp) {
                    calendarIcon.addEventListener('click', function() {
                        fp.open();
                        appointmentDateInput.focus();
                    });
                } else {
                    setTimeout(attachCalendarIconHandler, 100);
                }
            }
            attachCalendarIconHandler();

            let offDays = [];
            let holidayDates = []; // Array to store specific holiday dates

            // Fetch off days (days of week that are holiday)
            function fetchOffDays(branchId) {
                // Use default branch_id=1 if not provided
                const useBranchId = branchId || 1;
                const apiUrl = "{{ url('/app/bussinesshours/index_list') }}?branch_id=" + useBranchId;
                fetch(apiUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status && Array.isArray(data.data)) {
                            // Normalize to capitalized day names (e.g., 'Sunday')
                            offDays = data.data.filter(d => d.is_holiday == 1).map(d => d.day.charAt(0)
                                .toUpperCase() + d.day.slice(1).toLowerCase());

                        } else {
                            offDays = [];

                        }
                        updateDatePickerDisabledDays();
                    })
                    .catch(() => {
                        offDays = [];

                        updateDatePickerDisabledDays();
                    });
            }

            // Fetch specific holiday dates
            function fetchHolidayDates(branchId) {
                const useBranchId = branchId || 1;
                const apiUrl = "{{ url('/app/holidays/index_list') }}?branch_id=" + useBranchId;
                fetch(apiUrl)
                    .then(res => res.json())
                    .then(data => {
                        if (data.status && Array.isArray(data.data)) {
                            holidayDates = data.data.map(h => h.date);
                        } else {
                            holidayDates = [];
                        }
                        updateDatePickerDisabledDays();
                    })
                    .catch(() => {
                        holidayDates = [];
                        updateDatePickerDisabledDays();
                    });
            }

            function updateDatePickerDisabledDays() {
                // Defensive: ensure Flatpickr is initialized
                if (window.flatpickr && appointmentDateInput._flatpickr) {
                    appointmentDateInput._flatpickr.set('disable', [
                        function(date) {
                            // Check if it's an off day (day of week)
                            const dayName = date.toLocaleDateString('en-US', {
                                weekday: 'long'
                            });
                            if (offDays.includes(dayName)) {
                                return true;
                            }

                            // Check if it's a specific holiday date
                            // Format date in local timezone to avoid UTC conversion issues
                            const year = date.getFullYear();
                            const month = String(date.getMonth() + 1).padStart(2, '0');
                            const day = String(date.getDate()).padStart(2, '0');
                            const dateStr = `${year}-${month}-${day}`; // Format: YYYY-MM-DD
                            if (holidayDates.includes(dateStr)) {
                                return true;
                            }
                            
                            return false;
                        }
                    ]);
                    // Force Flatpickr to re-parse and re-render
                    appointmentDateInput._flatpickr.redraw();
                } else {
                    // If not initialized yet, try again shortly
                    setTimeout(updateDatePickerDisabledDays, 100);
                }
            }

            // Call fetchOffDays and fetchHolidayDates when branch changes
            // (and on page load if a branch is already selected)
            document.addEventListener('branchSelected', function(event) {
                fetchOffDays(event.detail.branchId);
                fetchHolidayDates(event.detail.branchId);
            });
            if (window.selectedBranchId) {
                fetchOffDays(window.selectedBranchId);
                fetchHolidayDates(window.selectedBranchId);
            } else {
                fetchOffDays(1); // Default branch_id=1
                fetchHolidayDates(1); // Default branch_id=1
            }
        });

        // Function to select expert (called from onclick)
        function selectExpert(expertName, expertId) {
            document.getElementById('selected_expert').textContent = expertName;
            if (typeof window.selectedExpertId !== 'undefined') {
                window.selectedExpertId = expertId;
            }
        }

        // Function to select service (called from onclick)
        function selectService(serviceName, duration) {
            document.getElementById('selected_service').textContent = serviceName;
            let checkedService = document.querySelector('input[name="service"]:checked');
            if (checkedService) {
                document.getElementById('quick-booking-service-id').value = checkedService.value;
                window.selectedServiceId = checkedService.value;
                window.selectedServiceDuration = checkedService.getAttribute('data-duration');
            } else {
                document.getElementById('quick-booking-service-id').value = '';
                window.selectedServiceId = '';
                window.selectedServiceDuration = '';
            }
            loadTimeSlots();
        }

        // Helper to get selected values
        function getQuickBookingSelections() {
            // Always use the hidden input for serviceId
            return {
                branchId: window.selectedBranchId || document.querySelector('[name="branch_id"]')?.value || '',
                serviceId: document.getElementById('quick-booking-service-id').value || '',
                expertId: window.selectedExpertId || document.querySelector('input[name="expert"]:checked')?.value || '',
                date: document.getElementById('appointment_date')?.value || ''
            };
        }

        // Fetch and render time slots
        function loadTimeSlots() {
            const {
                branchId,
                serviceId,
                expertId,
                date
            } = getQuickBookingSelections();
            const slotsList = document.getElementById('time-slots-list');
            let missing = [];
            if (!branchId) missing.push('branch');
            if (!serviceId) missing.push('service');
            if (!expertId) missing.push('expert');
            if (!date) missing.push('date');
            if (missing.length > 0) {
                const msg = 'Please select: ' + missing.map(m => m.charAt(0).toUpperCase() + m.slice(1)).join(', ');
                slotsList.innerHTML = `<li class="disabled"><span class="text-muted d-block text-center py-2 px-3" style="font-size: 0.875em;">${msg}</span></li>`;
                return;
            }

            // Show loading state
            slotsList.innerHTML = '<li class="disabled"><span class="text-muted d-block text-center py-2 px-3" style="font-size: 0.875em;">Loading slots...</span></li>';

            // Fetch available slots from backend
            const getAvailableSlotsUrl = "{{ route('get-available-slots') }}";
            fetch(`${getAvailableSlotsUrl}?date=${date}&branch_id=${branchId}&employee_id=${expertId}`)
                .then(response => response.json())
                .then(data => {
                    let slots = data.status === 'success' && Array.isArray(data.data) ? data.data : [];
                    // Filter out past slots for today
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');
                    const todayStr = `${yyyy}-${mm}-${dd}`;
                    if (date === todayStr) {
                        const now = new Date();
                        slots = slots.filter(slot => {
                            const slotDateTime = new Date(slot.value.replace(' ', 'T'));
                            return slotDateTime > now;
                        });
                    }
                    if (slots.length > 0) {
                        let html = '';
                        slots.forEach(slot => {
                            html +=
                                `<li${slot.disabled ? ' class="disabled"' : ''}><a href="#" onclick="event.preventDefault(); if(!${slot.disabled})selectTime('${slot.label}', '${slot.value}')">${slot.label}</a></li>`;
                        });
                        slotsList.innerHTML = html;
                    } else {
                        slotsList.innerHTML = '<li class="disabled"><span class="text-muted d-block text-center py-2 px-3" style="font-size: 0.875em;">No slots available</span></li>';
                    }
                })
                .catch(error => {
                    console.error('Error loading slots:', error);
                    slotsList.innerHTML = '<li class="disabled"><span class="invalid-feedback d-block text-center py-2 px-3" style="font-size: 0.875em; color: #dc3545; border: none; background: none;">Error loading slots</span></li>';
                });
        }

        // Update selectTime to store only the time part
        function selectTime(label, value) {
            document.getElementById('selected_time').textContent = label;
            // If value is like 'YYYY-MM-DD HH:MM:SS', store only 'HH:MM' or 'HH:MM:SS'
            let timePart = value;
            if (value.includes(' ')) {
                timePart = value.split(' ')[1];
            }
            window.selectedTimeValue = timePart;
            // Close the time dropdown after selection
            let timeDropdown = document.querySelector('.select-time .dropdown-toggle');
            if (timeDropdown && typeof bootstrap !== 'undefined') {
                bootstrap.Dropdown.getOrCreateInstance(timeDropdown).hide();
            }
        }

        // Listen for changes to branch, service, expert, and date
        // (Assumes you have event dispatches or can add listeners to your dropdowns/inputs)
        document.addEventListener('branchSelected', loadTimeSlots);
        document.addEventListener('change', function(e) {
            if (e.target.matches('input[name="service"]') || e.target.matches('input[name="expert"]') || e.target
                .matches('#appointment_date')) {
                loadTimeSlots();
            }
        });
    </script>
    <script>
        // Add this after all other scripts, before
        document.getElementById('bookNowButton').addEventListener('click', async function(e) {
            e.preventDefault();

            // Prevent multiple clicks - disable button immediately
            const bookNowButton = document.getElementById('bookNowButton');
            if (bookNowButton.disabled) {
                return; // Already processing, ignore additional clicks
            }
            
            // Disable button and show loading state
            bookNowButton.disabled = true;
            const originalText = bookNowButton.textContent;
            bookNowButton.textContent = '{{ __("frontend.processing") }}...';

            // Gather booking data
            const {
                branchId,
                date
            } = getQuickBookingSelections();
            const time = window.selectedTimeValue || '';

            // Helper function to reset button state
            function resetButtonState() {
                bookNowButton.disabled = false;
                bookNowButton.textContent = originalText;
            }

            // Validate all fields
            if (!branchId || !date || !time) {
                resetButtonState();
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('frontend.incomplete_booking') }}',
                    text: '{{ __('frontend.please_complete_all_booking_fields') }}',
                    confirmButtonText: '{{ __('frontend.ok') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false,
                });
                return;
            }

            // Get selected service and expert
            const serviceInput = document.querySelector('input[name="service"]:checked');
            const expertInput = document.querySelector('input[name="expert"]:checked');
            const serviceId = serviceInput ? serviceInput.value : '';
            const serviceDuration = serviceInput ? serviceInput.getAttribute('data-duration') : '';
            const expertId = expertInput ? expertInput.value : '';
            const servicePrice = serviceInput ? serviceInput.closest('.service-item').querySelector(
                '.text-primary.fw-bold.font-size-12')?.textContent.replace(/[^\d.]/g, '') : '0';

            if (!serviceId || !expertId) {
                resetButtonState();
                Swal.fire({
                    icon: 'warning',
                    title: '{{ __('frontend.incomplete_booking') }}',
                    text: '{{ __('frontend.please_select_service_and_expert') }}',
                    confirmButtonText: '{{ __('frontend.ok') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false,
                });
                return;
            }

            // Get logged-in user info (replace with your actual user data if available)
            const user = {
                email: '{{ auth()->check() ? auth()->user()->email : '' }}',
                first_name: '{{ auth()->check() ? auth()->user()->first_name : '' }}',
                last_name: '{{ auth()->check() ? auth()->user()->last_name : '' }}'
            };

            // Check if user is logged in
            if (!user.email || !user.first_name || !user.last_name) {
                resetButtonState();
                // Swal.fire({
                //     icon: 'warning',
                //     title: 'Login Required',
                //     text: 'You must be logged in to book an appointment.'
                // });
                // Show login modal after alert
                setTimeout(function() {
                    if (window.showLoginModal) {
                        window.showLoginModal();
                    } else {
                        window.dispatchEvent(new Event('forceShowLoginModal'));
                    }
                }, 500);
                return;
            }

            // Prepare booking data
            let selectedTime = time;
            if (selectedTime.includes(' ')) {
                selectedTime = selectedTime.split(' ')[1];
                if (!selectedTime) selectedTime = '00:00:00';
            }
            if (selectedTime.length === 5) { // e.g. '14:00'
                selectedTime = selectedTime + ':00';
            }
            const startDateTime = `${date} ${selectedTime}`.trim();
            const bookingData = {
                branch_id: branchId,
                start_date_time: startDateTime,
                note: '', // Optional, can be filled if you have a note field
                status: 'pending', // Or your default status
                payment_method: 'cash',
                services: [{
                    service_id: serviceId,
                    employee_id: expertId,
                    service_price: parseFloat(servicePrice) || 0,
                    duration_min: parseInt(serviceDuration) || 0,
                    start_date_time: startDateTime,
                    sequance: 0 // Default sequence
                }]
            };

            // Send to backend
            fetch("{{ url('/api/quick-booking/store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        user: user,
                        booking: bookingData
                    })
                })
                .then(async response => {
                    let data = {};
                    try {
                        data = await response.clone().json();
                    } catch (err) {
                        data.message = await response.text();
                    }
                    if (response.ok && data.success) {
                        // Reset button state on success
                        resetButtonState();
                        
                        // Extract details from response
                        const booking = data.data || {};
                        // Try multiple possible keys for expert name
                        let expertName = 'Expert';
                        if (booking.services && booking.services[0]) {
                            expertName = booking.services[0].employee_name || booking.services[0]
                                .employee || booking.services[0].expert_name || 'Expert';
                            if (typeof expertName === 'object' && expertName && expertName.name) {
                                expertName = expertName.name;
                            }
                        }
                        const branchName = booking.branch && booking.branch.name ? booking.branch.name :
                            'Branch';
                        const bookingDateTime = booking.start_date_time || '';
                        const bookingId = booking.id || '';
                        const paymentMethod = booking.payment_method || 'cash';
                        const totalPayment = booking.services && booking.services[0] && booking
                            .services[0].service_price ? booking.services[0].service_price : 0;
                        
                        // Get the dynamic currency symbol
                        const currencySymbol = window.defaultCurrencySymbol || '$';

                        Swal.fire({
                            icon: 'success',
                            title: 'Appointment Submitted!',
                            html: `Your appointment at <b>${branchName}</b> has been successfully booked on <b>${bookingDateTime}</b>.<br><br>
                            <span class="d-flex align-items-center gap-2 justify-content-center mb-2"><span class="h6 m-0 font-size-14">Booking ID:</span> <span class="text-primary fw-bold font-size-14">#${bookingId}</span></span>
                            <span class="d-flex align-items-center gap-2 justify-content-center mb-2"><span class="h6 m-0 font-size-14">Payment Method :</span> <span class="h6 m-0 fw-bold font-size-14">${paymentMethod}</span></span>
                            <span class="d-flex align-items-center gap-2 justify-content-center mb-4"><span class="h6 m-0 font-size-14">Total Payment:</span> <span class="h6 m-0 fw-bold font-size-14">${currencySymbol}${totalPayment}</span></span>
                            <span class="d-flex align-items-center justify-content-center flex-md-nowrap flex-wrap gap-2">
                                <button id="swal-close-btn" class="btn btn-primary">Close</button>
                                <button id="swal-goto-btn" class="btn btn-secondary">Go to Booking</button></span>`,
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                document.getElementById('swal-close-btn').onclick = () => {
                                    // Reset all dropdown selections
                                    const resetDropdown = (id, defaultText) => {
                                        const element = document.getElementById(id);
                                        if (element) {
                                            element.textContent = defaultText;
                                            // Also update any associated hidden inputs
                                            const hiddenInput = document
                                                .getElementById(id + '-input');
                                            if (hiddenInput) hiddenInput.value = '';
                                        }
                                    };

                                    // Reset service selection
                                    resetDropdown('selected_service',
                                        '{{ __('frontend.select_service') }}');

                                    // Reset expert selection
                                    resetDropdown('selected_expert',
                                        '{{ __('frontend.select_expert') }}');

                                    // Reset time selection
                                    resetDropdown('selected_time',
                                        '{{ __('frontend.select_time') }}');

                                    // Reset date picker
                                    const datePicker = document.getElementById(
                                        'appointment_date');
                                    if (datePicker) datePicker.value = '';

                                    // Reset any selected time slots
                                    document.querySelectorAll('.time-slot.selected')
                                        .forEach(slot => {
                                            slot.classList.remove('selected');
                                        });

                                    // Clear any error messages
                                    document.querySelectorAll('.invalid-feedback')
                                        .forEach(el => el.remove());
                                    document.querySelectorAll('.is-invalid').forEach(
                                        el => el.classList.remove('is-invalid'));

                                    // Close the modal
                                    Swal.close();
                                };
                                document.getElementById('swal-goto-btn').onclick = () =>
                                    window.location.href = "{{ route('bookings') }}";
                            }
                        });
                    } else {
                        // Reset button state on error
                        resetButtonState();
                        
                        let errorMsg = data.message || 'Booking failed. Please try again.';
                        if (data.errors) {
                            errorMsg = Object.values(data.errors).flat().join(' ');
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Booking Failed',
                            text: errorMsg,
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        });
                    }
                })
                .catch(async error => {
                    // Reset button state on catch error
                    resetButtonState();
                    
                    let errorMsg = error.message || 'Booking failed. Please try again.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Booking Failed',
                        text: errorMsg,
                        confirmButtonText: '{{ __('frontend.ok') }}',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false,
                    });
                    console.error('Booking error:', error);
                });
        });
    </script>
