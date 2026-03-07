@extends('layouts.app-custom')
@section('title', 'Hasil Ujian - ' . $exam->title)

@section('content')
<div class="max-w-3xl mx-auto">

    {{-- Result Summary Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 mb-6 text-center">
        <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center
                    {{ $session->score_academic >= 70 ? 'bg-green-100' : 'bg-red-100' }}">
            @if($session->score_academic >= 70)
                <svg class="w-10 h-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @endif
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-1">Ujian Selesai!</h1>
        <p class="text-gray-500 mb-6">{{ $exam->title }}</p>

        @if($exam->show_score_to_student)
            <div class="grid grid-cols-3 gap-4 max-w-md mx-auto">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-3xl font-bold text-indigo-600">{{ $session->score_academic }}</p>
                    <p class="text-xs text-gray-500 mt-1">Nilai Akademik</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-3xl font-bold {{ $session->score_integrity >= 80 ? 'text-green-600' : ($session->score_integrity >= 50 ? 'text-yellow-600' : 'text-red-600') }}">{{ $session->score_integrity }}%</p>
                    <p class="text-xs text-gray-500 mt-1">Skor Integritas</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-3xl font-bold text-gray-800">
                        @if(($totalScoredQuestions ?? 0) > 0)
                            {{ $correctAnswers }}/{{ $totalScoredQuestions }}
                        @else
                            -
                        @endif
                    </p>
                    <p class="text-xs text-gray-500 mt-1">Jawaban Benar (PG)</p>
                </div>
            </div>
        @else
            <div class="bg-gray-50 rounded-xl p-6 max-w-md mx-auto">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="text-gray-700 font-medium">Skor Disembunyikan</p>
                <p class="text-sm text-gray-500 mt-2">Admin belum mengizinkan peserta untuk melihat skor. Silakan hubungi admin untuk informasi lebih lanjut.</p>
            </div>
        @endif
    </div>

    @if($exam->show_answers)
        {{-- Review Questions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Pembahasan Soal</h2>

        <div class="space-y-6">
            @foreach($questions as $index => $question)
                @php
                    $answer = $answers->get($question->id);
                    $isEssay = $question->isEssay();
                    $studentAnswer = $answer?->selected_answer;
                    $essayText = $answer?->answer_text;
                    $isCorrect = $question->isMultipleChoice() && $studentAnswer !== null && strtolower($studentAnswer) === strtolower((string) $question->correct_answer);
                @endphp
                <div class="p-4 rounded-xl border-2 {{ $isEssay ? 'border-purple-200 bg-purple-50/50' : ($isCorrect ? 'border-green-200 bg-green-50/50' : 'border-red-200 bg-red-50/50') }}">
                    <div class="flex items-start justify-between mb-3">
                        <p class="font-semibold text-gray-900">{{ $index + 1 }}. {{ $question->question_text }}</p>
                        <span class="flex-shrink-0 ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                                     {{ $isEssay ? 'bg-purple-100 text-purple-700' : ($isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                            {{ $isEssay ? 'ESSAY' : ($isCorrect ? 'BENAR' : 'SALAH') }}
                        </span>
                    </div>

                    @if($isEssay)
                        <div class="rounded-lg border border-purple-200 bg-white p-3 text-sm">
                            <p class="text-xs font-semibold text-purple-700 mb-2">Jawaban Anda</p>
                            @if($essayText)
                                <p class="whitespace-pre-line text-gray-800">{{ $essayText }}</p>
                            @else
                                <p class="text-gray-400 italic">Tidak dijawab</p>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                            @foreach(['a', 'b', 'c', 'd'] as $opt)
                                @php
                                    $optionField = 'option_' . $opt;
                                    $isStudentPick = $studentAnswer === $opt;
                                    $isCorrectAnswer = $question->correct_answer === $opt;
                                @endphp
                                <div class="flex items-center gap-2 px-3 py-2 rounded-lg
                                            {{ $isCorrectAnswer ? 'bg-green-100 text-green-800 font-semibold' : ($isStudentPick && !$isCorrectAnswer ? 'bg-red-100 text-red-800' : 'text-gray-600') }}">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                                                 {{ $isCorrectAnswer ? 'bg-green-600 text-white' : ($isStudentPick ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-500') }}">
                                        {{ strtoupper($opt) }}
                                    </span>
                                    <span>{{ $question->$optionField }}</span>
                                    @if($isCorrectAnswer)
                                        <svg class="ml-auto w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @elseif($isStudentPick)
                                        <svg class="ml-auto w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if(!$studentAnswer)
                            <p class="text-sm text-gray-400 mt-2 italic">Tidak dijawab</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @else
        {{-- Answers Hidden Message --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Pembahasan Disembunyikan</h3>
            <p class="text-gray-600 max-w-md mx-auto">
                Admin telah menyembunyikan kunci jawaban dan pembahasan untuk ujian ini. 
                Silakan hubungi admin jika Anda memerlukan informasi lebih lanjut tentang hasil ujian Anda.
            </p>
        </div>
    @endif

    <div class="mt-6 text-center">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>
</div>
@endsection
