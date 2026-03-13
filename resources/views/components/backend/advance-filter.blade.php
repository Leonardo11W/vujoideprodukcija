<div class="offcanvas offcanvas-end" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel" data-bs-backdrop="true" data-bs-keyboard="true">
  <div class="offcanvas-header border-bottom">
    @if(isset($title))
      {{ $title }}
    @endif
    <button type="button" class="btn-close mb-1" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body">
    {{ $slot }}
  </div>
  <div class="offcanvas-body">
    @if(isset($footer))
      {{$footer}}
    @endif
  </div>
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
