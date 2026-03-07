@extends('layouts.app-custom')
@section('title', 'Dashboard Peserta')

@section('content')
<div class="mb-6 sm:mb-8">
    <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900">Dashboard Peserta</h1>
    <p class="mt-1 text-sm sm:text-base text-gray-600">Pilih ujian yang tersedia untuk bergabung.</p>
</div>

@if($exams->isEmpty())
    <div class="text-center py-8 sm:py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="mt-2 text-base sm:text-lg font-medium text-gray-900">Belum ada ujian tersedia</h3>
        <p class="mt-1 text-sm sm:text-base text-gray-500">Tunggu pengumuman pembukaan ujian.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        @foreach($exams as $exam)
            <div class="bg-white rounded-lg sm:rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-4 sm:p-6">
                    {{-- Status Badge --}}
                    <div class="flex items-center justify-between mb-3 sm:mb-4 gap-2">
                        <span class="px-2 sm:px-3 py-1 text-xs font-semibold rounded-full whitespace-nowrap
                            @if($exam->status === 'lobby' || $exam->status === 'countdown') bg-yellow-100 text-yellow-800
                            @elseif($exam->status === 'started') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $exam->status === 'lobby' ? 'Lobby Terbuka' : ($exam->status === 'countdown' ? 'Segera Dimulai' : ($exam->status === 'started' ? 'Sedang Berlangsung' : ($exam->status === 'finished' ? 'Selesai' : ucfirst($exam->status)))) }}
                        </span>
                        <span class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">{{ $exam->duration_minutes }} menit</span>
                    </div>

                    <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2 line-clamp-2">{{ $exam->title }}</h3>

                    @if($exam->status === 'lobby' || $exam->status === 'countdown')
                        @php $mySession = $exam->sessions->where('user_id', Auth::id())->first(); @endphp
                        @if($mySession && $mySession->status === 'ongoing')
                            {{-- Already joined — redirect to lobby to wait --}}
                            <a href="{{ route('student.exam.lobby', $exam) }}"
                               class="block w-full mt-3 sm:mt-4 bg-yellow-500 hover:bg-yellow-600 active:bg-yellow-700 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-center text-sm sm:text-base touch-manipulation">
                                Kembali ke Lobby
                            </a>
                        @elseif($mySession && $mySession->status === 'completed')
                            <a href="{{ route('student.exam.result', $exam) }}"
                               class="block w-full mt-3 sm:mt-4 bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-center text-sm sm:text-base touch-manipulation">
                                Lihat Hasil
                            </a>
                        @elseif($mySession && $mySession->status === 'blocked')
                            <div class="mt-3 sm:mt-4 bg-red-50 border border-red-200 rounded-lg p-2.5 sm:p-3 text-center">
                                <p class="text-xs sm:text-sm text-red-700 font-semibold">Akses Di-blokir</p>
                                <p class="text-xs text-red-500 mt-1">Menunggu admin mengizinkan kembali</p>
                            </div>
                        @else
                            <form action="{{ route('student.exam.join', $exam) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full mt-3 sm:mt-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-sm sm:text-base touch-manipulation">
                                    Masuk Lobby
                                </button>
                            </form>
                        @endif
                    @elseif($exam->status === 'started')
                        @php
                            $mySession = $exam->sessions->where('user_id', Auth::id())->first();
                        @endphp
                        @if($mySession && $mySession->status === 'ongoing')
                            <a href="{{ route('student.exam.take', $exam) }}"
                               class="block w-full mt-3 sm:mt-4 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-center text-sm sm:text-base touch-manipulation">
                                Lanjutkan Ujian
                            </a>
                        @elseif($mySession && $mySession->status === 'blocked')
                            <div class="mt-3 sm:mt-4 bg-red-50 border border-red-200 rounded-lg p-2.5 sm:p-3 text-center">
                                <p class="text-xs sm:text-sm text-red-700 font-semibold">Akses Di-blokir</p>
                                <p class="text-xs text-red-500 mt-1">Menunggu admin mengizinkan kembali</p>
                            </div>
                        @elseif($mySession && $mySession->status === 'completed')
                            <a href="{{ route('student.exam.result', $exam) }}"
                               class="block w-full mt-3 sm:mt-4 bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-center text-sm sm:text-base touch-manipulation">
                                Lihat Hasil
                            </a>
                        @else
                            <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-orange-600 font-medium">Ujian sudah dimulai</p>
                        @endif
                    @elseif($exam->status === 'finished')
                        @php
                            $mySession = $exam->sessions->where('user_id', Auth::id())->first();
                        @endphp
                        @if($mySession)
                            <a href="{{ route('student.exam.result', $exam) }}"
                               class="block w-full mt-3 sm:mt-4 bg-gray-600 hover:bg-gray-700 active:bg-gray-800 text-white font-medium py-2.5 sm:py-3 px-4 rounded-lg transition-colors text-center text-sm sm:text-base touch-manipulation">
                                Lihat Hasil
                            </a>
                        @else
                            <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-500">Ujian telah berakhir</p>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
