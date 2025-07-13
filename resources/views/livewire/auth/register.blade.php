<div class="flex flex-col items-center justify-center min-h-screen px-4 bg-gray-100 dark:bg-gray-900">
    <div class="w-full max-w-md px-8 py-10 space-y-6 bg-white rounded-lg shadow-xl dark:bg-gray-800">
        <div class="flex justify-center">
            <x-app-logo />
        </div>
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Registrasi via Telegram</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Pendaftaran akun baru dilakukan sepenuhnya melalui bot Telegram kami untuk kemudahan dan keamanan.</p>
        </div>

        <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Langkah-langkah Registrasi:</p>
            <ol class="mt-2 space-y-1 text-sm text-gray-700 list-decimal list-inside dark:text-gray-300">
                <li>Buka aplikasi Telegram Anda.</li>
                <li>Cari bot dengan username <span class="font-bold text-gray-900 dark:text-white">@kp_telkom_2025_bot</span>.</li>
                <li>Kirim perintah <code>/start</code> untuk memulai percakapan.</li>
                <li>Ikuti instruksi yang diberikan oleh bot untuk menyelesaikan pendaftaran.</li>
            </ol>
        </div>

        <div class="pt-6">
            <a href="https://t.me/kp_telkom_2025_bot" target="_blank" class="flex items-center justify-center w-full px-4 py-3 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                Lanjutkan ke Telegram
            </a>
        </div>

        <div class="pt-4 text-sm text-center border-t border-gray-200 dark:border-gray-700">
            <a wire:navigate href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                Sudah punya akun? Login di sini
            </a>
        </div>
    </div>
</div>