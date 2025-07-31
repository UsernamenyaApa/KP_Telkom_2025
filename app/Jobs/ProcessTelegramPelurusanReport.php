<?php

namespace App\Jobs;

use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessTelegramPelurusanReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $chatId,
        protected array $state,
        protected int $tipeOrderId
    ) {}

    public function handle(): void
    {
        Log::info('Memulai proses penyimpanan laporan pelurusan.', ['chat_id' => $this->chatId]);

        try {
            $userInfo = data_get($this->state, 'user_info');
            if (! $userInfo) {
                throw new \Exception('Informasi pengguna tidak ditemukan dalam state.');
            }

            $reportData = data_get($this->state, 'report_data', []);

            $dbData = $this->prepareReportDataForStorage($reportData, $userInfo);
            $pelurusanReport = $this->saveReportToDatabase($dbData);
            
            $this->notifyRelevantParties($pelurusanReport, $userInfo);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan pelurusan untuk chat {$this->chatId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyUserOnFailure('Terjadi kesalahan teknis saat menyimpan laporan.');
        }
    }
    
    private function sendCreationNotification(PelurusanReport $report, array $userInfo): void
    {
        $createdBy = $userInfo['username'] ? "@{$userInfo['username']}" : ($userInfo['name'] ?? $userInfo['first_name']);

        $lines = [
            '✅ *Laporan Pelurusan Data Baru Diterima*',
            '*Tipe Order:* ' . $this->escape($report->orderType->name),
            '*Nomor Layanan:* ' . $this->escape($report->nomer_layanan),
            '*Datek ODP:* ' . $this->escape($report->datek_odp) . ' Port ' . $this->escape((string)$report->port_odp),
            '*Dilaporkan Oleh:* ' . $this->escape($createdBy),
            '*Waktu:* ' . $this->escape($report->created_at->format('Y-m-d H:i:s')),
        ];
    }

    private function prepareReportDataForStorage(array $reportData, array $userInfo): array
    {
        $portOdp = data_get($reportData, 'port_odp');

        $data = [
            'tipe_order_id' => $this->tipeOrderId,
            'nomer_layanan' => data_get($reportData, 'nomer_layanan'),
            'datek_odp' => data_get($reportData, 'datek_odp'),
            'port_odp' => is_numeric($portOdp) ? (int) $portOdp : null,
            'image' => data_get($reportData, 'image'),
        ];

        // Customize data based on order type
        if ($this->tipeOrderId == 8) { // 8 is the ID for "Ex Gangguan"
            $data['order_id'] = data_get($reportData, 'nomor_incident');
            $data['sn_ont'] = '-'; // Not applicable
            $data['incident_fallout_description'] = null;
            $data['keterangan'] = null;
        } else {
            $data['order_id'] = data_get($reportData, 'order_id');
            $data['sn_ont'] = data_get($reportData, 'sn_ont');
            $data['incident_fallout_description'] = data_get($reportData, 'incident_fallout_description');
            $data['keterangan'] = data_get($reportData, 'keterangan');
        }

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

    private function saveReportToDatabase(array $dbData): PelurusanReport
    {
        return DB::transaction(function () use ($dbData) {
            $today = Carbon::today();
            $idHarian = (PelurusanReport::whereDate('created_at', $today)->max('id_harian') ?? 0) + 1;

            $openStatus = FalloutStatus::where('name', 'Open')->firstOrFail();

            $dbData['id_harian'] = $idHarian;
            $dbData['pelurusan_code'] = 'PL'.$today->format('Ymd').str_pad($idHarian, 3, '0', STR_PAD_LEFT);
            $dbData['fallout_status_id'] = $openStatus->id;

            return PelurusanReport::create($dbData);
        });
    }

    private function notifyRelevantParties(PelurusanReport $report, array $userInfo): void
    {
        // Use the real name if available (Office Staff), otherwise use the Telegram username.
        $reporterName = data_get($userInfo, 'name', data_get($userInfo, 'username', 'N/A'));
        $createdBy = data_get($userInfo, 'username') ? "@{$userInfo['username']}" : $reporterName;

        $esc = fn (?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? '-'
        );

        $lines = [
            '✏️ *Laporan Pelurusan Baru*',
            '',
            '*ID Laporan:* `' . $esc($report->id_harian) . '`',
            '*Kode Pelurusan:* `' . $esc($report->pelurusan_code) . '`',
            '*Tipe Order:* `' . $esc($report->orderType->name) . '`',
        ];

        if ($report->tipe_order_id == 8) { // Ex Gangguan
            $lines[] = '*Nomor Incident:* `' . $esc($report->order_id) . '`';
            $lines[] = '*Nomor Layanan:* `' . $esc($report->nomer_layanan) . '`';
            $lines[] = '*Datek ODP:* `' . $esc($report->datek_odp) . '`';
            $lines[] = '*Port ODP:* `' . $esc($report->port_odp) . '`';
        } else {
            // Sanitize input for the code block to prevent parsing errors.
            // Within `pre` blocks, all `\` and `` ` `` characters must be escaped.
            $description = $report->incident_fallout_description ?? '-';
            $keterangan = $report->keterangan ?? '-';
            $sanitizedDescription = str_replace(['\', '`'], ['\\', '\`'], $description);
            $sanitizedKeterangan = str_replace(['\', '`'], ['\\', '\`'], $keterangan);

            $lines[] = '*OrderID:* `' . $esc($report->order_id) . '`';
            $lines[] = '*Nomor Layanan:* `' . $esc($report->nomer_layanan) . '`';
            $lines[] = '*SN ONT:* `' . $esc($report->sn_ont) . '`';
            $lines[] = '*Datek ODP:* `' . $esc($report->datek_odp) . '`';
            $lines[] = '*Port ODP:* `' . $esc($report->port_odp) . '`';
            $lines[] = '';
            $lines[] = '*Keterangan Insiden:*';
            $lines[] = '```';
            $lines[] = $sanitizedDescription;
            $lines[] = '```';
            $lines[] = '*Keterangan Tambahan:*';
            $lines[] = '```';
            $lines[] = $sanitizedKeterangan;
            $lines[] = '```';
        }

        $lines[] = '----------------------------------------';
        $lines[] = '*Dibuat Oleh:* ' . $esc($createdBy);
        $lines[] = '*Waktu Dibuat:* ' . $esc($report->created_at->format('Y-m-d H:i:s'));

        $reportText = implode("\n", $lines);
        $groupChat = \App\Models\TelegramGroup::first();
        $reporterChatId = data_get($userInfo, 'id'); // Get reporter's chat ID from userInfo
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), $groupChat ? $groupChat->chat_id : null, $reporterChatId]);

        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
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
    
    private function notifyUserOnFailure(string $message): void
    {
        SendTelegramNotificationJob::dispatch($this->chatId, "❌ Gagal memproses laporan: {$message}");
    }
}