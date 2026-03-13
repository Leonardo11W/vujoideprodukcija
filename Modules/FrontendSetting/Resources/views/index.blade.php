@extends('backend.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-body setting-pills">
                    <ul class="nav flex-column list-group list-group-flush tabslink" role="tablist">
                        @hasanyrole('admin|demo_admin')
                            <li class="nav-item mb-3">
                                <a href="javascript:void(0)"
                                   data-href="{{ route('layout_frontend_page', ['page' => 'landing-page-setting']) }}"
                                   data-target=".paste_here"
                                   class="nav-link tab-link btn btn-border w-100 {{ $page == 'landing-page-setting' ? 'active' : '' }}">
                                   {{ __('messages.home') }}
                                </a>
                            </li>
                            <li class="nav-item mb-3">
                                <a href="javascript:void(0)"
                                   data-href="{{ route('layout_frontend_page', ['page' => 'header-menu-setting']) }}"
                                   data-target=".paste_here"
                                   class="nav-link tab-link btn btn-border w-100 {{ $page == 'header-menu-setting' ? 'active' : '' }}">
                                   {{ __('messages.header') }}
                                </a>
                            </li>
                            <li class="nav-item mb-3">
                                <a href="javascript:void(0)"
                                   data-href="{{ route('layout_frontend_page', ['page' => 'footer-setting']) }}"
                                   data-target=".paste_here"
                                   class="nav-link tab-link btn btn-border w-100 {{ $page == 'footer-setting' ? 'active' : '' }}">
                                   {{ __('messages.footer') }}
                                </a>
                            </li>
                        @endhasanyrole
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="col-lg-9">
            <div class="card">
                <div class="card-body">
                    <div class="paste_here">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading content...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
</div>
@endsection

@push('after-scripts')
<script>
(function ($) {
    let activeRequest = null;

    function loadTab($el) {
        const url = $el.data("href");
        const target = $el.data("target");

        if (!url || !target) return;

        // cancel previous request
        if (activeRequest) {
            activeRequest.abort();
        }

        // show loader
        $(target).html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Loading content...</p>
            </div>
        `);

        // fetch partial view
        activeRequest = $.get(url)
            .done(function (data) {
                $(target).html(data);
            })
            .fail(function (xhr, status) {
                if (status !== "abort") {
                    $(target).html(`
                        <div class="alert alert-danger">
                            Failed to load content. Please try again.
                        </div>
                    `);
                }
            })
            .always(function () {
                activeRequest = null;
            });
    }

    $(document).ready(function () {
        // delegate event (so it still works after reloads)
        $(document).on("click", ".tab-link", function (e) {
            e.preventDefault();
            $(".tab-link").removeClass("active");
            $(this).addClass("active");
            loadTab($(this));
        });

        // load first active tab on page load
        let $active = $(".tab-link.active");
        if ($active.length === 0) {
            $active = $(".tab-link").first().addClass("active");
        }
        loadTab($active);
    });
})(jQuery);
</script>
@endpush
