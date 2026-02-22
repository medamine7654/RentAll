<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:clean-reservations',
    description: 'Supprime toutes les réservations de test',
)]
class CleanReservationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReservationRepository $reservationRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $reservations = $this->reservationRepository->findAll();
        $count = count($reservations);

        foreach ($reservations as $reservation) {
            $this->entityManager->remove($reservation);
        }

        $this->entityManager->flush();

        $io->success(sprintf('%d réservation(s) supprimée(s)', $count));

        return Command::SUCCESS;
    }
}
