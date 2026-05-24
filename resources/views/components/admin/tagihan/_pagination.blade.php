{{-- _pagination.blade.php
     Dipanggil dengan: @include('components.admin.tagihan._pagination', ['data' => $tagihan])
--}}
@if ($data->hasPages())
    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
        <span style="font-size: .8rem; color: #6b7280;">
            Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
            dari {{ $data->total() }} tagihan
        </span>
        <div class="firabo-pagination">
            <button class="page-btn" wire:click="previousPage"
                {{ $data->onFirstPage() ? 'disabled' : '' }}>
                <i class="bi bi-chevron-left"></i>
            </button>
            @foreach ($data->getUrlRange(
                max(1, $data->currentPage() - 2),
                min($data->lastPage(), $data->currentPage() + 2)
            ) as $page => $url)
                <button
                    class="page-btn {{ $page == $data->currentPage() ? 'active' : '' }}"
                    wire:click="gotoPage({{ $page }})"
                >{{ $page }}</button>
            @endforeach
            <button class="page-btn" wire:click="nextPage"
                {{ $data->hasMorePages() ? '' : 'disabled' }}>
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
    </div>
@endif