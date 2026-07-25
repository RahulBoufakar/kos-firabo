<?php

use App\Models\Kamar;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithPagination;

    public string $activeView = 'table';

    public string $search       = '';
    public string $filterStatus = '';

    public bool   $isEditing    = false;
    public ?int   $editingId    = null;
    public string $nomor_kamar  = '';
    public string $tipe_kamar   = '';
    public string $harga_sewa   = '';
    public string $fasilitas    = '';
    public string $status_kamar = 'tersedia';

    public bool $kamarSedangDihuni = false;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        Gate::authorize('create', Kamar::class);

        $this->reset(['nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas', 'editingId']);
        $this->status_kamar      = 'tersedia';
        $this->kamarSedangDihuni = false;
        $this->isEditing         = false;
        $this->resetValidation();
        $this->activeView        = 'skeleton';
    }

    public function openEdit(int $id): void
    {
        $kamar = Kamar::findOrFail($id);
        Gate::authorize('update', $kamar);

        $this->editingId         = $kamar->kamar_id;
        $this->nomor_kamar       = $kamar->nomor_kamar;
        $this->tipe_kamar        = $kamar->tipe_kamar;
        $this->harga_sewa        = (string) $kamar->harga_sewa;
        $this->fasilitas         = $kamar->fasilitas ?? '';
        $this->status_kamar      = $kamar->status_kamar;
        $this->kamarSedangDihuni = $kamar->hunianAktif()->exists();
        $this->isEditing         = true;
        $this->resetValidation();
        $this->activeView        = 'skeleton';
    }

    public function cancelForm(): void
    {
        $this->reset(['nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas', 'editingId', 'isEditing', 'kamarSedangDihuni']);
        $this->status_kamar = 'tersedia';
        $this->resetValidation();
        $this->activeView   = 'table';
    }

    public function save(): void
    {
        $kamar = $this->isEditing ? Kamar::findOrFail($this->editingId) : null;

        if ($this->isEditing) {
            Gate::authorize('update', $kamar);
        } else {
            Gate::authorize('create', Kamar::class);
        }

        $sedangDihuniSekarang = $kamar?->hunianAktif()->exists() ?? false;
        $statusDiizinkan      = $sedangDihuniSekarang ? ['terisi'] : ['tersedia', 'nonaktif'];

        $this->validate([
            'nomor_kamar'  => 'required|string|max:10|unique:tb_kamar,nomor_kamar,'
                              . ($this->editingId ?? 'NULL') . ',kamar_id',
            'tipe_kamar'   => 'required|string|max:50',
            'harga_sewa'   => 'required|numeric|min:0',
            'fasilitas'    => 'nullable|string',
            'status_kamar' => ['required', Rule::in($statusDiizinkan)],
        ], [
            'nomor_kamar.required' => 'Nomor kamar wajib diisi.',
            'nomor_kamar.string'   => 'Format nomor kamar tidak valid.',
            'nomor_kamar.max'      => 'Nomor kamar maksimal terdiri dari 10 karakter.',
            'nomor_kamar.unique'   => 'Nomor kamar sudah digunakan. Silakan gunakan nomor lain.',
            'tipe_kamar.required'  => 'Tipe kamar wajib diisi.',
            'tipe_kamar.string'    => 'Format tipe kamar tidak valid.',
            'tipe_kamar.max'       => 'Tipe kamar maksimal terdiri dari 50 karakter.',
            'harga_sewa.required'  => 'Harga sewa wajib diisi.',
            'harga_sewa.numeric'   => 'Harga sewa harus berupa angka.',
            'harga_sewa.min'       => 'Harga sewa tidak boleh kurang dari 0.',
            'fasilitas.string'     => 'Format teks fasilitas tidak valid.',
            'status_kamar.required'=> 'Status kamar wajib dipilih.',
            'status_kamar.in'      => $sedangDihuniSekarang
                ? 'Status tidak dapat diubah karena kamar masih memiliki penghuni aktif.'
                : 'Status "Terisi" tidak dapat dipilih secara manual — status ini hanya terbentuk otomatis saat ada penghuni yang menempati kamar.',
        ]);

        if (! in_array($this->status_kamar, $statusDiizinkan, true)) {
            $this->addError('status_kamar', 'Perubahan status ditolak — kondisi kamar sudah berubah, silakan muat ulang.');
            return;
        }

        if ($this->isEditing) {
            $kamar->update([
                'nomor_kamar'  => $this->nomor_kamar,
                'tipe_kamar'   => $this->tipe_kamar,
                'harga_sewa'   => $this->harga_sewa,
                'fasilitas'    => $this->fasilitas,
                'status_kamar' => $this->status_kamar,
            ]);
        } else {
            Kamar::create([
                'nomor_kamar'  => $this->nomor_kamar,
                'tipe_kamar'   => $this->tipe_kamar,
                'harga_sewa'   => $this->harga_sewa,
                'fasilitas'    => $this->fasilitas,
                'status_kamar' => 'tersedia',
            ]);
        }

        $pesan = $this->isEditing ? 'Kamar berhasil diperbarui.' : 'Kamar baru berhasil ditambahkan.';

        $this->reset(['nomor_kamar', 'tipe_kamar', 'harga_sewa', 'fasilitas', 'editingId', 'isEditing', 'kamarSedangDihuni']);
        $this->status_kamar = 'tersedia';
        $this->activeView   = 'table';
        $this->resetPage();

        $this->dispatch('toast', pesan: $pesan, tipe: 'sukses');
    }

    // Dipanggil HANYA setelah user konfirmasi di _modal-hapus
    public function delete(int $id): void
    {
        $kamar = Kamar::findOrFail($id);
        Gate::authorize('delete', $kamar);

        // ── VALIDASI SERVER-SIDE ─────────────────────────────────────────
        // Tombol hapus sudah di-disable di client saat status 'terisi', tapi
        // wire:click bisa dipanggil manual lewat devtools. Cek ulang di sini
        // dari RELASI database (bukan kolom status_kamar saja) — kalau masih
        // ada penghuni aktif, TOLAK permintaan sepenuhnya dan kembalikan
        // error. Menonaktifkan penghuni adalah tindakan administratif
        // tersendiri dengan konsekuensi (piutang, jadwal tagihan) yang harus
        // dilakukan secara sadar lewat menu Penghuni — bukan sebagai efek
        // samping tak terduga dari tombol "Hapus Kamar".
        if ($kamar->hunianAktif()->exists()) {
            $this->dispatch(
                'toast',
                pesan: "Kamar {$kamar->nomor_kamar} tidak dapat dihapus karena masih memiliki penghuni aktif. Nonaktifkan penghuninya terlebih dahulu melalui menu Penghuni.",
                tipe: 'gagal'
            );
            return;
        }

        // Kamar tidak sedang dihuni tapi PERNAH punya histori hunian —
        // jangan hard delete, cukup nonaktifkan. Ini melindungi riwayat
        // tagihan/pembayaran lama supaya tidak kehilangan induk data kamar.
        if ($kamar->hunian()->exists()) {
            $kamar->update(['status_kamar' => 'nonaktif']);
            $this->resetPage();
            $this->dispatch(
                'toast',
                pesan: "Kamar {$kamar->nomor_kamar} pernah memiliki riwayat penghuni sehingga tidak dihapus permanen — status diubah menjadi Nonaktif.",
                tipe: 'sukses'
            );
            return;
        }

        // Kamar benar-benar bersih (tidak pernah dihuni sama sekali) -> aman dihapus permanen.
        $kamar->delete();
        $this->resetPage();
        $this->dispatch('toast', pesan: 'Kamar berhasil dihapus.', tipe: 'sukses');
    }

    public function render()
    {
        $kamar = Kamar::query()
            // withCount dipakai supaya modal delete bisa menampilkan pesan
            // yang tepat (akan dinonaktifkan vs dihapus permanen) TANPA
            // query tambahan per baris (hindari N+1 di blade loop).
            ->withCount('hunian')
            ->when($this->search, fn($q) =>
                $q->where('nomor_kamar', 'like', "%{$this->search}%")
                  ->orWhere('tipe_kamar', 'like', "%{$this->search}%")
            )
            ->when($this->filterStatus, fn($q) =>
                $q->where('status_kamar', $this->filterStatus)
            )
            ->orderBy('nomor_kamar')
            ->paginate(10);

        return view('components.admin.kamar.table', compact('kamar'));
    }
};
?>

