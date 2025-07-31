<?php

namespace App\Jobs;

use App\Models\PelurusanReport;
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

            unset($reportData['telegram_user_id']);

            $initialStatus = FalloutStatus::where('name', 'Submitted')->first();
            if (!$initialStatus) {
                Log::warning("Status awal 'Submitted' tidak ditemukan. Menggunakan fallback ID 1.");
                $initialStatusId = 1;
            } else {
                $initialStatusId = $initialStatus->id;
            }
            
            $data = [
                'tipe_order_id' => $this->tipeOrderId,
                'reporter_telegram_id' => $this->chatId,
                'fallout_status_id' => $initialStatusId,
                'order_id' => $reportData['order_id'] ?? '',
                'nomer_layanan' => $reportData['nomer_layanan'] ?? '',
                'sn_ont' => $reportData['sn_ont'] ?? '',
                'datek_odp' => $reportData['datek_odp'] ?? '',
                'port_odp' => $reportData['port_odp'] ?? 0,
                'keterangan' => $reportData['keterangan'] ?? null,
                'image_path' => empty($reportData['image_path']) ? null : $reportData['image_path'],
                'reporter_telegram_username' => $userInfo['username'] ?? $userInfo['first_name'],
            ];

            Log::info('Attempting to create PelurusanReport. Model table: ' . (new PelurusanReport())->getTable());
            Log::info('Data for PelurusanReport creation:', $data);

            $report = PelurusanReport::create($data);
            
            $this->sendCreationNotification($report, $userInfo);

        } catch (\Exception $e) {
            Log::error("Gagal memproses laporan pelurusan baru: " . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            SendTelegramNotificationJob::dispatch((string)$this->chatId, '❌ Terjadi kesalahan fatal saat menyimpan laporan pelurusan Anda.');
        }
    }
    
    private function sendCreationNotification(PelurusanReport $report, array $userInfo): void
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
            '*Tipe Order:* ' . $escapeMarkdown($report->orderType->name),
            '*Nomor Layanan:* ' . $escapeMarkdown($report->nomer_layanan),
            '*Datek ODP:* ' . $escapeMarkdown($report->datek_odp) . ' Port ' . $escapeMarkdown((string)$report->port_odp),
            '*Dilaporkan Oleh:* ' . $escapeMarkdown($createdBy),
            '*Waktu:* ' . $escapeMarkdown($report->created_at->format('Y-m-d H:i:s')),
        ];

        $reportText = implode("\n", array_filter($lines));

        $groupChat = \App\Models\TelegramGroup::first();
        $destinations = array_filter([env('TELEGRAM_CHANNEL_ID'), $groupChat ? $groupChat->chat_id : null]);

        foreach ($destinations as $dest) {
            if ($dest) {
                SendTelegramNotificationJob::dispatch((string)$dest, $reportText, null, 'MarkdownV2');
            }
        }
    }
}