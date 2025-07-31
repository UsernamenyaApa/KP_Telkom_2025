<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $chatId;
    protected string $message;
    protected ?array $replyMarkup;
    protected string $parseMode;

    public function __construct(string $chatId, string $message, ?array $replyMarkup = null, string $parseMode = 'Markdown')
    {
        $this->chatId = $chatId;
        $this->message = $message;
        $this->replyMarkup = $replyMarkup;
        $this->parseMode = $parseMode;
    }

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

        } catch (\Exception $e) {
            Log::error("Failed to send Telegram notification to {$this->chatId}: " . $e->getMessage());
        }
    }
}