{{--
    Alpine x-data di root mengurus dua hal sekaligus:
    1. showSkeleton — timer 700ms transisi tabel → form
    2. deletePopup  — state popup konfirmasi hapus
    Keduanya harus di root karena _modal-hapus dan view:skeleton
    perlu mengakses scope Alpine yang sama.
--}}
<div
    x-data="{
        showSkeleton: false,
        deletePopup: { show: false, id: null, nama: '', punyaHistori: false },
        toast: { show: false, pesan: '', tipe: 'sukses' }, // tipe: 'sukses' | 'gagal'

        mulaiSkeleton() {
            this.showSkeleton = true;
            setTimeout(() => {
                this.showSkeleton = false;
                $wire.activeView = 'form';
            }, 700);
        },
        bukaPopupHapus(id, nama, punyaHistori) {
            this.deletePopup = { show: true, id, nama, punyaHistori };
        },
        tutupPopupHapus() {
            this.deletePopup = { show: false, id: null, nama: '', punyaHistori: false };
        },
        konfirmasiHapus() {
            $wire.delete(this.deletePopup.id);
            this.tutupPopupHapus();
        },
        tampilToast(pesan, tipe = 'sukses') {
            this.toast = { show: true, pesan, tipe };
            setTimeout(() => this.toast.show = false, 3000);
        }
    }"
    x-init="
        $watch('$wire.activeView', val => {
            if (val === 'skeleton') mulaiSkeleton();
        });
        $wire.on('toast', ({ pesan, tipe }) => tampilToast(pesan, tipe));
    "
