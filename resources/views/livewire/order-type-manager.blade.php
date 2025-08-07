<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header dengan ikon di kiri -->
    <div class="flex items-center space-x-4 mb-6">
        <img src="{{ asset('images/gear-document.png') }}" alt="Gear Document Icon"
             class="w-14 h-14 sm:w-16 sm:h-16">

        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Manage Order Types</h1>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-400">Create, edit, and delete Order Types.</p>
        </div>
    </div>

    <!-- Pesan sukses -->
    @if (session()->has('message'))
        <div class="mb-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-[#131518] dark:text-green-400" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <!-- Form input order type -->
    <div class="mt-6 bg-white dark:bg-[#131518] p-4 rounded-lg shadow border-1 border-gray-300 dark:border-gray-600">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $isEditing ? 'Edit' : 'Create' }} Order Type</h2>
        <form wire:submit.prevent="{{ $isEditing ? 'update' : 'create' }}" class="mt-4 space-y-4">
            <div class="relative">
                <input type="text" wire:model="name" id="name" placeholder="Order Type Name"
                    class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-[#1c1e22] py-2 pl-3 pr-3 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm" />
                @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-start space-x-2">
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    {{ $isEditing ? 'Update Type' : 'Create Type' }}
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

    <!-- List Order Types -->
    <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @forelse ($orderTypes as $orderType)
            <div class="flex items-center justify-between bg-white dark:bg-[#131518] border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 shadow">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 text-white p-2 rounded-md">
                        📄
                    </div>
                    <span class="text-gray-900 dark:text-white font-medium">{{ $orderType->name }}</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button wire:click="edit({{ $orderType->id }})" class="text-indigo-600 hover:text-indigo-900 text-sm">Edit</button>
                    <button wire:click="delete({{ $orderType->id }})" onclick="return confirm('Are you sure?');" class="text-red-600 hover:text-red-900 text-sm">Delete</button>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center text-sm text-gray-500 dark:text-gray-400">
                No Order Types found.
            </div>
        @endforelse
    </div>
</div>