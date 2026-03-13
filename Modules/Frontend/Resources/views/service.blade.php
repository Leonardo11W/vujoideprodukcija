@extends('frontend::layouts.master')
@section('title') {{__('messages.service')}} @endsection

@section('content')
    <x-breadcrumb title="{{ __('frontend.services') }}" />
    <div class="section-spacing-inner-pages">
        <div class="container">
            <x-service_section :categories="$categories" :category="$category" :allServicesCount="$allServicesCount" id="service-section" />
        </div>
    </div>

    <div class="onclick-page-redirect bg-orange p-3 d-none" id="service-action-bar">
        <div class="container">
            <div class="d-flex justify-content-end align-items-center">
                @if (session()->has('selected_branch_id'))
                    <form id="service-selection-form" action="{{ route('choose-expert') }}" method="POST"
                        style="display:inline;">
                    @else
                        <form id="service-selection-form" action="{{ route('select-branch') }}" method="POST"
                            style="display:inline;">
                @endif
                @csrf
                <input type="hidden" id="selected-services" name="selected_services">
                <button type="submit" class="btn btn-secondary px-5" id="next-button"
                    disabled>{{ __('frontend.next') }}</button>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .service-card {
                transition: all 0.2s ease;
                cursor: pointer;
            }

            .service-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .service-card.selected {
                border: 2px solid #0d6efd;
                background-color: rgba(13, 110, 253, 0.05);
            }

            .service-card .service-checkbox,
            .service-card .addon-checkbox,
            .service-card-addons-collapse,
            .service-card-addons-collapse * {
                cursor: pointer;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {


                const nextButton = document.getElementById('next-button');
                const hiddenInput = document.getElementById('selected-services');
                const serviceSection = document.getElementById('service-cards-table');
                const actionBar = document.getElementById('service-action-bar');
                const selectService = document.getElementById('selected_service');





                // Function to update the selection state
                function updateSelection(event) {

                    try {
                        const changedCheckbox = event ? event.target : null;
                        if (changedCheckbox) {
                            const card = changedCheckbox.closest('.service-card');
                            if (card) {
                                card.classList.toggle('selected', changedCheckbox.checked);
                            }
                        }

                        const checkboxes = document.querySelectorAll('.service-checkbox:checked');
                        let selectedIds = Array.from(checkboxes).map(cb => cb.value).filter(Boolean);

                        if (nextButton) {
                            nextButton.disabled = selectedIds.length === 0;

                        }
                        if (hiddenInput) {
                            hiddenInput.value = selectedIds.join(',');
                        }

                        // Use pre-selected services only on initial load (when event is null)
                        if (!event && selectedIds.length === 0) {
                            selectedIds = (selectService?.value || '').split(',').map(id => id.trim()).filter(Boolean);
                        }

                        if (actionBar) {
                            if (selectedIds.length > 0) {
                                nextButton.disabled = false;
                                actionBar.classList.remove('d-none');
                            } else {
                                nextButton.disabled = true;
                                actionBar.classList.add('d-none');
                            }
                        }
                    } catch (error) {
                        console.error('Error in updateSelection:', error);
                    }
                }

                // Add click event listeners to service cards
                document.addEventListener('click', function(event) {
                    const serviceCard = event.target.closest('.service-card');
                    if (serviceCard && !event.target.matches(
                            '.service-checkbox, .addon-checkbox, .service-card-addons-collapse, .service-card-addons-collapse *'
                        )) {
                        const checkbox = serviceCard.querySelector('.service-checkbox');
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            updateSelection({
                                target: checkbox
                            });
                        }
                    }
                });


                document.getElementById('resetServiceFilters').addEventListener('click', function() {
                    // Clear the selected service input
                    document.getElementById('selected_service').value = '';

                    // Uncheck all service checkboxes
                    document.querySelectorAll('input[type="checkbox"][name="selected_services[]"]').forEach(
                        checkbox => {
                            checkbox.checked = false;
                        });

                    // Update next button and action bar state
                    if (nextButton) {
                        nextButton.disabled = true; // Disable next button since we're unchecking all
                    }

                    if (hiddenInput) {
                        hiddenInput.value = ''; // Clear the hidden input
                    }

                    // Hide action bar when resetting
                    if (actionBar) {
                        actionBar.classList.add('d-none');
                    }

                    // Trigger change event on checkboxes to ensure UI updates
                    document.querySelectorAll('.service-checkbox').forEach(checkbox => {
                        checkbox.dispatchEvent(new Event('change'));
                    });
                });

                serviceSection.addEventListener('change', function(event) {
                    if (event.target && event.target.matches('.service-checkbox')) {

                        updateSelection(event);
                    }
                });

                updateSelection();

            });
        </script>
    @endpush
@endsection
