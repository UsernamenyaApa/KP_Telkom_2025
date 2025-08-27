<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\OrderType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use League\CommonMark\CommonMarkConverter;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

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

    

        private function escapeMarkdown($text): string
    {
        if (is_null($text)) {
            return 'N/A';
        }

        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

        return str_replace($chars, array_map(fn ($char) => '\\'.$char, $chars), $text);
    }

    public function render()
    {
        $query = FalloutReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser']);

        // Filter by date
        if ($this->date) {
            $query->whereDate('created_at', $this->date);
        }

        // Filter by search term
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('incident_ticket', 'like', '%'.$this->search.'%')
                    ->orWhere('order_id', 'like', '%'.$this->search.'%');
            });
        }

        // Filter by order type
        if ($this->selectedOrderType) {
            $query->where('tipe_order_id', $this->selectedOrderType);
        }

        // Filter by assigned to user
        if ($this->selectedAssignedTo) {
            $query->where('assigned_to_user_id', $this->selectedAssignedTo);
        }

        // Filter by fallout status
        if ($this->selectedFalloutStatus) {
            $query->where('fallout_status_id', $this->selectedFalloutStatus);
        }

        $reports = $query->orderBy('created_at', 'asc')->paginate(10);

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
