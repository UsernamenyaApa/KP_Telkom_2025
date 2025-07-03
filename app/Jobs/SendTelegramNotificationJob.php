<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class SendTelegramNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chatId;
    protected $message;
    protected $replyMarkup;

    /**
     * Create a new job instance.
     *
     * @param string $chatId
     * @param string $message
     * @param array|null $replyMarkup
     */
    public function __construct(string $chatId, string $message, ?array $replyMarkup = null)
    {
        $this->chatId = $chatId;
        $this->message = $message;
        $this->replyMarkup = $replyMarkup;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $params = [
                'chat_id' => $this->chatId,
                'text' => $this->message,
                'parse_mode' => 'Markdown',
            ];

            if ($this->replyMarkup) {
                $params['reply_markup'] = json_encode($this->replyMarkup);
            }

            Telegram::sendMessage($params);
            Log::info("Telegram notification sent to {$this->chatId}");
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send Telegram notification to {$this->chatId}: " . $e->getMessage());
        }
    }
}
