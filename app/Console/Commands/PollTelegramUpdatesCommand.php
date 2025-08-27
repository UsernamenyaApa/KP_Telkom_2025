<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelegramUpdateJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class PollTelegramUpdatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll for Telegram updates using long polling';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Telegram update polling...');
        Log::info('Starting Telegram update polling...');

        // Get the offset of the last update
        $updates = Telegram::bot()->getUpdates();
        $offset = 0;
        if (! empty($updates)) {
            $offset = $updates[count($updates) - 1]->updateId + 1;
        }

        while (true) {
            try {
                $updates = Telegram::bot()->getUpdates(['offset' => $offset, 'timeout' => 30]);

                foreach ($updates as $update) {
                    $offset = $update->updateId + 1;
                    Log::info('Update received and dispatching job.', ['update_id' => $update->updateId]);
                    ProcessTelegramUpdateJob::dispatch($update);
                }
            } catch (Exception $e) {
                Log::error('Error polling for Telegram updates: '.$e->getMessage());
                $this->error('Error polling for Telegram updates: '.$e->getMessage());
                // Wait for a bit before retrying to avoid spamming logs in case of a persistent error
                sleep(10);
            }
        }

        return 0;
    }
}
