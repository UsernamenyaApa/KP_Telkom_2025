<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800" x-data="{
            sidebarOpen: false,
            get darkMode() {
                return $flux.appearance === 'dark' || ($flux.appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            },
            toggleDarkMode() {
                $flux.appearance = this.darkMode ? 'light' : 'dark';
            }
        }" x-init="
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            $watch('$flux.appearance', (value) => {
                if (value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
        ">
        <flux:sidebar
            class="fixed inset-y-0 left-0 z-50 w-64 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            @click.outside="sidebarOpen = false"
        >
            <flux:sidebar.toggle icon="x-mark" @click="sidebarOpen = false" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platorm')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="exclamation-circle" :href="route('fallout-reports.index')" :current="request()->routeIs('fallout-reports.index')" wire:navigate>{{ __('Fallout Reports') }}</flux:navlist.item>
                    <flux:navlist.item icon="check-circle" :href="route('pelurusan.index')" :current="request()->routeIs('pelurusan.index')" wire:navigate>{{ __('Pelurusan') }}</flux:navlist.item>
                </flux:navlist.group>

                @if (auth()->user()->hasRole('super-admin'))
                    <flux:navlist.group :heading="__('Admin')" class="grid">
                        <flux:navlist.item icon="server" :href="route('hd-damans.index')" :current="request()->routeIs('hd-damans.index')" wire:navigate>HD Damans</flux:navlist.item>
                        <flux:navlist.item icon="list-bullet" :href="route('order-types.index')" :current="request()->routeIs('order-types.index')" wire:navigate>Order Types</flux:navlist.item>
                        <flux:navlist.item icon="exclamation-circle" :href="route('fallout-statuses.index')" :current="request()->routeIs('fallout-statuses.index')" wire:navigate>Fallout Statuses</flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        <flux:header class="fixed top-0 w-full z-40 bg-white dark:bg-zinc-800 shadow">
            <flux:sidebar.toggle icon="bars-2" inset="left" @click="sidebarOpen = true" />

            <flux:spacer />

            <livewire:notification-bell />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                >
                    <livewire:profile-photo-display />
                </flux:profile>

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <livewire:profile-photo-display />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->nik }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

            <div class="p-2">
                <button
                    type="button"
                    @click="toggleDarkMode()"
                    class="flex w-full items-center rounded-lg p-1 transition-colors duration-200"
                    :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                >
                    <span
                        class="flex w-full items-center justify-center rounded-md py-1.5 text-sm font-medium transition-all"
                        :class="{
                            'text-zinc-800': !darkMode,
                            'text-white': darkMode
                        }"
                    >
                        <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path d="M12 3V4M12 20V21M4 12H3M21 12H20M18.364 5.636L17.657 6.343M6.343 17.657L5.636 18.364M18.364 18.364L17.657 17.657M6.343 6.343L5.636 5.636M12 7C9.23858 7 7 9.23858 7 12C7 14.7614 9.23858 17 12 17C14.7614 17 17 14.7614 17 12C17 9.23858 14.7614 7 12 7Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </span>
                </button>
            </div>
        </flux:header>

                <div class="pt-16" :class="{'lg:ms-64': sidebarOpen, 'lg:ms-0': !sidebarOpen}">
            {{ $slot }}
        </div>

        @fluxScripts
        @livewireStyles
        @livewireScripts
    </body>
</html>