<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        // 2. Password adalah default 'password'
        $password = 'password';

        $user = User::create([
            'name' => $this->name,
            'nik' => $this->nik,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('hd-daman');

        $this->reset('name', 'nik');
        $this->loadUsers();

        session()->flash('message', "Pengguna {$user->name} berhasil dibuat. Passwordnya adalah 'password'. Harap ganti password setelah login.");
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

    public function deleteUser(User $user)
    {
        // Pastikan pengguna yang masuk adalah superadmin
        if (! auth()->user()->hasRole('super-admin')) {
            session()->flash('error', 'Anda tidak memiliki izin untuk menghapus pengguna.');

            return;
        }

        // Jangan biarkan superadmin menghapus diri sendiri
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');

            return;
        }

        $userName = $user->name;
        $user->delete();
        $this->loadUsers();
        session()->flash('message', "Pengguna {$userName} berhasil dihapus.");
    }
}
