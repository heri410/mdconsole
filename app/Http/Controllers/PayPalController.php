<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayPalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\Customer;

class PayPalController extends Controller
{
    protected $paypalService;

    public function __construct(PayPalService $paypalService)
    {
        $this->paypalService = $paypalService;
    }

    // Startet die Sammelzahlung
    public function bulkPay(Request $request)
    {
        $user = Auth::user();
        $customer = $user->customer;
        
        if (!$customer) {
            return redirect()->back()->with('error', 'Sie sind kein registrierter Kunde.');
        }
        
        $invoices = $customer->invoices()
            ->whereIn('status', ['open', 'offen'])
            ->get();

        if ($invoices->isEmpty()) {
            return redirect()->back()->with('error', 'Keine offenen Rechnungen gefunden.');
        }

        try {
            // Teste zuerst die PayPal-Verbindung
            $connectionTest = $this->paypalService->testConnection();
            if (!$connectionTest['success']) {
                Log::error('PayPal Connection Failed:', $connectionTest);
                return redirect()->back()->with('error', 'PayPal-Verbindung fehlgeschlagen. Bitte kontaktieren Sie den Support.');
            }
            
            $response = $this->paypalService->createBulkPayment($invoices);
            Log::info('PayPal Response:', $response);
            
            // Prüfe auf Fehler in der Antwort
            if (isset($response['error'])) {
                Log::error('PayPal API Error:', $response['error']);
                return redirect()->back()->with('error', 'PayPal-Fehler: ' . ($response['error']['message'] ?? 'Unbekannter Fehler'));
            }
            
            // Prüfe ob Zahlung erfolgreich erstellt wurde
            if (!$response['success'] || empty($response['approval_url'])) {
                $errorMsg = 'Die Zahlung konnte nicht erstellt werden.';
                if (isset($response['payment_results'])) {
                    $errors = collect($response['payment_results'])->where('error')->pluck('error.message')->filter();
                    if ($errors->isNotEmpty()) {
                        $errorMsg .= ' Fehler: ' . $errors->first();
                    }
                }
                return redirect()->back()->with('error', $errorMsg);
            }
            
            // Speichere Zahlungsinformationen in der Session
            session([
                'paypal_order_id' => $response['order_id'],
                'paypal_invoices' => $invoices->pluck('id')->toArray(),
                'paypal_total_amount' => $response['total_amount']
            ]);
            
            Log::info('PayPal Bulk Payment created:', [
                'order_id' => $response['order_id'],
                'total_amount' => $response['total_amount'],
                'invoice_count' => $invoices->count()
            ]);

            // Weiterleitung zur PayPal-Genehmigungsseite
            return redirect($response['approval_url']);
        } catch (\Exception $e) {
            Log::error('PayPal Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
        }
    }

    // Erfolg-Callback
    public function bulkSuccess(Request $request)
    {
        $orderId = $request->get('token');
        $invoiceIds = session('paypal_invoices', []);
        $totalAmount = session('paypal_total_amount', 0);
        
        if (!$orderId || empty($invoiceIds)) {
            return redirect()->route('dashboard')->with('error', 'Zahlungsdaten nicht gefunden.');
        }
        
        try {
            // Capture die PayPal-Zahlung
            $result = $this->paypalService->capturePayment($orderId);

            if (isset($result['status']) && $result['status'] === 'COMPLETED') {
                // Ermittle den tatsächlich bezahlten Betrag aus dem PayPal-API-Result
                $paidAmount = null;
                if (isset($result['purchase_units'][0]['payments']['captures'][0]['amount']['value'])) {
                    $paidAmount = $result['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
                }

                // Markiere alle Rechnungen als bezahlt
                Invoice::whereIn('id', $invoiceIds)->update([
                    'status' => 'paid',
                    'web_payment_status' => 'completed',
                    'web_payment_date' => now(),
                    'web_payment_id' => $orderId,
                    'web_payment_amount' => $paidAmount ?? $totalAmount
                ]);

                // Bereinige Session
                session()->forget(['paypal_invoices', 'paypal_order_id', 'paypal_total_amount']);
                
                $invoiceCount = count($invoiceIds);
                return redirect()->route('dashboard')->with('success', 
                    "Zahlung erfolgreich! Alle {$invoiceCount} Rechnungen wurden bezahlt. Gesamtbetrag: " . number_format($totalAmount, 2, ',', '.') . ' €');
            } else {
                return redirect()->route('dashboard')->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
            }
        } catch (\Exception $e) {
            Log::error('PayPal bulk payment capture error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
        }
    }

    // Abbruch-Callback für Bulk-Zahlungen
    public function bulkCancel()
    {
        session()->forget(['paypal_invoices', 'paypal_order_id', 'paypal_total_amount']);
        return redirect()->route('dashboard')->with('error', 'Zahlung abgebrochen.');
    }
}
