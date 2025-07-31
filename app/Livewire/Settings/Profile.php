<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public User $user;
    public string $name = '';
    public string $nik = '';
    public $photo;

    public function mount(): void
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->nik = $this->user->nik;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:255',
            'photo' => 'nullable|image|max:1024',
        ]);

        if ($this->photo) {
            $this->user->updateProfilePhoto($this->photo);
        }

        $this->user->forceFill([
            'name' => $this->name,
            'nik' => $this->nik,
        ])->save();

        $this->photo = null;
        $this->user->refresh();

        $this->dispatch('saved');
    }

    public function deleteProfilePhoto(): void
    {
        $this->user->deleteProfilePhoto();
        $this->photo = null;
        $this->user->refresh();
        $this->dispatch('saved');
    }

    public function render()
    {
        return view('livewire.settings.profile');
    }
}
