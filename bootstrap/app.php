<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'penghuni.status' => \App\Http\Middleware\CekStatusAkunPenghuni::class,
        ]);
        // Exempt route callback Midtrans dari verifikasi CSRF token
        // karena webhook Midtrans tidak mengirim _token Laravel
        $middleware->preventRequestForgery(except: [
            'penghuni/pembayaran/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
    
