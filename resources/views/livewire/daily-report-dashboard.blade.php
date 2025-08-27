<div class="container mx-auto max-w-7xl px-4 py-8 bg-gray-900 text-white flex flex-col items-center">
  <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 w-full">
    <!-- Card untuk Open Orders -->
    <div class="card-hover ripple-effect relative animate-slide-up group">
      <div class="absolute -inset-0.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 opacity-20 blur transition duration-1000 group-hover:opacity-30"></div>
      <div class="relative rounded-xl border border-gray-700 bg-gray-800 p-6 shadow-xl">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-bold tracking-wide text-gray-300">Open Orders</p>
            <h3 class="text-4xl font-black text-blue-400">0</h3>
          </div>
          <div class="rounded-2xl bg-blue-900/40 p-3 text-blue-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Card untuk In Progress -->
    <div class="card-hover ripple-effect relative animate-slide-up group" style="animation-delay: 0.1s">
      <div class="absolute -inset-0.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 opacity-20 blur transition duration-1000 group-hover:opacity-30"></div>
      <div class="relative rounded-xl border border-gray-700 bg-gray-800 p-6 shadow-xl">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-bold tracking-wide text-gray-300">In Progress</p>
            <h3 class="text-4xl font-black text-blue-400">0</h3>
          </div>
          <div class="rounded-2xl bg-blue-900/40 p-3 text-blue-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Card untuk Completed -->
    <div class="card-hover ripple-effect relative animate-slide-up group" style="animation-delay: 0.2s">
      <div class="absolute -inset-0.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 opacity-20 blur transition duration-1000 group-hover:opacity-30"></div>
      <div class="relative rounded-xl border border-gray-700 bg-gray-800 p-6 shadow-xl">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-bold tracking-wide text-gray-300">Completed</p>
            <h3 class="text-4xl font-black text-blue-400">1</h3>
          </div>
          <div class="rounded-2xl bg-blue-900/40 p-3 text-blue-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Card untuk Team Progress -->
    <div class="card-hover ripple-effect relative animate-slide-up group" style="animation-delay: 0.3s">
      <div class="absolute -inset-0.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-800 opacity-20 blur transition duration-1000 group-hover:opacity-30"></div>
      <div class="relative rounded-xl border border-gray-700 bg-gray-800 p-6 shadow-xl">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-bold tracking-wide text-gray-300">Team Progress</p>
            <h3 class="text-4xl font-black text-blue-400">100.00%</h3>
          </div>
          <div class="rounded-2xl bg-blue-900/40 p-3 text-blue-300">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </div>
        </div>
        <div class="mt-4">
          <div class="h-4 w-full rounded-full bg-gray-700">
            <div class="h-4 rounded-full bg-blue-500" style="width: 100%;"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="mt-8 p-4 bg-gray-800 rounded-lg shadow-md border border-gray-700 w-full max-w-md">
    <h4 class="text-lg font-semibold text-white mb-2">Status Counts:</h4>
    <ul class="list-disc list-inside text-gray-300">
      <li>Open: 0</li>
      <li>In Progress: 0</li>
      <li>Completed: 1</li>
    </ul>
  </div>
</div>