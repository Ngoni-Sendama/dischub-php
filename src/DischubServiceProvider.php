<?php

namespace Ngoni\DischubPhp;

use Illuminate\Support\ServiceProvider;
use Ngoni\DischubPhp\NonRedirect\NonRedirectIntegration;
use Ngoni\DischubPhp\Redirect\RedirectIntegration;
use Ngoni\DischubPhp\Support\DischubClient;
use Ngoni\DischubPhp\Support\DischubConfig;
use Ngoni\DischubPhp\Support\PaymentValidator;

class DischubServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dischub.php', 'dischub');

        $this->app->singleton(DischubConfig::class, function ($app) {
            return new DischubConfig($app['config']->get('dischub', []));
        });

        $this->app->singleton(PaymentValidator::class, function () {
            return new PaymentValidator();
        });

        $this->app->singleton(DischubClient::class, function ($app) {
            return new DischubClient($app->make(DischubConfig::class));
        });

        $this->app->singleton(RedirectIntegration::class, function ($app) {
            return new RedirectIntegration(
                $app->make(DischubClient::class),
                $app->make(PaymentValidator::class),
                $app->make(DischubConfig::class)
            );
        });

        $this->app->singleton(NonRedirectIntegration::class, function ($app) {
            return new NonRedirectIntegration(
                $app->make(DischubClient::class),
                $app->make(PaymentValidator::class),
                $app->make(DischubConfig::class)
            );
        });

        $this->app->singleton(DischubService::class, function ($app) {
            return new DischubService(
                $app->make(RedirectIntegration::class),
                $app->make(NonRedirectIntegration::class)
            );
        });

        $this->app->alias(DischubService::class, 'dischub');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/dischub.php' => config_path('dischub.php'),
        ], 'config');
    }
}
