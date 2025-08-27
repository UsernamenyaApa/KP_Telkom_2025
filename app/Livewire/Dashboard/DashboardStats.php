<?php

namespace App\Livewire\Dashboard;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class DashboardStats extends Component
{
    #[Reactive]
    public $selectedDate;

    #[Reactive]
    public $reportType;

    public Collection $users;

    public array $reportData = [];

    public array $userTotals = [];

    public int $grandTotal = 0;

    public function boot(): void
    {
        if (is_null($this->selectedDate)) {
            $this->selectedDate = now()->subDay()->format('Y-m-d');
        }
    }

    public function mount(): void
    {
        $this->users = User::role('hd-daman')->get();
    }

    public function render()
    {
        $this->loadReportData();
        $stats = $this->getStats();
        $bestPerformerData = $this->getBestPerformer();

        $dashboardData = array_merge($stats, [
            'users' => $this->users->map(fn ($user) => ['id' => $user->id, 'name' => $user->name])->toArray(),
            'work_distribution' => [
                'Fallout Reports' => $this->reportData['fallout_completed'] ?? [],
                'Pelunasan' => $this->reportData['pelurusan_completed'] ?? [],
            ],
            'user_totals' => $this->userTotals,
            'grand_total' => $this->grandTotal,
            'best_performer' => $bestPerformerData['performer'],
        ]);

        return view('livewire.dashboard.dashboard-stats', [
            'stats' => $dashboardData,
            'bestPerformer' => $bestPerformerData['performer'],
            'bestScore' => $bestPerformerData['score'],
        ]);
    }

    public function loadReportData(): void
    {
        $userIds = $this->users->pluck('id')->toArray();
        $this->userTotals = array_fill_keys($userIds, 0);
        $this->grandTotal = 0;

        $baseQuery = fn ($modelClass) => $modelClass::query()
            ->whereHas('falloutStatus', fn ($q) => $q->where('name', '!=', 'Open')->where('name', '!=', 'OnProgress'))
            ->when($this->selectedDate, fn ($q) => $q->whereDate('created_at', $this->selectedDate));

        $fallout = $baseQuery(FalloutReport::class)
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        $pelurusan = $baseQuery(PelurusanReport::class)
            ->groupBy('assigned_to_user_id')
            ->select('assigned_to_user_id', DB::raw('count(*) as total'))
            ->pluck('total', 'assigned_to_user_id');

        $allCounts = ['fallout' => $fallout, 'pelurusan' => $pelurusan];

        $falloutCounts = $allCounts['fallout'];
        $pelurusanCounts = $allCounts['pelurusan'];

        foreach ($this->users as $user) {
            $countFallout = $falloutCounts->get($user->id, 0);
            $this->reportData['fallout_completed'][$user->id] = $countFallout;
            $this->userTotals[$user->id] += $countFallout;

            $countPelurusan = $pelurusanCounts->get($user->id, 0);
            $this->reportData['pelurusan_completed'][$user->id] = $countPelurusan;
            $this->userTotals[$user->id] += $countPelurusan;
        }

        $this->grandTotal = array_sum($this->userTotals);
    }

    private function getStats(): array
    {
        $modelClass = $this->reportType === 'fallout' ? FalloutReport::class : PelurusanReport::class;
        $tableName = (new $modelClass)->getTable();

        $query = $modelClass::query()
            ->join('fallout_statuses', $tableName.'.fallout_status_id', '=', 'fallout_statuses.id')
            ->when($this->selectedDate, fn ($q) => $q->whereDate($tableName.'.created_at', $this->selectedDate));

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
        $avgPerPerson = ($results->active_staff ?? 0) > 0 ? round(($results->completed_orders ?? 0) / $results->active_staff, 2) : 0;

        return [
            'open' => $results->open ?? 0, 'progress' => $results->progress ?? 0,
            'eskalasi' => $results->eskalasi ?? 0, 'close' => $results->close ?? 0,
            'completion_rate' => $completionRate, 'active_staff' => $results->active_staff ?? 0,
            'avg_per_person' => $avgPerPerson, 'team_open' => $results->open ?? 0,
            'team_progress' => $results->progress ?? 0, 'team_completed' => $results->completed_orders ?? 0,
        ];
    }

    private function getBestPerformer(): array
    {
        if (empty($this->userTotals) || max($this->userTotals) == 0) {
            return ['performer' => null, 'score' => 0];
        }

        $bestScore = max($this->userTotals);
        $bestPerformerId = array_search($bestScore, $this->userTotals);
        $user = $this->users->firstWhere('id', $bestPerformerId);

        if (! $user) {
            return ['performer' => null, 'score' => $bestScore];
        }

        return [
            'performer' => [
                'name' => $user->name,
                'initials' => strtoupper(substr($user->name, 0, 2)),
                'score' => $bestScore,
            ],
            'score' => $bestScore,
        ];
    }
}
