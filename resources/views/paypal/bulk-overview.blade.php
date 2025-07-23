<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('PayPal Zahlungen') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Ihre Rechnungen - Separate Zahlungen</h3>
                        <p class="text-gray-600 mb-4">
                            Um die PayPal-Gebühren zu reduzieren, wurde für jede Rechnung eine separate Zahlung erstellt. 
                            Bitte bezahlen Sie jede Rechnung einzeln über die untenstehenden Links.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        @foreach($paymentData as $payment)
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="font-semibold">Rechnung {{ $payment['invoice']->number }}</h4>
                                            <span class="text-lg font-bold text-green-600">
                                                {{ number_format($payment['invoice']->total, 2, ',', '.') }} €
                                            </span>
                                        </div>
                                        
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <div>
                                                <strong>Datum:</strong> 
                                                {{ $payment['invoice']->date ? $payment['invoice']->date->format('d.m.Y') : 'N/A' }}
                                            </div>
                                            <div>
                                                <strong>Fälligkeitsdatum:</strong> 
                                                {{ $payment['invoice']->due_date ? $payment['invoice']->due_date->format('d.m.Y') : 'N/A' }}
                                            </div>
                                            <div>
                                                <strong>Status:</strong> 
                                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">
                                                    {{ ucfirst($payment['invoice']->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4 flex flex-col space-y-2">
                                        <a href="{{ $payment['approval_url'] }}" 
                                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium text-center"
                                           target="_blank">
                                            Mit PayPal bezahlen
                                        </a>
                                        <div class="text-xs text-gray-500 text-center">
                                            Order ID: {{ substr($payment['order_id'], 0, 8) }}...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <strong>Gesamtsumme:</strong> 
                                {{ number_format($paymentData->sum(fn($p) => $p['invoice']->total), 2, ',', '.') }} €
                            </div>
                            <div class="space-x-2">
                                <a href="{{ route('paypal.bulk.cancel') }}" 
                                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded text-sm">
                                    Abbrechen
                                </a>
                                <a href="{{ route('dashboard') }}" 
                                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                    Zum Dashboard
                                </a>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-sm text-gray-500">
                            <p><strong>Hinweis:</strong> Jeder Link öffnet ein separates PayPal-Zahlungsfenster. Sie können die Links einzeln anklicken und die Zahlungen nacheinander durchführen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
