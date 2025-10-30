<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Minimal Route Service Provider
 * Loads routes without any middleware
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define route configuration
     * Routes loaded without middleware for simplest setup
     */
    public function boot(): void
    {
        $this->routes(function () {
            Route::group([], base_path('routes/web.php'));
        });
    }
}
