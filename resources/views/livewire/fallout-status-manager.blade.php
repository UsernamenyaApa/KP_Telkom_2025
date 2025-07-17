<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold leading-6 text-gray-900">Manage Fallout Statuses</h1>
            <p class="mt-2 text-sm text-gray-700">Create, edit, and delete Fallout Statuses.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <div class="mt-6">
        <form wire:submit.prevent="{{ $isEditing ? 'update' : 'create' }}" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Fallout Status Name</label>
                <div class="mt-2">
                    <input type="text" wire:model="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    {{ $isEditing ? 'Update' : 'Create' }}
                </button>
                @if ($isEditing)
                    <button type="button" wire:click="cancelEdit" class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Edit</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($falloutStatuses as $falloutStatus)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ $falloutStatus->name }}</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                    <button wire:click="edit({{ $falloutStatus->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                    <button wire:click="delete({{ $falloutStatus->id }})" onclick="return confirm('Are you sure?');" class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-0 text-center">No Fallout Statuses found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
