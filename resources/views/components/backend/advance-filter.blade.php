<div class="offcanvas offcanvas-end admin-advance-filter" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="offcanvas-header border-bottom px-4 py-3">
    @if(isset($title))
      {{ $title }}
    @endif
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body px-4">
    {{ $slot }}
  </div>
  @if(isset($footer))
  <div class="offcanvas-body border-top px-4 py-3">
    {{ $footer }}
  </div>
  @endif
</div>
  <!-- @push('after-scripts')
    <script>
      (function () {
        function dedupeOffcanvasBackdrops() {
          try {
            const oc = document.querySelectorAll('.offcanvas-backdrop');
            if (oc.length > 1) {
              oc.forEach((el, idx) => { if (idx > 0) el.remove(); });
            }
            const mb = document.querySelectorAll('.modal-backdrop');
            if (mb.length > 1) {
              mb.forEach((el, idx) => { if (idx > 0) el.remove(); });
            }
          } catch (e) {
            // ignore
          }
        }

        document.addEventListener('shown.bs.offcanvas', function () {
          setTimeout(dedupeOffcanvasBackdrops, 50);
        });

        document.addEventListener('hidden.bs.offcanvas', function () {
          setTimeout(dedupeOffcanvasBackdrops, 50);
        });
      })();
    </script>
  @endpush -->
