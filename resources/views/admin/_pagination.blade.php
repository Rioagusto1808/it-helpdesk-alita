@if ($paginator->hasPages())
    <nav class="pager" aria-label="Halaman">
        @if ($paginator->onFirstPage())
            <span class="btn btn--secondary btn--sm" aria-disabled="true"><x-icon name="arrow-left" class="icon--sm" /> Sebelumnya</span>
        @else
            <a class="btn btn--secondary btn--sm" href="{{ $paginator->previousPageUrl() }}" rel="prev"><x-icon name="arrow-left" class="icon--sm" /> Sebelumnya</a>
        @endif

        <span class="pager-info">Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn--secondary btn--sm" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya <x-icon name="arrow-right" class="icon--sm" /></a>
        @else
            <span class="btn btn--secondary btn--sm" aria-disabled="true">Berikutnya <x-icon name="arrow-right" class="icon--sm" /></span>
        @endif
    </nav>
@endif
