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
        <link rel="stylesheet" href="{{ asset('build/assets/app-nBeFMyzn.css') }}">
        <script src="{{ asset('build/assets/app-CBbTb_k3.js') }}" defer></script>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="auth-layout min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
            <div class="fade-in">
                <a href="{{ url('/') }}" class="flex flex-col items-center gap-2 sm:gap-3">
                    <img src="{{ asset('img/misdnar2.jpg') }}" alt="Logo Misdinar" class="w-20 h-20 sm:w-28 sm:h-28 rounded-xl sm:rounded-2xl object-contain shadow-xl ring-2 sm:ring-4 ring-white/30 bg-white p-1.5 sm:p-2">
                    <h1 class="text-xl sm:text-2xl font-bold text-white drop-shadow-lg text-center">{{ config('app.name', 'LMS Misdinar') }}</h1>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-4 sm:mt-6 px-4 py-6 sm:px-8 sm:py-8 auth-card fade-in">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
