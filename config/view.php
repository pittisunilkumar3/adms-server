<?php

/**
 * View Configuration - NO STORAGE FOLDER
 * Compiled Blade templates use system temp directory
 * No storage/ folder required
 */
return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    | Views are loaded from resources/views/
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path - SYSTEM TEMP DIRECTORY
    |--------------------------------------------------------------------------
    | Compiled Blade templates stored in system temp directory
    | No storage/ folder needed
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        sys_get_temp_dir() . '/laravel_views'
    ),

];
