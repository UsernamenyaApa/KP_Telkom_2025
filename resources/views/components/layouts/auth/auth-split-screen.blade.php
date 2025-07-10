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
            {{-- Bagian ini akan menampilkan gambar gedung --}}
        </div>

        {{-- BARIS DI BAWAH INI TELAH DIPERBARUI --}}
        <div class="w-full md:w-1/2 flex items-center justify-center p-6 sm:p-12 
                    bg-gradient-conic from-rose-100 via-gray-100 to-blue-200">
            {{ $slot }}
        </div>

    </div>
</body>
</html>