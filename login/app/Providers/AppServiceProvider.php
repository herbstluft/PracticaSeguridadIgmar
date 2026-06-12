<?php

namespace App\Providers;

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
        if (app()->environment('local')) {
            \Illuminate\Support\Facades\DB::listen(function ($query) {
                \Illuminate\Support\Facades\Log::channel('development')->info("SQL: {$query->sql} | Time: {$query->time}ms", [
                    'bindings' => $query->bindings
                ]);
            });
        }
    }
}
