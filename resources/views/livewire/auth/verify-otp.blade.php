<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// 1. Layout diubah ke 'minimal' agar tidak ada gambar gedung
new #[Layout('components.layouts.minimal')] class extends Component
{
    // 2. Properti untuk menampung kode OTP
    public string $otp_code = '';

    /**
     * Fungsi ini dijalankan saat tombol "Kirim" ditekan.
     */
    public function verifyOtp(): void
    {
        // 3. Validasi untuk memeriksa OTP
        $this->validate([
            'otp_code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        //
        // TODO: Implementasikan logika verifikasi OTP Anda di sini.
        // Cek apakah $this->otp_code cocok dengan yang ada di database atau cache.
        //
        // Jika berhasil, arahkan ke halaman ganti password.
        // Contoh: return $this->redirect('/ganti-password-baru', navigate: true);
        //
        // Jika gagal, tampilkan pesan error.
        // Contoh: $this->addError('otp_code', 'Kode OTP salah.');
        //
    }

    /**
     * Computed property untuk membuat tampilan OTP yang dinamis (misal: 1 2 3 * * *)
     */
    public function getMaskedOtpProperty(): string
    {
        $length = 6;
        $codeLength = strlen($this->otp_code);

        // Membuat string dengan angka yang sudah diinput, sisanya diisi '*'
        $masked = str_pad(substr($this->otp_code, 0, $codeLength), $length, '*');

        // Mengubah string "123***" menjadi "1 2 3 * * *"
        return implode(' ', str_split($masked));
    }
}; ?>

<!-- Tampilan Halaman OTP -->
<div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-100 via-rose-100 to-sky-200">

    <div class="w-full max-w-xl p-8 space-y-8 bg-white/70 backdrop-blur-lg rounded-xl shadow-lg border border-gray-200/80">

        <form wire:submit="verifyOtp">

            <div class="text-center">
                <p class="text-sm text-slate-800">
                    Silakan masukkan kode OTP yang telah dikirimkan melalui bot @nama_bot
                </p>
            </div>

            <!-- Bagian Input OTP -->
            <div class="py-8 relative">
                <!-- Input asli yang tidak terlihat, tempat pengguna mengetik -->
                <input
                    id="otp_input"
                    type="text"
                    wire:model.live="otp_code"
                    maxlength="6"
                    inputmode="numeric"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-text"
                    autofocus
                >

                <!-- Input palsu (label) yang terlihat, menampilkan bintang dan angka -->
                <label for="otp_input" class="block w-full px-4 py-3 text-center bg-gray-100/60 border-gray-300/80 rounded-lg shadow-inner text-3xl tracking-[0.5em] text-slate-800 cursor-text">
                    {{ $this->maskedOtp }}
                </label>

                @error('otp_code') <div class="mt-2 text-sm text-red-600 text-center">{{ $message }}</div> @enderror
            </div>
            
            <!-- ▼▼▼ BAGIAN TOMBOL YANG DIPERBARUI ▼▼▼ -->
            <div class="flex items-center justify-between">
                <!-- Tombol Batal di sebelah kiri -->
                <a href="{{ route('password.request') }}" wire:navigate
                   class="px-5 py-2 text-sm font-semibold text-white bg-gray-500 rounded-lg shadow-md hover:bg-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400">
                    Batal
                </a>

                <!-- Tombol Kirim di sebelah kanan -->
                <button type="submit"
                   class="px-5 py-2 text-sm font-semibold text-white bg-slate-800 rounded-lg shadow-md hover:bg-slate-900 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    Kirim
                </button>
            </div>

        </form>
    </div>
</div>
