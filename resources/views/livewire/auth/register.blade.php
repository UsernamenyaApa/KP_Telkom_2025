

<div class="flex flex-col items-center justify-center w-full min-h-screen p-4">
    
    <div class="w-full max-w-lg px-11 py-10 space-y-2 bg-zinc-300/70 rounded-2xl border border-black/20 dark:bg-gray-800">
        
        <div class="flex justify-center">
            <img src="/images/register logo.png" alt="Register Logo" class="w-16 h-16">
        </div>

        <div class="text-center">
            <h2 class="text-2xl font-extrabold text-gray-800 dark:text-white">Registrasi via Telegram</h2>
            <p class="mt-2 text-[12px] text-gray-600 dark:text-gray-400">Pendaftaran akun baru dilakukan sepenuhnya melalui bot Telegram kami untuk kemudahan dan keamanan.</p>
        </div>

        <div class="pt-5 pl-5">
            <p class="text-[12px] font-semibold text-gray-800 dark:text-gray-200">Langkah-langkah Registrasi:</p>
            <ol class="mt-2 space-y-2 text-[12px] text-gray-700 list-decimal list-inside">
                <li>Buka aplikasi Telegram Anda.</li>
                <li>Cari bot dengan username <span class="font-bold text-gray-800 dark:text-white">@kp_telkom_2025_bot</span>.</li>
                <li>Kirim perintah <code>/start</code> untuk memulai percakapan.</li>
                <li>Ikuti instruksi yang diberikan oleh bot untuk menyelesaikan pendaftaran.</li>
            </ol>
        </div>

        <div class="flex justify-center pt-6">
            <a href="https://t.me/kp_telkom_2025_bot" target="_blank" class="flex items-center justify-center w-3/4 px-4 py-3 text-sm font-semibold text-white bg-blue-600 border border-transparent rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800">
                Lanjutkan ke Telegram
            </a>
        </div>

        <div class="pt-4 text-xs text-center">
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                Sudah punya akun? Login di sini
            </a>
        </div>
    </div>
</div>

</body>
</html>