<?php

namespace App\Livewire;

use App\Models\OrderType;
use Livewire\Component;

class OrderTypeManager extends Component
{
    public $name;

    public $orderTypeId;

    public $isEditing = false;

    protected $rules = [
        'name' => 'required|string|max:255|unique:order_types,name',
    ];

    public function render()
    {
        return view('livewire.order-type-manager', [
            'orderTypes' => OrderType::all(),
        ]);
    }

    public function create()
    {
        $this->validate();

        OrderType::create(['name' => $this->name]);

        $this->reset(['name']);
        session()->flash('message', 'OrderType created successfully.');
    }

    public function edit($id)
    {
        $orderType = OrderType::findOrFail($id);
        $this->name = $orderType->name;
        $this->orderTypeId = $orderType->id;
        $this->isEditing = true;
    }

    public function update()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:order_types,name,'.$this->orderTypeId,
        ]);

        $orderType = OrderType::findOrFail($this->orderTypeId);
        $orderType->update(['name' => $this->name]);

        $this->reset(['name', 'orderTypeId', 'isEditing']);
        session()->flash('message', 'OrderType updated successfully.');
    }

    public function delete($id)
    {
        OrderType::destroy($id);
        session()->flash('message', 'OrderType deleted successfully.');
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'orderTypeId', 'isEditing']);
    }
}
