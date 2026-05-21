<div class="d-flex align-items-center justify-content-between px-3 py-3 border-top"
     style="font-size:13px; color:#6b7280">
    <span>
        Menampilkan {{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }}
        dari {{ $data->total() }} kamar
    </span>
    <div class="firabo-pagination">
        <button class="page-btn" wire:click="previousPage"
                {{ !$data->onFirstPage() ?: 'disabled' }}>
            <i class="bi bi-chevron-left"></i>
        </button>
        @foreach($data->getUrlRange(1, $data->lastPage()) as $page => $url)
            <button class="page-btn {{ $page == $data->currentPage() ? 'active' : '' }}"
                    wire:click="gotoPage({{ $page }})">
                {{ $page }}
            </button>
        @endforeach
        <button class="page-btn" wire:click="nextPage"
                {{ $data->hasMorePages() ?: 'disabled' }}>
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</div>