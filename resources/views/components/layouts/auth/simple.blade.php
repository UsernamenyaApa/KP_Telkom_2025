<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-neutral-950">
        <div class="relative grid min-h-svh w-full grid-cols-1 md:grid-cols-2">
            <div class="relative hidden flex-col justify-between bg-neutral-900 p-10 text-white md:flex">
                <a href="{{ route('dashboard') }}" class="z-10 flex items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo-icon class="size-9 fill-current" />
                    <span class="text-lg">{{ config('app.name', 'Laravel') }}</span>
                </a>

                <div class="z-10 my-auto w-full max-w-md">
                    <h1 class="text-3xl font-bold">
                        HALOOOOOO!
                    </h1>
                    <p class="mt-2 text-neutral-400">
                        Log in to access your dashboard and manage your fallout reports.
                    </p>
                </div>

                <div class="z-10">
                    &copy; {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.
                </div>

                <x-placeholder-pattern />
            </div>
            <div class="bg-background flex flex-col items-center justify-center gap-6 p-6 md:p-10">
                <div class="flex w-full max-w-sm flex-col gap-6 rounded-xl border bg-white p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>