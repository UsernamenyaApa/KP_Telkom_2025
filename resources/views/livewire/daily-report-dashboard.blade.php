<div>
    <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center sm:justify-between mb-4">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">Laporan Harian</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Menampilkan data untuk tanggal: {{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <input type="date" wire:model.live="selectedDate" class="block w-full px-3 py-2 text-base text-gray-900 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                <button wire:click="loadReportData" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Refresh
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pekerjaan</th>
                        @foreach($users as $user)
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">{{ $user->name }}</th>
                        @endforeach
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-extrabold text-blue-600 dark:text-blue-400 text-center">{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>