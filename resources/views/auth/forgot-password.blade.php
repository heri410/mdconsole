<x-guest-layout>
    <div class="mb-4 text-sm text-white/90 drop-shadow-lg leading-relaxed">
        {{ __('Passwort vergessen? Kein Problem. Geben Sie einfach Ihre E-Mail-Adresse ein und wir senden Ihnen einen Link zum Zurücksetzen des Passworts zu. Als Kunde erhalten Sie automatisch Zugang, falls Sie bereits in unserem System registriert sind.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('ui.email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('ui.send_password_reset_link') }}
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
