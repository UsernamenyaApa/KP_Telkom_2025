<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\TelegramGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;
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

    private const COMPLETED_STATUSES = ['cancel atau input ulang', 'Done'];

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
            if ($currentStatusName === 'Open') {
                return true;
            }

            if ($currentStatusName === self::ON_PROGRESS) {
                return ! in_array($status->name, ['Open', 'OnProgress']);
            }

            return ! in_array($status->name, ['Open', 'OnProgress']);
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

    public function takeOrder()
    {
        try {
            DB::beginTransaction();

            if ($this->report->assigned_to_user_id) {
                throw new \Exception('Laporan ini sudah diambil.');
            }

            $onProgressStatus = FalloutStatus::where('name', self::ON_PROGRESS)->firstOrFail();

            $this->report->update([
                'fallout_status_id' => $onProgressStatus->id,
                'assigned_to_user_id' => Auth::id(),
                'taken_at' => now(),
            ]);

            DB::commit();

            $user = Auth::user();

            $this->dispatchNotification($onProgressStatus, $user, 'take');

            $this->dispatch('reportAssigned');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: '.$e->getMessage());
        }
    }

    public function changeStatus()
    {
        $this->validate([
            'newStatusId' => 'required|exists:fallout_statuses,id',
            'keterangan' => 'nullable|string|max:1000',
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
                'resolution_notes' => $this->keterangan,
                'completed_at' => in_array($newStatus->name, self::COMPLETED_STATUSES) ? now() : null,
            ]);
            DB::commit();

            $this->dispatchNotification($newStatus, Auth::user(), 'change');

            $this->closeStatusModal();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengubah status laporan fallout: '.$e->getMessage());
            $this->addError('statusError', 'Gagal mengubah status laporan: '.$e->getMessage());
        }
    }

    private function dispatchNotification(FalloutStatus $newStatus, $user, $action = 'change')
    {
        $reporter = $this->report->reporter;
        $assignee = $this->report->assignedToUser;

        // Escape all dynamic data first
        $escapedStatusName = $this->escapeMarkdown($newStatus->name);
        $escapedIncidentTicket = $this->escapeMarkdown($this->report->incident_ticket);
        $escapedOrderType = $this->escapeMarkdown($this->report->orderType?->name ?? 'N/A');
        $escapedOrderId = $this->escapeMarkdown($this->report->order_id);
        $escapedKeterangan = $this->escapeMarkdown($this->keterangan);
        $escapedReporterUsername = $this->escapeMarkdown($reporter ? $reporter->telegram_username : ($this->report->reporter_telegram_username ?? 'N/A'));
        $escapedCreatedAt = $this->escapeMarkdown($this->report->created_at->format('Y-m-d H:i:s'));
        $escapedAssigneeUsername = $this->escapeMarkdown($assignee ? $assignee->telegram_username : 'N/A');
        $escapedTakenAt = $this->escapeMarkdown($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A');

        if ($action === 'take') {
            $title = '✅ *Laporan Fallout Diambil* ✅';
        } else {
            $title = '🔔 *Update Status Laporan Fallout* 🔔';
        }

        $messageLines = [
            $title,
            "*Status Baru:* {$escapedStatusName}",
            '',
            "*Kode Fallout:* `{$escapedIncidentTicket}`",
            "*Tipe Order:* {$escapedOrderType}",
            "*OrderID:* `{$escapedOrderId}`",
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
            $messageLines[] = '';
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

        if ($user && $user->telegram_user_id) {
            $recipients[] = $user->telegram_user_id;
        }

        // Send to unique recipients
        foreach (array_unique($recipients) as $recipient) {
            SendTelegramNotificationJob::dispatch($recipient, $message, null, 'MarkdownV2');
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

        return str_replace($chars, array_map(fn ($char) => '\\'.$char, $chars), $text);
    }
}
