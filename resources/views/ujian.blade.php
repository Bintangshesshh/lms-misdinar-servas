<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Online - LMS Misdinar St. Servatius</title>

    <!-- Tailwind via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-amber-50 text-slate-800 antialiased">
    <!-- Navbar utama -->
    <nav class="bg-blue-900 text-white shadow-md">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-base font-semibold tracking-wide sm:text-lg">LMS Misdinar St. Servatius</h1>
                <p class="text-xs text-blue-100">Ujian Calon Misdinar</p>
            </div>

            <div class="text-right text-sm">
                <p class="text-blue-100">Peserta</p>
                <p class="font-semibold">{{ auth()->user()->name ?? 'Peserta Ujian' }}</p>
            </div>
        </div>
    </nav>

    @php
        $totalSoal = count($soals ?? []);
    @endphp

    <main
        class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8"
        x-data="ujianPage({ initialSeconds: 3600, totalQuestions: {{ $totalSoal }} })"
        x-init="init()"
    >
        <!-- timer + progress -->
        <section class="sticky top-0 z-20 mb-6 rounded-xl border border-amber-200 bg-white/95 p-4 shadow-sm backdrop-blur">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-blue-900">Durasi Ujian</h2>
                    <p class="text-xs text-slate-600">Sisa waktu akan terus berkurang. Jawaban tersimpan saat dikirim.</p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-lg bg-amber-100 px-3 py-2 text-amber-900"
                    aria-live="polite"
                    aria-label="Sisa waktu ujian"
                >
                    <span class="text-xs font-medium">Sisa Waktu:</span>
                    <span class="font-mono text-lg font-bold" x-text="formattedTime"></span>
                </div>
            </div>

            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between text-xs sm:text-sm">
                    <span class="font-medium text-slate-700">Progress Pengerjaan</span>
                    <span class="font-semibold text-blue-900" x-text="`${progress}% (${answeredCount}/${totalQuestions})`"></span>
                </div>

                <div
                    class="h-3 w-full overflow-hidden rounded-full bg-slate-200"
                    role="progressbar"
                    aria-label="Progress pengerjaan soal"
                    :aria-valuenow="progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full bg-blue-700 transition-all duration-300"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
            </div>
        </section>

        <!-- Form utama ujian -->
        <form x-ref="ujianForm" action="/submit-ujian" method="POST" class="space-y-6" novalidate>
            @csrf

            @forelse ($soals as $index => $soal)
                <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm sm:p-6">
                    <header class="mb-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Soal {{ $index + 1 }}</p>
                        <h3 class="mt-1 text-base font-semibold leading-relaxed text-slate-800 sm:text-lg">
                            {{ $soal->pertanyaan }}
                        </h3>
                    </header>

                    @if (!empty($soal->gambar))
                        @php
                            $gambarUrl = \Illuminate\Support\Str::startsWith($soal->gambar, ['http://', 'https://'])
                                ? $soal->gambar
                                : asset('storage/' . $soal->gambar);
                        @endphp

                        <figure class="mb-4">
                            <img
                                src="{{ $gambarUrl }}"
                                alt="Ilustrasi untuk soal {{ $index + 1 }}"
                                class="max-h-72 w-full rounded-lg border border-amber-100 object-contain"
                                loading="lazy"
                            >
                        </figure>
                    @endif

                    <!-- Jawaban -->
                    <fieldset class="space-y-3" aria-describedby="bantuan-soal-{{ $soal->id }}">
                        <legend class="sr-only">Pilihan jawaban untuk soal {{ $index + 1 }}</legend>
                        <p id="bantuan-soal-{{ $soal->id }}" class="text-xs text-slate-500">Pilih satu jawaban yang paling tepat.</p>

                        @foreach (['a', 'b', 'c', 'd'] as $opsi)
                            @php
                                $label = $soal->{'pilihan_' . $opsi};
                                $inputId = 'soal-' . $soal->id . '-opsi-' . $opsi;
                            @endphp

                            <label
                                for="{{ $inputId }}"
                                class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition hover:border-blue-300 hover:bg-blue-50"
                            >
                                <input
                                    id="{{ $inputId }}"
                                    type="radio"
                                    name="jawaban[{{ $soal->id }}]"
                                    value="{{ $opsi }}"
                                    class="mt-1 h-4 w-4 border-slate-400 text-blue-700 focus:ring-blue-500"
                                    @checked(old('jawaban.' . $soal->id) === $opsi)
                                    @change="setAnswer({{ $soal->id }}, $event.target.value)"
                                >
                                <span class="text-sm leading-relaxed text-slate-700">
                                    <span class="font-semibold uppercase text-blue-900">{{ $opsi }}.</span>
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                </article>
            @empty
                <section class="rounded-xl border border-amber-200 bg-white p-6 text-center shadow-sm">
                    <h2 class="text-lg font-semibold text-blue-900">Belum Ada Soal</h2>
                    <p class="mt-2 text-sm text-slate-600">Data soal belum tersedia. Silakan hubungi panitia ujian.</p>
                </section>
            @endforelse

            <div class="sticky bottom-4 z-10">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-blue-800 px-4 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:text-base"
                    aria-label="Kirim jawaban ujian"
                >
                    Kirim Jawaban Ujian
                </button>
            </div>
        </form>
    </main>

    <script>
        // Komponen Alpine untuk logika timer dan progress ujian.
        function ujianPage({ initialSeconds, totalQuestions }) {
            return {
                remainingSeconds: initialSeconds,
                totalQuestions,
                answers: {},
                answeredCount: 0,
                progress: 0,
                warningShown: false,

                get formattedTime() {
                    const minutes = Math.floor(this.remainingSeconds / 60);
                    const seconds = this.remainingSeconds % 60;
                    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                },

                init() {
                    this.restoreInitialAnswers();
                    this.startTimer();
                },

                restoreInitialAnswers() {
                    const radios = document.querySelectorAll('input[type="radio"][name^="jawaban["]');
                    radios.forEach((radio) => {
                        if (radio.checked) {
                            const match = radio.name.match(/jawaban\[(\d+)\]/);
                            if (match) {
                                this.answers[match[1]] = radio.value;
                            }
                        }
                    });

                    this.updateProgress();
                },

                setAnswer(questionId, value) {
                    this.answers[String(questionId)] = value;
                    this.updateProgress();
                },

                updateProgress() {
                    this.answeredCount = Object.keys(this.answers).length;

                    if (this.totalQuestions > 0) {
                        this.progress = Math.round((this.answeredCount / this.totalQuestions) * 100);
                    } else {
                        this.progress = 0;
                    }
                },

                startTimer() {
                    setInterval(() => {
                        if (this.remainingSeconds <= 0) {
                            return;
                        }

                        this.remainingSeconds -= 1;

                        // Peringatan saat tersisa 5 menit.
                        if (this.remainingSeconds === 300 && !this.warningShown) {
                            this.warningShown = true;
                            Swal.fire({
                                icon: 'warning',
                                title: 'Waktu Tinggal 5 Menit',
                                text: 'Silakan periksa kembali jawaban Anda sebelum waktu habis.',
                                confirmButtonColor: '#1d4ed8'
                            });
                        }

                        // Saat waktu habis, kirim form otomatis.
                        if (this.remainingSeconds === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Waktu Habis',
                                text: 'Sistem akan mengirim jawaban Anda secara otomatis.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                confirmButtonColor: '#1d4ed8'
                            }).then(() => {
                                this.$refs.ujianForm.submit();
                            });
                        }
                    }, 1000);
                }
            };
        }
    </script>

</body>
</html>
