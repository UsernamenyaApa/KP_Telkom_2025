<?php

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.minimal')] class extends Component
{
    public int $userId;
    public string $otp = '';

    public function mount($userId): void
    {
        $this->userId = $userId;
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $cachedOtp = Cache::get('otp_for_user_' . $this->userId);

        if (!$cachedOtp || $this->otp !== (string) $cachedOtp) {
            $this->addError('otp', 'Kode OTP tidak valid atau telah kedaluwarsa.');
            return;
        }

        // Clear the OTP from cache
        Cache::forget('otp_for_user_' . $this->userId);

        // Store user ID in session to indicate OTP verification success
        session()->put('otp_verified_user_id', $this->userId);

        // Redirect to the password reset page
        $this->redirect(route('password.reset'), navigate: true);
    }
};
?>

<div class="flex items-center justify-center w-full h-full p-4">
    <div class="w-full max-w-md p-8 space-y-6 bg-gray-200/50 backdrop-blur-sm rounded-xl shadow-lg border border-gray-300/60">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-900">Verifikasi OTP</h2>
            <p class="mt-2 text-sm text-slate-700">Masukkan kode OTP yang telah kami kirim ke akun Telegram Anda.</p>
        </div>

        <form wire:submit="verifyOtp" class="space-y-6">
            <div>
                <label for="otp" class="block text-sm font-medium text-slate-700">Kode OTP</label>
                <div class="mt-1">
                    <input wire:model="otp" id="otp" name="otp" type="text" required
                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                @error('otp')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit"
                    class="flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-slate-800 border border-transparent rounded-md shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-700 transition-colors">
                    Verifikasi
                </button>
            </div>
        </form>
    </div>
</div>
