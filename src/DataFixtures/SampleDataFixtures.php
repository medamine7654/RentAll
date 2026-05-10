<?php

namespace App\DataFixtures;

use App\Entity\Service;
use App\Entity\Tool;
use App\Entity\Logement;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SampleDataFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get the admin user (created by AppFixtures)
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        
        if (!$adminUser) {
            throw new \Exception('Admin user not found. Please run AppFixtures first.');
        }

        // Get all categories for reference
        $categories = [];
        foreach ($manager->getRepository(Category::class)->findAll() as $category) {
            $categories[$category->getName()] = $category;
        }

        // Create Services
        $this->createServices($manager, $adminUser, $categories);
        
        // Create Tools
        $this->createTools($manager, $adminUser, $categories);

        // Create Logements
        $this->createLogements($manager, $adminUser);

        $manager->flush();
    }

    private function createServices(ObjectManager $manager, User $host, array $categories): void
    {
        $services = [
            [
                'name' => 'Emergency Plumbing Repair',
                'category' => 'Plumbing',
                'description' => 'Professional plumber available 24/7 for urgent repairs. Specializes in leak fixes, pipe bursts, and drain unclogging.',
                'basePrice' => '50.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Bathroom Renovation',
                'category' => 'Plumbing',
                'description' => 'Complete bathroom plumbing installation and renovation. Includes sink, toilet, shower, and bathtub installation.',
                'basePrice' => '120.00',
                'durationMinutes' => 480,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Home Electrical Wiring',
                'category' => 'Electrical',
                'description' => 'Licensed electrician for home wiring, circuit installation, and electrical panel upgrades. Safety certified.',
                'basePrice' => '60.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Solar Panel Installation',
                'category' => 'Electrical',
                'description' => 'Professional solar panel installation and maintenance. Reduce your electricity bills with renewable energy.',
                'basePrice' => '200.00',
                'durationMinutes' => 480,
                'location' => 'Ben Arous',
            ],
            [
                'name' => 'Garden Design & Maintenance',
                'category' => 'Gardening',
                'description' => 'Complete garden design, planting, lawn care, and seasonal maintenance. Transform your outdoor space.',
                'basePrice' => '40.00',
                'durationMinutes' => 60,
                'location' => 'La Marsa',
            ],
            [
                'name' => 'Tree Trimming & Removal',
                'category' => 'Gardening',
                'description' => 'Professional tree care including trimming, pruning, and safe removal. Certified arborist.',
                'basePrice' => '80.00',
                'durationMinutes' => 240,
                'location' => 'Carthage',
            ],
            [
                'name' => 'Deep House Cleaning',
                'category' => 'Cleaning',
                'description' => 'Thorough deep cleaning service for homes. Includes kitchen, bathrooms, floors, and windows.',
                'basePrice' => '35.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Move-Out Cleaning',
                'category' => 'Cleaning',
                'description' => 'Specialized cleaning for moving out. Ensure you get your deposit back with our thorough service.',
                'basePrice' => '150.00',
                'durationMinutes' => 240,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Interior House Painting',
                'category' => 'Painting',
                'description' => 'Professional interior painting service. Walls, ceilings, trim. Quality finish guaranteed.',
                'basePrice' => '45.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Exterior House Painting',
                'category' => 'Painting',
                'description' => 'Exterior painting and weatherproofing. Protect your home and improve curb appeal.',
                'basePrice' => '55.00',
                'durationMinutes' => 60,
                'location' => 'Ben Arous',
            ],
            [
                'name' => 'Residential Moving Service',
                'category' => 'Moving',
                'description' => 'Full-service moving with professional movers. Packing, loading, transport, and unloading.',
                'basePrice' => '250.00',
                'durationMinutes' => 480,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Furniture Assembly & Delivery',
                'category' => 'Moving',
                'description' => 'Furniture assembly and delivery service. We handle IKEA and all flat-pack furniture.',
                'basePrice' => '30.00',
                'durationMinutes' => 60,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Math & Science Tutoring',
                'category' => 'Tutoring',
                'description' => 'Experienced tutor for high school and university math and science. Improve your grades.',
                'basePrice' => '25.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Language Lessons',
                'category' => 'Tutoring',
                'description' => 'Native speaker offering English, French, and Arabic lessons. All levels welcome.',
                'basePrice' => '20.00',
                'durationMinutes' => 60,
                'location' => 'La Marsa',
            ],
            [
                'name' => 'Computer Repair & Setup',
                'category' => 'IT Support',
                'description' => 'Expert computer repair, virus removal, software installation, and network setup.',
                'basePrice' => '40.00',
                'durationMinutes' => 60,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Website Development',
                'category' => 'IT Support',
                'description' => 'Professional website development for small businesses. Responsive design and SEO optimized.',
                'basePrice' => '500.00',
                'durationMinutes' => 2400,
                'location' => 'Ariana',
            ],
        ];

        foreach ($services as $data) {
            $service = new Service();
            $service->setHost($host)
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setBasePrice($data['basePrice'])
                ->setDurationMinutes($data['durationMinutes'])
                ->setLocation($data['location'])
                ->setIsActive(true)
                ->setCategory($categories[$data['category']] ?? null);
            
            $manager->persist($service);
        }
    }

    private function createTools(ObjectManager $manager, User $host, array $categories): void
    {
        $tools = [
            [
                'name' => 'Heavy Duty Power Drill',
                'category' => 'Power Tools',
                'description' => 'Professional 18V cordless drill with 2 batteries. Perfect for drilling and driving screws.',
                'pricePerDay' => '15.00',
                'stockQuantity' => 2,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Circular Saw',
                'category' => 'Power Tools',
                'description' => '7-inch circular saw for cutting wood and boards. Includes safety guard and extra blade.',
                'pricePerDay' => '20.00',
                'stockQuantity' => 1,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Electric Sander',
                'category' => 'Power Tools',
                'description' => 'Orbital sander for smooth finishing. Great for furniture refinishing and woodworking.',
                'pricePerDay' => '12.00',
                'stockQuantity' => 2,
                'location' => 'Ben Arous',
            ],
            [
                'name' => 'Angle Grinder',
                'category' => 'Power Tools',
                'description' => 'Powerful angle grinder for cutting metal and grinding. Includes safety equipment.',
                'pricePerDay' => '18.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Hammer & Nail Set',
                'category' => 'Hand Tools',
                'description' => 'Complete hammer set with various nails and screws. Essential for basic repairs.',
                'pricePerDay' => '5.00',
                'stockQuantity' => 3,
                'location' => 'La Marsa',
            ],
            [
                'name' => 'Screwdriver Set',
                'category' => 'Hand Tools',
                'description' => 'Professional 20-piece screwdriver set. Phillips, flathead, and precision sizes included.',
                'pricePerDay' => '3.00',
                'stockQuantity' => 4,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Wrench Set',
                'category' => 'Hand Tools',
                'description' => 'Complete wrench set including adjustable and socket wrenches. Metric and imperial.',
                'pricePerDay' => '8.00',
                'stockQuantity' => 2,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Pliers & Wire Cutters',
                'category' => 'Hand Tools',
                'description' => 'Professional pliers set with wire cutters. Perfect for electrical and plumbing work.',
                'pricePerDay' => '4.00',
                'stockQuantity' => 3,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Lawn Mower',
                'category' => 'Garden Tools',
                'description' => 'Gas-powered lawn mower with adjustable cutting height. Maintain a beautiful lawn.',
                'pricePerDay' => '25.00',
                'stockQuantity' => 1,
                'location' => 'Carthage',
            ],
            [
                'name' => 'Hedge Trimmer',
                'category' => 'Garden Tools',
                'description' => 'Electric hedge trimmer for shaping bushes and hedges. Lightweight and easy to use.',
                'pricePerDay' => '15.00',
                'stockQuantity' => 2,
                'location' => 'La Marsa',
            ],
            [
                'name' => 'Garden Hose & Sprinkler',
                'category' => 'Garden Tools',
                'description' => '50-meter garden hose with adjustable sprinkler. Perfect for watering large areas.',
                'pricePerDay' => '8.00',
                'stockQuantity' => 3,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Leaf Blower',
                'category' => 'Garden Tools',
                'description' => 'Powerful electric leaf blower for quick yard cleanup. Lightweight and efficient.',
                'pricePerDay' => '12.00',
                'stockQuantity' => 2,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Extension Ladder',
                'category' => 'Ladders',
                'description' => '6-meter aluminum extension ladder. Safe and stable for high-reach work.',
                'pricePerDay' => '20.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Step Ladder',
                'category' => 'Ladders',
                'description' => '2-meter folding step ladder. Perfect for indoor tasks and light outdoor work.',
                'pricePerDay' => '10.00',
                'stockQuantity' => 3,
                'location' => 'Ben Arous',
            ],
            [
                'name' => 'Scaffolding Set',
                'category' => 'Ladders',
                'description' => 'Portable scaffolding system for large projects. Safe working platform for extended tasks.',
                'pricePerDay' => '35.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Vacuum Cleaner',
                'category' => 'Cleaning Equipment',
                'description' => 'Powerful vacuum cleaner with HEPA filter. Great for deep cleaning carpets and floors.',
                'pricePerDay' => '10.00',
                'stockQuantity' => 2,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Carpet Cleaner',
                'category' => 'Cleaning Equipment',
                'description' => 'Professional carpet cleaning machine. Remove stains and refresh your carpets.',
                'pricePerDay' => '18.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Pressure Washer',
                'category' => 'Cleaning Equipment',
                'description' => 'High-pressure washer for outdoor cleaning. Clean driveways, walls, and vehicles.',
                'pricePerDay' => '22.00',
                'stockQuantity' => 1,
                'location' => 'La Marsa',
            ],
            [
                'name' => 'Floor Polisher',
                'category' => 'Cleaning Equipment',
                'description' => 'Electric floor polisher for marble and tile floors. Restore shine to your floors.',
                'pricePerDay' => '15.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Laser Level',
                'category' => 'Measuring Tools',
                'description' => 'Professional laser level for accurate measurements. Perfect for hanging pictures and shelves.',
                'pricePerDay' => '8.00',
                'stockQuantity' => 2,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Measuring Tape Set',
                'category' => 'Measuring Tools',
                'description' => 'Professional measuring tape set with various lengths. Essential for any project.',
                'pricePerDay' => '3.00',
                'stockQuantity' => 5,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Stud Finder',
                'category' => 'Measuring Tools',
                'description' => 'Electronic stud finder for locating wall studs. Safely hang heavy items.',
                'pricePerDay' => '5.00',
                'stockQuantity' => 2,
                'location' => 'Ben Arous',
            ],
            [
                'name' => 'BBQ Grill',
                'category' => 'Outdoor Equipment',
                'description' => 'Large gas BBQ grill perfect for parties and gatherings. Includes propane tank.',
                'pricePerDay' => '30.00',
                'stockQuantity' => 1,
                'location' => 'Carthage',
            ],
            [
                'name' => 'Camping Tent',
                'category' => 'Outdoor Equipment',
                'description' => '6-person camping tent with rain cover. Perfect for weekend getaways.',
                'pricePerDay' => '20.00',
                'stockQuantity' => 2,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Folding Tables & Chairs',
                'category' => 'Party Equipment',
                'description' => 'Set of 4 folding tables and 20 chairs. Perfect for events and parties.',
                'pricePerDay' => '40.00',
                'stockQuantity' => 2,
                'location' => 'Ariana',
            ],
            [
                'name' => 'Sound System',
                'category' => 'Party Equipment',
                'description' => 'Professional PA system with microphone. Great for parties and presentations.',
                'pricePerDay' => '35.00',
                'stockQuantity' => 1,
                'location' => 'Tunis',
            ],
            [
                'name' => 'Projector & Screen',
                'category' => 'Party Equipment',
                'description' => 'HD projector with 100-inch screen. Perfect for movie nights and presentations.',
                'pricePerDay' => '25.00',
                'stockQuantity' => 1,
                'location' => 'La Marsa',
            ],
        ];

        foreach ($tools as $data) {
            $tool = new Tool();
            $tool->setHost($host)
                ->setName($data['name'])
                ->setDescription($data['description'])
                ->setPricePerDay($data['pricePerDay'])
                ->setStockQuantity($data['stockQuantity'])
                ->setLocation($data['location'])
                ->setIsActive(true)
                ->setCategory($categories[$data['category']] ?? null);
            
            $manager->persist($tool);
        }
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            CategoryFixtures::class,
        ];
    }

    private function createLogements(ObjectManager $manager, User $host): void
    {
        $logements = [
            [
                'titre' => 'Cozy Studio in Downtown Tunis',
                'type' => 'Apartment',
                'description' => 'Modern studio apartment in the heart of Tunis. Perfect for solo travelers or couples. Walking distance to restaurants, cafes, and public transport.',
                'adresse' => '15 Avenue Habib Bourguiba',
                'city' => 'Tunis',
                'country' => 'Tunisia',
                'prixParNuit' => '45.00',
                'nombreChambres' => 1,
                'numberOfBeds' => 1,
                'numberOfBathrooms' => 1,
                'maxGuests' => 2,
                'squareMeters' => 35,
                'capacite' => 2,
            ],
            [
                'titre' => 'Luxury Villa with Sea View',
                'type' => 'Villa',
                'description' => 'Stunning 4-bedroom villa overlooking the Mediterranean Sea. Private pool, garden, and direct beach access. Perfect for families or groups.',
                'adresse' => '42 Route de la Marsa',
                'city' => 'La Marsa',
                'country' => 'Tunisia',
                'prixParNuit' => '250.00',
                'nombreChambres' => 4,
                'numberOfBeds' => 6,
                'numberOfBathrooms' => 3,
                'maxGuests' => 8,
                'squareMeters' => 280,
                'capacite' => 8,
            ],
            [
                'titre' => 'Charming Traditional House in Medina',
                'type' => 'House',
                'description' => 'Authentic Tunisian house in the historic Medina. Beautifully restored with traditional tiles and architecture. Rooftop terrace with city views.',
                'adresse' => '8 Rue du Pacha',
                'city' => 'Tunis',
                'country' => 'Tunisia',
                'prixParNuit' => '80.00',
                'nombreChambres' => 3,
                'numberOfBeds' => 4,
                'numberOfBathrooms' => 2,
                'maxGuests' => 6,
                'squareMeters' => 150,
                'capacite' => 6,
            ],
            [
                'titre' => 'Modern 2BR Apartment in Ariana',
                'type' => 'Apartment',
                'description' => 'Spacious 2-bedroom apartment with modern amenities. Fully equipped kitchen, balcony, and parking. Close to shopping centers and schools.',
                'adresse' => '23 Avenue de la République',
                'city' => 'Ariana',
                'country' => 'Tunisia',
                'prixParNuit' => '60.00',
                'nombreChambres' => 2,
                'numberOfBeds' => 3,
                'numberOfBathrooms' => 1,
                'maxGuests' => 4,
                'squareMeters' => 85,
                'capacite' => 4,
            ],
            [
                'titre' => 'Beachfront Apartment in Hammamet',
                'type' => 'Apartment',
                'description' => 'Beautiful apartment right on the beach. Wake up to stunning sea views. Swimming pool, tennis court, and restaurant on-site.',
                'adresse' => '56 Avenue de la Plage',
                'city' => 'Hammamet',
                'country' => 'Tunisia',
                'prixParNuit' => '95.00',
                'nombreChambres' => 2,
                'numberOfBeds' => 3,
                'numberOfBathrooms' => 2,
                'maxGuests' => 5,
                'squareMeters' => 95,
                'capacite' => 5,
            ],
            [
                'titre' => 'Countryside Villa in Nabeul',
                'type' => 'Villa',
                'description' => 'Peaceful villa surrounded by olive groves. Large garden, BBQ area, and outdoor dining. Perfect for a relaxing getaway.',
                'adresse' => 'Route de Menzel Temime Km 5',
                'city' => 'Nabeul',
                'country' => 'Tunisia',
                'prixParNuit' => '120.00',
                'nombreChambres' => 3,
                'numberOfBeds' => 5,
                'numberOfBathrooms' => 2,
                'maxGuests' => 7,
                'squareMeters' => 200,
                'capacite' => 7,
            ],
            [
                'titre' => 'Budget-Friendly Room in Carthage',
                'type' => 'Room',
                'description' => 'Comfortable private room in a shared house. Great for budget travelers. Close to ancient Carthage ruins and museums.',
                'adresse' => '12 Rue Hannibal',
                'city' => 'Carthage',
                'country' => 'Tunisia',
                'prixParNuit' => '25.00',
                'nombreChambres' => 1,
                'numberOfBeds' => 1,
                'numberOfBathrooms' => 1,
                'maxGuests' => 1,
                'squareMeters' => 18,
                'capacite' => 1,
            ],
            [
                'titre' => 'Penthouse with Panoramic Views',
                'type' => 'Apartment',
                'description' => 'Luxurious penthouse on the top floor. 360-degree views of the city and sea. Modern design, high-end appliances, and private terrace.',
                'adresse' => '88 Avenue Mohamed V',
                'city' => 'Tunis',
                'country' => 'Tunisia',
                'prixParNuit' => '180.00',
                'nombreChambres' => 3,
                'numberOfBeds' => 4,
                'numberOfBathrooms' => 2,
                'maxGuests' => 6,
                'squareMeters' => 160,
                'capacite' => 6,
            ],
            [
                'titre' => 'Family House in Ben Arous',
                'type' => 'House',
                'description' => 'Spacious family home with garden and playground. Safe neighborhood, close to schools and parks. Perfect for long-term stays.',
                'adresse' => '34 Rue de la Liberté',
                'city' => 'Ben Arous',
                'country' => 'Tunisia',
                'prixParNuit' => '70.00',
                'nombreChambres' => 3,
                'numberOfBeds' => 5,
                'numberOfBathrooms' => 2,
                'maxGuests' => 6,
                'squareMeters' => 140,
                'capacite' => 6,
            ],
            [
                'titre' => 'Artistic Loft in Sidi Bou Said',
                'type' => 'Apartment',
                'description' => 'Unique loft in the famous blue and white village. High ceilings, artistic decor, and stunning views. Walk to cafes and art galleries.',
                'adresse' => '7 Rue Sidi Chabaane',
                'city' => 'Sidi Bou Said',
                'country' => 'Tunisia',
                'prixParNuit' => '110.00',
                'nombreChambres' => 2,
                'numberOfBeds' => 2,
                'numberOfBathrooms' => 1,
                'maxGuests' => 4,
                'squareMeters' => 75,
                'capacite' => 4,
            ],
        ];

        foreach ($logements as $data) {
            $logement = new Logement();
            $logement->setProprietaire($host)
                ->setTitre($data['titre'])
                ->setDescription($data['description'])
                ->setAdresse($data['adresse'])
                ->setCity($data['city'])
                ->setCountry($data['country'])
                ->setPrixParNuit($data['prixParNuit'])
                ->setNombreChambres($data['nombreChambres'])
                ->setNumberOfBeds($data['numberOfBeds'])
                ->setNumberOfBathrooms($data['numberOfBathrooms'])
                ->setMaxGuests($data['maxGuests'])
                ->setSquareMeters($data['squareMeters'])
                ->setCapacite($data['capacite'])
                ->setType($data['type'])
                ->setDisponible(true)
                ->setIsActive(true);
            
            $manager->persist($logement);
        }
    }
}
