<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;

    protected $message;

    protected $replyMarkup;

    protected $parseMode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $chatId, string $message, ?array $replyMarkup = null, string $parseMode = 'Markdown')
    {
        $this->chatId = $chatId;
        $this->message = str_replace('\\', '', $message);
        $this->replyMarkup = $replyMarkup;
        $this->parseMode = $parseMode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $params = [
                'chat_id' => $this->chatId,
                'text' => $this->message,
                'parse_mode' => $this->parseMode,
            ];

            if ($this->replyMarkup) {
                $params['reply_markup'] = json_encode($this->replyMarkup);
            }

            Telegram::sendMessage($params);
            Log::info("Telegram notification sent to {$this->chatId}");
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send Telegram notification to {$this->chatId}: ".$e->getMessage());
        }
    }
}
