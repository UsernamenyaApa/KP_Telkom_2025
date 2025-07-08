<div>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-base font-semibold leading-6 text-gray-900">Fallout Report Details</h1>
                <p class="mt-2 text-sm text-gray-700">Details for incident ticket: {{ $report->incident_ticket }}</p>
            </div>
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                @if ($report->falloutStatus?->name === 'OnProgress' && $report->assigned_to_user_id == auth()->id())
                    <button wire:click="openStatusModal" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Change Status</button>
                @endif
                <a href="{{ route('fallout-reports.index') }}" class="block rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0">Back to Dashboard</a>
            </div>
        </div>

        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <div class="grid grid-cols-1 sm:grid-cols-2">
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">General Information</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">No</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->id_harian }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Incident Ticket</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->incident_ticket }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Tipe Order</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->orderType?->name }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Order ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->order_id }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Nomer Layanan</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->nomer_layanan }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">SN ONT</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->sn_ont }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Datek ODP</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->datek_odp }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Port ODP</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->port_odp }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="px-4 py-5 sm:p-6">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Status and Assignment</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Status Fallout</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->falloutStatus?->name }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->assignedToUser?->name ?? 'Unassigned' }}</dd>
                                    </div>
                                    <div class="sm:col-span-1">
                                        <dt class="text-sm font-medium text-gray-500">Reporter</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->reporter?->name }}</dd>
                                    </div>
                                </dl>
                            </div>
                            <div class="px-4 py-5 sm:p-6 sm:col-span-2">
                                <h3 class="text-base font-semibold leading-6 text-gray-900">Descriptions</h3>
                                <dl class="mt-5 grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Keterangan Insiden Fallout</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->incident_fallout_description }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Catatan Resolusi</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $report->resolution_notes }}</dd>
                                    </div>
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
        <div class="fixed inset-0 bg-gray-500 opacity-75" wire:click="closeStatusModal"></div>

        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-xl p-6 relative z-20 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900">Change Fallout Status</h3>
                <div class="mt-4">
                    <label for="status" class="sr-only">Status</label>
                    <select wire:model="newStatusId" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Status</option>
                        @foreach(App\Models\FalloutStatus::all() as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4">
                    <label for="keterangan" class="sr-only">Keterangan</label>
                    <textarea wire:model="keterangan" id="keterangan" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Tambahkan catatan..."></textarea>
                </div>
                <div class="mt-6 flex justify-end space-x-4">
                    <button wire:click="closeStatusModal" type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
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