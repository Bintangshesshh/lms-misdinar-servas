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

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Simpan Soal
            </button>
        </form>
    </div>
</div>
@endsection
