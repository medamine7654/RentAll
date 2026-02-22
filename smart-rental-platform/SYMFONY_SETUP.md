# Symfony 6.4 Setup Guide for Twig Templates

## Prerequisites

Make sure you have installed:
- PHP 8.1 or higher
- Composer
- Symfony CLI (optional but recommended)

## Quick Start

### Option 1: Using Symfony CLI (Recommended)

If you have an existing Symfony 6.4 project, simply copy the files:

```bash
# Copy templates to your Symfony project
cp -r templates/* /path/to/your/symfony/project/templates/

# Copy controllers
cp -r src/Controller/* /path/to/your/symfony/project/src/Controller/

# Copy Stimulus controllers
cp -r assets/controllers/* /path/to/your/symfony/project/assets/controllers/
```

### Option 2: Create New Symfony 6.4 Project

```bash
# Create new Symfony 6.4 webapp
composer create-project symfony/skeleton:"6.4.*" my-rental-platform
cd my-rental-platform

# Install required packages
composer require webapp
composer require symfony/webpack-encore-bundle
composer require symfony/ux-stimulus-bundle
composer require symfony/security-bundle
composer require orm

# Copy the converted files
cp -r /path/to/smart-rental-platform/templates/* templates/
cp -r /path/to/smart-rental-platform/src/Controller/* src/Controller/
cp -r /path/to/smart-rental-platform/assets/controllers/* assets/controllers/
```

## Required Configuration

### 1. Install Tailwind CSS

```bash
npm install -D tailwindcss @tailwindcss/forms
npx tailwindcss init
```

Create `tailwind.config.js`:
```javascript
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
```

### 2. Configure Webpack Encore

Update `webpack.config.js`:
```javascript
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .enablePostCssLoader()
    .enableStimulusBridge('./assets/controllers.json')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
;
```

### 3. Setup Assets

Create `assets/styles/app.css`:
```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

Update `assets/app.js`:
```javascript
import './styles/app.css';
import './bootstrap.js';
```

### 4. Configure Routes

Create `config/routes.yaml`:
```yaml
# Home
app_home:
    path: /
    controller: App\Controller\HomeController::index

# Tools
app_tools:
    path: /tools
    controller: App\Controller\ToolController::index

app_tool_show:
    path: /tools/{id}
    controller: App\Controller\ToolController::show

app_tool_create:
    path: /tools/create
    controller: App\Controller\ToolController::create

# Services
app_services:
    path: /services
    controller: App\Controller\ServiceController::index

app_service_book:
    path: /services/{id}/book
    controller: App\Controller\ServiceController::book

# Reservations
app_reservations:
    path: /reservations
    controller: App\Controller\ReservationController::index

app_reservation_cancel:
    path: /reservations/{id}/cancel
    controller: App\Controller\ReservationController::cancel

# Host
app_host_dashboard:
    path: /host/dashboard
    controller: App\Controller\HostController::dashboard

app_logement_create:
    path: /logements/create
    controller: App\Controller\LogementController::create

app_logement_edit:
    path: /logements/{id}/edit
    controller: App\Controller\LogementController::edit

app_logement_show:
    path: /logements/{id}
    controller: App\Controller\LogementController::show

# Reviews
app_review_create:
    path: /reviews/create
    controller: App\Controller\ReviewController::create

# Admin
app_admin:
    path: /admin
    controller: App\Controller\AdminController::index
```

### 5. Build Assets

```bash
npm install
npm run dev
```

Or for production:
```bash
npm run build
```

### 6. Run the Development Server

```bash
symfony server:start
```

Or using PHP built-in server:
```bash
php -S localhost:8000 -t public
```

Then visit: `http://localhost:8000`

## Quick Demo Setup (Without Database)

To quickly see the templates working, update the controllers to use mock data:

### Update HomeController.php

```php
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

        // Mock data for demo
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
                'description' => 'Beautiful apartment in the heart of Paris',
                'category' => 'apartment',
                'pricePerNight' => 150,
                'location' => 'Le Marais, Paris',
                'city' => 'Paris',
                'country' => 'France',
                'images' => ['https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&h=600&fit=crop'],
                'amenities' => ['WiFi', 'Kitchen', 'Washer'],
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
                'images' => ['https://images.unsplash.com/photo-1493809842364-78817add7ffb?w=800&h=600&fit=crop'],
                'amenities' => ['WiFi', 'Kitchen', 'Heating'],
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
                'images' => ['https://images.unsplash.com/photo-1449158743715-0a90ebb6d2d8?w=800&h=600&fit=crop'],
                'amenities' => ['Fireplace', 'Kitchen', 'Hot tub'],
                'maxGuests' => 6,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'status' => 'active',
                'rating' => 4.9,
                'reviewCount' => 56,
            ],
        ];

        // Apply filters
        return array_filter($allLogements, function($logement) use ($filters) {
            if ($filters['category'] && $logement['category'] !== $filters['category']) {
                return false;
            }
            if ($filters['location']) {
                $search = strtolower($filters['location']);
                $matchesLocation = 
                    str_contains(strtolower($logement['location']), $search) ||
                    str_contains(strtolower($logement['city']), $search) ||
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
        });
    }

    private function getMockHosts(): array
    {
        return [
            1 => [
                'id' => 1,
                'name' => 'Bob Smith',
                'email' => 'host@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop',
            ],
            2 => [
                'id' => 2,
                'name' => 'Emma Host',
                'email' => 'host2@example.com',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&h=150&fit=crop',
            ],
        ];
    }
}
```

## Testing the Templates

1. **Homepage**: `http://localhost:8000/` - Should show logements grid with search filters
2. **Tools**: `http://localhost:8000/tools` - Tools marketplace
3. **Services**: `http://localhost:8000/services` - Services listing

## Troubleshooting

### Assets not loading
```bash
# Clear cache
php bin/console cache:clear

# Rebuild assets
npm run dev
```

### Routes not working
```bash
# Check routes
php bin/console debug:router

# Clear cache
php bin/console cache:clear
```

### Stimulus controllers not working
```bash
# Make sure Stimulus bridge is enabled in webpack.config.js
# Rebuild assets
npm run dev
```

## Next Steps

1. ✅ Set up database and create entities
2. ✅ Implement authentication system
3. ✅ Replace mock data with real database queries
4. ✅ Add form handling for creating/editing listings
5. ✅ Implement booking system
6. ✅ Add image upload functionality

For detailed entity structure, see the original TypeScript types in the walkthrough document.
