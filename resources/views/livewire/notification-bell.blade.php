<div class="relative" x-data="{ open: false, filter: 'all' }" @click.outside="open = false" wire:ignore.self x-init="
    () => {
        setInterval(() => {
            $wire.dispatch('checkNotifications');
        }, 5000);
    }
">
    <!-- Clean Notification Button -->
    <button @click="open = !open" class="relative group">
        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-white hover:bg-gray-50 border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 group-hover:scale-105">
            <!-- Bell Icon -->
            <svg class="w-5 h-5 text-gray-600 hover:text-gray-800 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4.732-5.854A2 2 0 009.268 5.146 6.002 6.002 0 004.732 11v3.159c0 .538-.214 1.055-.595 1.436L2 17h5m8 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
        </div>

        <!-- Clean Notification Badge -->
        @if($totalUnassignedCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-xs font-bold text-white bg-red-500 rounded-full shadow-sm ring-2 ring-white">
                {{ $totalUnassignedCount > 99 ? '99+' : $totalUnassignedCount }}
            </span>
        @endif
    </button>

    <!-- Clean Notification Dropdown -->
    <div x-show="open" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 overflow-hidden border border-gray-200">
        
        <!-- Clean Header -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 flex items-center">
                    <div class="w-6 h-6 bg-blue-500 rounded-md flex items-center justify-center mr-2">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4.732-5.854A2 2 0 009.268 5.146 6.002 6.002 0 004.732 11v3.159c0 .538-.214 1.055-.595 1.436L2 17h5m8 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    Unassigned Reports
                </h3>
                <span class="text-xs text-gray-500 font-medium">{{ $totalUnassignedCount }} items</span>
            </div>
            
            <!-- Simple Filter Tabs -->
            <div class="flex space-x-1 mt-3 bg-white rounded-md p-1 border border-gray-200">
                <button @click="filter = 'all'" 
                        :class="{ 'bg-blue-500 text-white': filter === 'all', 'text-gray-600 hover:text-gray-800 hover:bg-gray-50': filter !== 'all' }" 
                        class="flex-1 px-2 py-1.5 rounded text-xs font-medium transition-all duration-200 text-center">
                    All
                </button>
                <button @click="filter = 'fallout'" 
                        :class="{ 'bg-red-500 text-white': filter === 'fallout', 'text-gray-600 hover:text-gray-800 hover:bg-gray-50': filter !== 'fallout' }" 
                        class="flex-1 px-2 py-1.5 rounded text-xs font-medium transition-all duration-200 text-center">
                    Fallout
                </button>
                <button @click="filter = 'pelurusan'" 
                        :class="{ 'bg-green-500 text-white': filter === 'pelurusan', 'text-gray-600 hover:text-gray-800 hover:bg-gray-50': filter !== 'pelurusan' }" 
                        class="flex-1 px-2 py-1.5 rounded text-xs font-medium transition-all duration-200 text-center">
                    Pelurusan
                </button>
            </div>
        </div>
        
        <!-- Simple Notifications List -->
        <div class="max-h-80 overflow-y-auto">
            @if(count($unassignedFalloutReports) > 0 || count($unassignedPelurusanReports) > 0)
                
                <!-- Fallout Reports -->
                <div x-show="filter === 'all' || filter === 'fallout'">
                    @foreach($unassignedFalloutReports as $report)
                        <a href="{{ route('fallout-reports.show', $report->id) }}" 
                           class="flex items-center px-4 py-3 hover:bg-red-50 border-b border-gray-100 last:border-b-0 group transition-colors duration-200">
                            
                            <!-- Simple Icon -->
                            <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-red-200 transition-colors duration-200">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            
                            <!-- Simple Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        Fallout
                                    </span>
                                </div>
                                <p class="font-medium text-gray-900 text-sm mb-1 truncate">
                                    Order ID: {{ $report->order_id }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $report->created_at->diffForHumans() }}
                                </p>
                            </div>
                            
                            <!-- Simple Arrow -->
                            <div class="flex-shrink-0 ml-2">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-red-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <!-- Pelurusan Reports -->
                <div x-show="filter === 'all' || filter === 'pelurusan'">
                    @foreach($unassignedPelurusanReports as $report)
                        <a href="{{ route('pelurusan-reports.show', $report->id) }}" 
                           class="flex items-center px-4 py-3 hover:bg-green-50 border-b border-gray-100 last:border-b-0 group transition-colors duration-200">
                            
                            <!-- Simple Icon -->
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200 transition-colors duration-200">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- Simple Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Pelurusan
                                    </span>
                                </div>
                                <p class="font-medium text-gray-900 text-sm mb-1 truncate">
                                    Order ID: {{ $report->order_id }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $report->created_at->diffForHumans() }}
                                </p>
                            </div>
                            
                            <!-- Simple Arrow -->
                            <div class="flex-shrink-0 ml-2">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-green-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
                
            @else
                <!-- Simple Empty State -->
                <div class="px-4 py-12 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-medium text-gray-900 mb-1">All caught up!</h3>
                    <p class="text-xs text-gray-500">No unassigned reports at the moment.</p>
                </div>
            @endif
        </div>
        

    </div>
</div>