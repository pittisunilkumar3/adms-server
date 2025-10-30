<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Minimal HTTP Kernel - No Middleware
 * All middleware removed for simplest attendance system
 * Biometric devices can post data without any authentication or CSRF checks
 */
class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     * EMPTY - No global middleware
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [];

    /**
     * The application's route middleware groups.
     * EMPTY - No middleware groups
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [],
        'api' => [],
    ];

    /**
     * The application's middleware aliases.
     * EMPTY - No middleware aliases
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [];
}
