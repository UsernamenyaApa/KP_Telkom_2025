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

            $message = "✅ *Laporan Pelurusan Diambil!* ✅\n\n" .
                "*ID Laporan:* `" . ($this->report->id ?? 'N/A') . "`\n" .
                "*Kode Pelurusan:* `" . ($this->report->pelurusan_code ?? 'N/A') . "`\n" .
                "*Tipe Order:* `" . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "`\n" .
                "*OrderID:* `" . ($this->report->order_id ?? 'N/A') . "`\n" .
                "*Nomor Layanan:* `" . ($this->report->nomer_layanan ?? 'N/A') . "`\n" .
                "*SN ONT:* `" . ($this->report->sn_ont ?? 'N/A') . "`\n" .
                "*Datek ODP:* `" . ($this->report->datek_odp ?? 'N/A') . "`\n" .
                "*Port ODP:* `" . ($this->report->port_odp ?? 'N/A') . "`\n\n" .
                "*Diambil Oleh:* @" . ($user->telegram_username ?? 'N/A') . "\n" .
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

            $this->dispatchNotification($newStatus);

            $this->closeStatusModal();
        }
    }

    private function dispatchNotification(FalloutStatus $newStatus): void
    {
        $reporter = $this->report->reporter;
        $assignee = $this->report->assignedToUser;

        $messageLines = [
            '*Status Laporan Pelurusan Diperbarui* ',
            '*Status Baru:* `' . $newStatus->name . '`',
            '---',
            '*ID Laporan:* `' . $this->report->id_harian . '`',
            '*Kode Pelurusan:* `' . $this->report->pelurusan_code . '`',
            '*Tipe Order:* `' . ($this->report->orderType?->name ?? 'N/A') . '`',
            '*OrderID:* `' . $this->report->order_id . '`',
            '*Nomor Layanan:* `' . $this->report->nomer_layanan . '`',
            '*SN ONT:* `' . $this->report->sn_ont . '`',
            '*Datek ODP:* `' . $this->report->datek_odp . '`',
            '*Port ODP:* `' . $this->report->port_odp . '`',
        ];

        if ($this->keterangan) {
            $messageLines[] = '---';
            $messageLines[] = '*Catatan Resolusi:*';
            $messageLines[] = '```';
            $messageLines[] = $this->keterangan;
            $messageLines[] = '```';
        }

        $messageLines[] = '---';
        $messageLines[] = '*Dibuat Oleh:* @' . ($reporter ? $reporter->telegram_username : $this->report->reporter_telegram_username);
        $messageLines[] = '*Dibuat Pada:* `' . $this->report->created_at->format('Y-m-d H:i:s') . '`';
        $messageLines[] = '*Diambil Oleh:* @' . ($assignee ? $assignee->telegram_username : 'N/A');
        $messageLines[] = '*Diambil Pada:* `' . ($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A') . '`';

        if ($this->report->completed_at) {
            $messageLines[] = '*Selesai Pada:* `' . $this->report->completed_at->format('Y-m-d H:i:s') . '`';
            $duration = $this->report->created_at->diffForHumans($this->report->completed_at, true, true, 2);
            $messageLines[] = '*Durasi:* `' . $duration . '`';
        }

        $message = implode("\n", $messageLines);

        // Send to reporter
        if ($reporter && $reporter->telegram_user_id) {
            SendTelegramNotificationJob::dispatch($reporter->telegram_user_id, $message, null, 'MarkdownV2');
        } elseif ($this->report->reporter_telegram_id) {
            SendTelegramNotificationJob::dispatch($this->report->reporter_telegram_id, $message, null, 'MarkdownV2');
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
    }

    public function render()
    {
        return view('livewire.pelurusan-report-detail');
    }
}
