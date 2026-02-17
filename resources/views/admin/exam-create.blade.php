@extends('layouts.admin')
@section('title', 'Buat Ujian Baru')
@section('page-title', 'Buat Ujian Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Dashboard
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
            <h1 class="text-2xl font-bold text-white">📝 Buat Ujian Baru</h1>
            <p class="text-indigo-100 mt-1">Isi informasi ujian di bawah ini.</p>
        </div>

        <form action="{{ route('admin.exam.store') }}" method="POST" class="px-8 py-6 space-y-6">
            @csrf

            <div>
                <label for="title" class="block text-sm font-semibold text-gray-700 mb-2">Judul Ujian</label>
                <input type="text" name="title" id="title" required
                       value="{{ old('title') }}"
                       placeholder="Contoh: Ujian Akhir Semester IPA Kelas 9"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                @error('title')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="mata_pelajaran" class="block text-sm font-semibold text-gray-700 mb-2">Mata Pelajaran</label>
                <input type="text" name="mata_pelajaran" id="mata_pelajaran"
                       value="{{ old('mata_pelajaran') }}"
                       placeholder="Contoh: Matematika, IPA, Bahasa Indonesia"
                       list="mapel-list"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                <datalist id="mapel-list">
                    @foreach(\App\Models\Exam::whereNotNull('mata_pelajaran')->distinct()->pluck('mata_pelajaran') as $mapel)
                        <option value="{{ $mapel }}">
                    @endforeach
                </datalist>
                <p class="text-xs text-gray-400 mt-1">Ketik nama mapel baru atau pilih dari yang sudah ada</p>
                @error('mata_pelajaran')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="duration_minutes" class="block text-sm font-semibold text-gray-700 mb-2">Durasi (menit)</label>
                <input type="number" name="duration_minutes" id="duration_minutes" required
                       value="{{ old('duration_minutes', 60) }}" min="1" max="600"
                       class="w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm px-4 py-3">
                <p class="text-xs text-gray-400 mt-1">Minimum 1 menit, maksimum 600 menit (10 jam)</p>
                @error('duration_minutes')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Batal</a>
                <button type="submit"
                        class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Buat Ujian
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
        <p class="text-sm text-blue-700">
            💡 <strong>Tips:</strong> Setelah membuat ujian, Anda langsung diarahkan ke halaman kelola soal untuk menambahkan pertanyaan.
        </p>
    </div>
</div>
@endsection
