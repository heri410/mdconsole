<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainPrice;
use App\Models\Customer;

class ImportDomainData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importiert Domain-Daten aus der SQL-Datei';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sqlFile = base_path('import/db.sql');
        
        if (!file_exists($sqlFile)) {
            $this->error('SQL-Datei nicht gefunden: ' . $sqlFile);
            return Command::FAILURE;
        }

        $content = file_get_contents($sqlFile);
        
        // Erstelle eine Mapping-Tabelle für Kunden (alte ID -> neue ID basierend auf Email)
        $customerMapping = $this->createCustomerMapping($content);
        
        try {
            // Importiere Domain Provider
            $this->importDomainProviders($content);
            $this->info('Domain Provider importiert.');
            
            // Importiere Domain Preise
            $this->importDomainPrices($content);
            $this->info('Domain Preise importiert.');
            
            // Importiere Domains
            $this->importDomains($content, $customerMapping);
            $this->info('Domains importiert.');
            
            $this->info('Domain-Import erfolgreich abgeschlossen!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Fehler beim Import: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCustomerMapping($content)
    {
        $mapping = [];
        
        // Extrahiere Kunden aus der SQL-Datei
        $pattern = '/INSERT INTO `customer`.*?VALUES\s*(.*?);/s';
        if (preg_match($pattern, $content, $matches)) {
            $values = $matches[1];
            $pattern = '/\((\d+),\s*\d+,\s*(?:\'[^\']*\'|NULL),\s*(?:\'[^\']*\'|NULL),\s*\'([^\']+)\'/';
            
            if (preg_match_all($pattern, $values, $customerMatches, PREG_SET_ORDER)) {
                foreach ($customerMatches as $match) {
                    $oldId = $match[1];
                    $email = $match[2];
                    
                    // Finde den Kunden in der aktuellen Datenbank
                    $customer = Customer::where('email', $email)->first();
                    if ($customer) {
                        $mapping[$oldId] = $customer->getKey();
                        $this->info("Kunde gemappt: $oldId -> {$customer->getKey()} ($email)");
                    } else {
                        $this->warn("Kunde nicht gefunden: $email (alte ID: $oldId)");
                    }
                }
            }
        }
        
        return $mapping;
    }

    private function importDomainProviders($content)
    {
        $this->info('Importiere Domain-Provider...');
        
        // Lösche existierende Provider
        DomainProvider::truncate();
        
        // Provider aus SQL extrahieren
        $pattern = '/INSERT INTO `domainprovider`.*?VALUES\s*(.*?);/s';
        if (preg_match($pattern, $content, $matches)) {
            $values = $matches[1];
            $pattern = '/\((\d+),\s*\'([^\']+)\'\)/';
            
            if (preg_match_all($pattern, $values, $providerMatches, PREG_SET_ORDER)) {
                foreach ($providerMatches as $match) {
                    $id = $match[1];
                    $name = $match[2];
                    
                    DomainProvider::create([
                        'id' => $id,
                        'name' => $name
                    ]);
                }
                
                $this->info(count($providerMatches) . ' Provider importiert');
            }
        }
    }

    private function importDomainPrices($content)
    {
        $this->info('Importiere Domain-Preise...');
        
        // Lösche existierende Preise
        DomainPrice::truncate();
        
        // Preise aus SQL extrahieren
        $pattern = '/INSERT INTO `domainprice`.*?VALUES\s*(.*?);/s';
        if (preg_match($pattern, $content, $matches)) {
            $values = $matches[1];
            $pattern = '/\((\d+),\s*\'([^\']+)\',\s*(\d+),\s*([0-9.]+),\s*([0-9.]+),\s*([0-9.]+),\s*([0-9.]+),\s*([0-9.]+),\s*([0-9.]+)\)/';
            
            if (preg_match_all($pattern, $values, $priceMatches, PREG_SET_ORDER)) {
                foreach ($priceMatches as $match) {
                    DomainPrice::create([
                        'tld' => $match[2],
                        'provider_id' => $match[3],
                        'price_renew' => $match[4],
                        'price_transfer' => $match[5],
                        'price_update' => $match[6],
                        'price_create' => $match[7],
                        'price_restore' => $match[8],
                        'price_change_owner' => $match[9]
                    ]);
                }
                
                $this->info(count($priceMatches) . ' Domain-Preise importiert');
            }
        }
    }

    private function importDomains($content, $customerMapping)
    {
        $this->info('Importiere Domains...');
        
        // Lösche existierende Domains
        Domain::truncate();
        
        // Domains aus SQL extrahieren
        $pattern = '/INSERT INTO `domain`.*?VALUES\s*(.*?);/s';
        if (preg_match($pattern, $content, $matches)) {
            $values = $matches[1];
            $pattern = '/\((\d+),\s*(\d+),\s*\'([^\']+)\',\s*\'([^\']+)\',\s*\'([^\']+)\',\s*\'([^\']+)\',\s*(\d+),\s*\'([^\']+)\',\s*(\d+)\)/';
            
            if (preg_match_all($pattern, $values, $domainMatches, PREG_SET_ORDER)) {
                $importedCount = 0;
                $skippedCount = 0;
                
                foreach ($domainMatches as $match) {
                    $oldCustomerId = $match[2];
                    $tld = $match[3];
                    $fqdn = $match[4];
                    $registerDate = $match[5];
                    $dueDate = $match[6];
                    $providerId = $match[7];
                    $status = $match[8];
                    $billingInterval = $match[9];
                    
                    // Prüfe ob wir eine Mapping für den Kunden haben
                    if (isset($customerMapping[$oldCustomerId])) {
                        $newCustomerId = $customerMapping[$oldCustomerId];
                        
                        Domain::create([
                            'customer_id' => $newCustomerId,
                            'tld' => $tld,
                            'fqdn' => $fqdn,
                            'register_date' => $registerDate,
                            'due_date' => $dueDate,
                            'provider_id' => $providerId,
                            'status' => $status,
                            'billing_interval' => $billingInterval
                        ]);
                        
                        $importedCount++;
                    } else {
                        $this->warn("Domain übersprungen: $fqdn (Kunde-ID $oldCustomerId nicht gefunden)");
                        $skippedCount++;
                    }
                }
                
                $this->info("$importedCount Domains importiert, $skippedCount übersprungen");
            }
        }
    }
}
