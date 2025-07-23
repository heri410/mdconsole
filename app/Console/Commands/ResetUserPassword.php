<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password {email : E-Mail des Users} {password? : Neues Passwort (optional)}';
    protected $description = 'Setzt das Passwort für einen User zurück oder legt ihn an.';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password') ?? str()->random(12);

        $user = User::where('email', $email)->first();
        if (!$user) {
            // Versuche passenden Kunden zu finden
            $customer = Customer::where('email', $email)->first();
            if (!$customer) {
                $this->error('Kein User und kein Kunde mit dieser E-Mail gefunden.');
                return 1;
            }
            // User anlegen und mit Kunde verknüpfen
            $user = User::create([
                'name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'email' => $email,
                'password' => Hash::make($password),
                'customer_id' => $customer->id,
                'role' => 'customer',
                'email_verified_at' => now(),
            ]);
            $this->info("Neuer User für Kunden {$customer->customer_number} angelegt.");
        } else {
            $user->password = Hash::make($password);
            $user->save();
            $this->info("Passwort für {$email} wurde zurückgesetzt.");
        }
        $this->line("Neues Passwort: {$password}");
        return 0;
    }
}
