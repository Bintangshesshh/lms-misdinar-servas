@extends('layouts.app-custom')
@section('title', 'Monitor - ' . $exam->title)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $exam->title }}</h1>
        <p class="text-gray-500">Live Monitor — Durasi {{ $exam->duration_minutes }} menit</p>
    </div>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800">← Kembali</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- LEFT: Student List --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Siswa di Lobby
                    <span id="student-count" class="ml-2 px-2.5 py-0.5 text-sm bg-indigo-100 text-indigo-800 rounded-full">0</span>
                </h2>
                <span class="flex items-center gap-2 text-sm text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Live
                </span>
            </div>

            <div class="divide-y divide-gray-200" id="student-list">
                {{-- Populated by JS --}}
                <div class="px-6 py-12 text-center text-gray-400" id="empty-state">
                    <svg class="mx-auto h-10 w-10 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p>Belum ada siswa yang bergabung</p>
                </div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Control Panel --}}
    <div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">🎮 Kontrol Ujian</h2>

            {{-- Current Status --}}
            <div class="mb-6">
                <label class="text-sm text-gray-500">Status Saat Ini</label>
                <div id="exam-status-badge" class="mt-1">
                    <span class="px-3 py-1.5 text-sm font-semibold rounded-full
                        @switch($exam->status)
                            @case('lobby') bg-yellow-100 text-yellow-800 @break
                            @case('countdown') bg-orange-100 text-orange-800 @break
                            @case('started') bg-green-100 text-green-800 @break
                            @default bg-gray-100 text-gray-800
                        @endswitch">
                        {{ strtoupper($exam->status) }}
                    </span>
                </div>
            </div>

            {{-- Student Counter --}}
            <div class="mb-6 bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-black text-indigo-600" id="big-counter">0</p>
                <p class="text-sm text-gray-500 mt-1">Siswa Siap</p>
            </div>

            {{-- START Button --}}
            @if($exam->status === 'lobby')
                <form action="{{ route('admin.exam.start', $exam) }}" method="POST" id="start-form">
                    @csrf
                    <button type="submit" id="start-btn"
                        class="w-full bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        onclick="return confirm('Yakin mulai ujian? Semua siswa akan langsung masuk countdown 5 detik.')">
                        🚀 MULAI UJIAN
                    </button>
                </form>
                <p class="text-xs text-gray-400 text-center mt-3">
                    Pastikan semua siswa sudah bergabung sebelum menekan tombol ini.
                </p>
            @elseif($exam->status === 'countdown')
                <div class="w-full bg-orange-500 text-white font-bold py-4 px-6 rounded-xl text-lg text-center animate-pulse">
                    ⏳ COUNTDOWN...
                </div>
            @elseif($exam->status === 'started')
                <div class="w-full bg-green-500 text-white font-bold py-4 px-6 rounded-xl text-lg text-center">
                    ✅ UJIAN BERLANGSUNG
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    const lobbyUrl = "{{ route('admin.exam.lobbyStatus', $exam) }}";

    function renderStudent(student) {
        return `
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                        <span class="text-indigo-600 font-bold text-sm">${student.name.charAt(0).toUpperCase()}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">${student.name}</p>
                        <p class="text-sm text-gray-500">${student.email}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-400">${student.joined_at}</span>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full ${student.integrity >= 80 ? 'bg-green-100 text-green-800' : student.integrity >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                        ${student.integrity}%
                    </span>
                </div>
            </div>
        `;
    }

    async function pollLobby() {
        try {
            const res = await fetch(lobbyUrl);
            const data = await res.json();

            document.getElementById('student-count').textContent = data.student_count;
            document.getElementById('big-counter').textContent = data.student_count;

            const listEl = document.getElementById('student-list');
            const emptyEl = document.getElementById('empty-state');

            if (data.students.length > 0) {
                if (emptyEl) emptyEl.remove();
                listEl.innerHTML = data.students.map(renderStudent).join('');
            }
        } catch (e) {
            console.error('Poll error:', e);
        }
    }

    // Poll every 3 seconds
    pollLobby();
    setInterval(pollLobby, 3000);
</script>
@endsection
