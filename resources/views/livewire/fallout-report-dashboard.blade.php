<div>
    <div class="p-6 min-h-screen">
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Fallout Report Dashboard
                    </h1>
                    <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                        A list of all the fallout reports from the field
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4" x-data="{ openFilter: false }">
                    <div class="relative min-w-80">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search Incident Ticket or Order ID..." 
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
                    
                    <div class="relative">
                        <button 
                            @click="openFilter = !openFilter"
                            class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all duration-200"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span>Filters</span>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': openFilter}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div 
                            x-show="openFilter" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            @click.away="openFilter = false" 
                            class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl z-50 border border-gray-200 dark:border-gray-700 overflow-hidden"
                        >
                            <div class="p-6 space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filter Options</h3>
                                    <button @click="openFilter = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Order Type</label>
                                        <select wire:model.live="selectedOrderType" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">All Types</option>
                                            @foreach($orderTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Fallout</label>
                                        <select wire:model.live="selectedFalloutStatus" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">All Status</option>
                                            @foreach($falloutStatuses as $status)
                                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned To</label>
                                        <select wire:model.live="selectedAssignedTo" class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="">All Assignees</option>
                                            @foreach($assignedToUsers as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <button wire:click="resetFilters" @click="openFilter = false" class="flex-1 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200">
                                        Reset Filters
                                    </button>
                                    <button @click="openFilter = false" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Skeleton Loading Screen --}}
        <div wire:loading wire:target="search, date" class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden animate-pulse">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-blue-800 dark:bg-blue-900">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Incident Ticket</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Tipe Order</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Status Fallout</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Assigned To</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Create</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Take</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Complete</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Last Updated</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Duration</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold text-white uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-8"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-28"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-20"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-28"></div></td>
                                <td class="px-6 py-4"><div class="h-6 bg-gray-300 dark:bg-gray-600 rounded-full w-20"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-32"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-32"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-32"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-32"></div></td>
                                <td class="px-6 py-4"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div></td>
                                <td class="px-6 py-4 text-right"><div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16 ml-auto"></div></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
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
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Incident Ticket</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Tipe Order</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order ID</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Status Fallout</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Assigned To</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Create</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Take</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Order Complete</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Last Updated</th>
                                <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-white uppercase tracking-wider">Duration</th>
                                <th scope="col" class="px-6 py-4 text-right text-sm font-semibold text-white uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($reports as $report)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $loop->index + $reports->firstItem() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-900 dark:text-white break-words">{{ $report->incident_ticket }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 dark:text-gray-300">{{ $report->orderType?->name }}</div>
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
                                                'eskalasi' => 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/30 dark:text-orange-300',
                                                'Done' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300',
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $report->taken_at ? $report->taken_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $report->completed_at ? $report->completed_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $report->updated_at->format('d M Y, H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $report->taken_at && $report->completed_at ? $report->completed_at->diffForHumans($report->taken_at, true) : '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('fallout-reports.show', ['id' => $report->id, 'date' => $date]) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium transition-colors duration-150">View Details</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-6 py-12 text-center">
                                        <div class="text-gray-500 dark:text-gray-400">
                                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="text-sm font-medium">No fallout reports found</p>
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