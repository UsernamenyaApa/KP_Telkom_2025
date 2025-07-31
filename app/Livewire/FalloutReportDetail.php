<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\TelegramGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class FalloutReportDetail extends Component
{
    #[Url]
    public $date;

    public FalloutReport $report;
    public $showStatusModal = false;
    public $newStatusId;
    public $keterangan = '';
    public $availableStatuses = [];

    private const ON_PROGRESS = 'OnProgress';
    private const COMPLETED_STATUSES = ['FA', 'input ulang', 'PI'];

    public function mount($id, $date = null)
    {
        $this->report = FalloutReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])->findOrFail($id);
        $this->date = $date ?? $this->date;
    }

    

    public function openStatusModal()
    {
        $allStatuses = FalloutStatus::all();
        $currentStatusName = $this->report->falloutStatus?->name;

        $this->availableStatuses = $allStatuses->filter(function ($status) use ($currentStatusName) {
            if ($currentStatusName === 'Open') return true;
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

            $esc = fn (?string $text) => str_replace(
                ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '=', '|', '{', '}', '!'],
                ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\=', '\|', '\{', '\}', '\!'],
                $text ?? '-'
            );

            $message = "🔔 *Update Status Laporan Fallout* 🔔\n\n" .
                       "*Status Baru:* " . $esc($newStatus->name) . "\n\n" .
                       "*Kode Fallout:* `" . ($this->report->incident_ticket) . "`\n" .
                       "*Tipe Order:* " . $esc($this->report->orderType ? $this->report->orderType->name : 'N/A') . "\n" .
                       "*OrderID:* `" . ($this->report->order_id) . "`\n" .
                       "*Nomor Layanan:* `" . ($this->report->nomer_layanan) . "`\n\n" .
                       "📝 *Catatan Resolusi:*\n" . $esc($this->keterangan) . "\n\n" .
                       "----------------------------------------\n" .
                       "*Created By:* @" . $esc($this->report->reporter_user_id ? $this->report->reporter->telegram_username : $this->report->reporter_telegram_username) . "\n" .
                       "*Create Order:* " . $esc($this->report->created_at->format('Y-m-d H:i:s')) . "\n" .
                       "*Taken at:* " . $esc($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A') . "\n" .
                       "*Updated By:* @" . $esc(auth()->user()->telegram_username);

            // Add completed_at and duration if available
            if ($this->report->completed_at) {
                $message .= "\n\n".
                            '✅ *Selesai pada:* '.$esc($this->report->completed_at->format('Y-m-d H:i:s'))."\n";

                if ($this->report->taken_at) {
                    $duration = $this->report->taken_at->diffForHumans($this->report->completed_at, true, true, 2);
                    $message .= '⏳ *Durasi:* '.$esc($duration)."\n";
                }
            }

            // Send to personal chat (reporter)
            if ($this->report->reporter_user_id) {
                $reporterChatId = $this->report->reporter->telegram_user_id;
            } else {
                $reporterChatId = $this->report->reporter_telegram_id;
            }

            if ($reporterChatId) {
                SendTelegramNotificationJob::dispatch($reporterChatId, $message, null, 'MarkdownV2');
            }

            // Send to group chat
            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message, null, 'MarkdownV2');
            }

            // Send to the user who changed the status
            if (auth()->user()->telegram_user_id) {
                SendTelegramNotificationJob::dispatch(auth()->user()->telegram_user_id, $message, null, 'MarkdownV2');
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
                $message = "✅ *Laporan Fallout Diambil!* ✅

" .
                           "*Antrian:* `" . $this->report->id_harian . "`
" .
                           "*Kode Fallout:* `" . $this->report->incident_ticket . "`
" .
                           "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`
" .
                           "*OrderID:* `" . $this->report->order_id . "`
" .
                           "*Nomor Layanan:* `" . $this->report->nomer_layanan . "`

" .
                           "*Diambil Oleh:* @" . auth()->user()->telegram_username . "
" .
                           "*Waktu Diambil:* " . $this->report->taken_at->format('Y-m-d H:i:s');

                $groupChat = \App\Models\TelegramGroup::first();
                if ($groupChat) {
                    SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
                }

                // Send to personal chat (reporter)
                if ($this->report->reporter_user_id) {
                    $reporterChatId = $this->report->reporter->telegram_user_id;
                } else {
                    $reporterChatId = $this->report->reporter_telegram_id;
                }

                if ($reporterChatId) {
                    $personalMessage = "✅ Laporan Fallout Diambil! ✅

" .
                                       "*Antrian:* `" . $this->report->id_harian . "`
" .
                                       "*Kode Fallout:* `" . $this->report->incident_ticket . "`
" .
                                       "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`
" .
                                       "*OrderID:* `" . $this->report->order_id . "`
" .
                                       "*Nomor Layanan:* `" . $this->report->nomer_layanan . "`

" .
                                       "*Diambil Oleh:* @" . auth()->user()->telegram_username . "
" .
                                       "*Waktu Diambil:* " . $this->report->taken_at->format('Y-m-d H:i:s');
                    SendTelegramNotificationJob::dispatch($reporterChatId, $personalMessage, null, 'MarkdownV2');
                }

                // Send to the user who took the order
                if (auth()->user()->telegram_user_id) {
                    $takerMessage = "✅ Anda telah berhasil mengambil laporan.✅

" .
                                    "Berikut detail laporan:

" .
                                    "*Antrian:* `" . $this->report->id_harian . "`
" .
                                    "*Kode Fallout:* `" . $this->report->incident_ticket . "`
" .
                                    "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`
" .
                                    "*OrderID:* `" . $this->report->order_id . "`
" .
                                    "*Nomor Layanan:* `" . $this->report->nomer_layanan . "`

" .
                                    "*Diambil Oleh:* @" . auth()->user()->telegram_username . "
" .
                                    "*Waktu Diambil:* " . $this->report->taken_at->format('Y-m-d H:i:s');
                    SendTelegramNotificationJob::dispatch(auth()->user()->telegram_user_id, $takerMessage, null, 'MarkdownV2');
                }

                // Refresh the component to reflect changes
                $this->report = $this->report->fresh(['orderType', 'falloutStatus', 'reporter', 'assignedToUser']);
                $this->dispatch('reportAssigned');
            }
        }

    }

    public function render()
    {
        return view('livewire.fallout-report-detail');
    }

    private function escapeMarkdown($text): string
    {
        if (is_null($text)) {
            return 'N/A';
        }
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }
}