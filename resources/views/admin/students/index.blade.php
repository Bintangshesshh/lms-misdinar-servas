@extends('layouts.admin')
@section('title', 'Kelola Siswa')
@section('page-title', 'Kelola Siswa')

@section('content')
<div class="mb-8 flex items-center justify-between fade-in">
    <div>
        <h1 class="text-3xl font-bold text-misdinar-dark">Kelola Siswa</h1>
        <p class="mt-1 text-gray-600">Tambah, edit, atau hapus akun siswa.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('admin.students.import') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-xl shadow-sm transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import Siswa
        </a>
        <a href="{{ route('admin.students.create') }}"
           class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 text-white font-semibold rounded-xl shadow-sm">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Siswa
        </a>
    </div>
</div>

{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3 fade-in">
        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
    </div>
@endif

@if(session('import_errors'))
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-xl p-4 fade-in">
        <p class="text-sm font-medium text-yellow-800 mb-2">Beberapa baris gagal diimport:</p>
        <ul class="text-xs text-yellow-700 list-disc list-inside">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Stats --}}
<div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 flex items-center justify-between fade-in">
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div>
            <p class="text-2xl font-bold text-gray-900">{{ $students->count() }}</p>
            <p class="text-sm text-gray-500">Total Siswa Terdaftar</p>
        </div>
    </div>
    @if($students->count() > 0)
    <form action="{{ route('admin.students.deleteAll') }}" method="POST" onsubmit="return confirm('PERINGATAN: Anda akan menghapus SEMUA siswa. Lanjutkan?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition">
            Hapus Semua Siswa
        </button>
    </form>
    @endif
</div>

@if($students->isEmpty())
    <div class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-200 fade-in">
        <div class="w-20 h-20 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Belum ada siswa terdaftar</h3>
        <p class="mt-1 text-gray-500 mb-4">Tambahkan siswa satu per satu atau import sekaligus.</p>
        <div class="flex justify-center gap-3">
            <a href="{{ route('admin.students.import') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                Import Siswa
            </a>
            <a href="{{ route('admin.students.create') }}" class="btn-primary inline-flex items-center gap-2 px-4 py-2 text-white font-medium rounded-lg text-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Siswa
            </a>
        </div>
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden fade-in">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Lingkungan</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Asal Sekolah</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($students as $i => $student)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-6 py-4">
                            <span class="font-medium text-gray-900">{{ $student->name }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->full_name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->kelas ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->lingkungan ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $student->asal_sekolah ?? '-' }}</td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('admin.students.edit', $student) }}" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('admin.students.destroy', $student) }}" method="POST" onsubmit="return confirm('Hapus siswa {{ $student->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
