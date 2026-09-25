@if ($paginator->hasPages())
    <nav class="pager" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn--secondary btn--sm" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="btn btn--secondary btn--sm" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        <span class="pager-info">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn--secondary btn--sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="btn btn--secondary btn--sm" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
