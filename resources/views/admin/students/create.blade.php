@extends('layouts.admin')
@section('title', 'Tambah Siswa')
@section('page-title', 'Tambah Siswa')

@section('content')
<div class="mb-8 fade-in">
    <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Daftar Siswa
    </a>
</div>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
        <h2 class="text-xl font-bold text-misdinar-dark mb-6">Tambah Siswa Baru</h2>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.students.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: andreas">
                    <p class="mt-1 text-xs text-gray-500">Digunakan untuk login</p>
                </div>

                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="full_name" id="full_name" value="{{ old('full_name') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: Andreas Putra">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: andreas@siswa.lms">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <input type="text" name="password" id="password" value="{{ old('password', 'password123') }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Min. 6 karakter">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label for="kelas" class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                    <input type="text" name="kelas" id="kelas" value="{{ old('kelas') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: SMP Kelas 8">
                </div>

                <div>
                    <label for="umur" class="block text-sm font-medium text-gray-700 mb-1">Umur</label>
                    <input type="number" name="umur" id="umur" value="{{ old('umur') }}" min="5" max="100"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: 14">
                </div>

                <div>
                    <label for="lingkungan" class="block text-sm font-medium text-gray-700 mb-1">Lingkungan</label>
                    <input type="text" name="lingkungan" id="lingkungan" value="{{ old('lingkungan') }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Contoh: Servatius">
                </div>
            </div>

            <div>
                <label for="asal_sekolah" class="block text-sm font-medium text-gray-700 mb-1">Asal Sekolah</label>
                <input type="text" name="asal_sekolah" id="asal_sekolah" value="{{ old('asal_sekolah') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Contoh: SMP Negeri 1 Jakarta">
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('admin.students.index') }}" class="px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="btn-primary px-5 py-2.5 text-white font-semibold rounded-lg">
                    Simpan Siswa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
