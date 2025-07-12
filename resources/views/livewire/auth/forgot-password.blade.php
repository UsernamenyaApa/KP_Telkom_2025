<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

// Logika PHP untuk reset password tetap ada di file,
// meskipun tampilan saat ini tidak lagi menggunakan form input email.
new #[Layout('components.layouts.minimal')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

<div class="flex items-center justify-center min-h-screen bg-gradient-to-br from-blue-100 via-rose-100 to-sky-200">

    <div class="w-full max-w-xl p-6 space-y-6 bg-white/70 backdrop-blur-lg rounded-xl shadow-lg border border-gray-200/80">

        <div class="flex items-center justify-center gap-3">
            <p class="text-sm text-slate-800">
                Pembuatan akun dapat dilakukan melalui bot Telegram
            </p>
            <a href="https://t.me/nama_bot" target="_blank"
               class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md shadow-sm hover:bg-blue-700 transition-colors">
                @nama_bot
            </a>
        </div>

        <hr class="border-t border-gray-500/70 shadow-[0px_1px_5px_rgba(0,0,0,0.6)]">

        <div class="flex items-center justify-between">
            <a href="{{ route('login') }}" wire:navigate
               class="px-4 py-1.5 text-sm font-semibold text-white bg-gray-500 rounded-md shadow-sm hover:bg-gray-600 transition-colors">
                Kembali
            </a>
            <a href="{{ route('otp.verify') }}" wire:navigate
               class="px-4 py-1.5 text-sm font-semibold text-white bg-slate-700 rounded-md shadow-sm hover:bg-slate-800 transition-colors">
                Berikutnya
            </a>
        </div>
    </div>
</div>