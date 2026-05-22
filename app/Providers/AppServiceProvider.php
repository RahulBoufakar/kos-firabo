<?php

namespace App\Providers;

use App\Events\PenghuniTerdaftar;
use App\Listeners\BuatHunianDanJadwalTagihan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            PenghuniTerdaftar::class,
            BuatHunianDanJadwalTagihan::class,
        );
        // URL::forceScheme('https');
    }
}
