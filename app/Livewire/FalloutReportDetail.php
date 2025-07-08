<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendTelegramNotificationJob;

class FalloutReportDetail extends Component
{
    public FalloutReport $report;
    public $showStatusModal = false;
    public $newStatusId;
    public $keterangan = '';

    public function mount($id)
    {
        $this->report = FalloutReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])->findOrFail($id);
    }

    public function openStatusModal()
    {
        $this->newStatusId = $this->report->fallout_status_id;
        $this->keterangan = $this->report->resolution_notes;
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->reset(['newStatusId', 'keterangan']);
    }

    public function changeStatus()
    {
        if ($this->newStatusId && $this->report->assigned_to_user_id == auth()->id()) {
            $this->report->fallout_status_id = $this->newStatusId;
            $this->report->resolution_notes = $this->keterangan;
            $this->report->save();

            $newStatus = FalloutStatus::find($this->newStatusId);

            $message = "🔔 *Update Status Laporan Fallout* 🔔\n\n" .
                       "*Status Baru: {$newStatus->name}*\n\n" .
                       "Tipe Order: " . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "\n" .
                       "OrderID: " . $this->report->order_id . "\n" .
                       "Nomor Layanan: " . $this->report->nomer_layanan . "\n" .
                       "SN ONT: " . $this->report->sn_ont . "\n" .
                       "Datek ODP: " . $this->report->datek_odp . "\n" .
                       "Port ODP: " . $this->report->port_odp . "\n\n" .
                       "📝 *Catatan Resolusi:*\n" . $this->keterangan . "\n\n" .
                       "----------------------------------------\n" .
                       "Created By: @" . ($this->report->reporter ? $this->report->reporter->telegram_username : 'N/A') . "\n" .
                       "Create Order: " . $this->report->created_at->format('Y-m-d H:i:s') . "\n" .
                       "Updated By: @" . auth()->user()->telegram_username;

            // Send to personal chat (reporter)
            if ($this->report->reporter && $this->report->reporter->telegram_user_id) {
                SendTelegramNotificationJob::dispatch($this->report->reporter->telegram_user_id, $message);
            }

            // Send to group chat
            $groupChatId = env('TELEGRAM_GROUP_ID');
            if ($groupChatId) {
                SendTelegramNotificationJob::dispatch($groupChatId, $message);
            }

            $this->closeStatusModal();
        }
    }

    public function render()
    {
        return view('livewire.fallout-report-detail');
    }
}
