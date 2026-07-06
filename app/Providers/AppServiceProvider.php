<?php

namespace App\Providers;

use App\Http\Middleware\EnsureActiveEmpresa;
use App\Http\Middleware\InitializeSelectedTenant;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        Paginator::useBootstrapFive();

        Livewire::setUpdateRoute(function ($handle, $path) {
            return Route::post($path, $handle)
                ->middleware([
                    'web',
                    'auth',
                    InitializeSelectedTenant::class,
                    EnsureActiveEmpresa::class,
                ])
                ->name('tenant.livewire.update');
        });
    }
}
