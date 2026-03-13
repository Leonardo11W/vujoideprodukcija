<div class="choose-expert-card text-center position-relative" data-employee-id="{{ $employee->id }}">
    <input type="radio" name="selected-expert" id="choose-expert-{{ $employee->id }}"
        class="choose-expert-checkbox form-check-input">
    <label for="choose-expert-{{ $employee->id }}" class="doctor-inner-card">
        <div class="choose-expert-card-img">
            <img src="{{ $employee->profile_image }}" alt="staff image" class="img-fluid object-fit-cover">

            <span class="select-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none">
                    <rect width="24" height="24" rx="12" fill="currentColor" />
                    <g>
                        <path d="M7.375 12.75L10 15.375L16 9.375" stroke="white" stroke-width="3" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </g>
                    <defs>
                        <clipPath>
                            <rect width="12" height="12" fill="white" transform="translate(5.5 6)" />
                        </clipPath>
                    </defs>
                </svg>
            </span>
        </div>
        <div class="choose-expert-card-content mt-3">
            {{-- <h6 class="mb-0"><a href="{{route('expert-detail')}}">{{ $employee->full_name }}</a></h6> --}}
            <h6 class="mb-0"><a
                    href="{{ route('expert-detail', ['id' => $employee->id]) }}">{{ $employee->full_name }}</a></h6>
            <p class="font-size-12 mb-0 mt-2">{{ __('frontend.hair_specialist') }}</p>
        </div>
    </label>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let isUpdating = false;
        let updateTimeout = null;

        // Function to check if select icon is visible using computed styles
        function isSelectIconVisible(card) {
            try {
                const selectIcon = card.querySelector('.select-icon');
                if (!selectIcon) return false;

                const computedStyle = window.getComputedStyle(selectIcon);
                return computedStyle.visibility === 'visible';
            } catch (error) {
                console.warn('Error checking select icon visibility:', error);
                return false;
            }
        }

        // Function to update selected class and action bar
        function updateSelectionState() {
            if (isUpdating) return;

            try {
                isUpdating = true;
                const expertCards = document.querySelectorAll('.choose-expert-card');
                const actionBar = document.getElementById('expert-action-bar');
                const nextButton = document.getElementById('next-button');
                const hiddenInput = document.getElementById('employee_id');
                let hasSelectedCard = false;
                let selectedEmployeeId = null;

                expertCards.forEach(card => {
                    const isVisible = isSelectIconVisible(card);


                    // Add/remove selected class based on icon visibility
                    if (isVisible) {
                        card.classList.add('selected');
                        hasSelectedCard = true;
                        selectedEmployeeId = card.dataset.employeeId;
                    } else {
                        card.classList.remove('selected');
                    }
                });

                if (actionBar) {
                    if (hasSelectedCard) {
                        actionBar.classList.remove('d-none');
                    } else {
                        actionBar.classList.add('d-none');
                    }
                }

                if (nextButton) {
                    if (hasSelectedCard) {
                        nextButton.disabled = false;
                    } else {
                        nextButton.disabled = true;
                    }
                }

                // Update hidden input
                if (hiddenInput && selectedEmployeeId) {
                    hiddenInput.value = selectedEmployeeId;
                }

                // Dispatch a custom event for the booking page to listen to
                const event = new CustomEvent('expertSelectionChanged', {
                    detail: {
                        hasSelectedCard: hasSelectedCard,
                        selectedEmployeeId: selectedEmployeeId,
                        selectedCards: Array.from(expertCards).filter(card => card.classList.contains(
                            'selected'))
                    }
                });
                document.dispatchEvent(event);

            } catch (error) {
                console.error('Error updating selection state:', error);
            } finally {
                isUpdating = false;
            }
        }

        // Debounced update function
        function debouncedUpdate() {
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            updateTimeout = setTimeout(updateSelectionState, 50);
        }

        // Use MutationObserver to watch for changes in the DOM
        const observer = new MutationObserver(function(mutations) {
            if (isUpdating) return; // Skip if we're already updating

            let shouldUpdate = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const target = mutation.target;
                    // Only update if the change is specifically to the select-icon visibility
                    if (target.classList.contains('select-icon')) {
                        shouldUpdate = true;
                    }
                }
            });

            if (shouldUpdate) {
                debouncedUpdate();
            }
        });

        setTimeout(updateSelectionState, 100);
    });
</script>
