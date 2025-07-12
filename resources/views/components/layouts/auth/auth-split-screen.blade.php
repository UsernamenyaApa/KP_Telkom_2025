<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-g">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col md:flex-row">


        <div class="hidden lg:block md:w-1/2 bg-cover bg-center" style="background-image: url('{{ asset('images/image telkom.png') }}');">
        </div>

        <div class="w-full md:w-1/2 flex items-center justify-center p-6 sm:p-12 bg-gradient-to-br from-rose-200 via-white to-blue-200">
            <div class="w-full max-w-md bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg p-8">
                {{ $slot }}
            </div>
        </div>

    </div>
</body>
</html>