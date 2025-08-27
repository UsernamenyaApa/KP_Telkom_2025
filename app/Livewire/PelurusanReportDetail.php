<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use App\Models\TelegramGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public $availableStatuses = [];

    private const ON_PROGRESS = 'OnProgress';

    private const COMPLETED_STATUSES = ['Done'];

    public function mount($id)
    {
        $this->report = PelurusanReport::with([
            'orderType',
            'falloutStatus',
            'reporter',
            'assignedToUser',
        ])->findOrFail($id);

        \Illuminate\Support\Facades\Log::debug('PelurusanDetail Date: '.($this->date ?? 'null'));
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
                'assigned_at' => $this->report->assigned_at ?? now(),
                'taken_at' => now(),
            ]);

            DB::commit();

            $user = Auth::user();

            // Escape all dynamic data first
            $escapedIdHarian = $this->escapeMarkdown($this->report->id_harian ?? 'N/A');
            $escapedPelurusanCode = $this->escapeMarkdown($this->report->pelurusan_code ?? 'N/A');
            $escapedOrderType = $this->escapeMarkdown($this->report->orderType ? $this->report->orderType->name : 'N/A');
            $escapedOrderId = $this->escapeMarkdown($this->report->order_id ?? 'N/A');
            $escapedNomerLayanan = $this->escapeMarkdown($this->report->nomer_layanan ?? 'N/A');
            $escapedSnOnt = $this->escapeMarkdown($this->report->sn_ont ?? 'N/A');
            $escapedDatekOdp = $this->escapeMarkdown($this->report->datek_odp ?? 'N/A');
            $escapedPortOdp = $this->escapeMarkdown($this->report->port_odp ?? 'N/A');
            $escapedUsername = $this->escapeMarkdown($user->telegram_username ?? 'N/A');
            $escapedAssignedAt = $this->escapeMarkdown($this->report->assigned_at ? $this->report->assigned_at->format('Y-m-d H:i:s') : 'N/A');

            // Message for the group chat
            $groupMessage = "✅ Laporan Pelurusan Diambil\! ✅\n\n".
                "*ID Laporan:* `{$escapedIdHarian}`\n".
                "*Kode Pelurusan:* `{$escapedPelurusanCode}`\n".
                "*Tipe Order:* `{$escapedOrderType}`\n".
                "*OrderID:* `{$escapedOrderId}`\n".
                "*Nomor Layanan:* `{$escapedNomerLayanan}`\n".
                "*SN ONT:* `{$escapedSnOnt}`\n".
                "*Datek ODP:* `{$escapedDatekOdp}`\n".
                "*Port ODP:* `{$escapedPortOdp}`\n\n".
                "*Diambil Oleh:* @{$escapedUsername}\n".
                "*Waktu Diambil:* `{$escapedAssignedAt}`";

            // Personal message for the user who took the order
            $takerMessage = "✅ Anda telah berhasil mengambil laporan pelurusan dengan ID \#{$escapedIdHarian} (`{$escapedPelurusanCode}`)\. Mohon segera ditindaklanjuti\.\n\n".
                            "*Berikut detail laporan:*\n\n".
                            "*ID Laporan:* `{$escapedIdHarian}`\n".
                            "*Kode Pelurusan:* `{$escapedPelurusanCode}`\n".
                            "*Tipe Order:* `{$escapedOrderType}`\n".
                            "*OrderID:* `{$escapedOrderId}`\n".
                            "*Nomor Layanan:* `{$escapedNomerLayanan}`\n".
                            "*SN ONT:* `{$escapedSnOnt}`\n".
                            "*Datek ODP:* `{$escapedDatekOdp}`\n".
                            "*Port ODP:* `{$escapedPortOdp}`";

            // Define recipients
            $recipients = [];
            if ($this->report->reporter_user_id && $this->report->reporter->telegram_user_id) {
                $recipients[] = $this->report->reporter->telegram_user_id;
            } elseif ($this->report->reporter_telegram_id) {
                $recipients[] = $this->report->reporter_telegram_id;
            }

            $groupChat = Cache::remember('telegram_group', now()->addHour(), fn () => TelegramGroup::first());
            if ($groupChat?->chat_id) {
                $recipients[] = $groupChat->chat_id;
            }

            // Send notifications
            foreach (array_unique($recipients) as $recipient) {
                SendTelegramNotificationJob::dispatch($recipient, $groupMessage, null, 'MarkdownV2');
            }

            // Always send a personal notification to the user who took the action
            if ($user->telegram_user_id) {
                SendTelegramNotificationJob::dispatch($user->telegram_user_id, $takerMessage, null, 'MarkdownV2');
            }

            $this->dispatch('reportAssigned');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: '.$e->getMessage());
        }
    }

    public function openStatusModal()
    {
        $allStatuses = FalloutStatus::all();
        $currentStatusName = $this->report->falloutStatus?->name;

        $this->availableStatuses = $allStatuses->filter(function ($status) {
            // Allow all statuses except Open and OnProgress to be manually selected.
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

            $this->dispatchNotification($newStatus);

            $this->closeStatusModal();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal mengubah status laporan pelurusan: '.$e->getMessage());
            $this->addError('statusError', 'Gagal mengubah status laporan: '.$e->getMessage());
        }
    }

    private function dispatchNotification(FalloutStatus $newStatus): void
    {
        $reporter = $this->report->reporter;
        $assignee = $this->report->assignedToUser;

        // Escape all dynamic data first
        $escapedStatusName = $this->escapeMarkdown($newStatus->name);
        $escapedIdHarian = $this->escapeMarkdown($this->report->id_harian);
        $escapedPelurusanCode = $this->escapeMarkdown($this->report->pelurusan_code);
        $escapedOrderType = $this->escapeMarkdown($this->report->orderType?->name ?? 'N/A');
        $escapedOrderId = $this->escapeMarkdown($this->report->order_id);
        $escapedNomerLayanan = $this->escapeMarkdown($this->report->nomer_layanan);
        $escapedSnOnt = $this->escapeMarkdown($this->report->sn_ont);
        $escapedDatekOdp = $this->escapeMarkdown($this->report->datek_odp);
        $escapedPortOdp = $this->escapeMarkdown($this->report->port_odp);
        $escapedReporterUsername = $this->escapeMarkdown($reporter ? $reporter->telegram_username : ($this->report->reporter_telegram_username ?? 'N/A'));
        $escapedCreatedAt = $this->escapeMarkdown($this->report->created_at->format('Y-m-d H:i:s'));
        $escapedAssigneeUsername = $this->escapeMarkdown($assignee ? $assignee->telegram_username : 'N/A');
        $escapedTakenAt = $this->escapeMarkdown($this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A');

        $messageLines = [
            '*Status Laporan Pelurusan Diperbarui* ',
            "*Status Baru:* `{$escapedStatusName}`",
            '',
            "*ID Laporan:* `{$escapedIdHarian}`",
            "*Kode Pelurusan:* `{$escapedPelurusanCode}`",
            "*Tipe Order:* `{$escapedOrderType}`",
            "*OrderID:* `{$escapedOrderId}`",
            "*Nomor Layanan:* `{$escapedNomerLayanan}`",
            "*SN ONT:* `{$escapedSnOnt}`",
            "*Datek ODP:* `{$escapedDatekOdp}`",
            "*Port ODP:* `{$escapedPortOdp}`",
        ];

        if ($this->keterangan) {
            $messageLines[] = '';
            $messageLines[] = '*Catatan Resolusi:*';
            $messageLines[] = '```';
            $messageLines[] = $this->escapeMarkdown($this->keterangan);
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
            $messageLines[] = "*Selesai Pada:* `{$escapedCompletedAt}`";
            $messageLines[] = "*Durasi:* `{$escapedDuration}`";
        }

        $message = implode("\n", $messageLines);

        // Define recipients
        $recipients = [];
        if ($reporter && $reporter->telegram_user_id) {
            $recipients[] = $reporter->telegram_user_id;
        } elseif ($this->report->reporter_telegram_id) {
            $recipients[] = $this->report->reporter_telegram_id;
        }

        $groupChat = \App\Models\TelegramGroup::first();
        if ($groupChat) {
            $recipients[] = $groupChat->chat_id;
        }

        $currentUser = auth()->user();
        if ($currentUser->telegram_user_id) {
            $recipients[] = $currentUser->telegram_user_id;
        }

        // Send to unique recipients
        foreach (array_unique($recipients) as $recipient) {
            SendTelegramNotificationJob::dispatch($recipient, $message, null, 'MarkdownV2');
        }
    }

    public function render()
    {
        return view('livewire.pelurusan-report-detail');
    }

    private function sendTelegramNotifications($message)
    {
        $reporterChatId = $this->report->reporter_user_id
            ? $this->report->reporter->telegram_user_id
            : $this->report->reporter_telegram_id;

        if ($reporterChatId) {
            SendTelegramNotificationJob::dispatch($reporterChatId, $message, null, 'MarkdownV2');
        }

        $groupChat = Cache::remember('telegram_group', now()->addHour(), fn () => TelegramGroup::first());
        if ($groupChat?->chat_id) {
            SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message, null, 'MarkdownV2');
        }
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
