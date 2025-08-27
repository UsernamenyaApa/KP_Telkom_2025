@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
        <span>
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 cursor-default leading-5 rounded-lg shadow-sm">
                    First
                </span>
            @else
                <button wire:click="gotoPage(1)" class="relative inline-flex items-center px-4 py-2 text-sm font-medium bg-blue-600 text-white border border-blue-600 leading-5 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-blue-700 transition ease-in-out duration-150">
                    First
                </button>
            @endif

            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 cursor-default leading-5 rounded-lg shadow-sm">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium bg-blue-600 text-white border border-blue-600 leading-5 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-blue-700 transition ease-in-out duration-150">
                    {!! __('pagination.previous') !!}
                </button>
            @endif
        </span>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 leading-5">
                    Showing
                    <span class="font-medium">{{ $paginator->firstItem() }}</span>
                    to
                    <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    of
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    results
                </p>
            </div>
        </div>

        <span>
            @if ($paginator->hasMorePages())
                <button wire:click="nextPage" class="relative inline-flex items-center px-4 py-2 text-sm font-medium bg-blue-600 text-white border border-blue-600 leading-5 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-blue-700 transition ease-in-out duration-150">
                    {!! __('pagination.next') !!}
                </button>
            @else
                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 cursor-default leading-5 rounded-lg shadow-sm">
                    {!! __('pagination.next') !!}
                </span>
            @endif

            @if ($paginator->hasMorePages())
                <button wire:click="gotoPage({{ $paginator->lastPage() }})" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium bg-blue-600 text-white border border-blue-600 leading-5 rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring ring-blue-300 focus:border-blue-300 active:bg-blue-700 transition ease-in-out duration-150">
                    Last
                </button>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 cursor-default leading-5 rounded-lg shadow-sm">
                    Last
                </span>
            @endif
        </span>
    </nav>
@endif
