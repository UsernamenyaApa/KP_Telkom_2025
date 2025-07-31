<?php

namespace App\Jobs;

use App\Models\OrderType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\User as TelegramUser;

class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PROCESS_AUTH = 'auth';

    private const PROCESS_FALLOUT = 'fallout';

    private const PROCESS_PELURUSAN = 'pelurusan';

    private const PROCESS_REGISTER = 'register';

    private const STEP_AWAITING_PASSWORD = 'awaiting_password';

    private const STEP_AWAITING_NIK = 'awaiting_nik';

    private const STEP_MAIN_MENU = 'main_menu';

    private const CACHE_TTL_MINUTES = 60;

    public function __construct(protected Update $update) {}

    public function handle(): void
    {
        if ($this->update->has('message')) {
            $message = $this->update->getMessage();
            $chat = $message->getChat();

            if ($chat->type === 'group' || $chat->type === 'supergroup') {
                if (trim(strtolower($message->text)) === 'tiftang') {
                    $telegramGroup = \App\Models\TelegramGroup::updateOrCreate(
                        ['chat_id' => $chat->id],
                        ['name' => $chat->title]
                    );
                    Log::info("Group chat ID stored: " . $chat->id . " - " . $chat->title);

                    if ($telegramGroup->wasRecentlyCreated) {
                        \App\Jobs\SendTelegramNotificationJob::dispatch($chat->id, "✅ ID Grup Telegram ini telah berhasil didaftarkan dan disimpan.");
                    }
                }
            }
        }

        if ($this->update->isType('callback_query')) {
            $this->handleCallbackQuery($this->update);

            return;
        }

        if ($this->update->has('message') && $this->update->getMessage()->has('text')) {
            $this->handleMessage($this->update);

            return;
        }

        if ($this->update->has('message') && $this->update->getMessage()->has('photo')) {
            $this->handlePhoto($this->update);

            return;
        }
    }

    private function handleCallbackQuery(Update $update): void
    {
        $telegram = new Api(config('telegram.bots.mybot.token'));
        $callbackQuery = $update->getCallbackQuery();

        if (! $callbackQuery) {
            return;
        }

        $chatId = $callbackQuery->getMessage()->getChat()->id;
        $messageId = $callbackQuery->getMessage()->getMessageId();
        $data = $callbackQuery->getData();

        try {
            $telegram->answerCallbackQuery(['callback_query_id' => $callbackQuery->id]);
        } catch (\Exception $e) {
            Log::warning('Gagal menjawab callback query: '.$e->getMessage());
        }

        $state = Cache::get($chatId);
        if (! ($state['authenticated'] ?? false)) {
            SendTelegramNotificationJob::dispatch($chatId, '❌ Sesi Anda telah berakhir. Silakan /start ulang.');

            return;
        }

        if (str_starts_with($data, 'start_fallout_')) {
            $orderTypeName = substr($data, strlen('start_fallout_'));
            $this->editMessage($telegram, $chatId, $messageId, "✅ Tipe laporan dipilih: *{$orderTypeName}*");
            $this->startFalloutReport($chatId, $state, $orderTypeName);

            return;
        }

        if (str_starts_with($data, 'start_pelurusan_')) {
            $orderTypeName = substr($data, strlen('start_pelurusan_'));
            $this->editMessage($telegram, $chatId, $messageId, "✅ Tipe laporan dipilih: *{$orderTypeName}*");
            $this->startPelurusanReport($chatId, $state, $orderTypeName);

            return;
        }

        match ($data) {
            'show_fallout_menu' => $this->showFalloutMenu($telegram, $chatId, $messageId),
            'lapor_pelurusan' => $this->showPelurusanMenu($telegram, $chatId, $messageId),
            'back_to_main_menu' => $this->showMainMenu($telegram, $chatId, '↩️ Kembali ke menu utama. Pilih opsi:', $messageId),
            'image_yes' => $this->handleImageYes($telegram, $chatId, $messageId),
            'image_no' => $this->handleImageNo($telegram, $chatId, $messageId, $state),
            'pelurusan_image_yes' => $this->handleImageYes($telegram, $chatId, $messageId, self::PROCESS_PELURUSAN),
            'pelurusan_image_no' => $this->handleImageNo($telegram, $chatId, $messageId, $state, self::PROCESS_PELURUSAN),
            default => SendTelegramNotificationJob::dispatch($chatId, '⚠️ Aksi tidak valid.').
        };
    }

    private function showFalloutMenu(Api $telegram, int $chatId, ?int $messageId = null): void
    {
        $orderTypes = Cache::remember('fallout_order_types', now()->addMinutes(60), fn() => OrderType::where('name', '!=', 'Ex Gangguan')->get());
        if ($orderTypes->isEmpty()) {
            SendTelegramNotificationJob::dispatch($chatId, '⚠️ Maaf, belum ada tipe order yang tersedia di sistem.');
            $this->showMainMenu($telegram, $chatId, 'Silakan hubungi admin untuk menambahkan tipe order.', $messageId);

            return;
        }

        $keyboardArray = [];
        $row = [];
        foreach ($orderTypes as $type) {
            $row[] = ['text' => $type->name, 'callback_data' => 'start_fallout_'.$type->name];
            if (count($row) == 2) {
                $keyboardArray[] = $row;
                $row = [];
            }
        }
        if (! empty($row)) {
            $keyboardArray[] = $row;
        }

        $keyboardArray[] = [['text' => '« Kembali ke Menu Utama', 'callback_data' => 'back_to_main_menu']];

        $finalKeyboard = ['inline_keyboard' => $keyboardArray];

        $this->editMessage($telegram, $chatId, $messageId, 'Pilih jenis Laporan Fallout:', $finalKeyboard);
    }

    private function showPelurusanMenu(Api $telegram, int $chatId, ?int $messageId = null): void
    {
        $orderTypes = Cache::remember('order_types_all', now()->addMinutes(60), fn () => OrderType::all());

        if ($orderTypes->isEmpty()) {
            SendTelegramNotificationJob::dispatch($chatId, '⚠️ Maaf, belum ada tipe order yang tersedia di sistem.');
            $this->showMainMenu($telegram, $chatId, 'Silakan hubungi admin untuk menambahkan tipe order.', $messageId);

            return;
        }

        $keyboardArray = [];
        $row = [];
        foreach ($orderTypes as $type) {
            $row[] = ['text' => $type->name, 'callback_data' => 'start_pelurusan_'.$type->name];
            if (count($row) == 2) {
                $keyboardArray[] = $row;
                $row = [];
            }
        }
        if (! empty($row)) {
            $keyboardArray[] = $row;
        }

        $keyboardArray[] = [['text' => '« Kembali ke Menu Utama', 'callback_data' => 'back_to_main_menu']];

        $finalKeyboard = ['inline_keyboard' => $keyboardArray];

        $this->editMessage($telegram, $chatId, $messageId, 'Pilih jenis Laporan Pelurusan:', $finalKeyboard);
    }

    private function showMainMenu(Api $telegram, int $chatId, string $messageText = 'Silakan pilih menu:', ?int $messageId = null): void
    {
        $keyboard = ['inline_keyboard' => [
            [['text' => '📊 Laporan Fallout', 'callback_data' => 'show_fallout_menu']],
            [['text' => '✏️ Pelurusan Data', 'callback_data' => 'lapor_pelurusan']],
        ]];

        if ($messageId) {
            $this->editMessage($telegram, $chatId, $messageId, $messageText, $keyboard);
        } else {
            SendTelegramNotificationJob::dispatch($chatId, $messageText, $keyboard);
        }
    }

    private function editMessage(Api $telegram, int $chatId, int $messageId, string $text, ?array $keyboard = null): void
    {
        try {
            $params = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text, 'parse_mode' => 'Markdown'];
            if ($keyboard) {
                $params['reply_markup'] = json_encode($keyboard);
            }
            $telegram->editMessageText($params);
        } catch (TelegramSDKException $e) {
            if (str_contains($e->getMessage(), 'message is not modified')) {
                Log::info('Pesan tidak diubah karena konten sama.', ['chat_id' => $chatId]);
            } else {
                Log::error('Gagal mengedit pesan (TelegramSDKException): '.$e->getMessage(), ['chat_id' => $chatId]);
            }
        } catch (\Exception $e) {
            Log::error('Gagal mengedit pesan (Exception): '.$e->getMessage(), ['chat_id' => $chatId]);
        }
    }

    private function handleMessage(Update $update): void
    {
        $message = $update->getMessage();
        $chat = $message->getChat();
        $user = $message->getFrom();
        $text = $message->text;
        if ($chat->type !== 'private') {
            return;
        }
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chat->id, $text, $user);

            return;
        }
        $state = Cache::get($chat->id, []);
        if (! empty($state['process'])) {
            $this->continueConversation($chat->id, $text, $state);
        } else {
            $this->sendUnrecognizedMessage($chat->id);
        }
    }

    private function handleCommand(int $chatId, string $text, TelegramUser $user): void
    {
        $command = strtolower(trim($text));
        match ($command) {
            '/start' => $this->handleStartCommand($chatId, $user),
            '/new' => $this->handleLoginCommand($chatId, $user),
            '/register' => $this->startRegistration($chatId, $user),
            '/help' => $this->handleHelpCommand($chatId),
            '/cancel' => $this->handleCancelCommand($chatId),
            default => $this->sendUnrecognizedCommand($chatId, $command),
        };
    }

    private function continueConversation(int $chatId, string $text, array &$state): void
    {
        match ($state['process'] ?? null) {
            self::PROCESS_AUTH => $this->handlePassword($chatId, $text, $state),
            self::PROCESS_FALLOUT => $this->handleFalloutSteps($chatId, $text, $state),
            self::PROCESS_PELURUSAN => $this->handlePelurusanSteps($chatId, $text, $state),
            self::PROCESS_REGISTER => $this->handleRegistrationSteps($chatId, $text, $state),
            default => $this->sendUnrecognizedMessage($chatId),
        };
    }

    private function handleStartCommand(int $chatId, TelegramUser $user): void
    {
        $message = "👋 Halo, {$user->firstName}!\n\n"
                 ."Selamat datang di Bot Laporan Fallout. Bot ini akan membantu Anda membuat laporan teknis dengan mudah dan cepat.\n\n"
                 ."Gunakan perintah berikut untuk memulai:\n"
                 ."• `/new` - Untuk login dan memulai sesi baru.\n"
                 .'• `/help` - Untuk melihat semua daftar perintah yang tersedia.';
        SendTelegramNotificationJob::dispatch($chatId, $message);
    }

    private function handleLoginCommand(int $chatId, TelegramUser $user): void
    {
        $state = Cache::get($chatId, []);
        if ($state['authenticated'] ?? false) {
            $this->showMainMenu(new Api(config('telegram.bots.mybot.token')), $chatId, "Halo {$user->firstName}! Anda sudah login. Silakan pilih menu:");

            return;
        }
        if (isset($state['process'])) {
            SendTelegramNotificationJob::dispatch($chatId, '⚠️ Proses lain sedang berjalan. Gunakan /cancel untuk membatalkan.');

            return;
        }
        $userInfo = ['id' => $user->id, 'first_name' => $user->firstName, 'last_name' => $user->lastName ?? null, 'username' => $user->username ?? null];
        $newState = ['process' => self::PROCESS_AUTH, 'step' => self::STEP_AWAITING_PASSWORD, 'authenticated' => false, 'user_info' => $userInfo];
        Cache::put($chatId, $newState, now()->addMinutes(10));
        SendTelegramNotificationJob::dispatch($chatId, '🤖 Silakan masukkan kata sandi Anda untuk mengakses sistem:');
    }

    private function startRegistration(int $chatId, TelegramUser $user): void
    {
        $state = Cache::get($chatId, []);
        if ($state['authenticated'] ?? false) {
            SendTelegramNotificationJob::dispatch($chatId, '⚠️ Anda sudah login. Tidak perlu mendaftar lagi.');

            return;
        }
        $userInfo = ['id' => $user->id, 'first_name' => $user->firstName, 'last_name' => $user->lastName ?? null, 'username' => $user->username ?? null];
        $newState = ['process' => self::PROCESS_REGISTER, 'step' => self::STEP_AWAITING_NIK, 'user_info' => $userInfo, 'authenticated' => false];
        Cache::put($chatId, $newState, now()->addMinutes(5));
        SendTelegramNotificationJob::dispatch($chatId, '📝 Silakan masukkan NIK Anda untuk melanjutkan registrasi:');
    }

    private function handleHelpCommand(int $chatId): void
    {
        $helpMessage = "📋 *Panduan Bot Laporan Fallout*\n\n"
                     ."*Perintah Utama:*\n"
                     ."`/start` - Menampilkan pesan selamat datang.\n"
                     ."`/new` - Memulai sesi baru dan proses login.\n"
                     ."`/register` - Mendaftarkan/menghubungkan akun dengan NIK.\n"
                     ."`/help` - Menampilkan panduan ini.\n"
                     ."`/cancel` - Membatalkan proses yang sedang berjalan.\n\n"
                     ."*Cara Penggunaan:*\n"
                     ."1. Ketik `/new` dan masukkan kata sandi.\n"
                     ."2. Pilih menu 'Laporan Fallout'.\n"
                     ."3. Ikuti langkah-langkah pengisian data.\n"
                     .'4. Gunakan `/cancel` jika ingin berhenti di tengah jalan.';
        SendTelegramNotificationJob::dispatch($chatId, $helpMessage, null, 'Markdown');
    }

    private function handleCancelCommand(int $chatId): void
    {
        Cache::forget($chatId);
        SendTelegramNotificationJob::dispatch($chatId, '❌ Proses dibatalkan. Ketik /start atau /new untuk memulai lagi.');
    }

    private function handlePassword(int $chatId, string $text, array &$state): void
    {
        // For all bot users (Office or Field), authentication is done via a single shared password.
        if (trim($text) !== config('telegram.system_password')) {
            $attemptsKey = $chatId.'_attempts';
            $attempts = Cache::increment($attemptsKey);
            if ($attempts >= 3) {
                SendTelegramNotificationJob::dispatch($chatId, '❌ Terlalu banyak percobaan. Sesi dibatalkan. Gunakan /start.');
                Cache::forget([$chatId, $attemptsKey]);
            } else {
                SendTelegramNotificationJob::dispatch($chatId, '❌ Kata sandi salah. Sisa percobaan: '.(3 - $attempts));
            }

            return; // Exit if password is wrong
        }

        // Password is correct. Now, identify the user for reporting purposes.
        $state['authenticated'] = true;
        $state['process'] = null;
        $state['step'] = self::STEP_MAIN_MENU;

        $userName = $state['user_info']['first_name'] ?? 'User'; // Default to Telegram name

        // Check if the user is a registered Office Staff to link their reports.
        $userRecord = User::where('telegram_user_id', $chatId)->first();
        if ($userRecord) {
            // If found, use their real name and store their actual user_id for logging reports.
            $userName = $userRecord->name;
            $state['user_info']['db_user_id'] = $userRecord->id;
            $state['user_info']['name'] = $userRecord->name; // Store the real name in the state
        }

        Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));
        $this->showMainMenu(new Api(config('telegram.bots.mybot.token')), $chatId, "✅ Login berhasil! Halo {$userName}, silakan pilih menu:");
    }

    private function handleFalloutSteps(int $chatId, string $text, array &$state): void
    {
        $currentStep = $state['step'];
        $trimmedText = trim($text);
        if ($currentStep === 'port_odp' && ! is_numeric($trimmedText)) {
            SendTelegramNotificationJob::dispatch($chatId, '❌ Port ODP harus berupa angka. Silakan masukkan kembali:');

            return;
        }
        $state['report_data'][$currentStep] = $trimmedText;
        $this->advanceFalloutStep($chatId, $state);
    }

    private function handlePelurusanSteps(int $chatId, string $text, array &$state): void
    {
        $currentStep = $state['step'];
        $trimmedText = trim($text);
        if ($currentStep === 'port_odp' && ! is_numeric($trimmedText)) {
            SendTelegramNotificationJob::dispatch($chatId, '❌ Port ODP harus berupa angka. Silakan masukkan kembali:');

            return;
        }
        $state['report_data'][$currentStep] = $trimmedText;
        $this->advancePelurusanStep($chatId, $state);
    }

    private function handleRegistrationSteps(int $chatId, string $text, array &$state): void
    {
        if ($state['step'] === self::STEP_AWAITING_NIK) {
            $nik = trim($text);
            $userRecord = User::where('nik', $nik)->first();

            if ($userRecord) {
                $userInfo = $state['user_info'];
                $userRecord->update([
                    'telegram_user_id' => $userInfo['id'],
                    'telegram_username' => $userInfo['username'],
                ]);

                $message = "✅ Registrasi berhasil! Akun Telegram Anda telah terhubung ke sistem.\n\n"
                         ."Password default Anda untuk login di aplikasi web adalah NIK Anda: `{$nik}`\n\n"
                         .'Demi keamanan, harap segera ganti password Anda setelah berhasil login di web.';

                SendTelegramNotificationJob::dispatch($chatId, $message, null, 'Markdown');
                Cache::forget($chatId);
            } else {
                SendTelegramNotificationJob::dispatch($chatId, '❌ NIK tidak ditemukan. Pastikan NIK Anda benar atau hubungi Super Admin.');
            }
        }
    }

    private function startFalloutReport(int $chatId, array $state, string $orderTypeName): void
    {
        $orderType = OrderType::where('name', $orderTypeName)->first();
        if (! $orderType) {
            SendTelegramNotificationJob::dispatch($chatId, '❌ Tipe order tidak valid.');

            return;
        }
        $state['process'] = self::PROCESS_FALLOUT;
        $state['step'] = 'incident_ticket';
        $state['report_data'] = ['tipe_order_id' => $orderType->id];
        Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));
        SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step']));
    }

    private function startPelurusanReport(int $chatId, array $state, string $orderTypeName): void
    {
        $orderType = OrderType::where('name', $orderTypeName)->first();
        if (! $orderType) {
            SendTelegramNotificationJob::dispatch($chatId, '❌ Tipe order tidak valid.');

            return;
        }
        $state['process'] = self::PROCESS_PELURUSAN;

        if ($orderType->name === 'Ex Gangguan') {
            $state['step'] = 'nomor_incident';
        } else {
            $state['step'] = 'incident_fallout_description';
        }

        $state['report_data'] = ['tipe_order_id' => $orderType->id];
        Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));
        SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step'], 'pelurusan', $orderType->id));
    }

    private function advanceFalloutStep(int $chatId, array &$state): void
    {
        $steps = ['incident_ticket', 'incident_fallout_description', 'order_id', 'nomer_layanan', 'sn_ont', 'datek_odp', 'port_odp', 'keterangan', 'awaiting_image'];
        $currentStepIndex = array_search($state['step'], $steps);
        $nextStepIndex = $currentStepIndex + 1;
        if ($nextStepIndex < count($steps)) {
            $state['step'] = $steps[$nextStepIndex];
            Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));

            if ($state['step'] === 'awaiting_image') {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Ya', 'callback_data' => 'image_yes'],
                            ['text' => 'Tidak', 'callback_data' => 'image_no'],
                        ],
                    ],
                ];
                SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step']), $keyboard);
            } else {
                SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step']));
            }
        } else {
            $this->generateAndSendReport($chatId, $state);
        }
    }

    private function advancePelurusanStep(int $chatId, array &$state): void
    {
        $tipeOrderId = $state['report_data']['tipe_order_id'] ?? null;
        $orderType = $tipeOrderId ? OrderType::find($tipeOrderId) : null;

        if ($orderType && $orderType->name === 'Ex Gangguan') {
            $steps = ['nomor_incident', 'nomer_layanan', 'datek_odp', 'port_odp', 'awaiting_image'];
        } else {
            $steps = ['incident_fallout_description', 'order_id', 'nomer_layanan', 'sn_ont', 'datek_odp', 'port_odp', 'keterangan', 'awaiting_image'];
        }

        $currentStepIndex = array_search($state['step'], $steps);
        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < count($steps)) {
            $state['step'] = $steps[$nextStepIndex];
            Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));

            if ($state['step'] === 'awaiting_image') {
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Ya', 'callback_data' => 'pelurusan_image_yes'],
                            ['text' => 'Tidak', 'callback_data' => 'pelurusan_image_no'],
                        ],
                    ],
                ];
                SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step'], 'pelurusan', $tipeOrderId), $keyboard);
            } else {
                SendTelegramNotificationJob::dispatch($chatId, $this->getQuestionForStep($state['step'], 'pelurusan', $tipeOrderId));
            }
        } else {
            $this->generateAndSendPelurusanReport($chatId, $state);
        }
    }

    private function generateAndSendReport(int $chatId, array $state): void
    {
        ProcessTelegramReport::dispatch($chatId, $state, $state['report_data']['tipe_order_id']);
        Cache::forget($chatId);
        SendTelegramNotificationJob::dispatch($chatId, "✅ Laporan Anda telah diterima dan sedang diproses.");
    }

    private function generateAndSendPelurusanReport(int $chatId, array $state): void
    {
        ProcessTelegramPelurusanReport::dispatch($chatId, $state, $state['report_data']['tipe_order_id']);
        Cache::forget($chatId);
        SendTelegramNotificationJob::dispatch($chatId, "✅ Laporan Anda telah diterima dan sedang diproses.");
    }

    private function getQuestionForStep(string $step, string $process = 'fallout', ?int $tipeOrderId = null): string
    {
        if ($process === 'pelurusan') {
            $orderType = $tipeOrderId ? OrderType::find($tipeOrderId) : null;
            if ($orderType && $orderType->name === 'Ex Gangguan') {
                $questions = [
                    'nomor_incident' => '1/4: Masukkan Nomor Incident:',
                    'nomer_layanan' => '2/4: Masukkan Nomor Layanan:',
                    'datek_odp' => '3/4: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):',
                    'port_odp' => '4/4: Masukkan Port ODP (contoh: 3) (HARUS ANGKA):',
                    'awaiting_image' => 'Apakah Anda ingin menambahkan gambar?',
                ];
            } else {
                $questions = [
                    'incident_fallout_description' => '1/8: Masukkan Keterangan Insiden Pelurusan:',
                    'order_id' => '2/8: Masukkan Order ID:',
                    'nomer_layanan' => '3/8: Masukkan Nomor Layanan:',
                    'sn_ont' => '4/8: Masukkan SN ONT:',
                    'datek_odp' => '5/8: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):',
                    'port_odp' => '6/8: Masukkan Port ODP (contoh: 3) (HARUS ANGKA):',
                    'keterangan' => '7/8: Masukkan Keterangan Tambahan Laporan:',
                    'awaiting_image' => 'Apakah Anda ingin menambahkan gambar?',
                ];
            }
        } else {
            $questions = [
                'incident_ticket' => '1/8: Masukkan Nomor Tiket Insiden:',
                'incident_fallout_description' => '2/8: Masukkan Keterangan Insiden Fallout:',
                'order_id' => '3/8: Masukkan Order ID:',
                'nomer_layanan' => '4/8: Masukkan Nomor Layanan:',
                'sn_ont' => '5/8: Masukkan SN ONT:',
                'datek_odp' => '6/8: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):',
                'port_odp' => '7/8: Masukkan Port ODP (contoh: 3) (HARUS ANGKA):',
                'keterangan' => '8/8: Masukkan Keterangan Tambahan Laporan:',
                'awaiting_image' => 'Apakah Anda ingin menambahkan gambar?',
            ];
        }

        return $questions[$step] ?? 'Langkah tidak diketahui.';
    }

    private function sendUnrecognizedCommand(int $chatId, string $command): void
    {
        SendTelegramNotificationJob::dispatch($chatId, "❌ Perintah `{$command}` tidak dikenali.\nGunakan /help untuk melihat daftar perintah.");
    }

    private function sendUnrecognizedMessage(int $chatId): void
    {
        SendTelegramNotificationJob::dispatch($chatId, '❓ Pesan tidak dikenali. Gunakan /help untuk panduan atau /start untuk memulai.');
    }

    private function handlePhoto(Update $update): void
    {
        $message = $update->getMessage();
        $chatId = $message->getChat()->id;
        $state = Cache::get($chatId);

        if (isset($state['process']) && ($state['process'] === self::PROCESS_FALLOUT || $state['process'] === self::PROCESS_PELURUSAN) && $state['step'] === 'awaiting_image') {
            try {
                $telegram = new Api(config('telegram.bots.mybot.token'));
                $photoCollection = $message->photo;

                // Ambil foto dengan resolusi tertinggi (elemen terakhir dari koleksi)
                $photo = $photoCollection->last();

                if (!$photo || !$photo->file_id) {
                    Log::error('Gagal mendapatkan data foto yang valid dari update.', ['update' => $update->toArray()]);
                    SendTelegramNotificationJob::dispatch((string)$chatId, '❌ Gagal memproses file gambar. Format tidak didukung atau file kosong.');
                    return;
                }

                $file = $telegram->getFile(['file_id' => $photo->file_id]);
                $fileContents = file_get_contents('https://api.telegram.org/file/bot' . config('telegram.bots.mybot.token') . "/{$file->filePath}");

                $directory = ($state['process'] === self::PROCESS_FALLOUT) ? 'fallout-images/' : 'pelurusan-images/';
                $fileName = $directory . uniqid() . '_' . time() . '.jpg';
                Storage::disk('public')->put($fileName, $fileContents);

                $state['report_data']['image'] = $fileName;
                Cache::put($chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));

                if ($state['process'] === self::PROCESS_FALLOUT) {
                    $this->generateAndSendReport($chatId, $state);
                } elseif ($state['process'] === self::PROCESS_PELURUSAN) {
                    $this->generateAndSendPelurusanReport($chatId, $state);
                }
            } catch (\Exception $e) {
                Log::error("Gagal memproses foto: " . $e->getMessage() . ' on line ' . $e->getLine());
                SendTelegramNotificationJob::dispatch((string)$chatId, '❌ Terjadi kesalahan teknis saat memproses gambar Anda.');
            }
        } else {
            SendTelegramNotificationJob::dispatch($chatId, 'Tidak sedang dalam proses unggah gambar.');
        }
    }

    private function handleImageYes(Api $telegram, int $chatId, int $messageId, string $processType = self::PROCESS_FALLOUT): void
    {
        $this->editMessage($telegram, $chatId, $messageId, 'Silakan kirim gambar Anda.');
    }

    private function handleImageNo(Api $telegram, int $chatId, int $messageId, array $state, string $processType = self::PROCESS_FALLOUT): void
    {
        $this->editMessage($telegram, $chatId, $messageId, 'Baik, laporan akan diproses tanpa gambar.');
        if ($processType === self::PROCESS_FALLOUT) {
            $this->generateAndSendReport($chatId, $state);
        } elseif ($processType === self::PROCESS_PELURUSAN) {
            $this->generateAndSendPelurusanReport($chatId, $state);
        }
    }
}