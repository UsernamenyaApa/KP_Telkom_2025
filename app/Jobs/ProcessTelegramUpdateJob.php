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
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use App\Jobs\SendTelegramNotificationJob;

/**
 * Class ProcessTelegramUpdateJob
 * Handles incoming Telegram updates and processes user interactions.
 */
class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var Update The Telegram update object */
    protected $update;

    /**
     * Create a new job instance.
     *
     * @param Update $update The Telegram update to process
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
     *
     * @param Update $update The Telegram update object
     * @return void
     */
    private function handleMessage(Update $update): void
    {
        $chatType = $update->getChat()->getType();
        if ($chatType !== 'private') {
            Log::info("Ignoring message from non-private chat type: {$chatType}");
            return;
        }

        $chatId = $update->getChat()->getId();
        $text = $update->getMessage()->getText();
        $user = $update->getMessage()->getFrom();

        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $user);
            return;
        }

        $state = Cache::get($chatId);
        if ($state && ($state['step'] ?? 'idle') !== 'idle') {
            $this->continueConversation($chatId, $text, $state);
        } else {
            $this->sendUnrecognizedMessage($chatId);
        }
    }

    /**
     * Handle bot commands.
     *
     * @param int $chatId The chat identifier
     * @param string $text The command text
     * @param object $user The user object
     * @return void
     */
    private function handleCommand(int $chatId, string $text, object $user): void
    {
        $command = strtolower(trim($text));

        switch ($command) {
            case '/start':
                $this->handleStartCommand($chatId, $user);
                break;
            case '/help':
                $this->handleHelpCommand($chatId);
                break;
            case '/new':
                $this->handleNewCommand($chatId, $user);
                break;
            case '/cancel':
                $this->handleCancelCommand($chatId);
                break;
            case '/register':
                $this->startRegistration($chatId);
                break;
            default:
                $this->sendUnrecognizedCommand($chatId, $command);
                break;
        }
    }

    /**
     * Handle /start command to display guide.
     *
     * @param int $chatId The chat identifier
     * @param object $user The user object
     * @return void
     */
    private function handleStartCommand(int $chatId, object $user): void
    {
        $guideMessage = "🤖 Halo " . $user->getFirstName() . "! Selamat datang di Bot Laporan Fallout.\n\n"
                      . "📋 *Panduan Bot Laporan Fallout*\n\n"
                      . "🔹 /start - Tampilkan panduan ini\n"
                      . "🔹 /help - Tampilkan panduan terperinci\n"
                      . "🔹 /new - Mulai bot dan masukkan kata sandi untuk login\n"
                      . "🔹 /cancel - Batalkan proses yang sedang berjalan\n"
                      . "🔹 /register - Daftarkan akun dengan NIK Anda\n\n"
                      . "📊 *Fitur Utama:*\n"
                      . "- Laporan Fallout: Buat laporan berdasarkan jenis order via menu\n"
                      . "- Pelurusan Data: Fitur ini masih dalam pengembangan\n\n"
                      . "🛠️ *Cara Penggunaan:*\n"
                      . "1. Ketik /new dan masukkan kata sandi yang diberikan.\n"
                      . "2. Pilih 'Laporan Fallout' dari menu setelah login.\n"
                      . "3. Pilih tipe order (AO/FO), lalu ikuti 8 langkah pengisian data.\n"
                      . "4. Gunakan /cancel jika ingin menghentikan proses.\n\n"
                      . "❗ *Catatan:*\n"
                      . "- Hanya gunakan perintah yang terdaftar di atas.\n"
                      . "- Pastikan koneksi stabil saat mengirim laporan.\n"
                      . "- Hubungi admin jika lupa kata sandi atau ada masalah.";

        SendTelegramNotificationJob::dispatch($chatId, $guideMessage, null);
    }

    /**
     * Handle /help command with detailed guide.
     *
     * @param int $chatId The chat identifier
     * @return void
     */
    private function handleHelpCommand(int $chatId): void
    {
        $helpMessage = "📋 *Panduan Bot Laporan Fallout*\n\n"
                     . "🔹 /start - Tampilkan panduan ini\n"
                     . "🔹 /help - Tampilkan panduan terperinci\n"
                     . "🔹 /new - Mulai bot dan masukkan kata sandi untuk login\n"
                     . "🔹 /cancel - Batalkan proses yang sedang berjalan\n"
                     . "🔹 /register - Daftarkan akun dengan NIK Anda\n\n"
                     . "📊 *Fitur Utama:*\n"
                     . "- Laporan Fallout: Buat laporan berdasarkan jenis order via menu\n"
                     . "- Pelurusan Data: Fitur ini masih dalam pengembangan\n\n"
                     . "🛠️ *Cara Penggunaan:*\n"
                     . "1. Ketik /new dan masukkan kata sandi yang diberikan.\n"
                     . "2. Pilih 'Laporan Fallout' dari menu setelah login.\n"
                     . "3. Pilih tipe order (AO/FO), lalu ikuti 8 langkah pengisian data.\n"
                     . "4. Gunakan /cancel jika ingin menghentikan proses.\n\n"
                     . "❗ *Catatan:*\n"
                     . "- Hanya gunakan perintah yang terdaftar di atas.\n"
                     . "- Pastikan koneksi stabil saat mengirim laporan.\n"
                     . "- Hubungi admin jika lupa kata sandi atau ada masalah.";

        SendTelegramNotificationJob::dispatch($chatId, $helpMessage, null);
    }

    /**
     * Handle /new command with password prompt.
     *
     * @param int $chatId The chat identifier
     * @param object $user The user object
     * @return void
     */
    private function handleNewCommand(int $chatId, object $user): void
    {
        $state = Cache::get($chatId);

        if ($state && isset($state['process']) && in_array($state['process'], ['fallout', 'register', 'auth'])) {
            SendTelegramNotificationJob::dispatch($chatId, "⚠️ Proses sedang berjalan. Gunakan /cancel untuk membatalkan.");
            return;
        }

        $welcomeMessage = "🤖 Halo " . $user->getFirstName() . "! Selamat datang di Bot Laporan Fallout.\n\n"
                        . "🔐 Silakan masukkan kata sandi untuk mengakses sistem:";

        $state = [
            'process' => 'auth',
            'step' => 'awaiting_password',
            'authenticated' => false,
            'user_info' => [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
            ],
        ];

        Cache::put($chatId, $state, now()->addMinutes(10));
        SendTelegramNotificationJob::dispatch($chatId, $welcomeMessage, null);
    }

    /**
     * Handle /cancel command.
     *
     * @param int $chatId The chat identifier
     * @return void
     */
    private function handleCancelCommand(int $chatId): void
    {
        Cache::forget($chatId);
        SendTelegramNotificationJob::dispatch($chatId, "❌ Proses dibatalkan. Gunakan /new untuk memulai lagi.");
    }

    /**
     * Send message for unrecognized commands.
     *
     * @param int $chatId The chat identifier
     * @param string $command The unrecognized command
     * @return void
     */
    private function sendUnrecognizedCommand(int $chatId, string $command): void
    {
        $message = "❌ Perintah `{$command}` tidak dikenali.\n\n"
                 . "Gunakan perintah yang tersedia:\n"
                 . "🔹 /start - Tampilkan panduan\n"
                 . "🔹 /help - Lihat panduan\n"
                 . "🔹 /new - Mulai bot\n"
                 . "🔹 /cancel - Batalkan proses\n"
                 . "🔹 /register - Daftar akun";

        SendTelegramNotificationJob::dispatch($chatId, $message, null);
    }

    /**
     * Send message for unrecognized text.
     *
     * @param int $chatId The chat identifier
     * @return void
     */
    private function sendUnrecognizedMessage(int $chatId): void
    {
        $message = "❓ Pesan tidak dikenali.\n\n"
                 . "Gunakan /start untuk panduan atau /new untuk memulai.";

        SendTelegramNotificationJob::dispatch($chatId, $message, null);
    }

    /**
     * Handle button clicks from inline keyboards.
     *
     * @param Update $update The Telegram update object
     * @return void
     */
    private function handleCallbackQuery(Update $update): void
    {
        $callbackQuery = $update->getCallbackQuery();
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $data = $callbackQuery->getData();

        try {
            $telegram = new Api(config('telegram.bots.mybot.token'));
            $telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
        } catch (\Exception $e) {
            Log::error("Failed to answer callback query: " . $e->getMessage());
        }

        $state = Cache::get($chatId);
        if (!$state || !($state['authenticated'] ?? false)) {
            SendTelegramNotificationJob::dispatch($chatId, "❌ Sesi berakhir. Gunakan /new untuk login.");
            return;
        }

        if (str_starts_with($data, 'start_fallout_')) {
            $orderTypeName = substr($data, strlen('start_fallout_'));
            $this->startFalloutReport($chatId, $callbackQuery->getFrom(), $orderTypeName);
            return;
        }

        switch ($data) {
            case 'show_fallout_menu':
                $this->showFalloutMenu($chatId);
                break;
            case 'start_pelurusan':
                SendTelegramNotificationJob::dispatch($chatId, "⚠️ Fitur 'Pelurusan Data' dalam pengembangan. Pilih menu lain.");
                $this->showMainMenu($chatId);
                break;
            case 'back_to_main_menu':
                $this->showMainMenu($chatId, "↩️ Kembali ke menu utama. Pilih opsi:");
                break;
        }
    }

    /**
     * Continue the conversation based on the current step.
     *
     * @param int $chatId The chat identifier
     * @param string $text The user input
     * @param array &$state The conversation state
     * @return void
     */
    private function continueConversation(int $chatId, string $text, array &$state): void
    {
        if (($state['process'] ?? null) === 'auth' && $state['step'] === 'awaiting_password') {
            $this->handlePassword($chatId, $text, $state);
        } elseif (($state['process'] ?? null) === 'fallout') {
            $currentStep = $state['step'];
            $state['report_data'][$currentStep] = $text;
            $this->advanceStep($chatId, $state);
        } elseif (($state['process'] ?? null) === 'register') {
            $this->handleRegistrationSteps($chatId, $text, $state);
        } else {
            $this->sendUnrecognizedMessage($chatId);
        }
    }

    /**
     * Handle password authentication.
     *
     * @param int $chatId The chat identifier
     * @param string $text The user input
     * @param array &$state The conversation state
     * @return void
     */
    private function handlePassword(int $chatId, string $text, array &$state): void
    {
        $password = trim($text);
        $correctPassword = config('telegram.system_password');

        if (strtolower($password) === strtolower($correctPassword)) {
            $state['authenticated'] = true;
            $state['step'] = 'main_menu';
            Cache::put($chatId, $state, now()->addMinutes(60));
            $firstName = $state['user_info']['first_name'] ?? 'User';
            $this->showMainMenu($chatId, "✅ Login berhasil! Halo {$firstName}, pilih menu:");
        } else {
            $attempts = Cache::get($chatId . '_attempts', 0) + 1;
            if ($attempts >= 3) {
                SendTelegramNotificationJob::dispatch($chatId, "❌ Terlalu banyak percobaan. Sesi dibatalkan. Gunakan /new.");
                Cache::forget($chatId);
                Cache::forget($chatId . '_attempts');
            } else {
                Cache::put($chatId . '_attempts', $attempts, now()->addMinutes(10));
                $remaining = 3 - $attempts;
                SendTelegramNotificationJob::dispatch($chatId, "❌ Kata sandi salah. Sisa percobaan: {$remaining}. Coba lagi atau /cancel.");
            }
        }
    }

    /**
     * Start the registration process.
     *
     * @param int $chatId The chat identifier
     * @return void
     */
    private function startRegistration(int $chatId): void
    {
        $state = Cache::get($chatId);
        if ($state && !$state['authenticated'] ?? true) {
            SendTelegramNotificationJob::dispatch($chatId, "⚠️ Login terlebih dahulu dengan /new.");
            return;
        }

        $state = [
            'process' => 'register',
            'step' => 'awaiting_nik',
        ];
        Cache::put($chatId, $state, now()->addMinutes(5));
        SendTelegramNotificationJob::dispatch($chatId, "📝 Masukkan NIK Anda untuk registrasi:");
    }

    /**
     * Handle registration steps.
     *
     * @param int $chatId The chat identifier
     * @param string $text The user input
     * @param array &$state The conversation state
     * @return void
     */
    private function handleRegistrationSteps(int $chatId, string $text, array &$state): void
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
                $message = "✅ Registrasi berhasil!\n"
                         . "Email: " . ($userRecord->email ?? 'Tidak ada') . "\n"
                         . "Password: " . $userRecord->nik . "\n\n"
                         . "⚠️ Ubah password setelah login untuk keamanan.";
                SendTelegramNotificationJob::dispatch($chatId, $message, null);
                Cache::forget($chatId);
            } else {
                SendTelegramNotificationJob::dispatch($chatId, "❌ NIK tidak ditemukan. Coba lagi atau hubungi admin.");
            }
        }
    }

    /**
     * Start the fallout report process.
     *
     * @param int $chatId The chat identifier
     * @param object $user The user object
     * @param string $orderTypeName The selected order type
     * @return void
     */
    private function startFalloutReport(int $chatId, object $user, string $orderTypeName): void
    {
        $state = Cache::get($chatId);
        if ($state && !$state['authenticated'] ?? true) {
            SendTelegramNotificationJob::dispatch($chatId, "⚠️ Login terlebih dahulu dengan /new.");
            return;
        }

        $orderType = OrderType::where('name', $orderTypeName)->first();
        if (!$orderType) {
            SendTelegramNotificationJob::dispatch($chatId, "❌ Tipe order '$orderTypeName' tidak valid.");
            $this->showFalloutMenu($chatId);
            return;
        }

        $state = [
            'process' => 'fallout',
            'step' => 'incident_ticket',
            'order_type_name' => $orderTypeName,
            'user' => [
                'id' => $user->getId(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'username' => $user->getUsername(),
            ],
            'report_data' => ['tipe_order_id' => $orderType->id],
        ];
        Cache::put($chatId, $state, now()->addMinutes(30));

        SendTelegramNotificationJob::dispatch($chatId, "✅ Anda telah memilih tipe laporan: *{$orderTypeName}*.", null);
        SendTelegramNotificationJob::dispatch($chatId, $this->askQuestionForStep($chatId, $state), null);
    }

    /**
     * Advance to the next step in the conversation.
     *
     * @param int $chatId The chat identifier
     * @param array &$state The conversation state
     * @return void
     */
    private function advanceStep(int $chatId, array &$state): void
    {
        $steps = [
            'incident_ticket', 'incident_fallout_description', 'order_id', 'nomer_layanan',
            'sn_ont', 'datek_odp', 'port_odp', 'keterangan',
        ];
        $currentStepIndex = array_search($state['step'], $steps);

        if ($currentStepIndex === false) {
            Log::error("Invalid step '{$state['step']}' for chat {$chatId}. Resetting state.");
            $this->resetStateAndShowMenu($chatId, "❌ Kesalahan alur. Mulai lagi.");
            return;
        }

        $nextStepIndex = $currentStepIndex + 1;
        if ($nextStepIndex < count($steps)) {
            $state['step'] = $steps[$nextStepIndex];
            Cache::put($chatId, $state, now()->addMinutes(30));
            SendTelegramNotificationJob::dispatch($chatId, $this->askQuestionForStep($chatId, $state), null);
        } else {
            $this->generateAndSendReport($chatId, $state);
        }
    }

    /**
     * Ask the user the appropriate question for the current step.
     *
     * @param int $chatId The chat identifier
     * @param array $state The conversation state
     * @return string The question for the current step
     */
    private function askQuestionForStep(int $chatId, array $state): string
    {
        $stepMessages = [
            'incident_ticket' => "1/8: Masukkan Nomor Tiket Insiden:",
            'incident_fallout_description' => "2/8: Masukkan Keterangan Insiden Fallout:",
            'order_id' => "3/8: Masukkan Order ID:",
            'nomer_layanan' => "4/8: Masukkan Nomor Layanan:",
            'sn_ont' => "5/8: Masukkan SN ONT:",
            'datek_odp' => "6/8: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):",
            'port_odp' => "7/8: Masukkan Port ODP (contoh: 3) (HARUS ANGKA BOS!!! KLO GA ANGKA EROR!!!!!!!!!!!):",
            'keterangan' => "8/8: Masukkan Keterangan Laporan:",
        ];
        return $stepMessages[$state['step']] ?? '';
    }

    /**
     * Generate and send the final report.
     *
     * @param int $chatId The chat identifier
     * @param array $state The conversation state
     * @return void
     */
    private function generateAndSendReport(int $chatId, array $state): void
    {
        ProcessTelegramReport::dispatch($chatId, $state, $state['report_data']['tipe_order_id']);
        SendTelegramNotificationJob::dispatch($chatId, "✅ Laporan sedang diproses. Tunggu konfirmasi.");
        Cache::forget($chatId);
    }

    /**
     * Display the main menu.
     *
     * @param int $chatId The chat identifier
     * @param string $messageText The message text
     * @return void
     */
    private function showMainMenu(int $chatId, string $messageText = "Silakan pilih menu:"): void
    {
        $keyboard = [
            'inline_keyboard' => [
                [['text' => '📊 Laporan Fallout', 'callback_data' => 'show_fallout_menu']],
                [['text' => '✏️ Pelurusan Data', 'callback_data' => 'start_pelurusan']],
            ],
        ];
        SendTelegramNotificationJob::dispatch($chatId, $messageText, $keyboard);
        Cache::put($chatId, ['step' => 'main_menu', 'authenticated' => true], now()->addMinutes(30));
    }

    /**
     * Display the fallout report type sub-menu.
     *
     * @param int $chatId The chat identifier
     * @return void
     */
    private function showFalloutMenu(int $chatId): void
    {
        $orderTypes = Cache::remember('order_types_all', now()->addMinutes(60), fn() => OrderType::all());
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

        $replyMarkup = ['inline_keyboard' => $keyboard];
        SendTelegramNotificationJob::dispatch($chatId, "Pilih jenis Laporan Fallout:", $replyMarkup);
    }

    /**
     * Reset state and show main menu.
     *
     * @param int $chatId The chat identifier
     * @param string|null $messageText The message text
     * @return void
     */
    private function resetStateAndShowMenu(int $chatId, ?string $messageText = null): void
    {
        Cache::forget($chatId);
        if ($messageText) {
            $this->showMainMenu($chatId, $messageText);
        }
    }
}