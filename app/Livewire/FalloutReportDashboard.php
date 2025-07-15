<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendTelegramNotificationJob;
use Telegram\Bot\Laravel\Facades\Telegram;

class FalloutReportDashboard extends Component
{
    use WithPagination;

    public $date;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function takeOrder($reportId)
    {
        $report = FalloutReport::with(['orderType', 'reporter'])->find($reportId);

        if ($report) {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $report->fallout_status_id = $onProgressStatus->id;
                $report->assigned_to_user_id = Auth::id(); // Store user ID
                if (is_null($report->assigned_at)) {
                    $report->assigned_at = now();
                }
                $report->taken_at = now();
                $report->save();

                $user = Auth::user();

                $message = "✅ *Laporan Fallout Diambil!* ✅\n\n" .
                           "*ID Laporan:* `" . ($report->id ?? 'N/A') . "`\n" .
                           "*Kode Fallout:* `" . ($report->fallout_code ?? 'N/A') . "`\n" .
                           "*Tipe Order:* `" . ($report->orderType ? $report->orderType->name : 'N/A') . "`\n" .
                           "*OrderID:* `" . ($report->order_id ?? 'N/A') . "`\n" .
                           "*Nomor Layanan:* `" . ($report->nomer_layanan ?? 'N/A') . "`\n" .
                           "*SN ONT:* `" . ($report->sn_ont ?? 'N/A') . "`\n" .
                           "*Datek ODP:* `" . ($report->datek_odp ?? 'N/A') . "`\n" .
                           "*Port ODP:* `" . ($report->port_odp ?? 'N/A') . "`\n\n" .
                           "*Diambil Oleh:* @" . ($user->telegram_username ?? 'N/A') . "\n" .
                           "*Waktu Diambil:* " . ($report->assigned_at ? $report->assigned_at->format('Y-m-d H:i:s') : 'N/A') . "\n\n" .
                           "Mohon pantau status laporan ini.";

                // Send to personal chat (taker)
                if ($user->telegram_user_id) {
                    SendTelegramNotificationJob::dispatch($user->telegram_user_id, $message);
                }

                // Send to personal chat (reporter)
                if ($report->reporter && $report->reporter->telegram_user_id) {
                    SendTelegramNotificationJob::dispatch($report->reporter->telegram_user_id, $message);
                }

                // Send to group chat
                $groupChatId = env('TELEGRAM_GROUP_ID');
                if ($groupChatId) {
                    SendTelegramNotificationJob::dispatch($groupChatId, $message);
                }
            }
        }
    }

    public function render()
    {
        $reports = FalloutReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser'])
            ->whereDate('created_at', $this->date)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
