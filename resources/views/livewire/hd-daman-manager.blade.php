<div class="p-4 sm:p-6 lg:p-8">
    <div class="flex items-center space-x-4 mb-6">
        <img src="{{ asset('images/add user.png') }}" alt="User Icon"
             class="w-14 h-14 sm:w-16 sm:h-16">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Manage HD Daman</h1>
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-400">Create new users and manage their "hd-daman" role.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-[#131518] dark:text-green-400" role="alert">
            {{ session('message') }}
        </div>
    @endif

    <div class="mt-4 bg-white dark:bg-[#131518] p-4 rounded-lg shadow border-1 border-gray-300 dark:border-gray-600">
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Create New User</h2>
        <form wire:submit.prevent="createUser" class="mt-2 space-y-4">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                <div class="sm:col-span-2 relative">
                    <input type="text" wire:model="name" id="name" placeholder="Full Name"
                        class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-[#1c1e22] py-2 pl-3 pr-3 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 relative">
                    <input type="text" wire:model="nik" id="nik" placeholder="NIK"
                        class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-[#1c1e22] py-2 pl-3 pr-3 text-gray-900 dark:text-white placeholder:text-gray-400 shadow-inner focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:text-sm" />
                    @error('nik') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-start">
                <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Create User & Assign Role
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6">
        <div class="bg-white dark:bg-[#131518] sm:rounded-lg p-6 border-1 border-gray-300 dark:border-gray-600">
            <div class="mb-4">
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search users by Name or NIK..."
                    class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#1c1e22] px-3 py-1.5 text-gray-900 dark:text-white shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-600 sm:text-sm" />
            </div>
    
            <div class="overflow-x-auto">
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-blue-800 dark:bg-blue-950 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">NIK</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-white uppercase tracking-wider">Roles</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-white uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-[#131518] text-sm text-gray-800 dark:text-gray-200 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 font-medium whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $user->nik }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @foreach ($user->getRoleNames() as $role)
                                            @if($role === 'super-admin')
                                                <span class="inline-flex items-center rounded-full bg-[#e5e1ff] dark:bg-[#3c3c7a] px-3 py-1 text-xs font-semibold text-indigo-700 dark:text-indigo-300">super-admin</span>
                                            @elseif($role === 'hd-daman')
                                                <span class="inline-flex items-center rounded-full bg-[#e7f0ff] dark:bg-[#2b3a54] px-3 py-1 text-xs font-semibold text-blue-700 dark:text-blue-300">hd-daman</span>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($user->hasRole('hd-daman'))
                                            <button wire:click="revokeHdDamanRole({{ $user->id }})" class="text-red-600 hover:text-red-800 font-semibold">Revoke HD-Daman</button>
                                        @else
                                            <button wire:click="assignHdDamanRole({{ $user->id }})" class="text-indigo-600 hover:text-indigo-800 font-semibold">Assign HD-Daman</button>
                                        @endif
    
                                        @if (auth()->user()->hasRole('super-admin') && auth()->user()->id !== $user->id)
                                            <button wire:click="deleteUser({{ $user->id }})"
                                                onclick="return confirm('Are you sure you want to delete this user?') || event.stopImmediatePropagation()"
                                                class="ml-4 text-red-600 hover:text-red-800 font-semibold">Delete</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
