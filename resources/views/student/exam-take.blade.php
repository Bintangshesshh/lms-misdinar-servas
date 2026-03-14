@extends('layouts.app-custom')
@section('title', 'Ujian - ' . $exam->title)

@section('content')
<div class="max-w-4xl mx-auto">
    <div id="exam-data"
         data-session-id="{{ $session->id }}"
         data-exam-id="{{ $exam->id }}"
         data-score="{{ $session->score_integrity }}"
         data-csrf="{{ csrf_token() }}"
         data-total-questions="{{ $questions->count() }}"
         data-duration="{{ $exam->duration_minutes }}"
         data-started-at="{{ $exam->started_at?->toISOString() }}"
         class="hidden">
    </div>

    {{-- Sticky Top Bar: Timer + Progress --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 px-3 py-2.5 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-2">
            <div id="timer-display" class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 text-white rounded-lg font-mono">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="timer-text" class="text-sm font-bold tracking-wider">--:--</span>
            </div>
            <span class="text-xs text-gray-400 hidden sm:inline">Sisa waktu</span>
        </div>
        <span class="text-sm text-gray-500">
            <span id="answered-top" class="font-bold text-indigo-600">{{ count($answers) }}</span>/{{ $questions->count() }} dijawab
        </span>
    </div>

    {{-- Force-stop banner (hidden) --}}
    <div id="force-stop-banner" class="hidden mb-4 bg-red-600 text-white rounded-xl p-4 text-center">
        <p class="font-bold text-lg">UJIAN DIHENTIKAN</p>
        <p class="text-sm mt-1 text-red-100">Ujian telah dihentikan oleh Admin. Jawaban Anda sudah disimpan. Mengalihkan ke halaman hasil...</p>
    </div>

    {{-- INITIAL Fullscreen Entry Overlay — blocks exam until user clicks (provides user gesture) --}}
    <div id="fullscreen-entry-overlay" class="hidden fixed inset-0 z-[65] flex items-center justify-center" style="background:#1e1b4b">
        <div class="text-center max-w-md px-6">
            <div class="w-24 h-24 mx-auto mb-6 bg-indigo-600 rounded-full flex items-center justify-center">
                <svg class="w-14 h-14 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-3">MASUK MODE UJIAN</h2>
            <p class="text-indigo-200 mb-2">Ujian akan berjalan dalam mode layar penuh (fullscreen).</p>
            <p class="text-indigo-300 text-sm mb-6">Klik tombol di bawah untuk memulai. Jangan keluar dari mode fullscreen selama ujian berlangsung.</p>
            <button id="btn-enter-fullscreen"
                    class="px-10 py-5 bg-white text-indigo-900 font-bold rounded-xl hover:bg-gray-100 transition-colors text-lg shadow-lg animate-pulse">
                MULAI UJIAN FULLSCREEN
            </button>
            <p class="mt-4 text-indigo-400 text-xs">Mode fullscreen melindungi Anda dari pelanggaran yang tidak disengaja</p>
        </div>
    </div>

    {{-- Fullscreen Warning Overlay (shown when user exits fullscreen during exam) --}}
    <div id="fullscreen-warning-overlay" class="hidden fixed inset-0 z-[60] flex items-center justify-center" style="background:#7f1d1d">
        <div class="text-center max-w-md px-6">
            <div class="w-20 h-20 mx-auto mb-6 bg-red-600 rounded-full flex items-center justify-center animate-pulse">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-3">MODE FULLSCREEN DIPERLUKAN</h2>
            <p class="text-red-200 mb-2">Anda keluar dari mode fullscreen. Ini tercatat sebagai pelanggaran.</p>
            <p class="text-red-300 text-sm mb-6">Klik tombol di bawah untuk kembali ke mode fullscreen dan melanjutkan ujian.</p>
            <button id="btn-reenter-fullscreen"
                    class="px-8 py-4 bg-white text-red-900 font-bold rounded-xl hover:bg-gray-100 transition-colors text-lg shadow-lg">
                KEMBALI KE FULLSCREEN
            </button>
            <p class="mt-4 text-red-400 text-xs">Setiap pelanggaran dihitung. Jika mencapai {{ \App\Http\Controllers\Api\IntegrityController::MAX_VIOLATIONS }} pelanggaran, ujian otomatis dikumpulkan.</p>
        </div>
    </div>

    {{-- Mobile Split Screen Warning Overlay --}}
    <div id="split-screen-overlay" class="hidden fixed inset-0 z-[55] flex items-center justify-center" style="background:#7c2d12">
        <div class="text-center max-w-md px-6">
            <div class="w-20 h-20 mx-auto mb-6 bg-orange-600 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-3">SPLIT SCREEN TERDETEKSI!</h2>
            <p class="text-orange-200 mb-2">Anda terdeteksi menggunakan split screen atau mengubah ukuran browser.</p>
            <p class="text-orange-300 text-sm mb-6">Tutup aplikasi lain dan gunakan browser dalam mode penuh untuk melanjutkan.</p>
            <button id="btn-dismiss-split"
                    class="px-8 py-4 bg-white text-orange-900 font-bold rounded-xl hover:bg-gray-100 transition-colors text-lg shadow-lg">
                SAYA MENGERTI
            </button>
        </div>
    </div>

    {{-- TERMINATED Overlay (fullscreen block) --}}
    <div id="terminated-overlay" class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-95 flex items-center justify-center">
        <div class="text-center max-w-md mx-auto px-6">
            <div class="w-24 h-24 mx-auto mb-6 bg-red-600 rounded-full flex items-center justify-center">
                <svg class="w-14 h-14 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h2 class="text-3xl font-black text-white mb-3">UJIAN DIKUMPULKAN</h2>
            <p class="text-red-400 text-lg font-semibold mb-2">Anda telah melanggar 5 kali</p>
            <p class="text-gray-400 mb-6">Ujian Anda telah otomatis dikumpulkan karena terlalu banyak pelanggaran. Jawaban yang telah Anda masukkan tetap tersimpan.</p>
            <div id="terminated-waiting" class="bg-red-900 bg-opacity-50 rounded-xl p-4 border border-red-700 mb-4">
                <p class="text-red-300 text-sm">Menunggu keputusan Admin...</p>
            </div>
            <div id="terminated-reinstated" class="hidden bg-green-900 bg-opacity-50 rounded-xl p-4 border border-green-700 mb-4">
                <p class="text-green-300 text-sm font-semibold">Admin telah mengizinkan Anda kembali!</p>
                <p class="text-green-400 text-xs mt-1">Anda bisa kembali ke dashboard dan masuk ujian lagi.</p>
            </div>
            <a href="{{ route('student.dashboard') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-900 font-semibold rounded-xl hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    {{-- Violation Counter Bar --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-3 sm:p-4">
        <div class="flex items-center justify-between mb-1.5">
            <span class="text-xs sm:text-sm font-medium text-gray-700">Pelanggaran</span>
            <span id="integrity-score" class="text-xs sm:text-sm font-bold text-green-600">
                <span id="violation-count-text">{{ $session->violation_count ?? 0 }}</span> / 5 pelanggaran
            </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 sm:h-3">
            <div id="integrity-bar" class="bg-green-500 h-2 sm:h-3 rounded-full transition-all duration-500" style="width: {{ (($session->violation_count ?? 0) / 5) * 100 }}%"></div>
        </div>
        <p class="text-[10px] sm:text-xs text-gray-400 mt-1">Jika mencapai 5 pelanggaran, ujian akan otomatis dikumpulkan.</p>
    </div>

    <style>#question-nav-track::-webkit-scrollbar{display:none}</style>

    {{-- Warning Banner (hidden by default) --}}
    <div id="cheat-warning" class="hidden mb-4 bg-red-50 border-2 border-red-300 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-red-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-1.964-1.333-2.732 0L3.082 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <p class="font-bold text-red-800">PELANGGARAN TERDETEKSI!</p>
                <p class="text-sm text-red-600" id="warning-text">Anda terdeteksi meninggalkan halaman ujian.</p>
            </div>
        </div>
    </div>

    {{-- Screenshot Warning (persistent) --}}
    <div id="screenshot-warning" class="hidden mb-4 bg-orange-50 border-2 border-orange-400 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-orange-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <div>
                <p class="font-bold text-orange-800">SCREENSHOT TERDETEKSI!</p>
                <p class="text-sm text-orange-600">Upaya screenshot terdeteksi dan dicatat. Skor integritas dikurangi.</p>
            </div>
        </div>
    </div>

    {{-- Grace Period Toast (friendly notification return) --}}
    <div id="grace-toast" class="hidden fixed top-6 left-1/2 -translate-x-1/2 z-[9999]">
        <div id="grace-toast-inner" class="flex items-center gap-3 px-6 py-4 rounded-2xl shadow-lg border bg-white">
            <span id="grace-toast-icon" class="text-2xl"></span>
            <div>
                <p id="grace-toast-title" class="font-bold text-sm"></p>
                <p id="grace-toast-text" class="text-xs"></p>
            </div>
        </div>
    </div>

    {{-- Exam Header --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
        <div class="flex items-center justify-between">
            <div class="min-w-0 flex-1">
                <h1 class="text-base sm:text-xl font-bold text-gray-900 truncate">{{ $exam->title }}</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">{{ $questions->count() }} Soal • {{ $exam->duration_minutes }} menit</p>
            </div>
            <div class="text-right flex-shrink-0 ml-3">
                <p class="text-xs text-gray-400">Dijawab</p>
                <p class="text-base sm:text-lg font-bold text-indigo-600">
                    <span id="answered-count">{{ count($answers) }}</span> / {{ $questions->count() }}
                </p>
            </div>
        </div>

        {{-- Question Navigation — Horizontal Circular Slider --}}
        <div class="mt-4" id="question-nav">
            <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-xs text-gray-500">Geser nomor soal untuk navigasi cepat</p>
                <div class="flex items-center gap-1.5">
                    <button type="button" id="nav-scroll-prev" aria-label="Geser nomor ke kiri"
                            class="w-8 h-8 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 active:scale-95 transition">
                        &lsaquo;
                    </button>
                    <button type="button" id="nav-scroll-next" aria-label="Geser nomor ke kanan"
                            class="w-8 h-8 rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50 active:scale-95 transition">
                        &rsaquo;
                    </button>
                </div>
            </div>
            <div id="question-nav-track" class="flex gap-2 overflow-x-auto py-2 px-2 -mx-2 snap-x" style="scrollbar-width:none;-ms-overflow-style:none;">
                @foreach($questions as $i => $q)
                    <button type="button"
                            data-nav-index="{{ $i }}"
                            id="nav-dot-{{ $i }}"
                            class="nav-dot-btn flex-shrink-0 w-10 h-10 sm:w-11 sm:h-11 rounded-full text-xs sm:text-sm font-semibold transition-all snap-start ring-offset-2 focus:outline-none
                                   {{ isset($answers[$q->id]) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                            title="Soal {{ $i + 1 }}"
                            aria-label="Soal {{ $i + 1 }}">
                        {{ $i + 1 }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Questions Container --}}
    <div id="questions-container">
        @foreach($questions as $index => $question)
            <div class="question-slide {{ $index === 0 ? '' : 'hidden' }}"
                 data-question-index="{{ $index }}"
                 data-question-id="{{ $question->id }}">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6 mb-4">
                    {{-- Question Number + Points --}}
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs sm:text-sm font-semibold">
                            Soal {{ $index + 1 }} dari {{ $questions->count() }}
                        </span>
                        <span class="text-xs sm:text-sm text-gray-400 font-medium">{{ $question->points }} poin</span>
                    </div>

                    {{-- Question Text --}}
                    <div class="mb-4 sm:mb-6">
                        <p class="text-base sm:text-lg text-gray-900 leading-relaxed">{{ $question->question_text }}</p>
                    </div>

                    {{-- Answer Options --}}
                    @if($question->isEssay())
                        {{-- Essay Question --}}
                        <div class="space-y-3" id="options-{{ $question->id }}">
                            <textarea name="essay_{{ $question->id }}"
                                      id="essay-{{ $question->id }}"
                                      data-question-id="{{ $question->id }}"
                                      data-question-index="{{ $index }}"
                                      data-question-type="essay"
                                      class="essay-textarea w-full border-2 border-gray-200 rounded-xl p-4 text-base text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-colors resize-y"
                                      rows="6"
                                      placeholder="Tulis jawaban Anda di sini..."
                                      >{{ $answers[$question->id] ?? '' }}</textarea>
                            <p class="text-xs text-gray-400">
                                <span id="char-count-{{ $question->id }}">{{ strlen($answers[$question->id] ?? '') }}</span> karakter
                            </p>
                        </div>
                    @else
                        {{-- Multiple Choice --}}
                        <div class="space-y-3" id="options-{{ $question->id }}">
                            @foreach(['a', 'b', 'c', 'd'] as $opt)
                                @php
                                    $optionField = 'option_' . $opt;
                                    $isSelected = isset($answers[$question->id]) && $answers[$question->id] === $opt;
                                @endphp
                                <label class="answer-option group flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer
                                              {{ $isSelected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200' }}"
                                       data-question-id="{{ $question->id }}"
                                       data-option="{{ $opt }}">
                                    <input type="radio"
                                           name="answer_{{ $question->id }}"
                                           value="{{ $opt }}"
                                           {{ $isSelected ? 'checked' : '' }}
                                           data-question-id="{{ $question->id }}"
                                           data-option="{{ $opt }}"
                                           data-question-index="{{ $index }}"
                                           class="hidden answer-radio-input">
                                    <div class="answer-radio w-10 h-10 rounded-full border-2 flex items-center justify-center flex-shrink-0 font-bold text-sm
                                                {{ $isSelected ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-gray-300 text-gray-500' }}">
                                        {{ strtoupper($opt) }}
                                    </div>
                                    <span class="text-gray-800 text-base">{{ $question->$optionField }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Navigation Buttons --}}
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    @if($index > 0)
                        <button type="button" data-goto="{{ $index - 1 }}" class="nav-prev-btn flex items-center gap-1.5 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            <span class="hidden sm:inline">Sebelumnya</span>
                            <span class="sm:hidden">Prev</span>
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($index < $questions->count() - 1)
                        <button type="button" data-goto="{{ $index + 1 }}" class="nav-next-btn flex items-center gap-1.5 px-4 py-2.5 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                            <span class="hidden sm:inline">Selanjutnya</span>
                            <span class="sm:hidden">Next</span>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button type="button" id="btn-submit-exam"
                                class="flex items-center gap-2 px-5 py-2.5 bg-green-600 rounded-lg text-sm font-medium text-white hover:bg-green-700 transition-colors shadow-lg">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Selesai & Kumpulkan
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Submitting Overlay (Fullscreen Lock) --}}
    <div id="submitting-overlay" class="hidden fixed inset-0 z-[70] flex items-center justify-center" style="background:rgba(17,24,39,0.97)">
        <div class="text-center max-w-md mx-auto px-6">
            <div class="w-24 h-24 mx-auto mb-6 bg-indigo-600 rounded-full flex items-center justify-center">
                <svg class="w-14 h-14 text-white" fill="none" viewBox="0 0 24 24" style="animation:spin 1s linear infinite">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-black text-white mb-3">MENGUMPULKAN UJIAN...</h2>
            <p class="text-gray-400 text-lg mb-2">Mohon tunggu, jawaban Anda sedang diproses</p>
            <p id="submit-status-text" class="text-gray-500 text-sm">Jangan tutup atau refresh halaman ini!</p>
            <div class="mt-6 bg-gray-800 bg-opacity-50 rounded-xl p-4 border border-gray-700">
                <div class="flex items-center justify-center gap-2 text-gray-400 text-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" style="animation:spin 1s linear infinite">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span>Menyimpan hasil ujian...</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Submit Confirmation Modal --}}
    <div id="submit-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all">
            <div class="text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Kumpulkan Ujian?</h3>
                <p class="text-sm text-gray-500 mb-1">Anda telah menjawab <span id="modal-answered" class="font-bold text-indigo-600">0</span> dari <span class="font-bold">{{ $questions->count() }}</span> soal.</p>
                <p class="text-sm mb-6" id="modal-warning"></p>

                <div class="flex gap-3">
                    <button id="btn-close-modal" class="flex-1 px-4 py-2.5 bg-gray-100 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                        Kembali
                    </button>
                    <form id="submit-form" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2.5 bg-green-600 rounded-lg text-sm font-medium text-white hover:bg-green-700 transition-colors">
                            Ya, Kumpulkan!
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================================
    // EXAM QUESTION SYSTEM
    // ============================================
    var dataEl = document.getElementById('exam-data');
    var SESSION_ID = Number(dataEl.dataset.sessionId);
    var EXAM_ID = Number(dataEl.dataset.examId);
    var CSRF_TOKEN = dataEl.dataset.csrf;
    var TOTAL_QUESTIONS = Number(dataEl.dataset.totalQuestions);
    var DURATION_MINUTES = Number(dataEl.dataset.duration);
    var STARTED_AT = dataEl.dataset.startedAt;
    // Compute base path from current URL to handle subdirectory deployments
    // e.g. /LMS-Misdinar/public/student/exam/1/take → /LMS-Misdinar/public
    var BASE_PATH = (function() {
        var path = window.location.pathname || '';
        var marker = '/student/';
        var idx = path.indexOf(marker);
        return idx >= 0 ? path.slice(0, idx) : '';
    })();
    var POLL_URL = BASE_PATH + '/api/exam/' + EXAM_ID + '/poll-status';
    var SUBMIT_URL = BASE_PATH + '/student/exam/' + EXAM_ID + '/submit';
    var SAVE_URL = BASE_PATH + '/student/exam/' + EXAM_ID + '/save-answer';
    var BULK_SAVE_URL = BASE_PATH + '/student/exam/' + EXAM_ID + '/save-answers-bulk';
    var LOG_URL_BASE = BASE_PATH + '/student/integrity/log-violation';
    var RESULT_URL = BASE_PATH + '/student/exam/' + EXAM_ID + '/result';
    var MAX_VIOLATIONS = 5;
    var currentViolationCount = {{ $session->violation_count ?? 0 }};

    var currentQuestion = 0;
    var answeredSet = new Set();
    var examEnded = false;

    // Request load tuning for shared hosting (config-driven).
    var SAVE_DEBOUNCE_MC_MS = Number(@json(config('exam_runtime.student.save_debounce_mc_ms', 700)));
    var SAVE_DEBOUNCE_ESSAY_MS = Number(@json(config('exam_runtime.student.save_debounce_essay_ms', 3000)));
    var SYNC_DEBOUNCE_MS = Number(@json(config('exam_runtime.student.sync_debounce_ms', 1800)));
    var SYNC_MIN_INTERVAL_MS = Number(@json(config('exam_runtime.student.sync_min_interval_ms', 2500)));
    var SYNC_RETRY_INTERVAL_MS = Number(@json(config('exam_runtime.student.sync_retry_interval_ms', 6000)));
    var SYNC_PERIODIC_INTERVAL_MS = Number(@json(config('exam_runtime.student.sync_periodic_interval_ms', 20000)));
    var SYNC_TARGET_BATCH_SIZE = Number(@json(config('exam_runtime.student.sync_target_batch_size', 2)));
    var SYNC_IDLE_DELAY_MS = Number(@json(config('exam_runtime.student.sync_idle_delay_ms', 2200)));
    var SYNC_MAX_WAIT_MS = Number(@json(config('exam_runtime.student.sync_max_wait_ms', 7000)));
    var POLL_BASE_INTERVAL_MS = Number(@json(config('exam_runtime.student.poll_base_interval_ms', 15000)));
    var POLL_JITTER_MS = Number(@json(config('exam_runtime.student.poll_jitter_ms', 5000)));
    var REINSTATE_POLL_INTERVAL_MS = Number(@json(config('exam_runtime.student.reinstate_poll_interval_ms', 10000)));
    var BULK_SYNC_CHUNK_SIZE = Number(@json(config('exam_runtime.student.bulk_sync_chunk_size', 8)));

    // ========== OFFLINE QUEUE & RETRY SYSTEM ==========
    var pendingQueue = {}; // { questionId: { question_id, selected_answer|answer_text, is_essay?, timestamp } }
    var isSyncing = false;
    var syncTimer = null;
    var lastSyncAt = 0;
    var firstPendingAt = 0;

    // Create save status indicator element
    var saveStatusEl = document.createElement('div');
    saveStatusEl.id = 'save-status';
    saveStatusEl.style.cssText = 'position:fixed;bottom:20px;right:20px;padding:8px 16px;border-radius:8px;font-size:14px;z-index:9999;display:none;transition:opacity 0.3s;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.2);';
    document.body.appendChild(saveStatusEl);

    // Connection status indicator (top bar)
    var connBar = document.createElement('div');
    connBar.id = 'conn-bar';
    connBar.style.cssText = 'position:fixed;top:0;left:0;right:0;text-align:center;padding:4px;font-size:13px;font-weight:600;z-index:99999;display:none;transition:opacity 0.3s;';
    document.body.appendChild(connBar);

    function showConnStatus(online) {
        var bar = document.getElementById('conn-bar');
        if (!bar) return;
        if (!online) {
            bar.style.display = 'block';
            bar.style.background = '#EF4444';
            bar.style.color = 'white';
            bar.textContent = '⚠ Tidak ada koneksi internet — jawaban disimpan lokal, akan di-sync otomatis';
        } else {
            var pending = Object.keys(pendingQueue).length;
            if (pending > 0) {
                bar.style.display = 'block';
                bar.style.background = '#F59E0B';
                bar.style.color = 'white';
                bar.textContent = '🔄 Menyinkronkan ' + pending + ' jawaban tertunda...';
            } else {
                bar.style.display = 'none';
            }
        }
    }

    function showSaveStatus(status, detail) {
        var el = document.getElementById('save-status');
        if (!el) return;
        
        if (status === 'saving') {
            el.style.display = 'block';
            el.style.background = '#3B82F6';
            el.style.color = 'white';
            el.textContent = '💾 Menyimpan...';
        } else if (status === 'saved') {
            el.style.background = '#10B981';
            el.style.color = 'white';
            el.textContent = '✓ Tersimpan';
            setTimeout(function() { el.style.display = 'none'; }, 1500);
        } else if (status === 'queued') {
            el.style.display = 'block';
            el.style.background = '#F59E0B';
            el.style.color = 'white';
            var pendingCount = Object.keys(pendingQueue).length;
            el.textContent = '⏳ ' + pendingCount + ' jawaban menunggu koneksi...';
            // Don't auto-hide, keep showing until synced
        } else if (status === 'synced') {
            el.style.display = 'block';
            el.style.background = '#10B981';
            el.style.color = 'white';
            el.textContent = '✓ Semua jawaban berhasil disinkronkan!';
            setTimeout(function() { el.style.display = 'none'; }, 3000);
        } else if (status === 'error') {
            el.style.background = '#EF4444';
            el.style.color = 'white';
            el.textContent = '⏳ Gagal menyimpan, akan otomatis dicoba lagi...';
            // Don't auto-hide
        }
    }

    // Local answer storage format:
    // { [questionId]: { type: 'mc'|'essay', value: string } }
    // Backward compatible with legacy string-only values.
    function parseLocalAnswerEntry(entry) {
        if (entry === null || entry === undefined) return null;
        if (typeof entry === 'object' && entry.type && Object.prototype.hasOwnProperty.call(entry, 'value')) {
            return { type: entry.type, value: String(entry.value ?? '') };
        }

        var raw = String(entry);
        if (/^[a-d]$/i.test(raw)) {
            return { type: 'mc', value: raw.toLowerCase() };
        }
        return { type: 'essay', value: raw };
    }

    function setLocalAnswer(questionId, type, value) {
        try {
            var key = 'exam_' + EXAM_ID + '_answers';
            var store = JSON.parse(localStorage.getItem(key) || '{}');
            store[questionId] = { type: type, value: String(value ?? '') };
            localStorage.setItem(key, JSON.stringify(store));
        } catch (e) {}
    }

    function getLocalAnswer(questionId) {
        try {
            var key = 'exam_' + EXAM_ID + '_answers';
            var store = JSON.parse(localStorage.getItem(key) || '{}');
            return parseLocalAnswerEntry(store[questionId]);
        } catch (e) {
            return null;
        }
    }

    // Add to pending queue and try to sync
    function addToPendingQueue(questionId, option) {
        if (Object.keys(pendingQueue).length === 0) {
            firstPendingAt = Date.now();
        }
        pendingQueue[questionId] = { question_id: Number(questionId), selected_answer: option, timestamp: Date.now() };
        // Also backup to localStorage
        try {
            localStorage.setItem('exam_' + EXAM_ID + '_pending', JSON.stringify(pendingQueue));
        } catch (e) {}
        scheduleSync(getAdaptiveSyncDelay());
    }

    function getAdaptiveSyncDelay() {
        var pendingCount = Object.keys(pendingQueue).length;
        if (pendingCount <= 0) {
            return SYNC_DEBOUNCE_MS;
        }

        if (!firstPendingAt) {
            firstPendingAt = Date.now();
        }

        if (pendingCount >= Math.max(1, SYNC_TARGET_BATCH_SIZE)) {
            // Flush quickly once batch target is reached.
            return Math.min(SYNC_DEBOUNCE_MS, 900);
        }

        var elapsed = Date.now() - firstPendingAt;
        var remaining = Math.max(0, SYNC_MAX_WAIT_MS - elapsed);
        return Math.min(SYNC_IDLE_DELAY_MS, remaining);
    }

    function scheduleSync(delayMs) {
        if (syncTimer) {
            clearTimeout(syncTimer);
            syncTimer = null;
        }

        syncTimer = setTimeout(function() {
            syncTimer = null;
            syncPendingQueue();
        }, Math.max(0, Number(delayMs) || 0));
    }

    // Remove from pending queue only if value matches what we sent
    function removeFromPending(questionId, sentTimestamp) {
        var current = pendingQueue[questionId];
        // Only remove if the timestamp matches (no newer answer was added)
        if (current && current.timestamp === sentTimestamp) {
            delete pendingQueue[questionId];
            if (Object.keys(pendingQueue).length === 0) {
                firstPendingAt = 0;
            }
            try {
                if (Object.keys(pendingQueue).length > 0) {
                    localStorage.setItem('exam_' + EXAM_ID + '_pending', JSON.stringify(pendingQueue));
                } else {
                    localStorage.removeItem('exam_' + EXAM_ID + '_pending');
                }
            } catch (e) {}
        }
        showConnStatus(navigator.onLine);
    }

    // Restore pending queue from localStorage on load
    try {
        var storedPending = JSON.parse(localStorage.getItem('exam_' + EXAM_ID + '_pending') || '{}');
        if (Object.keys(storedPending).length > 0) {
            pendingQueue = storedPending;
            console.log('[SaveQueue] Restored ' + Object.keys(storedPending).length + ' pending items');
        }
    } catch (e) {}

    function buildAnswerPayload(item, withToken) {
        var payload = { question_id: item.question_id };
        if (item.is_essay) {
            payload.answer_text = item.answer_text;
        } else {
            payload.selected_answer = item.selected_answer;
        }
        if (withToken) {
            payload.client_token = String(item.timestamp || '');
        }
        return payload;
    }

    function processSyncSequential(keys, index, done) {
        if (index >= keys.length) {
            done();
            return;
        }

        var qId = keys[index];
        var item = pendingQueue[qId];
        if (!item) {
            processSyncSequential(keys, index + 1, done);
            return;
        }

        var sentTimestamp = item.timestamp;
        var payload = buildAnswerPayload(item, false);

        fetch(SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        }).then(function(res) {
            if (res.ok) {
                removeFromPending(qId, sentTimestamp);
                showSaveStatus('saved');
            }
            processSyncSequential(keys, index + 1, done);
        }).catch(function() {
            processSyncSequential(keys, index + 1, done);
        });
    }

    function processSyncBulk(keys, index, done) {
        if (index >= keys.length) {
            done();
            return;
        }

        var batchKeys = [];
        for (var i = index; i < keys.length && batchKeys.length < BULK_SYNC_CHUNK_SIZE; i++) {
            if (pendingQueue[keys[i]]) {
                batchKeys.push(keys[i]);
            }
        }

        var nextIndex = index + Math.max(1, batchKeys.length);

        if (batchKeys.length === 0) {
            processSyncBulk(keys, nextIndex, done);
            return;
        }

        var answersPayload = batchKeys.map(function(key) {
            return buildAnswerPayload(pendingQueue[key], true);
        });

        fetch(BULK_SAVE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ answers: answersPayload })
        }).then(function(res) {
            if (!res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return res.json().catch(function() { return {}; });
        }).then(function(data) {
            var resultByToken = {};
            if (data && Array.isArray(data.results)) {
                data.results.forEach(function(result) {
                    if (!result || result.client_token === undefined || result.client_token === null) {
                        return;
                    }
                    resultByToken[String(result.client_token)] = result;
                });
            }

            var removedAny = false;
            batchKeys.forEach(function(key) {
                var queued = pendingQueue[key];
                if (!queued) return;

                var token = String(queued.timestamp || '');
                var result = resultByToken[token];
                var ok = result ? !!result.success : true;

                if (ok) {
                    removeFromPending(key, queued.timestamp);
                    removedAny = true;
                }
            });

            if (removedAny) {
                showSaveStatus('saved');
            }

            processSyncBulk(keys, nextIndex, done);
        }).catch(function() {
            // Fallback to single-save mode if bulk endpoint fails.
            processSyncSequential(keys, index, done);
        });
    }

    // Sync all pending answers to server - SEQUENTIAL to prevent race conditions
    function syncPendingQueue() {
        var keys = Object.keys(pendingQueue);
        if (keys.length === 0 || isSyncing) return;

        if (!firstPendingAt) {
            firstPendingAt = Date.now();
        }

        if (keys.length < Math.max(1, SYNC_TARGET_BATCH_SIZE)) {
            var waitElapsed = Date.now() - firstPendingAt;
            if (waitElapsed < SYNC_MAX_WAIT_MS) {
                scheduleSync(Math.max(300, getAdaptiveSyncDelay()));
                return;
            }
        }

        if (!navigator.onLine) {
            showSaveStatus('queued');
            showConnStatus(false);
            return;
        }

        var now = Date.now();
        var sinceLastSync = now - lastSyncAt;
        if (sinceLastSync < SYNC_MIN_INTERVAL_MS) {
            scheduleSync(SYNC_MIN_INTERVAL_MS - sinceLastSync + 100);
            return;
        }

        isSyncing = true;
        lastSyncAt = now;

        var onDone = function() {
            isSyncing = false;
            if (Object.keys(pendingQueue).length > 0) {
                showSaveStatus('error');
                scheduleSync(Math.max(1200, Math.min(SYNC_RETRY_INTERVAL_MS, getAdaptiveSyncDelay())));
            } else {
                firstPendingAt = 0;
                showSaveStatus('synced');
                showConnStatus(true);
            }
        };

        processSyncBulk(keys, 0, onDone);
    }

    // Detect online/offline and auto-sync
    window.addEventListener('online', function() {
        console.log('[SaveQueue] Connection restored, syncing...');
        showConnStatus(true);
        scheduleSync(800);
    });
    window.addEventListener('offline', function() {
        console.log('[SaveQueue] Connection lost');
        showConnStatus(false);
    });

    // Periodic retry for any pending items
    setInterval(function() {
        if (Object.keys(pendingQueue).length > 0 && navigator.onLine && !isSyncing) {
            scheduleSync(0);
        }
    }, SYNC_PERIODIC_INTERVAL_MS);

    // Sync any items from previous session on page load
    if (Object.keys(pendingQueue).length > 0 && navigator.onLine) {
        scheduleSync(1200);
    }

    // Set form action from computed base path
    var submitForm = document.getElementById('submit-form');
    submitForm.action = SUBMIT_URL;

    // Initialize answered set from server data
    document.querySelectorAll('.answer-radio-input:checked').forEach(function(input) {
        answeredSet.add(Number(input.dataset.questionId));
    });
    
    // Try to restore answers from localStorage backup (if server didn't have them)
    try {
        var localAnswers = JSON.parse(localStorage.getItem('exam_' + EXAM_ID + '_answers') || '{}');
        var restoredCount = 0;
        for (var qId in localAnswers) {
            if (answeredSet.has(Number(qId))) {
                continue;
            }

            var parsed = parseLocalAnswerEntry(localAnswers[qId]);
            if (!parsed) {
                continue;
            }

            if (parsed.type === 'mc') {
                var opt = parsed.value;
                var radio = document.querySelector('.answer-radio-input[data-question-id="' + qId + '"][data-option="' + opt + '"]');
                if (radio && !radio.checked) {
                    radio.checked = true;
                    answeredSet.add(Number(qId));
                    restoredCount++;

                    // Update visual state
                    var label = radio.closest('.answer-option');
                    if (label) {
                        label.classList.remove('border-gray-200', 'hover:border-indigo-300', 'hover:bg-gray-50');
                        label.classList.add('border-indigo-500', 'bg-indigo-50');
                    }

                    // Sync to server
                    var qIndex = Number(radio.dataset.questionIndex || 0);
                    (function(questionId, option, index) {
                        setTimeout(function() {
                            selectAnswer(questionId, option, index);
                        }, restoredCount * 500);
                    })(Number(qId), opt, qIndex);
                }
            } else if (parsed.type === 'essay') {
                var textarea = document.querySelector('.essay-textarea[data-question-id="' + qId + '"]');
                if (textarea && textarea.value.trim().length === 0 && parsed.value.trim().length > 0) {
                    textarea.value = parsed.value;
                    answeredSet.add(Number(qId));
                    restoredCount++;

                    var charEl = document.getElementById('char-count-' + qId);
                    if (charEl) charEl.textContent = parsed.value.length;

                    var essayQIndex = Number(textarea.dataset.questionIndex || 0);
                    var navDot = document.getElementById('nav-dot-' + essayQIndex);
                    if (navDot) {
                        navDot.classList.remove('bg-gray-100', 'text-gray-600');
                        navDot.classList.add('bg-indigo-600', 'text-white');
                    }

                    // Push restored essay to sync queue.
                    (function(questionId, text, delayIndex) {
                        setTimeout(function() {
                            addEssayToPendingQueue(questionId, text);
                        }, delayIndex * 500);
                    })(Number(qId), parsed.value, restoredCount);
                }
            }
        }
        if (restoredCount > 0) {
            updateAnsweredCount();
        }
    } catch (e) {
        // Silent fail for localStorage
    }

    // ---- Horizontal nav slider controls ----
    var navTrack = document.getElementById('question-nav-track');
    var navScrollPrev = document.getElementById('nav-scroll-prev');
    var navScrollNext = document.getElementById('nav-scroll-next');

    function getNavScrollAmount() {
        if (!navTrack) {
            return 200;
        }
        return Math.max(200, Math.floor(navTrack.clientWidth * 0.7));
    }

    function centerActiveNavDot(index, smooth) {
        if (!navTrack) {
            return;
        }
        var activeDot = document.getElementById('nav-dot-' + index);
        if (!activeDot) {
            return;
        }
        var targetLeft = activeDot.offsetLeft - (navTrack.clientWidth / 2) + (activeDot.offsetWidth / 2);
        navTrack.scrollTo({
            left: Math.max(0, targetLeft),
            behavior: smooth ? 'smooth' : 'auto'
        });
    }

    if (navScrollPrev) {
        navScrollPrev.addEventListener('click', function() {
            if (!navTrack) {
                return;
            }
            navTrack.scrollBy({ left: -getNavScrollAmount(), behavior: 'smooth' });
        });
    }

    if (navScrollNext) {
        navScrollNext.addEventListener('click', function() {
            if (!navTrack) {
                return;
            }
            navTrack.scrollBy({ left: getNavScrollAmount(), behavior: 'smooth' });
        });
    }

    // Make mouse wheel convenient on laptop: vertical wheel scrolls nav horizontally.
    if (navTrack) {
        navTrack.addEventListener('wheel', function(e) {
            if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
                e.preventDefault();
                navTrack.scrollLeft += e.deltaY;
            }
        }, { passive: false });
    }

    // ---- EVENT DELEGATION: Nav dots ----
    document.querySelectorAll('.nav-dot-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            goToQuestion(Number(this.dataset.navIndex));
        });
    });

    // ---- EVENT DELEGATION: Answer option labels (click) ----
    // Using click on label wrapper for better reliability
    document.querySelectorAll('.answer-option').forEach(function(label) {
        label.addEventListener('click', function(e) {
            var qId = Number(this.dataset.questionId);
            var opt = this.dataset.option;
            var input = this.querySelector('.answer-radio-input');
            var qIndex = input ? Number(input.dataset.questionIndex) : 0;
            
            // Manually check the radio
            if (input) {
                input.checked = true;
            }
            
            // Call selectAnswer
            selectAnswer(qId, opt, qIndex);
        });
    });

    // ---- EVENT DELEGATION: Answer radio inputs (backup for programmatic changes) ----
    document.querySelectorAll('.answer-radio-input').forEach(function(input) {
        input.addEventListener('change', function() {
            selectAnswer(Number(this.dataset.questionId), this.dataset.option, Number(this.dataset.questionIndex));
        });
    });

    // ---- EVENT DELEGATION: Essay textarea (auto-save on input with debounce) ----
    document.querySelectorAll('.essay-textarea').forEach(function(textarea) {
        var qId = Number(textarea.dataset.questionId);
        var qIndex = Number(textarea.dataset.questionIndex);
        
        // Update character count
        textarea.addEventListener('input', function() {
            var countEl = document.getElementById('char-count-' + qId);
            if (countEl) countEl.textContent = this.value.length;
            
            // Mark as answered if has text
            if (this.value.trim().length > 0) {
                answeredSet.add(qId);
            } else {
                answeredSet.delete(qId);
            }
            updateAnsweredCount();
            var navDot = document.getElementById('nav-dot-' + qIndex);
            if (navDot) {
                if (this.value.trim().length > 0) {
                    navDot.classList.remove('bg-gray-100', 'text-gray-600');
                    navDot.classList.add('bg-indigo-600', 'text-white');
                } else {
                    navDot.classList.remove('bg-indigo-600', 'text-white');
                    navDot.classList.add('bg-gray-100', 'text-gray-600');
                }
            }
            
            // Debounce save (longer for essay)
            if (window._essayTimers && window._essayTimers[qId]) {
                clearTimeout(window._essayTimers[qId]);
            }
            if (!window._essayTimers) window._essayTimers = {};
            var currentText = this.value;
            
            showSaveStatus('saving');
            
            // Backup to localStorage immediately
            setLocalAnswer(qId, 'essay', currentText);
            
            window._essayTimers[qId] = setTimeout(function() {
                delete window._essayTimers[qId];
                addEssayToPendingQueue(qId, currentText);
            }, SAVE_DEBOUNCE_ESSAY_MS);
        });
        
        // Also init answered state for pre-filled essays
        if (textarea.value.trim().length > 0) {
            answeredSet.add(qId);
        }
    });

    // Add essay answer to pending queue
    function addEssayToPendingQueue(questionId, text) {
        pendingQueue[questionId] = { question_id: Number(questionId), answer_text: text, is_essay: true, timestamp: Date.now() };
        try {
            localStorage.setItem('exam_' + EXAM_ID + '_pending', JSON.stringify(pendingQueue));
        } catch (e) {}
        if (Object.keys(pendingQueue).length === 1) {
            firstPendingAt = Date.now();
        }
        scheduleSync(getAdaptiveSyncDelay());
    }

    // ---- EVENT DELEGATION: Prev/Next buttons ----
    document.querySelectorAll('.nav-prev-btn, .nav-next-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            goToQuestion(Number(this.dataset.goto));
        });
    });

    // ---- Submit & Modal buttons ----
    var submitBtnLast = document.getElementById('btn-submit-exam');
    if (submitBtnLast) submitBtnLast.addEventListener('click', confirmSubmit);

    var submitBtnSticky = document.getElementById('btn-submit-sticky');
    if (submitBtnSticky) submitBtnSticky.addEventListener('click', confirmSubmit);

    document.getElementById('btn-close-modal').addEventListener('click', closeModal);

    // ============================================
    // SUBMIT FORM WITH PENDING SYNC + UI LOCK
    // ============================================
    submitForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // IMMEDIATE ACTION: Lock everything BEFORE any network request
        lockUIForSubmission();
        
        // Flush pending saves, then submit
        setTimeout(function() {
            flushAndSubmit();
        }, 100);
    });

    function lockUIForSubmission() {
        // Set examEnded flag (prevents all other actions)
        examEnded = true;
        
        // Show fullscreen submitting overlay
        var overlay = document.getElementById('submitting-overlay');
        if (overlay) {
            overlay.classList.remove('hidden');
        }
        
        // Disable ALL answer radio buttons
        document.querySelectorAll('.answer-radio-input').forEach(function(input) {
            input.disabled = true;
        });
        
        // Disable ALL answer option labels
        document.querySelectorAll('.answer-option').forEach(function(label) {
            label.style.pointerEvents = 'none';
            label.style.opacity = '0.5';
        });
        
        // Disable ALL navigation buttons
        document.querySelectorAll('.nav-prev-btn, .nav-next-btn, .nav-dot-btn, #nav-scroll-prev, #nav-scroll-next').forEach(function(btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
        });
        
        // Disable submit buttons
        var submitBtns = document.querySelectorAll('#btn-submit-exam, #btn-submit-sticky');
        submitBtns.forEach(function(btn) {
            btn.disabled = true;
            btn.style.pointerEvents = 'none';
        });
        
        // Close modal
        closeModal();
        
        // Prevent page unload (back button, refresh)
        window.onbeforeunload = function() {
            return 'Ujian sedang dikumpulkan! Jangan tutup halaman ini.';
        };
        
        // Disable questions container (extra security)
        var questionsContainer = document.getElementById('questions-container');
        if (questionsContainer) {
            questionsContainer.style.pointerEvents = 'none';
            questionsContainer.style.opacity = '0.3';
        }
    }

    // ---- Navigation Scroll Logic ----
    var navTrack = document.getElementById('question-nav-track');
    var navPrevBtn = document.getElementById('nav-scroll-prev');
    var navNextBtn = document.getElementById('nav-scroll-next');

    if (navPrevBtn && navNextBtn && navTrack) {
        navPrevBtn.addEventListener('click', function() {
            navTrack.scrollBy({ left: -200, behavior: 'smooth' });
        });
        navNextBtn.addEventListener('click', function() {
            navTrack.scrollBy({ left: 200, behavior: 'smooth' });
        });
    }

    function centerActiveNavDot(index, smooth) {
        var btn = document.getElementById('nav-dot-' + index);
        if (btn && navTrack) {
            // center the button within the scrollable track
            var trackWidth = navTrack.clientWidth;
            var btnLeft = btn.offsetLeft;
            var btnWidth = btn.offsetWidth;
            
            // "offsetLeft" is relative to the offsetParent.
            // If navTrack is the offsetParent (position: relative/absolute/fixed), great.
            // If not, we need distance relative to track.
            // Assuming simplified layout where track is the parent or simple flow.
            // Using getBoundingClientRect is safer.
            
            var trackRect = navTrack.getBoundingClientRect();
            var btnRect = btn.getBoundingClientRect();
            
            // Expected scrollLeft = currentScrollLeft + (btnLeft_relative_to_viewport - trackLeft_relative_to_viewport) - (trackWidth/2 - btnWidth/2)
            var offset = btnRect.left - trackRect.left;
            var centerOffset = (trackWidth / 2) - (btnWidth / 2);
            var targetScroll = navTrack.scrollLeft + (offset - centerOffset);
            
            navTrack.scrollTo({
                left: targetScroll,
                behavior: smooth ? 'smooth' : 'auto'
            });
        }
    }

    function goToQuestion(index, options) {
        var opts = options || {};
        document.querySelectorAll('.question-slide').forEach(function(slide) {
            slide.classList.add('hidden');
        });
        var target = document.querySelector('[data-question-index="' + index + '"]');
        if (target) {
            target.classList.remove('hidden');
            currentQuestion = index;
        }
        // Highlight active dot
        document.querySelectorAll('.nav-dot-btn').forEach(function(btn) {
            btn.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2');
            if (Number(btn.dataset.navIndex) === index) {
                btn.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-2');
            }
        });
        centerActiveNavDot(index, !opts.instantNav);
        // Scroll to top of question on mobile
        if (!opts.preserveViewport) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function updateAnsweredCount() {
        var countEls = document.querySelectorAll('#answered-count, #answered-top');
        countEls.forEach(function(el) { el.textContent = answeredSet.size; });
    }

    function selectAnswer(questionId, option, questionIndex) {
        // Prevent interaction if exam ended or submitting
        if (examEnded) {
            return;
        }
        
        var container = document.getElementById('options-' + questionId);
        container.querySelectorAll('.answer-option').forEach(function(label) {
            var opt = label.dataset.option;
            var radio = label.querySelector('.answer-radio');
            if (opt === option) {
                label.classList.remove('border-gray-200', 'hover:border-indigo-300', 'hover:bg-gray-50');
                label.classList.add('border-indigo-500', 'bg-indigo-50');
                radio.classList.remove('border-gray-300', 'text-gray-500');
                radio.classList.add('border-indigo-500', 'bg-indigo-600', 'text-white');
            } else {
                label.classList.remove('border-indigo-500', 'bg-indigo-50');
                label.classList.add('border-gray-200', 'hover:border-indigo-300', 'hover:bg-gray-50');
                radio.classList.remove('border-indigo-500', 'bg-indigo-600', 'text-white');
                radio.classList.add('border-gray-300', 'text-gray-500');
            }
        });

        answeredSet.add(questionId);
        var navDot = document.getElementById('nav-dot-' + questionIndex);
        if (navDot) {
            navDot.classList.remove('bg-gray-100', 'text-gray-600');
            navDot.classList.add('bg-indigo-600', 'text-white');
        }
        updateAnsweredCount();

        // Auto-save via AJAX with debounce + offline queue (skip if exam ended)
        if (examEnded) {
            return;
        }

        // Debounce: cancel previous pending save for same question
        if (window._saveTimers && window._saveTimers[questionId]) {
            clearTimeout(window._saveTimers[questionId]);
        }
        if (!window._saveTimers) window._saveTimers = {};
        
        // Show saving indicator
        showSaveStatus('saving');
        
        // Backup to localStorage immediately
        setLocalAnswer(questionId, 'mc', option);
        
        window._saveTimers[questionId] = setTimeout(function() {
            delete window._saveTimers[questionId];
            // Add to queue and sync — queue handles retries automatically
            addToPendingQueue(questionId, option);
        }, SAVE_DEBOUNCE_MC_MS);
    }

    function confirmSubmit() {
        // Flush any pending debounced MC saves immediately
        if (window._saveTimers) {
            for (var qId in window._saveTimers) {
                clearTimeout(window._saveTimers[qId]);
                delete window._saveTimers[qId];
                try {
                    var saved = getLocalAnswer(qId);
                    if (saved && saved.type === 'mc' && saved.value) {
                        addToPendingQueue(Number(qId), saved.value);
                    }
                } catch (e) {}
            }
        }
        // Flush any pending debounced essay saves
        if (window._essayTimers) {
            for (var eqId in window._essayTimers) {
                clearTimeout(window._essayTimers[eqId]);
                delete window._essayTimers[eqId];
                try {
                    var savedEssay = getLocalAnswer(eqId);
                    if (savedEssay && savedEssay.type === 'essay') {
                        if (typeof addEssayToPendingQueue === 'function') {
                            addEssayToPendingQueue(Number(eqId), savedEssay.value);
                        }
                    }
                } catch (e) {}
            }
        }

        var modal = document.getElementById('submit-modal');
        document.getElementById('modal-answered').textContent = answeredSet.size;

        var unanswered = TOTAL_QUESTIONS - answeredSet.size;
        var warningEl = document.getElementById('modal-warning');

        var pendingCount = Object.keys(pendingQueue).length;
        if (pendingCount > 0) {
            warningEl.textContent = '⏳ ' + pendingCount + ' jawaban belum tersinkronkan ke server! Klik "Ya, Kumpulkan" untuk mencoba sync dan submit.';
            warningEl.className = 'text-sm mb-6 text-amber-600 font-semibold';
        } else if (unanswered > 0) {
            warningEl.textContent = 'Masih ada ' + unanswered + ' soal yang belum dijawab!';
            warningEl.className = 'text-sm mb-6 text-red-500';
        } else {
            warningEl.textContent = 'Semua soal telah dijawab.';
            warningEl.className = 'text-sm mb-6 text-green-600';
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('submit-modal').classList.add('hidden');
    }

    // Flush pending queue then submit the form - SEQUENTIAL
    function flushAndSubmit(isAutoSubmit) {
        // For auto-submit (max violations), show overlay immediately
        if (isAutoSubmit) {
            var overlay = document.getElementById('submitting-overlay');
            if (overlay) {
                var statusEl = overlay.querySelector('#submit-status-text');
                if (statusEl) statusEl.textContent = 'Ujian dikumpulkan otomatis karena pelanggaran...';
                overlay.classList.remove('hidden');
            }
            // Close any open modals/overlays
            document.getElementById('submit-modal').classList.add('hidden');
            document.getElementById('fullscreen-warning-overlay').classList.add('hidden');
        }

        var keys = Object.keys(pendingQueue);
        if (keys.length === 0 || !navigator.onLine) {
            // Nothing to flush or offline — just submit
            doSubmitWithRetry();
            return;
        }

        showSaveStatus('saving');

        processSyncBulk(keys, 0, function() {
            // Continue submit even if some pending items failed to sync,
            // preserving previous behavior and avoiding blocked submission.
            doSubmitWithRetry();
        });
    }

    // Submit exam with fetch + retry fallback
    var submitRetryCount = 0;
    var MAX_SUBMIT_RETRIES = 5;

    function doSubmitWithRetry() {
        var overlay = document.getElementById('submitting-overlay');

        // Update overlay to show status
        function updateOverlayStatus(msg) {
            if (!overlay) return;
            var statusEl = overlay.querySelector('#submit-status-text');
            if (statusEl) statusEl.textContent = msg;
        }

        updateOverlayStatus('Mengirim jawaban...');

        // Try fetch first for better error handling
        fetch(SUBMIT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
            body: '_token=' + encodeURIComponent(CSRF_TOKEN),
        }).then(function(res) {
            if (res.ok || res.redirected) {
                // Success — redirect to result page
                window.onbeforeunload = null;
                window.location.href = RESULT_URL;
            } else if (res.status === 419) {
                // CSRF token expired — try form submit as fallback
                updateOverlayStatus('Sesi expired, mencoba ulang...');
                submitForm.submit();
            } else {
                throw new Error('HTTP ' + res.status);
            }
        }).catch(function(err) {
            submitRetryCount++;
            console.warn('[Submit] Attempt ' + submitRetryCount + ' failed:', err);

            if (submitRetryCount >= MAX_SUBMIT_RETRIES) {
                // All retries exhausted — show manual retry option
                updateOverlayStatus('');
                showSubmitFailedUI();
                return;
            }

            // Exponential backoff: 2s, 4s, 6s, 8s, 10s
            var delay = submitRetryCount * 2000;
            updateOverlayStatus('Gagal mengirim. Mencoba ulang (' + submitRetryCount + '/' + MAX_SUBMIT_RETRIES + ')...');
            setTimeout(doSubmitWithRetry, delay);
        });
    }

    function showSubmitFailedUI() {
        var overlay = document.getElementById('submitting-overlay');
        if (!overlay) return;
        overlay.innerHTML = '<div class="text-center max-w-md px-6">' +
            '<div class="w-20 h-20 mx-auto mb-6 bg-red-600 rounded-full flex items-center justify-center">' +
            '<svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>' +
            '</svg></div>' +
            '<h2 class="text-2xl font-bold text-white mb-3">GAGAL MENGIRIM JAWABAN</h2>' +
            '<p class="text-red-200 mb-2">Koneksi internet bermasalah. Jawaban Anda sudah tersimpan di server.</p>' +
            '<p class="text-red-300 text-sm mb-6">Coba klik tombol di bawah, atau hubungi pengawas ujian.</p>' +
            '<button onclick="submitRetryCount=0;doSubmitWithRetry();" ' +
            'class="px-8 py-4 bg-white text-red-900 font-bold rounded-xl hover:bg-gray-100 transition-colors text-lg shadow-lg mb-3 w-full">' +
            'COBA KIRIM ULANG</button>' +
            '<button onclick="window.onbeforeunload=null;window.location.href=RESULT_URL;" ' +
            'class="px-8 py-3 bg-red-700 text-white font-bold rounded-xl hover:bg-red-800 transition-colors text-sm shadow-lg w-full">' +
            'LANGSUNG KE HALAMAN HASIL</button>' +
            '<p class="mt-4 text-red-400 text-xs">Jawaban yang sudah tersimpan tidak akan hilang</p>' +
            '</div>';
    }

    // Auto-submit (form POST)
    function autoSubmitExam() {
        if (examEnded) return;
        examEnded = true;
        lockUIForSubmission();
        
        // Flush pending saves, then submit
        setTimeout(function() {
            flushAndSubmit();
        }, 100);
    }

    // Initialize first question nav highlight
    goToQuestion(0, { instantNav: true, preserveViewport: true });

    // ============================================
    // COUNTDOWN TIMER
    // ============================================
    var timerEl = document.getElementById('timer-text');
    var timerBox = document.getElementById('timer-display');

    function updateTimer() {
        if (!STARTED_AT || examEnded) return;

        var startTime = new Date(STARTED_AT).getTime();
        var endTime = startTime + (DURATION_MINUTES * 60 * 1000);
        var now = Date.now();
        var remaining = Math.max(0, endTime - now);

        var totalSeconds = Math.floor(remaining / 1000);
        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;

        timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

        // Color coding
        if (totalSeconds <= 60) {
            timerBox.className = 'flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-mono';
        } else if (totalSeconds <= 300) {
            timerBox.className = 'flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white rounded-lg font-mono';
        } else {
            timerBox.className = 'flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg font-mono';
        }

        // Time's up: auto-submit
        if (remaining <= 0) {
            timerEl.textContent = '00:00';
            autoSubmitExam();
            return;
        }
    }

    updateTimer();
    setInterval(updateTimer, 1000);

    // ============================================
    // POLL FOR ADMIN FORCE-STOP
    // Uses visibility-aware polling: pauses when tab is hidden
    // ============================================
    var pollTimer = null;

    function pollExamStatus() {
        if (examEnded) return;
        // Skip polling when tab is hidden (saves requests)
        if (document.hidden) return;

        fetch(POLL_URL, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        }).then(function(res) {
            if (res.ok) return res.json();
        }).then(function(data) {
            if (!data) return;

            // Admin force-stopped the exam
            if (data.exam_status === 'finished') {
                examEnded = true;
                document.getElementById('force-stop-banner').classList.remove('hidden');
                document.getElementById('questions-container').style.opacity = '0.3';
                document.getElementById('questions-container').style.pointerEvents = 'none';
                setTimeout(function() {
                    window.location.href = RESULT_URL;
                }, 3000);
                return;
            }

            // Admin terminated this student specifically
            if (data.session_status === 'blocked') {
                examEnded = true;
                if (window.showTerminatedScreen) window.showTerminatedScreen();
                return;
            }

            // Session already completed on server (e.g. auto-submit from integrity threshold)
            if (data.session_status === 'completed') {
                examEnded = true;
                setTimeout(function() {
                    window.location.href = RESULT_URL;
                }, 500);
                return;
            }

            // Sync integrity and violation count from server (in case admin changed it)
            if (data.session_integrity !== null && data.session_integrity !== undefined) {
                if (window.updateIntegrityUI) window.updateIntegrityUI(data.session_integrity, data.violation_count);
            }
        }).catch(function(e) {
            // Silent fail for poll errors
        });
    }

    function scheduleExamPoll() {
        // Jitter avoids request bursts when many devices open exam simultaneously.
        var delay = POLL_BASE_INTERVAL_MS + Math.floor(Math.random() * POLL_JITTER_MS);
        pollTimer = setTimeout(function() {
            if (!examEnded && !document.hidden) {
                pollExamStatus();
            }
            if (!examEnded) {
                scheduleExamPoll();
            }
        }, delay);
    }

    // Keep average around 15-20s while spreading concurrent requests.
    scheduleExamPoll();
    // Also poll immediately when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && !examEnded) {
            pollExamStatus();
        }
    });
    // ============================================
    // ========== ANTI-CHEAT v2.1 — FIXED =========
    // Fullscreen with user gesture + robust Promise handling
    // ============================================
    (function() { // IIFE to prevent global scope pollution and catch all errors
    'use strict';

    var LOG_URL = LOG_URL_BASE;
    var currentIntegrity = Number(dataEl.dataset.score);
    var blurStart = null;
    var warningTimeout = null;
    var graceToastTimeout = null;
    var graceTimer = null;
    var GRACE_PERIOD_MS = 1500;
    var VIOLATION_COOLDOWN_MS = 12000;
    var VIOLATION_GLOBAL_COOLDOWN_MS = 8000;
    var CASCADE_WINDOW_MS = 3000;
    var CASCADE_GROUPS = {
        'tab_switch': 'leave', 'window_blur': 'leave', 'fullscreen_exit': 'leave',
        'resize_suspicion': 'leave', 'split_screen': 'leave',
        'screenshot': 'screenshot'
    };
    var lastViolationTime = {};
    var lastAnyViolationTime = 0;
    var lastCascadeGroupTime = {};
    var screenshotWarningTimeout = null;

    // ========== FULLSCREEN STATE ==========
    var isInFullscreen = false;
    var fullscreenSupported = false;
    var fullscreenRequested = false;
    var fullscreenEntryShown = false;

    // ========== MOBILE DETECTION ==========
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    var isAndroid = /Android/i.test(navigator.userAgent);
    var initialWidth = window.innerWidth;
    var initialHeight = window.innerHeight;
    var initialRatio = initialWidth / initialHeight;

    // Safely detect fullscreen support
    // iOS Safari/WebKit can expose partial fullscreen API but usually cannot
    // reliably lock arbitrary pages in true fullscreen like desktop/Android.
    try {
        fullscreenSupported = !isIOS && !!(document.documentElement.requestFullscreen ||
                                 document.documentElement.webkitRequestFullscreen ||
                                 document.documentElement.mozRequestFullScreen ||
                                 document.documentElement.msRequestFullscreen);
    } catch(e) { fullscreenSupported = false; }

    function reportViolation(type, duration) {
        duration = duration || 0;
        if (examEnded) return;

        var now = Date.now();
        // Cascade group suppression: tab_switch+fullscreen_exit+resize = 1 event
        var group = CASCADE_GROUPS[type] || type;
        if (lastCascadeGroupTime[group] && (now - lastCascadeGroupTime[group]) < CASCADE_WINDOW_MS) {
            return;
        }
        if (lastAnyViolationTime && (now - lastAnyViolationTime) < VIOLATION_GLOBAL_COOLDOWN_MS) {
            return;
        }
        if (lastViolationTime[type] && (now - lastViolationTime[type]) < VIOLATION_COOLDOWN_MS) {
            return;
        }
        lastViolationTime[type] = now;
        lastAnyViolationTime = now;
        lastCascadeGroupTime[group] = now;

        try {
            fetch(LOG_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    session_id: SESSION_ID,
                    type: type,
                    duration: Math.round(duration)
                })
            }).then(function(res) {
                if (!res.ok) {
                    return res.text().then(function(text) {
                        console.warn('[AntiCheat] Server error:', res.status, text);
                        throw new Error('HTTP ' + res.status);
                    });
                }
                return res.json();
            }).then(function(data) {
                if (data) {
                    updateIntegrityUI(data.current_integrity, data.violation_count);
                    var remaining = data.remaining_violations !== undefined ? data.remaining_violations : (MAX_VIOLATIONS - (data.violation_count || 0));
                    showWarning(type, remaining);

                    if (data.auto_submit) {
                        // Max violations reached — auto-submit exam
                        examEnded = true;
                        flushAndSubmit(true);
                    } else if (data.terminated) {
                        // Legacy terminated flag
                        examEnded = true;
                        showTerminatedScreen();
                    }
                }
            }).catch(function(e) {
                console.warn('[AntiCheat] Report failed:', e.message);
            });
        } catch(e) {
            console.warn('[AntiCheat] Report exception:', e.message);
        }
    }

    function updateIntegrityUI(score, violationCount) {
        if (violationCount !== undefined && violationCount !== null) {
            currentViolationCount = violationCount;
        }
        currentIntegrity = score;
        var scoreEl = document.getElementById('integrity-score');
        var barEl = document.getElementById('integrity-bar');
        var countEl = document.getElementById('violation-count-text');
        if (!scoreEl || !barEl) return;

        // Update violation counter display
        if (countEl) countEl.textContent = currentViolationCount;
        scoreEl.innerHTML = '<span id="violation-count-text">' + currentViolationCount + '</span> / ' + MAX_VIOLATIONS + ' pelanggaran';
        
        // Bar shows violation progress (fills up as violations increase)
        var pct = Math.min(100, (currentViolationCount / MAX_VIOLATIONS) * 100);
        barEl.style.width = pct + '%';

        if (currentViolationCount <= 1) {
            barEl.className = 'bg-green-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-green-600';
        } else if (currentViolationCount <= 3) {
            barEl.className = 'bg-yellow-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-yellow-600';
        } else {
            barEl.className = 'bg-red-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-red-600';
        }
    }
    // Expose to global scope for polling code
    window.updateIntegrityUI = updateIntegrityUI;

    function showWarning(type, remaining) {
        var warningEl = document.getElementById('cheat-warning');
        var textEl = document.getElementById('warning-text');
        if (!warningEl || !textEl) return;

        var suffix = remaining > 0 ? ' Sisa kesempatan: ' + remaining + ' kali.' : ' Ujian akan dikumpulkan otomatis!';

        var messages = {
            'tab_switch': 'Anda berpindah tab!' + suffix,
            'split_screen': 'Split screen terdeteksi!' + suffix,
            'window_blur': 'Anda keluar dari halaman ujian!' + suffix,
            'screenshot': 'Screenshot terdeteksi!' + suffix,
            'fullscreen_exit': 'Anda keluar dari mode fullscreen!' + suffix,
            'resize_suspicion': 'Perubahan ukuran layar terdeteksi!' + suffix,
        };

        textEl.textContent = messages[type] || ('Pelanggaran terdeteksi!' + suffix);
        warningEl.classList.remove('hidden');

        clearTimeout(warningTimeout);
        warningTimeout = setTimeout(function() {
            warningEl.classList.add('hidden');
        }, 5000);
    }

    // ---- GRACE PERIOD TOAST ----
    function showGraceToast(variant, title, text) {
        var toast = document.getElementById('grace-toast');
        var inner = document.getElementById('grace-toast-inner');
        var iconEl = document.getElementById('grace-toast-icon');
        var titleEl = document.getElementById('grace-toast-title');
        var textEl = document.getElementById('grace-toast-text');
        if (!toast || !inner || !iconEl || !titleEl || !textEl) return;

        var styles = {
            'ok': { bg: 'bg-green-50 border-green-300', icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>', title: 'text-green-800', text: 'text-green-600' },
            'warn': { bg: 'bg-yellow-50 border-yellow-300', icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>', title: 'text-yellow-800', text: 'text-yellow-600' },
            'screenshot': { bg: 'bg-orange-50 border-orange-400', icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>', title: 'text-orange-800', text: 'text-orange-600' },
            'fullscreen': { bg: 'bg-red-50 border-red-400', icon: '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H5.414l3.293 3.293a1 1 0 01-1.414 1.414L4 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 110-2h4a1 1 0 011 1v4a1 1 0 11-2 0V6.414l-3.293 3.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 112 0v1.586l3.293-3.293a1 1 0 011.414 1.414L6.414 15H8a1 1 0 110 2H4a1 1 0 01-1-1v-4zm13 1a1 1 0 10-2 0v1.586l-3.293-3.293a1 1 0 00-1.414 1.414L13.586 15H12a1 1 0 100 2h4a1 1 0 001-1v-4z" clip-rule="evenodd"/></svg>', title: 'text-red-800', text: 'text-red-600' }
        };
        var s = styles[variant] || styles['warn'];

        inner.className = 'flex items-center gap-3 px-6 py-4 rounded-2xl shadow-lg border ' + s.bg;
        iconEl.innerHTML = s.icon;
        titleEl.className = 'font-bold text-sm ' + s.title;
        textEl.className = 'text-xs ' + s.text;
        titleEl.textContent = title;
        textEl.textContent = text;

        toast.classList.remove('hidden');
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';

        clearTimeout(graceToastTimeout);
        graceToastTimeout = setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(-20px)';
            setTimeout(function() { toast.classList.add('hidden'); }, 400);
        }, 4000);
    }

    function showTerminatedScreen() {
        document.getElementById('terminated-overlay').classList.remove('hidden');
        document.getElementById('questions-container').style.pointerEvents = 'none';
        document.getElementById('questions-container').style.opacity = '0.2';

        document.getElementById('fullscreen-warning-overlay').classList.add('hidden');
        document.getElementById('fullscreen-entry-overlay').classList.add('hidden');
        document.getElementById('split-screen-overlay').classList.add('hidden');

        if (pollTimer) {
            clearTimeout(pollTimer);
            pollTimer = null;
        }

        var reinstateInterval = setInterval(function() {
            fetch(POLL_URL, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function(res) {
                if (res.ok) return res.json();
            }).then(function(data) {
                if (!data) return;
                if (data.exam_status === 'finished') {
                    clearInterval(reinstateInterval);
                    window.location.href = RESULT_URL;
                    return;
                }
                if (data.session_status === 'ongoing' && data.session_integrity > 0) {
                    clearInterval(reinstateInterval);
                    document.getElementById('terminated-waiting').classList.add('hidden');
                    document.getElementById('terminated-reinstated').classList.remove('hidden');
                }
            }).catch(function(e) {});
        }, REINSTATE_POLL_INTERVAL_MS);
    }
    // Expose to global scope for polling code
    window.showTerminatedScreen = showTerminatedScreen;

    // =============================================
    // DETECTION 1: Tab Switch / Blur — TIGHTENED
    // =============================================
    var tabSwitchReported = false;

    function handleLeave() {
        if (examEnded) return;
        if (!blurStart) {
            blurStart = Date.now();
            tabSwitchReported = false;
        }
        if (!graceTimer) {
            graceTimer = setTimeout(function() {
                graceTimer = null;
                if (blurStart && !tabSwitchReported) {
                    var duration = (Date.now() - blurStart) / 1000;
                    tabSwitchReported = true;
                    reportViolation('tab_switch', duration);
                }
            }, GRACE_PERIOD_MS);
        }
    }

    function handleReturn() {
        if (examEnded) return;
        var wasBenign = false;

        if (graceTimer) {
            clearTimeout(graceTimer);
            graceTimer = null;
            wasBenign = true;
        }

        if (blurStart) {
            var awayMs = Date.now() - blurStart;
            blurStart = null;

            if (wasBenign) {
                showGraceToast('ok', 'Selamat kembali!', 'Tetap fokus mengerjakan ujian ya!');
            } else if (!tabSwitchReported) {
                tabSwitchReported = true;
                reportViolation('tab_switch', awayMs / 1000);
            }
        }

        // Re-show fullscreen warning when returning if not in fullscreen
        if (fullscreenRequested && !checkFullscreen()) {
            showFullscreenWarning();
        }
    }

    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            handleLeave();
        } else {
            handleReturn();
        }
    });

    window.addEventListener('blur', function() {
        handleLeave();
    });

    window.addEventListener('focus', function() {
        handleReturn();
    });

    // =============================================
    // DETECTION 2: SCREENSHOT DETECTION
    // =============================================
    document.addEventListener('keyup', function(e) {
        if (examEnded) return;
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            reportViolation('screenshot', 0);
            showGraceToast('screenshot', 'Screenshot Terdeteksi!', 'Upaya screenshot dicatat sebagai pelanggaran.');
        }
        if (e.key === 's' && e.shiftKey && (e.metaKey || e.ctrlKey)) {
            e.preventDefault();
            reportViolation('screenshot', 0);
        }
    });

    document.addEventListener('keydown', function(e) {
        if (examEnded) return;
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            try { navigator.clipboard && navigator.clipboard.writeText('').catch(function(){}); } catch(x){}
        }
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            reportViolation('screenshot', 0);
        }
    });

    // ---- DETECTION 3: Prevent right-click & copy ----
    document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    document.addEventListener('copy', function(e) { e.preventDefault(); });
    document.addEventListener('cut', function(e) { e.preventDefault(); });

    // ---- DETECTION 4: Prevent keyboard shortcuts ----
    document.addEventListener('keydown', function(e) {
        if (examEnded) return;
        if ((e.ctrlKey && ['c', 'v', 'u', 'a'].includes(e.key.toLowerCase())) ||
            (e.key === 'F12') ||
            (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].includes(e.key.toLowerCase()))) {
            e.preventDefault();
            reportViolation('split_screen', 0);
        }
        if (e.ctrlKey && (e.key === 'Tab' || e.key.toLowerCase() === 'w')) {
            e.preventDefault();
        }
    });

    // =============================================
    // DETECTION 5: FULLSCREEN MODE (FIXED)
    // Uses proper Promise handling + user gesture requirement
    // =============================================
    function checkFullscreen() {
        return !!(document.fullscreenElement ||
                  document.webkitFullscreenElement ||
                  document.mozFullScreenElement ||
                  document.msFullscreenElement);
    }

    function safeRequestFullscreen() {
        // Always returns a proper Promise, even for prefixed methods
        if (!fullscreenSupported) {
            return Promise.resolve(false);
        }

        var elem = document.documentElement;
        try {
            var result;
            if (elem.requestFullscreen) {
                result = elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                result = elem.webkitRequestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                result = elem.mozRequestFullScreen();
            } else if (elem.msRequestFullscreen) {
                result = elem.msRequestFullscreen();
            }
            // Ensure we always return a Promise (webkit returns undefined)
            if (result && typeof result.then === 'function') {
                return result;
            }
            // If prefixed method returned undefined, check after a short delay
            return new Promise(function(resolve) {
                setTimeout(function() {
                    resolve(checkFullscreen());
                }, 200);
            });
        } catch (e) {
            return Promise.reject(e);
        }
    }

    function showFullscreenWarning() {
        if (examEnded) return;
        document.getElementById('fullscreen-warning-overlay').classList.remove('hidden');
    }

    function hideFullscreenWarning() {
        document.getElementById('fullscreen-warning-overlay').classList.add('hidden');
    }

    // Handle fullscreen change events
    function handleFullscreenChange() {
        var nowFullscreen = checkFullscreen();

        if (isInFullscreen && !nowFullscreen && fullscreenRequested) {
            // User exited fullscreen — VIOLATION
            reportViolation('fullscreen_exit', 0);
            showFullscreenWarning();
            showGraceToast('fullscreen', 'Fullscreen Keluar!', 'Kembali ke fullscreen untuk melanjutkan.');
        }

        if (nowFullscreen) {
            hideFullscreenWarning();
        }

        isInFullscreen = nowFullscreen;
    }

    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('MSFullscreenChange', handleFullscreenChange);

    // Re-enter fullscreen button (user gesture!)
    var reenterBtn = document.getElementById('btn-reenter-fullscreen');
    if (reenterBtn) {
        reenterBtn.addEventListener('click', function() {
            safeRequestFullscreen().then(function() {
                isInFullscreen = true;
                hideFullscreenWarning();
            }).catch(function() {
                // Even if fullscreen fails, hide the overlay so they can continue
                hideFullscreenWarning();
            });
        });
    }

    // Try to block ESC for fullscreen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && checkFullscreen()) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    // ===== FULLSCREEN ENTRY OVERLAY =====
    // Show overlay on page load — user must click to enter fullscreen (provides gesture)
    function showFullscreenEntry() {
        if (!fullscreenSupported) {
            // Skip fullscreen entry gate on unsupported browsers (e.g. iOS Safari).
            fullscreenRequested = false;
            return;
        }

        if (fullscreenSupported && !checkFullscreen()) {
            document.getElementById('fullscreen-entry-overlay').classList.remove('hidden');
            fullscreenEntryShown = true;
        } else if (checkFullscreen()) {
            // Already in fullscreen (came from lobby transition)
            isInFullscreen = true;
            fullscreenRequested = true;
        }
        // If fullscreen not supported (some mobile browsers), skip — use other detections
    }

    var enterBtn = document.getElementById('btn-enter-fullscreen');
    if (enterBtn) {
        enterBtn.addEventListener('click', function() {
            safeRequestFullscreen().then(function() {
                isInFullscreen = true;
                fullscreenRequested = true;
                document.getElementById('fullscreen-entry-overlay').classList.add('hidden');
                // Update baseline dimensions after entering fullscreen
                setTimeout(function() {
                    initialWidth = window.innerWidth;
                    initialHeight = window.innerHeight;
                    initialRatio = initialWidth / initialHeight;
                }, 500);
            }).catch(function() {
                // Fullscreen failed — still let them take the exam
                fullscreenRequested = false;
                document.getElementById('fullscreen-entry-overlay').classList.add('hidden');
            });
        });
    }

    // Show the entry overlay on DOM ready
    showFullscreenEntry();

    // =============================================
    // DETECTION 6: MOBILE SPLIT SCREEN / RESIZE
    // Improved thresholds and edge case handling
    // =============================================
    var resizeDebounce = null;

    function checkSplitScreen() {
        if (examEnded) return;

        var currentW = window.innerWidth;
        var currentH = window.innerHeight;

        // Calculate reductions from initial dimensions
        var widthReduction = (initialWidth - currentW) / initialWidth;
        var heightReduction = (initialHeight - currentH) / initialHeight;

        if (isMobile) {
            // MOBILE: Split screen typically reduces width by 40-50%
            // Lower threshold to 20% to catch more aggressive split screen
            if (widthReduction > 0.20) {
                reportViolation('resize_suspicion', 0);
                showSplitScreenOverlay();
                return;
            }
            // Landscape split: height reduces but not width
            // Exclude keyboard popup (only when input/textarea is focused)
            var activeTag = document.activeElement ? document.activeElement.tagName : '';
            var isKeyboardLikely = (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT');
            if (heightReduction > 0.30 && !isKeyboardLikely) {
                reportViolation('resize_suspicion', 0);
                showSplitScreenOverlay();
                return;
            }
        } else {
            // DESKTOP: Window resize > 15% width
            if (widthReduction > 0.15) {
                reportViolation('resize_suspicion', 0);
                showSplitScreenOverlay();
                return;
            }
        }
    }

    function showSplitScreenOverlay() {
        if (examEnded) return;
        document.getElementById('split-screen-overlay').classList.remove('hidden');
    }

    var dismissSplitBtn = document.getElementById('btn-dismiss-split');
    if (dismissSplitBtn) {
        dismissSplitBtn.addEventListener('click', function() {
            document.getElementById('split-screen-overlay').classList.add('hidden');
            // Update baseline
            initialWidth = window.innerWidth;
            initialHeight = window.innerHeight;
            initialRatio = initialWidth / initialHeight;
        });
    }

    window.addEventListener('resize', function() {
        clearTimeout(resizeDebounce);
        resizeDebounce = setTimeout(function() {
            checkSplitScreen();
        }, 400);
    });

    // =============================================
    // DETECTION 7: Orientation change — update baseline
    // =============================================
    if (screen.orientation) {
        screen.orientation.addEventListener('change', function() {
            setTimeout(function() {
                initialWidth = window.innerWidth;
                initialHeight = window.innerHeight;
                initialRatio = initialWidth / initialHeight;
            }, 500);
        });
    }

    // =============================================
    // DETECTION 8: pagehide/pageshow (iOS/Android app switch)
    // =============================================
    window.addEventListener('pagehide', function() {
        if (!examEnded) handleLeave();
    });

    window.addEventListener('pageshow', function(e) {
        if (e.persisted && !examEnded) {
            handleReturn();
        }
    });

    // iOS swipe-from-bottom detection
    if (isIOS) {
        var touchStartY = 0;
        document.addEventListener('touchstart', function(e) {
            touchStartY = e.touches[0].clientY;
        }, { passive: true });
    }

    // =============================================
    // DETECTION 9: Prevent "pull to refresh"
    // =============================================
    document.body.style.overscrollBehavior = 'none';

    // Initialize UI
    updateIntegrityUI(currentIntegrity);

    console.log('[AntiCheat v2.1] Initialized. Fullscreen supported:', fullscreenSupported, '| Mobile:', isMobile, '| iOS:', isIOS);

    })(); // End IIFE
</script>
@endsection
