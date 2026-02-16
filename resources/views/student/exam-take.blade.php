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
         data-poll-url="/api/exam/{{ $exam->id }}/poll-status"
         data-submit-url="/student/exam/{{ $exam->id }}/submit"
         data-result-url="{{ route('student.exam.result', $exam) }}"
         class="hidden">
    </div>

    {{-- Sticky Top Bar: Timer + Submit --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-3 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-3">
            {{-- Timer --}}
            <div id="timer-display" class="flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg font-mono">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span id="timer-text" class="text-sm font-bold tracking-wider">--:--</span>
            </div>
            <span class="text-sm text-gray-500 hidden sm:inline">Sisa waktu</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">
                <span id="answered-top" class="font-bold text-indigo-600">{{ count($answers) }}</span>/{{ $questions->count() }} dijawab
            </span>
            <button type="button" id="btn-submit-sticky"
                    class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Kumpulkan
            </button>
        </div>
    </div>

    {{-- Force-stop banner (hidden) --}}
    <div id="force-stop-banner" class="hidden mb-4 bg-red-600 text-white rounded-xl p-4 text-center">
        <p class="font-bold text-lg">⛔ Ujian telah dihentikan oleh Admin</p>
        <p class="text-sm mt-1 text-red-100">Jawaban Anda sudah disimpan. Mengalihkan ke halaman hasil...</p>
    </div>

    {{-- TERMINATED Overlay (fullscreen block) --}}
    <div id="terminated-overlay" class="hidden fixed inset-0 z-50 bg-gray-900 bg-opacity-95 flex items-center justify-center">
        <div class="text-center max-w-md mx-auto px-6">
            <div class="w-24 h-24 mx-auto mb-6 bg-red-600 rounded-full flex items-center justify-center">
                <svg class="w-14 h-14 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
            </div>
            <h2 class="text-3xl font-black text-white mb-3">TERMINATED</h2>
            <p class="text-red-400 text-lg font-semibold mb-2">Skor integritas Anda mencapai 0%</p>
            <p class="text-gray-400 mb-6">Anda tidak dapat melanjutkan ujian ini karena terlalu banyak pelanggaran. Hubungi admin/guru jika ingin diizinkan kembali.</p>
            <div id="terminated-waiting" class="bg-red-900 bg-opacity-50 rounded-xl p-4 border border-red-700 mb-4">
                <p class="text-red-300 text-sm">⏳ Menunggu keputusan Admin...</p>
            </div>
            <div id="terminated-reinstated" class="hidden bg-green-900 bg-opacity-50 rounded-xl p-4 border border-green-700 mb-4">
                <p class="text-green-300 text-sm font-semibold">✅ Admin telah mengizinkan Anda kembali!</p>
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

    {{-- Integrity Bar --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Skor Integritas</span>
            <span id="integrity-score" class="text-sm font-bold text-green-600">{{ $session->score_integrity }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div id="integrity-bar" class="bg-green-500 h-3 rounded-full transition-all duration-500"></div>
        </div>
    </div>

    {{-- Warning Banner (hidden by default) --}}
    <div id="cheat-warning" class="hidden mb-4 bg-red-50 border-2 border-red-300 rounded-xl p-4 animate-pulse">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🚨</span>
            <div>
                <p class="font-bold text-red-800">PELANGGARAN TERDETEKSI!</p>
                <p class="text-sm text-red-600" id="warning-text">Anda terdeteksi meninggalkan halaman ujian.</p>
            </div>
        </div>
    </div>

    {{-- Screenshot Warning (persistent) --}}
    <div id="screenshot-warning" class="hidden mb-4 bg-orange-50 border-2 border-orange-400 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📸</span>
            <div>
                <p class="font-bold text-orange-800">SCREENSHOT TERDETEKSI!</p>
                <p class="text-sm text-orange-600">Upaya screenshot terdeteksi dan dicatat. Skor integritas dikurangi.</p>
            </div>
        </div>
    </div>

    {{-- Grace Period Toast (friendly notification return) --}}
    <div id="grace-toast" class="hidden fixed top-6 left-1/2 -translate-x-1/2 z-[9999] transition-all duration-500 ease-out">
        <div id="grace-toast-inner" class="flex items-center gap-3 px-6 py-4 rounded-2xl shadow-xl border backdrop-blur-sm">
            <span id="grace-toast-icon" class="text-2xl"></span>
            <div>
                <p id="grace-toast-title" class="font-bold text-sm"></p>
                <p id="grace-toast-text" class="text-xs"></p>
            </div>
        </div>
    </div>

    {{-- Exam Header --}}
    <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $exam->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $questions->count() }} Soal • {{ $exam->duration_minutes }} menit</p>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-400">Dijawab</p>
                <p class="text-lg font-bold text-indigo-600">
                    <span id="answered-count">{{ count($answers) }}</span> / {{ $questions->count() }}
                </p>
            </div>
        </div>

        {{-- Question Navigation Dots --}}
        <div class="mt-4 flex flex-wrap gap-2" id="question-nav">
            @foreach($questions as $index => $q)
                <button type="button"
                        data-nav-index="{{ $index }}"
                        id="nav-dot-{{ $index }}"
                        class="nav-dot-btn w-9 h-9 rounded-lg text-sm font-semibold transition-all duration-200
                               {{ isset($answers[$q->id]) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        title="Soal {{ $index + 1 }}">
                    {{ $index + 1 }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Questions Container --}}
    <div id="questions-container">
        @foreach($questions as $index => $question)
            <div class="question-slide {{ $index === 0 ? '' : 'hidden' }}"
                 data-question-index="{{ $index }}"
                 data-question-id="{{ $question->id }}">

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4">
                    {{-- Question Number + Points --}}
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center gap-2 px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full text-sm font-semibold">
                            Soal {{ $index + 1 }} dari {{ $questions->count() }}
                        </span>
                        <span class="text-sm text-gray-400 font-medium">{{ $question->points }} poin</span>
                    </div>

                    {{-- Question Text --}}
                    <div class="mb-6">
                        <p class="text-lg text-gray-900 leading-relaxed">{{ $question->question_text }}</p>
                    </div>

                    {{-- Answer Options --}}
                    <div class="space-y-3" id="options-{{ $question->id }}">
                        @foreach(['a', 'b', 'c', 'd'] as $opt)
                            @php
                                $optionField = 'option_' . $opt;
                                $isSelected = isset($answers[$question->id]) && $answers[$question->id] === $opt;
                            @endphp
                            <label class="answer-option group flex items-center gap-4 p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                                          {{ $isSelected ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-gray-50' }}"
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
                                <div class="answer-radio w-10 h-10 rounded-full border-2 flex items-center justify-center flex-shrink-0 font-bold text-sm transition-all duration-200
                                            {{ $isSelected ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-gray-300 text-gray-500 group-hover:border-indigo-400' }}">
                                    {{ strtoupper($opt) }}
                                </div>
                                <span class="text-gray-800 text-base">{{ $question->$optionField }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Navigation Buttons --}}
                <div class="flex items-center justify-between mb-8">
                    @if($index > 0)
                        <button type="button" data-goto="{{ $index - 1 }}" class="nav-prev-btn flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Sebelumnya
                        </button>
                    @else
                        <div></div>
                    @endif

                    @if($index < $questions->count() - 1)
                        <button type="button" data-goto="{{ $index + 1 }}" class="nav-next-btn flex items-center gap-2 px-5 py-2.5 bg-indigo-600 rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition-colors">
                            Selanjutnya
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button type="button" id="btn-submit-exam"
                                class="flex items-center gap-2 px-6 py-2.5 bg-green-600 rounded-lg text-sm font-medium text-white hover:bg-green-700 transition-colors">
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
                    <form id="submit-form" method="POST" class="flex-1" data-action="/student/exam/{{ $exam->id }}/submit">
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
    var POLL_URL = dataEl.dataset.pollUrl;
    var SUBMIT_URL = dataEl.dataset.submitUrl;
    var RESULT_URL = dataEl.dataset.resultUrl;

    var currentQuestion = 0;
    var answeredSet = new Set();
    var examEnded = false;

    // Set form action from data attribute
    var submitForm = document.getElementById('submit-form');
    submitForm.action = submitForm.dataset.action;

    // Initialize answered set from server data
    document.querySelectorAll('.answer-radio-input:checked').forEach(function(input) {
        answeredSet.add(Number(input.dataset.questionId));
    });

    // ---- EVENT DELEGATION: Nav dots ----
    document.querySelectorAll('.nav-dot-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            goToQuestion(Number(this.dataset.navIndex));
        });
    });

    // ---- EVENT DELEGATION: Answer radio inputs ----
    document.querySelectorAll('.answer-radio-input').forEach(function(input) {
        input.addEventListener('change', function() {
            selectAnswer(Number(this.dataset.questionId), this.dataset.option, Number(this.dataset.questionIndex));
        });
    });

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

    function goToQuestion(index) {
        document.querySelectorAll('.question-slide').forEach(function(slide) {
            slide.classList.add('hidden');
        });
        var target = document.querySelector('[data-question-index="' + index + '"]');
        if (target) {
            target.classList.remove('hidden');
            currentQuestion = index;
        }
        document.querySelectorAll('#question-nav button').forEach(function(btn, i) {
            btn.classList.remove('ring-2', 'ring-indigo-400', 'ring-offset-2');
            if (i === index) {
                btn.classList.add('ring-2', 'ring-indigo-400', 'ring-offset-2');
            }
        });
    }

    function updateAnsweredCount() {
        var countEls = document.querySelectorAll('#answered-count, #answered-top');
        countEls.forEach(function(el) { el.textContent = answeredSet.size; });
    }

    function selectAnswer(questionId, option, questionIndex) {
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

        // Auto-save via AJAX
        fetch('/student/exam/' + EXAM_ID + '/save-answer', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                question_id: questionId,
                selected_answer: option,
            })
        }).catch(function(err) { console.error('Auto-save failed:', err); });
    }

    function confirmSubmit() {
        var modal = document.getElementById('submit-modal');
        document.getElementById('modal-answered').textContent = answeredSet.size;

        var unanswered = TOTAL_QUESTIONS - answeredSet.size;
        var warningEl = document.getElementById('modal-warning');
        if (unanswered > 0) {
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

    // Auto-submit (form POST)
    function autoSubmitExam() {
        if (examEnded) return;
        examEnded = true;
        submitForm.submit();
    }

    // Initialize first question nav highlight
    goToQuestion(0);

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
            timerBox.className = 'flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-mono animate-pulse';
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
    // ============================================
    function pollExamStatus() {
        if (examEnded) return;
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
                showTerminatedScreen();
                return;
            }

            // Sync integrity from server (in case admin changed it)
            if (data.session_integrity !== null && data.session_integrity !== undefined) {
                updateIntegrityUI(data.session_integrity);
            }
        }).catch(function(e) {
            console.error('Poll status error:', e);
        });
    }

    setInterval(pollExamStatus, 3000);
    // ============================================
    // ========== ANTI-CHEAT + GRACE PERIOD =========
    // ============================================
    var LOG_URL = '/student/integrity/log-violation';
    var currentIntegrity = Number(dataEl.dataset.score);
    var blurStart = null;
    var warningTimeout = null;
    var graceToastTimeout = null;
    var graceTimer = null;
    var GRACE_PERIOD_MS = 1500; // 1.5 detik grace period untuk notifikasi
    var VIOLATION_COOLDOWN_MS = 5000; // 5 detik cooldown antar pelanggaran sejenis
    var lastViolationTime = {}; // track per-type cooldown
    var screenshotWarningTimeout = null;

    function reportViolation(type, duration) {
        duration = duration || 0;

        // Cooldown: jangan spam pelanggaran sejenis dalam 5 detik
        var now = Date.now();
        if (lastViolationTime[type] && (now - lastViolationTime[type]) < VIOLATION_COOLDOWN_MS) {
            return;
        }
        lastViolationTime[type] = now;

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
            if (res.ok) return res.json();
        }).then(function(data) {
            if (data) {
                updateIntegrityUI(data.current_integrity);
                showWarning(type, data.penalty_applied || 0);

                // Auto-terminated: skor 0 → langsung diblokir
                if (data.terminated) {
                    examEnded = true;
                    showTerminatedScreen();
                }
            }
        }).catch(function(e) {
            console.error('Failed to report violation:', e);
        });
    }

    function updateIntegrityUI(score) {
        currentIntegrity = score;
        var scoreEl = document.getElementById('integrity-score');
        var barEl = document.getElementById('integrity-bar');

        scoreEl.textContent = score + '%';
        barEl.style.width = score + '%';

        if (score >= 80) {
            barEl.className = 'bg-green-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-green-600';
        } else if (score >= 50) {
            barEl.className = 'bg-yellow-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-yellow-600';
        } else {
            barEl.className = 'bg-red-500 h-3 rounded-full transition-all duration-500';
            scoreEl.className = 'text-sm font-bold text-red-600';
        }
    }

    function showWarning(type, penalty) {
        var warningEl = document.getElementById('cheat-warning');
        var textEl = document.getElementById('warning-text');

        var messages = {
            'tab_switch': 'Anda berpindah tab! Skor dikurangi ' + penalty + ' poin.',
            'split_screen': 'Split screen terdeteksi! Skor dikurangi ' + penalty + ' poin.',
            'window_blur': 'Anda keluar dari halaman ujian! Skor dikurangi ' + penalty + ' poin.',
            'screenshot': '📸 Screenshot terdeteksi! Skor dikurangi ' + penalty + ' poin.',
        };

        textEl.textContent = messages[type] || ('Pelanggaran terdeteksi! -' + penalty + ' poin.');
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

        // Style variants
        if (variant === 'ok') {
            inner.className = 'flex items-center gap-3 px-6 py-4 rounded-2xl shadow-xl border backdrop-blur-sm bg-green-50 border-green-300';
            iconEl.textContent = '✅';
            titleEl.className = 'font-bold text-sm text-green-800';
            textEl.className = 'text-xs text-green-600';
        } else if (variant === 'warn') {
            inner.className = 'flex items-center gap-3 px-6 py-4 rounded-2xl shadow-xl border backdrop-blur-sm bg-yellow-50 border-yellow-300';
            iconEl.textContent = '⚠️';
            titleEl.className = 'font-bold text-sm text-yellow-800';
            textEl.className = 'text-xs text-yellow-600';
        } else if (variant === 'screenshot') {
            inner.className = 'flex items-center gap-3 px-6 py-4 rounded-2xl shadow-xl border backdrop-blur-sm bg-orange-50 border-orange-400';
            iconEl.textContent = '📸';
            titleEl.className = 'font-bold text-sm text-orange-800';
            textEl.className = 'text-xs text-orange-600';
        }

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

        // Poll setiap 3 detik untuk cek apakah admin sudah reinstate
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
                }
            }).catch(function(e) {});

            // Cek session status via integrity endpoint
            fetch('/student/integrity/status/' + SESSION_ID, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function(res) {
                if (res.ok) return res.json();
            }).then(function(data) {
                if (data && data.score_integrity > 0) {
                    // Admin sudah reinstate! Tampilkan notifikasi hijau
                    clearInterval(reinstateInterval);
                    document.getElementById('terminated-waiting').classList.add('hidden');
                    document.getElementById('terminated-reinstated').classList.remove('hidden');
                }
            }).catch(function(e) {});
        }, 3000);
    }

    // =============================================
    // DETECTION 1: Tab Switch / Blur WITH GRACE PERIOD
    // Notifikasi HP / pop-up singkat < 3.5s = aman
    // =============================================
    var tabSwitchReported = false;

    function handleLeave() {
        if (!blurStart) {
            blurStart = Date.now();
            tabSwitchReported = false;
        }
        // Set grace timer — hanya report kalau masih blur setelah 3.5 detik
        if (!graceTimer) {
            graceTimer = setTimeout(function() {
                graceTimer = null;
                // Masih blur setelah grace period → ini mencurigakan
                if (blurStart && !tabSwitchReported) {
                    var duration = (Date.now() - blurStart) / 1000;
                    tabSwitchReported = true;
                    reportViolation('tab_switch', duration);
                }
            }, GRACE_PERIOD_MS);
        }
    }

    function handleReturn() {
        var wasBenign = false;

        if (graceTimer) {
            // Kembali sebelum 3.5s → kemungkinan cuma notifikasi
            clearTimeout(graceTimer);
            graceTimer = null;
            wasBenign = true;
        }

        if (blurStart) {
            var awayMs = Date.now() - blurStart;
            blurStart = null;

            if (wasBenign) {
                // Grace period: tidak dihitung pelanggaran
                showGraceToast('ok',
                    'Selamat kembali! 👋',
                    'Sepertinya cuma notifikasi. Tetap fokus mengerjakan ujian ya!'
                );
            } else if (!tabSwitchReported) {
                // Kembali setelah grace period TAPI belum di-report (race condition safety)
                tabSwitchReported = true;
                reportViolation('tab_switch', awayMs / 1000);
            }
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
    // PrintScreen, Win+Shift+S, Ctrl+Shift+S, dll
    // Screenshot TIDAK ADA grace period (langsung report)
    // =============================================
    document.addEventListener('keyup', function(e) {
        var isScreenshot = false;

        // PrintScreen key
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            isScreenshot = true;
        }
        // Win + Shift + S (Windows Snipping Tool) — keyup will fire for 's'
        if (e.key === 's' && e.shiftKey && (e.metaKey || e.ctrlKey)) {
            isScreenshot = true;
        }

        if (isScreenshot) {
            e.preventDefault();
            reportViolation('screenshot', 0);
            showGraceToast('screenshot',
                'Screenshot Terdeteksi! 📸',
                'Upaya screenshot dicatat sebagai pelanggaran.'
            );
        }
    });

    // Also catch on keydown for prevention
    document.addEventListener('keydown', function(e) {
        // PrintScreen
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            e.preventDefault();
            // Try to overwrite clipboard with blank
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText('Screenshot tidak diizinkan selama ujian.').catch(function(){});
            }
        }

        // Win + Shift + S (Snipping Tool)
        if (e.shiftKey && (e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
        }

        // Ctrl+Shift+S (browser Save As, sometimes screenshot)
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 's') {
            e.preventDefault();
            reportViolation('screenshot', 0);
        }
    });

    // ---- DETECTION 3: Prevent right-click & copy ----
    document.addEventListener('contextmenu', function(e) { e.preventDefault(); });
    document.addEventListener('copy', function(e) { e.preventDefault(); });
    document.addEventListener('cut', function(e) { e.preventDefault(); });

    // ---- DETECTION 4: Prevent keyboard shortcuts (DevTools, copy, etc) ----
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && ['c', 'v', 'u', 'a'].includes(e.key.toLowerCase())) ||
            (e.key === 'F12') ||
            (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            e.preventDefault();
            reportViolation('split_screen', 0);
        }
    });

    // Initialize UI
    updateIntegrityUI(currentIntegrity);
</script>
@endsection
