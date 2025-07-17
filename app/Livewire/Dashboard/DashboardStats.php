<?php

namespace App\Livewire\Dashboard;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class DashboardStats extends Component
{
    public $reportType = 'fallout'; // 'fallout' or 'pelurusan'

    public function render()
    {
        $stats = $this->getStats();

        return view('livewire.dashboard.dashboard-stats', [
            'stats' => $stats,
        ]);
    }

    public function updatedReportType()
    {
        $this->dispatch('reportTypeChanged', $this->reportType);
    }

    private function getStats()
    {
        $modelClass = $this->reportType === 'fallout' ? FalloutReport::class : PelurusanReport::class;
        $modelInstance = new $modelClass();
        $tableName = $modelInstance->getTable();

        $results = $modelClass::query()
            ->join('fallout_statuses', $tableName . '.fallout_status_id', '=', 'fallout_statuses.id')
            ->select(
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "Open" THEN 1 ELSE 0 END) as open'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "OnProgress" THEN 1 ELSE 0 END) as progress'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "eskalasi" THEN 1 ELSE 0 END) as eskalasi'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name NOT IN ("Open", "OnProgress", "eskalasi") THEN 1 ELSE 0 END) as close')
            )
            ->first();

        return [
            'open' => $results->open ?? 0,
            'progress' => $results->progress ?? 0,
            'eskalasi' => $results->eskalasi ?? 0,
            'close' => $results->close ?? 0,
        ];
    }
}