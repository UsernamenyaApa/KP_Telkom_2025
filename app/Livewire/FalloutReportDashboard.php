<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FalloutReportDashboard extends Component
{
    use WithPagination;

    public $date;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function takeOrder($reportId)
    {
        $report = FalloutReport::find($reportId);

        if ($report) {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $report->fallout_status_id = $onProgressStatus->id;
                $report->assigned_to_user_id = Auth::id(); // Store user ID
                $report->respon_fallout = Auth::user()->name; // Store user name for display
                $report->save();
            }
        }
    }

    public function render()
    {
        $reports = FalloutReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser'])
            ->whereDate('created_at', $this->date)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
