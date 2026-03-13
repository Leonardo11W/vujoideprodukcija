<meta name="csrf-token" content="{{ csrf_token() }}" />

<!-- jQuery -->
<!-- <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script> -->

<!-- Select2 CSS -->
<!-- <link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" /> -->

<!-- Select2 JS -->
<!-- <script src="{{ asset('vendor/select2/select2.min.js') }}"></script> -->

<!-- <div class="section-content py-4">
  <div class="card shadow-sm">
    <div class="card-body">

      {{-- Toggle Section --}}
      <div class="row mb-4">
        <div class="col">
          <div class="d-flex justify-content-between align-items-center border p-3 rounded">
            <h5 class="mb-0">Our Branches</h5>
            <div class="form-check form-switch">
              <input
                type="checkbox"
                id="section_3"
                class="form-check-input"
                name="status"
                data-type="section_3"
              />
            </div>
          </div>
        </div>
      </div>

      {{-- Branch Select Section --}}
      <div class="row mb-4" id="enable_section_3" style="display: none;">
        <div class="col">
          <div class="border p-3 rounded bg-body">
            <h6 class="fw-semibold mb-3">Select Branches</h6>
            <div class="mb-3">
              <label for="branch_id" class="form-label">Branch Names</label>
              <select id="branch_id" name="branch_ids[]" class="form-select w-100" multiple></select>
            </div>
          </div>
        </div>
      </div>

      {{-- Save Button --}}
      <div class="row">
        <div class="d-flex justify-content-end mt-4">
          <button id="saveButton" class="btn btn-primary">Save</button>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- Toast --}}
<div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1100;">
  <div id="toastNotif" class="toast align-items-center text-white bg-success border-0" role="alert"
       aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
              aria-label="Close"></button>
    </div>
  </div>
</div> -->

<script>
  $(function () {
    const $toggle = $('#section_3');
    const $branchSection = $('#enable_section_3');
    const $branchSelect = $('#branch_id');
    const sectionType = $toggle.data('type');
    const page = "{{ $tabpage ?? '' }}";
    const getDataRoute = "{{ url('/app/api/get-landing-layout-page-config') }}";
    const postDataRoute = "{{ route('saveLandingLayoutPageConfig') }}";
    const _token = $('meta[name="csrf-token"]').attr('content');
    let settingId = null;

    const toast = new bootstrap.Toast(document.getElementById('toastNotif'));

    // Initialize Select2
    $branchSelect.select2({
      placeholder: 'Search Branches...',
      allowClear: true,
      width: '100%',
      ajax: {
        url: '{{ route("ajax.branches") }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return { q: params.term };
        },
        processResults: function (data) {
          return { results: data };
        },
        cache: true,
      }
    });

    function toggleVisibility(show) {
      if (show) {
        $branchSection.slideDown(200);
        $branchSelect.prop('required', true);
      } else {
        $branchSection.slideUp(200);
        $branchSelect.prop('required', false).val(null).trigger('change');
      }
    }

    function showToast(message, isError = false) {
      $('#toastNotif').removeClass('bg-success bg-danger')
                      .addClass(isError ? 'bg-danger' : 'bg-success');
      $('#toastMessage').text(message);
      toast.show();
    }

    function loadConfig() {
      $.ajax({
        url: getDataRoute,
        type: 'POST',
        data: { _token, type: sectionType, page },
        success: function (res) {
          try {
            const config = typeof res.data?.value === 'string'
              ? JSON.parse(res.data.value)
              : (res.data?.value || {});

            const isEnabled = config.status == 1;
            settingId = res.data?.id ?? null;

            $toggle.prop('checked', isEnabled);
            toggleVisibility(isEnabled);

            if (isEnabled && Array.isArray(config.branch_ids)) {
              config.branch_ids.forEach((id, index) => {
                const name = config.branch_names?.[index] || `Branch ${index + 1}`;
                const option = new Option(name, id, true, true);
                $branchSelect.append(option);
              });
              $branchSelect.trigger('change');
            }
          } catch (e) {
            console.error('JSON parse error:', e);
            showToast('Invalid configuration format.', true);
          }
        },
        error: function () {
          console.error('Failed to load config');
          $toggle.prop('checked', false);
          toggleVisibility(false);
        },
      });
    }

    $toggle.on('change', function () {
      toggleVisibility($(this).is(':checked'));
    });

    $('#saveButton').on('click', function (e) {
      e.preventDefault();
      const isEnabled = $toggle.prop('checked');
      const selectedIds = $branchSelect.val() || [];
      const selectedNames = $branchSelect.select2('data').map((item) => item.text);

      if (isEnabled && selectedIds.length === 0) {
        showToast('Please select at least one branch.', true);
        return;
      }

      const payload = new FormData();
      payload.append('_token', _token);
      payload.append('type', sectionType);
      payload.append('page', page);
      payload.append('status', isEnabled ? 1 : 0);
      if (settingId) {
        payload.append('id', settingId);
      }
      selectedIds.forEach((id) => payload.append('branch_ids[]', id));
      selectedNames.forEach((name) => payload.append('branch_names[]', name));

      $('#saveButton').prop('disabled', true).text('Saving...');

      $.ajax({
        url: postDataRoute,
        type: 'POST',
        data: payload,
        contentType: false,
        processData: false,
        success: function (res) {
          showToast(res.message || 'Saved successfully');
        },
        error: function (xhr) {
          console.error(xhr);
          showToast('Error saving settings', true);
        },
        complete: function () {
          $('#saveButton').prop('disabled', false).text('Save');
        }
      });
    });

    loadConfig();
  });
</script>
