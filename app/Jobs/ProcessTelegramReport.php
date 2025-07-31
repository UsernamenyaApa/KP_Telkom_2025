<?php

namespace App\Jobs;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\TelegramGroup;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info("Memulai proses penyimpanan laporan fallout.", ['chat_id' => $this->chatId]);

        try {
            $userInfo = data_get($this->state, 'user_info');
            if (!$userInfo) throw new \Exception('Informasi pengguna tidak ditemukan dalam state.');

            $reportData = data_get($this->state, 'report_data', []);
            $dbData = $this->prepareReportData($reportData, $userInfo);
            $falloutReport = $this->saveReportToDatabase($dbData);
            
            $this->notifyRelevantParties($falloutReport, $userInfo);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan fallout untuk chat {$this->chatId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            SendTelegramNotificationJob::dispatch((string)$this->chatId, '❌ Terjadi kesalahan teknis saat menyimpan laporan Anda.');
        }
    }

    private function prepareReportData(array $reportData, array $userInfo): array
    {
        unset($reportData['telegram_user_id']);

        $data = [
            'tipe_order_id' => $this->tipeOrderId,
            'order_id' => data_get($reportData, 'order_id'),
            'nomer_layanan' => data_get($reportData, 'nomer_layanan'),
            'sn_ont' => data_get($reportData, 'sn_ont'),
            'datek_odp' => data_get($reportData, 'datek_odp'),
            'port_odp' => data_get($reportData, 'port_odp'),
            'incident_ticket' => data_get($reportData, 'incident_ticket'),
            'incident_fallout_description' => data_get($reportData, 'incident_fallout_description'),
            'keterangan' => data_get($reportData, 'keterangan'),
            'image' => data_get($reportData, 'image'),
        ];

        if ($dbUserId = data_get($userInfo, 'db_user_id')) {
            $data['reporter_user_id'] = $dbUserId;
        } else {
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
            $dbData['fallout_code'] = 'FA' . $today->format('Ymd') . str_pad((string)$idHarian, 3, '0', STR_PAD_LEFT);
            $dbData['fallout_status_id'] = $openStatus->id;

            Log::info('Data for FalloutReport creation:', $dbData);

            return FalloutReport::create($dbData);
        });
    }

    private function notifyRelevantParties(FalloutReport $report, array $userInfo): void
    {
        $createdBy = data_get($userInfo, 'username') ? "@{$userInfo['username']}" : (data_get($userInfo, 'name', 'N/A'));

        $summaryLines = [
            '📊 *Laporan Fallout Baru Diterima*',
            '*ID Harian:* `' . $this->escape($report->id_harian) . '`',
            '*Kode Fallout:* `' . $this->escape($report->fallout_code) . '`',
            '*Tipe Order:* `' . $this->escape($report->orderType->name) . '`',
            '*Dibuat Oleh:* ' . $this->escape($createdBy),
        ];
        $summaryText = implode("\n", array_filter($summaryLines));

        $detailLines = [
            '📋 *Detail Laporan Fallout*',
            '*ID Harian:* `' . $this->escape($report->id_harian) . '`',
            '*Kode Fallout:* `' . $this->escape($report->fallout_code) . '`',
            '*Tipe Order:* `' . $this->escape($report->orderType->name) . '`',
            '*OrderID:* `' . $this->escape($report->order_id) . '`',
            '*Nomor Layanan:* `' . $this->escape($report->nomer_layanan) . '`',
            '*SN ONT:* `' . $this->escape($report->sn_ont) . '`',
            '*Tiket Insiden:* `' . $this->escape($report->incident_ticket) . '`',
            '*Keterangan Insiden:*',
            '```',
            $this->escape($report->incident_fallout_description),
            '```',
            '*Keterangan Tambahan:*',
            '```',
            $this->escape($report->keterangan),
            '```',
            '---',
            '*Dibuat Oleh:* ' . $this->escape($createdBy),
            '*Waktu Dibuat:* `' . $this->escape($report->created_at->format('Y-m-d H:i:s')) . '`',
        ];
        $detailText = implode("\n", array_filter($detailLines));

        $groupChat = TelegramGroup::first();
        
        $destinations = array_filter([
            $groupChat ? $groupChat->chat_id : null,
            env('TELEGRAM_CHANNEL_ID')
        ]);

        foreach ($destinations as $dest) {
            if ($dest) {
                SendTelegramNotificationJob::dispatch((string)$dest, $summaryText, null, 'MarkdownV2');
            }
        }
        
        $confirmationMessage = "✅ Laporan Anda berhasil dibuat dengan detail sebagai berikut:\n\n" . $detailText;
        SendTelegramNotificationJob::dispatch((string)$this->chatId, $confirmationMessage, null, 'MarkdownV2');
    }

    private function escape(?string $text): string
    {
        if (is_null($text) || $text === '') return '-';
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace(
            $chars,
            array_map(fn ($char) => '\\' . $char, $chars),
            $text
        );
    }
}