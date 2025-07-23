<?php

namespace App\Services;

use Baebeca\LexwareApi;
use Baebeca\LexwareException;
use Illuminate\Support\Facades\Log;

class LexofficeWebhookService
{
    private LexwareApi $lexwareApi;
    private string $webhookUrl;

    public function __construct()
    {
        $this->lexwareApi = new LexwareApi([
            'api_key' => config('lexoffice.api_key'),
            'ssl_verify' => true,
            'callback' => config('lexoffice.webhook_url')
        ]);
        $this->webhookUrl = config('lexoffice.webhook_url');
    }

    /**
     * Registriert alle wichtigen Webhook-Events
     */
    public function registerWebhooks(): array
    {
        $events = [
            'contact.created',
            'contact.changed',
            'contact.deleted',
            'invoice.created',
            'invoice.changed',
            'invoice.deleted',
            'invoice.status.changed',
            'payment.changed',
        ];

        $results = [];

        foreach ($events as $eventType) {
            try {
                $result = $this->registerWebhook($eventType);
                $results[$eventType] = $result;
                Log::info("Webhook für Event '{$eventType}' erfolgreich registriert", (array) $result);
            } catch (\Exception $e) {
                $results[$eventType] = ['error' => $e->getMessage()];
                Log::error("Fehler beim Registrieren des Webhooks für Event '{$eventType}': " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Registriert einen einzelnen Webhook
     */
    public function registerWebhook(string $eventType): object
    {
        try {
            $result = $this->lexwareApi->create_event($eventType, $this->webhookUrl);
            return $result;
        } catch (LexwareException $e) {
            throw new \Exception("Lexware API Fehler: " . $e->getMessage());
        }
    }

    /**
     * Listet alle registrierten Webhooks auf
     */
    public function listWebhooks(): object
    {
        try {
            $result = $this->lexwareApi->get_events_all();
            return $result;
        } catch (LexwareException $e) {
            throw new \Exception("Lexware API Fehler: " . $e->getMessage());
        }
    }

    /**
     * Löscht einen Webhook
     */
    public function deleteWebhook(string $subscriptionId): bool
    {
        try {
            $result = $this->lexwareApi->delete_event($subscriptionId);
            return $result === true; // DELETE returns true on success (status 204)
        } catch (LexwareException $e) {
            throw new \Exception("Lexware API Fehler: " . $e->getMessage());
        }
    }

    /**
     * Löscht alle Webhooks
     */
    public function deleteAllWebhooks(): array
    {
        $webhooks = $this->listWebhooks();
        $results = [];

        foreach ($webhooks->content ?? [] as $webhook) {
            try {
                $this->deleteWebhook($webhook->subscriptionId);
                $results[$webhook->subscriptionId] = 'Gelöscht';
                Log::info("Webhook {$webhook->subscriptionId} gelöscht");
            } catch (\Exception $e) {
                $results[$webhook->subscriptionId] = 'Fehler: ' . $e->getMessage();
                Log::error("Fehler beim Löschen des Webhooks {$webhook->subscriptionId}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Überprüft ob ein bestimmter Event-Type bereits registriert ist
     */
    public function isEventRegistered(string $eventType): bool
    {
        try {
            $webhooks = $this->listWebhooks();
            
            foreach ($webhooks->content ?? [] as $webhook) {
                if ($webhook->eventType === $eventType && $webhook->callbackUrl === $this->webhookUrl) {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Fehler beim Überprüfen der Webhook-Registrierung: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registriert nur fehlende Webhooks (verhindert Duplikate)
     */
    public function registerMissingWebhooks(): array
    {
        $events = [
            'contact.created',
            'contact.changed',
            'contact.deleted',
            'invoice.created',
            'invoice.changed',
            'invoice.deleted',
            'invoice.status.changed',
            'payment.changed',
        ];

        $results = [];

        foreach ($events as $eventType) {
            try {
                if ($this->isEventRegistered($eventType)) {
                    $results[$eventType] = ['status' => 'already_registered'];
                    Log::info("Webhook für Event '{$eventType}' bereits registriert");
                } else {
                    $result = $this->registerWebhook($eventType);
                    $results[$eventType] = $result;
                    Log::info("Webhook für Event '{$eventType}' erfolgreich registriert", (array) $result);
                }
            } catch (\Exception $e) {
                $results[$eventType] = ['error' => $e->getMessage()];
                Log::error("Fehler beim Registrieren des Webhooks für Event '{$eventType}': " . $e->getMessage());
            }
        }

        return $results;
    }
}
