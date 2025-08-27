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
    ) {}

    public function handle(): void
    {
        Log::info('Memulai proses penyimpanan laporan fallout.', ['chat_id' => $this->chatId]);

        try {
            $userInfo = data_get($this->state, 'user_info');
            if (! $userInfo) {
                throw new \Exception('Informasi pengguna tidak ditemukan dalam state.');
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
            $this->notifyUserOnFailure('Terjadi kesalahan teknis saat menyimpan laporan.');
        }
    }

    private function prepareReportDataForStorage(array $reportData, array $userInfo): array
    {
        $data = [
            'tipe_order_id' => $this->tipeOrderId,
            'order_id' => data_get($reportData, 'order_id'),
            'incident_ticket' => data_get($reportData, 'incident_ticket'),
            'incident_fallout_description' => data_get($reportData, 'incident_fallout_description'),
            'keterangan' => data_get($reportData, 'keterangan'),
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
            $dbData['fallout_code'] = 'FO'.$today->format('Ymd').str_pad($idHarian, 3, '0', STR_PAD_LEFT);
            $dbData['fallout_status_id'] = $openStatus->id;

            return FalloutReport::create($dbData);
        });
    }

    private function notifyRelevantParties(FalloutReport $report, array $userInfo): void
    {
        $reporterName = data_get($userInfo, 'name', data_get($userInfo, 'username', 'N/A'));
        $createdBy = data_get($userInfo, 'username') ? "@{$userInfo['username']}" : $reporterName;

        // Sanitize for MarkdownV2
        $esc = fn (?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? ''
        );

        $lines = [
            '📊 *Laporan Fallout Baru* 📊',
            '',
            '*Antrian:* `'.$esc($report->id_harian).'`',
            '*Kode Fallout:* `'.$esc($report->incident_ticket).'`',
            '*Tipe Order:* `'.$esc($report->orderType->name).'`',
            '*OrderID:* `'.$esc($report->order_id).'`',

            '',
            '*Keterangan Insiden:*',
            '```text',
            $esc($report->incident_fallout_description ?? '-'),
            '```',
            '*Keterangan Tambahan:*',
            '```text',
            $esc($report->keterangan ?? '-'),
            '```',
            '',
            '*Dibuat Oleh:* '.$esc($createdBy),
            '*Waktu Dibuat:* `'.$esc($report->created_at->format('Y-m-d H:i:s')).'`',
        ];

        $reportText = implode("\n", $lines);
        $groupChat = \App\Models\TelegramGroup::first();
        $reporterChatId = data_get($userInfo, 'id');

        $destinations = array_unique(array_filter([
            env('TELEGRAM_CHANNEL_ID'),
            $groupChat ? $groupChat->chat_id : null,
            $reporterChatId,
        ]));

        Log::info('Sending Fallout Report Notification to Destinations:', ['destinations' => $destinations]);

        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
    }

    private function notifyUserOnFailure(string $message): void
    {
        SendTelegramNotificationJob::dispatch($this->chatId, "❌ Gagal memproses laporan: {$message}");
    }
}
