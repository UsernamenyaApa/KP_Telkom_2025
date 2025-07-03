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
    public $showStatusModal = false;
    public $selectedReport;
    public $newStatusId;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function openStatusModal($reportId)
    {
        $this->selectedReport = FalloutReport::find($reportId);
        $this->showStatusModal = true;
    }

    public function changeStatus()
    {
        if ($this->selectedReport && $this->newStatusId && $this->selectedReport->assigned_to_user_id == auth()->id()) {
            $this->selectedReport->fallout_status_id = $this->newStatusId;
            $this->selectedReport->save();

            $newStatus = FalloutStatus::find($this->newStatusId);

            if (in_array($newStatus->name, ['input ulang', 'eskalasi', 'PI', 'FA'])) {
                $message = "Laporan Fallout Selesai\n\n" .
                           "Tipe Order: " . ($this->selectedReport->orderType ? $this->selectedReport->orderType->name : 'N/A') . "\n" .
                           "OrderID: " . $this->selectedReport->order_id . "\n" .
                           "Nomor Layanan: " . $this->selectedReport->nomer_layanan . "\n" .
                           "SN ONT: " . $this->selectedReport->sn_ont . "\n" .
                           "Datek ODP: " . $this->selectedReport->datek_odp . "\n" .
                           "Port ODP: " . $this->selectedReport->port_odp . "\n\n" .
                           "Keterangan:\n" . $this->selectedReport->keterangan . "\n\n" .
                           "----------------------------------------\n" .
                           "Created By: @" . ($this->selectedReport->reporter ? $this->selectedReport->reporter->telegram_username : 'N/A') . "\n" .
                           "Create Order: " . $this->selectedReport->created_at->format('Y-m-d H:i:s') . "\n\n" .
                           "Take by: @" . auth()->user()->telegram_username;

                // Send to personal chat (reporter)
                if ($this->selectedReport->reporter && $this->selectedReport->reporter->telegram_user_id) {
                    SendTelegramNotificationJob::dispatch($this->selectedReport->reporter->telegram_user_id, $message);
                }

                // Send to group chat
                $groupChatId = env('TELEGRAM_GROUP_ID');
                if ($groupChatId) {
                    SendTelegramNotificationJob::dispatch($groupChatId, $message);
                }
            }

            $this->showStatusModal = false;
        }
    }

    public function takeOrder($reportId)
    {
        $report = FalloutReport::with(['orderType', 'reporter'])->find($reportId);

        if ($report) {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $report->fallout_status_id = $onProgressStatus->id;
                $report->assigned_to_user_id = Auth::id(); // Store user ID
                $report->save();

                $user = Auth::user();

                $message = "Laporan Fallout On Progress\n\n" .
                           "Tipe Order: " . ($report->orderType ? $report->orderType->name : 'N/A') . "\n" .
                           "OrderID: " . $report->order_id . "\n" .
                           "Nomor Layanan: " . $report->nomer_layanan . "\n" .
                           "SN ONT: " . $report->sn_ont . "\n" .
                           "Datek ODP: " . $report->datek_odp . "\n" .
                           "Port ODP: " . $report->port_odp . "\n\n" .
                           "Keterangan:\n" . $report->keterangan . "\n\n" .
                           "----------------------------------------\n" .
                           "Created By: @" . ($report->reporter ? $report->reporter->telegram_username : 'N/A') . "\n" .
                           "Create Order: " . $report->created_at->format('Y-m-d H:i:s') . "\n\n" .
                           "Take by: @" . $user->telegram_username;

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
