<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramReport;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\OrderType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Exceptions\TelegramSDKException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    /**
     * Handles incoming Telegram updates.
     */
    public function handle()
    {
        Log::info('Webhook received!');
        $update = Telegram::getWebhookUpdate();
        Log::info($update);

        if ($update->isType('callback_query')) {
            $this->handleCallbackQuery($update);
            return;
        }

        if ($update->has('message') && $update->getMessage()->has('text')) {
            $this->handleMessage($update);
            return;
        }

        Log::warning('Update does not contain a message with text or a callback query.');
    }

    /**
     * Handle incoming messages from users.
     */
    private function handleMessage($update)
    {
        $chatType = $update->getChat()->getType();
        if ($chatType !== 'private') {
            // Ignore messages from groups or channels
            Log::info("Ignoring message from non-private chat type: {$chatType}");
            return;
        }

        $chat_id = $update->getChat()->getId();
        $text = $update->getMessage()->getText();
        $user = $update->getMessage()->getFrom();

        if ($text === '/start') {
            $this->resetStateAndShowMenu($chat_id, "Halo " . $user->getFirstName() . "! Selamat datang. Silakan pilih menu.");
            return;
        }

        if ($text === '/cancel') {
            $this->resetStateAndShowMenu($chat_id, "Proses dibatalkan. Anda bisa memulai lagi kapan saja.");
            return;
        }

        $state = Cache::get($chat_id);
        if ($state && ($state['step'] ?? 'idle') !== 'idle') {
            $this->continueConversation($chat_id, $text, $state);
        } else {
            $this->showMainMenu($chat_id, "Gunakan perintah /start untuk memulai atau pilih menu di bawah.");
        }
    }

    /**
     * Handle button clicks from inline keyboards.
     */
    private function handleCallbackQuery($update)
    {
        $callbackQuery = $update->getCallbackQuery();
        $chat_id = $callbackQuery->getMessage()->getChat()->getId();
        $user = $callbackQuery->getFrom();
        $data = $callbackQuery->getData();

        try {
            Telegram::answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
        } catch (TelegramSDKException $e) {
            Log::error("Failed to answer callback query: " . $e->getMessage());
        }

        // Handle selection of a specific fallout type (e.g., AO, MO)
        if (str_starts_with($data, 'start_fallout_')) {
            $orderType = substr($data, strlen('start_fallout_'));
            $this->startFalloutReport($chat_id, $user, $orderType);
            return;
        }

        // Handle main menu navigation
        switch ($data) {
            case 'show_fallout_menu':
                $this->showFalloutMenu($chat_id);
                break;
            case 'start_pelurusan':
                $this->sendMessage($chat_id, "Fitur 'Pelurusan Data' sedang dalam pengembangan. Silakan pilih menu lain.");
                $this->showMainMenu($chat_id);
                break;
            case 'back_to_main_menu':
                $this->showMainMenu($chat_id, "Anda kembali ke menu utama. Silakan pilih lagi:");
                break;
        }
    }

    /**
     * Guides the user through the conversation steps (handles text input).
     */
    private function continueConversation($chat_id, $text, &$state)
    {
        $currentStep = $state['step'];
        $state['report_data'][$currentStep] = $text;
        $this->advanceStep($chat_id, $state);
    }

    /**
     * Advances the conversation to the next step with robust state management.
     */
    private function advanceStep($chat_id, &$state)
    {
        $steps = [
            'order_id', 'nomer_layanan', 'sn_ont',
            'datek_odp', 'port_odp', 'keterangan'
        ];
        $currentStepIndex = array_search($state['step'], $steps);

        if ($currentStepIndex === false) {
            Log::error("Invalid step '{$state['step']}' found for chat {$chat_id}. Resetting state.");
            $this->resetStateAndShowMenu($chat_id, "Terjadi kesalahan pada alur, silakan mulai lagi.");
            return;
        }

        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < count($steps)) {
            $state['step'] = $steps[$nextStepIndex];
            // Save state to cache BEFORE asking the next question. This is crucial.
            Cache::put($chat_id, $state, now()->addMinutes(30));
            $this->askQuestionForStep($chat_id, $state);
        } else {
            // Last step is done, now generate the report.
            $this->generateAndSendReport($chat_id, $state);
        }
    }

    /**
     * Asks the user the correct question for the current step.
     */
    private function askQuestionForStep($chat_id, $state)
    {
        switch ($state['step']) {
            case 'order_id':
                $this->sendMessage($chat_id, "1/6: Masukkan Order ID:");
                break;
            case 'nomer_layanan':
                $this->sendMessage($chat_id, "2/6: Masukkan Nomor Layanan:");
                break;
            case 'sn_ont':
                $this->sendMessage($chat_id, "3/6: Masukkan SN ONT:");
                break;
            case 'datek_odp':
                $this->sendMessage($chat_id, "4/6: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):");
                break;
            case 'port_odp':
                $this->sendMessage($chat_id, "5/6: Masukkan Port ODP (contoh: 3):");
                break;
            case 'keterangan':
                $this->sendMessage($chat_id, "6/6: Masukkan Keterangan Laporan:");
                break;
        }
    }

    /**
     * Starts the process for a new fallout report.
     */
    private function startFalloutReport($chat_id, $user, $orderTypeName)
    {
        Log::info("startFalloutReport: Received orderTypeName: {$orderTypeName} for chat_id: {$chat_id}");
        $orderType = OrderType::where('name', $orderTypeName)->first();

        if (!$orderType) {
            Log::error("Invalid order type '{$orderTypeName}' selected by user in chat {$chat_id}. OrderType not found in DB.");
            $this->sendMessage($chat_id, "Terjadi kesalahan: Tipe order tidak valid. Silakan coba lagi.");
            $this->showMainMenu($chat_id);
            return;
        }

        Log::info("startFalloutReport: Found OrderType ID: {$orderType->id} for name: {$orderTypeName}");

        $state = [
            'process' => 'fallout',
            'step' => 'order_id',
            'report_data' => [
                'tipe_order_id' => $orderType->id // Store the order type ID
            ],
            'user' => [ // Store user info in the state
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
            ],
        ];
        // Save state to cache BEFORE asking the first question.
        Cache::put($chat_id, $state, now()->addMinutes(30));
        Log::info("startFalloutReport: State cached for chat_id {$chat_id} with tipe_order_id: {$state['report_data']['tipe_order_id']}");
        $this->askQuestionForStep($chat_id, $state);
    }

    /**
     * Generates the final report, saves it, and sends it to all channels.
     */
    private function generateAndSendReport($chat_id, $state)
    {
        ProcessTelegramReport::dispatch($chat_id, $state);
        $this->resetStateAndShowMenu($chat_id, "✅ Laporan berhasil dibuat dan sedang diproses. Anda akan menerima rekap laporan sebentar lagi.");
    }

    /**
     * Forwards a message to predefined destinations from .env file.
     */
    private function forwardMessageToDestinations($from_chat_id, $message_id)
    {
        $destinations = [
            env('TELEGRAM_CHANNEL_ID'),
            env('TELEGRAM_GROUP_ID')
        ];

        foreach ($destinations as $to_chat_id) {
            if ($to_chat_id) {
                try {
                    Telegram::forwardMessage([
                        'chat_id'      => $to_chat_id,
                        'from_chat_id' => $from_chat_id,
                        'message_id'   => $message_id,
                    ]);
                    Log::info("Successfully forwarded message {$message_id} to {$to_chat_id}");
                } catch (TelegramSDKException $e) {
                    Log::error("Failed to forward message {$message_id} to {$to_chat_id}: " . $e->getMessage());
                }
            }
        }
    }


    /**
     * Displays the main menu with two primary choices.
     */
    private function showMainMenu($chat_id, $messageText = "Silakan pilih menu:")
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📊 Laporan Fallout', 'callback_data' => 'show_fallout_menu']],
                [['text' => '✏️ Pelurusan Data', 'callback_data' => 'start_pelurusan']]
            ]
        ];
        $this->sendMessage($chat_id, $messageText, $keyboard);
    }

    /**
     * Displays the sub-menu for Fallout report types.
     */
    private function showFalloutMenu($chat_id)
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

        $this->sendMessage($chat_id, "Silakan pilih jenis Laporan Fallout:", $reply_markup);
    }

    /**
     * Resets the user's state and shows the main menu.
     */
    private function resetStateAndShowMenu($chat_id, $messageText = null)
    {
        Cache::forget($chat_id);
        if ($messageText) {
            $this->showMainMenu($chat_id, $messageText);
        }
    }

    /**
     * A helper function to send messages.
     */
    private function sendMessage($chat_id, $text, $reply_markup = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];
        if ($reply_markup) {
            $params['reply_markup'] = json_encode($reply_markup);
        }
        try {
            return Telegram::sendMessage($params);
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send message to chat_id {$chat_id}: " . $e->getMessage());
            return null;
        }
    }
}
