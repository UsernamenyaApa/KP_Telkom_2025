<span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
    @if ($user->profile_photo_path)
        <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover rounded-lg">
    @else
        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
            {{ $user->initials() }}
        </span>
    @endif
</span>