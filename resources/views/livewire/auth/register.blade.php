<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-200/40 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi via Telegram</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Enable dark mode using a class
            theme: {
                extend: {
                    // You can extend your theme here if needed
                }
            }
        }
    </script>
    </head>
<body class="h-full">

<div class="flex flex-col items-center justify-center w-full h-fit ">
    <div class="w-full max-w-md px-8 py-8 space-y-6 bg-white rounded-lg shadow-xl">
        <div class="flex justify-center">
            <svg class="w-12 h-12 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        </div>
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">Registrasi via Telegram</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Pendaftaran akun baru dilakukan sepenuhnya melalui bot Telegram kami untuk kemudahan dan keamanan.</p>
        </div>

        <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Langkah-langkah Registrasi:</p>
            <ol class="mt-2 space-y-2 text-sm text-gray-700 list-decimal list-inside dark:text-gray-300">
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
            Sudah punya akun?
            <a href="{{ route('login') }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                Login di sini
            </a>
        </div>
    </div>
</div>

</body>
</html>