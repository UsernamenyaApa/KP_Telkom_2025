<div>
    <div class="bg-white dark:bg-gray-800 shadow-lg sm:rounded-lg p-6 border-2 border-gray-200 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between pb-4">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Laporan Harian</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Menampilkan data untuk tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
            </div>
        </div>

        <div wire:loading.remove class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead style="background-color: #005AB2;">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black dark:text-white uppercase tracking-wider">Pekerjaan</th>
                        @foreach($users as $user)
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black dark:text-white uppercase tracking-wider">{{ $user->name }}</th>
                        @endforeach
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black dark:text-white uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reportRows as $rowKey => $rowLabel)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">{{ $rowLabel }}</td>
                            @foreach($users as $user)
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-center">{{ $reportData[$rowKey][$user->id] ?? 0 }}</td>
                            @endforeach
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-100 text-center">{{ $rowTotals[$rowKey] ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $users->count() + 2 }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-center">Tidak ada data laporan untuk ditampilkan.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">Total Per Orang</td>
                        @foreach($users as $user)
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-100 text-center">{{ $userTotals[$user->id] ?? 0 }}</td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-blue-600 dark:text-white text-center">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div wire:loading.delay.short class="overflow-x-auto animate-pulse">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead style="background-color: #005AB2;">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-black dark:text-white uppercase tracking-wider">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-3/4"></div>
                        </th>
                        @foreach(range(1, $userCount) as $i)
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black dark:text-white uppercase tracking-wider">
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2 mx-auto"></div>
                            </th>
                        @endforeach
                        <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-black dark:text-white uppercase tracking-wider">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4 mx-auto"></div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach(range(1, 5) as $i)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                            </td>
                            @foreach(range(1, $userCount) as $j)
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 text-center">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/3 mx-auto"></div>
                                </td>
                            @endforeach
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-100 text-center">
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4 mx-auto"></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/2"></div>
                        </td>
                        @foreach(range(1, $userCount) as $i)
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-100 text-center">
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/3 mx-auto"></div>
                            </td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-blue-600 dark:text-white text-center">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-1/4 mx-auto"></div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>