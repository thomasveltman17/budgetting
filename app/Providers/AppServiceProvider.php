<?php

namespace App\Providers;

use App\Services\PeriodService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PeriodService::class);
    }

    public function boot(): void
    {
        //
    }
}
