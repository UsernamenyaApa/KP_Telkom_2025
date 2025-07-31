<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class HdDamanManager extends Component
{
    public $users;

    public $searchTerm = '';

    // Properti untuk pengguna baru
    public $name;

    public $nik;

    protected $rules = [
        'name' => 'required|string|max:255',
        'nik' => 'required|string|unique:users,nik',
    ];

    public function mount()
    {
        $this->loadUsers();
    }

    public function render()
    {
        return view('livewire.hd-daman-manager');
    }

    public function loadUsers()
    {
        $this->users = User::where(function ($query) {
            $query->where('name', 'like', '%'.$this->searchTerm.'%')
                  ->orWhere('nik', 'like', '%'.$this->searchTerm.'%');
        })
        ->with('roles') // Eager load roles
        ->get();
    }

    public function updatedSearchTerm()
    {
        $this->loadUsers();
    }

    public function createUser()
    {
        $this->validate();

        // Periksa apakah NIK yang dimasukkan sudah ada
        if (User::where('nik', $this->nik)->exists()) {
            $this->addError('nik', "NIK {$this->nik} sudah terdaftar. Silakan gunakan NIK yang lain.");

            return;
        }
        // 2. Password adalah NIK
        $password = $this->nik;

        $user = User::create([
            'name' => $this->name,
            'nik' => $this->nik,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('hd-daman');

        $this->reset('name', 'nik');
        $this->loadUsers();

        session()->flash('message', "Pengguna {$user->name} berhasil dibuat. Passwordnya adalah NIK pengguna.");
    }

    public function assignHdDamanRole(User $user)
    {
        $user->assignRole('hd-daman');
        $this->loadUsers();
        session()->flash('message', 'Role "hd-daman" telah diberikan kepada '.$user->name);
    }

    public function revokeHdDamanRole(User $user)
    {
        $user->removeRole('hd-daman');
        $this->loadUsers();
        session()->flash('message', 'Role "hd-daman" telah dicabut dari '.$user->name);
    }
}
