@extends('layouts.admin')
@section('title', 'Import Siswa')
@section('page-title', 'Import Siswa')

@section('content')
<div class="mb-8 fade-in">
    <a href="{{ route('admin.students.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Daftar Siswa
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
        <h2 class="text-xl font-bold text-misdinar-dark mb-2">Import Banyak Siswa Sekaligus</h2>
        <p class="text-gray-600 mb-6">Masukkan data siswa dalam format CSV (satu baris per siswa).</p>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Format Guide --}}
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">Format Data:</h3>
            <code class="text-sm text-blue-700 block mb-2">username,nama_lengkap,email,password,kelas,lingkungan,asal_sekolah</code>
            <p class="text-sm text-blue-700 mb-2">Contoh:</p>
            <pre class="text-xs bg-blue-100 p-3 rounded overflow-x-auto">andreas,Andreas Putra,andreas@siswa.lms,rahasia123,SMP Kelas 8,Servatius,SMP Negeri 1 Jakarta
benediktus,Benediktus Wijaya,bene@siswa.lms,,SMP Kelas 7,Paulus,SMP Katolik Santo Yosef
christina,Christina Dewi,,,SMP Kelas 8,Maria
dominikus,Dominikus Adi</pre>
            <p class="text-xs text-blue-600 mt-2">
                <strong>Catatan:</strong> 
                - Hanya username yang wajib diisi<br>
                - Jika email kosong, akan dibuat otomatis: username@siswa.lms<br>
                - Jika password kosong, akan menggunakan password default<br>
                - Kelas, lingkungan, dan asal sekolah boleh dikosongkan
            </p>
        </div>

        <form action="{{ route('admin.students.importProcess') }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="default_password" class="block text-sm font-medium text-gray-700 mb-1">Password Default</label>
                <input type="text" name="default_password" id="default_password" value="{{ old('default_password', 'password123') }}"
                       class="w-full max-w-xs px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="password123">
                <p class="mt-1 text-xs text-gray-500">Digunakan jika baris tidak memiliki password</p>
            </div>

            <div>
                <label for="data" class="block text-sm font-medium text-gray-700 mb-1">Data Siswa <span class="text-red-500">*</span></label>
                <textarea name="data" id="data" rows="15" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                          placeholder="andreas,Andreas Putra,andreas@siswa.lms,rahasia123,SMP Kelas 8,Servatius,SMP Negeri 1
benediktus,Benediktus Wijaya,bene@siswa.lms,,SMP Kelas 7,Paulus
christina,Christina Dewi">{{ old('data') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Satu siswa per baris</p>
            </div>

            {{-- Quick Templates --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Template Cepat (klik untuk mengisi):</h4>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="fillTemplate('siswa10')" class="px-3 py-1.5 text-xs font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        10 Siswa (siswa1-siswa10)
                    </button>
                    <button type="button" onclick="fillTemplate('siswa50')" class="px-3 py-1.5 text-xs font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        50 Siswa (siswa1-siswa50)
                    </button>
                    <button type="button" onclick="fillTemplate('siswa100')" class="px-3 py-1.5 text-xs font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        100 Siswa (siswa1-siswa100)
                    </button>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('admin.students.index') }}" class="px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="btn-primary px-5 py-2.5 text-white font-semibold rounded-lg inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Siswa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function fillTemplate(type) {
    const textarea = document.getElementById('data');
    let data = '';
    
    const counts = { siswa10: 10, siswa50: 50, siswa100: 100 };
    const count = counts[type] || 10;
    
    for (let i = 1; i <= count; i++) {
        data += `siswa${i},Siswa ${i},siswa${i}@siswa.lms,,,\n`;
    }
    
    textarea.value = data.trim();
    textarea.focus();
}
</script>
@endsection
