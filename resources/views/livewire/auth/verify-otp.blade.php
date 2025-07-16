<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Layout diubah ke 'minimal' agar tidak ada gambar gedung
new #[Layout('components.layouts.minimal')] class extends Component
{
    // Properti untuk menampung kode OTP
    public string $otp_code = '';

    /**
     * Fungsi ini dijalankan saat tombol "Kirim" ditekan.
     */
    public function verifyOtp(): void
    {
        // 1. Validasi format input (harus 6 karakter)
        $this->validate([
            'otp_code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        // 2. Logika sementara untuk memeriksa OTP default
        if ($this->otp_code === '123456') {
            // Jika benar, arahkan ke halaman berikutnya (contoh: dashboard).
            // Anda bisa mengganti 'dashboard' dengan route lain jika perlu.
            $this->redirect(route('password.reset'), navigate: true);
        } else {
            // Jika salah, kosongkan input dan tampilkan pesan error.
            $this->reset('otp_code');
            $this->addError('otp_code', 'Kode OTP yang Anda masukkan salah. Masukkan "123456" untuk melanjutkan.');
        }
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

<div class="flex items-center justify-center w-full h-full p-4">

    <div class="w-full max-w-xl p-8 space-y-8 bg-zinc-300/70 rounded-2xl border border-black/20 dark:bg-gray-800">

        <form wire:submit="verifyOtp">

            <div class="text-center">
                <p class="text-sm text-slate-800">
                    Silakan masukkan kode OTP yang telah dikirimkan melalui bot @nama_bot
                </p>
            </div>

            <div class="py-8 relative">
                <input
                    id="otp_input"
                    type="text"
                    wire:model.live="otp_code"
                    maxlength="6"
                    inputmode="numeric"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-text"
                    autofocus
                >

                <div class="flex justify-center">
                    <label for="otp_input" class="block w-md px-4 py-3 text-center bg-gray-100/60 border-gray-300/80 rounded-lg shadow-inner text-2xl tracking-[0.5em] text-slate-800 cursor-text">
                        {{ $this->maskedOtp }}
                    </label>
                </div>

                @error('otp_code') <div class="mt-2 text-sm text-red-600 text-center">{{ $message }}</div> @enderror
            </div>
            
            <div class="flex items-center justify-end">
                <button type="submit"
                    class="px-5 py-2 text-sm font-semibold text-white bg-slate-800 rounded-lg shadow-md hover:bg-slate-900 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500">
                    Kirim
                </button>
            </div>

        </form>
    </div>
</div>