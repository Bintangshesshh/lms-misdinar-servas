@extends('layouts.admin')
@section('title', 'Kelola Soal')
@section('page-title', 'Kelola Soal Ujian')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div>
        <p class="text-gray-500 text-sm">Pilih ujian untuk mengelola soal, atau buat ujian baru.</p>
    </div>
    <a href="{{ route('admin.exam.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors shadow-sm text-sm">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Buat Ujian Baru
    </a>
</div>

{{-- Filter by Mata Pelajaran --}}
@php
    $subjects = $exams->pluck('mata_pelajaran')->filter()->unique()->sort()->values();
    $grouped = $exams->groupBy(fn($e) => $e->mata_pelajaran ?? 'Tanpa Mata Pelajaran');
@endphp

@if($subjects->isNotEmpty())
    <div class="mb-6 flex flex-wrap gap-2" id="filter-pills">
        <button onclick="filterSubject('all')"
                class="filter-pill px-3 py-1.5 text-xs font-semibold rounded-full transition-colors bg-indigo-600 text-white"
                data-subject="all">
            Semua ({{ $exams->count() }})
        </button>
        @foreach($subjects as $subj)
            <button onclick="filterSubject('{{ Str::slug($subj) }}')"
                    class="filter-pill px-3 py-1.5 text-xs font-semibold rounded-full transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200"
                    data-subject="{{ Str::slug($subj) }}">
                {{ $subj }} ({{ $exams->where('mata_pelajaran', $subj)->count() }})
            </button>
        @endforeach
        @if($exams->whereNull('mata_pelajaran')->count() > 0 || $exams->where('mata_pelajaran', '')->count() > 0)
            <button onclick="filterSubject('tanpa-mata-pelajaran')"
                    class="filter-pill px-3 py-1.5 text-xs font-semibold rounded-full transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200"
                    data-subject="tanpa-mata-pelajaran">
                Lainnya ({{ $exams->filter(fn($e) => !$e->mata_pelajaran)->count() }})
            </button>
        @endif
    </div>
@endif

@if($exams->isEmpty())
    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
        <div class="w-20 h-20 mx-auto mb-4 bg-indigo-50 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Belum ada ujian</h3>
        <p class="mt-1 text-gray-500 mb-4">Buat ujian pertama untuk mulai menambahkan soal.</p>
        <a href="{{ route('admin.exam.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg text-sm transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Ujian Baru
        </a>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($exams as $exam)
            <div class="exam-card bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-all"
                 data-subject="{{ $exam->mata_pelajaran ? Str::slug($exam->mata_pelajaran) : 'tanpa-mata-pelajaran' }}">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            @switch($exam->status)
                                @case('draft') bg-gray-100 text-gray-800 @break
                                @case('lobby') bg-yellow-100 text-yellow-800 @break
                                @case('countdown') bg-orange-100 text-orange-800 @break
                                @case('started') bg-green-100 text-green-800 @break
                                @case('finished') bg-blue-100 text-blue-800 @break
                            @endswitch">
                            {{ strtoupper($exam->status ?? 'DRAFT') }}
                        </span>
                        <span class="text-sm font-bold text-indigo-600">{{ $exam->questions_count }} soal</span>
                    </div>

                    @if($exam->mata_pelajaran)
                        <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-purple-50 text-purple-700 mb-2">{{ $exam->mata_pelajaran }}</span>
                    @endif

                    <h3 class="text-lg font-semibold text-gray-900">{{ $exam->title }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $exam->duration_minutes }} menit • Total {{ $exam->questions->sum('points') ?? 0 }} poin</p>

                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.questions.show', $exam) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Kelola Soal
                        </a>
                        <a href="{{ route('admin.exam.edit', $exam) }}"
                           class="inline-flex items-center justify-center px-3 py-2.5 rounded-lg text-sm font-medium bg-gray-50 text-gray-600 hover:bg-gray-100 transition-colors"
                           title="Edit Ujian">
                            ✏️
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

<script>
function filterSubject(subject) {
    const cards = document.querySelectorAll('.exam-card');
    const pills = document.querySelectorAll('.filter-pill');

    pills.forEach(p => {
        if (p.dataset.subject === subject) {
            p.className = 'filter-pill px-3 py-1.5 text-xs font-semibold rounded-full transition-colors bg-indigo-600 text-white';
        } else {
            p.className = 'filter-pill px-3 py-1.5 text-xs font-semibold rounded-full transition-colors bg-gray-100 text-gray-600 hover:bg-gray-200';
        }
    });

    cards.forEach(card => {
        if (subject === 'all' || card.dataset.subject === subject) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
@endsection
