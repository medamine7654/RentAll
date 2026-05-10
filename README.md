# Smart Rental Platform

A full-featured rental marketplace built with **Symfony 6.4**, allowing users to rent accommodations, tools, and services — with carpooling, reviews, real-time notifications, and AI-powered features.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the App](#running-the-app)
- [Admin Panel](#admin-panel)
- [Console Commands](#console-commands)
- [Project Structure](#project-structure)

---

## Overview

Smart Rental Platform is a multi-role web application where:

- **Guests** can browse and book accommodations, rent tools, hire services, and join carpools.
- **Hosts** can list and manage their properties, tools, and services through a dedicated host dashboard.
- **Admins** have full control over users, listings, reservations, and categories via a back-office panel.

---

## Features

### Core
- User registration, login, and password reset (email + SMS code)
- OAuth login support
- Role-based access control: `ROLE_USER`, `ROLE_ADMIN`
- Account status management: active / suspended / banned
- Face & identity document verification

### Listings
- **Accommodations (Logements)**: title, description, address, price per night, rooms, beds, bathrooms, max guests, photos, category, city/country
- **Tools**: rental listings with image upload
- **Services**: service listings with image upload
- **Carpooling (Covoiturage)**: departure/arrival city, date, time, price per seat, available seats

### Bookings & Reviews
- Reservation system with statuses: pending → confirmed / refused / cancelled / completed
- Cancellation policy (up to 3 days before check-in)
- Review system (1–5 stars + comment) — only available after a completed stay
- Bad-words filter on review content

### AI & Smart Features
- **AI Risk Scoring**: Python-based login risk scoring (detects suspicious logins, can auto-suspend accounts)
- **Smart Recommendations**: similarity-based recommendations for accommodations, tools, and services
- **Price Suggestion**: AI-assisted pricing hints for hosts
- **Category Suggestion**: automatic category suggestions for new listings
- **Quality Score**: listing quality scoring

### Other
- **Weather widget**: live weather data for accommodation locations (Open-Meteo API, no key required)
- **Real-time notifications**: powered by Pusher Channels
- **Favorites**: save accommodations, tools, and services
- **Interactive maps**: Leaflet.js integration for accommodation locations
- **PDF generation**: reservation receipts via DomPDF
- **Live search & filters**: Stimulus.js controllers for dynamic search
- **Pagination**: KnpPaginatorBundle
- **Admin back-office**: full CRUD for users, listings, reservations, and categories

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 6.4 |
| Language | PHP 8.1+ |
| ORM | Doctrine ORM + Migrations |
| Frontend | Twig, Tailwind CSS, Stimulus.js, Turbo |
| Real-time | Pusher Channels |
| File uploads | VichUploaderBundle |
| Maps | Leaflet.js |
| Weather | Open-Meteo (free, no API key) |
| PDF | DomPDF |
| AI scoring | Python 3 script (`bin/risk_score.py`) |
| Database | MySQL (or PostgreSQL via Docker) |
| Testing | PHPUnit |

---

## Requirements

- PHP >= 8.1 with extensions: `ctype`, `iconv`
- Composer
- Node.js + npm (for Tailwind CSS)
- MySQL 8+ (or PostgreSQL 16 via Docker)
- Python 3.10+ (optional — only needed for AI risk scoring)

---

## Installation

```bash
# 1. Clone the repository
git clone <repository-url>
cd <project-folder>

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies and build CSS
npm install
npm run build
```

---

## Configuration

Copy the example environment file and fill in your values:

```bash
cp .env.example .env
```

Key variables to set in `.env`:

```dotenv
APP_ENV=dev
APP_SECRET=your_random_secret

# Database (MySQL example)
DATABASE_URL="mysql://user:password@127.0.0.1:3306/smart_rental?serverVersion=8.0.32&charset=utf8mb4"

# Mailer (use null://null for local dev)
MAILER_DSN=null://null

# Pusher (real-time notifications) — get free credentials at https://dashboard.pusher.com/
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### Optional AI Risk Scoring

```dotenv
AI_RISK_THRESHOLD=0.72          # Risk score threshold (0.4–0.98)
AI_RISK_TIMEOUT=8               # Python script timeout in seconds
AI_RISK_AUTO_SUSPEND=0          # Set to 1 to auto-suspend suspicious accounts
AI_RISK_AUTO_SUSPEND_THRESHOLD=0.92
```

---

## Database Setup

```bash
# Create the database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# (Optional) Load sample fixtures
php bin/console doctrine:fixtures:load
```

### Docker (PostgreSQL)

A `compose.yaml` is included for a quick PostgreSQL setup:

```bash
docker compose up -d
```

---

## Running the App

```bash
# Start the Symfony local server
symfony server:start

# Or use PHP's built-in server
php -S localhost:8000 -t public/
```

Then open [http://localhost:8000](http://localhost:8000).

### Create an Admin User

```bash
php bin/console app:create-admin
```

---

## Admin Panel

Accessible at `/admin` — requires `ROLE_ADMIN`.

| Section | URL |
|---|---|
| Dashboard | `/admin` |
| Users | `/admin/users` |
| Accommodations | `/admin/logements` |
| Reservations | `/admin/bookings` |
| Categories | `/admin/categories` |
| Carpooling | `/admin/covoiturage` |

The admin panel supports:
- User account management (activate / suspend / ban)
- Reservation status changes and deletion
- Listing moderation
- Category management

---

## Console Commands

| Command | Description |
|---|---|
| `app:create-admin` | Create an admin user interactively |
| `app:create-past-reservation` | Create a past reservation for review testing |
| `app:clean-reservations` | Clean up stale/expired reservations |
| `app:create-test-data` | Seed test data |
| `app:test-pusher` | Test Pusher connection |

---

## Project Structure

```
src/
├── Controller/
│   ├── Api/          # Search and weather API endpoints
│   ├── Back/         # Admin back-office controllers
│   └── Front/        # Public-facing controllers
├── Entity/           # Doctrine entities (User, Logement, Reservation, ...)
├── Form/             # Symfony form types
├── Repository/       # Doctrine repositories
├── Security/         # Authenticator and user checker
├── Service/          # Business logic (AI scoring, recommendations, weather, ...)
└── Twig/             # Custom Twig extensions

assets/
├── controllers/      # Stimulus.js controllers
└── styles/           # Tailwind CSS source

templates/            # Twig templates
migrations/           # Doctrine database migrations
public/               # Web root
```

---

## License

Proprietary — all rights reserved.
