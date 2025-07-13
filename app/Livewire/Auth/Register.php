<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Attributes\Rule;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public function render()
    {
        return view('livewire.auth.register');
    }
}
