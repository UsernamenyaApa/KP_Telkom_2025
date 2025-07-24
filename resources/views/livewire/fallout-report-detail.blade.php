<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Fallout Report Details</h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-400">Details for incident ticket: {{ $report->incident_ticket }}</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none flex items-center space-x-4">
                <a href="{{ route('fallout-reports.index', ['date' => $date]) }}" class="block rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-center text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 sm:mt-0">Back to Dashboard</a>
                @if ($report->falloutStatus?->name === 'Open')
                    <button wire:click="takeOrder" class="block rounded-md bg-green-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">Ambil Order</button>
                @endif
                @if ($report->falloutStatus?->name === 'OnProgress' && $report->assigned_to_user_id == auth()->id())
                    <button wire:click="openStatusModal" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Change Status</button>
                @endif
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden bg-white dark:bg-gray-800 shadow ring-1 ring-black ring-opacity-5 dark:ring-white/10 sm:rounded-lg">
                        <div class="grid grid-cols-1 sm:grid-cols-2">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">General Information</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">No</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->id_harian }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Incident Ticket</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->incident_ticket }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipe Order</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->orderType?->name }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->order_id }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nomer Layanan</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->nomer_layanan }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SN ONT</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->sn_ont }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Datek ODP</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->datek_odp }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Port ODP</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->port_odp }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Status and Assignment</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Fallout</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->falloutStatus?->name }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->assignedToUser?->name ?? 'Unassigned' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reporter</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->reporter?->name ?? $report->reporter_telegram_username ?? 'N/A' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order Create</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->created_at->format('d M Y, H:i') }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order Take</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->taken_at ? $report->taken_at->format('d M Y, H:i') : '-' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Order Complete</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->completed_at ? $report->completed_at->format('d M Y, H:i') : '-' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->updated_at->format('d M Y, H:i') }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</dt>
                                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->taken_at && $report->completed_at ? $report->completed_at->diffForHumans($report->taken_at, true) : '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="px-4 py-5 sm:p-6 sm:col-span-2">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white">Descriptions</h3>
                                <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-8">
                                    <!-- Text Descriptions Column -->
                                    <div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Keterangan Insiden Fallout</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->incident_fallout_description }}</dd>
                                        </div>
                                        <div class="mt-8">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Catatan Resolusi</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $report->resolution_notes }}</dd>
                                        </div>
                                    </div>

                                    <!-- Image Column -->
                                    @if ($report->image)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Image</dt>
                                        <dd class="mt-1">
                                            <img src="{{ asset('storage/' . $report->image) }}" alt="Fallout Image" class="w-full max-h-96 object-contain rounded-lg shadow-md">
                                        </dd>
                                    </div>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showStatusModal)
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0" wire:click="closeStatusModal"></div>

        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 relative z-20 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Change Fallout Status</h3>
                <div class="mt-4">
                    <label for="status" class="sr-only">Status</label>
                    <select wire:model="newStatusId" id="status" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Status</option>
                        @foreach($availableStatuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4">
                    <label for="keterangan" class="sr-only">Keterangan</label>
                    <textarea wire:model="keterangan" id="keterangan" rows="4" class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Tambahkan catatan..."></textarea>
                </div>
                
                <div class="mt-6 flex justify-end space-x-4">
                    <button wire:click="closeStatusModal" type="button" class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-900 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button wire:click="changeStatus" type="button" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
