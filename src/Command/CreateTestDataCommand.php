<?php

namespace App\Command;

use App\Entity\Logement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Creates test users and logements for development',
)]
class CreateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create test users
        $io->section('Creating test users...');
        
        $testUser = $this->createUser('user@test.com', 'password', 'Jean', 'Dupont', ['ROLE_USER']);
        $testHost = $this->createUser('host@test.com', 'password', 'Marie', 'Martin', ['ROLE_HOST']);
        $testAdmin = $this->createUser('admin@test.com', 'password', 'Admin', 'System', ['ROLE_ADMIN']);
        
        $io->success('Created 3 test users');
        $io->text([
            'User (Client): user@test.com / password',
            'Host (Propriétaire): host@test.com / password',
            'Admin: admin@test.com / password',
        ]);

        // Create test logements
        $io->section('Creating test logements...');
        
        $logements = [
            [
                'titre' => 'Appartement Parisien Charmant',
                'description' => 'Magnifique appartement au cœur de Paris avec vue imprenable sur la Tour Eiffel. Idéal pour un séjour romantique ou des vacances en famille.',
                'adresse' => '15 Rue de la Paix, 75002 Paris',
                'prixParNuit' => '150.00',
                'nombreChambres' => 2,
                'capacite' => 4,
                'type' => 'Appartement',
                'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop',
            ],
            [
                'titre' => 'Villa Moderne avec Piscine',
                'description' => 'Superbe villa contemporaine avec piscine privée, jardin paysager et vue panoramique. Parfait pour des vacances de luxe en famille.',
                'adresse' => '42 Avenue des Mimosas, 06400 Cannes',
                'prixParNuit' => '350.00',
                'nombreChambres' => 4,
                'capacite' => 8,
                'type' => 'Villa',
                'image' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&h=600&fit=crop',
            ],
            [
                'titre' => 'Studio Cosy Centre-Ville',
                'description' => 'Studio moderne et fonctionnel en plein centre-ville. Proche de toutes commodités, transports et attractions touristiques.',
                'adresse' => '8 Rue du Commerce, 69002 Lyon',
                'prixParNuit' => '75.00',
                'nombreChambres' => 1,
                'capacite' => 2,
                'type' => 'Studio',
                'image' => 'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800&h=600&fit=crop',
            ],
            [
                'titre' => 'Maison de Campagne Authentique',
                'description' => 'Charmante maison de campagne rénovée avec goût. Grand jardin, cheminée et calme absolu. Idéal pour se ressourcer.',
                'adresse' => '23 Chemin des Vignes, 84220 Gordes',
                'prixParNuit' => '180.00',
                'nombreChambres' => 3,
                'capacite' => 6,
                'type' => 'Maison',
                'image' => 'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800&h=600&fit=crop',
            ],
            [
                'titre' => 'Loft Industriel Moderne',
                'description' => 'Loft spacieux au style industriel dans un ancien entrepôt rénové. Hauteur sous plafond exceptionnelle et grande luminosité.',
                'adresse' => '56 Rue des Artistes, 13001 Marseille',
                'prixParNuit' => '120.00',
                'nombreChambres' => 2,
                'capacite' => 4,
                'type' => 'Loft',
                'image' => 'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&h=600&fit=crop',
            ],
        ];

        foreach ($logements as $data) {
            $logement = new Logement();
            $logement->setTitre($data['titre']);
            $logement->setDescription($data['description']);
            $logement->setAdresse($data['adresse']);
            $logement->setPrixParNuit($data['prixParNuit']);
            $logement->setNombreChambres($data['nombreChambres']);
            $logement->setCapacite($data['capacite']);
            $logement->setType($data['type']);
            $logement->setImage($data['image']);
            $logement->setDisponible(true);
            $logement->setProprietaire($testHost);

            $this->entityManager->persist($logement);
        }

        $this->entityManager->flush();

        $io->success('Created ' . count($logements) . ' test logements');
        $io->text('You can now browse and book these properties!');

        return Command::SUCCESS;
    }

    private function createUser(string $email, string $password, string $prenom, string $nom, array $roles = ['ROLE_USER']): User
    {
        // Check if user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $existingUser;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
