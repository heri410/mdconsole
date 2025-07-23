<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Invoice;
use Baebeca\LexwareApi;
use Baebeca\LexwareException;

class LexofficeWebhookController extends Controller
{
    private LexwareApi $lexwareApi;

    public function __construct()
    {
        $this->lexwareApi = new LexwareApi([
            'api_key' => config('lexoffice.api_key'),
            'ssl_verify' => true
        ]);
    }

    public function handle(Request $request)
    {
        Log::debug('Lexoffice Webhook empfangen', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);

        // Rohdaten für Signature-Check
        $rawBody = $request->getContent();
        $signature = $request->header('X-Lxo-Signature');
        $publicKey = config('lexoffice.public_key');

        if (!$signature || !$publicKey) {
            Log::warning('Lexoffice Webhook: Signature oder Public Key fehlt');
            abort(403, 'Signature oder Public Key fehlt');
        }

        // Signature prüfen (RSA-SHA512, base64-dekodiert)
        $isValid = openssl_verify(
            $rawBody,
            base64_decode($signature),
            $publicKey,
            OPENSSL_ALGO_SHA512
        );
        if ($isValid !== 1) {
            Log::warning('Lexoffice Webhook: Ungültige Signatur');
            abort(403, 'Ungültige Signatur');
        }

        $payload = $request->json()->all();
        Log::info('Lexoffice Webhook empfangen', $payload);

        // Event-Type verarbeiten
        try {
            $this->processWebhookEvent($payload);
        } catch (\Exception $e) {
            Log::error('Fehler beim Verarbeiten des Webhooks', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);
            // Trotzdem 200 zurückgeben, damit Lexoffice nicht retry macht
        }

        return response()->json(['status' => 'ok']);
    }

    private function processWebhookEvent(array $payload)
    {
        $eventType = $payload['eventType'] ?? null;
        $resourceId = $payload['resourceId'] ?? null;

        if (!$eventType || !$resourceId) {
            Log::warning('Webhook-Payload unvollständig', $payload);
            return;
        }

        switch ($eventType) {
            case 'contact.created':
            case 'contact.changed':
                $this->handleContactEvent($resourceId, $eventType);
                break;

            case 'contact.deleted':
                $this->handleContactDeleted($resourceId);
                break;

            case 'invoice.created':
            case 'invoice.changed':
            case 'invoice.status.changed':
                $this->handleInvoiceEvent($resourceId, $eventType);
                break;

            case 'invoice.deleted':
                $this->handleInvoiceDeleted($resourceId);
                break;

            case 'payment.changed':
                $this->handlePaymentChanged($resourceId);
                break;

            default:
                Log::info("Unbekannter Event-Type: {$eventType}", $payload);
        }
    }

    private function handleContactEvent(string $resourceId, string $eventType)
    {
        try {
            $contact = $this->lexwareApi->get_contact($resourceId);
            
            if (!$contact) {
                Log::warning("Kontakt {$resourceId} nicht gefunden");
                return;
            }

            // Model-Methode verwenden für saubere Datenverarbeitung
            $customer = Customer::fromLexofficeContact($contact);
            
            // Daten für updateOrCreate vorbereiten
            $customerData = $customer->toArray();
            
            // Timestamps setzen
            $customerData['updated_at'] = now();
            if ($eventType === 'contact.created') {
                $customerData['created_at'] = now();
            }

            Customer::updateOrCreate(
                ['lexoffice_id' => $contact->id],
                $customerData
            );

            Log::info("Kontakt {$eventType}: {$contact->id} verarbeitet");

        } catch (LexwareException $e) {
            Log::error("Fehler beim Abrufen des Kontakts {$resourceId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleContactDeleted(string $resourceId)
    {
        $customer = Customer::where('lexoffice_id', $resourceId)->first();
        
        if ($customer) {
            $customer->delete();
            Log::info("Kontakt gelöscht: {$resourceId}");
        } else {
            Log::warning("Zu löschender Kontakt nicht gefunden: {$resourceId}");
        }
    }

    private function handleInvoiceEvent(string $resourceId, string $eventType)
    {
        try {
            $invoice = $this->lexwareApi->get_invoice($resourceId);
            
            if (!$invoice) {
                Log::warning("Rechnung {$resourceId} nicht gefunden");
                return;
            }

            // Kundenbezug ermitteln
            $customerId = null;
            if ($invoice->address->contactId ?? null) {
                $customer = Customer::where('lexoffice_id', $invoice->address->contactId)->first();
                $customerId = $customer?->id;
            }

            // Model-Methode verwenden für saubere Datenverarbeitung
            $invoiceModel = Invoice::fromLexofficeInvoice($invoice, $customerId);
            
            // Daten für updateOrCreate vorbereiten
            $invoiceData = $invoiceModel->toArray();
            
            // Timestamps setzen
            $invoiceData['updated_at'] = now();
            if ($eventType === 'invoice.created') {
                $invoiceData['created_at'] = now();
            }

            Invoice::updateOrCreate(
                ['lexoffice_id' => $invoice->id],
                $invoiceData
            );

            Log::info("Rechnung {$eventType}: {$invoice->id} verarbeitet");

        } catch (LexwareException $e) {
            Log::error("Fehler beim Abrufen der Rechnung {$resourceId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleInvoiceDeleted(string $resourceId)
    {
        $invoice = Invoice::where('lexoffice_id', $resourceId)->first();
        
        if ($invoice) {
            $invoice->delete();
            Log::info("Rechnung gelöscht: {$resourceId}");
        } else {
            Log::warning("Zu löschende Rechnung nicht gefunden: {$resourceId}");
        }
    }

    private function handlePaymentChanged(string $resourceId)
    {
        // Payment-Daten abrufen und Invoice-Status aktualisieren
        try {
            // Hier können wir das Payments-Endpoint nutzen, aber das ist in der Library nicht direkt verfügbar
            // Alternativ die Invoice neu laden und Status aktualisieren
            $this->handleInvoiceEvent($resourceId, 'payment.changed');
            
            Log::info("Payment-Änderung verarbeitet für: {$resourceId}");
        } catch (\Exception $e) {
            Log::error("Fehler beim Verarbeiten der Payment-Änderung für {$resourceId}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
