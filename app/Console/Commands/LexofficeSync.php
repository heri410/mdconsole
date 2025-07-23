<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Baebeca\LexwareApi;
use App\Models\Customer;
use App\Models\Invoice;

class LexofficeSync extends Command
{
    protected $signature = 'lexoffice:sync {type? : Typ (customers=Kunden, invoices=Rechnungen, all=beides)}';
    protected $description = 'Lexoffice: Synchronisiere Kunden und Rechnungen';

    public function handle(): int
    {
        $type = $this->argument('type');
        $this->info('Verfügbare Typen:');
        $this->line('  customers - Synchronisiert alle Kunden');
        $this->line('  invoices  - Synchronisiert alle Rechnungen');
        $this->line('  all       - Synchronisiert Kunden und Rechnungen');
        $this->newLine();

        if (empty($type)) {
            $this->warn('Bitte geben Sie einen Typ an! Beispiel: php artisan lexoffice:sync all');
            return 1;
        }

        $apiKey = config('app.lexoffice_api_key') ?? env('LEXOFFICE_API_KEY');
        $lexware = new LexwareApi([
            'api_key' => $apiKey
        ]);

        if ($type === 'customers') {
            $this->importCustomers($lexware);
        } elseif ($type === 'invoices') {
            $this->importInvoices($lexware);
        } elseif ($type === 'all') {
            $this->importCustomers($lexware);
            $this->importInvoices($lexware);
        } else {
            $this->error('Ungültiger Typ. Erlaubt: customers, invoices, all');
            return 1;
        }
        return 0;
    }

    protected function importCustomers($lexware)
    {
        $contacts = $lexware->get_contacts_all();
        $contactsCount = count($contacts);
        $this->info('Loaded customers from Lexoffice: ' . $contactsCount);

        $imported = 0;
        $skipped = 0;

        foreach ($contacts as $contact) {
            // Only import customers, skip vendors
            if (!isset($contact->roles->customer)) {
                $skipped++;
                continue;
            }

            try {
                // Mapping mit Model-Methode
                $customerObj = Customer::fromLexofficeContact($contact);
                $customerArr = $customerObj->toArray();
                if (empty($customerArr['customer_number'])) {
                    $this->error("Customer {$contact->id} has no customer number. Skipping this entry.");
                    $skipped++;
                    continue;
                }

                // Create or update customer
                $customer = Customer::updateOrCreate(
                    ['lexoffice_id' => $contact->id],
                    $customerArr
                );
                $imported++;
                // Display name
                $displayName = $customerArr['company_name'] ?? '';
                if (!$displayName) {
                    $first = $customerArr['first_name'] ?? '';
                    $last = $customerArr['last_name'] ?? '';
                    if ($first || $last) {
                        $displayName = trim($first . ' ' . $last);
                    } else {
                        $displayName = $customerArr['lexoffice_id'] ?? $contact->id;
                    }
                }
                $this->line("Imported/Updated customer: " . $displayName);

            } catch (\Exception $e) {
                $this->error("Error importing customer {$contact->id}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("Customer import finished: {$imported} imported/updated, {$skipped} skipped.");
    }
    protected function importInvoices($lexware)
    {
        $invoices = $lexware->get_invoices_all();
        $invoicesCount = count($invoices);
        $this->info('Loaded invoices from Lexoffice: ' . $invoicesCount);

        $imported = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($invoices as $invoice) {
            try {
                if ($invoice->voucherStatus === 'draft') {
                    $skipped++;
                    continue; // Skip draft invoices
                }

                if ($invoice->voucherType !== 'invoice') {
                    $skipped++;
                    continue; // Skip non-invoice vouchers
                }

                // Skip invoices without contactId
                if (!isset($invoice->contactId) || empty($invoice->contactId)) {
                    $this->info("Invoice {$invoice->voucherNumber} has no contactId. Skipping.");
                    $skipped++;
                    continue;
                }

                // Find customer id
                $customerId = Customer::where('lexoffice_id', $invoice->contactId)->value('id');
                if (!$customerId) {
                    $this->info("No customer found for invoice {$invoice->voucherNumber} (contactId: {$invoice->contactId}). Skipping.");
                    $skipped++;
                    continue;
                }

                // Mapping mit Model-Methode
                $invoiceObj = Invoice::fromLexofficeInvoice($invoice, $customerId);
                // Create or update invoice
                $dbInvoice = Invoice::updateOrCreate(
                    ['number' => $invoice->voucherNumber],
                    $invoiceObj->toArray()
                );
                $dbInvoiceArr = $dbInvoice->toArray();
                if ($dbInvoice->wasRecentlyCreated) {
                    $imported++;
                    $this->line("Imported invoice: " . ($dbInvoiceArr['number'] ?? ''));
                    // PDF bei neuen Rechnungen herunterladen
                    $this->downloadInvoicePdf($lexware, $invoice);
                } else if ($dbInvoice->wasChanged()) {
                    $updated++;
                    $this->line("Updated invoice: " . ($dbInvoiceArr['number'] ?? ''));
                    // PDF bei geänderten Rechnungen herunterladen
                    $this->downloadInvoicePdf($lexware, $invoice);
                } else {
                    $this->line("No changes for invoice: " . ($dbInvoiceArr['number'] ?? ''));
                }
            } catch (\Exception $e) {
                $this->error("Error importing/updating invoice {$invoice->voucherNumber}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("Invoice import finished: {$imported} imported, {$updated} updated, {$skipped} skipped.");
    }

    private function downloadInvoicePdf($lexware, $invoice)
    {
        try {
            $filename = $invoice->voucherNumber . ".pdf";
            $path = storage_path("app/private/invoices/");
            
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            
            $lexware->get_pdf("invoices", $invoice->id, $path . $filename);
            $this->info("PDF gespeichert: invoices/" . $filename);
        } catch (\Exception $e) {
            $this->warn("PDF-Download fehlgeschlagen für Rechnung {$invoice->voucherNumber}: " . $e->getMessage());
        }
    }
}
