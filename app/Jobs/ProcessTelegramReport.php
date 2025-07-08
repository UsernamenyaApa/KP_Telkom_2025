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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\SendTelegramNotificationJob;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ProcessTelegramReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chat_id;
    protected $state;
    protected $tipe_order_id;

    /**
     * Create a new job instance.
     *
     * @param int $chat_id
     * @param array $state
     * @param int $tipe_order_id
     */
    public function __construct($chat_id, $state, $tipe_order_id)
    {
        $this->chat_id = $chat_id;
        $this->state = $state;
        $this->tipe_order_id = $tipe_order_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        \Illuminate\Support\Facades\Log::info('ProcessTelegramReport: Job handle method started.');
        $chat_id = $this->chat_id;
        $state = $this->state;
        Log::info("ProcessTelegramReport: State received by job for chat_id {$chat_id}: " . json_encode($state));
        $reportData = $state['report_data'];
        $user = $state['user'];
        $createdBy = $user['username'] ? "@" . $user['username'] : $user['first_name'];

        // 1. Prepare data for database matching the database schema
        // Find user by telegram_id, then by email, and finally create if not found.
        // This handles cases where a user was created before the telegram_user_id field was added.
        $telegramId = $user['id'];
        $telegramUsername = $user['username'] ?? null;
        Log::info("ProcessTelegramReport: Processing user with telegram_user_id: {$telegramId} and telegram_username: {$telegramUsername}");

        $reporterUser = null;

        // 1. Try to find user by telegram_username first
        if ($telegramUsername) {
            $reporterUser = User::where('telegram_username', $telegramUsername)->first();
            if ($reporterUser && !$reporterUser->telegram_user_id) {
                $reporterUser->telegram_user_id = $telegramId;
                $reporterUser->save();
            }
        }

        // 2. If not found by username, try to find by telegram_user_id (for existing users without username or before username was primary)
        if (!$reporterUser) {
            $reporterUser = User::where('telegram_user_id', $telegramId)->first();
            if ($reporterUser && !$reporterUser->telegram_username && $telegramUsername) {
                $reporterUser->telegram_username = $telegramUsername;
                $reporterUser->save();
            }
        }

        // 3. If user still not found, create a new one
        if (!$reporterUser) {
            $reporterUser = User::create([
                'telegram_user_id' => $telegramId,
                'telegram_username' => $telegramUsername,
                'name'             => $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
                'email'            => null, // No @telegram.com email
                'nik'              => null, // NIK is not provided by Telegram
                'password'         => bcrypt(Str::random(10)),
            ]);
        }

        $dbData = [
            'tipe_order_id' => $reportData['tipe_order_id'] ?? null,
            'tipe_order_id' => $this->tipe_order_id,
            'order_id'      => $reportData['order_id'] ?? null,
            'reporter_user_id' => $reporterUser->id, // Use the internal user ID
            'nomer_layanan' => $reportData['nomer_layanan'] ?? null,
            'sn_ont'        => $reportData['sn_ont'] ?? null,
            'datek_odp'     => $reportData['datek_odp'] ?? null,
            'port_odp'      => $reportData['port_odp'] ?? null,
            'incident_ticket' => $reportData['incident_ticket'] ?? null,
            'incident_fallout_description' => $reportData['incident_fallout_description'] ?? null,
            'keterangan'    => $reportData['keterangan'] ?? null,
        ];

        

        // Generate id_harian
        $today = Carbon::today();
        $id_harian = FalloutReport::whereDate('created_at', $today)->count() + 1;
        $openStatus = FalloutStatus::where('name', 'Open')->first();
        $status_fallout_id = $openStatus ? $openStatus->id : null;

        
        $dbData['id_harian'] = $id_harian;
        $dbData['incident_ticket'] = $reportData['incident_ticket'] ?? null;
        $dbData['fallout_status_id'] = $status_fallout_id;
        \Illuminate\Support\Facades\Log::info("ProcessTelegramReport: dbData['tipe_order_id'] before save: " . ($dbData['tipe_order_id'] ?? 'NULL'));

        // 2. Save to database
        \Illuminate\Support\Facades\Log::info("ProcessTelegramReport: Attempting to save with dbData: " . json_encode($dbData));
        try {
            DB::transaction(function () use ($dbData) {
                FalloutReport::create($dbData);
            });
        } catch (\Exception $e) {
            // Enhanced logging to help debug database issues.
            Log::error("DATABASE SAVE FAILED for chat {$chat_id}. Error: " . $e->getMessage() . " --- Attempted Data: " . json_encode($dbData));
            $this->sendMessage($chat_id, "❌ Terjadi kesalahan fatal saat menyimpan laporan ke database. Laporan tidak tersimpan. Silakan hubungi admin.");
            return;
        }

        // 3. Format the report message
        $orderType = OrderType::find($dbData['tipe_order_id']);
        $orderTypeName = $orderType ? $orderType->name : 'N/A';

        // Sanitize data for Markdown to prevent formatting issues
        $escapeForMarkdown = function (string $text): string {
            // Escape characters for Telegram's Markdown parse mode.
            return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
        };

        $reportText = "📊 *Laporan Fallout Baru* 📊\n\n"
            . "*Tipe Order:* `" . $escapeForMarkdown($orderTypeName) . "`\n"
            . "*OrderID:* `" . $escapeForMarkdown($dbData['order_id'] ?? '-') . "`\n"
            . "*Nomor Layanan:* `" . $escapeForMarkdown($dbData['nomer_layanan'] ?? '-') . "`\n"
            . "*SN ONT:* `" . $escapeForMarkdown($dbData['sn_ont'] ?? '-') . "`\n"
            . "*Datek ODP:* `" . $escapeForMarkdown($dbData['datek_odp'] ?? '-') . "`\n"
            . "*Port ODP:* `" . $escapeForMarkdown($dbData['port_odp'] ?? '-') . "`\n"
            . "*Incident Ticket:* `" . $escapeForMarkdown($dbData['incident_ticket'] ?? '-') . "`\n"
            . "*Keterangan Insiden Fallout:* `" . $escapeForMarkdown($dbData['incident_fallout_description'] ?? '-') . "`\n\n"
            . "*Keterangan:*\n" . $escapeForMarkdown($dbData['keterangan'] ?? '-') . "\n\n"

            . "----------------------------------------\n"
            . "*Created By:* " . $escapeForMarkdown($createdBy) . "\n"
            . "*Create Order:* " . now()->format('Y-m-d H:i:s');


        // 4. Send confirmation to the user who created it
        $this->sendMessage($chat_id, "✅ Laporan berhasil dibuat dan dikirim!");
        $this->sendMessage($chat_id, $reportText);

        // 5. Send the message to the channel and group
        $destinations = [
            env('TELEGRAM_CHANNEL_ID'),
            env('TELEGRAM_GROUP_ID')
        ];

        foreach ($destinations as $to_chat_id) {
            if ($to_chat_id) {
                try {
                    $this->sendMessage($to_chat_id, $reportText);
                    Log::info("Successfully sent report to {$to_chat_id}");
                } catch (TelegramSDKException $e) {
                    Log::error("Failed to send report to {$to_chat_id}: " . $e->getMessage());
                }
            }
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
        SendTelegramNotificationJob::dispatch($chat_id, $text, $reply_markup);
    }
}