<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.minimal')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink($this->only('email'));

        session()->flash('status', __('A reset link will be sent if the account exists.'));
    }
}; ?>

{{-- Perhatikan, div pembungkus dengan gradien telah dihapus. --}}
{{-- Kita mulai dengan div yang bertugas memusatkan kartu. --}}
<div class="flex items-center justify-center w-full h-full p-4">

    {{-- Ini adalah kartu kontennya --}}
    <div class="w-full max-w-xl p-6 space-y-6 bg-white/70 backdrop-blur-lg rounded-lg shadow-lg border border-gray-200/80">

        <div class="flex items-center justify-center gap-3">
            <p class="text-sm text-slate-800 dark:text-white">
                    Pembuatan akun dapat dilakukan melalui bot Telegram
                </p>
            <a href="https://t.me/kp_telkom_2025_bot" target="_blank"
                class="px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded-md shadow-sm hover:bg-blue-700 transition-colors">
                @KPMAGANGBOT
            </a>
        </div>
        <div class="flex items-center justify-center">
            <a href="{{ route('nik.verify') }}" wire:navigate
                class="px-4 py-1.5 text-sm font-semibold text-white bg-slate-700 rounded-md shadow-sm hover:bg-slate-800 transition-colors">
                Berikutnya
            </a>
        </div>
    </div>
</div>