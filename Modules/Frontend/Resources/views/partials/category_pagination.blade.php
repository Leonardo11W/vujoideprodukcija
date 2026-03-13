@if($categories->hasPages())
<div class="row mt-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="showing-result">
                Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }} entries
            </div>
            <nav>
                <ul class="pagination mb-0">
                    @if($categories->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link">&laquo; Previous</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $categories->previousPageUrl() }}" data-page="{{ $categories->currentPage() - 1 }}">&laquo; Previous</a>
                        </li>
                    @endif

                    @foreach($categories->getUrlRange(1, $categories->lastPage()) as $page => $url)
                        <li class="page-item {{ $categories->currentPage() == $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}" data-page="{{ $page }}">{{ $page }}</a>
                        </li>
                    @endforeach

                    @if($categories->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $categories->nextPageUrl() }}" data-page="{{ $categories->currentPage() + 1 }}">Next &raquo;</a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link">Next &raquo;</span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    </div>
</div>
@endif 