<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendStaleOrderNotifications extends Command
{
    protected $signature = 'notify:stale-reports';
    protected $description = 'Cari laporan fallout yang belum diproses dan kirim notifikasi';

    // Ubah nilai ini ke 60 untuk produksi nanti
    private const STALE_THRESHOLD_MINUTES = 3; 
    
    public function handle()
    {
        $this->info('Mulai memeriksa laporan terbengkalai...');
        Log::info('Scheduler: Memeriksa laporan terbengkalai...');

        try {
            $openStatus = FalloutStatus::where('name', 'Open')->first();
            if (!$openStatus) {
                $this->error('Status "Open" tidak ditemukan di database.');
                Log::error('Scheduler: Status "Open" tidak ditemukan.');
                return 1;
            }

            $staleReports = FalloutReport::with(['reporter', 'orderType'])
                ->where('fallout_status_id', $openStatus->id)
                ->where('created_at', '<=', now()->subMinutes(self::STALE_THRESHOLD_MINUTES))
                ->get();

            if ($staleReports->isEmpty()) {
                $this->info('Tidak ada laporan terbengkalai yang ditemukan.');
                return 0;
            }

            $this->info("Ditemukan {$staleReports->count()} laporan terbengkalai. Memproses notifikasi...");

            foreach ($staleReports as $report) {
                $cacheKey = 'stale_notification_sent_' . $report->id;

                if (Cache::has($cacheKey)) {
                    $this->warn("Notifikasi untuk laporan #{$report->id} sudah dikirim sebelumnya. Dilewati.");
                    continue;
                }
                
                $this->sendNotification($report);
                Cache::put($cacheKey, true, now()->addHours(24));
                $this->info("Notifikasi untuk laporan #{$report->id} berhasil dikirim.");
            }

        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan saat memproses laporan: ' . $e->getMessage());
            Log::error('Scheduler Error: ' . $e->getMessage());
            return 1;
        }

        $this->info('Selesai memeriksa laporan terbengkalai.');
        return 0;
    }

    private function sendNotification(FalloutReport $report): void
    {
        $reporterName = $report->reporter->name ?? 'N/A';
        $duration = $report->created_at->diffForHumans(null, true);

        $esc = fn(?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? '-'
        );

        $lines = [
            "⚠️ *PERINGATAN: LAPORAN TERBENGKALAI*",
            "Laporan berikut belum diproses selama lebih dari *{$esc($duration)}*\\.",
            "",
            "----------------------------------------",
            "*ID Laporan:* `" . $esc($report->id) . "`",
            "*Kode Fallout:* `" . $esc($report->fallout_code) . "`",
            "*Tipe Order:* `" . $esc($report->orderType->name) . "`",
            "*Dibuat Oleh:* " . $esc($reporterName),
            "*Waktu Dibuat:* " . $esc($report->created_at->format('Y-m-d H:i:s')),
            "----------------------------------------",
            "",
            "Mohon untuk segera ditindaklanjuti\\!",
        ];

        $message = implode("\n", $lines);
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), env('TELEGRAM_GROUP_ID')]);
        
        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $message, null, 'MarkdownV2');
        }
    }
}