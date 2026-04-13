# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EyeCare is an optical management system for a local PH clinic, built as a monorepo with:
- `backend/` — Laravel 12 REST API + Livewire web admin
- `android/` — Kotlin Android app (MVVM, Hilt, Retrofit)
- `wireframes/` — UI design references

## Backend Commands

```bash
cd backend

# Install and first-time setup
composer install && npm install
php artisan migrate && php artisan db:seed

# Run all three dev processes concurrently (server + queue + Vite)
composer dev

# Build assets
npm run build

# Fix code style
composer lint

# Check code style without fixing
composer test:lint

# Run full test suite (lint + PHPUnit)
composer test

# Run PHPUnit only
php artisan test

# Run a single test file
php artisan test tests/Feature/SomeTest.php
```

Dev server: `http://localhost:8000` | API base: `http://localhost:8000/api/v1/`

## Android Commands

```bash
cd android

# Configure dev backend URL (required once)
echo "dev.backend.url=http://10.0.2.2:6969" >> local.properties  # emulator
# or: dev.backend.url=http://<your-pc-ip>:6969  # physical device

./gradlew build
./gradlew installDebug
./gradlew test
./gradlew connectedAndroidTest
```

## Architecture

### Layered Service Pattern (Backend)

```
HTTP Request → Controller (thin) → Form Request (validate)
                                 → Policy (authorize)
                                 → Service (all business logic)
                                     → Eloquent Model → MySQL
                                 → API Resource (JSON response)
```

**Rule:** No business logic in controllers. No SQL in controllers. No response formatting in services. Each layer has one job.

### Role-Based Access Control

Three roles as a `role` enum on `users` table (no RBAC package):
- `admin` — full access including product management, inventory adjustment, refunds/voids, system settings
- `staff` — process orders, record payments, respond to messages, manage schedules, view inventory (cannot adjust stock levels or manage products)
- `customer` — browse catalog, place orders, book appointments, write feedback

Enforced via `EnsureUserHasRole` middleware on route groups and Laravel Policies for model-level checks.

### Key Domain Rules

- **Products → Variants → Inventory**: Inventory is tracked per `ProductVariant`, not per `Product`. Every product always has a default variant (auto-created via observer). SKUs live on variants, formatted as `PRD-` + 8 random alphanumeric characters when auto-generated.
- **Orders**: Cart is client-side (Android). Backend receives all items in one `POST /api/v1/orders` call. Stock is validated at order creation; order is blocked if any item is out of stock. Walk-ins use `walk_in_name` + `walk_in_phone` (nullable `user_id`).
- **Billing**: No payment gateway — staff manually records payments. Bills are auto-generated on order creation. `amount_paid` + `balance_due` track partial payments. Cancelling before payment → voided; after payment → refunded.
- **Scheduling**: Admin creates `ScheduleTemplate` records (day-of-week, time range, slot duration); system batch-generates `TimeSlot` rows for a date range. Appointments with `fee > 0` auto-generate a bill.
- **Inventory audit**: Every stock change writes an `InventoryAdjustment` row (`before`, `after`, `delta`, `type`, `reason`, `adjusted_by`). Stock is not auto-decremented on order confirmation — manual adjustment for now.
- **Real-time messaging**: Laravel Reverb (WebSocket) + Laravel Broadcasting. Private channels per conversation, authorized via Sanctum.

### Authentication

- Android → Sanctum API token auth (`POST /api/v1/login` returns token)
- Web admin → Laravel session auth (Livewire, Fortify)

### Module Development Pattern

When adding a new module, always follow this order:

1. Migration → 2. Model → 3. Enum → 4. Service → 5. Controller → 6. Form Requests → 7. API Resources → 8. Policy → 9. Routes → 10. Seeder

### Testing

- Tests use SQLite in-memory (`:memory:`), configured in `phpunit.xml`
- Feature tests: `tests/Feature/` | Unit tests: `tests/Unit/`
- Cache, queue, and session use `array`/`sync` drivers in test env

### Database

- MySQL, database: `eyecare`, default: `root` / no password / `127.0.0.1:3306`
- A full DB dump is at `backend/database/eyecare_backup.sql`
- Soft deletes are enabled on `User`, `Product`, and `Order`

### Android Architecture

- MVVM with `ViewModel` + `LiveData`
- Hilt for dependency injection (DI modules in `di/`)
- Retrofit + OkHttp for API calls
- Backend URL is injected at build time from `local.properties`
