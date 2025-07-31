<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class DashboardPage extends Component
{
    public $selectedDate;

    public $reportType = 'fallout';

    public function mount()
    {
        $this->selectedDate = Carbon::now()->toDateString();
        $this->dispatch('dateUpdated', date: $this->selectedDate);
    }

    public function updatedSelectedDate($value)
    {
        $this->dispatch('dateUpdated', date: $value);
    }

    public function render()
    {
        return view('livewire.dashboard-page');
    }
}
