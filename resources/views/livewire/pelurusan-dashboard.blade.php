<div>
    <div class="p-6 min-h-screen">
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Pelurusan Report Dashboard
                    </h1>
                    <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                        A list of all the pelurusan reports from the field
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="relative min-w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search Pelurusan Code or Order ID..." 
                            class="block w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        >
                    </div>
                    
                    <div class="relative">
                        <input 
                            type="date" 
                            wire:model.live="date"
                            class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        >
                    </div>
                </div>
            </div>
        </div>

        {{-- Actual Table Content --}}
        <div wire:loading.class="hidden" wire:target="search, date">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-blue-800 dark:bg-blue-900">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">No</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Pelurusan Code</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order ID</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Assigned To</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Created At</th>
                                <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-white uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($reports as $report)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $loop->index + $reports->firstItem() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-900 dark:text-white break-words">{{ $report->pelurusan_code }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-900 dark:text-white break-words">{{ $report->order_id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusName = $report->falloutStatus?->name;
                                            $badgeClasses = match($statusName) {
                                                'Open' => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300',
                                                'OnProgress' => 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                'input ulang' => 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300',
                                                default => 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-700/30 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border {{ $badgeClasses }}">
                                            {{ $statusName }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8">
                                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                                                    {{ substr($report->assignedToUser?->name ?? '', 0, 1) }}{{ substr(explode(' ', $report->assignedToUser?->name ?? '')[1] ?? '', 0, 1) }}
                                                </div>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm text-gray-900 dark:text-gray-300">{{ $report->assignedToUser?->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $report->created_at->format('d M Y, H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('pelurusan-reports.show', ['id' => $report->id, 'date' => $date]) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition-colors duration-150">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-sm font-medium">No pelurusan reports found</p>
                                            <p class="text-sm">Try adjusting your search or filter criteria</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-white dark:bg-gray-900 px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Show {{ $reports->firstItem() }} to {{ $reports->lastItem() }} from {{ $reports->total() }} data
                    </div>
                    <div>
                        {{ $reports->links('livewire.pagination-links') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>