<div class="p-4 sm:p-6 lg:p-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold leading-6 text-gray-900">Manage HD Daman</h1>
            <p class="mt-2 text-sm text-gray-700">Create new users and manage their "hd-daman" role.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            {{ session('message') }}
        </div>
    @endif

    {{-- Form untuk membuat pengguna baru --}}
    <div class="mt-6 bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-medium text-gray-900">Create New User</h2>
        <form wire:submit.prevent="createUser" class="mt-4 space-y-4">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Full Name</label>
                    <input type="text" wire:model="name" id="name" placeholder="e.g., Kahfi Fauzan Habibi" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="nik" class="block text-sm font-medium leading-6 text-gray-900">NIK</label>
                    <input type="text" wire:model="nik" id="nik" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('nik') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date_of_birth" class="block text-sm font-medium leading-6 text-gray-900">Date of Birth</label>
                    <input type="date" wire:model="date_of_birth" id="date_of_birth" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    @error('date_of_birth') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    Create User & Assign Role
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar pengguna yang ada --}}
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="mb-4">
                     <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search users by name or email..." class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                </div>
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Name</th>
                            <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">Email</th>
                            <th scope="col" class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">Roles</th>
                            <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($users as $user)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0">{{ $user->name }}</td>
                                <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-500">{{ $user->email }}</td>
                                <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-500">
                                    @foreach ($user->getRoleNames() as $role)
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">{{ $role }}</span>
                                    @endforeach
                                </td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                    @if ($user->hasRole('hd-daman'))
                                        <button wire:click="revokeHdDamanRole({{ $user->id }})" class="text-red-600 hover:text-red-900">Revoke HD-Daman</button>
                                    @else
                                        <button wire:click="assignHdDamanRole({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900">Assign HD-Daman</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-0 text-center">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
