@extends('layouts.admin')
@section('title', 'Import Soal - ' . $exam->title)
@section('page-title', 'Import Soal')

@section('content')
<div class="mb-8 fade-in">
    <a href="{{ route('admin.questions.show', $exam) }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Kembali ke Daftar Soal
    </a>
</div>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
        <h2 class="text-xl font-bold text-misdinar-dark mb-2">Import Banyak Soal Sekaligus</h2>
        <p class="text-gray-600 mb-1">Ujian: <strong>{{ $exam->title }}</strong></p>
        <p class="text-gray-500 text-sm mb-6">Soal yang sudah ada: {{ $exam->questions()->count() }} soal</p>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="text-sm text-red-700 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('import_errors'))
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-semibold text-yellow-800 mb-2">Baris yang error:</h4>
                <ul class="text-sm text-yellow-700 list-disc list-inside">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Format Guide --}}
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 mb-2">Format Data (CSV):</h3>
            <code class="text-sm text-blue-700 block mb-2">soal,opsi_a,opsi_b,opsi_c,opsi_d,jawaban_benar,poin</code>
            <p class="text-sm text-blue-700 mb-2">Contoh:</p>
            <pre class="text-xs bg-blue-100 p-3 rounded overflow-x-auto">Siapakah Bapa Gereja yang menulis buku "Pengakuan-Pengakuan"?,Santo Agustinus,Santo Thomas Aquinas,Santo Ambrosius,Santo Hieronimus,a,10
Sakramen Inisiasi Kristen terdiri dari?,Baptis - Ekaristi - Krisma,Baptis - Tobat - Krisma,Ekaristi - Tobat - Perminyakan,Baptis - Ekaristi - Imamat,a
Berapa jumlah Sakramen dalam Gereja Katolik?,5,6,7,8,c</pre>
            <p class="text-xs text-blue-600 mt-2">
                <strong>Catatan:</strong><br>
                - Minimal 6 kolom wajib: soal, opsi_a, opsi_b, opsi_c, opsi_d, jawaban_benar<br>
                - Jawaban benar: <strong>a</strong>, <strong>b</strong>, <strong>c</strong>, atau <strong>d</strong> (huruf kecil)<br>
                - Poin boleh dikosongkan → akan pakai poin default<br>
                - Jika soal mengandung koma, bungkus dengan tanda kutip: <code>"Soal tentang ini, itu"</code>
            </p>
        </div>

        <form action="{{ route('admin.questions.importProcess', $exam) }}" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="default_points" class="block text-sm font-medium text-gray-700 mb-1">Poin Default per Soal</label>
                <input type="number" name="default_points" id="default_points" value="{{ old('default_points', 10) }}" min="1" max="100"
                       class="w-full max-w-xs px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Digunakan jika baris tidak memiliki kolom poin</p>
            </div>

            <div>
                <label for="data" class="block text-sm font-medium text-gray-700 mb-1">Data Soal <span class="text-red-500">*</span></label>
                <textarea name="data" id="data" rows="18" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm"
                          placeholder="Siapakah santo pelindung Indonesia?,Santo Mikael,Santo Yosef,Santa Maria,Santa Theresia,c,10&#10;Apa arti kata &quot;Ekaristi&quot;?,Pengampunan,Ucapan Syukur,Pembaptisan,Pengutusan,b">{{ old('data') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Satu soal per baris, pisahkan tiap kolom dengan koma</p>
            </div>

            {{-- Quick Templates --}}
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Template Contoh (klik untuk mengisi):</h4>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="fillTemplate('sample5')" class="px-3 py-1.5 text-xs font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        5 Soal Contoh
                    </button>
                    <button type="button" onclick="clearData()" class="px-3 py-1.5 text-xs font-medium bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                        Kosongkan
                    </button>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3">
                <a href="{{ route('admin.questions.show', $exam) }}" class="px-5 py-2.5 text-gray-700 font-medium hover:bg-gray-100 rounded-lg transition">
                    Batal
                </a>
                <button type="submit" class="btn-primary px-5 py-2.5 text-white font-semibold rounded-lg inline-flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Soal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function fillTemplate(type) {
    const textarea = document.getElementById('data');
    if (type === 'sample5') {
        textarea.value = `Siapakah Bapa Gereja yang menulis "Pengakuan-Pengakuan"?,Santo Agustinus,Santo Thomas Aquinas,Santo Ambrosius,Santo Hieronimus,a,10
Sakramen Inisiasi Kristen terdiri dari?,Baptis - Ekaristi - Krisma,Baptis - Tobat - Krisma,Ekaristi - Tobat - Perminyakan,Baptis - Ekaristi - Imamat,a,10
Berapa jumlah Sakramen dalam Gereja Katolik?,5,6,7,8,c,10
Apa nama doa yang diajarkan langsung oleh Yesus?,Salam Maria,Bapa Kami,Kemuliaan,Aku Percaya,b,10
Kitab Suci Perjanjian Baru terdiri dari berapa kitab?,24,25,27,30,c,10`;
    }
    textarea.focus();
}

function clearData() {
    document.getElementById('data').value = '';
    document.getElementById('data').focus();
}
</script>
@endsection
