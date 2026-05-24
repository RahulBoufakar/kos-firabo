{{-- _pagination.blade.php — pagination jadwal --}}
@if($data->hasPages())
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
@endif