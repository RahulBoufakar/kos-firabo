<?php

namespace App\Events;

use App\Models\Kamar;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PenghuniTerdaftar
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * Event ini di-fire setelah penghuni baru berhasil dibuat,
     * baik melalui registrasi publik maupun penambahan manual oleh Admin.
     *
     * Listener akan menangkap event ini untuk:
     * 1. Membuat record tb_hunian
     * 2. Membuat tb_jadwal_tagihan (tanggal_generate = hari daftar, jatuh_tempo = +7 hari)
     */
    public function __construct(
        public readonly User  $penghuni,
        public readonly Kamar $kamar,
    ) {}

    // /**
    //  * Get the channels the event should broadcast on.
    //  *
    //  * @return array<int, Channel>
    //  */
    // public function broadcastOn(): array
    // {
    //     return [
    //         new PrivateChannel('channel-name'),
    //     ];
    // }
}
