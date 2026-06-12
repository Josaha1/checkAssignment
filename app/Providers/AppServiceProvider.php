<?php

namespace App\Providers;

use App\Contracts\DriveStorage;
use App\Services\GoogleDriveService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DriveStorage::class, GoogleDriveService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
