<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProfilePhotoDisplay extends Component
{
    public $user;

    protected $listeners = ['profile-updated' => 'refreshUser'];

    public function mount()
    {
        $this->user = Auth::user();
    }

    public function refreshUser()
    {
        $this->user = Auth::user();
    }

    public function render()
    {
        return view('livewire.profile-photo-display');
    }
}