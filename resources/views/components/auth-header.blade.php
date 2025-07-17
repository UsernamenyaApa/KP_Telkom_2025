@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <flux:heading size="xl">{{ $title }}</flux:heading>
    <flux:subheading class="dark:text-gray-300">{{ $description }}</flux:subheading>
</div>
