<div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Card Wrapper -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <!-- Content -->
            <div wire:loading.remove>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['open'] }} Order</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">OPEN</h3>
                    </div>
                    <div class="text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" /></svg>
                    </div>
                </div>
                <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index', ['selectedFalloutStatus' => 1, 'date' => $selectedDate]) : route('pelurusan.index') }}" class="text-sm text-blue-500 dark:text-blue-400 underline mt-4 block font-akatab tracking-wider">view details</a>
            </div>
            <!-- Skeleton -->
            <div wire:loading class="animate-pulse">
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

        <!-- Card Wrapper -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <!-- Content -->
            <div wire:loading.remove>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['progress'] }} Order</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">PROGRES</h3>
                    </div>
                    <div class="text-yellow-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                </div>
                <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index', ['selectedFalloutStatus' => 2, 'date' => $selectedDate]) : route('pelurusan.index') }}" class="text-sm text-yellow-500 dark:text-yellow-400 underline mt-4 block font-akatab tracking-wider">view details</a>
            </div>
            <!-- Skeleton -->
            <div wire:loading class="animate-pulse">
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

        <!-- Card Wrapper -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <!-- Content -->
            <div wire:loading.remove>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['eskalasi'] }} Order</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">ESKALASI</h3>
                    </div>
                    <div class="text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" /></svg>
                    </div>
                </div>
                <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index', ['selectedFalloutStatus' => 4, 'date' => $selectedDate]) : route('pelurusan.index') }}" class="text-sm text-red-500 dark:text-red-400 underline mt-4 block font-akatab tracking-wider">view details</a>
            </div>
            <!-- Skeleton -->
            <div wire:loading class="animate-pulse">
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

        <!-- Card Wrapper -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <!-- Content -->
            <div wire:loading.remove>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400 font-akatab tracking-wider">{{ $stats['close'] }} Order</p>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 font-akatab tracking-wider">CLOSE</h3>
                    </div>
                    <div class="text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                </div>
                <a href="{{ $reportType === 'fallout' ? route('fallout-reports.index', ['selectedFalloutStatus' => 7, 'date' => $selectedDate]) : route('pelurusan.index') }}" class="text-sm text-green-500 dark:text-green-400 underline mt-4 block font-akatab tracking-wider">view details</a>
            </div>
            <!-- Skeleton -->
            <div wire:loading class="animate-pulse">
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
</div>