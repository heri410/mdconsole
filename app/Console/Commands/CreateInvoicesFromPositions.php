<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Position;
use App\Models\Invoice;
use App\Services\LexofficeWebhookService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CreateInvoicesFromPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:create-from-positions {--customer-id= : Nur für spezifischen Kunden} {--force : Auch außerhalb des Abrechnungstages erstellen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Erstellt Rechnungen aus unbezahlten Positionen basierend auf dem Abrechnungstag der Kunden';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $force = $this->option('force');
        $customerId = $this->option('customer-id');
        
        $this->info("Starte Rechnungserstellung für " . $today->format('d.m.Y'));
        
        // Bestimme welche Kunden abgerechnet werden sollen
        $customersQuery = Customer::query();
        
        if ($customerId) {
            $customersQuery->where('id', $customerId);
        } elseif (!$force) {
            // Nur Kunden deren Abrechnungstag heute ist
            $customersQuery->where('billing_day', $today->day);
        }
        
        $customers = $customersQuery->get();
        
        if ($customers->isEmpty()) {
            $this->info('Keine Kunden für Abrechnung gefunden.');
            return 0;
        }
        
        $this->info("Prüfe {$customers->count()} Kunde(n) für Abrechnung...");
        
        $createdInvoices = 0;
        $totalPositions = 0;
        
        foreach ($customers as $customer) {
            $this->line("");
            $this->info("Verarbeite Kunde: {$customer->company_name} (ID: {$customer->id})");
            
            // Hole alle unbezahlten Positionen für diesen Kunden
            $positions = $customer->unbilledPositions()->get();
            
            if ($positions->isEmpty()) {
                $this->warn("  → Keine unbezahlten Positionen gefunden");
                continue;
            }
            
            $this->info("  → {$positions->count()} Position(en) gefunden");
            
            try {
                $invoice = $this->createInvoiceFromPositions($customer, $positions);
                
                if ($invoice) {
                    $createdInvoices++;
                    $totalPositions += $positions->count();
                    $this->info("  → Rechnung erstellt: {$invoice->number} (€ " . number_format($invoice->total_amount, 2, ',', '.') . ")");
                } else {
                    $this->error("  → Fehler beim Erstellen der Rechnung");
                }
                
            } catch (\Exception $e) {
                $this->error("  → Fehler: " . $e->getMessage());
            }
        }
        
        $this->line("");
        $this->info("Fertig! {$createdInvoices} Rechnung(en) aus {$totalPositions} Position(en) erstellt.");
        
        return 0;
    }
    
    /**
     * Erstellt eine Rechnung aus den gegebenen Positionen über Lexoffice
     */
    private function createInvoiceFromPositions(Customer $customer, $positions)
    {
        // Bereite die Rechnungspositionen für Lexoffice vor
        $lineItems = [];
        $totalAmount = 0;
        
        foreach ($positions as $position) {
            $unitPrice = $position->unit_price;
            $quantity = $position->quantity;
            $discount = $position->discount;
            
            // Berechne Nettopreis (bei 19% MwSt)
            $netUnitPrice = round($unitPrice / 1.19, 4);
            
            $lineItems[] = [
                'type' => 'custom',
                'name' => $position->name,
                'description' => $position->description ?: '',
                'quantity' => $quantity,
                'unitName' => $position->unit_name,
                'unitPrice' => [
                    'currency' => 'EUR',
                    'netAmount' => $netUnitPrice,
                    'taxRatePercentage' => 19
                ],
                'discountPercentage' => $discount
            ];
            
            $totalAmount += $position->total_amount;
        }
        
        // Erstelle Rechnung über Lexoffice
        $lexofficeService = app(LexofficeWebhookService::class);
        
        $invoiceData = [
            'type' => 'invoice',
            'voucherDate' => Carbon::today()->format('Y-m-d'),
            'dueDate' => Carbon::today()->addDays(14)->format('Y-m-d'),
            'address' => [
                'contactId' => $customer->lexoffice_id
            ],
            'lineItems' => $lineItems,
            'totalPrice' => [
                'currency' => 'EUR'
            ],
            'taxConditions' => [
                'taxType' => 'net'
            ],
            'paymentConditions' => [
                'paymentTermLabel' => 'Zahlbar innerhalb von 14 Tagen',
                'paymentTermDuration' => 14
            ]
        ];
        
        // Hier würden Sie die Lexoffice API aufrufen
        // Für den Moment erstellen wir eine lokale Rechnung
        $invoice = new Invoice([
            'customer_id' => $customer->id,
            'date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(14),
            'total_amount' => $totalAmount,
            'open_amount' => $totalAmount,
            'status' => 'open',
            'number' => 'POS-' . Carbon::today()->format('Y-m-d') . '-' . $customer->id,
        ]);
        
        $invoice->save();
        
        // Markiere alle Positionen als abgerechnet
        Position::whereIn('id', $positions->pluck('id'))
            ->update([
                'billed' => true,
                'billed_at' => now(),
                'invoice_id' => $invoice->id
            ]);
            
        return $invoice;
    }
}
