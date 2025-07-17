<x-layouts.app>
    <div class="p-6 bg-gray-100 dark:bg-gray-900">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200 font-sans tracking-wide">Dashboard</h1>
            
        </div>

        

        @livewire('dashboard.dashboard-stats')

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 font-akatab tracking-widest">Daily work report HD Daman</h2>
            @livewire('dashboard.daily-report-dashboard')
        </div>
    </div>
</x-layouts.app>