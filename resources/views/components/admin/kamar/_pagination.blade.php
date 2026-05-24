{{--
    _pagination.blade.php
    Pagination reusable untuk tabel dan mobile card view.

    Cara pakai:
    @include('components.admin.kamar._pagination', ['data' => $kamar])
--}}
@if ($data->hasPages())
    <div class="d-flex align-items-center justify-content-between px-3 py-2 flex-wrap gap-2"
         style="border-top: 1px solid var(--firabo-border); background: #fafafa;">

        <span style="font-size: .8rem; color: #6b7280;">
            Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }}
            dari {{ $data->total() }} kamar
        </span>

        <div class="firabo-pagination">
            <button
                class="page-btn"
                wire:click="previousPage"
                {{ $data->onFirstPage() ? 'disabled' : '' }}
            >
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

            <button
                class="page-btn"
                wire:click="nextPage"
                {{ $data->hasMorePages() ? '' : 'disabled' }}
            >
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>

    </div>
@endif