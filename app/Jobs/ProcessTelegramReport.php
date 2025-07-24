<?php

namespace App\Jobs;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
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

/**
 * Memproses dan menyimpan data laporan fallout dari state percakapan Telegram.
 */
class ProcessTelegramReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $chatId,
        protected array $state,
        protected int $tipeOrderId
    ) {
    }

    public function handle(): void
    {
        Log::info('Memulai proses penyimpanan laporan fallout.', ['chat_id' => $this->chatId]);

        try {
            $userInfo = data_get($this->state, 'user_info');
            if (!$userInfo) {
                throw new \Exception("Informasi pengguna tidak ditemukan dalam state.");
            }

            $reportData = data_get($this->state, 'report_data', []);
            
            $dbData = $this->prepareReportDataForStorage($reportData, $userInfo);
            $falloutReport = $this->saveReportToDatabase($dbData);
            
            $this->notifyRelevantParties($falloutReport, $userInfo);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan fallout untuk chat {$this->chatId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyUserOnFailure("Terjadi kesalahan teknis saat menyimpan laporan.");
        }
    }
    
    private function prepareReportDataForStorage(array $reportData, array $userInfo): array
    {
        $portOdp = data_get($reportData, 'port_odp');
        
        $data = [
            'tipe_order_id' => $this->tipeOrderId,
            'order_id' => data_get($reportData, 'order_id'),
            'nomer_layanan' => data_get($reportData, 'nomer_layanan'),
            'sn_ont' => data_get($reportData, 'sn_ont'),
            'datek_odp' => data_get($reportData, 'datek_odp'),
            'port_odp' => is_numeric($portOdp) ? (int) $portOdp : null,
            'incident_ticket' => data_get($reportData, 'incident_ticket'),
            'incident_fallout_description' => data_get($reportData, 'incident_fallout_description'),
            'keterangan' => data_get($reportData, 'keterangan'),
            'image' => data_get($reportData, 'image'),
        ];

        // If it's an Office Staff, link to their user account.
        if ($dbUserId = data_get($userInfo, 'db_user_id')) {
            $data['reporter_user_id'] = $dbUserId;
        } else {
            // If it's a Field Staff, store their Telegram info directly.
            $data['reporter_telegram_id'] = data_get($userInfo, 'id');
            $data['reporter_telegram_username'] = data_get($userInfo, 'username');
        }

        return $data;
    }

    private function saveReportToDatabase(array $dbData): FalloutReport
    {
        return DB::transaction(function () use ($dbData) {
            $today = Carbon::today();
            $idHarian = (FalloutReport::whereDate('created_at', $today)->max('id_harian') ?? 0) + 1;
            
            $openStatus = FalloutStatus::where('name', 'Open')->firstOrFail();

            $dbData['id_harian'] = $idHarian;
            $dbData['fallout_code'] = 'FA' . $today->format('Ymd') . str_pad($idHarian, 3, '0', STR_PAD_LEFT);
            $dbData['fallout_status_id'] = $openStatus->id;

            return FalloutReport::create($dbData);
        });
    }

    private function notifyRelevantParties(FalloutReport $report, array $userInfo): void
    {
        // Use the real name if available (Office Staff), otherwise use the Telegram username.
        $reporterName = data_get($userInfo, 'name', data_get($userInfo, 'username', 'N/A'));
        $createdBy = data_get($userInfo, 'username') ? "@{$userInfo['username']}" : $reporterName;
        
        $esc = fn(?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? '-'
        );

        // Sanitize input for the code block to prevent parsing errors.
        // Within `pre` blocks, all `` and `` ` `` characters must be escaped.
        $description = $report->incident_fallout_description ?? '-';        $keterangan = $report->keterangan ?? '-';        $sanitizedDescription = str_replace(['\\', '`'], ['/', '\\`'], $description);        $sanitizedKeterangan = str_replace(['\\', '`'], ['/', '\\`'], $keterangan);

        $lines = [
            "📊 *Laporan Fallout Baru*",
            "",
            "*ID Laporan:* `" . $esc($report->id) . "`",
            "*Kode Fallout:* `" . $esc($report->fallout_code) . "`",
            "*Tipe Order:* `" . $esc($report->orderType->name) . "`",
            "*OrderID:* `" . $esc($report->order_id) . "`",
            "*Nomor Layanan:* `" . $esc($report->nomer_layanan) . "`",
            "*SN ONT:* `" . $esc($report->sn_ont) . "`",
            "*Datek ODP:* `" . $esc($report->datek_odp) . "`",
            "*Port ODP:* `" . $esc($report->port_odp) . "`",
            "*Tiket Insiden:* `" . $esc($report->incident_ticket) . "`",
            "",
            "*Keterangan Insiden:*",
            "```",
            $sanitizedDescription,
            "```",
            "*Keterangan Tambahan:*",
            "```",
            $sanitizedKeterangan,
            "```",
            "----------------------------------------",
            "*Dibuat Oleh:* " . $esc($createdBy),
            "*Waktu Dibuat:* " . $esc($report->created_at->format('Y-m-d H:i:s')),
        ];
        
        $reportText = implode("\n", $lines);
        $groupChat = \App\Models\TelegramGroup::first();
        $reporterChatId = data_get($userInfo, 'id'); // Get reporter's chat ID from userInfo
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), $groupChat ? $groupChat->chat_id : null, $reporterChatId]);

        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
    }
    
    

    
    
    private function notifyUserOnFailure(string $message): void
    {
        SendTelegramNotificationJob::dispatch($this->chatId, "❌ Gagal memproses laporan: {$message}");
    }

    
}