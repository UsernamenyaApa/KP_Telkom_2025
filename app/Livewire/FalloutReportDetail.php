<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Livewire\Component;

use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendTelegramNotificationJob;

class FalloutReportDetail extends Component
{
    

    #[Url]
    public $date;
    public FalloutReport $report;
    public $showStatusModal = false;
    public $newStatusId;
    public $keterangan = '';
    public $availableStatuses = [];

    public function mount($id, $date = null)
    {
        $this->report = FalloutReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])->findOrFail($id);
        if ($date) {
            $this->date = $date;
        }
    }

    public function openStatusModal()
    {
        $allStatuses = FalloutStatus::all();
        $currentStatusName = $this->report->falloutStatus?->name;

        $this->availableStatuses = $allStatuses->filter(function ($status) use ($currentStatusName) {
            if ($currentStatusName === 'Open') {
                return true; // All statuses available from Open
            }
            // For any other status, exclude Open and OnProgress
            return !in_array($status->name, ['Open', 'OnProgress']);
        });

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

            

            $newStatus = FalloutStatus::find($this->newStatusId);
            if ($newStatus && in_array($newStatus->name, ['FA', 'eskalasi', 'input ulang', 'PI'])) {
                $this->report->completed_at = now();
            }

            $this->report->save();

            $esc = fn(?string $text) => str_replace(
                ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
                ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
                $text ?? '-'
            );

            $message = "🔔 *Update Status Laporan Fallout* 🔔\n\n" .
                       "*Status Baru:* " . $esc($newStatus->name) . "\n\n" .
                       "*Tipe Order:* " . $esc($this->report->orderType ? $this->report->orderType->name : 'N/A') . "\n" .
                       "*OrderID:* `" . $esc($this->report->order_id) . "`\n" .
                       "*Nomor Layanan:* `" . $esc($this->report->nomer_layanan) . "`\n" .
                       "*SN ONT:* `" . $esc($this->report->sn_ont) . "`\n" .
                       "*Datek ODP:* `" . $esc($this->report->datek_odp) . "`\n" .
                       "*Port ODP:* `" . $esc($this->report->port_odp) . "`\n\n" .
                       "📝 *Catatan Resolusi:*\n" . $esc($this->keterangan) . "\n\n" .
                       "----------------------------------------\n" .
                       "*Created By:* @" . $esc($this->report->reporter ? $this->report->reporter->telegram_username : 'N/A') . "\n" .
                       "*Create Order:* " . $esc($this->report->created_at->format('Y-m-d H:i:s')) . "\n" .
                       "*Taken at:* " . $esc($this->report->assigned_at ? $this->report->assigned_at->format('Y-m-d H:i:s') : 'N/A') . "\n" .
                       "*Updated By:* @" . $esc(auth()->user()->telegram_username);

            // Add completed_at and duration if available
            if ($this->report->completed_at) {
                $message .= "\n\n" .
                            "✅ *Selesai pada:* " . $esc($this->report->completed_at->format('Y-m-d H:i:s')) . "\n";

                if ($this->report->created_at) {
                    $duration = $this->report->created_at->diffForHumans($this->report->completed_at, true, true, 2);
                    $message .= "⏳ *Durasi:* " . $esc($duration) . "\n";
                }
            }

            // Send to personal chat (reporter)
            if ($this->report->reporter && $this->report->reporter->telegram_user_id) {
                SendTelegramNotificationJob::dispatch($this->report->reporter->telegram_user_id, $message, null, 'MarkdownV2');
            }

            // Send to group chat
            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                $groupChatId = $groupChat->chat_id;
            }

            $this->closeStatusModal();
        }
    }

    public function takeOrder()
    {
        if ($this->report->falloutStatus?->name === 'Open') {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $this->report->fallout_status_id = $onProgressStatus->id;
                $this->report->assigned_to_user_id = Auth::id();
                $this->report->taken_at = now();
                $this->report->save();

                // Send Telegram notification
                $message = "

" .
                           "*ID Laporan:* `" . $this->report->id . "`
" .
                           "*Kode Fallout:* `" . $this->report->fallout_code . "`
" .
                           "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`
" .
                           "*OrderID:* `" . $this->report->order_id . "`
" .
                           "*Diambil Oleh:* @" . auth()->user()->telegram_username . "
" .
                           "*Waktu Diambil:* " . $this->report->taken_at->format('Y-m-d H:i:s') . "

" .
                           "Mohon segera ditindaklanjuti.";

                $groupChat = \App\Models\TelegramGroup::first();
                if ($groupChat) {
                    SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
                }

                // Send to personal chat (reporter)
                if ($this->report->reporter && $this->report->reporter->telegram_user_id) {
                    $personalMessage = "🔔 Laporan Anda dengan ID #{$this->report->id} telah diambil oleh @" . auth()->user()->telegram_username . " pada " . $this->report->taken_at->format('Y-m-d H:i:s') . ".";
                    SendTelegramNotificationJob::dispatch($this->report->reporter->telegram_user_id, $personalMessage);
                }

                // Refresh the component to reflect changes
                $this->report = $this->report->fresh(['orderType', 'falloutStatus', 'reporter', 'assignedToUser']);
            }
        }
    }

    public function render()
    {
        return view('livewire.fallout-report-detail');
    }
}
