<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

// The layout is set to a new custom layout 'auth-split-screen'
// which should be created to accommodate the two-column design.
new #[Layout('components.layouts.auth.auth-split-screen')] class extends Component {
    // The validation is for 'nik'.
    #[Validate('required|string')]
    public string $nik = '';

    #[Validate('required|string')]
    public string $password = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        // The authentication logic now uses 'nik'.
        if (! Auth::attempt(['nik' => $this->nik, 'password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'nik' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'nik' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->nik).'|'.request()->ip());
    }
}; ?>

<div class="w-full max-w-md mx-auto">
    <div class="p-6 bg-slate-200/40 rounded-2xl border border-black/30 backdrop-blur-[2px]">
        <div class="text-center mb-4">
            <div class="w-100 h-14 text-center justify-center text-black/95 text-3xl font-bold font-['Mukta_Vaani'] tracking-[5.20px]">
                LOGIN
            </div>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login" class="flex flex-col gap-4">
            {{-- NIK Input --}}
            <div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        wire:model="nik"
                        id="nik"
                        name="nik"
                        type="text"
                        required
                        autofocus
                        placeholder="NIK"
                        class="block w-full pl-10 pr-3 py-3 bg-gray-100 border-transparent rounded-md shadow-[0px_4px_4px_0px_rgba(0,0,0,0.25)] focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>
                @error('nik') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            {{-- Password Input dengan Ikon Mata --}}
            <div x-data="{ showPassword: false }">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input
                        wire:model="password"
                        id="password"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="password"
                        class="block w-full pl-10 pr-10 py-3 bg-gray-100 border-transparent rounded-md shadow-[0px_4px_4px_0px_rgba(0,0,0,0.25)] focus:ring-blue-500 focus:border-blue-500"
                    />
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
                @error('password') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 shadow-[0px_4px_4px_0px_rgba(0,0,0,0.25)]">
                    Login
                </button>
            </div>

            <div>
                <div class="text-center text-sm mt-1">
                    <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                        Forget password
                    </a>
                </div>
                <div class="text-center text-sm text-gray-600 dark:text-white mt-0">
                    No account?
                    <a href="{{ route('register') }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                        Create one
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="mt-12 flex justify-center">
        <img src="{{ asset('images/infranexia logo (copy).png') }}" alt="Logo Infranexia by Telkom Indonesia" class="w-32 h-auto">
    </div>
</div>