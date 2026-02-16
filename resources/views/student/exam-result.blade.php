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
                <p class="text-3xl font-bold text-gray-800">{{ $correctAnswers }}/{{ $questions->count() }}</p>
                <p class="text-xs text-gray-500 mt-1">Jawaban Benar</p>
            </div>
        </div>
    </div>

    {{-- Review Questions --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Pembahasan Soal</h2>

        <div class="space-y-6">
            @foreach($questions as $index => $question)
                @php
                    $studentAnswer = $answers[$question->id] ?? null;
                    $isCorrect = $studentAnswer === $question->correct_answer;
                @endphp
                <div class="p-4 rounded-xl border-2 {{ $isCorrect ? 'border-green-200 bg-green-50/50' : 'border-red-200 bg-red-50/50' }}">
                    <div class="flex items-start justify-between mb-3">
                        <p class="font-semibold text-gray-900">{{ $index + 1 }}. {{ $question->question_text }}</p>
                        <span class="flex-shrink-0 ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                                     {{ $isCorrect ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $isCorrect ? '✓ Benar' : '✗ Salah' }}
                        </span>
                    </div>

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
                                    <span class="ml-auto text-green-600">✓</span>
                                @elseif($isStudentPick)
                                    <span class="ml-auto text-red-500">✗</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if(!$studentAnswer)
                        <p class="text-sm text-gray-400 mt-2 italic">Tidak dijawab</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

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
