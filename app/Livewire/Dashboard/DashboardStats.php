<?php

namespace App\Livewire\Dashboard;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Reactive;

class DashboardStats extends Component
{
    #[Reactive]
    public $selectedDate;

    #[Reactive]
    public $reportType;

    public function render()
    {
        $stats = $this->getStats();

        return view('livewire.dashboard.dashboard-stats', [
            'stats' => $stats,
        ]);
    }

    private function getStats()
    {
        // Determine the model and table name based on the report type
        $modelClass = $this->reportType === 'fallout' ? FalloutReport::class : PelurusanReport::class;
        $tableName = (new $modelClass)->getTable();

        // Build the base query
        $query = $modelClass::query()
            ->join('fallout_statuses', $tableName . '.fallout_status_id', '=', 'fallout_statuses.id');

        // Apply date filter if a date is selected
        if ($this->selectedDate) {
            $query->whereDate($tableName . '.created_at', $this->selectedDate);
        }

        // Execute the query and get the results
        $results = $query->select(
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "Open" THEN 1 ELSE 0 END) as open'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "OnProgress" THEN 1 ELSE 0 END) as progress'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name = "eskalasi" THEN 1 ELSE 0 END) as eskalasi'),
                DB::raw('SUM(CASE WHEN fallout_statuses.name NOT IN ("Open", "OnProgress", "eskalasi") THEN 1 ELSE 0 END) as close')
            )
            ->first();

        // Return the stats array
        return [
            'open' => $results->open ?? 0,
            'progress' => $results->progress ?? 0,
            'eskalasi' => $results->eskalasi ?? 0,
            'close' => $results->close ?? 0,
        ];
    }
}
