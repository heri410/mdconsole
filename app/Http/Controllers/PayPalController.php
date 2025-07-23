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
            
            // Prüfe ob Zahlungen erfolgreich erstellt wurden
            if (!$response['success'] || empty($response['approval_urls'])) {
                $errorMsg = 'Keine Zahlungen konnten erstellt werden.';
                if (isset($response['payment_results'])) {
                    $errors = collect($response['payment_results'])->where('error')->pluck('error.message')->filter();
                    if ($errors->isNotEmpty()) {
                        $errorMsg .= ' Fehler: ' . $errors->first();
                    }
                }
                return redirect()->back()->with('error', $errorMsg);
            }
            
            // Bei mehreren separaten Zahlungen nehme die erste für die Weiterleitung
            // TODO: Implementiere eine Übersichtsseite für mehrere Zahlungen
            $firstPayment = $response['approval_urls'][0];
            $approvalUrl = $firstPayment['approval_url'];
            
            Log::info('PayPal Approval URLs:', $response['approval_urls']);

            if ($approvalUrl) {
                // Speichere alle Zahlungsinformationen in der Session
                session([
                    'paypal_bulk_payments' => $response['approval_urls'],
                    'paypal_invoices' => $invoices->pluck('id')->toArray()
                ]);
                
                // Bei mehreren Zahlungen zeige eine Übersichtsseite
                if (count($response['approval_urls']) > 1) {
                    return redirect()->route('paypal.bulk.overview');
                }
                
                // Bei nur einer Zahlung direkt weiterleiten
                return redirect($approvalUrl);
            }
            return redirect()->back()->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
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
                    'web_payment_amount' => $paidAmount
                ]);

                session()->forget('paypal_invoices');
                return redirect()->route('dashboard')->with('success', 'Zahlung erfolgreich! Alle Rechnungen wurden bezahlt.');
            } else {
                return redirect()->route('dashboard')->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
            }
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Die Zahlung konnte nicht durchgeführt werden. Bitte versuchen Sie es später erneut.');
        }
    }

    // Übersichtsseite für mehrere separate Zahlungen
    public function bulkOverview()
    {
        $bulkPayments = session('paypal_bulk_payments', []);
        $invoiceIds = session('paypal_invoices', []);
        
        if (empty($bulkPayments)) {
            return redirect()->route('dashboard')->with('error', 'Keine Zahlungsinformationen gefunden.');
        }
        
        // Lade die Rechnungsdaten
        $invoices = Invoice::whereIn('id', $invoiceIds)->get()->keyBy('id');
        
        // Kombiniere Zahlungs- und Rechnungsdaten
        $paymentData = collect($bulkPayments)->map(function ($payment) use ($invoices) {
            $invoice = $invoices->get($payment['invoice_id']);
            return [
                'invoice' => $invoice,
                'approval_url' => $payment['approval_url'],
                'order_id' => $payment['order_id']
            ];
        });
        
        return view('paypal.bulk-overview', compact('paymentData'));
    }

    // Erfolg-Callback für einzelne Rechnung
    public function success(Request $request, Invoice $invoice)
    {
        $orderId = $request->get('token');
        
        if (!$orderId) {
            return redirect()->route('dashboard')->with('error', 'Zahlungsdaten nicht gefunden.');
        }
        
        try {
            // Capture die PayPal-Zahlung
            $result = $this->paypalService->capturePayment($orderId);

            if (isset($result['status']) && $result['status'] === 'COMPLETED') {
                // Markiere die Rechnung als bezahlt
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);

                Log::info('Invoice paid successfully', [
                    'invoice_id' => $invoice->id,
                    'paypal_order_id' => $orderId
                ]);

                return redirect()->route('dashboard')->with('success', 
                    'Rechnung ' . $invoice->number . ' wurde erfolgreich bezahlt!');
            } else {
                Log::error('PayPal payment capture failed for single invoice', [
                    'result' => $result,
                    'invoice_id' => $invoice->id
                ]);
                return redirect()->route('dashboard')->with('error', 
                    'Die Zahlung für Rechnung ' . $invoice->number . ' konnte nicht durchgeführt werden.');
            }
        } catch (\Exception $e) {
            Log::error('PayPal single payment error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 
                'Die Zahlung für Rechnung ' . $invoice->number . ' konnte nicht durchgeführt werden.');
        }
    }
    
    // Abbruch-Callback für einzelne Rechnung
    public function cancel(Invoice $invoice)
    {
        return redirect()->route('dashboard')->with('error', 
            'Zahlung für Rechnung ' . $invoice->number . ' abgebrochen.');
    }

    // Abbruch-Callback für Bulk-Zahlungen
    public function bulkCancel()
    {
        session()->forget(['paypal_invoices', 'paypal_bulk_payments']);
        return redirect()->route('dashboard')->with('error', 'Zahlung abgebrochen.');
    }
}
