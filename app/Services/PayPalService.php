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
        $order = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => $invoice->total
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
     * Erstellt Bulk-Zahlungen für mehrere Rechnungen
     */
    public function createBulkPayment($invoices)
    {
        try {
            $this->provider->getAccessToken();
            $approvalUrls = [];
            $paymentResults = [];

            foreach ($invoices as $invoice) {
                $order = [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'amount' => [
                            'currency_code' => config('paypal.currency', 'EUR'),
                            'value' => $invoice->total
                        ],
                        'description' => 'Rechnung Nr. ' . $invoice->number,
                        'invoice_id' => $invoice->id
                    ]],
                    'application_context' => [
                        'return_url' => route('paypal.bulk.success'),
                        'cancel_url' => route('paypal.bulk.cancel')
                    ]
                ];

                $response = $this->provider->createOrder($order);
                
                if (isset($response['links'])) {
                    foreach ($response['links'] as $link) {
                        if ($link['rel'] === 'approve') {
                            $approvalUrls[] = [
                                'invoice_id' => $invoice->id,
                                'order_id' => $response['id'],
                                'approval_url' => $link['href']
                            ];
                            break;
                        }
                    }
                }

                $paymentResults[] = [
                    'invoice_id' => $invoice->id,
                    'order_id' => $response['id'] ?? null,
                    'status' => isset($response['id']) ? 'created' : 'failed',
                    'error' => isset($response['error']) ? $response['error'] : null
                ];
            }

            return [
                'success' => !empty($approvalUrls),
                'approval_urls' => $approvalUrls,
                'payment_results' => $paymentResults
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }
}