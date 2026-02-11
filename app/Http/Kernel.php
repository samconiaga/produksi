<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * Global HTTP middleware stack.
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,

        // CORS (pilih salah satu yang ada di project kamu)
        // Laravel 10+ biasanya:
        // \Illuminate\Http\Middleware\HandleCors::class,

        // Kalau project kamu masih pakai Fruitcake:
        \Fruitcake\Cors\HandleCors::class,

        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,

            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     */
    protected $routeMiddleware = [
        // Auth
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        // Core
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,

        // Custom (punya kamu)
        'isGlobalAccess' => \App\Http\Middleware\IsGlobalAccess::class,
        'admin' => \App\Http\Middleware\Admin::class,

        // Legacy (biarin kalau masih dipakai)
        'guru' => \App\Http\Middleware\Guru::class,
        'siswa' => \App\Http\Middleware\Siswa::class,
        'isWaliKelas' => \App\Http\Middleware\WaliKelas::class,

        // ✅ Role based (dipakai: middleware('role:Admin,Produksi,...'))
        'role' => \App\Http\Middleware\Role::class,

        // ✅ Produksi Level (dipakai: middleware('produksi.level:ADMIN,SPV'))
        'produksi.level' => \App\Http\Middleware\ProduksiLevel::class,
    ];
}
