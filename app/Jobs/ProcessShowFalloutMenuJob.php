<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\OrderType;

class ProcessShowFalloutMenuJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chat_id;

    /**
     * Create a new job instance.
     *
     * @param int $chat_id
     */
    public function __construct(int $chat_id)
    {
        $this->chat_id = $chat_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $orderTypes = OrderType::all();
        $keyboard = [];
        $row = [];

        foreach ($orderTypes as $type) {
            $row[] = ['text' => $type->name, 'callback_data' => 'start_fallout_' . $type->name];
            if (count($row) === 2) {
                $keyboard[] = $row;
                $row = [];
            }
        }

        if (!empty($row)) {
            $keyboard[] = $row;
        }

        $keyboard[] = [['text' => '« Kembali ke Menu Utama', 'callback_data' => 'back_to_main_menu']];

        $reply_markup = [
            'inline_keyboard' => $keyboard
        ];

        SendTelegramNotificationJob::dispatch($this->chat_id, "Silakan pilih jenis Laporan Fallout:", $reply_markup);
    }
}
