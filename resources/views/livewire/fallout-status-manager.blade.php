<div class="p-4 sm:p-6 lg:p-8">
    <div class="flex items-center space-x-4 mb-6">
        <img src="{{ asset('images/gear-document.png') }}" alt="Status Icon"
             class="w-14 h-14 sm:w-16 sm:h-16">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Manage Fallout Statuses</h1>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-400">Create, edit, and delete Fallout Statuses.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-[#131518] dark:text-green-400" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <div class="mt-6 bg-white dark:bg-[#131518] p-4 rounded-lg shadow border-1 border-gray-300 dark:border-gray-600">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $isEditing ? 'Edit' : 'Create' }} Fallout Status</h2>
        <form wire:submit.prevent="{{ $isEditing ? 'update' : 'create' }}" class="mt-4 space-y-4">
            <div class="relative">
                <input type="text" wire:model="name" id="name" placeholder="Fallout Status Name"
                    class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-[#1c1e22] py-2 pl-3 pr-3 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm" />
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-start space-x-2">
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    {{ $isEditing ? 'Update Status' : 'Create Status' }}
                </button>
                @if ($isEditing)
                    <button type="button" wire:click="cancelEdit"
                        class="rounded-md bg-gray-200 dark:bg-gray-700 px-4 py-2 text-sm font-semibold text-gray-800 dark:text-white shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="mt-6">
        <div class="bg-white dark:bg-[#131518] sm:rounded-lg p-6 border-1 border-gray-300 dark:border-gray-600">
            <div class="overflow-x-auto">
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-blue-800 dark:bg-blue-950 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#131518] text-sm text-gray-800 dark:text-gray-200 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($falloutStatuses as $falloutStatus)
                                <tr>
                                    <td class="px-6 py-4 font-medium whitespace-nowrap">{{ $falloutStatus->name }}</td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <button wire:click="edit({{ $falloutStatus->id }})" class="text-indigo-600 hover:text-indigo-800 font-semibold">Edit</button>
                                        <button wire:click="delete({{ $falloutStatus->id }})" onclick="return confirm('Are you sure you want to delete this status?');" class="ml-4 text-red-600 hover:text-red-800 font-semibold">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No fallout statuses found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
