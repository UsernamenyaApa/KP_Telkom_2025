<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Menentukan file layout utama. Diasumsikan layout ini sudah ada
// dan memiliki background yang sesuai (misalnya, gradien biru).
new #[Layout('components.layouts.minimal')] class extends Component
{
    /**
     * Properti untuk menampung data NIK dari input form.
     * `wire:model="nik"` akan mengikat data ke properti ini secara real-time.
     */
    public string $nik = '';

    /**
     * Method yang akan dipanggil saat form di-submit (`wire:submit`).
     * Logika di dalamnya diatur untuk hanya menerima NIK default "nik123"
     * sebagai nilai yang valid untuk keperluan pengembangan.
     */
    public function verifyNik(): void
    {
        // Memeriksa apakah NIK yang diinput adalah "nik123"
        if ($this->nik === 'nik123') {
            // Jika benar, lanjutkan ke halaman berikutnya.
            // Arahkan ke route utama '/' sebagai contoh.
            $this->redirect(route('otp.verify'), navigate: true);
        } else {
            // Jika salah, bersihkan input dan tampilkan pesan error.
            $this->reset('nik');
            $this->addError('nik', 'NIK tidak valid. Masukkan "nik123" untuk melanjutkan.');
        }
    }
}; ?>

{{-- Bagian ini adalah struktur HTML dan tampilan halaman --}}
<div class="flex items-center justify-center w-full h-full p-4">

    {{-- Kartu atau panel utama di tengah layar --}}
    <div class="w-full max-w-md p-8 space-y-6 bg-gray-200/50 backdrop-blur-sm rounded-xl shadow-lg border border-gray-300/60">

        {{-- Form yang terhubung dengan Livewire --}}
        <form wire:submit="verifyNik" class="space-y-6">
            @csrf
            
            <p class="text-center text-slate-800">
                Masukkan NIK Anda untuk melanjutkan proses verifikasi.
            </p>

            {{-- Bagian Input NIK --}}
            <div>
                <label for="nik" class="block text-sm font-medium text-slate-700">
                    NIK
                </label>
                <div class="mt-1">
                    <input 
                        wire:model="nik" 
                        id="nik" 
                        name="nik" 
                        type="text"
                        placeholder="Masukkan NIK" 
                        required
                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                
                {{-- Menampilkan pesan error jika validasi gagal --}}
                @error('nik')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tombol untuk submit form --}}
            <div>
                <button type="submit"
                    class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-slate-800 border border-transparent rounded-md shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-700 transition-colors">
                    Kirim
                </button>
            </div>
        </form>
        
    </div>
</div>