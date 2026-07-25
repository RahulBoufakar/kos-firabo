{{-- _pagination.blade.php — pagination penghuni --}}
@if($data->hasPages())
    <div class="d-flex align-items-center justify-content-between px-3 py-2 flex-wrap gap-2"
         style="border-top: 1px solid var(--firabo-border); background: #fafafa;">
 
        <span style="font-size: .8rem; color: #6b7280;">
            Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
            dari {{ $data->total() }} penghuni
        </span>
 
        <div class="firabo-pagination">
            {{-- Prev --}}
            @if($data->onFirstPage())
                <button class="page-btn" disabled>
                    <i class="bi bi-chevron-left"></i>
                </button>
            @else
                <button class="page-btn" wire:click="previousPage" wire:loading.attr="disabled">
                    <i class="bi bi-chevron-left"></i>
                </button>
            @endif
 
            {{-- Page numbers --}}
            @foreach($data->getUrlRange(1, $data->lastPage()) as $page => $url)
                @if($page == $data->currentPage())
                    <button class="page-btn active">{{ $page }}</button>
                @else
                    <button class="page-btn" wire:click="gotoPage({{ $page }})">{{ $page }}</button>
                @endif
            @endforeach
 
            {{-- Next --}}
            @if($data->hasMorePages())
                <button class="page-btn" wire:click="nextPage" wire:loading.attr="disabled">
                    <i class="bi bi-chevron-right"></i>
                </button>
            @else
                <button class="page-btn" disabled>
                    <i class="bi bi-chevron-right"></i>
                </button>
            @endif
        </div>
    </div>
@endif