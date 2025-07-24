<div class="relative" x-data="{ open: false }" @click.away="open = false">
    <button @click="open = !open" class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white focus:outline-none">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if ($unassignedFalloutReportsCount > 0 || $unassignedPelurusanReportsCount > 0)
            <span class="absolute -top-1 -right-1 block h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
        @endif
    </button>

    <div x-show="open" class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg z-20 overflow-hidden">
        <div class="py-1">
            <div class="block px-4 py-2 text-xs text-gray-400">Unassigned Orders</div>

            @if ($unassignedFalloutReportsCount === 0 && $unassignedPelurusanReportsCount === 0)
                <div class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                    No unassigned orders.
                </div>
            @else
                @foreach ($unassignedFalloutReports as $report)
                    <a href="{{ route('fallout-reports.show', $report->id) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Fallout: {{ $report->order_id }} ({{ $report->created_at->diffForHumans() }})
                    </a>
                @endforeach

                @foreach ($unassignedPelurusanReports as $report)
                    <a href="{{ route('pelurusan-reports.show', $report->id) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Pelurusan: {{ $report->order_id }} ({{ $report->created_at->diffForHumans() }})
                    </a>
                @endforeach
            @endif
        </div>
    </div>
</div>