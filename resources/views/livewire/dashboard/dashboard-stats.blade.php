<div>
    <div class="flex justify-start items-center mb-6">
        <div class="relative">
            <select wire:model.live="reportType" class="block appearance-none w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:border-gray-400 px-4 py-2 pr-8 rounded-md shadow-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-gray-800 dark:text-gray-200">
                <option value="fallout">Fallout Reports</option>
                <option value="pelurusan">Pelurusan Reports</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- OPEN Card -->
        <div wire:loading.remove wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['open'] }} Order</p>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">OPEN</h3>
                </div>
                <div class="text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index') : route('pelurusan.index') }}" class="text-sm text-blue-500 dark:text-blue-400 underline mt-4 block font-akatab tracking-wider">view details</a>
        </div>
        {{-- Skeleton for OPEN Card --}}
        <div wire:loading wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 animate-pulse">
            <div class="flex justify-between items-start">
                <div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mb-4"></div>
                </div>
                <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
            </div>
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full mt-4"></div>
        </div>

        <!-- PROGRES Card -->
        <div wire:loading.remove wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['progress'] }} Order</p>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">PROGRES</h3>
                </div>
                <div class="text-yellow-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            </div>
            <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index') : route('pelurusan.index') }}" class="text-sm text-yellow-500 dark:text-yellow-400 underline mt-4 block font-akatab tracking-wider">view details</a>
        </div>
        {{-- Skeleton for PROGRES Card --}}
        <div wire:loading wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 animate-pulse">
            <div class="flex justify-between items-start">
                <div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mb-4"></div>
                </div>
                <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
            </div>
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full mt-4"></div>
        </div>

        <!-- ESKALASI Card -->
        <div wire:loading.remove wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['eskalasi'] }} Order</p>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">ESKALASI</h3>
                </div>
                <div class="text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                </div>
            </div>
            <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index') : route('pelurusan.index') }}" class="text-sm text-red-500 dark:text-red-400 underline mt-4 block font-akatab tracking-wider">view details</a>
        </div>
        {{-- Skeleton for ESKALASI Card --}}
        <div wire:loading wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 animate-pulse">
            <div class="flex justify-between items-start">
                <div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mb-4"></div>
                </div>
                <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
            </div>
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full mt-4"></div>
        </div>

        <!-- CLOSE Card -->
        <div wire:loading.remove wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['close'] }} Order</p>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">CLOSE</h3>
                </div>
                <div class="text-green-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index') : route('pelurusan.index') }}" class="text-sm text-green-500 dark:text-green-400 underline mt-4 block font-akatab tracking-wider">view details</a>
        </div>
        {{-- Skeleton for CLOSE Card --}}
        <div wire:loading wire:target="reportType" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 animate-pulse">
            <div class="flex justify-between items-start">
                <div>
                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mb-4"></div>
                </div>
                <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
            </div>
            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full mt-4"></div>
        </div>
    </div>
</div>