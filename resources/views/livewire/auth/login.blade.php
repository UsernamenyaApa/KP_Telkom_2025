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
new #[Layout('components.layouts.auth')] class extends Component {
    // The validation remains for 'email' as requested, even though the placeholder is 'NIK'.
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        // The authentication logic still uses 'email'. The user should enter their email
        // in the NIK field for the login to work.
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
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
            'email' => __('auth.throttle', [
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
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<!-- Import Google Font for Myanmar Khyay -->
<!-- <style>
    @import url('https://fonts.googleapis.com/css2?family=Myanmar+Khyay&display=swap');
</style> -->

<div class="w-full max-w-sm mx-auto">
    <!-- Updated h2 tag with the new font -->
    <h2 class="text-center text-3xl font-bold tracking-wider text-gray-800 mb-8 [text-shadow:0_4px_4px_rgba(0,0,0,0.25)]" style="font-family: 'Myanmar Khyay', sans-serif;">
        LOGIN USER
    </h2>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input
                    wire:model="email"
                    id="email"
                    name="email"
                    type="email"
                    required
                    autofocus
                    placeholder="email@example.com"
                    class="block w-full pl-10 pr-3 py-3 bg-gray-100 border-transparent rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            @error('email') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
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
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="masukkan password"
                    class="block w-full pl-10 pr-3 py-3 bg-gray-100 border-transparent rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
            </div>
            @error('password') <span class="mt-1 text-sm text-red-600">{{ $message }}</span> @enderror
        </div>

        <div>
            <button type="submit" class="w-full px-4 py-3 bg-zinc-700 hover:bg-zinc-800 text-white font-medium rounded-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-zinc-600 transition duration-150 shadow-[0_5px_15px_rgba(0,0,0,0.2)]">
                LOGIN
            </button>
        </div>

        <div class="text-center text-sm">
            <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-blue-600 hover:underline">
                Reset password
            </a>
        </div>
        <div class="text-center text-sm text-gray-600">
            No account?
            <a href="{{ route('register') }}" wire:navigate class="font-medium text-blue-600 hover:underline">
                Create one
            </a>
        </div>
    </form>

    <div class="mt-12 flex justify-center">
        <img src="{{ asset('images/infranexia logo (copy).png') }}" alt="Logo Infranexia by Telkom Indonesia" class="w-40 h-auto">
    </div>
</div>
