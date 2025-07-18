<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\OrderType;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendTelegramNotificationJob;
use Telegram\Bot\Laravel\Facades\Telegram;

class FalloutReportDashboard extends Component
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
        $this->orderTypes = OrderType::all();
        $this->falloutStatuses = FalloutStatus::all();
        $this->assignedToUsers = User::role('hd-daman')->get();
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
        $report = FalloutReport::with(['orderType', 'reporter'])->find($reportId);

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

                $message = "✅ *Laporan Fallout Diambil!* ✅\n\n" .
                           "*ID Laporan:* `" . ($report->id ?? 'N/A') . "`\n" .
                           "*Kode Fallout:* `" . ($report->fallout_code ?? 'N/A') . "`\n" .
                           "*Tipe Order:* `" . ($report->orderType ? $report->orderType->name : 'N/A') . "`\n" .
                           "*OrderID:* `" . ($report->order_id ?? 'N/A') . "`\n" .
                           "*Nomor Layanan:* `" . ($report->nomer_layanan ?? 'N/A') . "`\n" .
                           "*SN ONT:* `" . ($report->sn_ont ?? 'N/A') . "`\n" .
                           "*Datek ODP:* `" . ($report->datek_odp ?? 'N/A') . "`\n" .
                           "*Port ODP:* `" . ($report->port_odp ?? 'N/A') . "`\n\n" .
                           "*Diambil Oleh:* @" . ($user->telegram_username ?? 'N/A') . "\n" .
                           "*Waktu Diambil:* " . ($report->assigned_at ? $report->assigned_at->format('Y-m-d H:i:s') : 'N/A') . "\n\n" .
                           "Mohon pantau status laporan ini.";

                if ($user->telegram_user_id) {
                    SendTelegramNotificationJob::dispatch($user->telegram_user_id, $message);
                }

                if ($report->reporter && $report->reporter->telegram_user_id) {
                    SendTelegramNotificationJob::dispatch($report->reporter->telegram_user_id, $message);
                }

                $groupChatId = env('TELEGRAM_GROUP_ID');
                if ($groupChatId) {
                    SendTelegramNotificationJob::dispatch($groupChatId, $message);
                }
            }
        }
    }

    public function render()
    {
        $reports = FalloutReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser'])
            ->when($this->date, function ($query) {
                $query->whereDate('created_at', $this->date);
            })
            ->when($this->search, function ($query) {
                $query->where('incident_ticket', 'like', '%' . $this->search . '%')
                      ->orWhere('order_id', 'like', '%' . $this->search . '%');
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

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}