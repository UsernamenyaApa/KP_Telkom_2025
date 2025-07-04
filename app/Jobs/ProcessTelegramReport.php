<?php

namespace App\Jobs;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\OrderType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\SendTelegramNotificationJob;
use Telegram\Bot\Exceptions\TelegramSDKException;

class ProcessTelegramReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chat_id;
    protected $state;
    protected $tipe_order_id;

    /**
     * Create a new job instance.
     *
     * @param int $chat_id
     * @param array $state
     * @param int $tipe_order_id
     */
    public function __construct($chat_id, $state, $tipe_order_id)
    {
        $this->chat_id = $chat_id;
        $this->state = $state;
        $this->tipe_order_id = $tipe_order_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Log::debug("*** ProcessTelegramReport Job Started ***");

        $now = Carbon::now('Asia/Jakarta');
        
        $state = $this->state;
        $reportData = $state['report_data'];
        $user = $state['user'];
        $createdBy = $user['username'] ? "@" . $user['username'] : $user['first_name'];

        // Temukan atau buat pengguna yang melaporkan dengan lebih ringkas.
        $reporterUser = User::firstOrCreate(
            ['telegram_user_id' => $user['id']],
            [
                'telegram_username' => $user['username'] ?? null,
                'name' => $user['first_name'] . ' ' . ($user['last_name'] ?? ''),
                'password' => bcrypt(Str::random(10)),
            ]
        );
        
        // Hasilkan id_harian dan fallout_code.
        $today = $now->copy()->startOfDay(); // Gunakan waktu yang sudah disesuaikan
        
        // Use updateOrCreate to handle both creation and incrementing
        $dailyCounter = DB::table('daily_counters')->where('report_date', $today)->first();

        if ($dailyCounter) {
            DB::table('daily_counters')->where('report_date', $today)->increment('last_number');
            $id_harian = $dailyCounter->last_number + 1;
        } else {
            DB::table('daily_counters')->insert(['report_date' => $today, 'last_number' => 1]);
            $id_harian = 1;
        }

        // Retrieve the updated last_number for id_harian
        $id_harian = DB::table('daily_counters')->where('report_date', $today)->value('last_number');

        $openStatus = Cache::remember('fallout_status_open', now('Asia/Jakarta')->addMinutes(60), fn() => FalloutStatus::where('name', 'Open')->first());

        // Siapkan data untuk disimpan ke database.
        $dbData = [
            'tipe_order_id' => $this->tipe_order_id,
            'order_id'      => $reportData['order_id'] ?? null,
            'reporter_user_id' => $reporterUser->id,
            'nomer_layanan' => $reportData['nomer_layanan'] ?? null,
            'sn_ont'        => $reportData['sn_ont'] ?? null,
            'datek_odp'     => $reportData['datek_odp'] ?? null,
            'port_odp'      => $reportData['port_odp'] ?? null,
            'keterangan'    => $reportData['keterangan'] ?? null,
            'id_harian'     => $id_harian,
            'fallout_code'  => 'FA' . $today->format('Ymd') . str_pad($id_harian, 2, '0', STR_PAD_LEFT),
            'fallout_status_id' => $openStatus?->id,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];
        
        // Simpan ke database.
        try {
            FalloutReport::insert($dbData);
        } catch (\Exception $e) {
            Log::error("DATABASE SAVE FAILED for chat {$this->chat_id}. Error: " . $e->getMessage());
            $this->sendMessage($this->chat_id, "❌ Terjadi kesalahan fatal saat menyimpan laporan ke database. Laporan tidak tersimpan. Silakan hubungi admin.");
            return;
        }

        // Format pesan laporan.
        $orderType = OrderType::find($dbData['tipe_order_id']);
        $escapeForMarkdown = fn(?string $text): string => $text ? str_replace(['_', '*', '`', '['], ['\_', '\*', '`', '\['], $text) : '-';

        $reportText = "📊 *Laporan Fallout Baru* 📊

"
            . "*Tipe Order:* `" . $escapeForMarkdown($orderType?->name) . "`
"
            . "*OrderID:* `" . $escapeForMarkdown($dbData['order_id']) . "`
"
            . "*Nomor Layanan:* `" . $escapeForMarkdown($dbData['nomer_layanan']) . "`
"
            . "*SN ONT:* `" . $escapeForMarkdown($dbData['sn_ont']) . "`
"
            . "*Datek ODP:* `" . $escapeForMarkdown($dbData['datek_odp']) . "`
"
            . "*Port ODP:* `" . $escapeForMarkdown($dbData['port_odp']) . "`

"
            . "*Keterangan:*
" . $escapeForMarkdown($dbData['keterangan']) . "

"
            . "----------------------------------------
"
            . "*Created By:* " . $escapeForMarkdown($createdBy) . "
";

        $reportText .= "*Create Order:* " . $now->format('Y-m-d H:i:s') . " (WIB - Confirmed)";

        // Kirim konfirmasi dan siarkan laporan.
        $this->sendMessage($this->chat_id, "✅ Laporan berhasil dibuat dan dikirim!");
        $destinations = [env('TELEGRAM_CHANNEL_ID'), env('TELEGRAM_GROUP_ID'), $this->chat_id];
        foreach (array_unique($destinations) as $to_chat_id) {
            if ($to_chat_id) {
                try {
                    $this->sendMessage($to_chat_id, $reportText);
                } catch (TelegramSDKException $e) {
                    Log::error("Failed to send report to {$to_chat_id}: " . $e->getMessage());
                }
            }
        }
    }

    private function sendMessage($chat_id, $text, $reply_markup = null)
    {
        SendTelegramNotificationJob::dispatch($chat_id, $text, $reply_markup);
    }
}
