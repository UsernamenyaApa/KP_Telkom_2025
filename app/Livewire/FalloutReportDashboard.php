<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class FalloutReportDashboard extends Component
{
    use WithPagination;

    public $date;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function render()
    {
        $reports = FalloutReport::with(['reporter', 'orderType', 'falloutStatus'])
            ->whereDate('created_at', $this->date)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
