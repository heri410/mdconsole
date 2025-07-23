<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('ui.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('ui.password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-black/30 border-white/20 text-blue-400 shadow-sm focus:ring-blue-400 focus:ring-offset-transparent" name="remember">
                <span class="ms-2 text-sm text-white/90 drop-shadow-lg">{{ __('ui.remember_me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-white/80 hover:text-white drop-shadow-lg rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-400 focus:ring-offset-transparent transition-all duration-200" href="{{ route('password.request') }}">
                    {{ __('ui.forgot_password') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('ui.log_in') }}
            </x-primary-button>
        </div>
        
        <!-- Legal Links -->
        <div class="mt-6 pt-4 border-t border-white/10 flex justify-center space-x-8">
            <a href="https://mertens.digital/datenschutzerklaerung/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="text-xs text-white/70 hover:text-white/90 drop-shadow-lg transition-all duration-200">
                Datenschutzerklärung
            </a>
            <a href="https://mertens.digital/impressum/" 
               target="_blank" 
               rel="noopener noreferrer"
               class="text-xs text-white/70 hover:text-white/90 drop-shadow-lg transition-all duration-200">
                Impressum
            </a>
        </div>
    </form>
</x-guest-layout>
