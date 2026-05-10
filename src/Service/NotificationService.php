<?php

namespace App\Service;

use Pusher\Pusher;
use Psr\Log\LoggerInterface;

class NotificationService
{
    private ?Pusher $pusher = null;
    private LoggerInterface $logger;
    private bool $enabled;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        
        $appId = $_ENV['PUSHER_APP_ID'] ?? null;
        $key = $_ENV['PUSHER_APP_KEY'] ?? null;
        $secret = $_ENV['PUSHER_APP_SECRET'] ?? null;
        $cluster = $_ENV['PUSHER_APP_CLUSTER'] ?? 'eu';
        
        $this->enabled = !empty($appId) && !empty($key) && !empty($secret);
        
        if ($this->enabled) {
            try {
                $this->pusher = new Pusher($key, $secret, $appId, [
                    'cluster' => $cluster,
                    'useTLS' => true
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Pusher initialization failed: ' . $e->getMessage());
                $this->enabled = false;
            }
        }
    }

    public function sendReservationNotification(int $id, string $locataireEmail, string $logementTitre): void
    {
        if (!$this->enabled || !$this->pusher) {
            $this->logger->info('Pusher not enabled, skipping notification');
            return;
        }

        try {
            $this->pusher->trigger('admin-channel', 'new-reservation', [
                'type' => 'reservation',
                'id' => $id,
                'title' => 'Nouvelle réservation',
                'message' => sprintf('%s a réservé %s', $locataireEmail, $logementTitre),
                'locataire' => $locataireEmail,
                'logement' => $logementTitre,
                'createdAt' => date('Y-m-d H:i:s')
            ]);
            
            $this->logger->info(sprintf('Reservation notification sent for ID %d', $id));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send reservation notification: ' . $e->getMessage());
        }
    }

    public function sendCovoiturageNotification(int $id, string $conducteurEmail, string $depart, string $destination): void
    {
        if (!$this->enabled || !$this->pusher) {
            $this->logger->info('Pusher not enabled, skipping notification');
            return;
        }

        try {
            $this->pusher->trigger('admin-channel', 'new-covoiturage', [
                'type' => 'covoiturage',
                'id' => $id,
                'title' => 'Nouveau covoiturage',
                'message' => sprintf('%s propose %s → %s', $conducteurEmail, $depart, $destination),
                'conducteur' => $conducteurEmail,
                'depart' => $depart,
                'destination' => $destination,
                'createdAt' => date('Y-m-d H:i:s')
            ]);
            
            $this->logger->info(sprintf('Covoiturage notification sent for ID %d', $id));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send covoiturage notification: ' . $e->getMessage());
        }
    }

    public function sendAccountDeactivatedNotification(int $userId, string $userEmail): void
    {
        if (!$this->enabled || !$this->pusher) {
            $this->logger->info('Pusher not enabled, skipping notification');
            return;
        }

        try {
            $this->pusher->trigger('admin-channel', 'account-deactivated', [
                'type' => 'account',
                'id' => $userId,
                'title' => 'Compte desactive',
                'message' => sprintf('Le compte %s vient d etre desactive par son proprietaire.', $userEmail),
                'userId' => $userId,
                'userEmail' => $userEmail,
                'createdAt' => date('Y-m-d H:i:s')
            ]);

            $this->logger->info(sprintf('Account deactivation notification sent for user ID %d', $userId));
        } catch (\Exception $e) {
            $this->logger->error('Failed to send account deactivation notification: ' . $e->getMessage());
        }
    }
}
