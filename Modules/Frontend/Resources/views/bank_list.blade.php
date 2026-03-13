@extends('frontend::layouts.master')
@section('title')
    {{ __('frontend.bank_list') }}
@endsection

@section('content')
    <x-breadcrumb />
    <div class="section-spacing-inner-pages">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
                <h6 class="font-size-21-3 m-0">{{ __('Bank.bank_list') }}</h6>
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group mb-0">
                        <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                        <input type="search" id="bankHeaderSearch" class="form-control p-2 form-control"
                            placeholder="{{ __('Bank.search_banks') }}" aria-controls="bank-cards-table">

                    </div>
                    <button class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#bankInfoModal"
                        onclick="resetModalForAdd()">{{ __('Bank.add_new_bank') }}</button>
                </div>
            </div>
            <div id="bankCardContainer"></div>

            <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader list-inline">
                @for ($i = 0; $i < 3; $i++)
                    @include('frontend::components.card.shimmer_bank_card')
                @endfor
            </div>
            <table id="bank-cards-table" class="table d-none w-100">
                <thead>
                    <tr>
                        <th>Card</th>
                        <th>Bank Name</th> {{-- hidden column for search --}}
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Modal for adding/editing bank information -->
    <div class="modal fade" id="bankInfoModal" tabindex="-1" aria-labelledby="bankInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="bankForm" method="POST" action="{{ route('bank.store') }}">
                    @csrf
                    <input type="hidden" id="bankId" name="bank_id">
                    <div class="modal-body">
                        <h6 id="modalTitle" class="font-size-21-3 mb-3">{{ __('Bank.add_bank') }}</h6>
                        <div class="row gy-4">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="branch_name"
                                        class="form-label fw-medium">{{ __('Bank.branch_name') }}</label>
                                    <span class="text-danger">*</span>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="branch_name" id="branch_name" class="form-control"
                                            placeholder="{{ __('Bank.placeholder_branch_name') }}" />
                                        <span class="input-group-text"><i class="ph ph-piggy-bank"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="branch_name_error"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="bank_name" class="form-label fw-medium">{{ __('Bank.bank_name') }}</label>
                                    <span class="text-danger">*</span>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="bank_name" id="bank_name" class="form-control"
                                            placeholder="{{ __('Bank.placeholder_bank_name') }}" />
                                        <span class="input-group-text"><i class="ph ph-piggy-bank"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="bank_name_error"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="account_no"
                                        class="form-label fw-medium">{{ __('Bank.account_number') }}</label>
                                    <span class="text-danger">*</span>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="account_no" id="account_no" class="form-control"
                                            placeholder="{{ __('Bank.placeholder_account_number') }}" />
                                        <span class="input-group-text"><i class="ph ph-dots-three-circle"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="account_no_error"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="ifsc_no" class="form-label fw-medium">{{ __('Bank.ifsc_code') }}</label>
                                    <span class="text-danger">*</span>
                                    <div class="input-group custom-input-group">
                                        <input type="text" name="ifsc_no" id="ifsc_no" class="form-control"
                                            placeholder="{{ __('bank.eg_SBIN5642310') }}" />
                                        <span class="input-group-text"><i class="ph ph-user"></i></span>
                                    </div>
                                    <div class="invalid-feedback" id="ifsc_no_error"></div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="form-group d-flex justify-content-between align-items-center">
                                    <label for="status"
                                        class="form-label fw-medium mb-0">{{ __('Bank.status') }}</label>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" id="status" name="status"
                                            value="active" checked>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            class="d-flex align-items-center justify-content-end gap-lg-4 gap-2 flex-wrap mt-5 pt-lg-3 pt-0">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Bank.cancel') }}</button>
                            <button type="submit" id="submitButton"
                                class="btn btn-primary">{{ __('Bank.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Form validation
            const form = document.getElementById('bankForm');
            const requiredFields = ['branch_name', 'bank_name', 'account_no', 'ifsc_no'];
            const shimmerLoader = document.querySelector('.shimmer-loader');

            function validateField(field, showError = true) {
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';
                const errorElement = document.getElementById(`${field.id}_error`);

                if (field.id === 'account_no') {
                    if (!value) {
                        errorMessage = 'Account number is required';
                    } else if (!/^\d+$/.test(value)) {
                        errorMessage = 'Account number must be numeric';
                    }
                } else if (field.id === 'ifsc_no') {
                    // IFSC validation removed - now only checks if field is not empty
                    if (!value) {
                        errorMessage = 'IFSC code is required';
                    }
                } else if (!value) {
                    errorMessage = 'This field is required';
                }

                isValid = !errorMessage;

                if (!isValid && showError) {
                    field.classList.add('is-invalid');
                    if (errorElement) {
                        errorElement.textContent = errorMessage;
                        errorElement.style.display = 'block';
                    }
                } else {
                    field.classList.remove('is-invalid');
                    if (errorElement) {
                        errorElement.textContent = '';
                        errorElement.style.display = 'none';
                    }
                }

                return isValid;
            }

            // Add validation on input and blur
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    ['input', 'blur'].forEach(event => {
                        field.addEventListener(event, () => validateField(field, true));
                    });
                }
            });

            // Form submission validation
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                let isValid = true;
                let firstInvalidField = null;

                // Validate all fields
                requiredFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        const fieldValid = validateField(field, true);
                        if (!fieldValid && !firstInvalidField) {
                            firstInvalidField = field;
                        }
                        isValid = isValid && fieldValid;
                    }
                });

                if (isValid) {
                    this.submit();
                } else if (firstInvalidField) {
                    firstInvalidField.focus();
                    firstInvalidField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });

            // Modal functions
            window.resetModalForAdd = function() {
                const form = document.getElementById('bankForm');
                form.reset();
                form.action = "{{ route('bank.store') }}";
                form.method = "POST";
                document.getElementById('modalTitle').textContent = "{{ __('Bank.add_bank') }}";
                document.getElementById('submitButton').textContent = "{{ __('Bank.save') }}";
                document.getElementById('bankId').value = '';

                const methodInput = form.querySelector('input[name="_method"]');
                if (methodInput) methodInput.remove();

                // Clear all validation errors
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.textContent = '';
                    el.style.display = 'none';
                });

                document.getElementById('status').checked = true;
            };

            window.populateModalForEdit = function(bank) {
                const form = document.getElementById('bankForm');
                form.reset();
                const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
                form.action = `${baseUrl}/bank/${bank.id}`;
                form.method = "POST";

                document.getElementById('modalTitle').textContent = "{{ __('Bank.edit_bank') }}";
                document.getElementById('submitButton').textContent = "{{ __('Bank.update') }}";
                document.getElementById('bankId').value = bank.id;

                ['branch_name', 'bank_name', 'account_no', 'ifsc_no'].forEach(field => {
                    document.getElementById(field).value = bank[field];
                });

                document.getElementById('status').checked = bank.status == 1;

                let methodInput = form.querySelector('input[name="_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = 'PUT';

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => {
                    el.textContent = '';
                    el.style.display = 'none';
                });
            };

            // DataTable initialization for bank cards
            const $table = $('#bank-cards-table');
            const $container = $('#bankCardContainer');
            const table = $table.DataTable({
                processing: false,
                serverSide: true,
                ajax: "{{ route('bank.data') }}",
                columns: [{
                        data: 'card',
                        name: 'card',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'bank_name',
                        name: 'bank_name',
                        visible: false
                    }
                ],
                pageLength: 6,
                searching: true,
                lengthChange: false,
                pagingType: 'simple_numbers',
                dom: 'rt<"row mt-3"<"col-12 col-md-6 d-flex align-items-center"i><"col-12 col-md-6 d-flex justify-content-end"p>>',
                language: {
                    searchPlaceholder: '{{ __("frontend.search_banks") }}',
                    search: '',
                    emptyTable: "<div class='text-center p-4'>{{ __('frontend.no_banks_available_at_the_moment') }}</div>",
                    zeroRecords: "<div class='text-center p-4'>{{ __('frontend.no_matching_banks_found') }}</div>",

                },
                drawCallback: function(settings) {
                    const data = table.rows().data();
                    $container.empty();
                    if (data.length === 0) {
                        $container.append('<div class="text-center p-4">{{ __("frontend.no_data_available") }}</div>');
                    } else {
                        for (let i = 0; i < data.length; i++) {
                            const row = $('<div class="row mb-4"></div>');
                            row.append(`<div class="col-12">${data[i].card}</div>`);
                            $container.append(row);
                        }
                    }
                }
            });

            table.on('preXhr.dt', function() {
                $('#bankCardContainer').empty();
                shimmerLoader.classList.remove('d-none');

            });

            // // Hide loader after data loads
            table.on('xhr.dt', function() {
                shimmerLoader.classList.add('d-none');

            });

            function htmlDecode(input) {
                const e = document.createElement('textarea');
                e.innerHTML = input;
                return e.value;
            }

            $(document).on('click', '.edit-bank-btn', function() {
                const bankDataStr = $(this).attr('data-bank');
                let bankData = {};
                try {
                    const decodedStr = htmlDecode(bankDataStr);
                    bankData = JSON.parse(decodedStr);
                } catch (e) {
                    console.error('Invalid bank data', e, bankDataStr);
                    return;
                }
                populateModalForEdit(bankData);
                $('#bankInfoModal').modal('show');
            });

            // Set default bank handler
            $(document).on('click', '.set-default-btn', function() {
                const bankId = $(this).data('id');
                $.ajax({
                    url: `{{ url('/bank') }}/${bankId}/set-default`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message ||
                                'Failed to set default bank',
                            confirmButtonText: '{{ __('frontend.ok') }}',
                            customClass: {
                                confirmButton: 'btn btn-primary'
                            },
                            buttonsStyling: false,
                        });
                    }
                });
            });

            // Delete bank handler
            $(document).on('click', '.delete-bank-btn', function() {
                const form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message ||
                                        'Bank deleted successfully',
                                    showConfirmButton: true,
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false,
                                    timer: 1500
                                });
                                table.ajax.reload(null, false);
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: xhr.responseJSON?.message ||
                                        'Failed to delete bank',
                                    confirmButtonText: '{{ __('frontend.ok') }}',
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false
                                });
                            }
                        });
                    }
                });
            });

            $('#bankHeaderSearch').on('input', function() {
                table.search(this.value).draw();
                // Show/hide clear button based on input value
                if (this.value.length > 0) {
                    $('#clearBankSearch').show();
                } else {
                    $('#clearBankSearch').hide();
                }
            });

            // Clear search functionality
            $('#clearBankSearch').on('click', function() {
                $('#bankHeaderSearch').val('').focus();
                table.search('').draw();
                $(this).hide();
            });
        });
    </script>
@endpush
