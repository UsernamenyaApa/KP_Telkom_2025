<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramUpdateJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    /**
     * Handles incoming Telegram updates.
     */
    public function handle(Request $request)
    {
        $update = Telegram::getWebhookUpdate();
        Log::info('Webhook received and dispatching job.', ['update_id' => $update->updateId]);

        // Dispatch the job to process the update asynchronously
        ProcessTelegramUpdateJob::dispatch($update);

        // Return an immediate response to Telegram to prevent timeouts
        return response('OK', 200);
    }
}