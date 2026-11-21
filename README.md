# iGarage

iGarage is a vehicle garage rental web application. Renters register, wait for an admin to verify their ID card, and then rent garages for their bicycle, motorcycle, car or electric car, daily or monthly. Electric cars can be charged in electric garages. The admin creates garages, verifies renters, extends rents and reads the revenue report.

Built for the LSP/BNSP **Pemrogram** (Programmer) certification, 21–22 November 2026.

- Repository: https://github.com/drphnd/iGarage
- Architecture: monolithic MVC (Laravel controllers + Eloquent models + Blade views) with a MySQL relational database

---

## Features

### Renter
| Feature | Page |
|---|---|
| Register with NIK, full name, phone, email and password (account starts as *pending*) | `/register` |
| Log in and log out | `/login` |
| Waiting page while the account is pending or rejected (only a logout button) | `/verification` |
| Rent a garage: check available garages for the dates, choose one, fill in the vehicle, upload the payment proof | `/rents/create` |
| First full charge for an electric car in an electric garage, paid together with the rent | `/rents/create` |
| Request another charging session during an active rent (enter the current kWh, upload the payment proof) | `/rents/{rent}/charges/create` |
| My Rents: every rent with its status, charging sessions, extensions and total paid | `/my-rents` |

### Admin
| Feature | Page |
|---|---|
| Garage list with today's status (available, booked, in use) | `/admin/garages` |
| Create garages in bulk (codes continue automatically: C001, C002, …) | `/admin/garages/create` |
| Verify renter registrations (approve or reject) | `/admin/renters` |
| Extend a rent that is active today, with the renter's payment proof | `/admin/extends` |
| Report per month in a year or per day in a month, per garage type and per renter | `/admin/reports` |

---

## Business rules

| Rule | Detail |
|---|---|
| Vehicle → garage | Bicycle and motorcycle → bicycle garage (S). Car → car garage (C). Electric car → car garage (C) or electric garage (E). |
| Daily rent | 1 day up to (days in the start month − 1). Fee = days × daily price. |
| Monthly rent | Exactly one calendar month (21 Nov → 21 Dec, 2 Feb → 2 Mar). Fee = 25 × daily price. |
| Dates | Start date cannot be in the past; booking ahead is allowed (up to one year). The end date is the day the vehicle leaves, so the next rent may start on that day. |
| Availability | Two rents clash when `existing.start < new.end AND existing.end > new.start`. Checked when listing garages and again when saving. |
| Same vehicle | A vehicle (same plate number, spaces ignored) cannot be in two garages at the same time. |
| Charging | Only electric cars in electric garages. Fee = (battery capacity − current level) × price per kWh. Every session has its own payment proof. |
| Extension | Admin only, for rents active today. Amount = extra days × daily price. Cannot run past the next booking of the same garage. |
| Garage status | Not stored. Calculated from rents: *in use* (a rent covers today), *booked* (a rent starts later), *available*. |
| Report | Each amount counts on the day it was paid. Rent revenue = rent fees + extensions. Charging revenue = charging fees. |

---

## Tech stack and libraries

| Library / tool | Version | Used for |
|---|---|---|
| **PHP** | 8.5 (min. 8.3) | Server-side language |
| **Laravel** | 13 | MVC framework: routing, controllers, Eloquent ORM, migrations, validation, middleware, file storage |
| **Laravel authentication (built in)** | – | `Auth` facade, session, password hashing, `auth` / `guest` / `throttle` middleware. No starter kit (no Breeze); login and registration are written in `AuthController`. |
| **Blade** | (Laravel) | Server-side templates and reusable components (`x-button`, `x-input`, `x-card`, …) |
| **MySQL** | 8 (via DBngin) | Relational database |
| **Tailwind CSS** | 4 | Utility-first CSS for the Apple-style interface |
| **Vite** + `laravel-vite-plugin` | 8 | Builds and serves CSS/JS |
| **Inter** (Bunny Fonts) | – | Interface font |
| **Pest** (on PHPUnit) | 5 | Automated feature tests, run on an in-memory SQLite database |
| **Laravel Herd** | – | Local PHP server on Windows (`http://igarage.test`) |

Custom code worth knowing:

