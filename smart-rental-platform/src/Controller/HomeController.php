<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $filters = [
            'category' => $request->query->get('category'),
            'location' => $request->query->get('location'),
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'guests' => $request->query->get('guests'),
        ];

        // Mock data for demo - replace with database queries later
        $logements = $this->getMockLogements($filters);
        $hosts = $this->getMockHosts();

        return $this->render('home/index.html.twig', [
            'logements' => $logements,
            'hosts' => $hosts,
            'filters' => $filters,
            'isLoading' => false,
        ]);
    }

    private function getMockLogements(array $filters): array
    {
        $allLogements = [
            [
                'id' => 1,
                'hostId' => 1,
                'title' => 'Charming Parisian Apartment',
                'description' => 'Beautiful apartment in the heart of Paris with stunning views',
                'category' => 'apartment',
                'pricePerNight' => 150,
                'location' => 'Le Marais, Paris',
                'city' => 'Paris',
                'country' => 'France',
                'images' => [
                    'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop',
                    'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800&h=600&fit=crop',
                ],
                'amenities' => ['WiFi', 'Kitchen', 'Washer', 'Air conditioning'],
                'maxGuests' => 4,
                'bedrooms' => 2,
                'bathrooms' => 1,
                'status' => 'active',
                'rating' => 4.8,
                'reviewCount' => 124,
            ],
            [
                'id' => 2,
                'hostId' => 1,
                'title' => 'Modern Loft in Berlin',
                'description' => 'Spacious industrial loft in trendy Kreuzberg',
                'category' => 'loft',
                'pricePerNight' => 95,
                'location' => 'Kreuzberg, Berlin',
                'city' => 'Berlin',
                'country' => 'Germany',
                'images' => [
                    'https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&h=600&fit=crop',
                ],
                'amenities' => ['WiFi', 'Kitchen', 'Heating', 'Workspace'],
                'maxGuests' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'status' => 'active',
                'rating' => 4.6,
                'reviewCount' => 89,
            ],
            [
                'id' => 3,
                'hostId' => 2,
                'title' => 'Cozy Cabin in the Alps',
                'description' => 'Escape to nature in this traditional alpine cabin',
                'category' => 'cabin',
                'pricePerNight' => 180,
                'location' => 'Chamonix',
                'city' => 'Chamonix',
                'country' => 'France',
                'images' => [
                    'https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?w=800&h=600&fit=crop',
                ],
                'amenities' => ['Fireplace', 'Kitchen', 'Mountain view', 'Hot tub'],
                'maxGuests' => 6,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'status' => 'active',
                'rating' => 4.9,
                'reviewCount' => 56,
            ],
            [
                'id' => 4,
                'hostId' => 2,
                'title' => 'Luxury Villa with Pool',
                'description' => 'Stunning Mediterranean villa with private pool',
                'category' => 'villa',
                'pricePerNight' => 350,
                'location' => 'Costa Brava',
                'city' => 'Barcelona',
                'country' => 'Spain',
                'images' => [
                    'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&h=600&fit=crop',
                ],
                'amenities' => ['Pool', 'Garden', 'BBQ', 'Beach access'],
                'maxGuests' => 8,
                'bedrooms' => 4,
                'bathrooms' => 3,
                'status' => 'active',
                'rating' => 4.7,
                'reviewCount' => 34,
            ],
            [
                'id' => 5,
                'hostId' => 1,
                'title' => 'Minimalist Studio in Tokyo',
                'description' => 'Compact but efficient studio in Shibuya',
                'category' => 'studio',
                'pricePerNight' => 85,
                'location' => 'Shibuya',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'images' => [
                    'https://images.unsplash.com/photo-1540518614846-7eded433c457?w=800&h=600&fit=crop',
                ],
                'amenities' => ['WiFi', 'Air conditioning', 'Washer'],
                'maxGuests' => 2,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'status' => 'active',
                'rating' => 4.5,
                'reviewCount' => 201,
            ],
            [
                'id' => 6,
                'hostId' => 2,
                'title' => 'Seaside House in Lisbon',
                'description' => 'Bright house near the beach with traditional Portuguese tiles',
                'category' => 'house',
                'pricePerNight' => 120,
                'location' => 'Cascais',
                'city' => 'Lisbon',
                'country' => 'Portugal',
                'images' => [
                    'https://images.unsplash.com/photo-1580587771525-78b9dba3b914?w=800&h=600&fit=crop',
                ],
                'amenities' => ['WiFi', 'Garden', 'Beach access', 'Parking'],
                'maxGuests' => 5,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'status' => 'active',
                'rating' => 4.4,
                'reviewCount' => 67,
            ],
        ];

        // Apply filters
        return array_values(array_filter($allLogements, function($logement) use ($filters) {
            if ($filters['category'] && $logement['category'] !== $filters['category']) {
                return false;
            }
            if ($filters['location']) {
                $search = strtolower($filters['location']);
                $matchesLocation = 
                    str_contains(strtolower($logement['location']), $search) ||
                    str_contains(strtolower($logement['city']), $search) ||
                    str_contains(strtolower($logement['country']), $search) ||
                    str_contains(strtolower($logement['title']), $search);
                if (!$matchesLocation) return false;
            }
            if ($filters['minPrice'] && $logement['pricePerNight'] < $filters['minPrice']) {
                return false;
            }
            if ($filters['maxPrice'] && $logement['pricePerNight'] > $filters['maxPrice']) {
                return false;
            }
            if ($filters['guests'] && $logement['maxGuests'] < $filters['guests']) {
                return false;
            }
            return true;
        }));
    }

    private function getMockHosts(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Bob Smith',
                'email' => 'host@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop',
                'role' => 'host',
            ],
            2 => [
                'id' => 2,
                'name' => 'Emma Host',
                'email' => 'host2@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&h=150&fit=crop',
                'role' => 'host',
            ],
        ];
    }
}
