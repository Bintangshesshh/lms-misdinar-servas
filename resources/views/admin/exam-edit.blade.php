@extends('layouts.admin')
@section('title', 'Edit Ujian - ' . $exam->title)
@section('page-title', 'Edit Ujian')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-6">
            <h1 class="text-2xl font-bold text-white">✏️ Edit Ujian</h1>
            <p class="text-amber-100 mt-1">Ubah informasi ujian.</p>
        </div>

        <form action="{{ route('admin.exam.update', $exam) }}" method="POST" class="px-8 py-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Judul Ujian</label>
                <input type="text" name="title" id="title" required
                       value="{{ old('title', $exam->title) }}"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mata_pelajaran" class="block text-sm font-semibold text-gray-700 mb-2">Mata Pelajaran</label>
                <input type="text" name="mata_pelajaran" id="mata_pelajaran"
                       value="{{ old('mata_pelajaran', $exam->mata_pelajaran) }}"
                       placeholder="Contoh: Matematika, IPA, Bahasa Indonesia"
                       list="mapel-list"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                <datalist id="mapel-list">
                    @foreach(\App\Models\Exam::whereNotNull('mata_pelajaran')->distinct()->pluck('mata_pelajaran') as $mapel)
                        <option value="{{ $mapel }}">
                    @endforeach
                </datalist>
                @error('mata_pelajaran')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duration_minutes" class="block text-sm font-semibold text-gray-700 mb-2">Durasi (menit)</label>
                <input type="number" name="duration_minutes" id="duration_minutes" required
                       value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="1" max="600"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                @error('duration_minutes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Privacy Settings --}}
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-5">
                <h3 class="text-sm font-bold text-orange-900 mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Pengaturan Privasi Hasil
                </h3>
                <p class="text-xs text-orange-700 mb-4">Atur apa yang bisa dilihat peserta setelah ujian selesai</p>

                <div class="space-y-3">
                    {{-- Show Score Toggle --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="show_score_to_student" value="1" 
                               {{ old('show_score_to_student', $exam->show_score_to_student) ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Tampilkan Skor ke Peserta</p>
                            <p class="text-xs text-gray-600">Peserta bisa melihat nilai akademik & integritas mereka</p>
                        </div>
                    </label>

                    {{-- Show Answers Toggle --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="show_answers" value="1"
                               {{ old('show_answers', $exam->show_answers) ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Tampilkan Pembahasan & Kunci Jawaban</p>
                            <p class="text-xs text-red-600 font-medium">⚠️ TIDAK DIREKOMENDASIKAN: Peserta yang sudah selesai bisa bocorkan jawaban ke yang masih ujian</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Info Ujian</h3>
                <div class="grid grid-cols-2 gap-3 text-sm text-gray-600">
                    <div>Status: <span class="font-bold">{{ strtoupper($exam->status) }}</span></div>
                    <div>Soal: <span class="font-bold">{{ $exam->questions()->count() }}</span></div>
                    <div>Peserta: <span class="font-bold">{{ $exam->sessions()->count() }}</span></div>
                    <div>Dibuat: <span class="font-bold">{{ $exam->created_at->format('d/m/Y') }}</span></div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                <button type="submit"
                        class="px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- Quick Links --}}
    <div class="mt-6 grid grid-cols-2 gap-4">
        <a href="{{ route('admin.questions.show', $exam) }}"
           class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow flex items-center gap-3">
            <span class="text-2xl">📋</span>
            <div>
                <p class="font-semibold text-gray-900">Kelola Soal</p>
                <p class="text-xs text-gray-500">{{ $exam->questions()->count() }} soal</p>
            </div>
        </a>
        @if($exam->status !== 'draft')
            <a href="{{ route('admin.exam.monitor', $exam) }}"
               class="bg-white border border-gray-200 rounded-xl p-4 hover:shadow-md transition-shadow flex items-center gap-3">
                <span class="text-2xl">📊</span>
                <div>
                    <p class="font-semibold text-gray-900">Monitor Ujian</p>
                    <p class="text-xs text-gray-500">{{ strtoupper($exam->status) }}</p>
                </div>
            </a>
        @endif
    </div>
</div>
@endsection