- `app/Http/Middleware/EnsureUserHasRole.php`: `role:admin` / `role:renter` route protection
- `app/Http/Middleware/EnsureRenterIsApproved.php`: blocks pending and rejected renters
- `Garage::scopeAvailableBetween()`: the date-overlap availability query, reused for renting and extending
- `Garage::statusOn()` and `Rent::status()`: derived statuses

---

## Database

Six tables: `users`, `rides` (vehicles), `garages`, `rents`, `rent_extends`, `ev_charges`.
`rents` resolves the many-to-many between rides and garages. Every relationship is one-to-many.

| Diagram | File |
|---|---|
| Use case diagram | [`docs/use-case-igarage.png`](docs/use-case-igarage.png) |
| Entity relationship diagram | [`docs/erd-igarage.png`](docs/erd-igarage.png) |
| Class diagram | [`docs/class-diagram-igarage.png`](docs/class-diagram-igarage.png) |

The `.drawio` files next to them can be opened and edited in [diagrams.net](https://app.diagrams.net).

---

## Installation

Requirements: PHP 8.3+, Composer, Node.js 20+, MySQL.

```bash
git clone https://github.com/drphnd/iGarage.git igarage
cd igarage
composer install
npm install
cp .env.example .env            # Windows PowerShell: copy .env.example .env
php artisan key:generate
```

Set the database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=igarage
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan storage:link        # makes uploaded payment proofs viewable
npm run build                   # or: npm run dev
```

Open `http://igarage.test` (Herd) or run `php artisan serve` and open `http://127.0.0.1:8000`.

### Demo accounts (password: `password`)

| Email | Role |
|---|---|
| admin@igarage.test | Admin |
| renter@igarage.test | Renter, approved |
| pending@igarage.test | Renter, waiting for verification |

The seeder also creates garages S001–S003 (Rp 10.000/day), C001–C003 (Rp 40.000/day) and E001–E002 (Rp 50.000/day, Rp 2.500/kWh).

---

## Testing

```bash
php artisan test
```

92 automated tests in two groups:

**Unit tests** (`tests/Unit`) check one model method at a time, without HTTP or database:

| File | What it checks |
|---|---|
| `UserTest` | `isAdmin()`, `isApproved()`, role and status cannot be mass assigned |
| `RideTest` | `normalizePlate()`, `canParkIn()` for every vehicle type, `isElectric()` |
| `GarageTest` | `makeCode()`, `monthlyPrice()`, `statusOn()` (in use, booked, available) |
| `RentTest` | `maxDailyDays()` per month incl. leap year, `daysFor()`, `feeFor()`, `status()`, `totalDays()`, `totalPaid()`, `canRequestCharge()` |
| `EvChargeTest` | `kwhToFull()`, `feeFor()`, `batteryPercent()` |

**Feature tests** (`tests/Feature`) send real HTTP requests through routes, middleware and **controllers** to the database and views:

| File | Controller | What it checks |
|---|---|---|
| `AccountAccessTest` | `AuthController`, `HomeController` | Login, registration, verification block, role-based pages |
| `RenterVerificationTest` | `Admin\RenterController` | Admin approves or rejects pending renters |
| `GarageManagementTest` | `Admin\GarageController` | Bulk create, codes, kWh price, 999 limit, derived status, availability |
| `RentGarageTest` | `RentController` | Renting: dates, daily/monthly rules, clashes, vehicle rules, first charge, uploads |
| `ChargingSessionTest` | `EvChargeController` | Extra charging sessions, battery limits, ownership |
| `ExtendRentTest` | `Admin\RentExtendController` | Extensions, next-booking limit, active rents only |
| `ReportTest` | `Admin\ReportController` | Totals per month, per day, per garage type, per renter |

---

## Project structure

```
app/Http/Controllers/
    AuthController.php            login, register, verification page, logout
    HomeController.php            sends each role to its start page
    RentController.php            renter: My Rents, rent a garage
    EvChargeController.php        renter: extra charging session
    Admin/GarageController.php    garage list and bulk create
    Admin/RenterController.php    renter verification
    Admin/RentExtendController.php rent extensions
    Admin/ReportController.php    report
app/Http/Middleware/              role and approval checks
app/Models/                       User, Ride, Garage, Rent, RentExtend, EvCharge
database/migrations/              table definitions
database/seeders/AdminSeeder.php  demo accounts and garages
resources/views/                  Blade pages and components
routes/web.php                    all routes, grouped by role
tests/Feature/                    automated tests
docs/                             diagrams
```
