<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutStatus;
use App\Models\PelurusanReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PelurusanReportDashboard extends Component
{
    use WithPagination;

    #[Url]
    public $date;

    public $search = '';

    #[Url]
    public $selectedOrderType = '';

    #[Url]
    public $selectedFalloutStatus = '';

    #[Url]
    public $selectedAssignedTo = '';

    public $orderTypes;

    public $falloutStatuses;

    public $assignedToUsers;

    public function mount()
    {
        if (empty($this->date)) {
            $this->date = Carbon::today()->format('Y-m-d');
        }
        $this->orderTypes = \App\Models\OrderType::all();
        $this->falloutStatuses = \App\Models\FalloutStatus::all();
        $this->assignedToUsers = \App\Models\User::role('hd-daman')->get();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDate()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->selectedOrderType = '';
        $this->selectedFalloutStatus = '';
        $this->selectedAssignedTo = '';
        $this->resetPage();
    }

    public function updatedSelectedOrderType()
    {
        $this->resetPage();
    }

    public function updatedSelectedFalloutStatus()
    {
        $this->resetPage();
    }

    public function updatedSelectedAssignedTo()
    {
        $this->resetPage();
    }

    public function takeOrder($reportId)
    {
        $report = PelurusanReport::with(['orderType', 'reporter'])->find($reportId);

        if ($report) {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $report->fallout_status_id = $onProgressStatus->id;
                $report->assigned_to_user_id = Auth::id();
                if (is_null($report->assigned_at)) {
                    $report->assigned_at = now();
                }
                $report->taken_at = now();
                $report->save();

                $user = Auth::user();

                $esc = fn (?string $text) => str_replace(
                    ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
                    ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
                    $text ?? '-'
                );

                $message = "✅ *Laporan Pelurusan Diambil!* ✅\n\n" .
                           "*ID Laporan:* `" . $esc($report->id_harian) . "`\n" .
                           "*Kode Pelurusan:* `" . $esc($report->pelurusan_code) . "`\n" .
                           "*Tipe Order:* `" . $esc($report->orderType ? $report->orderType->name : 'N/A') . "`\n" .
                           "*OrderID:* `" . $esc($report->order_id) . "`\n" .
                           "*Nomor Layanan:* `" . $esc($report->nomer_layanan) . "`\n" .
                           "*SN ONT:* `" . $esc($report->sn_ont) . "`\n" .
                           "*Datek ODP:* `" . $esc($report->datek_odp) . "`\n" .
                           "*Port ODP:* `" . $esc($report->port_odp) . "`\n\n" .
                           "*Diambil Oleh:* " . $esc('@' . $user->telegram_username) . "\n" .
                           "*Waktu Diambil:* `" . $esc($report->assigned_at ? $report->assigned_at->format('Y-m-d H:i:s') : 'N/A') . "`";

                $chatIdsToNotify = collect();

                // Add the user who took the order
                if ($user->telegram_user_id) {
                    $chatIdsToNotify->push($user->telegram_user_id);
                }

                // Add the reporter
                if ($report->reporter && $report->reporter->telegram_user_id) {
                    $chatIdsToNotify->push($report->reporter->telegram_user_id);
                }

                // Add the group chat
                $groupChat = \App\Models\TelegramGroup::first();
                if ($groupChat) {
                    $chatIdsToNotify->push($groupChat->chat_id);
                }

                // Dispatch notification to unique chat IDs
                $chatIdsToNotify->unique()->each(function ($chatId) use ($message) {
                    SendTelegramNotificationJob::dispatch($chatId, $message, null, 'MarkdownV2');
                });
            }
        }
    }

    public function render()
    {
        $reports = PelurusanReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser'])
            ->when($this->date, function ($query) {
                $query->whereDate('created_at', $this->date);
            })
            ->when($this->search, function ($query) {
                $query->where('order_id', 'like', '%'.$this->search.'%');
            })
            ->when($this->selectedOrderType, function ($query) {
                $query->where('tipe_order_id', $this->selectedOrderType);
            })
            ->when($this->selectedFalloutStatus, function ($query) {
                $query->where('fallout_status_id', $this->selectedFalloutStatus);
            })
            ->when($this->selectedAssignedTo, function ($query) {
                $query->where('assigned_to_user_id', $this->selectedAssignedTo);
            })
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.pelurusan-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
