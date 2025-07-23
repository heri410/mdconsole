<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        </p>
    </header>

    <div class="mt-6 space-y-6">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label :value="__('Email')" />
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->email }}</p>
                </div>

                <div>
                    <x-input-label value="Rolle" />
                    <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">
                        @if($user->role === 'admin')
                            Administrator
                        @elseif($user->role === 'customer')
                            Kunde
                        @else
                            {{ $user->role }}
                        @endif
                    </p>
                </div>

                @if($user->customer)
                    <div>
                        <x-input-label value="Kundennummer" />
                        <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->customer_number }}</p>
                    </div>

                    @if($user->customer->company_name)
                        <div>
                            <x-input-label value="Firmenname" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->company_name }}</p>
                        </div>
                    @endif

                    @if($user->customer->first_name || $user->customer->last_name)
                        <div>
                            <x-input-label value="Kontaktperson" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ trim($user->customer->first_name . ' ' . $user->customer->last_name) }}</p>
                        </div>
                    @endif

                    @if($user->customer->email)
                        <div>
                            <x-input-label value="Firmen-Email" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->email }}</p>
                        </div>
                    @endif

                    @if($user->customer->phone)
                        <div>
                            <x-input-label value="Telefon" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->phone }}</p>
                        </div>
                    @endif

                    @if($user->customer->street)
                        <div>
                            <x-input-label value="Straße" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->street }}</p>
                        </div>
                    @endif

                    @if($user->customer->zip || $user->customer->city)
                        <div>
                            <x-input-label value="PLZ / Ort" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ trim($user->customer->zip . ' ' . $user->customer->city) }}</p>
                        </div>
                    @endif

                    @if($user->customer->country)
                        <div>
                            <x-input-label value="Land" />
                            <p class="mt-1 text-sm text-gray-900 dark:text-gray-100 p-2 bg-white dark:bg-gray-800 rounded border">{{ $user->customer->country }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Hinweis für Änderungen --}}
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 p-4 rounded-lg">
            <div class="flex">
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        Daten ändern
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>Um Ihre Profil- oder Firmendaten zu ändern, senden Sie bitte eine E-Mail an:</p>
                        <p class="mt-1">
                            <a href="mailto:support@mertens.digital" class="font-medium underline hover:no-underline">
                                support@mertens.digital
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
