<?php

namespace App\Jobs;

use App\Models\PelurusanReport;
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

class ProcessTelegramPelurusanReport implements ShouldQueue
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
        Log::info('Memulai proses penyimpanan laporan pelurusan.', ['chat_id' => $this->chatId]);

        try {
            $userInfo = data_get($this->state, 'user_info');
            if (!$userInfo) {
                throw new \Exception("Informasi pengguna tidak ditemukan dalam state.");
            }

            $reporterUser = $this->findOrCreateReporter($userInfo);
            $reportData = data_get($this->state, 'report_data', []);
            
            $dbData = $this->prepareReportDataForStorage($reportData, $reporterUser->id);
            $pelurusanReport = $this->saveReportToDatabase($dbData);
            
            $this->notifyUserOnSuccess($pelurusanReport);
            $this->notifyAdmins($pelurusanReport, $reporterUser);
            $this->notifyNewOrderCreated($pelurusanReport);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan pelurusan untuk chat {$this->chatId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyUserOnFailure("Terjadi kesalahan teknis saat menyimpan laporan.");
        }
    }
    
    private function prepareReportDataForStorage(array $reportData, int $reporterUserId): array
    {
        $portOdp = data_get($reportData, 'port_odp');
        
        return [
            'tipe_order_id' => $this->tipeOrderId,
            'reporter_user_id' => $reporterUserId,
            'order_id' => data_get($reportData, 'order_id'),
            'nomer_layanan' => data_get($reportData, 'nomer_layanan'),
            'sn_ont' => data_get($reportData, 'sn_ont'),
            'datek_odp' => data_get($reportData, 'datek_odp'),
            'port_odp' => is_numeric($portOdp) ? (int) $portOdp : null,
            'incident_fallout_description' => data_get($reportData, 'incident_fallout_description'),
            'keterangan' => data_get($reportData, 'keterangan'),
        ];
    }

    private function saveReportToDatabase(array $dbData): PelurusanReport
    {
        return DB::transaction(function () use ($dbData) {
            $today = Carbon::today();
            $idHarian = (PelurusanReport::whereDate('created_at', $today)->max('id_harian') ?? 0) + 1;
            
            $openStatus = FalloutStatus::where('name', 'Open')->firstOrFail();

            $dbData['id_harian'] = $idHarian;
            $dbData['pelurusan_code'] = 'PL' . $today->format('Ymd') . str_pad($idHarian, 3, '0', STR_PAD_LEFT);
            $dbData['fallout_status_id'] = $openStatus->id;

            return PelurusanReport::create($dbData);
        });
    }

    private function notifyAdmins(PelurusanReport $report, User $reporter): void
    {
        $createdBy = $reporter->telegram_username ? "@{$reporter->telegram_username}" : $reporter->name;
        
        $esc = fn(?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? '-'
        );

        $lines = [
            "✏️ *Laporan Pelurusan Baru*",
            "",
            "*ID Laporan:* `" . $esc($report->id) . "`",
            "*Kode Pelurusan:* `" . $esc($report->pelurusan_code) . "`",
            "*Tipe Order:* `" . $esc($report->orderType->name) . "`",
            "*OrderID:* `" . $esc($report->order_id) . "`",
            "*Nomor Layanan:* `" . $esc($report->nomer_layanan) . "`",
            "*SN ONT:* `" . $esc($report->sn_ont) . "`",
            "*Datek ODP:* `" . $esc($report->datek_odp) . "`",
            "*Port ODP:* `" . $esc($report->port_odp) . "`",
            "",
            "*Keterangan Insiden:*",
            "```",
            $esc($report->incident_fallout_description),
            "```",
            "*Keterangan Tambahan:*",
            "```",
            $esc($report->keterangan),
            "```",
            "----------------------------------------",
            "*Dibuat Oleh:* " . $esc($createdBy),
            "*Waktu Dibuat:* " . $esc($report->created_at->format('Y-m-d H:i:s')),
        ];
        
        $reportText = implode("\n", $lines);
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), env('TELEGRAM_GROUP_ID')]);

        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
    }
    
    private function findOrCreateReporter(array $userInfo): User
    {
        return User::updateOrCreate(
            ['telegram_user_id' => $userInfo['id']],
            [
                'telegram_username' => $userInfo['username'],
                'name' => trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? '')),
                'email' => $userInfo['username'] ? "{$userInfo['username']}@telegram.user" : "tele-{$userInfo['id']}@telegram.user",
                'password' => bcrypt(Str::random(16)),
            ]
        );
    }

    private function notifyUserOnSuccess(PelurusanReport $report): void
    {
        SendTelegramNotificationJob::dispatch($this->chatId, "✅ Laporan Anda dengan ID #{$report->id} telah berhasil diproses dan disimpan.");
    }
    
    private function notifyUserOnFailure(string $message): void
    {
        SendTelegramNotificationJob::dispatch($this->chatId, "❌ Gagal memproses laporan: {$message}");
    }

    private function notifyNewOrderCreated(PelurusanReport $report): void
    {
        $esc = fn(?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? '-'
        );

        $message = "✨ *Order Baru Dibuat!* ✨\n\n"
                   . "*ID Laporan:* `" . $esc($report->id) . "`\n"
                   . "*Kode Pelurusan:* `" . $esc($report->pelurusan_code) . "`\n"
                   . "*Tipe Order:* `" . $esc($report->orderType->name) . "`\n"
                   . "*OrderID:* `" . $esc($report->order_id) . "`\n"
                   . "*Nomor Layanan:* `" . $esc($report->nomer_layanan) . "`\n"
                   . "*SN ONT:* `" . $esc($report->sn_ont) . "`\n"
                   . "*Datek ODP:* `" . $esc($report->datek_odp) . "`\n"
                   . "*Port ODP:* `" . $esc($report->port_odp) . "`\n"
                   . "*Dibuat pada:* `" . $esc($report->created_at->format('d M Y H:i:s')) . "`\n"
                   . "Mohon segera ditindaklanjuti.";

        SendTelegramNotificationJob::dispatch(env('TELEGRAM_GROUP_ID'), $message, null, 'MarkdownV2');
    }
}