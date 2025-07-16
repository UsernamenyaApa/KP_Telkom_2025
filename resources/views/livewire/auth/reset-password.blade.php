<?php

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Menggunakan layout minimal yang sama
new #[Layout('components.layouts.minimal')] class extends Component
{
    // Properti untuk menampung password baru dan konfirmasinya
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Fungsi untuk menyimpan password baru.
     */
    public function savePassword(): void
    {
        // 1. Validasi input: harus diisi, minimal 8 karakter, dan harus cocok.
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 2. Logika sementara untuk memeriksa password default
        if ($this->password === 'KPMAGANG') {
            // FIX: Arahkan ke halaman 'dashboard'
            $this->redirect(route('password.success'), navigate: true);
        } else {
            // Jika salah, kosongkan input dan tampilkan pesan error.
            $this->reset('password', 'password_confirmation');
            $this->addError('password', 'Password tidak valid. Gunakan "KPMAGANG" untuk melanjutkan.');
        }
    }
}; ?>

{{-- Tampilan Halaman Reset Password dengan Ikon Gambar --}}
<div class="flex items-center justify-center w-full h-full p-4">

    <div class="w-full max-w-md p-8 space-y-6 bg-gray-200/50 backdrop-blur-sm rounded-xl shadow-lg border border-gray-300/60">
        
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">
                Password
            </h2>
            <p class="mt-2 text-sm text-slate-700">
                Silakan atur ulang kata sandi Anda untuk menjaga keamanan akun.
            </p>
        </div>

        <form wire:submit="savePassword" class="space-y-6">
            @csrf
            
            {{-- Input Password Baru dengan Ikon Gambar --}}
            <div x-data="{ showPassword: false }">
                <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                <div class="mt-1 relative">
                    <input 
                        wire:model="password" 
                        id="password" 
                        name="password" 
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="New password" required
                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    
                    {{-- Tombol untuk Toggle Ikon --}}
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                        <button type="button" @click="showPassword = !showPassword" class="text-gray-500 hover:text-gray-700">
                            {{-- Ikon untuk MENAMPILKAN password (mata terbuka) --}}
                            <img x-show="!showPassword" src="{{ asset('images/show icon logo.png') }}" alt="Show Password" class="h-5 w-5">
                            
                            {{-- Ikon untuk MENYEMBUNYIKAN password (mata tercoret) --}}
                            <img x-show="showPassword" src="{{ asset('images/hidden icon logo.png') }}" alt="Hide Password" class="h-5 w-5" style="display: none;">
                        </button>
                    </div>
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            {{-- Input Konfirmasi Password dengan Ikon Gambar --}}
            <div x-data="{ showConfirmPassword: false }">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <div class="mt-1 relative">
                    <input 
                        wire:model="password_confirmation" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        :type="showConfirmPassword ? 'text' : 'password'"
                        placeholder="Confirm password" required
                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">

                    {{-- Tombol untuk Toggle Ikon --}}
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="text-gray-500 hover:text-gray-700">
                            {{-- Ikon untuk MENAMPILKAN password (mata terbuka) --}}
                            <img x-show="!showConfirmPassword" src="{{ asset('images/show icon logo.png') }}" alt="Show Password" class="h-5 w-5">
                            
                            {{-- Ikon untuk MENYEMBUNYIKAN password (mata tercoret) --}}
                            <img x-show="showConfirmPassword" src="{{ asset('images/hidden icon logo.png') }}" alt="Hide Password" class="h-5 w-5" style="display: none;">
                        </button>
                    </div>
                </div>
            </div>

            {{-- Tombol Simpan --}}
            <div>
                <button type="submit"
                    class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-slate-800 border border-transparent rounded-md shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-700 transition-colors">
                    Save
                </button>
            </div>
        </form>
        
    </div>
</div>