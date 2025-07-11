<?php

namespace App\Jobs;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\OrderType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $update;

    /**
     * Create a new job instance.
     *
     * @param Update $update
     */
    public function __construct(Update $update)
    {
        $this->update = $update;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('ProcessTelegramUpdateJob: Handling update.');

        if ($this->update->isType('callback_query')) {
            $this->handleCallbackQuery($this->update);
            return;
        }

        if ($this->update->has('message') && $this->update->getMessage()->has('text')) {
            $this->handleMessage($this->update);
            return;
        }

        Log::warning('ProcessTelegramUpdateJob: Update does not contain a message with text or a callback query.');
    }

    /**
     * Handle incoming messages from users.
     */
    private function handleMessage(Update $update)
    {
        $chatType = $update->getChat()->getType();
        if ($chatType !== 'private') {
            // Ignore messages from groups or channels
            Log::info("ProcessTelegramUpdateJob: Ignoring message from non-private chat type: {$chatType}");
            return;
        }

        $chat_id = $update->getChat()->getId();
        $text = $update->getMessage()->getText();
        $user = $update->getMessage()->getFrom();

        if ($text === '/register') {
            $this->startRegistration($chat_id);
            return;
        }

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
    private function handleCallbackQuery(Update $update)
    {
        $callbackQuery = $update->getCallbackQuery();
        $chat_id = $callbackQuery->getMessage()->getChat()->getId();
        $user = $callbackQuery->getFrom();
        $data = $callbackQuery->getData();

        // Answer callback query immediately to prevent timeout
        try {
            $telegram = new Api(config('telegram.bots.mybot.token'));
            $telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
        } catch (\Exception $e) {
            Log::error("ProcessTelegramUpdateJob: Failed to answer callback query: " . $e->getMessage());
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
                SendTelegramNotificationJob::dispatch($chat_id, "Fitur 'Pelurusan Data' sedang dalam pengembangan. Silakan pilih menu lain.", null);
                $this->showMainMenu($chat_id);
                break;
            case 'back_to_main_menu':
                $this->showMainMenu($chat_id, "Anda kembali ke menu utama. Silakan pilih lagi:");
                break;
        }
    }

    private function startRegistration(int $chat_id)
    {
        $state = [
            'process' => 'register',
            'step' => 'awaiting_nik',
        ];
        Cache::put($chat_id, $state, now()->addMinutes(5));
        SendTelegramNotificationJob::dispatch($chat_id, "Silakan masukkan NIK Anda untuk melanjutkan registrasi.", null);
    }

    /**
     * Guides the user through the conversation steps (handles text input).
     */
    private function continueConversation(int $chat_id, string $text, array &$state)
    {
        if (($state['process'] ?? null) === 'register') {
            $this->handleRegistrationSteps($chat_id, $text, $state);
        } elseif (($state['step'] ?? null) === 'awaiting_fallout_password') {
            $this->handleFalloutPassword($chat_id, $text, $state);
        } else {
            $currentStep = $state['step'];
            $state['report_data'][$currentStep] = $text;

            // Ensure tipe_order_id is carried over
            if (isset($state['report_data']['tipe_order_id'])) {
                $state['report_data']['tipe_order_id'] = $state['report_data']['tipe_order_id'];
            }

            $this->advanceStep($chat_id, $state);
        }
    }

    private function handleRegistrationSteps(int $chat_id, string $text, array &$state)
    {
        $user = $this->update->getMessage()->getFrom();

        if ($state['step'] === 'awaiting_nik') {
            $nik = trim($text);
            $userRecord = User::where('nik', $nik)->first();

            if ($userRecord) {
                $userRecord->update([
                    'telegram_user_id' => $user->getId(),
                    'telegram_username' => $user->getUsername(),
                ]);

                $message = "Registrasi berhasil! Berikut adalah detail akun Anda:\n"
                    . "Email: " . $userRecord->email . "\n"
                    . "Password: " . $userRecord->nik . "\n\n"
                    . "Demi keamanan, harap segera ubah password Anda setelah login.";

                SendTelegramNotificationJob::dispatch($chat_id, $message, null);
                Cache::forget($chat_id); // Clear state after successful registration
            } else {
                SendTelegramNotificationJob::dispatch($chat_id, "NIK tidak ditemukan. Silakan coba lagi atau hubungi admin.", null);
                // We don't forget the cache here so they can try again
            }
        }
    }

    private function handleFalloutPassword(int $chat_id, string $text, array &$state)
    {
        $password = trim($text);
        $correctPassword = config('telegram.field_report_password');

        if ($password === $correctPassword) {
            // Correct password, proceed to the actual report.
            $this->initiateFalloutReport($chat_id, $state);
        } else {
            // Incorrect password, reset and show main menu.
            SendTelegramNotificationJob::dispatch($chat_id, "Kata sandi salah. Silakan coba lagi dari menu utama.", null);
            $this->resetStateAndShowMenu($chat_id);
        }
    }

    private function initiateFalloutReport(int $chat_id, array &$state)
    {
        $orderTypeName = $state['order_type_name'];
        $orderType = OrderType::where('name', $orderTypeName)->first();

        if (!$orderType) {
            Log::error("ProcessTelegramUpdateJob: Invalid order type '{$orderTypeName}' after password auth.");
            SendTelegramNotificationJob::dispatch($chat_id, "Terjadi kesalahan: Tipe order tidak valid. Silakan coba lagi.", null);
            $this->resetStateAndShowMenu($chat_id);
            return;
        }

        // Update state to start the actual report
        $state['step'] = 'incident_ticket';
        $state['report_data'] = [
            'tipe_order_id' => $orderType->id
        ];

        Cache::put($chat_id, $state, now()->addMinutes(30));
        $this->askQuestionForStep($chat_id, $state);
    }

    /**
     * Advances the conversation to the next step with robust state management.
     */
    private function advanceStep(int $chat_id, array &$state)
    {
        $steps = [
            'incident_ticket', 'incident_fallout_description', 'order_id', 'nomer_layanan', 'sn_ont',
            'datek_odp', 'port_odp', 'keterangan'
        ];
        $currentStepIndex = array_search($state['step'], $steps);

        if ($currentStepIndex === false) {
            Log::error("ProcessTelegramUpdateJob: Invalid step '{$state['step']}' found for chat {$chat_id}. Resetting state.");
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
    private function askQuestionForStep(int $chat_id, array $state)
    {
        switch ($state['step']) {
            case 'incident_ticket':
                SendTelegramNotificationJob::dispatch($chat_id, "1/8: Masukkan Nomor Tiket Insiden:", null);
                break;
            case 'incident_fallout_description':
                SendTelegramNotificationJob::dispatch($chat_id, "2/8: Masukkan Keterangan Insiden Fallout:", null);
                break;
            case 'order_id':
                SendTelegramNotificationJob::dispatch($chat_id, "3/8: Masukkan Order ID:", null);
                break;
            case 'nomer_layanan':
                SendTelegramNotificationJob::dispatch($chat_id, "4/8: Masukkan Nomor Layanan:", null);
                break;
            case 'sn_ont':
                SendTelegramNotificationJob::dispatch($chat_id, "5/8: Masukkan SN ONT:", null);
                break;
            case 'datek_odp':
                SendTelegramNotificationJob::dispatch($chat_id, "6/8: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):");
                break;
            case 'port_odp':
                SendTelegramNotificationJob::dispatch($chat_id, "7/8: Masukkan Port ODP (contoh: 3):");
                break;
            case 'keterangan':
                SendTelegramNotificationJob::dispatch($chat_id, "8/8: Masukkan Keterangan Laporan:");
                break;
        }
    }

    /**
     * Starts the process for a new fallout report.
     */
    private function startFalloutReport(int $chat_id, object $user, string $orderTypeName)
    {
        $state = [
            'process' => 'fallout',
            'step' => 'awaiting_fallout_password',
            'order_type_name' => $orderTypeName, // Save for later
            'user' => [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
            ],
        ];

        Cache::put($chat_id, $state, now()->addMinutes(5));
        SendTelegramNotificationJob::dispatch($chat_id, "Untuk melanjutkan, silakan masukkan kata sandi laporan lapangan:", null);
    }

    /**
     * Generates the final report, saves it, and sends it to all channels.
     */
    private function generateAndSendReport(int $chat_id, array $state)
    {
        ProcessTelegramReport::dispatch($chat_id, $state, $state['report_data']['tipe_order_id']);
        SendTelegramNotificationJob::dispatch($chat_id, "✅ Laporan berhasil dibuat dan sedang diproses. Anda akan menerima rekap laporan sebentar lagi.", null);
    }

    /**
     * Displays the main menu with two primary choices.
     */
    private function showMainMenu(int $chat_id, string $messageText = "Silakan pilih menu:")
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📊 Laporan Fallout', 'callback_data' => 'show_fallout_menu']],
                [['text' => '✏️ Pelurusan Data', 'callback_data' => 'start_pelurusan']]
            ]
        ];
        SendTelegramNotificationJob::dispatch($chat_id, $messageText, $keyboard);
    }

    /**
     * Displays the sub-menu for Fallout report types.
     */
    private function showFalloutMenu(int $chat_id)
    {
        // Cache the order types for 60 minutes to reduce DB queries.
        $orderTypes = Cache::remember('order_types_all', now()->addMinutes(60), function () {
            return OrderType::all();
        });

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

        SendTelegramNotificationJob::dispatch($chat_id, "Silakan pilih jenis Laporan Fallout:", $reply_markup);
    }

    /**
     * Resets the user's state and shows the main menu.
     */
    private function resetStateAndShowMenu(int $chat_id, ?string $messageText = null)
    {
        Cache::forget($chat_id);
        if ($messageText) {
            $this->showMainMenu($chat_id, $messageText);
        }
    }
}