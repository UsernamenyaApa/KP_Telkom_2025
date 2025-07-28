<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use App\Models\TelegramGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    private const ON_PROGRESS = 'OnProgress';
    private const COMPLETED_STATUSES = ['FA', 'eskalasi', 'input ulang', 'PI'];

    public function mount($id, $date = null)
    {
        $this->report = PelurusanReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])
            ->findOrFail($id);
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
                'fallout_status_id' => $onProgressStatus->id,
                'assigned_to_user_id' => Auth::id(),
                'assigned_at' => $this->report->assigned_at ?? now(),
                'taken_at' => now(),
            ]);

            DB::commit();

            $user = Auth::user();
            $message = sprintf(
                "✅ *Laporan Pelurusan Diambil!* ✅\n\n" .
                "*ID Laporan:* `%s`\n" .
                "*Kode Pelurusan:* `%s`\n" .
                "*Tipe Order:* `%s`\n" .
                "*OrderID:* `%s`\n" .
                "*Nomor Layanan:* `%s`\n" .
                "*SN ONT:* `%s`\n" .
                "*Datek ODP:* `%s`\n" .
                "*Port ODP:* `%s`\n\n" .
                "*Diambil Oleh:* @%s\n" .
                "*Waktu Diambil:* %s",
                $this->escapeMarkdown($this->report->id ?? 'N/A'),
                $this->escapeMarkdown($this->report->pelurusan_code ?? 'N/A'),
                $this->escapeMarkdown($this->report->orderType->name ?? 'N/A'),
                $this->escapeMarkdown($this->report->order_id ?? 'N/A'),
                $this->escapeMarkdown($this->report->nomer_layanan ?? 'N/A'),
                $this->escapeMarkdown($this->report->sn_ont ?? 'N/A'),
                $this->escapeMarkdown($this->report->datek_odp ?? 'N/A'),
                $this->escapeMarkdown($this->report->port_odp ?? 'N/A'),
                $this->escapeMarkdown($user->telegram_username ?? 'N/A'),
                $this->report->assigned_at ? $this->report->assigned_at->format('Y-m-d H:i:s') : 'N/A'
            );

            if ($user->telegram_user_id) {
                $takerMessage = sprintf(
                    "✅ Anda telah berhasil mengambil laporan pelurusan dengan ID #%s (`%s`). Mohon segera ditindaklanjuti.",
                    $this->report->id,
                    $this->escapeMarkdown($this->report->pelurusan_code)
                );
                SendTelegramNotificationJob::dispatch($user->telegram_user_id, $takerMessage, null, 'MarkdownV2');
            }

            $this->sendTelegramNotifications($message);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: ' . $e->getMessage());
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

            $this->report->refresh();

            $message = sprintf(
                "*Status Baru:* %s\n\n" .
                "*ID Laporan:* `%s`\n" .
                "*Kode Pelurusan:* `%s`\n" .
                "*Tipe Order:* `%s`\n" .
                "*OrderID:* `%s`\n" .
                "*Nomor Layanan:* `%s`\n" .
                "*SN ONT:* `%s`\n" .
                "*Datek ODP:* `%s`\n" .
                "*Port ODP:* `%s`\n\n" .
                "*Keterangan:*\n%s\n\n" .
                "----------------------------------------\n" .
                "*Dibuat Oleh:* @%s\n" .
                "*Tanggal Dibuat:* %s\n" .
                "*Diambil Pada:* %s\n" .
                "*Diperbarui Oleh:* @%s",
                $this->escapeMarkdown($newStatus->name),
                $this->report->id ?? 'N/A',
                $this->report->pelurusan_code ?? 'N/A',
                $this->escapeMarkdown($this->report->orderType->name ?? 'N/A'),
                $this->report->order_id ?? 'N/A',
                $this->report->nomer_layanan ?? 'N/A',
                $this->report->sn_ont ?? 'N/A',
                $this->report->datek_odp ?? 'N/A',
                $this->report->port_odp ?? 'N/A',
                $this->escapeMarkdown($this->keterangan ?? 'N/A'),
                $this->escapeMarkdown($this->report->reporter_user_id ? $this->report->reporter->telegram_username : $this->report->reporter_telegram_username ?? 'N/A'),
                $this->report->created_at->format('Y-m-d H:i:s'),
                $this->report->taken_at ? $this->report->taken_at->format('Y-m-d H:i:s') : 'N/A',
                $this->escapeMarkdown(Auth::user()->telegram_username ?? 'N/A')
            );

            if ($this->report->completed_at && $this->report->taken_at) {
                $duration = $this->report->taken_at->diffForHumans($this->report->completed_at, true, true, 2);
                $message .= sprintf(
                    "\n\n*Selesai Pada:* %s\n*Durasi:* %s",
                    $this->report->completed_at->format('Y-m-d H:i:s'),
                    $duration
                );
            }

            $this->sendTelegramNotifications($message);
            if (Auth::user()->telegram_user_id) {
                SendTelegramNotificationJob::dispatch(Auth::user()->telegram_user_id, $message, null, 'MarkdownV2');
            }

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

    private function escapeMarkdown($text)
    {
        if (is_null($text)) {
            return 'N/A';
        }

        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '=', '|', '{', '}', '!'];
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }
}