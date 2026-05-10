<?php

namespace App\Command;

use App\Entity\Reservation;
use App\Repository\LogementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-past-reservation',
    description: 'Crée une réservation passée pour tester les avis',
)]
class CreatePastReservationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private LogementRepository $logementRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Trouver un utilisateur simple et un logement
        $user = $this->userRepository->findOneBy(['email' => 'user@test.com']);
        $logements = $this->logementRepository->findAll();

        if (!$user) {
            $io->error('Utilisateur user@test.com non trouvé. Exécutez d\'abord app:create-test-data');
            return Command::FAILURE;
        }

        if (empty($logements)) {
            $io->error('Aucun logement trouvé. Exécutez d\'abord app:create-test-data');
            return Command::FAILURE;
        }

        $logement = $logements[0];

        // Créer une réservation passée (il y a 1 mois, durée 3 jours)
        $reservation = new Reservation();
        $reservation->setLogement($logement);
        $reservation->setLocataire($user);
        
        // IMPORTANT: Utiliser l'année actuelle moins 1 mois
        $dateDebut = new \DateTime('-1 month');
        $dateFin = (clone $dateDebut)->modify('+3 days');
        
        $io->note([
            'Date actuelle: ' . (new \DateTime())->format('d/m/Y H:i:s'),
            'Date début: ' . $dateDebut->format('d/m/Y H:i:s'),
            'Date fin: ' . $dateFin->format('d/m/Y H:i:s'),
        ]);
        
        $reservation->setDateDebut($dateDebut);
        $reservation->setDateFin($dateFin);
        $reservation->setNombrePersonnes(2);
        $reservation->setStatut('confirmee');
        
        $nombreNuits = $reservation->getNombreNuits();
        $montantTotal = $nombreNuits * floatval($logement->getPrixParNuit());
        $reservation->setMontantTotal((string)$montantTotal);
        $reservation->setDateCreation(new \DateTime('-1 month'));

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $io->success([
            'Réservation passée créée avec succès !',
            sprintf('ID: %d', $reservation->getId()),
            sprintf('Logement: %s', $logement->getTitre()),
            sprintf('Dates: %s au %s', $dateDebut->format('d/m/Y'), $dateFin->format('d/m/Y')),
            sprintf('Statut: %s', $reservation->getStatut()),
            sprintf('Terminée: %s', $reservation->isTerminee() ? 'Oui' : 'Non'),
            '',
            'Vous pouvez maintenant laisser un avis sur cette réservation !',
            sprintf('URL: /reservation/%d', $reservation->getId()),
        ]);

        return Command::SUCCESS;
    }
}
