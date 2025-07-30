<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <form wire:submit="save" class="my-6 w-full space-y-6">
            <flux:radio.group x-data variant="segmented" x-model="$wire.appearance">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>

            <div x-show="$wire.appearance === 'light' || ($wire.appearance === 'system' && !darkMode)">
                <h4 class="text-lg font-semibold mb-2">{{ __('Light Mode Themes') }}</h4>
                <flux:radio.group x-data variant="segmented" x-model="$wire.theme_color">
                    <flux:radio value="light-red" class="text-red-500 border-red-500">{{ __('Red') }}</flux:radio>
                    <flux:radio value="light-blue" class="text-blue-500 border-blue-500">{{ __('Blue') }}</flux:radio>
                    <flux:radio value="light-dark-blue" class="text-indigo-800 border-indigo-800">{{ __('Dark Blue') }}</flux:radio>
                </flux:radio.group>
            </div>

            <div x-show="$wire.appearance === 'dark' || ($wire.appearance === 'system' && darkMode)">
                <h4 class="text-lg font-semibold mb-2">{{ __('Dark Mode Themes') }}</h4>
                <flux:radio.group x-data variant="segmented" x-model="$wire.theme_color">
                    <flux:radio value="dark-red" class="text-red-500 border-red-500">{{ __('Red') }}</flux:radio>
                    <flux:radio value="dark-blue" class="text-blue-500 border-blue-500">{{ __('Blue') }}</flux:radio>
                    <flux:radio value="dark-dark-blue" class="text-indigo-800 border-indigo-800">{{ __('Dark Blue') }}</flux:radio>
                </flux:radio.group>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="saved">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</section>