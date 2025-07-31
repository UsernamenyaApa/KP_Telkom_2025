<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyReportDashboard extends Component
{
    public $users;
    public $reportRows = [];
    public $reportData = [];
    public $userTotals = [];
    public $rowTotals = []; // Ensure it's initialized as an empty array
    public $grandTotal = 0;
    public $selectedDate;
    public $search = '';
    public $selectedStatus = null;
    public $selectedUserId = null;
    public $statusCounts = ['OPEN' => 0, 'PROGRESS' => 0, 'ESKALASI' => 0, 'CLOSE' => 0];

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadReportData();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadReportData();
    }

    public function updatedSearch(): void
    {
        $this->loadReportData();
    }

    public function loadReportData(): void
    {
        $userQuery = User::role('hd-daman');

        if (!empty($this->search)) {
            $userQuery->where('name', 'like', '%' . $this->search . '%');
        }

        $this->users = $userQuery->get();
        $filterDate = Carbon::parse($this->selectedDate);

        $this->reportRows = [
            'fallout_data' => 'Fallout Data',
            'pelurusan' => 'Pelurusan',
            'total_perorang' => 'Total Perorang',
        ];

        $this->reportData = [];
        $this->userTotals = array_fill_keys($this->users->pluck('id')->toArray(), 0);
        $this->rowTotals = array_fill_keys(array_keys($this->reportRows), 0);
        $this->grandTotal = 0;
        $this->statusCounts = ['OPEN' => 0, 'PROGRESS' => 0, 'ESKALASI' => 0, 'CLOSE' => 0];

        // Fetch status counts for fallout and pelurusan
        $falloutCounts = FalloutReport::whereDate('updated_at', $filterDate)
            ->groupBy('fallout_status_id')
            ->select('fallout_status_id', DB::raw('count(*) as total'))
            ->pluck('total', 'fallout_status_id');

        $pelurusanCounts = PelurusanReport::whereDate('updated_at', $filterDate)
            ->groupBy('fallout_status_id')
            ->select('fallout_status_id', DB::raw('count(*) as total'))
            ->pluck('total', 'fallout_status_id');

        $statusMap = [1 => 'OPEN', 2 => 'PROGRESS', 4 => 'ESKALASI', 6 => 'CLOSE'];
        foreach ($statusMap as $id => $status) {
            $this->statusCounts[$status] += ($falloutCounts->get($id, 0) + $pelurusanCounts->get($id, 0));
        }

        // Fetch user-specific data
        $completedCounts = FalloutReport::whereDate('updated_at', $filterDate)
            ->whereIn('fallout_status_id', [3, 4, 5, 6])
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        $onProgressCounts = FalloutReport::where('fallout_status_id', 2)
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        $pelurusanCompletedCounts = PelurusanReport::whereDate('updated_at', $filterDate)
            ->whereIn('fallout_status_id', [3, 4, 5, 6])
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        $pelurusanOnProgressCounts = PelurusanReport::where('fallout_status_id', 2)
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        foreach ($this->users as $user) {
            $countFalloutCompleted = $completedCounts->get($user->id, 0);
            $countFalloutOnProgress = $onProgressCounts->get($user->id, 0);
            $countPelurusanCompleted = $pelurusanCompletedCounts->get($user->id, 0);
            $countPelurusanOnProgress = $pelurusanOnProgressCounts->get($user->id, 0);

            $this->reportData['fallout_data'][$user->id] = $countFalloutCompleted + $countFalloutOnProgress;
            $this->reportData['pelurusan'][$user->id] = $countPelurusanCompleted + $countPelurusanOnProgress;
            $this->reportData['total_perorang'][$user->id] = $this->reportData['fallout_data'][$user->id] + $this->reportData['pelurusan'][$user->id];

            $this->rowTotals['fallout_data'] += $this->reportData['fallout_data'][$user->id];
            $this->rowTotals['pelurusan'] += $this->reportData['pelurusan'][$user->id];
            $this->rowTotals['total_perorang'] += $this->reportData['total_perorang'][$user->id];
            $this->userTotals[$user->id] = $this->reportData['total_perorang'][$user->id];
        }

        $this->grandTotal = array_sum($this->userTotals);
    }

    public function viewDetails($status, $userId = null): void
    {
        $this->selectedStatus = $status;
        $this->selectedUserId = $userId;
    }

    public function resetDetails(): void
    {
        $this->selectedStatus = null;
        $this->selectedUserId = null;
    }

    public function render()
    {
        return view('livewire.daily-report-dashboard', [
            'selectedStatus' => $this->selectedStatus,
            'selectedUserId' => $this->selectedUserId,
            'users' => $this->users,
            'reportData' => $this->reportData,
            'rowTotals' => $this->rowTotals ?? [],
            'grandTotal' => $this->grandTotal,
            'statusCounts' => $this->statusCounts,
        ]);
    }
}