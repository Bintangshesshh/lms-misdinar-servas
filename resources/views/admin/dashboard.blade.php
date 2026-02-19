@extends('layouts.admin')
@section('title', 'Admin Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="mb-8 flex items-center justify-between fade-in">
    <div>
        <h1 class="text-3xl font-bold text-misdinar-dark">Dashboard Administrasi</h1>
        <p class="mt-1 text-gray-600">Kelola ujian calon Misdinar dan pantau aktivitas peserta.</p>
    </div>
    <a href="{{ route('admin.exam.create') }}"
       class="btn-primary inline-flex items-center gap-2 px-5 py-3 text-white font-semibold rounded-xl shadow-sm">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Buat Ujian Baru
    </a>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3 fade-in">
        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
@endif
@if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-3 fade-in">
        <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
    </div>
@endif

@if($exams->isEmpty())
    <div class="text-center py-16 section-card bg-white fade-in">
        <div class="w-20 h-20 mx-auto mb-4 bg-misdinar-50 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-misdinar-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Belum ada ujian</h3>
        <p class="mt-1 text-gray-500 mb-4">Buat ujian pertama Anda sekarang!</p>
        <a href="{{ route('admin.exam.create') }}"
           class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-white font-medium rounded-lg text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Ujian Baru
        </a>
    </div>
@else
    <div class="dashboard-grid">
        @foreach($exams as $exam)
            <div class="card-hover bg-white overflow-hidden fade-in">
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
                        <span class="text-sm text-gray-500">{{ $exam->sessions_count }} peserta</span>
                    </div>

                    @if($exam->mata_pelajaran)
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-misdinar-50 text-misdinar-700 mb-1">{{ $exam->mata_pelajaran }}</span>
                    @endif

                    <h3 class="text-lg font-semibold text-misdinar-dark">{{ $exam->title }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $exam->duration_minutes }} menit • {{ $exam->questions_count ?? $exam->questions()->count() }} soal</p>

                    {{-- Privacy Settings Indicator --}}
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @if(!$exam->show_score_to_student)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700" title="Skor disembunyikan dari peserta">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                                Skor Hidden
                            </span>
                        @endif
                        @if(!$exam->show_answers)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-700" title="Pembahasan disembunyikan - Aman dari kebocoran">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                Pembahasan Hidden
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-700" title="PERINGATAN: Pembahasan terlihat - Risiko kebocoran">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Pembahasan Visible
                            </span>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-4 space-y-2">
                        {{-- Primary actions --}}
                        <div class="flex gap-2">
                            @if($exam->status === 'draft')
                                <form action="{{ route('admin.exam.openLobby', $exam) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg text-sm btn-transition">
                                        Buka Lobby
                                    </button>
                                </form>
                            @endif

                            @if(in_array($exam->status, ['lobby', 'started', 'countdown']))
                                <a href="{{ route('admin.exam.monitor', $exam) }}"
                                   class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    Monitor
                                </a>
                            @endif

                            @if($exam->status === 'finished')
                                <a href="{{ route('admin.exam.monitor', $exam) }}"
                                   class="flex-1 text-center bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    Lihat Hasil
                                </a>
                                <a href="{{ route('admin.exam.export', $exam) }}"
                                   class="flex-1 text-center bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                                    Export
                                </a>
                            @endif
                        </div>

                        {{-- Secondary actions (edit/questions/delete) --}}
                        <div class="flex gap-2 pt-2 border-t border-gray-100">
                            <a href="{{ route('admin.exam.edit', $exam) }}"
                               class="flex-1 text-center text-xs font-medium py-1.5 px-3 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors">
                                Edit
                            </a>
                            <a href="{{ route('admin.questions.show', $exam) }}"
                               class="flex-1 text-center text-xs font-medium py-1.5 px-3 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                               target="_blank">
                                Soal
                            </a>
                            @if($exam->status === 'finished')
                                <form action="{{ route('admin.exam.reset', $exam) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('Reset ujian ini? Semua data sesi & jawaban akan dihapus!')">
                                    @csrf
                                    <button class="w-full text-xs font-medium py-1.5 px-3 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors">
                                        Reset
                                    </button>
                                </form>
                            @endif
                            @if(in_array($exam->status, ['draft', 'finished']))
                                <form action="{{ route('admin.exam.delete', $exam) }}" method="POST" class="flex-1"
                                      onsubmit="return confirm('Yakin hapus ujian &quot;{{ $exam->title }}&quot;? Data soal & sesi akan ikut terhapus!')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="w-full text-xs font-medium py-1.5 px-3 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                                        Hapus
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
