<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\OrderType;

class ProcessStartFalloutReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chat_id;
    protected $user;
    protected $orderTypeName;

    /**
     * Create a new job instance.
     *
     * @param int $chat_id
     * @param object $user
     * @param string $orderTypeName
     */
    public function __construct(int $chat_id, object $user, string $orderTypeName)
    {
        $this->chat_id = $chat_id;
        $this->user = $user;
        $this->orderTypeName = $orderTypeName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info("ProcessStartFalloutReportJob: Handling for chat_id: {$this->chat_id}, orderTypeName: {$this->orderTypeName}");

        $orderType = OrderType::where('name', $this->orderTypeName)->first();

        if (!$orderType) {
            Log::error("ProcessStartFalloutReportJob: Invalid order type '{$this->orderTypeName}' for chat {$this->chat_id}. OrderType not found in DB.");
            // Send error message back to user via a new job
            SendTelegramNotificationJob::dispatch($this->chat_id, "Terjadi kesalahan: Tipe order tidak valid. Silakan coba lagi.");
            return;
        }

        $state = [
            'process' => 'fallout',
            'step' => 'order_id',
            'report_data' => [
                'tipe_order_id' => $orderType->id // Store the order type ID
            ],
            'user' => [ // Store user info in the state
                'id' => $this->user->getId(),
                'first_name' => $this->user->getFirstName(),
                'last_name' => $this->user->getLastName(),
                'username' => $this->user->getUsername(),
            ],
        ];

        Cache::put($this->chat_id, $state, now()->addMinutes(30));
        Log::info("ProcessStartFalloutReportJob: State cached for chat_id {$this->chat_id} with tipe_order_id: {$state['report_data']['tipe_order_id']}");

        // Dispatch job to ask the first question
        // This assumes askQuestionForStep is now a separate job or can be called from here
        // For simplicity, we'll re-implement the logic here or call a helper method that dispatches the question.
        // In a real scenario, you might have a dedicated job for conversation steps.
        switch ($state['step']) {
            case 'order_id':
                SendTelegramNotificationJob::dispatch($this->chat_id, "1/6: Masukkan Order ID:", null);
                break;
            // ... other steps would go here, dispatching SendTelegramNotificationJob
        }
    }
}
