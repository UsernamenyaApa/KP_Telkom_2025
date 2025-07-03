<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HdDamanManager extends Component
{
    public $users;
    public $searchTerm = '';

    // Properti untuk pengguna baru
    public $name;
    public $nik;
    public $telegram_username;

    protected $rules = [
        'name' => 'required|string|max:255',
        'nik' => 'required|string|unique:users,nik',
        'telegram_username' => 'nullable|string|max:255|unique:users,telegram_username',
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
                  ->orWhere('email', 'like', '%'.$this->searchTerm.'%');
        })
        ->where('email', 'like', '%@tif.co.id') // Filter for @tif.co.id emails
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
            ->join('') . '@tif.co.id';

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
            'telegram_username' => $this->telegram_username,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('hd-daman');

        $this->reset('name', 'nik', 'telegram_username');
        $this->loadUsers();

        session()->flash('message', "Pengguna {$user->name} berhasil dibuat dengan email: {$email}. Passwordnya adalah NIK pengguna.");
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
