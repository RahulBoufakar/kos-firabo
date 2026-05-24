{{-- _skeleton.blade.php — skeleton rows untuk tbody desktop jadwal --}}
@for($i = 0; $i < 5; $i++)
    <tr>
        <td><div class="skeleton skeleton-text" style="width:130px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width:50px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width:80px;"></div></td>
        <td><div class="skeleton skeleton-text" style="width:60px;"></div></td>
        <td><div class="skeleton skeleton-badge"></div></td>
        <td class="text-end">
            <div class="skeleton" style="width:32px; height:28px; border-radius:6px; margin-left:auto;"></div>
        </td>
    </tr>
@endfor