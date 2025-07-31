{{-- resources/views/components/layouts/minimal.blade.php --}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        {{-- Aset dikompilasi menggunakan Vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'], null, ['preload' => false])
    </head>
    {{-- Body ini adalah 'kanvas' untuk gradien --}}
    <body class="h-full bg-gradient-to-b from-sky-700 to-slate-300 backdrop-blur-[2px]">
        {{ $slot }}
    </body>
</html>