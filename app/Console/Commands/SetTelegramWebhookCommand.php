<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Telegram\Bot\HttpClients\GuzzleHttpClient;

class SetTelegramWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the Telegram bot webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $webhookUrl = "{$url}/telegram/webhook";

        try {
            $guzzleClient = new GuzzleHttpClient(new Client(['verify' => false]));
            $telegram = new Api(config('telegram.bots.mybot.token'), false, $guzzleClient);
            $response = $telegram->setWebhook(['url' => $webhookUrl]);
            $this->info("Webhook set to: {$webhookUrl}");
            if ($response) {
                $this->info('Webhook set successfully.');
            } else {
                $this->error('Failed to set webhook.');
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
