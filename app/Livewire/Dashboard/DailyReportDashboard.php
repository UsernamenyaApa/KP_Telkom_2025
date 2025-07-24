<?php

namespace App\Livewire\Dashboard;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class DailyReportDashboard extends Component
{
    public $users;

    public $userCount = 0;

    public $reportRows = [];

    public $reportData = [];

    public $userTotals = [];

    public $rowTotals = [];

    public $grandTotal = 0;

    #[Reactive]
    public $selectedDate;

    public function mount(): void
    {
        $this->loadReportData();
        $this->userCount = \App\Models\User::role('hd-daman')->count();
    }

    public function loadReportData(): void
    {
        $userQuery = User::role('hd-daman');

        $this->users = $userQuery->get() ?? collect();
        $this->userCount = $this->users->count();

        $this->reportRows = [
            'fallout_completed' => 'Fallout Reports',
            'pelurusan_completed' => 'Pelurusan',
        ];

        // Reset data arrays
        $this->reportData = [];
        $this->userTotals = array_fill_keys($this->users->pluck('id')->toArray(), 0);
        $this->rowTotals = array_fill_keys(array_keys($this->reportRows), 0);
        $this->grandTotal = 0;

        // --- QUERIES ---

        // 1. Fallout reports considered "completed" for the selected date.
        // "Completed" means the status is NOT 'Open' or 'OnProgress'.
        $falloutQuery = FalloutReport::query()
            ->whereHas('falloutStatus', function ($query) {
                $query->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress');
            });

        if ($this->selectedDate) {
            $falloutQuery->whereDate('created_at', $this->selectedDate);
        }

        $falloutCounts = $falloutQuery->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        // 2. Pelurusan reports considered "completed" for the selected date.
        $pelurusanQuery = PelurusanReport::query()
            ->whereHas('falloutStatus', function ($query) {
                $query->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress');
            });

        if ($this->selectedDate) {
            $pelurusanQuery->whereDate('created_at', $this->selectedDate);
        }

        $pelurusanCounts = $pelurusanQuery->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        // --- DATA PROCESSING ---

        foreach ($this->users as $user) {
            // Populate data for 'Laporan Fallout Selesai'
            $countFallout = $falloutCounts->get($user->id, 0);
            $this->reportData['fallout_completed'][$user->id] = $countFallout;
            $this->rowTotals['fallout_completed'] += $countFallout;
            $this->userTotals[$user->id] += $countFallout;

            // Populate data for 'Laporan Pelurusan Selesai'
            $countPelurusan = $pelurusanCounts->get($user->id, 0);
            $this->reportData['pelurusan_completed'][$user->id] = $countPelurusan;
            $this->rowTotals['pelurusan_completed'] += $countPelurusan;
            $this->userTotals[$user->id] += $countPelurusan;
        }

        $this->grandTotal = $this->userTotals ? array_sum($this->userTotals) : 0;
    }

    public function render(): View
    {
        $this->loadReportData();

        return view('livewire.dashboard.daily-report-dashboard');
    }
}
