@props(['experts', 'expert_ids'])
<div class="export-section section-spacing-bottom vujo-section vujo-experts">
    <div class="container">
        <div class="section-title text-center">
            <span
                class="decorator-title decorator-font text-primary text-uppercase text-decoration-underline">{{ __('frontend.top_experts') }}</span>
            <h4 class="title">{{ __('frontend.meet_our_experts') }}</h4>
        </div>
        <div class="row row-cols-2 row-cols-sm-2 row-cols-lg-3 row-cols-xl-5 gy-4 justify-content-center" id="experts-section-list">
            <!-- Shimmer loader - shown by default -->
            <div id="shimmer-loader" class="d-flex gap-3 flex-wrap p-4 shimmer-loader w-100">
                @for ($i = 0; $i < 5; $i++)
                    @include('frontend::components.card.shimmer_employee_card')
                @endfor
            </div>
        </div>



        {{-- Experts will be dynamically injected here --}}

        <div class="text-center export-button">
            <a href="{{ route('expert') }}" class="btn btn-secondary">{{ __('frontend.view_all') }}</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Section 7 enabled and selected experts from backend
        window.section7Enabled = true;
        window.section7Experts = @json($experts->values());



        function renderExperts(experts) {
            const expertsList = document.getElementById('experts-section-list');
            if (!experts || experts.length === 0) {
                expertsList.innerHTML =
                    '<div class="col-12 text-center py-5"><p class="text-body">{{ e(__("frontend.no_experts_available_at_the_moment")) }}</p></div>';
                return;
            }
            let expertsHtml = '';
            experts.slice(0, 5).forEach(expert => {



                let rating = (typeof expert.rating !== 'undefined' && expert.rating !== null) ?
                    expert.rating :
                    (typeof expert.avg_rating !== 'undefined' && expert.avg_rating !== null) ?
                    expert.avg_rating :
                    4.2;
                
                // Calculate star display
                let fullStars = Math.floor(rating);
                let decimalPart = rating - fullStars;
                let nextStarFill = decimalPart;
                let emptyStars = 5 - fullStars - 1;
                
                // Generate star HTML
                let starsHtml = '';
                for (let i = 0; i < fullStars; i++) {
                    starsHtml += '<i class="ph-fill ph-star text-warning"></i>';
                }
                // Partial star with background gradient
                starsHtml += `<div class="position-relative d-inline-block">
                    <i class="ph ph-star text-body"></i>
                    <div class="position-absolute top-0 start-0" style="width: ${nextStarFill * 100}%; height: 100%; overflow: hidden;">
                        <i class="ph-fill ph-star text-warning"></i>
                    </div>
                </div>`;
                for (let i = 0; i < emptyStars; i++) {
                    starsHtml += '<i class="ph ph-star text-body"></i>';
                }
                
                expertsHtml += `
                <div class="col">
                    <a href="{{ route('expert-detail', '') }}/${expert.id}" class="text-decoration-none">
                        <div class="export-card text-center cursor-pointer">
                            <div class="export-image position-relative">
                                <img class="avatar-128 rounded-circle object-fit-cover" src="${expert.profile_image || expert.image_path || '{{ asset('img/frontend/default-expert.png') }}'}" alt="${expert.full_name || expert.name}">
                            </div>
                            <div class="export-info mt-3">
                                <div class="d-flex align-items-center justify-content-center gap-lg-3 gap-2 mb-1">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        ${starsHtml}
                                    </div>
                                        <span class="fw-semibold heading-color font-size-14">
                                        ${Number(rating).toFixed(1)}
                                    </span>
                                </div>
                                <h5 class="mb-1 title-export">${expert.full_name || expert.name}</h5>
                                <p class="font-size-14 mb-0">${expert.speciality || (expert.profile ? expert.profile.expert : 'Makeup specialist')}</p>
                            </div>
                        </div>
                    </a>
                </div>
            `;
            });
            expertsList.innerHTML = expertsHtml;
        }

        // Function to load experts based on selected branch
        function loadExpertsForSection(branchId) {
            const expertsList = document.getElementById('experts-section-list');
            const shimmerLoader = document.getElementById('shimmer-loader');




            // Show shimmer and clear experts
            shimmerLoader.classList.remove('d-none');
            expertsList.innerHTML = '';
            expertsList.appendChild(shimmerLoader);

            if (!branchId) {
                if (window.section7Enabled && window.section7Experts?.length > 0) {
                    setTimeout(() => {
                        shimmerLoader.classList.add('d-none');
                        renderExperts(window.section7Experts);
                    }, 800); // Loading delay
                } else {
                    shimmerLoader.classList.add('d-none');
                    expertsList.innerHTML =
                        '<div class="col-12 text-center py-5"><p class="text-body">{{ e(__("frontend.no_experts_available_at_the_moment")) }}</p></div>';
                }
                return;
            }

            // Fetch experts for selected branch
            fetch(`{{ route('experts.by-branch') }}?branch_id=${branchId}`)
                .then(response => response.json())
                .then(data => {
                    shimmerLoader.classList.add('d-none');
                    if (data.success && data.experts?.length > 0) {
                        renderExperts(data.experts);
                    } else {
                        expertsList.innerHTML =
                            '<div class="col-12 text-center py-5"><p class="text-body">{{ e(__("frontend.no_experts_for_this_branch")) }}</p></div>';
                        document.querySelector('.export-button').classList.add('d-none');
                    }
                })
                .catch(error => {
                    shimmerLoader.classList.add('d-none');
                    expertsList.innerHTML =
                        '<div class="col-12 text-center py-5"><p class="text-danger">{{ e(__("frontend.error_loading_experts")) }}</p></div>';
                    document.querySelector('.export-button').classList.add('d-none');
                });
        }


        // Listen for branch selection changes
        document.addEventListener('branchSelected', function(event) {
            const branchId = event.detail.branchId;
            loadExpertsForSection(branchId);
        });

        // Also check if there's already a selected branch in session
        const selectedBranchId = '{{ session('selected_branch_id') }}';
        if (selectedBranchId) {
            loadExpertsForSection(selectedBranchId);
        } else {
            // Show shimmer first, then load experts after a delay
            setTimeout(() => {
                const shimmerLoader = document.getElementById('shimmer-loader');
                if (window.section7Enabled && window.section7Experts && window.section7Experts.length > 0) {
                    shimmerLoader.classList.add('d-none');
                    renderExperts(window.section7Experts);
                } else {
                    shimmerLoader.classList.add('d-none');
                    document.getElementById('experts-section-list').innerHTML =
                        '<div class="col-12 text-center py-5"><p class="text-body">{{ e(__("frontend.no_experts_available_at_the_moment")) }}</p></div>';
                }
            }, 1000); // 1 second delay to show shimmer
        }
    });
</script>

