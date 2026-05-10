<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create or update an admin user',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    )
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Create Admin User');

        $email = $io->ask('Email', 'admin@test.com');
        $password = $io->askHidden('Password (leave empty for "password")', null, function ($value) {
            return $value ?: 'password';
        });

        // Check if user exists
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user) {
            $io->warning('User already exists. Updating...');
        } else {
            $user = new User();
            $user->setEmail($email);
            $io->success('Creating new user...');
        }

        // Set password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Set roles
        $user->setRoles(['ROLE_ADMIN', 'ROLE_HOST', 'ROLE_USER']);
        $user->setIsVerified(true);

        // Set default values if new user
        if (!$user->getId()) {
            $user->setNom('Admin');
            $user->setPrenom('User');
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            'Admin user created/updated successfully!',
            '',
            'Email: ' . $email,
            'Password: ' . $password,
            'Roles: ROLE_ADMIN, ROLE_HOST, ROLE_USER',
            '',
            'You can now login at: http://localhost:8000/login',
        ]);

        return Command::SUCCESS;
    }
}
