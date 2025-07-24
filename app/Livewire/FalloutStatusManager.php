<?php

namespace App\Livewire;

use App\Models\FalloutStatus;
use Livewire\Component;

class FalloutStatusManager extends Component
{
    public $name;

    public $falloutStatusId;

    public $isEditing = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:fallout_statuses,name',
    ];

    public function render()
    {
        return view('livewire.fallout-status-manager', [
            'falloutStatuses' => FalloutStatus::all(),
        ]);
    }

    public function create()
    {
        $this->validate();

        FalloutStatus::create(['name' => $this->name]);

        $this->reset(['name']);
        session()->flash('message', 'FalloutStatus created successfully.');
    }

    public function edit($id)
    {
        $falloutStatus = FalloutStatus::findOrFail($id);
        $this->name = $falloutStatus->name;
        $this->falloutStatusId = $falloutStatus->id;
        $this->isEditing = true;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:fallout_statuses,name,'.$this->falloutStatusId,
        ]);

        $falloutStatus = FalloutStatus::findOrFail($this->falloutStatusId);
        $falloutStatus->update(['name' => $this->name]);

        $this->reset(['name', 'falloutStatusId', 'isEditing']);
        session()->flash('message', 'FalloutStatus updated successfully.');
    }

    public function delete($id)
    {
        FalloutStatus::destroy($id);
        session()->flash('message', 'FalloutStatus deleted successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'falloutStatusId', 'isEditing']);
    }
}
