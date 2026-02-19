<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-misdinar-dark">Pendaftaran Peserta</h2>
        <p class="mt-1 text-sm text-gray-500">Lengkapi data diri Anda untuk mengikuti ujian calon Misdinar.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        {{-- Section: Data Diri --}}
        <div class="mb-5 p-4 bg-misdinar-50 rounded-xl border border-misdinar-200">
            <h3 class="text-sm font-semibold text-misdinar-700 mb-3">Informasi Pribadi</h3>

            <!-- Nama Penuh -->
            <div class="mb-3">
                <x-input-label for="full_name" value="Nama Lengkap" />
                <x-text-input id="full_name" class="block mt-1 w-full" type="text" name="full_name"
                    :value="old('full_name')" required autofocus
                    placeholder="Nama lengkap sesuai KTP/Kartu Pelajar" />
                <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
            </div>

            <!-- Kelas -->
            <div class="mb-3">
                <x-input-label for="kelas" value="Tingkat Pendidikan" />
                <select id="kelas" name="kelas" required
                    class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-misdinar-primary focus:ring-misdinar-primary text-sm">
                    <option value="" disabled {{ old('kelas') ? '' : 'selected' }}>-- Pilih Tingkat --</option>
                    @foreach(['SD Kelas 1-3','SD Kelas 4-6','SMP Kelas 7','SMP Kelas 8','SMP Kelas 9','SMA Kelas 10','SMA Kelas 11','SMA Kelas 12','Mahasiswa/Umum'] as $k)
                        <option value="{{ $k }}" {{ old('kelas') === $k ? 'selected' : '' }}>{{ $k }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('kelas')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <!-- Umur -->
                <div>
                    <x-input-label for="umur" value="Usia" />
                    <x-text-input id="umur" class="block mt-1 w-full" type="number" name="umur"
                        :value="old('umur')" required min="7" max="100"
                        placeholder="Usia saat ini" />
                    <x-input-error :messages="$errors->get('umur')" class="mt-1" />
                </div>

                <!-- Lingkungan -->
                <div>
                    <x-input-label for="lingkungan" value="Paroki/Lingkungan" />
                    <x-text-input id="lingkungan" class="block mt-1 w-full" type="text" name="lingkungan"
                        :value="old('lingkungan')" required
                        placeholder="Contoh: Paroki Santo Yohanes" />
                    <x-input-error :messages="$errors->get('lingkungan')" class="mt-1" />
                </div>
            </div>
        </div>

        {{-- Section: Akun --}}
        <div class="mb-5 p-4 bg-gray-50 rounded-xl border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Informasi Akun</h3>

            <!-- Username / Name -->
            <div class="mb-3">
                <x-input-label for="name" value="Username" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
                    :value="old('name')" required autocomplete="name"
                    placeholder="Username untuk login" />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <!-- Email -->
            <div class="mb-3">
                <x-input-label for="email" value="Email" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                    :value="old('email')" required autocomplete="username"
                    placeholder="Email aktif Anda" />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <!-- Password -->
            <div class="mb-3">
                <x-input-label for="password" value="Password" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
                    required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" value="Konfirmasi Password" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                    name="password_confirmation" required autocomplete="new-password"
                    placeholder="Ketik ulang password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <a class="text-sm text-gray-600 hover:text-gray-900 underline" href="{{ route('login') }}">
                Sudah punya akun? Login
            </a>

            <x-primary-button>
                Daftar Sekarang
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
