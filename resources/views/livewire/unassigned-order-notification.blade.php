<div class="relative" x-data="{ open: false }" @click.away="open = false" wire:poll.5s="loadUnassignedReports">
    <button @click="open = !open" class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white focus:outline-none">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unassignedFalloutReportsCount > 0 || $unassignedPelurusanReportsCount > 0)
            <span class="absolute -top-1 -right-1 block h-3 w-3 rounded-full bg-red-500 ring-2 ring-white animate-pulse"></span>
        @endif
    </button>

    <div x-show="open" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 mt-2 w-96 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20 overflow-hidden border border-gray-200 dark:border-gray-700">
        
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Unassigned Orders</h3>
        </div>

        <div class="py-1 max-h-80 overflow-y-auto">
            @if ($unassignedFalloutReportsCount === 0 && $unassignedPelurusanReportsCount === 0)
                <div class="flex flex-col items-center justify-center p-6 text-center">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">No unassigned orders at the moment. All clear!</p>
                </div>
            @else
                @foreach ($unassignedFalloutReports as $report)
                    <a href="{{ route('fallout-reports.show', $report->id) }}" class="flex items-start px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition ease-in-out duration-150">
                        <div class="flex-shrink-0 mr-3">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div class="w-full">
                            <p class="font-semibold text-gray-800 dark:text-gray-200">New Fallout: {{ $report->order_id }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $report->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @endforeach

                @foreach ($unassignedPelurusanReports as $report)
                    <a href="{{ route('pelurusan-reports.show', $report->id) }}" class="flex items-start px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition ease-in-out duration-150">
                        <div class="flex-shrink-0 mr-3">
                           <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                         <div class="w-full">
                            <p class="font-semibold text-gray-800 dark:text-gray-200">New Pelurusan: {{ $report->order_id }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $report->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>
