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
        $this->validate([
            'newStatusId' => 'required|exists:fallout_statuses,id',
            'keterangan'  => 'nullable|string|max:1000',
        ]);

        if ($this->report->assigned_to_user_id != Auth::id()) {
            $this->addError('auth', 'Anda tidak ditugaskan untuk laporan ini.');
            return;
        }

        try {
            DB::beginTransaction();
            $newStatus = FalloutStatus::findOrFail($this->newStatusId);
            $this->report->update([
                'fallout_status_id' => $this->newStatusId,
                'resolution_notes'  => $this->keterangan,
                'completed_at'      => in_array($newStatus->name, self::COMPLETED_STATUSES) ? now() : null,
            ]);
            DB::commit();

            $this->dispatchStatusUpdateNotification($newStatus);

            $this->closeStatusModal();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('statusError', 'Gagal mengubah status laporan: ' . $e->getMessage());
        }
    }

    private function dispatchStatusUpdateNotification(FalloutStatus $newStatus): void
    {
        $reporter = $this->report->reporter;
        $assignee = $this->report->assignedToUser;

        // Escape all dynamic data first
        $escapedStatusName = $this->escapeMarkdown($newStatus->name);
        $escapedIncidentTicket = $this->escapeMarkdown($this->report->incident_ticket);
        $escapedOrderType = $this->escapeMarkdown($this->report->orderType?->name ?? 'N/A');
        $escapedOrderId = $this->escapeMarkdown($this->report->order_id);
        $escapedNomerLayanan = $this->escapeMarkdown($this->report->nomer_layanan);
        $escapedKeterangan = $this->escapeMarkdown($this->keterangan);
        $escapedReporterUsername = $this->escapeMarkdown($reporter ? $reporter->telegram_username : ($this->report->reporter_telegram_username ?? 'N/A'));
        $escapedCreatedAt = $this->escapeMarkdown($this->report->created_at->format('Y-m-d H:i:s'));
        $escapedAssigneeUsername = $this->escapeMarkdown($assignee ? $assignee->telegram_username : 'N/A');
        $escapedTakenAt = $this->escapeMarkdown($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A');

        $messageLines = [
            '🔔 *Update Status Laporan Fallout* 🔔',
            "*Status Baru:* {$escapedStatusName}",
            '',
            "*Kode Fallout:* `{$escapedIncidentTicket}`",
            "*Tipe Order:* {$escapedOrderType}",
            "*OrderID:* `{$escapedOrderId}`",
            "*Nomor Layanan:* `{$escapedNomerLayanan}`",
        ];

        if ($this->keterangan) {
            $messageLines[] = '';
            $messageLines[] = '📝 *Catatan Resolusi:*';
            $messageLines[] = '```';
            $messageLines[] = $escapedKeterangan;
            $messageLines[] = '```';
        }

        $messageLines[] = '';
        $messageLines[] = "*Dibuat Oleh:* @{$escapedReporterUsername}";
        $messageLines[] = "*Dibuat Pada:* `{$escapedCreatedAt}`";
        $messageLines[] = "*Diambil Oleh:* @{$escapedAssigneeUsername}";
        $messageLines[] = "*Diambil Pada:* `{$escapedTakenAt}`";

        if ($this->report->completed_at) {
            $escapedCompletedAt = $this->escapeMarkdown($this->report->completed_at->format('Y-m-d H:i:s'));
            $duration = $this->report->created_at->diffForHumans($this->report->completed_at, true, true, 2);
            $escapedDuration = $this->escapeMarkdown($duration);
            $messageLines[] = "";
            $messageLines[] = "✅ *Selesai pada:* `{$escapedCompletedAt}`";
            $messageLines[] = "⏳ *Durasi:* `{$escapedDuration}`";
        }

        $message = implode("\n", $messageLines);

        // Define recipients
        $recipients = [];
        if ($reporter && $reporter->telegram_user_id) {
            $recipients[] = $reporter->telegram_user_id;
        } elseif ($this->report->reporter_telegram_id) {
            $recipients[] = $this->report->reporter_telegram_id;
        }

        $groupChat = TelegramGroup::first();
        if ($groupChat) {
            $recipients[] = $groupChat->chat_id;
        }

        $currentUser = auth()->user();
        if ($currentUser->telegram_user_id) {
            // Add the current user only if they are not the reporter
            if (!$reporter || $reporter->telegram_user_id != $currentUser->telegram_user_id) {
                $recipients[] = $currentUser->telegram_user_id;
            }
        }

        // Send to unique recipients
        foreach (array_unique($recipients) as $recipient) {
            SendTelegramNotificationJob::dispatch($recipient, $message, null, 'MarkdownV2');
        }
    }

    public function takeOrder()
    {
        if ($this->report->falloutStatus?->name !== 'Open') {
            return; // Or display an error to the user
        }

        try {
            DB::beginTransaction();

            $onProgressStatus = FalloutStatus::where('name', self::ON_PROGRESS)->firstOrFail();

            $this->report->update([
                'fallout_status_id'     => $onProgressStatus->id,
                'assigned_to_user_id'   => Auth::id(),
                'taken_at'              => now(),
            ]);

            DB::commit();

            $user = Auth::user();

            // Escape all dynamic data first
            $escapedIdHarian = $this->escapeMarkdown($this->report->id_harian ?? 'N/A');
            $escapedIncidentTicket = $this->escapeMarkdown($this->report->incident_ticket ?? 'N/A');
            $escapedOrderType = $this->escapeMarkdown($this->report->orderType ? $this->report->orderType->name : 'N/A');
            $escapedOrderId = $this->escapeMarkdown($this->report->order_id ?? 'N/A');
            $escapedNomerLayanan = $this->escapeMarkdown($this->report->nomer_layanan ?? 'N/A');
            $escapedUsername = $this->escapeMarkdown($user->telegram_username ?? 'N/A');
            $escapedTakenAt = $this->escapeMarkdown($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A');

            // Message for the group chat and reporter
            $groupMessage = "✅ *Laporan Fallout Diambil\!* ✅\n\n" .
                "*Antrian:* `{$escapedIdHarian}`\n" .
                "*Kode Fallout:* `{$escapedIncidentTicket}`\n" .
                "*Tipe Order:* `{$escapedOrderType}`\n" .
                "*OrderID:* `{$escapedOrderId}`\n" .
                "*Nomor Layanan:* `{$escapedNomerLayanan}`\n\n" .
                "*Diambil Oleh:* @{$escapedUsername}\n" .
                "*Waktu Diambil:* `{$escapedTakenAt}`";

            // Personal message for the user who took the order
            $takerMessage = "✅ Anda telah berhasil mengambil laporan Fallout\.\n\n" .
                            "*Berikut detail laporan:*\n\n" .
                            "*Antrian:* `{$escapedIdHarian}`\n" .
                            "*Kode Fallout:* `{$escapedIncidentTicket}`\n" .
                            "*Tipe Order:* `{$escapedOrderType}`\n" .
                            "*OrderID:* `{$escapedOrderId}`\n" .
                            "*Nomor Layanan:* `{$escapedNomerLayanan}`";

            // Define recipients for the group message
            $recipients = [];
            if ($this->report->reporter_user_id && $this->report->reporter->telegram_user_id) {
                $recipients[] = $this->report->reporter->telegram_user_id;
            } elseif ($this->report->reporter_telegram_id) {
                $recipients[] = $this->report->reporter_telegram_id;
            }

            $groupChat = TelegramGroup::first();
            if ($groupChat?->chat_id) {
                $recipients[] = $groupChat->chat_id;
            }

            // Send notifications to group and reporter
            foreach (array_unique($recipients) as $recipient) {
                SendTelegramNotificationJob::dispatch($recipient, $groupMessage, null, 'MarkdownV2');
            }

            // Always send a personal notification to the user who took the action
            if ($user->telegram_user_id) {
                // Ensure the user doesn't get the group message twice if they are also the reporter
                if (!in_array($user->telegram_user_id, $recipients)) {
                    SendTelegramNotificationJob::dispatch($user->telegram_user_id, $takerMessage, null, 'MarkdownV2');
                } else {
                    // If they are the reporter, they already got the main message.
                    // We can send a simplified personal confirmation.
                    $personalConfirmation = "✅ Anda telah berhasil mengambil laporan Fallout ini\.";
                    SendTelegramNotificationJob::dispatch($user->telegram_user_id, $personalConfirmation, null, 'MarkdownV2');
                }
            }

            $this->dispatch('reportAssigned');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: ' . $e->getMessage());
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