<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use Livewire\Component;

class NotificationBell extends Component
{
    public $totalUnassignedCount = 0;
    public $unassignedFalloutReports = [];
    public $unassignedPelurusanReports = [];

    protected $listeners = [
        'reportAssigned' => 'loadUnassignedReports',
        'checkNotifications' => 'loadUnassignedReports', // Tambahkan listener ini
    ];

    public function mount()
    {
        $this->loadUnassignedReports();
    }

    public function loadUnassignedReports()
    {
        $this->unassignedFalloutReports = FalloutReport::whereNull('assigned_to_user_id')
            ->where('fallout_status_id', 1)
            ->latest()
            ->get();

        $this->unassignedPelurusanReports = PelurusanReport::whereNull('assigned_to_user_id')
            ->where('fallout_status_id', 1)
            ->latest()
            ->get();

        $this->totalUnassignedCount = $this->unassignedFalloutReports->count() + $this->unassignedPelurusanReports->count();
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}
