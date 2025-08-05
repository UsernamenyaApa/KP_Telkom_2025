<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;

class ProcessPelurusanMediaGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_IMAGES = 5;
    private const CACHE_TTL_MINUTES = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $chatId,
        protected string $mediaGroupId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $state = Cache::get($this->chatId);
        if (!isset($state['process']) || $state['process'] !== 'pelurusan') {
            return; // Bukan proses pelurusan, abaikan
        }

        $cacheKey = "media_group_{$this->mediaGroupId}";
        $fileIds = Cache::pull($cacheKey, []);

        if (empty($fileIds)) {
            Log::warning("Tidak ada file_id ditemukan di cache untuk media_group_id: {$this->mediaGroupId}");
            return;
        }

        try {
            $telegram = new Api(config('telegram.bots.mybot.token'));
            $imagePaths = $state['report_data']['images'] ?? [];
            $imagesProcessedCount = 0;

            foreach ($fileIds as $fileId) {
                if (count($imagePaths) >= self::MAX_IMAGES) {
                    break; // Berhenti jika sudah mencapai batas maksimal
                }

                $file = $telegram->getFile(['file_id' => $fileId]);
                $fileContents = file_get_contents('https://api.telegram.org/file/bot' . config('telegram.bots.mybot.token') . "/{$file->filePath}");

                $directory = 'pelurusan-images/';
                $fileName = $directory . uniqid() . '_' . time() . '_' . basename($file->filePath);
                Storage::disk('public')->put($fileName, $fileContents);

                $imagePaths[] = $fileName;
                $imagesProcessedCount++;
            }

            $state['report_data']['images'] = $imagePaths;
            $totalImages = count($imagePaths);

            if ($totalImages >= self::MAX_IMAGES) {
                // Batas maksimal tercapai, langsung proses laporan
                SendTelegramNotificationJob::dispatch($this->chatId, "✅ Gambar ke-{$totalImages} telah diterima. Batas maksimal tercapai, laporan akan diproses.");
                ProcessTelegramPelurusanReport::dispatch($this->chatId, $state, $state['report_data']['tipe_order_id']);
                Cache::forget($this->chatId);
                return;
            }

            // Update state dan kirim konfirmasi
            $state['step'] = 'awaiting_more_images_confirmation';
            Cache::put($this->chatId, $state, now()->addMinutes(self::CACHE_TTL_MINUTES));

            $keyboard = [
                'inline_keyboard' => [
                    [['text' => '✅ Selesai', 'callback_data' => 'done_sending_images']],
                    [['text' => 'Kirim Gambar Lain', 'callback_data' => 'send_more_images']],
                ],
            ];

            $message = "{$imagesProcessedCount} gambar berhasil diterima. Total gambar saat ini: {$totalImages}."
                     . "\nKirim gambar lain (maksimal " . self::MAX_IMAGES . "), atau klik Selesai.";

            SendTelegramNotificationJob::dispatch($this->chatId, $message, $keyboard);

        } catch (\Exception $e) {
            Log::error("Gagal memproses media group: " . $e->getMessage(), [
                'chat_id' => $this->chatId,
                'media_group_id' => $this->mediaGroupId,
                'trace' => $e->getTraceAsString(),
            ]);
            SendTelegramNotificationJob::dispatch($this->chatId, '❌ Terjadi kesalahan teknis saat memproses gambar Anda.');
        }
    }
}