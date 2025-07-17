<div>
    <div class="p-6 bg-gray-100 dark:bg-gray-900 min-h-screen">
        <!-- Top Bar (already handled by x-layouts.app, so we'll skip the redundant part from your HTML) -->

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white font-sans tracking-wide">Fallout Report</h1>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="w-full px-4 py-2 rounded-md border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
                <div class="relative">
                    <input type="date" wire:model.live="date" class="px-4 py-2 rounded-md border border-gray-300 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-md shadow-md flex items-center space-x-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Filter</span>
                </button>
            </div>
        </div>

        <p class="mb-4 text-sm text-gray-700 dark:text-white font-akatab tracking-wider">A list of all the fallout reports from the field.</p>

        <div class="mt-8 flow-root rounded-lg shadow-md overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <thead class="bg-blue-800 dark:bg-blue-900">
                            <tr>
                                <th scope="col" class="py-3.5 px-3 text-center text-sm font-semibold text-white sm:pl-0 font-istok-web min-w-[80px]">No</th>
                                <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold text-white font-istok-web">Incident Ticket</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Tipe Order</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Order ID</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Status Fallout</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Assigned To</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Order Create</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Order Take</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Order Complete</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Last Updated</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white font-istok-web">Duration</th>
                                <th scope="col" class="relative py-3.5 pl-6 pr-8 text-white font-istok-web text-right min-w-[120px]">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @forelse ($reports as $report)
                                <tr>
                                    <td class="whitespace-nowrap py-4 px-3 text-center text-sm font-medium text-gray-900 dark:text-white sm:pl-0 font-istok-web min-w-[80px]">{{ $report->id_harian }}</td>
                                    <td class="whitespace-nowrap py-4 px-3 text-sm font-medium text-gray-900 dark:text-white font-istok-web">{{ $report->incident_ticket }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->orderType?->name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->order_id }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm font-istok-web">
                                        @php
                                            $statusColorClass = '';
                                            switch ($report->falloutStatus?->name) {
                                                case 'Eskalasi':
                                                    $statusColorClass = 'bg-red-500/50';
                                                    break;
                                                case 'FA':
                                                    $statusColorClass = 'bg-green-500/50';
                                                    break;
                                                case 'OnProgress':
                                                    $statusColorClass = 'bg-yellow-500/50';
                                                    break;
                                                case 'Open':
                                                    $statusColorClass = 'bg-blue-500/50';
                                                    break;
                                                case 'Re-Input':
                                                    $statusColorClass = 'bg-orange-500/50';
                                                    break;
                                                case 'Completed':
                                                    $statusColorClass = 'bg-green-700/50';
                                                    break;
                                                default:
                                                    $statusColorClass = 'bg-gray-500/50';
                                                    break;
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-black dark:text-white {{ $statusColorClass }}">
                                            {{ $report->falloutStatus?->name }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->assignedToUser?->name }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->created_at->format('d M Y, H:i') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->taken_at ? $report->taken_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->completed_at ? $report->completed_at->format('d M Y, H:i') : '-' }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->updated_at->format('d M Y, H:i') }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-white font-istok-web">{{ $report->taken_at && $report->completed_at ? $report->completed_at->diffForHumans($report->taken_at, true) : '-' }}</td>
                                    <td class="relative py-4 pl-6 pr-8 text-right text-sm font-medium font-istok-web min-w-[120px]">
                                        <a href="{{ route('fallout-reports.show', $report->id) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 px-2">Details</a>
                                        @if ($report->falloutStatus?->name === 'Open')
                                            <button wire:click="takeOrder({{ $report->id }})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 px-2">Ambil Order</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="whitespace-nowrap py-4 text-center text-sm font-medium text-gray-900 dark:text-white font-istok-web">No fallout reports found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-4 flex justify-between items-center text-gray-500 dark:text-white font-istok-web">
            <div>Show {{ $reports->firstItem() }} to {{ $reports->lastItem() }} from {{ $reports->total() }} data</div>
            <div>
                {{ $reports->links('livewire.pagination-links') }}
            </div>
        </div>
    </div>
</div>