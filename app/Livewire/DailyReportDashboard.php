<?php

namespace App\Livewire\Dashboard;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class DailyReportDashboard extends Component
{
    #[Reactive]
    public $selectedDate;

    #[Reactive]
    public $reportType;

    public $users = [];

    public $userCount = 0;

    public $reportRows = [];

    public $reportData = [];

    public $userTotals = [];

    public $rowTotals = [];

    public $grandTotal = 0;

    public $dashboardData = []; // Initialize as an empty array to avoid undefined variable

    public $bestPerformer = null;

    public $bestScore = 0;

    public function mount(): void
    {
        $this->selectedDate = $this->selectedDate ?? now()->format('Y-m-d'); // Default to today if not set
        $this->loadReportData();
        $this->userCount = User::role('hd-daman')->count();
        $this->getStats();
        $this->getBestPerformer();
    }

    public function loadReportData(): void
    {
        $userQuery = User::role('hd-daman');
        $this->users = $userQuery->get() ?? collect();
        $this->userCount = $this->users->count();

        $this->reportRows = [
            'fallout_completed' => 'Fallout Reports',
            'pelurusan_completed' => 'Pelunasan',
        ];

        // Reset data arrays
        $this->reportData = [];
        $this->userTotals = array_fill_keys($this->users->pluck('id')->toArray(), 0);
        $this->rowTotals = array_fill_keys(array_keys($this->reportRows), 0);
        $this->grandTotal = 0;

        // Fallout Reports
        $falloutQuery = FalloutReport::query()
            ->whereHas('falloutStatus', fn ($q) => $q->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress'));
        if ($this->selectedDate) {
            $falloutQuery->whereDate('created_at', $this->selectedDate);
        }
        $falloutCounts = $falloutQuery->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        // Pelurusan Reports
        $pelurusanQuery = PelurusanReport::query()
            ->whereHas('falloutStatus', fn ($q) => $q->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress'));
        if ($this->selectedDate) {
            $pelurusanQuery->whereDate('created_at', $this->selectedDate);
        }
        $pelurusanCounts = $pelurusanQuery->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        foreach ($this->users as $user) {
            $countFallout = $falloutCounts->get($user->id, 0);
            $this->reportData['fallout_completed'][$user->id] = $countFallout;
            $this->rowTotals['fallout_completed'] += $countFallout;
            $this->userTotals[$user->id] += $countFallout;

            $countPelurusan = $pelurusanCounts->get($user->id, 0);
            $this->reportData['pelurusan_completed'][$user->id] = $countPelurusan;
            $this->rowTotals['pelurusan_completed'] += $countPelurusan;
            $this->userTotals[$user->id] += $countPelurusan;
        }

        $this->grandTotal = array_sum($this->userTotals);
    }

    private function getStats(): void
    {
        $modelClass = $this->reportType === 'fallout' ? FalloutReport::class : PelurusanReport::class;
        $tableName = (new $modelClass)->getTable();

        $query = $modelClass::query()
            ->join('fallout_statuses', "$tableName.fallout_status_id", '=', 'fallout_statuses.id');

        if ($this->selectedDate) {
            $query->whereDate("$tableName.created_at", $this->selectedDate);
        }

        $results = $query->select(
            DB::raw('SUM(CASE WHEN fallout_statuses.name = "Open" THEN 1 ELSE 0 END) as open'),
            DB::raw('SUM(CASE WHEN fallout_statuses.name = "OnProgress" THEN 1 ELSE 0 END) as progress'),
            DB::raw('SUM(CASE WHEN fallout_statuses.name = "eskalasi" THEN 1 ELSE 0 END) as eskalasi'),
            DB::raw('SUM(CASE WHEN fallout_statuses.name NOT IN ("Open", "OnProgress", "eskalasi") THEN 1 ELSE 0 END) as close'),
            DB::raw('COUNT(*) as total_orders'),
            DB::raw('SUM(CASE WHEN fallout_statuses.name NOT IN ("Open", "OnProgress") THEN 1 ELSE 0 END) as completed_orders'),
            DB::raw('COUNT(DISTINCT assigned_to_user_id) as active_staff')
        )->first();

        $completionRate = ($results->total_orders ?? 0) > 0 ? round(($results->completed_orders ?? 0) / $results->total_orders * 100, 2) : 0;
        $avgPerPerson = ($results->active_staff ?? 0) > 0 ? round(($results->total_orders ?? 0) / $results->active_staff, 2) : 0;

        $this->dashboardData = [
            'open' => $results->open ?? 0,
            'progress' => $results->progress ?? 0,
            'eskalasi' => $results->eskalasi ?? 0,
            'close' => $results->close ?? 0,
            'completion_rate' => $completionRate,
            'active_staff' => $results->active_staff ?? 0,
            'avg_per_person' => $avgPerPerson,
            'team_open' => $results->open ?? 0,
            'team_progress' => $results->progress ?? 0,
            'team_completed' => $results->completed_orders ?? 0,
            'users' => $this->users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->toArray(),
            'work_distribution' => [
                'Fallout Reports' => $this->reportData['fallout_completed'] ?? [],
                'Pelunasan' => $this->reportData['pelurusan_completed'] ?? [],
            ],
            'user_totals' => $this->userTotals,
            'grand_total' => $this->grandTotal,
            'best_performer' => $this->bestPerformer,
        ];
    }

    private function getBestPerformer(): void
    {
        $userTotals = [];

        $fetchCompleted = fn ($modelClass) => $modelClass::query()
            ->whereHas('falloutStatus', fn ($q) => $q->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress'))
            ->when($this->selectedDate, fn ($q) => $q->whereDate('created_at', $this->selectedDate))
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->get();

        $falloutCompleted = $fetchCompleted(FalloutReport::class);
        $pelurusanCompleted = $fetchCompleted(PelurusanReport::class);

        foreach ($falloutCompleted->concat($pelurusanCompleted) as $result) {
            $userId = $result->assigned_to_user_id;
            $userTotals[$userId] = ($userTotals[$userId] ?? 0) + $result->total;
        }

        if (empty($userTotals)) {
            $this->bestPerformer = null;
            $this->bestScore = 0;

            return;
        }

        $bestScore = max($userTotals);
        $bestPerformerId = array_search($bestScore, $userTotals);
        $user = User::find($bestPerformerId);

        $this->bestPerformer = $user ? [
            'name' => $user->name,
            'initials' => strtoupper(substr($user->name, 0, 2)),
            'score' => $bestScore,
        ] : null;
        $this->bestScore = $bestScore;
    }

    public function render()
    {
        $this->loadReportData();
        $this->getStats();
        $this->getBestPerformer();

        $themeColor = auth()->user()->theme_color ?? 'light-blue';

        return view('livewire.dashboard.daily-report-dashboard', [
            'themeColor' => $themeColor,
            'dashboardData' => $this->dashboardData, // Explicitly pass $dashboardData to the view
        ]);
    }
}
