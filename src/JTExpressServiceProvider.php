<?php

namespace GeniusCode\JTExpressEg;

use GeniusCode\JTExpressEg\Builders\OrderRequestBuilder;
use GeniusCode\JTExpressEg\Formatters\AddressFormatter;
use GeniusCode\JTExpressEg\Formatters\OrderItemFormatter;
use GeniusCode\JTExpressEg\Handlers\OrderResponseHandler;
use GeniusCode\JTExpressEg\Http\JTExpressApiClient;
use GeniusCode\JTExpressEg\Validators\OrderDataValidator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class JTExpressServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/jt-express.php', 'jt-express');

        $this->app->singleton(JTExpressApiClient::class, function (Application $app) {
            $config = $app->make('config');
            $baseUrl = $config->get('app.env') === 'production'
                ? 'https://openapi.jtjms-eg.com'
                : 'https://demoopenapi.jtjms-eg.com';
            return new JTExpressApiClient($baseUrl);
        });

        $this->app->singleton(AddressFormatter::class);
        $this->app->singleton(OrderItemFormatter::class);
        $this->app->singleton(OrderDataValidator::class);
        $this->app->singleton(OrderRequestBuilder::class);

        $this->app->singleton(JTExpressService::class, function (Application $app) {
            $config = $app->make('config');
            return new JTExpressService(
                $app->make(JTExpressApiClient::class),
                $app->make(OrderResponseHandler::class),
                $app->make(AddressFormatter::class),
                $app->make(OrderItemFormatter::class),
                $app->make(OrderDataValidator::class),
                $app->make(OrderRequestBuilder::class),
                (string) $config->get('jt-express.apiAccount'),
                (string) $config->get('jt-express.privateKey'),
                (string) $config->get('jt-express.customerCode'),
                (string) $config->get('jt-express.customerPwd')
            );
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