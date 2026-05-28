<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Süper admin tüm permission kontrollerini otomatik geçer
        Gate::before(function ($user, $ability) {
            if ($user->is_super_admin) {
                return true;
            }
        });
    }
}
