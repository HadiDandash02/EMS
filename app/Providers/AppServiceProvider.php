<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
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
        // Older MySQL/MariaDB (e.g. bundled with XAMPP) limit index keys to 1000 bytes,
        // which a utf8mb4 VARCHAR(255) unique index exceeds. 191 chars is the safe default.
        Schema::defaultStringLength(191);
    }
}
