<div class="flex items-stretch max-md:flex-col h-full">
    <div class="w-full pb-4 md:w-[220px] md:border-e md:border-zinc-200 md:dark:border-zinc-700 md:pe-10">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <div class="flex-1 self-stretch max-md:pt-6 md:ps-10">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
