<?php

namespace App\Jobs;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramPelurusanReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $chatId;
    protected array $state;
    protected int $tipeOrderId;

    public function __construct(int $chatId, array $state, int $tipeOrderId)
    {
        $this->chatId = $chatId;
        $this->state = $state;
        $this->tipeOrderId = $tipeOrderId;
    }

    public function handle(): void
    {
        try {
            $reportData = $this->state['report_data'];
            $userInfo = $this->state['user_info'];

            $initialStatus = FalloutStatus::where('name', 'Submitted')->first();
            if (!$initialStatus) {
                Log::warning("Status awal 'Submitted' tidak ditemukan. Menggunakan fallback ID 1.");
                $initialStatusId = 1;
            } else {
                $initialStatusId = $initialStatus->id;
            }
            
            $report = FalloutReport::create([
                'tipe_order_id' => $this->tipeOrderId,
                // 'user_id' BARIS INI DIHAPUS KARENA TIDAK ADA DI DATABASE ANDA
                'telegram_user_id' => $this->chatId,
                'fallout_status_id' => $initialStatusId,
                'incident_ticket' => $reportData['nomor_incident'] ?? null,
                'incident_fallout_description' => $reportData['incident_fallout_description'] ?? null,
                'order_id' => $reportData['order_id'] ?? null,
                'nomer_layanan' => $reportData['nomer_layanan'] ?? null,
                'sn_ont' => $reportData['sn_ont'] ?? null,
                'datek_odp' => $reportData['datek_odp'] ?? null,
                'port_odp' => $reportData['port_odp'] ?? null,
                'keterangan' => $reportData['keterangan'] ?? null,
                'image_path' => $reportData['image'] ?? null,
                'created_by_telegram_username' => $userInfo['username'] ?? $userInfo['first_name'],
            ]);
            
            $this->sendCreationNotification($report, $userInfo);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan pelurusan baru: " . $e->getMessage());
            SendTelegramNotificationJob::dispatch($this->chatId, '❌ Terjadi kesalahan fatal saat menyimpan laporan pelurusan Anda.');
        }
    }
    
    private function sendCreationNotification(FalloutReport $report, array $userInfo): void
    {
        $escapeMarkdown = function (?string $text): string {
            if ($text === null || $text === '') return '-';
            $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
            $escapedChars = array_map(fn($c) => '\\' . $c, $chars);
            return str_replace($chars, $escapedChars, $text);
        };
        
        $createdBy = $userInfo['username'] ? "@{$userInfo['username']}" : ($userInfo['name'] ?? $userInfo['first_name']);

        $lines = [
            '✅ *Laporan Pelurusan Data Baru Diterima*',
            '',
            '*Tipe Order:* ' . $escapeMarkdown($report->orderType->name),
            '*Nomor Layanan:* ' . $escapeMarkdown($report->nomer_layanan),
            '*Datek ODP:* ' . $escapeMarkdown($report->datek_odp) . ' Port ' . $escapeMarkdown((string)$report->port_odp),
            '',
            '*Dilaporkan Oleh:* ' . $escapeMarkdown($createdBy),
            '*Waktu:* ' . $escapeMarkdown($report->created_at->format('Y-m-d H:i:s')),
        ];

        $reportText = implode("\n", $lines);
        $groupChat = \App\Models\TelegramGroup::first();
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), $groupChat ? $groupChat->chat_id : null]);

        foreach ($destinations as $chatId) {
            SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
    }
}