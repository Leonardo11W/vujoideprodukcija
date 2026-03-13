@extends('frontend::layouts.master')

@section('title')
{{ __('frontend.branch') }}
@endsection

@section('content')

<x-breadcrumb title="{{ __('frontend.branch') }}" />

<div class="branch-section-wrapper section-spacing">
    <div class="container">
        <a href="javascript:void(0)" onclick="goBack()" class="text-body fw-medium d-inline-block mb-4">
            <span class="d-flex align-items-center gap-1">
                <i class="ph ph-caret-left"></i>
                <span>{{__("frontend.back")}}</span>
            </span>
        </a>
        <div class="section-title d-flex flex-wrap gap-3 justify-content-between align-items-center">
            <div class="title-left">
                <span class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">
                    {{__("frontend.our_branches")}}
                </span>
                <h4 class="title mb-0">{{__("frontend.nearby_branches")}}</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group mb-0">
                    <span class="input-group-text"><i class="ph ph-magnifying-glass"></i></span>
                    <input type="text" class="form-control p-2" id="searchInput" placeholder='{{__("frontend.example_branch_city_address")}}'>
                </div>
            </div>
        </div>

        <div id="branchCardContainer"></div>

       <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader d-none">
          @for ($i = 0; $i < 4; $i++)
              @include('frontend::components.card.shimmer_branch_card')
          @endfor
       </div>

        <table id="branch-cards-table" class="table d-none w-100">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Name</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Function to handle back button with fallback
function goBack() {
    // Check if there's history to go back to
    if (window.history.length > 1) {
        // Check if the previous page is a meaningful page (not new tab or external)
        const referrer = document.referrer;
        const currentDomain = window.location.origin;
        
        // If referrer is empty (new tab) or from different domain, go to home
        if (!referrer || !referrer.startsWith(currentDomain)) {
            window.location.href = "{{ route('index') }}";
            return;
        }
        
        // Try to go back
        window.history.back();
    } else {
        // If no history, redirect to home page
        window.location.href = "{{ route('index') }}";
    }
}

