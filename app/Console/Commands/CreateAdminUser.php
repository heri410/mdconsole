<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create {--email= : E-Mail-Adresse des Administrators} {--password= : Passwort des Administrators}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Erstellt einen neuen Administrator-Benutzer';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Administrator-Benutzer erstellen');
        $this->line('');

        // E-Mail abfragen
        $email = $this->option('email') ?: $this->ask('E-Mail-Adresse des Administrators');
        
        // Validiere E-Mail
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email|unique:users,email'
        ]);

        if ($validator->fails()) {
            $this->error('Fehler bei der E-Mail-Validierung:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('  - ' . $error);
            }
            return 1;
        }

        // Passwort abfragen
        $password = $this->option('password') ?: $this->secret('Passwort des Administrators');
        
        if (empty($password)) {
            $this->error('Passwort darf nicht leer sein.');
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('Passwort muss mindestens 8 Zeichen lang sein.');
            return 1;
        }

        // Bestätigung anzeigen
        $this->line('');
        $this->info('Folgende Daten werden verwendet:');
        $this->table(['Feld', 'Wert'], [
            ['E-Mail', $email],
            ['Passwort', str_repeat('*', strlen($password))],
            ['Rolle', 'Administrator'],
        ]);

        if (!$this->confirm('Administrator-Benutzer mit diesen Daten erstellen?', true)) {
            $this->info('Abgebrochen.');
            return 0;
        }

        try {
            // Benutzer erstellen
            $user = User::create([
                'name' => 'Administrator',
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $this->line('');
            $this->info('✅ Administrator-Benutzer erfolgreich erstellt!');
            $this->line('');
            $this->table(['ID', 'Name', 'E-Mail', 'Rolle'], [
                [$user->id, $user->name, $user->email, $user->role]
            ]);

            $this->line('');
            $this->info('Der Administrator kann sich jetzt mit folgenden Daten anmelden:');
            $this->info('E-Mail: ' . $email);
            $this->info('Passwort: [Das von Ihnen eingegebene Passwort]');

            return 0;

        } catch (\Exception $e) {
            $this->error('Fehler beim Erstellen des Administrator-Benutzers:');
            $this->error($e->getMessage());
            return 1;
        }
    }
}
