<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full text-base" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="mt-1 flex items-stretch gap-2">
                <x-text-input id="password" class="block w-full text-base"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />
                <button type="button"
                        id="toggle-password"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-white text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-misdinar-primary"
                        aria-label="Lihat password"
                        aria-pressed="false">
                    <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.966 9.966 0 012.293-3.95m3.643-2.31A9.955 9.955 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.969 9.969 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a2.99 2.99 0 00-2.121.879M12 9l0 0m0 6a3 3 0 01-3-3m3 3c.88 0 1.67-.38 2.22-.98" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-misdinar-primary text-misdinar-primary shadow-sm focus:ring-misdinar-primary w-4 h-4" name="remember">
                <span class="ms-2 text-sm sm:text-base text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mt-6">
            @if (Route::has('password.request'))
                <a class="text-center sm:text-left underline text-sm text-misdinar-primary hover:text-misdinar-dark rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-misdinar-primary" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="w-full sm:w-auto justify-center">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <!-- Registration disabled - contact admin for account -->
    </form>

    <script>
        (function() {
            var passwordInput = document.getElementById('password');
            var toggleButton = document.getElementById('toggle-password');
            var iconEye = document.getElementById('icon-eye');
            var iconEyeOff = document.getElementById('icon-eye-off');

            if (!passwordInput || !toggleButton || !iconEye || !iconEyeOff) {
                return;
            }

            toggleButton.addEventListener('click', function() {
                var isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';

                iconEye.classList.toggle('hidden', isHidden);
                iconEyeOff.classList.toggle('hidden', !isHidden);

                toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isHidden ? 'Sembunyikan password' : 'Lihat password');
            });
        })();
    </script>
</x-guest-layout>
