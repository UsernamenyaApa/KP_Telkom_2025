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
    private const COMPLETED_STATUSES = ['FA', 'input ulang', 'PI'];

    public function mount($id, $date = null)
    {
        $this->report = PelurusanReport::with([
            'orderType',
            'falloutStatus',
            'reporter',
            'assignedToUser'
        ])->findOrFail($id);

        $this->date = $date ?? $this->date;
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
                'fallout_status_id'     => $onProgressStatus->id,
                'assigned_to_user_id'   => Auth::id(),
                'assigned_at'           => $this->report->assigned_at ?? now(),
                'taken_at'              => now(),
            ]);

            DB::commit();

            $user = Auth::user();

            $message = "✅ *Laporan Pelurusan Diambil!* ✅\n\n" .
                "*ID Laporan:* `" . $this->escapeMarkdown($this->report->id_harian) . "`\n" .
                "*Kode Pelurusan:* `" . $this->escapeMarkdown($this->report->pelurusan_code) . "`\n" .
                "*Tipe Order:* `" . $this->escapeMarkdown($this->report->orderType?->name) . "`\n" .
                "*OrderID:* `" . $this->escapeMarkdown($this->report->order_id) . "`\n" .
                "*Nomor Layanan:* `" . $this->escapeMarkdown($this->report->nomer_layanan) . "`\n" .
                "*SN ONT:* `" . $this->escapeMarkdown($this->report->sn_ont) . "`\n" .
                "*Datek ODP:* `" . $this->escapeMarkdown($this->report->datek_odp) . "`\n" .
                "*Port ODP:* `" . $this->escapeMarkdown($this->report->port_odp) . "`\n\n" .
                "*Diambil Oleh:* @" . $this->escapeMarkdown($user->telegram_username) . "\n" .
                "*Waktu Diambil:* `" . $this->escapeMarkdown(now()->format('Y-m-d H:i:s')) . "`";

            // Kirim notifikasi ke semua channel yang relevan
            $this->dispatchNotificationsOnTake($message);
            $this->dispatch('reportAssigned');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: ' . $e->getMessage());
        }
    }

    public function openStatusModal()
    {
        $allStatuses = FalloutStatus::all();
        $currentStatusName = $this->report->falloutStatus?->name;

        $this->availableStatuses = $allStatuses->filter(function ($status) use ($currentStatusName) {
            if ($currentStatusName === 'Open') {
                return true;
            }
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

            $this->dispatchNotificationOnStatusChange($newStatus);
            $this->closeStatusModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.pelurusan-report-detail');
    }

    private function dispatchNotificationsOnTake(string $message): void
    {
        try {
            $user = Auth::user();
            $recipients = [];

            // 1. Add Group Chat
            $groupChat = Cache::remember('telegram_group', now()->addHour(), fn () => TelegramGroup::first());
            if ($groupChat?->chat_id) {
                $recipients[] = $groupChat->chat_id;
            }

            // 2. Add Reporter
            $reporterChatId = $this->report->reporter_user_id
                ? $this->report->reporter->telegram_user_id
                : $this->report->reporter_telegram_id;
            if ($reporterChatId) {
                $recipients[] = $reporterChatId;
            }

            $uniqueRecipients = array_unique($recipients);
            
            Log::info('Dispatching take order notifications to: ' . implode(', ', $uniqueRecipients));

            foreach ($uniqueRecipients as $chatId) {
                if ($chatId) {
                    SendTelegramNotificationJob::dispatch($chatId, $message, null, 'MarkdownV2');
                }
            }

            // 3. Notifikasi ke Pengambil Laporan (personal message)
            if ($user?->telegram_user_id) {
                $personalMessage = "✅ Anda telah berhasil mengambil laporan pelurusan berikut:\n\n" . $message;
                SendTelegramNotificationJob::dispatch($user->telegram_user_id, $personalMessage, null, 'MarkdownV2');
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch take order notification: ' . $e->getMessage());
            $this->addError('notification_error', 'Gagal mengirim notifikasi: ' . $e->getMessage());
        }
    }

    private function dispatchNotificationOnStatusChange(FalloutStatus $newStatus): void
    {
        try {
            $reporter = $this->report->reporter;
            $assignee = $this->report->assignedToUser;

            $messageLines = [
                '🔔 *Status Laporan Pelurusan Diperbarui* 🔔',
                '',
                '*Status Baru:* `' . $this->escapeMarkdown($newStatus->name) . '`',
                '---',
                '*ID Laporan:* `' . $this->escapeMarkdown($this->report->id_harian) . '`',
                '*Kode Pelurusan:* `' . $this->escapeMarkdown($this->report->pelurusan_code) . '`',
                '*OrderID:* `' . $this->escapeMarkdown($this->report->order_id) . '`',
            ];

            if ($this->keterangan) {
                $messageLines[] = '---';
                $messageLines[] = '*Catatan Resolusi:*';
                $messageLines[] = '```';
                $messageLines[] = $this->escapeMarkdown($this->keterangan);
                $messageLines[] = '```';
            }

            $messageLines[] = '---';
            $messageLines[] = '*Dibuat Oleh:* @' . $this->escapeMarkdown($reporter ? $reporter->telegram_username : $this->report->reporter_telegram_username);
            $messageLines[] = '*Diambil Oleh:* @' . $this->escapeMarkdown($assignee ? $assignee->telegram_username : 'N/A');

            if ($this->report->completed_at) {
                $messageLines[] = '*Selesai Pada:* `' . $this->escapeMarkdown($this->report->completed_at->format('Y-m-d H:i:s')) . '`';
                $duration = $this->report->created_at->diffForHumans($this->report->completed_at, true, true, 2);
                $messageLines[] = '*Durasi:* `' . $this->escapeMarkdown($duration) . '`';
            }

            $message = implode("\n", $messageLines);

            $recipients = [];

            // 1. Add Group Chat
            $groupChat = Cache::remember('telegram_group', now()->addHour(), fn () => TelegramGroup::first());
            if ($groupChat?->chat_id) {
                $recipients[] = $groupChat->chat_id;
            }

            // 2. Add Reporter
            $reporterChatId = $this->report->reporter_user_id
                ? $this->report->reporter->telegram_user_id
                : $this->report->reporter_telegram_id;
            if ($reporterChatId) {
                $recipients[] = $reporterChatId;
            }

            // 3. Add Assignee
            if ($assignee?->telegram_user_id) {
                $recipients[] = $assignee->telegram_user_id;
            }
            
            // 4. Add current user who changed the status
            if (Auth::check() && Auth::user()->telegram_user_id) {
                $recipients[] = Auth::user()->telegram_user_id;
            }

            $uniqueRecipients = array_unique($recipients);
            
            Log::info('Dispatching status change notifications to: ' . implode(', ', $uniqueRecipients));

            foreach ($uniqueRecipients as $chatId) {
                if ($chatId) {
                    SendTelegramNotificationJob::dispatch($chatId, $message, null, 'MarkdownV2');
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to dispatch status change notification: ' . $e->getMessage());
            $this->addError('notification_error', 'Gagal mengirim notifikasi: ' . $e->getMessage());
        }
    }

    private function escapeMarkdown($text): string
    {
        if (is_null($text)) {
            return 'N/A';
        }
        $chars = ['_', '*', '`', '['];
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }
}