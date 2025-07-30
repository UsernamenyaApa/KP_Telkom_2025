<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800">
        <flux:header container class="border-b border-gray-200 bg-white dark:bg-gradient-to-br dark:from-blue-900 dark:to-blue-800 dark:border-blue-600 shadow-lg">
            <flux:sidebar.toggle class="lg:hidden text-gray-900 dark:text-white hover:text-blue-500 dark:hover:text-blue-200" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-blue-700" wire:navigate>
                <x-app-logo class="text-gray-900 dark:text-white drop-shadow-lg" />
            </a>

            <flux:navbar class="-mb-px max-lg:hidden [&_*]:text-gray-900 [&_*]:dark:text-white">
                <flux:navbar.item 
                    icon="layout-grid" 
                    :href="route('dashboard')" 
                    :current="request()->routeIs('dashboard')" 
                    wire:navigate
                    class="text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-blue-700 transition-all duration-200 rounded-lg border-b-2 border-transparent [&.flux-current]:border-blue-500 dark:[&.flux-current]:border-blue-200 [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-blue-700 [&.flux-current]:bg-opacity-30"
                >
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <div class="flex items-center space-x-3 me-3">
                <livewire:unassigned-order-notification wire:poll.10s />

                <!-- Desktop User Menu -->
                <flux:dropdown position="top" align="end">
                    <flux:profile
                        class="cursor-pointer text-gray-900 dark:text-white border-blue-400 hover:border-blue-300 transition-all duration-200"
                        :initials="auth()->user()->initials()"
                    />

                    <flux:menu class="bg-white dark:bg-blue-800 border-blue-200 dark:border-blue-600 shadow-xl">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="flex h-full w-full items-center justify-center rounded-lg bg-blue-100 text-blue-800 dark:bg-blue-700 dark:text-blue-100 font-semibold"
                                        >
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs text-gray-600 dark:text-blue-200">{{ auth()->user()->nik }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate class="text-gray-700 dark:text-white hover:bg-blue-50 dark:hover:bg-blue-700 transition-colors duration-200">{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900 dark:hover:bg-opacity-20 transition-colors duration-200">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>

                <div class="p-2">
                    <button
                        type="button"
                        @click="toggleDarkMode()"
                        class="flex items-center justify-center w-10 h-10 rounded-lg transition-colors duration-200 hover:bg-gray-100 dark:hover:bg-blue-700"
                        :aria-label="darkMode ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                    >
                        <span x-show="!darkMode" class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-yellow-500">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                            </svg>
                        </span>
                        <span x-show="darkMode" x-cloak class="flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-blue-200">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-blue-200 bg-gradient-to-b from-blue-600 to-blue-700 dark:border-blue-600 dark:from-blue-800 dark:to-blue-900 shadow-xl">
            <flux:sidebar.toggle class="lg:hidden text-gray-900 dark:text-white hover:text-blue-500 dark:hover:text-blue-200" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-1 flex items-center space-x-2 rtl:space-x-reverse mb-6 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-blue-700" wire:navigate>
                <x-app-logo class="text-gray-900 dark:text-white drop-shadow-lg" />
            </a>

            <flux:navlist variant="outline" class="[&_*]:text-gray-900 [&_*]:dark:text-white [&_*]:border-blue-400">
                <flux:navlist.group :heading="__('Platform')" class="[&_h3]:text-blue-200 [&_h3]:font-semibold [&_h3]:text-sm [&_h3]:uppercase [&_h3]:tracking-wider">
                    <flux:navlist.item 
                        icon="layout-grid" 
                        :href="route('dashboard')" 
                        :current="request()->routeIs('dashboard')" 
                        wire:navigate
                        class="text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-blue-700 transition-all duration-200 rounded-lg [&.flux-current]:bg-gray-100 dark:[&.flux-current]:bg-blue-700 [&.flux-current]:bg-opacity-70 [&.flux-current]:shadow-inner"
                    >
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline" class="[&_*]:text-gray-900 [&_*]:dark:text-white [&_*]:border-blue-400">
                <flux:navlist.item 
                    icon="folder-git-2" 
                    href="https://github.com/laravel/livewire-starter-kit" 
                    target="_blank"
                    class="text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-blue-700 transition-all duration-200 rounded-lg"
                >
                    {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item 
                    icon="book-open-text" 
                    href="https://laravel.com/docs/starter-kits#livewire" 
                    target="_blank"
                    class="text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-blue-700 transition-all duration-200 rounded-lg"
                >
                    {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>