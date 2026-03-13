<div class="breadcrumb-section">
    <div class="gradient pink-gradient"></div>
    <div class="gradient blue-gradient"></div>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center">
            <nav class="breadcrumb-container" aria-label="breadcrumb">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{url('/')}}">{{ __('frontend.home') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        @php
                            $currentSegment = request()->segment(1);
                            $currentPath = request()->path();
                            
                            // Use the title parameter if provided
                            if (isset($title) && !empty($title)) {
                                $displayTitle = $title;
                            } else {
                                // Fallback logic based on current route
                                $displayTitle = __('frontend.home'); // Default fallback
                                
                                // Check by segment first
                                switch ($currentSegment) {
                                    case 'contact':
                                        $displayTitle = __('frontend.contact_us');
                                        break;
                                    case 'wishlist':
                                        $displayTitle = __('frontend.wishlist');
                                        break;
                                    case 'expert-detail':
                                        $displayTitle = __('frontend.expert_detail');
                                        break;
                                    case 'faq':
                                        $displayTitle = __('frontend.faq');
                                        break;
                                    case 'bank':
                                        $displayTitle = __('frontend.bank');
                                        break;
                                    case 'notifications':
                                        $displayTitle = __('frontend.notifications');
                                        break;
                                    case 'privacy':
                                        $displayTitle = __('frontend.privacy_policy');
                                        break;
                                    case 'terms':
                                        $displayTitle = __('frontend.terms_conditions');
                                        break;
                                    case 'support':
                                        $displayTitle = __('frontend.support');
                                        break;
                                    case 'data-deletion':
                                        $displayTitle = __('frontend.data_deletion');
                                        break;
                                    case 'about':
                                        $displayTitle = __('frontend.about_us');
                                        break;
                                    case 'refund':
                                        $displayTitle = __('frontend.refund_policy');
                                        break;
                                    case 'blog-details':
                                        $displayTitle = __('frontend.blog_details');
                                        break;
                                    case 'changepassword':
                                        $displayTitle = __('frontend.change_password');
                                        break;
                                    default:
                                        // Check by path if segment didn't match
                                        if (str_contains($currentPath, 'privacy')) {
                                            $displayTitle = __('frontend.privacy_policy');
                                        } elseif (str_contains($currentPath, 'terms')) {
                                            $displayTitle = __('frontend.terms_conditions');
                                        } elseif (str_contains($currentPath, 'support')) {
                                            $displayTitle = __('frontend.support');
                                        } elseif (str_contains($currentPath, 'data-deletion')) {
                                            $displayTitle = __('frontend.data_deletion');
                                        } elseif (str_contains($currentPath, 'about')) {
                                            $displayTitle = __('frontend.about_us');
                                        } elseif (str_contains($currentPath, 'refund')) {
                                            $displayTitle = __('frontend.refund_policy');
                                        } elseif (str_contains($currentPath, 'blog-details')) {
                                            $displayTitle = __('frontend.blog_details');
                                        } elseif (str_contains($currentPath, 'bank')) {
                                            $displayTitle = __('frontend.bank');
                                        } elseif (str_contains($currentPath, 'notification')) {
                                            $displayTitle = __('frontend.notifications');
                                        } elseif (str_contains($currentPath, 'changepassword')) {
                                            $displayTitle = __('frontend.change_password');
                                        } elseif (isset($pageTitle)) {
                                            $displayTitle = $pageTitle;
                                        }
                                        break;
                                }
                            }
                            
                            // Remove any trailing colons from the title
                            $displayTitle = rtrim($displayTitle, ':');
                        @endphp
                        {{ $displayTitle }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>  
</div>