@extends('layouts.app-custom')
@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
    <p class="mt-1 text-gray-600">Kelola ujian dan pantau siswa.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($exams as $exam)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-3">
                <span class="px-3 py-1 text-xs font-semibold rounded-full
                    @switch($exam->status)
                        @case('draft') bg-gray-100 text-gray-800 @break
                        @case('lobby') bg-yellow-100 text-yellow-800 @break
                        @case('countdown') bg-orange-100 text-orange-800 @break
                        @case('started') bg-green-100 text-green-800 @break
                        @case('finished') bg-blue-100 text-blue-800 @break
                    @endswitch">
                    {{ strtoupper($exam->status) }}
                </span>
                <span class="text-sm text-gray-500">{{ $exam->sessions_count }} siswa</span>
            </div>

            <h3 class="text-lg font-semibold text-gray-900">{{ $exam->title }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ $exam->duration_minutes }} menit</p>

            <div class="mt-4 flex gap-2">
                @if($exam->status === 'draft')
                    <form action="{{ route('admin.exam.openLobby', $exam) }}" method="POST" class="flex-1">
                        @csrf
                        <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                            🚪 Buka Lobby
                        </button>
                    </form>
                @endif

                @if(in_array($exam->status, ['lobby', 'started', 'countdown']))
                    <a href="{{ route('admin.exam.monitor', $exam) }}"
                       class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm transition-colors">
                        📊 Monitor
                    </a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
