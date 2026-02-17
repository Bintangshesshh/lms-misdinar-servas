@extends('layouts.admin')
@section('title', 'Monitor - ' . $exam->title)
@section('page-title', 'Live Monitor: ' . $exam->title)

@section('content')
<div id="monitor-data"
     data-lobby-url="{{ route('admin.exam.lobbyStatus', $exam) }}"
     data-terminate-url="{{ route('admin.exam.terminateStudent', $exam) }}"
     data-reinstate-url="{{ route('admin.exam.reinstateStudent', $exam) }}"
     data-csrf="{{ csrf_token() }}">
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- LEFT: Student List --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Siswa
                    <span id="student-count" class="ml-2 px-2.5 py-0.5 text-sm bg-indigo-100 text-indigo-800 rounded-full">0</span>
                </h2>
                <span class="flex items-center gap-2 text-sm text-green-600">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Live
                </span>
            </div>

            <div class="divide-y divide-gray-200" id="student-list">
                <div class="px-6 py-12 text-center text-gray-400" id="empty-state">
                    <svg class="mx-auto h-10 w-10 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <p>Belum ada siswa yang bergabung</p>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Keterangan Status</p>
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-green-500"></span>
                    <span class="text-gray-600">Aman (80-100%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-yellow-500"></span>
                    <span class="text-gray-600">Peringatan (50-79%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-red-500"></span>
                    <span class="text-gray-600">Bahaya (&lt;50%)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full bg-gray-800"></span>
                    <span class="text-gray-600">TERMINATED</span>
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

            {{-- Counters --}}
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-green-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-black text-green-600" id="safe-counter">0</p>
                    <p class="text-xs text-green-700 mt-1">Aman</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-black text-yellow-600" id="warning-counter">0</p>
                    <p class="text-xs text-yellow-700 mt-1">Peringatan</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-black text-red-600" id="danger-counter">0</p>
                    <p class="text-xs text-red-700 mt-1">Bahaya</p>
                </div>
                <div class="bg-gray-100 rounded-lg p-3 text-center">
                    <p class="text-2xl font-black text-gray-800" id="terminated-counter">0</p>
                    <p class="text-xs text-gray-600 mt-1">Terminated</p>
                </div>
            </div>

            {{-- Student Counter --}}
            <div class="mb-6 bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-3xl font-black text-indigo-600" id="big-counter">0</p>
                <p class="text-sm text-gray-500 mt-1">Total Siswa</p>
            </div>

            {{-- Quick Edit Soal --}}
            @if(in_array($exam->status, ['lobby', 'started']))
                <a href="{{ route('admin.questions.show', $exam) }}" target="_blank"
                   class="w-full mb-4 flex items-center justify-center gap-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-3 px-4 rounded-xl text-sm border border-indigo-200 transition-colors">
                    ✏️ Edit Soal (tab baru)
                </a>
            @endif

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
                <div class="w-full bg-green-500 text-white font-bold py-4 px-6 rounded-xl text-lg text-center mb-3">
                    ✅ UJIAN BERLANGSUNG
                </div>

                {{-- STOP EXAM Button --}}
                <form action="{{ route('admin.exam.stop', $exam) }}" method="POST" id="stop-form">
                    @csrf
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02]"
                        onclick="return confirm('⛔ HENTIKAN UJIAN?\n\nSemua siswa akan otomatis dikumpulkan jawabannya dan ujian berakhir.\n\nTindakan ini TIDAK bisa dibatalkan!')">
                        ⛔ HENTIKAN UJIAN
                    </button>
                </form>
                <p class="text-xs text-gray-400 text-center mt-3">
                    Tekan ini jika ada masalah dan ujian harus dihentikan segera.
                </p>
            @elseif($exam->status === 'finished')
                <div class="w-full bg-gray-800 text-white font-bold py-4 px-6 rounded-xl text-lg text-center mb-3">
                    🏁 UJIAN SELESAI
                </div>

                {{-- Export Excel --}}
                <a href="{{ route('admin.exam.export', $exam) }}"
                   class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-lg hover:shadow-xl transition-all transform hover:scale-[1.02]">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    📊 Export ke Excel
                </a>
                <p class="text-xs text-gray-400 text-center mt-3">
                    Download laporan lengkap: skor, jawaban, pelanggaran, dan statistik.
                </p>
            @endif
        </div>
    </div>
</div>

<script>
    const monitorData = document.getElementById('monitor-data');
    const lobbyUrl = monitorData.dataset.lobbyUrl;
    const terminateUrl = monitorData.dataset.terminateUrl;
    const reinstateUrl = monitorData.dataset.reinstateUrl;
    const csrfToken = monitorData.dataset.csrf;

    function getStatusLevel(integrity, status) {
        if (status === 'blocked') return 'terminated';
        if (integrity >= 80) return 'safe';
        if (integrity >= 50) return 'warning';
        return 'danger';
    }

    function getStatusConfig(level) {
        switch (level) {
            case 'safe':
                return { bg: 'bg-green-500', ring: 'ring-green-300', text: 'text-green-700', badge: 'bg-green-100 text-green-800', label: '' };
            case 'warning':
                return { bg: 'bg-yellow-500', ring: 'ring-yellow-300', text: 'text-yellow-700', badge: 'bg-yellow-100 text-yellow-800', label: '⚠️' };
            case 'danger':
                return { bg: 'bg-red-500', ring: 'ring-red-300', text: 'text-red-700', badge: 'bg-red-100 text-red-800', label: '🚨' };
            case 'terminated':
                return { bg: 'bg-gray-800', ring: 'ring-gray-400', text: 'text-gray-900', badge: 'bg-gray-800 text-white', label: '⛔ TERMINATED' };
        }
    }

    function renderStudent(student) {
        const level = getStatusLevel(student.integrity, student.status);
        const cfg = getStatusConfig(level);

        const actionBtn = level === 'terminated'
            ? `<button onclick="reinstateStudent(${student.session_id})"
                 class="px-3 py-1.5 text-xs font-medium bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors">
                 ✅ Izinkan Kembali
               </button>`
            : (level === 'danger'
                ? `<button onclick="terminateStudent(${student.session_id})"
                     class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors">
                     ⛔ Terminate
                   </button>`
                : '');

        return `
            <div class="px-6 py-4 flex items-center justify-between ${level === 'terminated' ? 'bg-gray-50 opacity-75' : ''}">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div class="w-10 h-10 ${cfg.bg} bg-opacity-20 rounded-full flex items-center justify-center ring-2 ${cfg.ring}">
                            <span class="${cfg.text} font-bold text-sm">${student.name.charAt(0).toUpperCase()}</span>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 ${level === 'terminated' ? 'line-through' : ''}">${student.name}</p>
                        <p class="text-sm text-gray-500">${student.email}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    ${cfg.label ? `<span class="text-sm font-semibold">${cfg.label}</span>` : ''}
                    <span class="px-2.5 py-1 text-xs font-bold rounded-full ${cfg.badge}">
                        ${level === 'terminated' ? 'TERMINATED' : student.integrity + '%'}
                    </span>
                    <span class="text-xs text-gray-400">${student.joined_at}</span>
                    ${actionBtn}
                </div>
            </div>
        `;
    }

    async function terminateStudent(sessionId) {
        if (!confirm('Terminate siswa ini? Mereka tidak bisa melanjutkan ujian.')) return;
        try {
            await fetch(terminateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ session_id: sessionId })
            });
        } catch (e) { console.error(e); }
    }

    async function reinstateStudent(sessionId) {
        if (!confirm('Izinkan siswa ini melanjutkan ujian?')) return;
        try {
            await fetch(reinstateUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ session_id: sessionId })
            });
        } catch (e) { console.error(e); }
    }

    async function pollLobby() {
        try {
            const res = await fetch(lobbyUrl);
            const data = await res.json();

            document.getElementById('student-count').textContent = data.student_count;
            document.getElementById('big-counter').textContent = data.student_count;

            let safe = 0, warning = 0, danger = 0, terminated = 0;
            data.students.forEach(s => {
                const level = getStatusLevel(s.integrity, s.status);
                if (level === 'safe') safe++;
                else if (level === 'warning') warning++;
                else if (level === 'danger') danger++;
                else if (level === 'terminated') terminated++;
            });

            document.getElementById('safe-counter').textContent = safe;
            document.getElementById('warning-counter').textContent = warning;
            document.getElementById('danger-counter').textContent = danger;
            document.getElementById('terminated-counter').textContent = terminated;

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

    pollLobby();
    setInterval(pollLobby, 2000);
</script>
@endsection
