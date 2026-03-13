@extends('frontend::layouts.master')
@section('title')
    {{ __('messages.home') }}
@endsection

@section('content')
    @if ($section1Enabled)
        <x-banner :sliders="$sliders" :title="$section1Value['title']" :enable_search="$section1Value['enable_search']" :description="$section1Value['description']" />
    @endif

    @if ($section2Enabled)
        <x-quick_booking :services="$services" />
    @endif
    <x-refer_section />
    <x-branch_section :branches="$branches" :showBranchSection="$showBranchSection" />

    @if ($section4Enabled)
        <!-- Debug Info: Section 4 is enabled -->
        <!-- Debug Info: Categories count: {{ count($filteredCategories) }} -->


        <x-category_section :categories="$filteredCategories" :enabled="$section4Enabled" />
    @else
        <!-- Debug Info: Section 4 is disabled (value: {{ $section4Enabled }}) -->
    @endif

    @if ($section5Enabled)
        <x-package_section :packages="$packages" />
    @endif

    @include('frontend::components.section.why_choose_sections', ['whyChoose' => $whyChoose])

    @if ($section7Enabled)
        <x-expert_section :experts="$experts" :expert_ids="$expert_id" />
    @endif

    @if ($section8Enabled)
        <x-product_section :products="$products" />
    @endif

    @include('frontend::components.section.top_experts_section', ['videoSection' => $videoSection])

    @if ($section9Enabled)
        <x-frontend::section.faq_section :faqs="$faqs" />
    @endif

    @if ($section10Enabled)
        <x-testimonial_section :ratings="$ratings" />
    @endif

    @if ($section11Enabled)
        <x-frontend::section.blog_section :blogs="$blogs" />
    @endif
@endsection
