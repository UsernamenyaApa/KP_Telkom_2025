<?php

namespace App\Jobs;

use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTelegramPelurusanReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $chatId,
        protected array $state,
        protected int $tipeOrderId
    ) {}

    /**
     * Titik masuk utama untuk menjalankan proses penyimpanan laporan.
     */
    public function handle(): void
    {
        Log::info('Memulai proses penyimpanan laporan pelurusan.', ['chat_id' => $this->chatId]);

        try {
            // Membungkus semua operasi database dalam satu transaksi
            DB::transaction(function () {
                $reportData = data_get($this->state, 'report_data', []);
                $userInfo = data_get($this->state, 'user_info');

                if (!$userInfo) {
                    throw new \Exception('Informasi pengguna tidak ditemukan dalam state.');
                }

                // 1. Siapkan data HANYA untuk tabel utama (pelurusan_reports)
                $mainReportData = $this->prepareMainReportData($reportData, $userInfo);

                // 2. Ekstrak data gambar dari state
                $imagesData = data_get($reportData, 'images', []);
                if (empty($imagesData) || !is_array($imagesData)) {
                    throw new \Exception('Laporan harus memiliki minimal 1 gambar.');
                }

                // 3. Simpan laporan utama ke database dan dapatkan modelnya
                $pelurusanReport = $this->saveMainReport($mainReportData);

                // 4. Simpan setiap path gambar ke tabel relasi
                foreach ($imagesData as $imagePath) {
                    $pelurusanReport->images()->create(['image_path' => $imagePath]);
                }

                // 5. Kirim notifikasi setelah semua berhasil tersimpan
                //    Gunakan ->fresh() untuk mendapatkan data terbaru termasuk relasi gambar
                $this->notifyRelevantParties($pelurusanReport->fresh(), $userInfo);
            });

        } catch (Throwable $e) {
            Log::error("Gagal memproses laporan pelurusan untuk chat {$this->chatId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyUserOnFailure('Terjadi kesalahan teknis saat menyimpan laporan.');
        }
    }

    /**
     * Menyiapkan data HANYA untuk tabel utama `pelurusan_reports`.
     * Fungsi ini memastikan tidak ada data 'images' yang ikut masuk.
     */
    private function prepareMainReportData(array $reportData, array $userInfo): array
    {
        $sanitize = fn(?string $text) => $text ? str_replace('\\', '', $text) : null;

        $data = [
            'tipe_order_id' => $this->tipeOrderId,
            'nomer_layanan' => $sanitize(data_get($reportData, 'nomer_layanan')),
            'datek_odp'     => $sanitize(data_get($reportData, 'datek_odp')),
            'port_odp'      => is_numeric($portOdp = data_get($reportData, 'port_odp')) ? (int) $portOdp : null,
        ];

        // Kustomisasi berdasarkan tipe order
        if ($this->tipeOrderId == 8) { // ID untuk "Ex Gangguan"
            $data['order_id'] = $sanitize(data_get($reportData, 'nomor_incident'));
            $data['sn_ont'] = '-';
        } else {
            $data['order_id'] = $sanitize(data_get($reportData, 'order_id'));
            $data['sn_ont'] = $sanitize(data_get($reportData, 'sn_ont'));
            $data['incident_fallout_description'] = $sanitize(data_get($reportData, 'incident_fallout_description'));
            $data['keterangan'] = $sanitize(data_get($reportData, 'keterangan'));
        }

        // Menambahkan info pelapor
        if ($dbUserId = data_get($userInfo, 'db_user_id')) {
            $data['reporter_user_id'] = $dbUserId;
        } else {
            $data['reporter_telegram_id'] = data_get($userInfo, 'id');
            $data['reporter_telegram_username'] = data_get($userInfo, 'username');
        }

        unset($data['image']); // Ensure 'image' is not passed to the main report table
        return $data;
    }

    /**
     * Menyimpan data ke tabel `pelurusan_reports` dan mengembalikan instance model.
     */
    private function saveMainReport(array $mainReportData): PelurusanReport
    {
        $today = Carbon::today();
        $idHarian = (PelurusanReport::whereDate('created_at', $today)->max('id_harian') ?? 0) + 1;
        $openStatus = FalloutStatus::where('name', 'Open')->firstOrFail();

        $mainReportData['id_harian'] = $idHarian;
        $mainReportData['pelurusan_code'] = 'PL' . $today->format('Ymd') . str_pad($idHarian, 3, '0', STR_PAD_LEFT);
        $mainReportData['fallout_status_id'] = $openStatus->id;

        
        return PelurusanReport::create($mainReportData);
    }

    /**
     * Mengirim notifikasi ke pihak-pihak terkait.
     * (Fungsi ini tidak diubah, asumsikan sudah benar)
     */
    private function notifyRelevantParties(PelurusanReport $report, array $userInfo): void
    {
        $esc = fn (?string $text) => str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
            ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
            $text ?? ''
        );

        $reporterName = data_get($userInfo, 'name', data_get($userInfo, 'username', 'N/A'));
        $createdBy = data_get($userInfo, 'username') ? "@{$userInfo['username']}" : $reporterName;

        $lines = ["✏️ *Laporan Pelurusan Baru*\n"];
        $lines[] = "*ID Laporan:* `{$esc($report->id_harian)}`";
        $lines[] = "*Kode Pelurusan:* `{$esc($report->pelurusan_code)}`";
        $lines[] = "*Tipe Order:* `{$esc($report->orderType->name)}`";

        if ($report->tipe_order_id == 8) { // Ex Gangguan
            $lines[] = "*Nomor Incident:* `{$esc($report->order_id)}`";
            $lines[] = "*Nomor Layanan:* `{$esc($report->nomer_layanan)}`";
            $lines[] = "*Datek ODP:* `{$esc($report->datek_odp)}`";
            $lines[] = "*Port ODP:* `{$esc($report->port_odp)}`";
        } else {
            $lines[] = "*OrderID:* `{$esc($report->order_id)}`";
            $lines[] = "*Nomor Layanan:* `{$esc($report->nomer_layanan)}`";
            $lines[] = "*SN ONT:* `{$esc($report->sn_ont)}`";
            $lines[] = "*Datek ODP:* `{$esc($report->datek_odp)}`";
            $lines[] = "*Port ODP:* `{$esc($report->port_odp)}`";
            $lines[] = "\n*Keterangan Insiden:*";
            $lines[] = "```";
            $lines[] = $esc($report->incident_fallout_description);
            $lines[] = "```";
            $lines[] = "*Keterangan Tambahan:*";
            $lines[] = "```";
            $lines[] = $esc($report->keterangan);
            $lines[] = "```";
        }

        if ($report->images->isNotEmpty()) {
            $lines[] = $esc("\n*Gambar Terlampir: (" . $report->images->count() . ")*");
        }

        $lines[] = ""; // Safe separator
        $lines[] = '*Dibuat Oleh:* ' . $esc($createdBy);
        $lines[] = '*Waktu Dibuat:* `' . $esc($report->created_at->format('Y-m-d H:i:s')) . '`';

        $reportText = implode("\n", $lines);
        
        $groupChatId = \App\Models\TelegramGroup::first()?->chat_id;
        $destinations = array_unique(array_filter([env('TELEGRAM_CHANNEL_ID'), $groupChatId, $this->chatId]));

        foreach ($destinations as $chatId) {
            \App\Jobs\SendTelegramNotificationJob::dispatch($chatId, $reportText, null, 'MarkdownV2');
        }
    }

    private function notifyUserOnFailure(string $message): void
    {
        \App\Jobs\SendTelegramNotificationJob::dispatch($this->chatId, "❌ *Gagal memproses laporan:*\n{$message}");
    }
}