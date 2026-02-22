<?php

namespace App\Command;

use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-pusher',
    description: 'Test Pusher notification system',
)]
class TestPusherCommand extends Command
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Pusher Notification System');

        $io->section('Environment Variables');
        $io->table(
            ['Variable', 'Value'],
            [
                ['PUSHER_APP_ID', $_ENV['PUSHER_APP_ID'] ?? 'NOT SET'],
                ['PUSHER_APP_KEY', $_ENV['PUSHER_APP_KEY'] ?? 'NOT SET'],
                ['PUSHER_APP_SECRET', isset($_ENV['PUSHER_APP_SECRET']) ? str_repeat('*', 10) : 'NOT SET'],
                ['PUSHER_APP_CLUSTER', $_ENV['PUSHER_APP_CLUSTER'] ?? 'NOT SET'],
            ]
        );

        if (empty($_ENV['PUSHER_APP_ID']) || empty($_ENV['PUSHER_APP_KEY']) || empty($_ENV['PUSHER_APP_SECRET'])) {
            $io->error('Pusher credentials not configured in .env file!');
            $io->note('Please add your Pusher credentials to .env file');
            return Command::FAILURE;
        }

        $io->section('Sending Test Notification');
        
        try {
            $this->notificationService->sendReservationNotification(
                999,
                'test@example.com',
                'Test Logement'
            );
            
            $io->success('Test notification sent successfully!');
            
            $io->section('Next Steps');
            $io->listing([
                'Open admin dashboard: http://localhost:8000/admin',
                'Open browser console (F12)',
                'Check for Pusher connection logs',
                'You should see the test notification appear',
                'If not, check Pusher Debug Console at https://dashboard.pusher.com/',
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send notification: ' . $e->getMessage());
            
            $io->section('Troubleshooting');
            $io->listing([
                'Verify credentials are correct in .env',
                'Check cluster is correct (you have: ' . ($_ENV['PUSHER_APP_CLUSTER'] ?? 'NOT SET') . ')',
                'Verify Pusher app is active in dashboard',
                'Check network/firewall settings',
                'Check var/log/dev.log for errors',
            ]);
            
            return Command::FAILURE;
        }
    }
}
