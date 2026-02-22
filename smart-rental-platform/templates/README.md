# React to Twig Conversion - README

This directory contains Twig templates converted from the original React/Next.js application.

## Directory Structure

```
templates/
├── base.html.twig                 # Base layout template
├── _navbar.html.twig              # Navigation bar component
├── home/
│   └── index.html.twig           # Homepage
├── tools/
│   └── index.html.twig           # Tools listing page
├── services/
│   └── index.html.twig           # Services listing page
├── reservation/
│   └── index.html.twig           # User reservations page
├── host/
│   └── dashboard.html.twig       # Host dashboard
└── components/
    ├── ui_macros.html.twig       # Reusable UI component macros
    ├── logement_card.html.twig   # Logement card component
    ├── tool_card.html.twig       # Tool card component
    └── search_filters.html.twig  # Search and filter component
```

## Stimulus Controllers

The following Stimulus controllers are located in `assets/controllers/`:

- **dropdown_controller.js** - Handles dropdown menu interactions in navbar
- **carousel_controller.js** - Image carousel for logement cards
- **favorite_controller.js** - Favorite/like button toggle
- **search_filters_controller.js** - Search and filter panel functionality

## Required Symfony Routes

The templates expect the following routes to be defined:

```php
// Home and listings
app_home                    # Homepage with logements
app_tools                   # Tools listing
app_services                # Services listing

// Logements
app_logement_show           # View single logement (requires id parameter)
app_logement_create         # Create new logement
app_logement_edit           # Edit logement (requires id parameter)

// Tools
app_tool_show              # View single tool (requires id parameter)
app_tool_create            # Create new tool

// Services
app_service_book           # Book a service (requires id parameter)

// Reservations
app_reservations           # User reservations list
app_reservation_cancel     # Cancel reservation (requires id parameter)

// Reviews
app_review_create          # Create review (requires reservationId parameter)

// Host
app_host_dashboard         # Host dashboard

// Admin
app_admin                  # Admin panel

// Auth
app_login                  # Login page
app_register               # Registration page
app_logout                 # Logout action
```

## Controller Data Requirements

### HomeController (app_home)

```php
return $this->render('home/index.html.twig', [
    'logements' => $logements,          // Array of Logement entities
    'hosts' => $hostsById,              // Array of User entities indexed by ID
    'filters' => $filters,              // Array of current filter values
    'isLoading' => false,               // Boolean for loading state
]);
```

### ToolsController (app_tools)

```php
return $this->render('tools/index.html.twig', [
    'tools' => $tools,                  // Array of Tool entities
    'hosts' => $hostsById,              // Array of User entities indexed by ID
    'filters' => $filters,              // Array of current filter values
]);
```

### ServicesController (app_services)

```php
return $this->render('services/index.html.twig', [
    'services' => $services,            // Array of Service entities
    'hosts' => $hostsById,              // Array of User entities indexed by ID
    'filters' => $filters,              // Array of current filter values
]);
```

### ReservationController (app_reservations)

```php
return $this->render('reservation/index.html.twig', [
    'reservations' => $reservations,    // Array of Reservation entities
    'logements' => $logementsById,      // Array of Logement entities indexed by ID
    'tools' => $toolsById,              // Array of Tool entities indexed by ID
    'hosts' => $hostsById,              // Array of User entities indexed by ID
    'status' => $statusFilter,          // Current status filter
]);
```

### HostController (app_host_dashboard)

```php
return $this->render('host/dashboard.html.twig', [
    'listings' => $listings,            // Array of host's listings (logements + tools)
    'stats' => [
        'totalListings' => $count,
        'activeBookings' => $count,
        'totalRevenue' => $amount,
        'avgRating' => $rating,
    ],
]);
```

## Tailwind CSS Setup

The templates use Tailwind CSS utility classes. Make sure you have Tailwind CSS configured in your Symfony project:

1. Install Tailwind CSS via npm:
   ```bash
   npm install -D tailwindcss
   ```

2. Create `tailwind.config.js`:
   ```javascript
   module.exports = {
     content: [
       "./templates/**/*.html.twig",
       "./assets/**/*.js",
     ],
     theme: {
       extend: {},
     },
     plugins: [],
   }
   ```

3. Add Tailwind directives to your CSS file (`assets/styles/app.css`):
   ```css
   @tailwind base;
   @tailwind components;
   @tailwind utilities;
   ```

4. Build your CSS with Webpack Encore or your preferred build tool.

## Symfony UX Setup

Install Symfony UX Stimulus:

```bash
composer require symfony/ux-stimulus-bundle
npm install --force
npm run watch
```

## Authentication

The templates use Symfony's security component. Make sure you have:

1. User entity implementing `UserInterface`
2. Security configuration in `config/packages/security.yaml`
3. Login/logout routes configured
4. Role hierarchy (ROLE_USER, ROLE_HOST, ROLE_ADMIN)

## Next Steps

1. **Create Symfony Controllers** - Implement the controllers listed above
2. **Set up Entities** - Create Doctrine entities for Logement, Tool, Service, Reservation, etc.
3. **Configure Routes** - Define all required routes in `config/routes.yaml` or via annotations
4. **Install Dependencies** - Set up Tailwind CSS and Symfony UX Stimulus
5. **Test Templates** - Render each template and verify functionality
6. **Add Form Handling** - Create forms for creating/editing listings, bookings, etc.

## Notes

- All templates extend `base.html.twig` for consistent layout
- Components use Twig macros for reusability
- Interactive features require Stimulus controllers to be properly registered
- Images use the `asset()` function - configure your asset paths accordingly
- Date formatting uses Twig's `date` filter - adjust format as needed
