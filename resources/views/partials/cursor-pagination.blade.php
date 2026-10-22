@if(isset($paginator) && ($paginator->hasMorePages() || $paginator->previousCursor() || $paginator->nextCursor()))
    <div class="cursor-pagination-bar">
        <div class="pagination-meta">
            <span>Showing <strong>{{ $paginator->count() }}</strong> items on this page</span>
        </div>
        <div class="pagination-nav">
            @if($paginator->previousCursor())
                <a href="{{ $paginator->previousPageUrl() }}" class="cursor-nav-btn prev">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                    <span>Previous</span>
                </a>
            @else
                <span class="cursor-nav-btn disabled prev">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M15 18l-6-6 6-6"/></svg>
                    <span>Previous</span>
                </span>
            @endif

            <span class="cursor-nav-indicator">
                Cursor Navigation
            </span>

            @if($paginator->nextCursor())
                <a href="{{ $paginator->nextPageUrl() }}" class="cursor-nav-btn next">
                    <span>Next</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            @else
                <span class="cursor-nav-btn disabled next">
                    <span>Next</span>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M9 18l6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </div>
@endif
