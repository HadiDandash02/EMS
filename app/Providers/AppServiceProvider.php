<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // __('English text') reads its translation from the database (table `translations`), not from lang/ar.json
        $this->app->extend('translation.loader', fn ($files) => new \App\Support\DatabaseTranslationLoader($files));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL/MariaDB (e.g. bundled with XAMPP) limit index keys to 1000 bytes,
        // which a utf8mb4 VARCHAR(255) unique index exceeds. 191 chars is the safe default.
        Schema::defaultStringLength(191);

        // Whenever a user is authenticated (web request, Livewire update, console, test) every route()
        // call gets that user's role prefix: route('tasks.index') -> /manager/tasks.
        Event::listen(Authenticated::class, fn (Authenticated $e) => URL::defaults(['panel' => $e->user->url_prefix]));

        // Account / department administration is reserved for the super admin.
        Gate::define('manage-users', fn (User $user) => $user->isSuperAdmin());

        // Privilege: open and edit the Translations catalogue (General Configurations > Translations). Held by the super admin.
        Gate::define('manage-translations', fn (User $user) => $user->isSuperAdmin());
    }
}
