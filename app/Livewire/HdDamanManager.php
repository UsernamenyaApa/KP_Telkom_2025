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

        // 1. Auto-generate email
        $email = Str::of($this->name)
            ->lower()
            ->split('/\s+/') // split by one or more spaces
            ->take(2)
            ->join('').'@tif.co.id';

        // Periksa apakah email yang dihasilkan sudah ada
        if (User::where('email', $email)->exists()) {
            $this->addError('name', "Generated email ({$email}) already exists. Please use a different name.");

            return;
        }

        // 2. Password adalah NIK
        $password = $this->nik;

        $user = User::create([
            'name' => $this->name,
            'email' => $email,
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
