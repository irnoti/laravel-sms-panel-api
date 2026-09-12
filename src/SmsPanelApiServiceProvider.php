<?php

namespace Irnoti\SmsPanelApi;

use Illuminate\Support\ServiceProvider;

class SmsPanelApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms-panel-api.php', 'sms-panel-api');

        $this->app->singleton(IrnotiClient::class, function ($app) {
            $config = $app['config']->get('sms-panel-api');

            return new IrnotiClient(
                baseUrl: $config['base_url'],
                apiKey: $config['key'],
                timeout: (int) $config['timeout'],
                retryTimes: (int) ($config['retry']['times'] ?? 1),
                retrySleep: (int) ($config['retry']['sleep'] ?? 200),
            );
        });

        $this->app->alias(IrnotiClient::class, 'irnoti-api');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sms-panel-api.php' => config_path('sms-panel-api.php'),
            ], 'sms-panel-api-config');
        }
    }
}
