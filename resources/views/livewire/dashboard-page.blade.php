<div class="min-h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Header Section -->
    <div class="p-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <!-- Left Side: Title -->
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 font-sans tracking-wide">Dashboard</h1>

            <!-- Right Side: Controls -->
            <div class="flex items-center space-x-3">
                <!-- Date Selector -->
                <div class="flex items-center space-x-1">
                    <label for="selectedDate" class="text-gray-700 dark:text-gray-300 text-xs">Date:</label>
                    <input type="date" id="selectedDate" wire:model.live="selectedDate" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-xs px-2 py-1 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Report Type Selector -->
                <div class="flex items-center space-x-1">
                    <label for="reportType" class="text-gray-700 dark:text-gray-300 text-xs">Report:</label>
                    <select id="reportType" wire:model.live="reportType" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md text-xs px-2 py-1 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="fallout">Fallout</option>
                        <option value="pelurusan">Pelurusan</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="p-4">
        @livewire('dashboard.dashboard-stats', ['selectedDate' => $selectedDate, 'reportType' => $reportType], key($selectedDate . $reportType . '-stats'))

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-3 mt-4">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2 font-akatab tracking-widest">Daily Work Report HD Daman</h2>
            @livewire('dashboard.daily-report-dashboard', ['selectedDate' => $selectedDate], key($selectedDate . $reportType . '-daily'))
        </div>
    </div>
</div>