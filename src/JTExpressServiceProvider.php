<?php

namespace GeniusCode\JtExpressEg;

use Illuminate\Support\ServiceProvider;

class JTExpressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jt-express.php', 'jt-express');

        $this->app->singleton(JTExpressService::class, function () {
            return new JTExpressService();
        });

        $this->app->alias(JTExpressService::class, 'jt-express');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/jt-express.php' => config_path('jt-express.php'),
        ], 'jt-express-config');
    }
}