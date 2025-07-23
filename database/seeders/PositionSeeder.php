<?php

na    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Hole alle Kunden
        $customers = \App\Models\Customer::all();
        
        if ($customers->isEmpty()) {
            $this->command->warn('Keine Kunden gefunden. Erstelle zuerst Kunden.');
            return;
        }
        
        $positionTemplates = [
            [
                'name' => 'Webdesign Services',
                'description' => 'Konzeption und Gestaltung der Website',
                'quantity' => 10.0,
                'unit_name' => 'Stunden',
                'unit_price' => 85.00,
                'discount' => 0.00,
            ],
            [
                'name' => 'Frontend Development',
                'description' => 'HTML/CSS/JavaScript Programmierung',
                'quantity' => 15.5,
                'unit_name' => 'Stunden',
                'unit_price' => 75.00,
                'discount' => 5.00,
            ],
            [
                'name' => 'Backend Development',
                'description' => 'Server-side Programmierung und Datenbank',
                'quantity' => 20.0,
                'unit_name' => 'Stunden',
                'unit_price' => 90.00,
                'discount' => 0.00,
            ],
            [
                'name' => 'Content Management',
                'description' => 'Einrichtung und Konfiguration CMS',
                'quantity' => 5.0,
                'unit_name' => 'Stunden',
                'unit_price' => 65.00,
                'discount' => 10.00,
            ],
            [
                'name' => 'SEO Optimierung',
                'description' => 'Suchmaschinenoptimierung und Meta-Tags',
                'quantity' => 8.0,
                'unit_name' => 'Stunden',
                'unit_price' => 70.00,
                'discount' => 0.00,
            ],
        ];
        
        // Erstelle für jeden Kunden 3-5 zufällige Positionen
        foreach ($customers as $customer) {
            $numberOfPositions = rand(3, 5);
            $selectedTemplates = collect($positionTemplates)->random($numberOfPositions);
            
            foreach ($selectedTemplates as $template) {
                \App\Models\Position::create([
                    'customer_id' => $customer->id,
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'quantity' => $template['quantity'] + (rand(-20, 20) / 10),
                    'unit_name' => $template['unit_name'],
                    'unit_price' => $template['unit_price'] + rand(-10, 15),
                    'discount' => $template['discount'],
                    'billed' => false,
                ]);
            }
            
            $this->command->info("Erstellt {$numberOfPositions} Positionen für Kunde: {$customer->company_name}");
        }
        
        $totalPositions = \App\Models\Position::count();
        $this->command->info("Insgesamt {$totalPositions} Positionen erstellt.");
    } Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
    }
}
