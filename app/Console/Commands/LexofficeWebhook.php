<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LexofficeWebhookService;

class LexofficeWebhook extends Command
{
    protected $signature = 'lexoffice:webhook {action? : Aktion (create=erstellen, list=auflisten, delete=löschen)}';
    protected $description = 'Lexoffice Webhook-Management: Erstellen, Auflisten und Löschen von Webhooks für Lexoffice.';

    public function handle(): int
    {
        $action = $this->argument('action');
        $webhookService = app(LexofficeWebhookService::class);
        $this->info('Verfügbare Aktionen:');
        $this->line('  create  - Erstellt alle fehlenden Webhooks für Lexoffice');
        $this->line('  list    - Listet alle registrierten Webhooks auf');
        $this->line('  delete  - Löscht alle registrierten Webhooks');
        $this->newLine();

        if (empty($action)) {
            $this->warn('Bitte geben Sie eine Aktion an! Beispiel: php artisan lexoffice:webhook list');
            return 1;
        }

        switch ($action) {
            case 'create':
                $this->info('Starte das Erstellen der Webhooks...');
                return $this->registerWebhooks($webhookService);
            case 'list':
                $this->info('Starte das Auflisten der Webhooks...');
                return $this->listWebhooks($webhookService);
            case 'delete':
                $this->info('Starte das Löschen der Webhooks...');
                return $this->deleteWebhooks($webhookService);
            default:
                $this->error('Ungültige Aktion. Erlaubt: create, list, delete');
                return 1;
        }
    }

    private function listWebhooks(LexofficeWebhookService $webhookService): int
    {
        $this->info('Lade alle registrierten Webhooks...');
        $webhooks = $webhookService->listWebhooks();
        if (empty($webhooks->content)) {
            $this->warn('Keine Webhooks registriert.');
            return 0;
        }
        $this->table(
            ['Subscription ID', 'Event Type', 'Callback URL', 'Erstellt am'],
            collect($webhooks->content)->map(function ($webhook) {
                return [
                    $webhook->subscriptionId ?? '-',
                    $webhook->eventType ?? '-',
                    $webhook->callbackUrl ?? '-',
                    $webhook->createdDate ?? '-'
                ];
            })->toArray()
        );
        return 0;
    }

    private function deleteWebhooks(LexofficeWebhookService $webhookService): int
    {
        $this->info('Lösche alle Webhooks...');
        $results = $webhookService->deleteAllWebhooks();
        foreach ($results as $subscriptionId => $result) {
            if ($result === 'Gelöscht') {
                $this->line("✓ Webhook {$subscriptionId} gelöscht");
            } else {
                $this->error("✗ Webhook {$subscriptionId}: {$result}");
            }
        }
        return 0;
    }

    private function registerWebhooks(LexofficeWebhookService $webhookService): int
    {
        $this->info('Registriere Lexoffice-Webhooks für diese Anwendung...');
        $webhookUrl = config('lexoffice.webhook_url');
        if (!$webhookUrl) {
            $this->error('LEXOFFICE_WEBHOOK_URL ist nicht konfiguriert! Bitte setzen Sie APP_URL in der .env Datei.');
            return 1;
        }
        $this->line("Webhook-URL: {$webhookUrl}");
        $results = $webhookService->registerMissingWebhooks();
        $this->newLine();
        $this->info('Ergebnisse:');
        foreach ($results as $eventType => $result) {
            if (is_array($result)) {
                if (isset($result['error'])) {
                    $this->error("✗ {$eventType}: {$result['error']}");
                } elseif (isset($result['status']) && $result['status'] === 'already_registered') {
                    $this->line("○ {$eventType}: Bereits registriert");
                } else {
                    $id = $result['id'] ?? 'unbekannt';
                    $this->line("✓ {$eventType}: Registriert (ID: {$id})");
                }
            } elseif (is_object($result)) {
                $id = $result->id ?? 'unbekannt';
                $this->line("✓ {$eventType}: Registriert (ID: {$id})");
            } else {
                $this->error("✗ {$eventType}: Unbekannter Rückgabetyp");
            }
        }
        return 0;
    }
}
