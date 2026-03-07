@extends('layouts.admin')
@section('title', 'Tambah Soal - ' . $exam->title)
@section('page-title', 'Tambah Soal: ' . $exam->title)

@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('admin.questions.show', $exam) }}" class="text-sm text-indigo-600 hover:text-indigo-800 mb-6 inline-flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke daftar soal
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-5">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center text-white font-bold text-lg">
                    {{ $exam->questions()->count() + 1 }}
                </span>
                <div>
                    <h1 class="text-xl font-bold text-white">Tambah Soal Baru</h1>
                    <p class="text-indigo-100 text-sm">{{ $exam->title }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.questions.store', $exam) }}" method="POST" class="p-8 space-y-6">
            @csrf

            {{-- Question Type Selector --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipe Soal</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="question_type" value="multiple_choice" class="peer sr-only" {{ old('question_type', 'multiple_choice') === 'multiple_choice' ? 'checked' : '' }} onchange="toggleQuestionType()">
                        <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-indigo-500 peer-checked:bg-indigo-50 border-gray-200 hover:border-gray-300">
                            <span class="text-lg">📝</span>
                            <p class="text-sm font-medium mt-1">Pilihan Ganda</p>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="question_type" value="essay" class="peer sr-only" {{ old('question_type') === 'essay' ? 'checked' : '' }} onchange="toggleQuestionType()">
                        <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 border-gray-200 hover:border-gray-300">
                            <span class="text-lg">✍️</span>
                            <p class="text-sm font-medium mt-1">Essay</p>
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label for="question_text" class="block text-sm font-semibold text-gray-700 mb-2">Pertanyaan</label>
                <textarea name="question_text" id="question_text" rows="3" required
                    class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('question_text') border-red-500 @enderror"
                    placeholder="Tulis pertanyaan di sini...">{{ old('question_text') }}</textarea>
                @error('question_text') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- MC Options (hidden for essay) --}}
            <div id="mc-options-section">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                        <div>
                            <label for="option_{{ $key }}" class="block text-sm font-medium text-gray-700 mb-1">Opsi {{ $label }}</label>
                            <input type="text" name="option_{{ $key }}" id="option_{{ $key }}"
                                value="{{ old('option_' . $key) }}"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500 @error('option_' . $key) border-red-500 @enderror mc-required-field"
                                placeholder="Jawaban {{ $label }}">
                            @error('option_' . $key) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <label for="correct_answer" class="block text-sm font-medium text-gray-700 mb-1">Jawaban Benar</label>
                    <select name="correct_answer" id="correct_answer"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500 mc-required-field">
                        <option value="">Pilih...</option>
                        @foreach(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                            <option value="{{ $key }}" {{ old('correct_answer') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('correct_answer') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Essay Info (hidden for MC) --}}
            <div id="essay-info-section" class="hidden">
                <div class="bg-purple-50 border border-purple-200 rounded-xl p-4">
                    <p class="text-sm text-purple-700">
                        <strong>Soal Essay:</strong> Siswa akan mengetik jawaban sendiri di textarea. Jawaban essay harus dinilai manual oleh admin.
                    </p>
                </div>
            </div>

            <div>
                <label for="points" class="block text-sm font-medium text-gray-700 mb-1">Poin</label>
                <input type="number" name="points" id="points" min="1" max="100" value="{{ old('points', 10) }}" required
                    class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                @error('points') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" id="btn-submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors flex items-center justify-center gap-2">
                <svg id="btn-icon" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span id="btn-text">Simpan Soal</span>
            </button>
        </form>
    </div>
</div>

<script>
// Prevent double submit
(function() {
    var form = document.querySelector('form[action]');
    var submitted = false;
    form.addEventListener('submit', function(e) {
        if (submitted) { e.preventDefault(); return; }
        submitted = true;
        var btn = document.getElementById('btn-submit');
        btn.disabled = true;
        btn.classList.add('opacity-60', 'cursor-not-allowed');
        document.getElementById('btn-text').textContent = 'Menyimpan...';
        document.getElementById('btn-icon').innerHTML = '<circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="31" stroke-dashoffset="10"><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.8s" repeatCount="indefinite"/></circle>';
    });
})();

function toggleQuestionType() {
    var isEssay = document.querySelector('input[name="question_type"][value="essay"]').checked;
    var mcSection = document.getElementById('mc-options-section');
    var essaySection = document.getElementById('essay-info-section');
    var mcFields = document.querySelectorAll('.mc-required-field');

    if (isEssay) {
        mcSection.classList.add('hidden');
        essaySection.classList.remove('hidden');
        mcFields.forEach(function(f) { f.removeAttribute('required'); });
    } else {
        mcSection.classList.remove('hidden');
        essaySection.classList.add('hidden');
        mcFields.forEach(function(f) { f.setAttribute('required', ''); });
    }
}
// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleQuestionType);
</script>
@endsection
