<?php

namespace App\Http\Controllers;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Carbon\Carbon;

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
    private function startFalloutReport($chat_id, $user, $orderType)
    {
        $state = [
            'process' => 'fallout',
            'step' => 'order_id',
            'report_data' => [
                'tipe_order' => $orderType // Pre-fill the selected order type
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
        $this->askQuestionForStep($chat_id, $state);
    }

    /**
     * Generates the final report, saves it, and sends it to all channels.
     */
    private function generateAndSendReport($chat_id, $state)
    {
        $reportData = $state['report_data'];
        $user = $state['user'];
        $createdBy = $user['username'] ? "@" . $user['username'] : $user['first_name'];

        // 1. Prepare data for database matching the database schema
        $dbData = [
            'tipe_order'    => $reportData['tipe_order'] ?? null,
            'order_id'      => $reportData['order_id'] ?? null,
            'reporter_user_id' => $user['id'], // Store Telegram user ID
            'nomer_layanan' => $reportData['nomer_layanan'] ?? null,
            'sn_ont'        => $reportData['sn_ont'] ?? null,
            'datek_odp'     => $reportData['datek_odp'] ?? null,
            'port_odp'      => $reportData['port_odp'] ?? null,
            'keterangan'    => $reportData['keterangan'] ?? null,
        ];

        // Generate id_harian and fallout_code
        $today = Carbon::today();
        $id_harian = FalloutReport::whereDate('created_at', $today)->count() + 1;
        $fallout_code = 'FA' . $today->format('Ymd') . str_pad($id_harian, 2, '0', STR_PAD_LEFT);

        // Get 'Open' status ID
        $openStatus = FalloutStatus::where('name', 'Open')->first();
        $status_fallout_id = $openStatus ? $openStatus->id : null;

        $dbData['id_harian'] = $id_harian;
        $dbData['fallout_code'] = $fallout_code;
        $dbData['fallout_status_id'] = $status_fallout_id;

        // 2. Save to database
        try {
            DB::transaction(function () use ($dbData) {
                FalloutReport::create($dbData);
            });
        } catch (\Exception $e) {
            // Enhanced logging to help debug database issues.
            Log::error("DATABASE SAVE FAILED for chat {$chat_id}. Error: " . $e->getMessage() . " --- Attempted Data: " . json_encode($dbData));
            $this->sendMessage($chat_id, "❌ Terjadi kesalahan fatal saat menyimpan laporan ke database. Laporan tidak tersimpan. Silakan hubungi admin.");
            $this->resetStateAndShowMenu($chat_id);
            return;
        }

        // 3. Format the report message
        $reportText = "📊 *Laporan Fallout Baru* 📊\n\n"
                    . "*Tipe Order:* `" . ($dbData['tipe_order'] ?? '-') . "`\n"
                    . "*OrderID:* `" . ($dbData['order_id'] ?? '-') . "`\n"
                    . "*Nomor Layanan:* `" . ($dbData['nomer_layanan'] ?? '-') . "`\n"
                    . "*SN ONT:* `" . ($dbData['sn_ont'] ?? '-') . "`\n"
                    . "*Datek ODP:* `" . ($dbData['datek_odp'] ?? '-') . "`\n"
                    . "*Port ODP:* `" . ($dbData['port_odp'] ?? '-') . "`\n\n"
                    . "*Keterangan:*\n" . ($dbData['keterangan'] ?? '-') . "\n\n"
                    . "----------------------------------------\n"
                    . "*Created By:* " . $createdBy . "\n"
                    . "*Create Order:* " . now()->format('Y-m-d H:i:s');

        // 4. Send confirmation to the user who created it
        $this->sendMessage($chat_id, "✅ Laporan berhasil dibuat dan dikirim!");
        $sentMessage = $this->sendMessage($chat_id, $reportText);

        // 5. Forward the message to the channel and group
        if ($sentMessage) {
            $this->forwardMessageToDestinations($chat_id, $sentMessage->message_id);
        }

        // 6. Clean up state
        $this->resetStateAndShowMenu($chat_id);
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
        $keyboard = [
            'inline_keyboard' => [
                [['text' => 'AO', 'callback_data' => 'start_fallout_AO'], ['text' => 'MO', 'callback_data' => 'start_fallout_MO']],
                [['text' => 'DO', 'callback_data' => 'start_fallout_DO'], ['text' => 'RO', 'callback_data' => 'start_fallout_RO']],
                [['text' => 'SO', 'callback_data' => 'start_fallout_SO']],
                [['text' => '« Kembali ke Menu Utama', 'callback_data' => 'back_to_main_menu']]
            ]
        ];
        $this->sendMessage($chat_id, "Silakan pilih jenis Laporan Fallout:", $keyboard);
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
