<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="auth-layout min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
            <div class="fade-in">
                <a href="/" class="flex flex-col items-center gap-3">
                    <img src="{{ asset('img/misdnar2.jpg') }}" alt="Logo Misdinar" class="w-28 h-28 rounded-2xl object-cover shadow-xl ring-4 ring-white/30">
                    <h1 class="text-2xl font-bold text-white drop-shadow-lg">{{ config('app.name', 'LMS Misdinar') }}</h1>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-8 py-8 auth-card fade-in">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
