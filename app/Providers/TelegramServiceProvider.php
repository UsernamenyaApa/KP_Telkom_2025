<?php

namespace App\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Api;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Api::class, function ($app) {
            $config = $app['config']['telegram'];
            $guzzleClient = new GuzzleHttpClient(new Client(['verify' => false]));
            $telegram = new Api($config['bots']['mybot']['token'], false, $guzzleClient);

            return $telegram;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
