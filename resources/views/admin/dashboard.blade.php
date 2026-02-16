@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="mt-1 text-gray-600">Kelola ujian dan pantau siswa.</p>
    </div>
    <a href="{{ route('admin.exam.create') }}"
       class="inline-flex items-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Buat Ujian Baru
    </a>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
        <span class="text-xl">✅</span>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3">
        <span class="text-xl">❌</span>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
@endif

@if($exams->isEmpty())
    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
        <div class="w-20 h-20 mx-auto mb-4 bg-indigo-50 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Belum ada ujian</h3>
        <p class="mt-1 text-gray-500 mb-4">Buat ujian pertama Anda sekarang!</p>
        <a href="{{ route('admin.exam.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Ujian Baru
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($exams as $exam)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            @switch($exam->status)
                                @case('draft') bg-gray-100 text-gray-800 @break
                                @case('lobby') bg-yellow-100 text-yellow-800 @break
                                @case('countdown') bg-orange-100 text-orange-800 @break
                                @case('started') bg-green-100 text-green-800 @break
                                @case('finished') bg-blue-100 text-blue-800 @break
                            @endswitch">
                            {{ strtoupper($exam->status) }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $exam->sessions_count }} siswa</span>
                    </div>

                    @if($exam->mata_pelajaran)
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-purple-50 text-purple-700 mb-1">{{ $exam->mata_pelajaran }}</span>
                    @endif

                    <h3 class="text-lg font-semibold text-gray-900">{{ $exam->title }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $exam->duration_minutes }} menit • {{ $exam->questions_count ?? $exam->questions()->count() }} soal</p>

                    {{-- Action Buttons --}}
                    <div class="mt-4 space-y-2">
                        {{-- Primary actions --}}
                        <div class="flex gap-2">
                            @if($exam->status === 'draft')
                                <form action="{{ route('admin.exam.openLobby', $exam) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                        🚪 Buka Lobby
                                    </button>
                                </form>
                            @endif

                            @if(in_array($exam->status, ['lobby', 'started', 'countdown']))
                                <a href="{{ route('admin.exam.monitor', $exam) }}"
                                   class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    📊 Monitor
                                </a>
                            @endif

                            @if($exam->status === 'finished')
                                <a href="{{ route('admin.exam.monitor', $exam) }}"
                                   class="flex-1 text-center bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    👁️ Lihat
                                </a>
                                <a href="{{ route('admin.exam.export', $exam) }}"
                                   class="flex-1 text-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    📥 Export
                                </a>
                            @endif
                        </div>

                        {{-- Secondary actions (edit/questions/delete) --}}
                        <div class="flex gap-2 pt-2 border-t border-gray-100">
                            <a href="{{ route('admin.exam.edit', $exam) }}"
                               class="flex-1 text-center text-xs font-medium py-1.5 px-3 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                ✏️ Edit
                            </a>
                            <a href="{{ route('admin.questions.show', $exam) }}"
                               class="flex-1 text-center text-xs font-medium py-1.5 px-3 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                               target="_blank">
                                📋 Soal
                            </a>
                            @if($exam->status === 'finished')
                                <form action="{{ route('admin.exam.reset', $exam) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('Reset ujian ini? Semua data sesi & jawaban akan dihapus!')">
                                    @csrf
                                    <button class="w-full text-xs font-medium py-1.5 px-3 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                                        🔄 Reset
                                    </button>
                                </form>
                            @endif
                            @if(in_array($exam->status, ['draft', 'finished']))
                                <form action="{{ route('admin.exam.delete', $exam) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('Yakin hapus ujian &quot;{{ $exam->title }}&quot;? Data soal & sesi akan ikut terhapus!')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="w-full text-xs font-medium py-1.5 px-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
