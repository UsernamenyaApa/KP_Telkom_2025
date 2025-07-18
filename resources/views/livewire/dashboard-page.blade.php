<div class="p-6 bg-gray-100 dark:bg-gray-900">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200 font-sans tracking-wide">Dashboard</h1>
    </div>

    <div class="flex items-center space-x-4 mb-6">
        <div class="flex items-center space-x-2">
            <label for="selectedDate" class="text-gray-700 dark:text-gray-300 text-sm">Date:</label>
            <input type="date" id="selectedDate" wire:model.live="selectedDate" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div class="flex items-center space-x-2">
            <label for="reportType" class="text-gray-700 dark:text-gray-300 text-sm">Report Type:</label>
            <select id="reportType" wire:model.live="reportType" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm px-3 py-2 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="fallout">Fallout Report</option>
                <option value="pelurusan">Pelurusan Report</option>
            </select>
        </div>
    </div>

    @livewire('dashboard.dashboard-stats', ['selectedDate' => $selectedDate, 'reportType' => $reportType], key($selectedDate . $reportType . '-stats'))

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mt-6">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 font-akatab tracking-widest">Daily work report HD Daman</h2>
        @livewire('dashboard.daily-report-dashboard', ['selectedDate' => $selectedDate], key($selectedDate . $reportType . '-daily'))
    </div>
</div>
