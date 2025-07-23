<?php

namespace App\Services;

use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalService {
    protected $provider;

    public function __construct()
    {
        $this->provider = new PayPalClient;
        
        // Manuelle Konfiguration, falls config() nicht verfügbar ist
        $config = [
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            'sandbox' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            ],
            'live' => [
                'client_id' => env('PAYPAL_LIVE_CLIENT_ID', env('PAYPAL_CLIENT_ID')),
                'client_secret' => env('PAYPAL_LIVE_CLIENT_SECRET', env('PAYPAL_CLIENT_SECRET')),
            ],
            'currency' => env('PAYPAL_CURRENCY', 'EUR'),
        ];
        
        // Versuche Laravel config() zu verwenden, falls verfügbar
        if (function_exists('config')) {
            try {
                $config = config('paypal', $config);
            } catch (\Exception $e) {
                // Fallback zur manuellen Konfiguration
            }
        }
        
        $this->provider->setApiCredentials($config);
    }

    /**
     * Erstellt eine PayPal-Zahlung für eine Rechnung
     */
    public function createPayment($invoice)
    {
        // Validierung: Betrag muss größer als 0 sein
        if (!$invoice->total || $invoice->total <= 0) {
            throw new \InvalidArgumentException('Der Rechnungsbetrag muss größer als 0 sein. Aktueller Betrag: ' . $invoice->total);
        }
        
        $order = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($invoice->total, 2, '.', '')
                ],
                'description' => 'Rechnung Nr. ' . $invoice->number
            ]],
            'application_context' => [
                'return_url' => route('paypal.success', ['invoice' => $invoice->id]),
                'cancel_url' => route('paypal.cancel', ['invoice' => $invoice->id])
            ]
        ];
        return $this->provider->createOrder($order);
    }

    /**
     * Testet die Verbindung zu PayPal
     */
    public function testConnection()
    {
        try {
            $token = $this->provider->getAccessToken();
            return [
                'success' => !empty($token['access_token']),
                'message' => 'PayPal-Verbindung erfolgreich'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'PayPal-Verbindung fehlgeschlagen: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Erstellt eine kombinierte PayPal-Zahlung für mehrere Rechnungen
     */
    public function createBulkPayment($invoices)
    {
        try {
            $this->provider->getAccessToken();
            
            // Berechne den Gesamtbetrag aller Rechnungen
            $totalAmount = 0;
            $validInvoices = [];
            $invalidInvoices = [];
            
            foreach ($invoices as $invoice) {
                // Validierung: Betrag muss größer als 0 sein
                if (!$invoice->total || $invoice->total <= 0) {
                    $invalidInvoices[] = [
                        'invoice_id' => $invoice->id,
                        'order_id' => null,
                        'status' => 'failed',
                        'error' => [
                            'name' => 'INVALID_AMOUNT',
                            'message' => 'Der Rechnungsbetrag muss größer als 0 sein. Aktueller Betrag: ' . $invoice->total
                        ]
                    ];
                    continue;
                }
                
                $totalAmount += $invoice->total;
                $validInvoices[] = $invoice;
            }
            
            if (empty($validInvoices)) {
                return [
                    'success' => false,
                    'approval_urls' => [],
                    'payment_results' => $invalidInvoices,
                    'error' => ['message' => 'Keine gültigen Rechnungen für die Zahlung gefunden.']
                ];
            }
            
            // Erstelle eine einzelne PayPal-Bestellung für alle Rechnungen
            $invoiceNumbers = collect($validInvoices)->pluck('number')->implode(', ');
            $description = 'Sammlung für Rechnungen: ' . $invoiceNumbers;
            
            $order = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => config('paypal.currency', 'EUR'),
                        'value' => number_format($totalAmount, 2, '.', '')
                    ],
                    'description' => $description,
                    'custom_id' => 'bulk_' . collect($validInvoices)->pluck('id')->implode('_')
                ]],
                'application_context' => [
                    'return_url' => route('paypal.bulk.success'),
                    'cancel_url' => route('paypal.bulk.cancel')
                ]
            ];

            $response = $this->provider->createOrder($order);
            
            if (isset($response['id'])) {
                // Suche nach der Approval-URL
                $approvalUrl = null;
                if (isset($response['links'])) {
                    foreach ($response['links'] as $link) {
                        if ($link['rel'] === 'approve') {
                            $approvalUrl = $link['href'];
                            break;
                        }
                    }
                }
                
                $paymentResults = [];
                foreach ($validInvoices as $invoice) {
                    $paymentResults[] = [
                        'invoice_id' => $invoice->id,
                        'order_id' => $response['id'],
                        'status' => 'created',
                        'error' => null
                    ];
                }
                
                // Füge ungültige Rechnungen hinzu
                $paymentResults = array_merge($paymentResults, $invalidInvoices);
                
                return [
                    'success' => true,
                    'approval_url' => $approvalUrl,
                    'order_id' => $response['id'],
                    'payment_results' => $paymentResults,
                    'total_amount' => $totalAmount
                ];
            }
            
            return [
                'success' => false,
                'error' => $response['error'] ?? ['message' => 'Unbekannter Fehler beim Erstellen der PayPal-Bestellung']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }

    /**
     * Capture eine bereits genehmigte PayPal-Zahlung
     */
    public function capturePayment($orderId)
    {
        try {
            $this->provider->getAccessToken();
            return $this->provider->capturePaymentOrder($orderId);
        } catch (\Exception $e) {
            throw new \Exception('PayPal Capture Fehler: ' . $e->getMessage());
        }
    }
}