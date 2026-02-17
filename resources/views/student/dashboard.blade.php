@extends('layouts.app-custom')
@section('title', 'Dashboard Siswa')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard Siswa</h1>
    <p class="mt-1 text-gray-600">Pilih ujian yang tersedia untuk bergabung.</p>
</div>

@if($exams->isEmpty())
    <div class="text-center py-12 bg-white rounded-lg shadow">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <h3 class="mt-2 text-lg font-medium text-gray-900">Belum ada ujian tersedia</h3>
        <p class="mt-1 text-gray-500">Tunggu admin membuka lobby ujian.</p>
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($exams as $exam)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6">
                    {{-- Status Badge --}}
                    <div class="flex items-center justify-between mb-4">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full
                            @if($exam->status === 'lobby') bg-yellow-100 text-yellow-800
                            @elseif($exam->status === 'started') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $exam->status === 'lobby' ? '🟡 Lobby Terbuka' : ($exam->status === 'started' ? '🟢 Sedang Berlangsung' : ($exam->status === 'finished' ? '🏁 Selesai' : ucfirst($exam->status))) }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $exam->duration_minutes }} menit</span>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $exam->title }}</h3>

                    @if($exam->status === 'lobby')
                        <form action="{{ route('student.exam.join', $exam) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors">
                                🚪 Masuk Lobby
                            </button>
                        </form>
                    @elseif($exam->status === 'started')
                        @php
                            $mySession = $exam->sessions->where('user_id', Auth::id())->first();
                        @endphp
                        @if($mySession && $mySession->status === 'ongoing')
                            <a href="{{ route('student.exam.take', $exam) }}"
                               class="block w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-center">
                                🔄 Lanjutkan Ujian
                            </a>
                        @elseif($mySession && $mySession->status === 'blocked')
                            <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-center">
                                <p class="text-sm text-red-700 font-semibold">⛔ Anda di-terminate</p>
                                <p class="text-xs text-red-500 mt-1">Menunggu admin mengizinkan kembali</p>
                            </div>
                        @elseif($mySession && $mySession->status === 'completed')
                            <a href="{{ route('student.exam.result', $exam) }}"
                               class="block w-full mt-4 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-center">
                                📊 Lihat Hasil
                            </a>
                        @else
                            <p class="mt-4 text-sm text-orange-600 font-medium">⚠️ Ujian sudah dimulai</p>
                        @endif
                    @elseif($exam->status === 'finished')
                        @php
                            $mySession = $exam->sessions->where('user_id', Auth::id())->first();
                        @endphp
                        @if($mySession)
                            <a href="{{ route('student.exam.result', $exam) }}"
                               class="block w-full mt-4 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors text-center">
                                📊 Lihat Hasil
                            </a>
                        @else
                            <p class="mt-4 text-sm text-gray-500">Ujian telah berakhir</p>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
