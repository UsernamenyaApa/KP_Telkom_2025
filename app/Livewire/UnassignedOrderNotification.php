<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use Livewire\Component;

class UnassignedOrderNotification extends Component
{
    public $unassignedFalloutReportsCount = 0;
    public $unassignedPelurusanReportsCount = 0;
    public $unassignedFalloutReports = [];
    public $unassignedPelurusanReports = [];

    protected $listeners = ['reportAssigned' => 'loadUnassignedReports'];

    public function mount()
    {
        $this->loadUnassignedReports();
        // Add dd() here to inspect the counts
        
    }

    public function loadUnassignedReports()
    {
        $this->unassignedFalloutReports = FalloutReport::whereNull('assigned_to_user_id')
            ->where('fallout_status_id', 1) // Assuming 1 is 'Open' status
            ->latest()
            ->get();
        $this->unassignedFalloutReportsCount = $this->unassignedFalloutReports->count();

        $this->unassignedPelurusanReports = PelurusanReport::whereNull('assigned_to_user_id')
            ->where('fallout_status_id', 1) // Corrected to use fallout_status_id
            ->latest()
            ->get();
        $this->unassignedPelurusanReportsCount = $this->unassignedPelurusanReports->count();
    }

    public function render()
    {
        return view('livewire.unassigned-order-notification');
    }
}
