@extends('layouts.admin')
@section('title', 'Soal - ' . $exam->title)
@section('page-title', 'Soal: ' . $exam->title)

@section('content')
{{-- Header --}}
<div class="mb-6">
    <a href="{{ route('admin.questions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-3">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar ujian
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                @if($exam->mata_pelajaran)
                    <span class="inline-block px-2.5 py-0.5 text-xs font-medium rounded bg-purple-50 text-purple-700 mb-1">{{ $exam->mata_pelajaran }}</span>
                @endif
                <h2 class="text-xl font-bold text-gray-900">{{ $exam->title }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $exam->questions->count() }} soal &middot; Total {{ $exam->questions->sum('points') }} poin &middot; {{ $exam->duration_minutes }} menit
                </p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Set All Points --}}
                <form action="{{ route('admin.questions.setAllPoints', $exam) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <input type="number" name="points" min="1" max="100" value="10" placeholder="Poin"
                           class="w-20 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors whitespace-nowrap"
                            onclick="return confirm('Set semua soal ke poin yang sama?')">
                        ⚡ Set Semua
                    </button>
                </form>
                <a href="{{ route('admin.exam.edit', $exam) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                    ✏️ Edit Ujian
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Question List --}}
<div class="space-y-4 mb-8">
    @forelse($exam->questions as $index => $question)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 group" id="question-{{ $question->id }}">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-sm font-bold">
                            {{ $index + 1 }}
                        </span>
                        <span class="px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">
                            {{ $question->points }} poin
                        </span>
                    </div>
                    <p class="text-gray-900 font-medium mb-4 whitespace-pre-line">{{ $question->question_text }}</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach(['a', 'b', 'c', 'd'] as $opt)
                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm
                                {{ $question->correct_answer === $opt ? 'bg-green-50 border border-green-300 text-green-800' : 'bg-gray-50 text-gray-700' }}">
                                <span class="font-bold uppercase">{{ $opt }}.</span>
                                <span class="flex-1">{{ $question->{'option_' . $opt} }}</span>
                                @if($question->correct_answer === $opt)
                                    <span class="text-green-600 font-bold">✓</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-1 ml-4 opacity-30 group-hover:opacity-100 transition-opacity">
                    <a href="{{ route('admin.questions.edit', [$exam, $question]) }}"
                       class="p-2 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition-colors" title="Edit Soal">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </a>
                    <form action="{{ route('admin.questions.destroy', [$exam, $question]) }}" method="POST"
                          onsubmit="return confirm('Hapus soal nomor {{ $index + 1 }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors" title="Hapus Soal">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-12 bg-white rounded-xl border border-dashed border-gray-300">
            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500">Belum ada soal</p>
            <p class="text-sm text-gray-400 mt-1">Tambahkan soal pertama di bawah ini 👇</p>
        </div>
    @endforelse
</div>

{{-- Inline Add Question Form --}}
<div class="bg-white rounded-2xl shadow-sm border-2 border-dashed border-indigo-300 overflow-hidden" id="add-question-section">
    <button type="button" onclick="toggleAddForm()"
            class="w-full px-6 py-4 flex items-center justify-center gap-2 text-indigo-600 font-semibold hover:bg-indigo-50 transition-colors" id="add-btn">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tambah Soal Baru (#{{ $exam->questions->count() + 1 }})
    </button>

    <div id="add-form" class="hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4 flex items-center justify-between">
            <h3 class="text-white font-bold text-lg">➕ Tambah Soal #{{ $exam->questions->count() + 1 }}</h3>
            <button type="button" onclick="toggleAddForm()" class="text-white/70 hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form action="{{ route('admin.questions.store', $exam) }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div>
                <label for="question_text" class="block text-sm font-semibold text-gray-700 mb-2">Pertanyaan</label>
                <textarea name="question_text" id="question_text" rows="3" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('question_text') border-red-500 @enderror"
                    placeholder="Tulis pertanyaan di sini...">{{ old('question_text') }}</textarea>
                @error('question_text') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                    <div>
                        <label for="option_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">Opsi {{ $label }}</label>
                        <input type="text" name="option_{{ $key }}" id="option_{{ $key }}" required
                            value="{{ old('option_' . $key) }}"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('option_' . $key) border-red-500 @enderror"
                            placeholder="Jawaban {{ $label }}">
                        @error('option_' . $key) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-1">Jawaban Benar</label>
                    <select name="correct_answer" id="correct_answer" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Pilih...</option>
                        @foreach(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                            <option value="{{ $key }}" {{ old('correct_answer') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('correct_answer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="points" class="block text-sm font-medium text-gray-700 mb-1">Poin</label>
                    <input type="number" name="points" id="points" min="1" max="100" value="{{ old('points', 10) }}" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('points') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-200">
                <button type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Soal
                </button>
                <button type="button" onclick="toggleAddForm()"
                    class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-medium rounded-xl transition-colors">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<div id="flash-flags" class="hidden" data-has-errors="{{ $errors->any() ? '1' : '0' }}" data-has-success="{{ session('success') ? '1' : '0' }}"></div>

<script>
function toggleAddForm() {
    const btn = document.getElementById('add-btn');
    const form = document.getElementById('add-form');
    const isHidden = form.classList.contains('hidden');
    btn.classList.toggle('hidden', isHidden);
    form.classList.toggle('hidden', !isHidden);
    if (isHidden) {
        const textarea = document.getElementById('question_text');
        if (textarea) {
            textarea.focus();
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const flagsEl = document.getElementById('flash-flags');
    const hasErrors = flagsEl && flagsEl.dataset.hasErrors === '1';
    const hasSuccess = flagsEl && flagsEl.dataset.hasSuccess === '1';

    if (hasErrors) {
        toggleAddForm();
    }

    if (hasSuccess) {
        const cards = document.querySelectorAll('[id^="question-"]');
        if (cards.length > 0) {
            const last = cards[cards.length - 1];
            last.scrollIntoView({ behavior: 'smooth', block: 'center' });
            last.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-2');
            setTimeout(() => last.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2'), 3000);
        }
    }
});
</script>
@endsection
