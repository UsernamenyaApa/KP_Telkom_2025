<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;

class Appearance extends Component
{
    public string $appearance;
    public string $theme_color;

    public function mount(): void
    {
        $this->appearance = auth()->user()->appearance ?? 'system';
        $this->theme_color = auth()->user()->theme_color ?? 'light-blue';
    }

    public function save(): void
    {
        auth()->user()->forceFill([
            'appearance' => $this->appearance,
            'theme_color' => $this->theme_color,
        ])->save();

        $this->dispatch('saved', appearance: $this->appearance, themeColor: $this->theme_color);
    }

    public function render()
    {
        return view('livewire.settings.appearance');
    }
}
