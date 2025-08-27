<div class="flex min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- Sidebar --}}
    <x-layouts.app.sidebar :title="$title ?? null" />

    {{-- Main Content --}}
    <div class="flex-1 p-6">
        <flux:main class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 text-gray-900 dark:text-gray-100">
            {{ $slot }}
        </flux:main>
    </div>
</div>
