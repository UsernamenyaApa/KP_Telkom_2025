<div class="relative">
    <a href="#" class="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100">
        Uncollected Orders: {{ $uncollectedCount }}
    </a>
    @if ($newUncollectedCount > 0)
        <span class="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
    @endif
</div>