$(document).ready(function () {
    const $table = $('#branch-cards-table');
    const $container = $('#branchCardContainer');
    const shimmerLoader = document.querySelector('.shimmer-loader');

    // Show loader initially
    shimmerLoader.classList.remove('d-none');
    shimmerLoader.style.display = 'flex';
    $container.empty();

    const table = $table.DataTable({
       processing: false,
        serverSide: true,
        ajax: "{{ route('frontend.branches.data') }}",
        columns: [
            { data: 'card', name: 'card', orderable: false, searchable: false },
            { data: 'name', name: 'name', visible: false }
        ],
        pageLength: 4,
        searching: true,
        lengthChange: false,
        pagingType: 'simple_numbers',
        dom: 'rt<"row mt-3"<"col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"ip>>',
        language: {
            searchPlaceholder: 'Search by branch name, city, country, or address...',
            search: '',
            emptyTable: "<div class='text-center p-4'>{{__('frontend.no_branches_available_at_the_moment')}}</div>",
            zeroRecords: "<div class='text-center p-4'>{{__('frontend.no_matching_branches_found')}}</div>",

        },
        drawCallback: function (settings) {
            const data = table.rows().data();
            $container.empty();
            
            // Hide shimmer loader when data is loaded
            shimmerLoader.classList.add('d-none');
            shimmerLoader.style.display = 'none';
            
            if (data.length === 0) {
                $container.html('<div class="text-center p-4">{{__("frontend.no_matching_branches_found")}}</div>');
                return;
            }
            for (let i = 0; i < data.length; i += 4) {
                const row = $('<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 gy-4 mb-4"></div>');
                for (let j = i; j < i + 4 && j < data.length; j++) {
                    const cardHtml = `<div class='col branch-selectable' data-branch-id='${data[j].branch_id || ''}'>${data[j].card}</div>`;
                    row.append(cardHtml);
                }
                $container.append(row);
            }

            // Mark currently selected branch
            const selectedBranchId = '{{ session("selected_branch_id") }}';
            if (selectedBranchId) {
                const selectedCard = document.querySelector(`.branch-select-badge-card[data-branch-id="${selectedBranchId}"]`);
                if (selectedCard) {
                    selectedCard.classList.add('selected');
                }
            }

            // Handle branch card clicks (entire card is clickable)
            $('.branch-select-badge-card').off('click').on('click', function(e) {
                // Don't trigger if clicking on the branch info link (let it redirect to detail page)
                if ($(e.target).hasClass('branch-info-box') || $(e.target).closest('.branch-info-box').length) {
                    return;
                }

                const branchId = this.getAttribute('data-branch-id');
                const badge = this.querySelector('span[data-branch-id]');

                // Remove selected class from all branch cards
                document.querySelectorAll('.branch-select-badge-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selected class to clicked card
                this.classList.add('selected');

                // Show loading spinner on the badge
                badge.innerHTML = '<i class="ph ph-spinner ph-spin font-size-16"></i>';

                fetch('{{ route("branch.select") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ branch_id: branchId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear all badges first
                        document.querySelectorAll('span[data-branch-id]').forEach(b => {
                            b.innerHTML = '';
                        });

                        // Add check icon to selected branch
                        badge.innerHTML = '<i class="ph-fill ph-check-circle fs-4 text-success"></i>';

                        const branchSelectedEvent = new CustomEvent('branchSelected', {
                            detail: {
                                branchId: data.branch_id
                            }
                        });
                        document.dispatchEvent(branchSelectedEvent);

                        // Show success message
                        if (typeof window.successSnackbar === 'function') {
                            window.successSnackbar('{{ __("frontend.branch_selected_successfully") }}');
                        }

                        // Redirect after a short delay
                        setTimeout(() => {
                            window.location.href = '{{ route('index') }}';
                        }, 1000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Remove selected class on error
                    this.classList.remove('selected');
                    if (typeof window.errorSnackbar === 'function') {
                        window.errorSnackbar('{{ __("frontend.error_selecting_branch") }}');
                    } else {
                        alert('An error occurred while selecting the branch.');
                    }
                    badge.innerHTML = '';
                });
            });
        }
    });

    $('#searchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    table.on('preXhr.dt', function () {
        $('#branchCardContainer').empty();
        shimmerLoader.classList.remove('d-none');
        shimmerLoader.style.display = 'flex';
    });

    table.on('xhr.dt', function () {
        // Add a small delay to make the loader more visible
        setTimeout(function() {
            shimmerLoader.classList.add('d-none');
            shimmerLoader.style.display = 'none';
        }, 500);
    });

});
</script>

@endpush

@push('styles')
<style>
/* Shimmer loader visibility */
#shimmer-loader {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #e0e0e0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

#shimmer-loader.d-none {
    display: none !important;
}

/* Make sure loader is visible */
#shimmer-loader:not(.d-none) {
    display: flex !important;
}

/* Shimmer animation */
.shimmer-loader {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.placeholder-glow .placeholder {
    animation: placeholder-glow 2s ease-in-out infinite alternate;
}

@keyframes placeholder-glow {
    50% {
        opacity: 0.2;
    }
}

/* Fixed branch card sizing */
.branch-card {
    width: 100%;
    max-width: 300px;
    min-height: 280px;
    margin: 0 auto;
}

/* Ensure shimmer cards match branch card sizing */
.shimmer-loader .branch-card {
    width: 100%;
    max-width: 300px;
    min-height: 280px;
}

/* Fix search input spacing - reduce gap between icon and text */
.input-group .input-group-text {
    padding-right: 4px !important;
    border-right: none !important;
}

.input-group .form-control {
    padding-left: 4px !important;
    border-left: none !important;
}
</style>
@endpush
