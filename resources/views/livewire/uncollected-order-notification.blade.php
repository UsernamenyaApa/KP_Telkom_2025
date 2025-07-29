<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <!-- Uncollected Orders Link -->
    <button @click="open = !open" class="relative flex items-center justify-center px-4 py-2 rounded-lg bg-gray-50 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-all duration-300 font-medium text-sm">
        {{ __('Uncollected Orders') }}: {{ $uncollectedCount }}
        @if ($newUncollectedCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[1.5rem] h-5 px-2 py-1 text-xs font-semibold text-black bg-red-500 rounded-full shadow-md ring-2 ring-white dark:ring-gray-800">
                {{ $newUncollectedCount > 99 ? '99+' : $newUncollectedCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown (Optional, for future expansion) -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-xl z-50 overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                {{ __('Uncollected Orders') }}
            </h3>
        </div>
        <div class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('View uncollected orders in the dashboard.') }}
        </div>
    </div>
</div>