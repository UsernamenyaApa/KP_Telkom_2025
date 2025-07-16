<?php

use App\Models\User;
use App\Jobs\SendTelegramNotificationJob;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.minimal')] class extends Component
{
    public string $nik = '';

    public function verifyNik(): void
    {
        $this->validate([
            'nik' => ['required', 'string', 'exists:users,nik'],
        ]);

        $user = User::where('nik', $this->nik)->first();

        if (!$user || !$user->telegram_user_id) {
            $this->addError('nik', 'User tidak ditemukan atau belum terhubung dengan Telegram.');
            return;
        }

        // Generate a 6-digit OTP
        $otp = random_int(100000, 999999);

        // Store OTP in cache for 5 minutes, associated with the user's ID
        Cache::put('otp_for_user_' . $user->id, $otp, now()->addMinutes(5));

        // Send OTP to user via Telegram
        $message = "Kode OTP untuk reset password Anda adalah: *{$otp}*.\nJangan berikan kode ini kepada siapa pun.";
        SendTelegramNotificationJob::dispatch($user->telegram_user_id, $message);

        // Redirect to OTP verification page, passing user ID
        $this->redirect(route('password.verify-otp', ['userId' => $user->id]), navigate: true);
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