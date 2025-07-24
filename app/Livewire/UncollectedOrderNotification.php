<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\PelurusanReport;
use Livewire\Component;

class UncollectedOrderNotification extends Component
{
    public $uncollectedCount = 0;
    public $newUncollectedCount = 0;

    public function mount()
    {
        $this->getUncollectedOrders();
    }

    public function getUncollectedOrders()
    {
        $uncollectedFallout = FalloutReport::whereNull('taken_at')->count();
        $uncollectedPelurusan = PelurusanReport::whereNull('taken_at')->count();

        $this->uncollectedCount = $uncollectedFallout + $uncollectedPelurusan;

        $newFallout = FalloutReport::whereNull('taken_at')
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $newPelurusan = PelurusanReport::whereNull('taken_at')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $this->newUncollectedCount = $newFallout + $newPelurusan;
    }

    public function render()
    {
        return view('livewire.uncollected-order-notification');
    }
}
