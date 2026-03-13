{{-- landing-page-setting.blade.php --}}
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="row">
    <div class="col-lg-12">
        <ul class="nav nav-tabs pay-tabs nav-fill tabslink row-gap-2 column-gap-1" id="tab-text" role="tablist">
            @foreach([
                'section_1' => 'Banner Section',
                'section_2' => 'Quick booking',
                'section_4' => 'Category Section',
                'section_7' => 'Experts Section',
                'section_8' => 'Product Section',
                'section_9' => 'Faq Section',
                'why_choose_section' => 'Why Choose Us',
                'video_section' => 'Video Section',
                'section_10' => 'Customer Section',
                'section_11' => 'Blog Section'
            ] as $section => $title)
                <li class="nav-item payment-link">
                    <a href="javascript:void(0)"
                       data-tabpage="{{ $section }}"
                       class="nav-link {{ $tabpage == $section ? 'active' : '' }}"
                       rel="tooltip">{{ $title }}</a>
                </li>
            @endforeach
        </ul>

        <div class="card payment-content-wrapper mt-3">
            <div class="card-body">
                <div class="tab-content" id="pills-tabContent-1">
                    <div class="tab-pane active p-1">
                        <div class="payment_paste_here text-center py-4">
                            <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                            <p>Loading section...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadContent(tabpage) {
    $.ajax({
        url: '{{ route("landing_layout_page") }}',
        type: 'POST',
        data: {
            tabpage: tabpage,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function() {
            $('.payment_paste_here').html(`
                <div class="text-center py-4">
                    <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                    <p>Loading section...</p>
                </div>
            `);
        },
        success: function(data) {
            if (data && data.view) {
                $('.payment_paste_here').html(data.view);
                if ($.fn.select2) {
                    $('.select2js').select2();
                }
            } else {
                $('.payment_paste_here').html('<p class="text-danger">No content found.</p>');
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            $('.payment_paste_here').html('<p class="text-danger">Error loading section.</p>');
        }
    });
}

$(document).ready(function() {
    // Load initial tab from URL or default
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tabpage') || 'section_1';
    
    // Set the active tab in the UI
    $('.payment-link a').removeClass('active');
    $(`.payment-link a[data-tabpage="${initialTab}"]`).addClass('active');
    
    // Load the content
    loadContent(initialTab);

    // Tab click handler
    $('.payment-link a').on('click', function(e) {
        e.preventDefault();
        const tabpage = $(this).data('tabpage');

        $('.payment-link a').removeClass('active');
        $(this).addClass('active');

        loadContent(tabpage);
        history.replaceState(null, null, "?tabpage=" + tabpage);
    });
});
</script>
