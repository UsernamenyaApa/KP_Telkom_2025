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

    protected $chatId;
    protected $state;
    protected $tipeOrderId;

    /**
     * Create a new job instance.
     *
     * @param int $chatId
     * @param array $state
     * @param int $tipeOrderId
     */
    public function __construct($chatId, $state, $tipeOrderId)
    {
        $this->chatId = $chatId;
        $this->state = $state;
        $this->tipeOrderId = $tipeOrderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::info('ProcessTelegramReport: Job handle method started.');
        $chatId = $this->chatId;
        $state = $this->state;
        Log::info("ProcessTelegramReport: State received by job for chat_id {$chatId}: " . json_encode($state));
        $reportData = $state['report_data'];
        $user = $state['user'];
        $createdBy = $user['username'] ? "@" . $user['username'] : $user['first_name'];

        // Prepare data for database
        $telegramId = $user['id'];
        $telegramUsername = $user['username'] ?? null;
        Log::info("Processing user with telegram_user_id: {$telegramId} and telegram_username: {$telegramUsername}");

        $reporterUser = User::where('telegram_username', $telegramUsername)->first()
            ?: User::where('telegram_user_id', $telegramId)->first();

        if ($reporterUser) {
            if (!$reporterUser->telegram_user_id && $telegramId) {
                $reporterUser->telegram_user_id = $telegramId;
                $reporterUser->save();
            }
            if (!$reporterUser->telegram_username && $telegramUsername) {
                $reporterUser->telegram_username = $telegramUsername;
                $reporterUser->save();
            }
        } else {
            $reporterUser = User::create([
                'telegram_user_id' => $telegramId,
                'telegram_username' => $telegramUsername,
                'name' => $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
                'email' => null,
                'nik' => null,
                'password' => bcrypt(Str::random(10)),
            ]);
        }

        $dbData = [
            'tipe_order_id' => $this->tipeOrderId,
            'order_id' => $reportData['order_id'] ?? null,
            'reporter_user_id' => $reporterUser->id,
            'nomer_layanan' => $reportData['nomer_layanan'] ?? null,
            'sn_ont' => $reportData['sn_ont'] ?? null,
            'datek_odp' => $reportData['datek_odp'] ?? null,
            'port_odp' => $reportData['port_odp'] ?? null,
            'incident_ticket' => $reportData['incident_ticket'] ?? null,
            'incident_fallout_description' => $reportData['incident_fallout_description'] ?? null,
            'keterangan' => $reportData['keterangan'] ?? null,
        ];

        // Generate id_harian and set status
        $today = Carbon::today();
        $dbData['id_harian'] = FalloutReport::whereDate('created_at', $today)->count() + 1;
        $openStatus = FalloutStatus::where('name', 'Open')->first();
        $dbData['fallout_status_id'] = $openStatus ? $openStatus->id : null;

        Log::info("Attempting to save with dbData: " . json_encode($dbData));

        // Save to database
        try {
            DB::transaction(function () use ($dbData, $today, &$falloutReport) {
                $falloutReport = FalloutReport::create($dbData);
                DB::table('daily_counters')->updateOrInsert(
                    ['report_date' => $today->toDateString()],
                    ['last_number' => DB::raw('last_number + 1')]
                );
            });
        } catch (\Exception $e) {
            Log::error("DATABASE SAVE FAILED for chat {$chatId}. Error: " . $e->getMessage() . " --- Attempted Data: " . json_encode($dbData));
            SendTelegramNotificationJob::dispatch($chatId, "❌ Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi atau hubungi admin.");
            return;
        }

        // Format the report message
        $orderType = OrderType::find($dbData['tipe_order_id']);
        $orderTypeName = $orderType ? $orderType->name : 'N/A';

        $escapeForMarkdown = function (string $text) {
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

        // Send confirmation and full report to the user
        SendTelegramNotificationJob::dispatch($chatId, "✅ Laporan berhasil dibuat dan dikirim!", null);
        SendTelegramNotificationJob::dispatch($chatId, $reportText, null);

        // Send to channel and group
        $destinations = [
            env('TELEGRAM_CHANNEL_ID'),
            env('TELEGRAM_GROUP_ID')
        ];

        foreach ($destinations as $toChatId) {
            if ($toChatId) {
                try {
                    SendTelegramNotificationJob::dispatch($toChatId, $reportText, null);
                    Log::info("Successfully sent report to {$toChatId}");
                } catch (\Exception $e) {
                    Log::error("Failed to send report to {$toChatId}: " . $e->getMessage());
                }
            }
        }
    }
}