<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin — LMS Misdinar')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --sidebar-width: 256px;
            --sidebar-collapsed-width: 72px;
            --transition-speed: 300ms;
        }

        /* Sidebar base */
        #admin-sidebar {
            width: var(--sidebar-width);
            transition: width var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        /* Main content follows sidebar */
        #admin-main {
            margin-left: var(--sidebar-width);
            transition: margin-left var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Text fade animation */
        .sidebar-text,
        .sidebar-section-label {
            opacity: 1;
            white-space: nowrap;
            transition: opacity calc(var(--transition-speed) * 0.5) ease;
        }

        /* ===== COLLAPSED STATE ===== */
        body.sidebar-collapsed #admin-sidebar {
            width: var(--sidebar-collapsed-width);
        }
        body.sidebar-collapsed #admin-main {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Fade out all text */
        body.sidebar-collapsed .sidebar-text,
        body.sidebar-collapsed .sidebar-section-label {
            opacity: 0;
            pointer-events: none;
            width: 0;
            overflow: hidden;
        }

        /* Center nav icons */
        body.sidebar-collapsed .sidebar-nav-link {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        body.sidebar-collapsed .sidebar-nav-link .sidebar-icon {
            margin: 0;
        }
        body.sidebar-collapsed .sidebar-nav {
            padding-left: 0.625rem;
            padding-right: 0.625rem;
        }

        /* Brand: hide text, show icon only */
        body.sidebar-collapsed .sidebar-brand-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        body.sidebar-collapsed .sidebar-brand {
            justify-content: center;
        }

        /* Toggle button: center it */
        body.sidebar-collapsed .sidebar-header {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        body.sidebar-collapsed .sidebar-toggle-btn {
            padding: 0.5rem;
        }

        /* User footer: center avatar */
        body.sidebar-collapsed .sidebar-user {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        body.sidebar-collapsed .sidebar-user-info,
        body.sidebar-collapsed .sidebar-user-logout {
            opacity: 0;
            width: 0;
            overflow: hidden;
            pointer-events: none;
        }

        /* Tooltip on collapsed icons */
        .sidebar-nav-link {
            position: relative;
        }
        .sidebar-tooltip {
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background: #1e293b;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 150ms ease;
            z-index: 50;
        }
        .sidebar-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #1e293b;
        }
        body.sidebar-collapsed .sidebar-nav-link:hover .sidebar-tooltip {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" id="admin-body">

    {{-- Sidebar --}}
    <aside id="admin-sidebar" class="bg-white border-r border-gray-200 min-h-screen flex flex-col fixed inset-y-0 left-0 z-30">

        {{-- Header: Brand + Toggle --}}
        <div class="sidebar-header h-16 flex items-center justify-between px-4 border-b border-gray-200 transition-all duration-300">
            <div class="sidebar-brand flex items-center gap-3 min-w-0 transition-all duration-300">
                <img src="{{ asset('img/misdnar2.jpg') }}" alt="Logo Misdinar" class="w-9 h-9 rounded-lg object-cover shadow-sm flex-shrink-0">
                <span class="sidebar-brand-text text-lg font-bold text-misdinar-dark whitespace-nowrap transition-all duration-300">LMS Misdinar</span>
            </div>
            <button id="sidebar-toggle" class="sidebar-toggle-btn flex-shrink-0 p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition-colors" title="Toggle Sidebar">
                <svg id="toggle-icon-open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <svg id="toggle-icon-closed" class="w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav flex-1 px-4 py-6 space-y-1 overflow-y-auto overflow-x-hidden transition-all duration-300">
            <p class="sidebar-section-label px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 transition-all duration-200">Menu Utama</p>

            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                      {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="sidebar-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>
                </svg>
                <span class="sidebar-text">Dashboard</span>
                <span class="sidebar-tooltip">Dashboard</span>
            </a>

            <a href="{{ route('admin.questions.index') }}"
               class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                      {{ request()->routeIs('admin.questions.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="sidebar-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="sidebar-text">Kelola Soal</span>
                <span class="sidebar-tooltip">Kelola Soal</span>
            </a>

            @if(isset($currentExam))
            <p class="sidebar-section-label px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-3 transition-all duration-200">Ujian Aktif</p>
            <a href="{{ route('admin.exam.monitor', $currentExam) }}"
               class="sidebar-nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                      {{ request()->routeIs('admin.exam.monitor') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <svg class="sidebar-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="sidebar-text">Live Monitor</span>
                <span class="sidebar-tooltip">Live Monitor</span>
            </a>
            @endif
        </nav>

        {{-- User Footer --}}
        <div class="sidebar-user px-4 py-4 border-t border-gray-200 flex items-center gap-3 transition-all duration-300">
            <div class="w-9 h-9 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-indigo-600 font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
            <div class="sidebar-user-info flex-1 min-w-0 transition-all duration-200">
                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-500">Admin</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="sidebar-user-logout transition-all duration-200">
                @csrf
                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Logout">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div id="admin-main" class="flex-1 flex flex-col min-h-screen">
        {{-- Top Bar --}}
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-20">
            <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Admin</span>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mx-8 mt-4">
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg p-4 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-8 mt-4">
                <div class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main class="flex-1 p-8">
            @yield('content')
        </main>
    </div>

    <script>
        (function() {
            const body = document.getElementById('admin-body');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const iconOpen = document.getElementById('toggle-icon-open');
            const iconClosed = document.getElementById('toggle-icon-closed');
            const STORAGE_KEY = 'admin-sidebar-collapsed';

            // Restore state from localStorage
            if (localStorage.getItem(STORAGE_KEY) === '1') {
                body.classList.add('sidebar-collapsed');
                iconOpen.classList.add('hidden');
                iconClosed.classList.remove('hidden');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    body.classList.toggle('sidebar-collapsed');
                    const isCollapsed = body.classList.contains('sidebar-collapsed');

                    // Swap icons
                    iconOpen.classList.toggle('hidden', isCollapsed);
                    iconClosed.classList.toggle('hidden', !isCollapsed);

                    // Persist state
                    localStorage.setItem(STORAGE_KEY, isCollapsed ? '1' : '0');
                });
            }
        })();
    </script>
</body>
</html>
