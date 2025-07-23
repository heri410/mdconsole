<x-guest-layout>
    <form method="POST" action="{{ route('customer.register.store') }}">
        @csrf

        <div class="mb-4 text-sm text-gray-600">
            {{ __('Als bestehender Kunde können Sie hier einen Online-Account erstellen. Geben Sie die E-Mail-Adresse ein, die wir für Sie in unseren Kundendaten haben.') }}
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('E-Mail-Adresse')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Bereits registriert?') }}
            </a>

            <x-primary-button>
                {{ __('Kunden-Account erstellen') }}
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                {{ __('Passwort vergessen?') }}
            </a>
        </div>
    </form>
</x-guest-layout>
</x-parameter>
</invoke>
