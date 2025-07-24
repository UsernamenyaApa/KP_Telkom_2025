<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

class PelurusanReportDetail extends Component
{
    #[Url]
    public $date;

    public PelurusanReport $report;

    public $showStatusModal = false;

    public $newStatusId;

    public $keterangan = '';

    public function mount($id, $date = null)
    {
        $this->report = PelurusanReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])->findOrFail($id);
        if ($date) {
            $this->date = $date;
        }
    }

    public function takeOrder()
    {
        $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
        if ($onProgressStatus) {
            $this->report->fallout_status_id = $onProgressStatus->id;
            $this->report->assigned_to_user_id = Auth::id();
            if (is_null($this->report->assigned_at)) {
                $this->report->assigned_at = now();
            }
            $this->report->taken_at = now();
            $this->report->save();

            $user = Auth::user();

            $message = '✅ *Laporan Pelurusan Diambil!* ✅

" .
                       "*ID Laporan:* `" . ($this->report->id ?? 'N/A') . "`
" .
                       "*Kode Pelurusan:* `" . ($this->report->pelurusan_code ?? 'N/A') . "`
" .
                       "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`
" .
                       "*OrderID:* `" . ($this->report->order_id ?? 'N/A') . "`
" .
                       "*Nomor Layanan:* `" . ($this->report->nomer_layanan ?? 'N/A') . "`
" .
                       "*SN ONT:* `" . ($this->report->sn_ont ?? 'N/A') . "`
" .
                       "*Datek ODP:* `" . ($this->report->datek_odp ?? 'N/A') . "`
" .
                       "*Port ODP:* `" . ($this->report->port_odp ?? 'N/A') . "`

" .
                       "*Diambil Oleh:* @" . ($user->telegram_username ?? 'N/A') . "
" .
                       "*Waktu Diambil:* " . ($this->report->assigned_at ? $this->report->assigned_at->format('Y-m-d H:i:s') : 'N/A');

            if ($user->telegram_user_id) {
                $takerMessage = "✅ Anda telah berhasil mengambil laporan pelurusan dengan ID #{$this->report->id} (`{$this->report->pelurusan_code}`). Mohon segera ditindaklanjuti.";
                SendTelegramNotificationJob::dispatch($user->telegram_user_id, $takerMessage);
            }

            if ($this->report->reporter_user_id) {
                $reporterChatId = $this->report->reporter->telegram_user_id;
            } else {
                $reporterChatId = $this->report->reporter_telegram_id;
            }

            if ($reporterChatId) {
                SendTelegramNotificationJob::dispatch($reporterChatId, $message);
            }

            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
            }
        }
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

            $newStatus = FalloutStatus::find($this->newStatusId);
            if ($newStatus && in_array($newStatus->name, ['FA', 'eskalasi', 'input ulang', 'PI'])) {
                $this->report->completed_at = now();
            }

            $this->report->save();

            $message = '

" .
                       "*Status Baru: {$newStatus->name}*\n\n" .
                       "Tipe Order: " . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "\n" .
                       "OrderID: " . $this->report->order_id . "\n" .
                       "Nomor Layanan: " . $this->report->nomer_layanan . "\n" .
                       "SN ONT: " . $this->report->sn_ont . "\n" .
                       "Datek ODP: " . $this->report->datek_odp . "\n" .
                       "Port ODP: " . $this->report->port_odp . "\n\n" .
                       "
" . $this->keterangan . "\n\n" .
                       "----------------------------------------\n" .
                       "Created By: @" . ($this->report->reporter_user_id ? $this->report->reporter->telegram_username : $this->report->reporter_telegram_username) . "\n" .
                       "Create Order: " . $this->report->created_at->format('Y-m-d H:i:s') . "\n" .
                       "Taken at: " . ($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A') . "\n" .
                       "Updated By: @" . auth()->user()->telegram_username;

            // Add completed_at and duration if available
            if ($this->report->completed_at) {
                $message .= "\n\n".
                            '
'.$this->report->completed_at->format('Y-m-d H:i:s')."\n";

                if ($this->report->created_at) {
                    $duration = $this->report->created_at->diffForHumans($this->report->completed_at, true, true, 2);
                    $message .= '
'.$duration."\n";
                }
            }

            // Send to personal chat (reporter)
            if ($this->report->reporter_user_id) {
                $reporterChatId = $this->report->reporter->telegram_user_id;
            } else {
                $reporterChatId = $this->report->reporter_telegram_id;
            }

            if ($reporterChatId) {
                SendTelegramNotificationJob::dispatch($reporterChatId, $message);
            }

            // Send to group chat
            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
            }

            // Send to the user who changed the status
            if (auth()->user()->telegram_user_id) {
                SendTelegramNotificationJob::dispatch(auth()->user()->telegram_user_id, $message, null, 'MarkdownV2');
            }

            $this->closeStatusModal();
        }
    }

    public function render()
    {
        return view('livewire.pelurusan-report-detail');
    }
}
