<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class DailyReportDashboard extends Component
{
    public $users;
    public $reportRows = [];
    public $reportData = [];
    public $userTotals = [];
    public $rowTotals = [];
    public $grandTotal = 0;

    public $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadReportData();
    }

    public function updatedSelectedDate(): void
    {
        $this->loadReportData();
    }

    public function loadReportData(): void
    {
        $this->users = User::role('hd-daman')->get();
        $filterDate = Carbon::parse($this->selectedDate);

        // Define the rows for our report table
        $this->reportRows = [
            'reports_completed_today' => 'Laporan Selesai (Hari Ini)',
            'reports_on_progress' => 'Laporan Dalam Pengerjaan',
        ];

        // Reset data arrays
        $this->reportData = [];
        $this->userTotals = array_fill_keys($this->users->pluck('id')->toArray(), 0);
        $this->rowTotals = array_fill_keys(array_keys($this->reportRows), 0);
        $this->grandTotal = 0;

        // --- QUERIES ---

        // 1. Reports that were COMPLETED on the selected date.
        // "Completed" means the status changed to a final state (not 'Open' or 'OnProgress').
        $completedCounts = FalloutReport::whereDate('updated_at', $filterDate)
            ->whereIn('fallout_status_id', [3, 4, 5, 6]) // 'input ulang', 'eskalasi', 'PI', 'FA'
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        // 2. Reports currently 'OnProgress' (regardless of date).
        $onProgressCounts = FalloutReport::where('fallout_status_id', 2) // ID for 'OnProgress'
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        // --- DATA PROCESSING ---

        foreach ($this->users as $user) {
            // Populate data for 'Laporan Selesai (Hari Ini)'
            $countCompleted = $completedCounts->get($user->id, 0);
            $this->reportData['reports_completed_today'][$user->id] = $countCompleted;
            $this->rowTotals['reports_completed_today'] += $countCompleted;
            $this->userTotals[$user->id] += $countCompleted;

            // Populate data for 'Laporan Dalam Pengerjaan'
            $countOnProgress = $onProgressCounts->get($user->id, 0);
            $this->reportData['reports_on_progress'][$user->id] = $countOnProgress;
            $this->rowTotals['reports_on_progress'] += $countOnProgress;
            $this->userTotals[$user->id] += $countOnProgress;
        }

        $this->grandTotal = $this->userTotals ? array_sum($this->userTotals) : 0;
    }

    public function render(): View
    {
        return view('livewire.daily-report-dashboard');
    }
}
