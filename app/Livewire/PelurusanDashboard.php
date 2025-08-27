<?php

namespace App\Livewire;

use App\Models\PelurusanReport;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PelurusanDashboard extends Component
{
    use WithPagination;

    #[Url]
    public $date;

    public $search = '';

    public function mount()
    {
        if (empty($this->date)) {
            $this->date = Carbon::today()->format('Y-m-d');
        }
        \Illuminate\Support\Facades\Log::debug('PelurusanDashboard Date: '.($this->date ?? 'null'));
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDate()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = PelurusanReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser']);

        // Filter by date
        if ($this->date) {
            $query->whereDate('created_at', $this->date);
        }

        // Filter by search term
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pelurusan_code', 'like', '%'.$this->search.'%')
                    ->orWhere('order_id', 'like', '%'.$this->search.'%');
            });
        }

        $reports = $query->orderBy('created_at', 'asc')->paginate(10);

        return view('livewire.pelurusan-dashboard', [
            'reports' => $reports,
        ]);
    }
}
