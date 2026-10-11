@if ($paginator->hasPages())
    <nav aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="pagination-disabled" aria-disabled="true">← Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Sebelumnya</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page === $paginator->currentPage())
                <span class="current-page" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $url }}" aria-label="Halaman {{ $page }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya →</a>
        @else
            <span class="pagination-disabled" aria-disabled="true">Berikutnya →</span>
        @endif
    </nav>
@endif
