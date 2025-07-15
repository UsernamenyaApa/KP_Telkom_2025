<div class="p-4 sm:p-6 lg:p-8 h-screen">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold leading-6 text-gray-900">Data Pelurusan</h1>
        <p class="mt-2 text-sm text-gray-700">A list of all the Pelurusan from the field.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 items-end">
        <div>
            <label for="filter-by" class="block text-sm font-medium text-gray-700">Filter by</label>
            <select id="filter-by" name="filter-by" class="mt-1 block w-full rounded-md border-gray-300 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option selected disabled>Pilih Opsi</option>
                <option>Tipe Order</option>
                <option>Status</option>
                <option>Assigned To</option>
            </select>
        </div>
        <div>
             <label for="filter-periode" class="block text-sm font-medium text-gray-700">Filter Periode</label>
            <select id="filter-periode" class="mt-1 block w-full rounded-md border-gray-300 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option>Pilih Periode</option>
            </select>
        </div>
        <div class="flex gap-2 items-end">
             <div class="w-full">
                <label for="search" class="sr-only">Search</label>
                <input type="search" id="search" placeholder="Search..." class="block w-full rounded-md border-gray-300 py-2.5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
             </div>
             <button type="button" class="flex-shrink-0 rounded-md bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Search
             </button>
        </div>
    </div>


    <div class="flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">NO</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">TIPE ORDER</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ORDER ID</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">STATUS</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ASSIGNED TO</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">1</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">SO</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">ODP-6</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Completed</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">Ignasius Jonathan</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-sm font-medium sm:pr-6">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">2</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">DO</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">2344</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                     <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">Eskalasi</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">Test User</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-sm font-medium sm:pr-6">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">3</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">AO</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">Order 1</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">OnProgress</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">Test User</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-sm font-medium sm:pr-6">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">4</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">AO</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">1112</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">Open</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">-</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-sm font-medium sm:pr-6">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">5</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">SO</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">ODP-12</td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">Completed</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">User Baru</td>
                                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-sm font-medium sm:pr-6">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">Details</a>
                                </td>
                            </tr>
                            
                            </tbody>
                    </table>
                </div>

                <div class="mt-2 flex items-center justify-between">
                    <div class="flex flex-1 justify-between sm:hidden">
                      <a href="#" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                      <a href="#" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                      <div>
                        <p class="text-sm text-gray-700">
                          Menampilkan
                          <span class="font-medium">1</span>
                          sampai
                          <span class="font-medium">5</span>
                          dari
                          <span class="font-medium">9</span>
                          data
                        </p>
                      </div>
                      <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                          <span class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300">
                            First
                          </span>
                          <span class="relative inline-flex items-center px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 cursor-not-allowed">
                            <span class="sr-only">Previous</span>&lt;
                          </span>
                          
                          <a href="#" aria-current="page" class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20">1</a>
                          <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20">2</a>
                          
                          <a href="#" class="relative inline-flex items-center px-2 py-2 text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <span class="sr-only">Next</span>&gt;
                          </a>
                          <a href="#" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Last
                          </a>
                        </nav>
                      </div>
                    </div>
                  </div>

            </div>
        </div>
    </div>
</div>