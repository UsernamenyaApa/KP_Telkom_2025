<section class="w-full" x-data="{ 
    photoName: null, 
    photoPreview: null
}">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information')">
        <div class="max-w-2xl mx-auto">
            <form wire:submit="save" class="space-y-8">
                <!-- Profile Photo Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">{{ __('Profile Photo') }}</h3>
                    
                    <!-- Photo and Actions Container -->
                    <div class="flex flex-col items-center space-y-6">
                        <!-- Photo Preview -->
                        <div class="relative">
                            <img class="h-32 w-32 rounded-full object-cover border-4 border-gray-100 dark:border-gray-600 shadow-lg" 
                                 :src="photoPreview || '{{ $user->profilePhotoUrl() }}'" 
                                 alt="{{ $user->name }}" />
                            
                            <!-- Photo overlay indicator -->
                            <div class="absolute bottom-0 right-0 bg-blue-500 dark:bg-blue-600 rounded-full p-2 shadow-lg border-2 border-white dark:border-gray-800" 
                                 x-show="photoPreview">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- File Input -->
                        <input type="file" 
                               class="sr-only" 
                               wire:model="photo" 
                               x-ref="photo" 
                               accept="image/*"
                               @change="
                                    if ($refs.photo.files[0]) {
                                        photoName = $refs.photo.files[0].name;
                                        const reader = new FileReader();
                                        reader.onload = (e) => {
                                            photoPreview = e.target.result;
                                        };
                                        reader.readAsDataURL($refs.photo.files[0]);
                                    }
                                " />
                        <input type="hidden" wire:model="croppedPhoto" x-model="photoPreview">

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <button type="button" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-lg transition-colors duration-200 shadow-sm"
                                    @click.prevent="$refs.photo.click()">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{ __('Change Photo') }}
                            </button>

                            <!-- Remove Photo Button -->
                            @if($user->profile_photo_path)
                                <button type="button" 
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 focus:ring-2 focus:ring-red-500 dark:focus:ring-red-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-lg transition-colors duration-200 border border-red-200 dark:border-red-800"
                                        wire:click="deleteProfilePhoto" 
                                        x-show="!photoPreview"
                                        wire:confirm="{{ __('Are you sure you want to remove your profile photo?') }}">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    {{ __('Remove') }}
                                </button>
                            @endif
                        </div>

                        <!-- Photo Selection Status -->
                        <div x-show="photoPreview" class="w-full max-w-md">
                            <div class="flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-center space-x-3 text-sm text-blue-800 dark:text-blue-300">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ __('New photo selected') }}</p>
                                        <p x-text="photoName" class="text-xs text-blue-600 dark:text-blue-400 truncate max-w-xs"></p>
                                    </div>
                                </div>
                                <button type="button" 
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium px-3 py-1 rounded hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                        @click="photoPreview = null; photoName = null; $refs.photo.value = ''">>
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </div>

                        <!-- Upload Guidelines -->
                        <div class="w-full max-w-md text-center">
                            <div class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border dark:border-gray-600">
                                <p class="font-medium mb-2 text-gray-700 dark:text-gray-300">{{ __('Photo Requirements:') }}</p>
                                <div class="space-y-1 text-left">
                                    <p>• {{ __('High-quality photo showing your face clearly') }}</p>
                                    <p>• {{ __('Supported: JPG, PNG, GIF') }}</p>
                                    <p>• {{ __('Maximum size: 2MB') }}</p>
                                </div>
                            </div>
                            <x-input-error for="photo" class="text-sm mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">{{ __('Personal Information') }}</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div class="space-y-2">
                            <flux:input wire:model="name" 
                                       :label="__('Full Name')" 
                                       type="text" 
                                       required 
                                       autofocus 
                                       autocomplete="name"
                                       class="block w-full" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This is the name that will be displayed on your profile.') }}</p>
                        </div>

                        <div class="space-y-2">
                            <flux:input wire:model="nik" 
                                       :label="__('NIK (Nomor Induk Kependudukan)')" 
                                       type="text" 
                                       required 
                                       autocomplete="nik"
                                       minlength="6"
                                       maxlength="16"
                                       class="block w-full" />
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('6-16 digit unique identification number from your ID card.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 rounded-b-lg" 
                     x-data="{ showSuccess: false }" 
                     @saved.window="showSuccess = true; setTimeout(() => showSuccess = false, 3000)">
                    
                    <!-- Success Message -->
                    <div x-show="showSuccess" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="flex items-center space-x-2 text-green-700 dark:text-green-400">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium">{{ __('Profile updated successfully!') }}</span>
                    </div>

                    <!-- Empty div when success message is shown -->
                    <div x-show="!showSuccess"></div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-3">
                        <button type="button" 
                                x-show="!showSuccess"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-600 border border-gray-300 dark:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 hover:text-gray-700 dark:hover:text-gray-200 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-gray-500 dark:focus:ring-gray-400 rounded-lg shadow-sm transition-all duration-200"
                                @click="window.location.reload()">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ __('Reset') }}
                        </button>

                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white bg-blue-600 dark:bg-blue-700 hover:bg-blue-700 dark:hover:bg-blue-800 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 min-w-[150px] justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>