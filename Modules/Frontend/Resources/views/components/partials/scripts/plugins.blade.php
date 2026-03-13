<!-- Slick Slider -->
<script src="{{ asset('vendor/slick/slick.min.js') }}" defer></script>
<!-- DataTables -->
<script src="{{ asset('vendor/datatable/datatables.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Initialize all select elements with form-select class
        $('select.form-select').each(function() {
            // Get the placeholder from data attribute or use default
            const placeholder = $(this).data('placeholder') || 'Select an option';

            $(this).select2({
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                dropdownParent: $(this).parent(),

            });
        });
    });
</script>
