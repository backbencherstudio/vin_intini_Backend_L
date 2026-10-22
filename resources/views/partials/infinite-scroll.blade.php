@php
    $paginator = $paginator ?? null;
    $hasMore = $paginator ? $paginator->hasMorePages() : false;
    $nextCursor = $paginator && $paginator->nextCursor() ? $paginator->nextCursor()->encode() : null;
    $targetGridId = $targetGridId ?? 'grid-dedicated-section';
    $feedMode = $feedMode ?? 'section';
@endphp

<div class="infinite-scroll-container" 
     id="infiniteScrollWrap"
     data-has-more="{{ $hasMore ? 'true' : 'false' }}"
     data-next-cursor="{{ $nextCursor ?? '' }}"
     data-target-grid="{{ $targetGridId }}"
     data-feed-mode="{{ $feedMode }}">

    <!-- Sentinel trigger element for IntersectionObserver -->
    <div id="infiniteScrollSentinel" class="infinite-scroll-sentinel" aria-hidden="true"></div>

    <!-- Active Loading Spinner (Auto-loads on scroll) -->
    <div id="infiniteScrollLoader" class="infinite-scroll-loader" style="display: none;">
        <div class="scroll-spinner-ring"></div>
        <span class="scroll-spinner-text">Loading more products...</span>
    </div>

    <!-- End of Feed Indicator (When all products in this section/view are loaded) -->
    <div id="infiniteScrollEnd" class="infinite-scroll-end" style="display: {{ $hasMore ? 'none' : 'flex' }};">
        <div class="scroll-end-line"></div>
        <div class="scroll-end-badge">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span id="infiniteScrollEndText">All products loaded</span>
        </div>
        <div class="scroll-end-line"></div>
    </div>

    <!-- Graceful Error / Retry State -->
    <div id="infiniteScrollError" class="infinite-scroll-error" style="display: none;">
        <span class="error-msg-text">Failed to load more products. Please try again.</span>
        <button type="button" class="scroll-retry-btn" onclick="retryInfiniteScroll()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="23 4 23 10 17 10"></polyline>
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
            </svg>
            <span>Retry</span>
        </button>
    </div>
</div>
