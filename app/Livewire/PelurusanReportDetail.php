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

        // Debugging: Log reporter information
        if ($this->report->reporter) {
            \Illuminate\Support\Facades\Log::info('PelurusanReportDetail: Reporter User ID: ' . $this->report->reporter_user_id . ', Reporter Name: ' . $this->report->reporter->name);
        } else {
            \Illuminate\Support\Facades\Log::info('PelurusanReportDetail: Reporter not found for report ID: ' . $this->report->id . ', Reporter User ID in DB: ' . $this->report->reporter_user_id);
        }
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

            $message = "✅ Laporan Pelurusan Diambil! ✅\n\n" .
                "ID Laporan: " . ($this->report->id_harian ?? 'N/A') . "\n" .
                "Kode Pelurusan: " . ($this->report->pelurusan_code ?? 'N/A') . "\n" .
                "Tipe Order: " . ($this->report->orderType ? $this->report->orderType->name : 'N/A') . "\n" .
                "OrderID: " . ($this->report->order_id ?? 'N/A') . "\n" .
                "Nomor Layanan: " . ($this->report->nomer_layanan ?? 'N/A') . "\n" .
                "SN ONT: " . ($this->report->sn_ont ?? 'N/A') . "\n" .
                "Datek ODP: " . ($this->report->datek_odp ?? 'N/A') . "\n" .
                "Port ODP: " . ($this->report->port_odp ?? 'N/A') . "\n\n" .
                "Diambil Oleh: @" . ($user->telegram_username ?? 'N/A') . "\n" .
                "Waktu Diambil: " . ($this->report->assigned_at ? $this->report->assigned_at->format('Y-m-d H:i:s') : 'N/A');

            if ($user->telegram_user_id) {
                $takerMessage = "✅ Anda telah berhasil mengambil laporan pelurusan dengan ID #{$this->report->id_harian} (`{$this->report->pelurusan_code}`). Mohon segera ditindaklanjuti.

" .
                                "Berikut detail laporan:

" .
                                "*ID Laporan:* `" . ($this->report->id_harian ?? 'N/A') . "`
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
                SendTelegramNotificationJob::dispatch($user->telegram_user_id, $takerMessage, null, 'MarkdownV2');
            }

            if ($this->report->reporter_user_id) {
                $reporterChatId = $this->report->reporter->telegram_user_id;
            } else {
                $reporterChatId = $this->report->reporter_telegram_id;
            }

            if ($reporterChatId) {
                $reporterMessage = "✅ Laporan Pelurusan Diambil! ✅

" .
                                   "*ID Laporan:* `" . ($this->report->id_harian ?? 'N/A') . "`
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
                SendTelegramNotificationJob::dispatch($reporterChatId, $reporterMessage, null, 'MarkdownV2');
            }

            $this->sendTelegramNotifications($message);
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

            $this->dispatchNotification($newStatus);

            $this->closeStatusModal();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal mengubah status laporan pelurusan: " . $e->getMessage());
            $this->addError('statusError', 'Gagal mengubah status laporan: ' . $e->getMessage());
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
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }
}