>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: TABEL
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'table'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0"
    >
        {{-- Toolbar --}}
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3 flex-wrap">
            <div class="d-flex gap-2 flex-wrap">
                <div class="search-bar">
                    <i class="bi bi-search"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Cari kamar..."
                    >
                </div>
                <select
                    wire:model.live="filterStatus"
                    class="firabo-input"
                    style="width: auto; height: 38px; padding: 0 0.875rem;"
                >
                    <option value="">Semua Status</option>
                    <option value="tersedia">Tersedia</option>
                    <option value="terisi">Terisi</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>
            <button wire:click="openCreate" class="btn-firabo">
                <i class="bi bi-plus-lg"></i> Tambah Kamar
            </button>
        </div>

        {{-- Table + Card wrapper --}}
        <div
            class="table-card-wrapper"
            x-data="{ ready: false }"
            x-init="setTimeout(() => ready = true, 600)"
        >
            {{-- ── Desktop ── --}}
            <div class="table-view">
                <div class="firabo-card p-0 overflow-hidden">
                    <table class="firabo-table">
                        <thead>
                            <tr>
                                <th>Nomor Kamar</th>
                                <th>Tipe Kamar</th>
                                <th>Harga Sewa</th>
                                <th>Fasilitas</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody x-show="!ready" x-cloak>
                            @include('components.admin.kamar._skeleton')
                        </tbody>
                        <tbody x-show="ready" x-cloak>
                            @forelse ($kamar as $item)
                                <tr>
                                    <td>
                                        <span style="color: var(--firabo-primary); font-weight: 500;">
                                            {{ $item->nomor_kamar }}
                                        </span>
                                    </td>
                                    <td>{{ $item->tipe_kamar }}</td>
                                    <td>Rp {{ number_format($item->harga_sewa, 0, ',', '.') }}/bln</td>
                                    <td style="max-width: 180px;">
                                        <span class="text-truncate d-block" title="{{ $item->fasilitas }}">
                                            {{ $item->fasilitas ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->status_kamar === 'tersedia')
                                            <span class="badge-tersedia">Tersedia</span>
                                        @elseif ($item->status_kamar === 'terisi')
                                            <span class="badge-terisi">Terisi</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button
                                            wire:click="openEdit({{ $item->kamar_id }})"
                                            class="btn btn-sm btn-outline-secondary me-1"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button
                                            @click="bukaPopupHapus(
                                                {{ $item->kamar_id }},
                                                '{{ addslashes('Kamar '.$item->nomor_kamar) }}',
                                                {{ $item->hunian_count > 0 ? 'true' : 'false' }}
                                            )"
                                            class="btn btn-sm btn-outline-danger"
                                            {{ $item->status_kamar === 'terisi' ? 'disabled' : '' }}
                                            title="Hapus"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                                        Tidak ada data kamar ditemukan
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @include('components.admin.kamar._pagination', ['data' => $kamar])
                </div>
            </div>

            {{-- ── Mobile: Card View ── --}}
            <div class="card-view">
                <div x-show="!ready" x-cloak>
                    @for ($i = 0; $i < 4; $i++)
                        <div class="item-card">
                            <div class="item-card-header">
                                <div class="skeleton skeleton-text" style="width:70px; height:16px;"></div>
                                <div class="skeleton" style="width:70px; height:28px; border-radius:6px;"></div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:90px;"></div>
                                </div>
                                <div class="item-card-field">
                                    <div class="skeleton skeleton-text" style="width:40px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-badge"></div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="skeleton skeleton-text" style="width:50px; margin-bottom:4px;"></div>
                                    <div class="skeleton skeleton-text" style="width:140px;"></div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
                <div x-show="ready" x-cloak>
                    @forelse ($kamar as $item)
                        <div class="item-card">
                            <div class="item-card-header">
                                <span class="item-card-title">{{ $item->nomor_kamar }}</span>
                                <div class="item-card-actions">
                                    <button
                                        wire:click="openEdit({{ $item->kamar_id }})"
                                        class="btn btn-sm btn-outline-secondary"
                                    ><i class="bi bi-pencil"></i></button>
                                    <button
                                        @click="bukaPopupHapus(
                                            {{ $item->kamar_id }},
                                            '{{ addslashes('Kamar '.$item->nomor_kamar) }}',
                                            {{ $item->hunian_count > 0 ? 'true' : 'false' }}
                                        )"
                                        class="btn btn-sm btn-outline-danger"
                                        {{ $item->status_kamar === 'terisi' ? 'disabled' : '' }}
                                        ><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <div class="item-card-body">
                                <div class="item-card-field">
                                    <div class="field-label">Tipe</div>
                                    <div class="field-value">{{ $item->tipe_kamar }}</div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Status</div>
                                    <div class="field-value">
                                        @if ($item->status_kamar === 'tersedia')
                                            <span class="badge-tersedia">Tersedia</span>
                                        @elseif ($item->status_kamar === 'terisi')
                                            <span class="badge-terisi">Terisi</span>
                                        @else
                                            <span class="badge-nonaktif">Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="item-card-field">
                                    <div class="field-label">Harga Sewa</div>
                                    <div class="field-value">
                                        Rp {{ number_format($item->harga_sewa, 0, ',', '.') }}/bln
                                    </div>
                                </div>
                                <div class="item-card-field full-width">
                                    <div class="field-label">Fasilitas</div>
                                    <div class="field-value" style="font-size:12px;">
                                        {{ $item->fasilitas ?? '-' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            Tidak ada data kamar ditemukan
                        </div>
                    @endforelse
                    @include('components.admin.kamar._pagination', ['data' => $kamar])
                </div>
            </div>

        </div>{{-- /table-card-wrapper --}}
    </div>{{-- /view:table --}}

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: SKELETON TRANSISI
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="showSkeleton"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-end="opacity-0"
        x-cloak
    >
        @include('components.admin.kamar._skeleton-form')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         VIEW: FORM TAMBAH / EDIT
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.activeView === 'form'"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
    >
        @include('components.admin.kamar._modal-form')
    </div>

    {{-- ════════════════════════════════════════════════════════════════
         POPUP HAPUS
    ════════════════════════════════════════════════════════════════ --}}
    @include('components.admin.kamar._modal-delete')

    {{-- ════════════════════════════════════════════════════════════════
         TOAST NOTIFICATION — pojok kanan bawah
         Dipicu via $wire.on('toast') dari Livewire dispatch().

         Fix: static style dan dynamic :style tidak bisa digabung —
         Alpine `:style` akan override/replace `style` statis sepenuhnya.
         Solusi: semua style statis masuk ke CSS class `.firabo-toast`,
         dynamic hanya untuk warna (via x-bind:class).
    ════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-3"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-end="opacity-0 translate-y-3"
        x-cloak
        class="firabo-toast"
        :class="toast.tipe === 'sukses' ? 'firabo-toast--sukses' : 'firabo-toast--gagal'"
    >
        <i
            class="bi"
            :class="toast.tipe === 'sukses' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'"
        ></i>
        <span x-text="toast.pesan"></span>
    </div>

</div>{{-- /root --}}
