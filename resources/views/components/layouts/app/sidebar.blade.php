<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ userTheme: '{{ auth()->user()->theme_color ?? 'default' }}' }" x-bind:data-theme="userTheme">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-theme-primary dark:bg-[#131518]" x-data="{
        sidebarOpen: false,
        get darkMode() {
            return $flux.appearance === 'dark' || ($flux.appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        },
        toggleDarkMode() {
            $flux.appearance = this.darkMode ? 'light' : 'dark';
        },
        handleSavedEvent(appearance, themeColor) {
            this.userTheme = themeColor;
            if (typeof $flux !== 'undefined' && $flux.appearance !== appearance) {
                $flux.appearance = appearance;
            }
            if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
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
        $watch('userTheme', (value) => {
            document.documentElement.setAttribute('data-theme', value);
        });
    " x-on:saved.window="handleSavedEvent($event.detail.appearance, $event.detail.themeColor)">
    <flux:sidebar
        class="fixed inset-y-0 left-0 z-50 w-64 border-e border-zinc-200 bg-theme-primary dark:border-zinc-700 dark:bg-[#131518]"
        x-show="sidebarOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        @click.outside="sidebarOpen = false"
    >
        <!-- Header Sidebar dengan Logo dan Close Button -->
        <div class="flex items-center justify-between px-4 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center space-x-3">
                <div>
                    <x-app-logo class="h-7 w-7 text-gray-900 dark:text-white drop-shadow-lg translate-y-0.5" />
                </div>
                <a href="{{ route('dashboard') }}" class="flex items-center text-lg font-semibold text-gray-900 dark:text-white hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200" wire:navigate>
                    Infranexia
                </a>
            </div>
            <button 
                @click="sidebarOpen = false" 
                class="flex items-center justify-center w-8 h-8 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition-colors duration-200"
                aria-label="Close sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="px-4 pb-4 mt-4">
            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid [&_h3]:text-zinc-500 [&_h3]:font-semibold [&_h3]:text-sm [&_h3]:uppercase [&_h3]:tracking-wider">
                    <flux:navlist.item 
                        icon="home" 
                        :href="route('dashboard')" 
                        :current="request()->routeIs('dashboard')" 
                        wire:navigate
                        class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                    >
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item 
                        icon="exclamation-circle" 
                        :href="route('fallout-reports.index')" 
                        :current="request()->routeIs('fallout-reports.index')" 
                        wire:navigate
                        class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                    >
                        {{ __('Fallout Reports') }}
                    </flux:navlist.item>
                    <flux:navlist.item 
                        icon="check-circle" 
                        :href="route('pelurusan.index')" 
                        :current="request()->routeIs('pelurusan.index')" 
                        wire:navigate
                        class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                    >
                        {{ __('Pelurusan') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                @if (auth()->user()->hasRole('super-admin'))
                    <flux:navlist.group :heading="__('Admin')" class="grid [&_h3]:text-zinc-500 [&_h3]:font-semibold [&_h3]:text-sm [&_h3]:uppercase [&_h3]:tracking-wider [&_h3]:mt-6">
                        <flux:navlist.item 
                            icon="server" 
                            :href="route('hd-damans.index')" 
                            :current="request()->routeIs('hd-damans.index')" 
                            wire:navigate
                            class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                        >
                            HD Damans
                        </flux:navlist.item>
                        <flux:navlist.item 
                            icon="list-bullet" 
                            :href="route('order-types.index')" 
                            :current="request()->routeIs('order-types.index')" 
                            wire:navigate
                            class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                        >
                            Order Types
                        </flux:navlist.item>
                        <flux:navlist.item 
                            icon="exclamation-circle" 
                            :href="route('fallout-statuses.index')" 
                            :current="request()->routeIs('fallout-statuses.index')" 
                            wire:navigate
                            class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-zinc-700 [&.flux-current]:shadow-inner"
                        >
                            Fallout Statuses
                        </flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item 
                    icon="folder-git-2" 
                    href="https://github.com/laravel/livewire-starter-kit" 
                    target="_blank"
                    class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg"
                >
                    {{ __('Repository') }}
                </flux:navlist.item>
                <flux:navlist.item 
                    icon="book-open-text" 
                    href="https://laravel.com/docs/starter-kits#livewire" 
                    target="_blank"
                    class="text-theme-text hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all duration-200 rounded-lg"
                >
                    {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </div>
    </flux:sidebar>

    <flux:header class="fixed top-0 w-full z-40 shadow-lg border-b border-gray-200 dark:border-zinc-700 bg-theme-primary dark:bg-[#131518]">
        <flux:sidebar.toggle icon="bars-2" inset="left" @click="sidebarOpen = true" class="text-theme-text hover:text-zinc-700 dark:hover:text-zinc-300" />

        <flux:spacer />

        <div class="flex items-center space-x-3 me-3">
            <livewire:notification-bell />

            <flux:dropdown position="top" align="end">
                <button class="flex items-center space-x-2">
                    <img class="h-8 w-8 rounded-full object-cover" src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" />
                    <span class="text-sm font-medium text-theme-text">{{ auth()->user()->name }}</span>
                </button>

                <flux:menu class="bg-theme-primary dark:bg-[#131518] border-gray-200 dark:border-zinc-600">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    @if (auth()->user()->profile_photo_path)
                                        <img class="h-full w-full object-cover" src="{{ auth()->user()->profilePhotoUrl() }}" alt="{{ auth()->user()->name }}" />
                                    @else
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-blue-100 text-blue-800 dark:bg-zinc-600 dark:text-zinc-100 font-semibold">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    @endif
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold text-theme-text">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-theme-text-light">{{ auth()->user()->nik }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate class="text-theme-text hover:bg-theme-secondary dark:hover:bg-zinc-600">{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-zinc-700">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

            <div class="p-2">
                <button
                    type="button"
                    @click="toggleDarkMode()"
                    class="flex items-center justify-center w-10 h-10 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-zinc-700"
                    :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                >
                    <span x-show="!darkMode" class="flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-theme-accent">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                    </span>
                    <span x-show="darkMode" x-cloak class="flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-theme-accent">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </flux:header>

    <div class="bg-theme-primary dark:bg-[#131518] pt-16 min-h-screen" :class="{'lg:ms-64': sidebarOpen, 'lg:ms-0': !sidebarOpen}">
        {{ $slot }}
    </div>

    @fluxScripts
    @livewireStyles
    @livewireScripts
</body>
</html>