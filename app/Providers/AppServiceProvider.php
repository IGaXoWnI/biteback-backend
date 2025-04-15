<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Gate::define('admin', function ($user) {
            return $user->role === 'Admin';
        });
        Gate::define('business', function ($user) {
            return $user->role === 'Business';
        });
        Gate::define('consumer', function ($user) {
            return $user->role === 'Consumer';
        });
    }
}
