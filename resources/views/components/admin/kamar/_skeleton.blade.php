{{--
    _skeleton.blade.php
    Skeleton rows untuk tbody tabel kamar (desktop).
    Dipanggil saat x-show="!ready" di table.blade.php.
    6 kolom sesuai thead: Nomor, Tipe, Harga, Fasilitas, Status, Aksi
--}}
@for ($i = 0; $i < 6; $i++)
    <tr>
        <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width: 110px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width: 150px;"></div></td>
        <td><div class="skeleton skeleton-badge"></div></td>
        <td class="text-end">
            <div class="d-flex justify-content-end gap-1">
                <div class="skeleton" style="width: 30px; height: 30px; border-radius: 6px;"></div>
                <div class="skeleton" style="width: 30px; height: 30px; border-radius: 6px;"></div>
            </div>
        </td>
    </tr>
@endfor