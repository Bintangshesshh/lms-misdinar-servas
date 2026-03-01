<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS Misdinar')</title>
    <link rel="stylesheet" href="{{ asset('build/assets/app-nBeFMyzn.css') }}">
    <script src="{{ asset('build/assets/app-CBbTb_k3.js') }}" defer></script>
</head>
<body class="bg-gray-50 min-h-screen">
    {{-- Navbar --}}
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <div class="flex items-center">
                    <span class="text-lg sm:text-xl font-bold text-indigo-600">LMS Misdinar</span>
                </div>
                @auth
                    <div class="flex items-center gap-2 sm:gap-4">
                        <span class="text-sm text-gray-600 truncate max-w-[150px]">{{ Auth::user()->name }}</span>
                        <span class="px-2 py-0.5 sm:py-1 text-xs font-medium rounded-full whitespace-nowrap bg-green-100 text-green-800">
                            Siswa
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-3 sm:gap-4">
                        <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">Login</a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-3 sm:mt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-3 sm:p-4 text-sm sm:text-base">
                {{ session('success') }}
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-3 sm:mt-4">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 sm:p-4 text-sm sm:text-base">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        @yield('content')
    </main>
</body>
</html>
