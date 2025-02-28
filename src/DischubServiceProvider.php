<?php

namespace Ngoni\DischubPhp;

use Illuminate\Support\ServiceProvider;

class DischubServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DischubService::class, function ($app) {
            return new DischubService();
        });
    }

    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/dischub.php' => config_path('dischub.php'),
        ], 'config');
    }
}
