<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Domain;
use App\Models\DomainProvider;
use App\Models\DomainPrice;
use App\Models\Customer;

class ImportDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:import {--domains : Importiert zusätzlich die Domains aus der SQL-Datei}';

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
        try {
            $this->info('Starte Import von Domain-Providern und -Preisen...');

            $sqlFile = base_path('import/db.sql');
            if (!file_exists($sqlFile)) {
                $this->error('SQL-Datei nicht gefunden: ' . $sqlFile);
                return Command::FAILURE;
            }
            $content = file_get_contents($sqlFile);

            // Importiere Domain Provider
            $this->importDomainProviders($content);
            $this->info('Domain Provider importiert.');

            // Leere die Domain-Preise Tabelle vor dem Import
            DomainPrice::truncate();
            $this->info('Alte Domain-Preise gelöscht.');

            // Importiere Webteufel Domain Preise
            $this->importDomainPricesWebteufel();
            $this->info('Webteufel Domain Preise importiert.');

            // Importiere IP-Projekts Domain Preise
            $this->importDomainPricesIP($content);
            $this->info('IP-Projekts Domain Preise importiert.');

            $this->info('Import von Providern und Preisen abgeschlossen.');

            // Prüfe, ob auch Domains importiert werden sollen
            if ($this->option('domains')) {
                $this->info('Starte Import von Domains...');

                // Erstelle eine Mapping-Tabelle für Kunden (alte ID -> neue ID basierend auf Email)
                $customerMapping = $this->createCustomerMapping($content);

                // Importiere Domains
                $this->importDomains($content, $customerMapping);
                $this->info('Domains importiert.');
            }

            $this->info('Domain-Import erfolgreich abgeschlossen!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Fehler beim Import: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    private function createCustomerMapping($content)
    {
        $mapping = [];
        
        $this->info('Erstelle Kunden-Mapping...');
        
        // Finde den Start und das Ende des Customer INSERT Statements
        $startPos = strpos($content, "INSERT INTO `customer`");
        if ($startPos === false) {
            $this->error('Customer INSERT Statement nicht gefunden!');
            return $mapping;
        }
        
        // Finde das Ende des Statements (nächstes CREATE, INSERT oder ALTER)
        $endMarkers = ['CREATE TABLE', 'INSERT INTO', 'ALTER TABLE', '--', 'DROP TABLE'];
        $endPos = strlen($content);
        
        foreach ($endMarkers as $marker) {
            $pos = strpos($content, $marker, $startPos + 100); // +100 um das aktuelle INSERT zu überspringen
            if ($pos !== false && $pos < $endPos) {
                $endPos = $pos;
            }
        }
        
        $customerBlock = substr($content, $startPos, $endPos - $startPos);
        
        // Extrahiere alle Tupel aus dem Customer-Block
        $tuplePattern = '/\(\s*(\d+)\s*,\s*\d+\s*,\s*(?:\'[^\']*\'|NULL)\s*,\s*(?:\'[^\']*\'|NULL)\s*,\s*(?:\'([^\']+)\'|NULL)[^)]*\)/';
        
        if (preg_match_all($tuplePattern, $customerBlock, $customerMatches, PREG_SET_ORDER)) {
            $this->info('Gefundene Kunden-Tupel: ' . count($customerMatches));
            
            foreach ($customerMatches as $match) {
                $oldId = $match[1];
                $email = isset($match[2]) && !empty($match[2]) ? $match[2] : null;
                
                if ($email) {
                    // Finde den Kunden in der aktuellen Datenbank
                    $customer = Customer::where('email', $email)->first();
                    if ($customer) {
                        $mapping[$oldId] = $customer->getKey();
                        $this->info("Kunde gemappt: $oldId -> {$customer->getKey()} ($email)");
                    } else {
                        $this->warn("Kunde nicht gefunden: $email (alte ID: $oldId)");
                    }
                } else {
                    $this->warn("Kunde ohne Email übersprungen: ID $oldId");
                }
            }
        } else {
            $this->error('Keine Kunden-Tupel gefunden!');
        }
        
        $this->info('Kunden-Mapping erstellt: ' . count($mapping) . ' Mappings');
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
                    
                    $this->info("Provider erstellt: $id - $name");
                }
                
                $this->info(count($providerMatches) . ' Provider importiert');
            }
        } else {
            $this->warn('Keine Domain-Provider in SQL-Datei gefunden');
        }
    }

    private function importDomainPricesIP($content)
    {
        $this->info('Importiere IP Projekts Domain Preise');

        $provider = \App\Models\DomainProvider::where('name', 'IP-Projekts')->first();
        if (!$provider) {
            $this->error('Domain Provider "IP-Projekts" nicht gefunden!');
            return;
        }

        $providerID = $provider->id;

        // Import CSV File
        $csvPath = storage_path('../import/IP-Projekts.csv');
        if (!file_exists($csvPath)) {
            $this->error('CSV-Datei nicht gefunden: ' . $csvPath);
            return;
        }

        $csvFile = fopen($csvPath, 'r');
        if (!$csvFile) {
            $this->error('Fehler beim Öffnen der CSV-Datei');
            return;
        }

        //Read CSV File
        while($data = fgetcsv($csvFile, 0, ';')) {
            if ($data[0] == 'TLD') {
                continue; // Skip header row
            }

            print("Importiere TLD: {$data[0]}\n");

            //First use the monthly price and then calculate the yearly price
        
            if ($data[2] > 12 || $data[4] > 12 || $data[6] > 12) {
                $this->warn("Skip invalid period: {$data[2]} for TLD {$data[0]}");
                continue;
            }

            $renew_monthly = (float)$data[3] / (float)$data[4];

            $create = 0;
            if ($data[2] == 12) {
                $create = $data[1];
            }elseif ($data[2] == 1) {
                $create = $data[1] + (11 * $renew_monthly);
            } else {
                $this->warn("Skip invalid period: {$data[2]} for TLD {$data[0]}");
                continue; // Skip invalid periods
            }

            if ($create < 0 || $renew_monthly < 0) {
                $this->warn("Skip negative price for TLD {$data[0]}");
                continue; // Skip negative prices
            }

            DomainPrice::create([
                'provider_id' => $providerID,
                'tld' => $data[0],
                'price_renew' => $renew_monthly * 12,
                'price_transfer' => $data[5],
                'price_create' => $create,
                'price_restore' => $data[9],
                'price_change_owner' => $data[9],
                'price_update' => $data[7],
            ]);
        }

        fclose($csvFile);
    }

    /**
     * Importiert Domainpreise von Webteufel
     */
    private function importDomainPricesWebteufel()
    {
        $this->info('Importiere Webteufel Domain Preise...');

        $provider = DomainProvider::where('name', 'Webteufel')->first();
        if (!$provider) {
            $this->error('Domain Provider "Webteufel" nicht gefunden!');
            return;
        }
        $providerID = $provider->id;
        $this->info("Provider ID: $providerID");

        $url = 'https://www.webteufel-it.solutions/Henrik_Domain_preisliste.php';
        
        // SSL-Kontext für file_get_contents erstellen
        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
            "http" => [
                "timeout" => 30,
                "user_agent" => "Mozilla/5.0 (compatible; Laravel/PHP)"
            ]
        ]);
        
        $html = @file_get_contents($url, false, $context);
        if ($html === false) {
            $this->error('Fehler beim Abrufen der Webteufel-Preisliste');
            return;
        }
        
        $this->info('HTML erfolgreich geladen, Länge: ' . strlen($html));

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $rows = $xpath->query('//tr');
        
        $this->info('Gefundene Tabellenzeilen: ' . $rows->length);

        $imported = 0;
        $skipped = 0;
        
        foreach ($rows as $index => $row) {
            $cells = $row->getElementsByTagName('td');
            
            if ($cells->length !== 7) {
                if ($cells->length > 0) {
                    $this->warn("Zeile $index übersprungen: " . $cells->length . " Zellen statt 7");
                }
                continue;
            }
            
            $tld = trim($cells->item(0)->textContent);
            if ($tld === 'TLD' || empty($tld)) {
                $this->info("Header-Zeile übersprungen");
                continue;
            }

            // Preise extrahieren und bereinigen (entferne €-Zeichen und Leerzeichen)
            $price_create = preg_replace('/[^0-9.,]/', '', $cells->item(2)->textContent);
            $price_create = str_replace(',', '.', $price_create);
            
            $price_renew = preg_replace('/[^0-9.,]/', '', $cells->item(3)->textContent);
            $price_renew = str_replace(',', '.', $price_renew);
            
            $price_update = preg_replace('/[^0-9.,]/', '', $cells->item(4)->textContent);
            $price_update = str_replace(',', '.', $price_update);
            
            $price_transfer = preg_replace('/[^0-9.,]/', '', $cells->item(5)->textContent);
            $price_transfer = str_replace(',', '.', $price_transfer);
            
            // Zeilenweise Ausgabe für jede TLD
            $this->info("Importiere TLD: $tld | Create: $price_create | Renew: $price_renew | Transfer: $price_transfer | Update: $price_update");

            // Sonstiges extrahieren (HTML in Zelle 6)
            $sonstiges_html = $dom->saveHTML($cells->item(6));
            preg_match_all('/<h6[^>]*>(.*?)<\/h6>/s', $sonstiges_html, $matches);
            $sonstiges = array_map('trim', $matches[1]);
            $price_restore = isset($sonstiges[0]) ? self::parsePrice($sonstiges[0]) : 0;
            $price_change_owner = isset($sonstiges[4]) ? self::parsePrice($sonstiges[4]) : 0;

            // Prüfe ob Preise numerisch sind
            if (!is_numeric($price_create) || !is_numeric($price_renew)) {
                $this->warn("Überspringe $tld: Preise nicht numerisch (Create: $price_create, Renew: $price_renew)");
                $skipped++;
                continue;
            }

            try {
                DomainPrice::create([
                    'provider_id' => $providerID,
                    'tld' => $tld,
                    'price_renew' => floatval($price_renew),
                    'price_transfer' => floatval($price_transfer),
                    'price_create' => floatval($price_create),
                    'price_update' => floatval($price_update),
                    'price_restore' => $price_restore,
                    'price_change_owner' => $price_change_owner,
                ]);
                $imported++;
                
                if ($imported % 100 == 0) {
                    $this->info("$imported Preise importiert...");
                }
            } catch (\Exception $e) {
                $this->error("Fehler beim Import von $tld: " . $e->getMessage());
                $skipped++;
            }
        }
        
        $this->info("$imported Webteufel-Domainpreise importiert, $skipped übersprungen.");
    }

    /**
     * Hilfsfunktion zum Parsen von Preisen aus Text
     */
    private static function parsePrice($input)
    {
        // Extrahiere die erste Zahl aus dem String
        if (preg_match('/([0-9]+([.,][0-9]+)?)/', $input, $m)) {
            return floatval(str_replace(',', '.', $m[1]));
        }
        return 0;
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
                    
                    // Prüfe ob wir ein Mapping für den Kunden haben
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
        } else {
            $this->warn('Keine Domains in SQL-Datei gefunden');
        }
    }
}
