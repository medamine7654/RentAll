<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Service;
use App\Entity\Tool;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_HOST', 'ROLE_USER']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Create Host User
        $host = new User();
        $host->setEmail('host@test.com');
        $host->setFirstName('Host');
        $host->setLastName('User');
        $host->setRoles(['ROLE_HOST', 'ROLE_USER']);
        $host->setPassword($this->passwordHasher->hashPassword($host, 'host123'));
        $manager->persist($host);

        // Create Guest User
        $guest = new User();
        $guest->setEmail('guest@test.com');
        $guest->setFirstName('Guest');
        $guest->setLastName('User');
        $guest->setRoles(['ROLE_USER']);
        $guest->setPassword($this->passwordHasher->hashPassword($guest, 'guest123'));
        $manager->persist($guest);

        // Create Sample Services
        $service1 = new Service();
        $service1->setHost($host);
        $service1->setName('Plumbing Repair');
        $service1->setDescription('Professional plumbing services for your home');
        $service1->setBasePrice('50.00');
        $service1->setDurationMinutes(120);
        $service1->setLocation('Tunis');
        $service1->setIsActive(true);
        $service1->setCategory($this->getReference(CategoryFixtures::PLUMBING_CATEGORY, Category::class));
        $manager->persist($service1);

        $service2 = new Service();
        $service2->setHost($host);
        $service2->setName('Garden Maintenance');
        $service2->setDescription('Keep your garden beautiful all year round');
        $service2->setBasePrice('35.00');
        $service2->setDurationMinutes(180);
        $service2->setLocation('Ariana');
        $service2->setIsActive(false);
        $service2->setCategory($this->getReference(CategoryFixtures::GARDENING_CATEGORY, Category::class));
        $manager->persist($service2);

        // Create Sample Tools
        $tool1 = new Tool();
        $tool1->setHost($host);
        $tool1->setName('Electric Drill');
        $tool1->setDescription('Professional grade electric drill with multiple bits');
        $tool1->setPricePerDay('15.00');
        $tool1->setStockQuantity(3);
        $tool1->setLocation('Tunis');
        $tool1->setIsActive(true);
        $tool1->setCategory($this->getReference(CategoryFixtures::POWER_TOOLS_CATEGORY, Category::class));
        $manager->persist($tool1);

        $tool2 = new Tool();
        $tool2->setHost($host);
        $tool2->setName('Lawn Mower');
        $tool2->setDescription('Gas-powered lawn mower for large gardens');
        $tool2->setPricePerDay('25.00');
        $tool2->setStockQuantity(1);
        $tool2->setLocation('Ariana');
        $tool2->setIsActive(false);
        $tool2->setCategory($this->getReference(CategoryFixtures::GARDEN_TOOLS_CATEGORY, Category::class));
        $manager->persist($tool2);

        $manager->flush();
    }
}
