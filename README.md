# Film Ticket Booking Management System (FTBMS) — Sri Lanka

A complete, high-performance, multi-branch **Film Ticket Booking Management System (BMS)** built with **Laravel 13**, **Livewire**, **Tailwind CSS**, and **MySQL**.

---

## 🌟 Key Features

1. **Multi-Branch Cinema Management (Sri Lanka)**:
   - Pre-seeded with 3 realistic cinema branches:
     - **Regal Cinema — Colombo** (Screen 1 Dolby Atmos & Screen 2 Digital 3D)
     - **Liberty Cinema — Kandy** (Cinema Hall A & Cinema Hall B)
     - **Majestic Cineplex — Galle** (Gold Screen & Silver Screen)
   - 6 total screens, 300 physical seats, 72 showtimes, and 3,600 inventory records.

2. **Concurrency-Safe Seat Inventory & 7-Minute Holds**:
   - MySQL InnoDB pessimistic locking (`SELECT ... FOR UPDATE` via `lockForUpdate()`).
   - Prevents race conditions and double bookings even under concurrent traffic.
   - Real-time seat inventory polling (every 4 seconds via Livewire).
   - Dynamic seat categories: **Standard** (Rs. 1,000.00), **Premium** (Rs. 1,500.00), and **VIP** (Rs. 2,000.00).

3. **Sri Lankan Currency (LKR) Minor Units**:
   - Stored in integer cents (`price_minor`) to avoid floating-point inaccuracies.
   - Authoritative server-side pricing and discount calculations.

4. **Dual Payment Workflows**:
   - **Simulated Online Gateway**: Select between instant approval or declined payment simulations without needing real credit card numbers or external webhooks.
   - **Box Office Counter POS Cash Sales**: Built-in cash drawer that validates received cash, computes exact customer change, and issues admission tickets immediately.

5. **Cryptographic QR Code Tickets**:
   - Generates unique ticket codes and SHA-256 hashed QR tokens rendered as pure inline SVG via `chillerlan/php-qrcode`.
   - Gate scanner validation with **atomic duplicate-entry prevention** (rejects re-scans with exact previous validation timestamps).

6. **Branch-Scoped Authorization & Financial Reporting**:
   - Role-Based Access Control (**Admin**, **Cinema Manager**, **Counter Staff**, **Customer**).
   - Branch segregation: Cinema Managers are restricted to their assigned branch.
   - Real-time sales reconciliation: Online gateway revenue, Counter cash collected, processed refunds, and seat occupancy tracking.

---

## 👥 Demo User Credentials

All sample accounts use the password: **`password`**

| Role | Email | Scope / Assigned Branch |
| --- | --- | --- |
| **System Admin** | `admin@bms.lk` | Full island-wide access to all branches, reports & POS |
| **Cinema Manager (Colombo)** | `manager.colombo@bms.lk` | Regal Cinema — Colombo |
| **Cinema Manager (Kandy)** | `manager.kandy@bms.lk` | Liberty Cinema — Kandy |
| **Cinema Manager (Galle)** | `manager.galle@bms.lk` | Majestic Cineplex — Galle |
| **Counter Staff (Colombo)** | `staff.colombo@bms.lk` | Regal Cinema — Colombo (POS & Scanner) |
| **Registered Customer** | `customer@bms.lk` | Online booking, history & cancellations |

---

## 🚀 How to Run the Application Locally

### Prerequisites
- **PHP 8.3+** with extensions (`pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, `pdo_sqlite`)
- **Composer**
- **MySQL 8.4** or MariaDB
- **Node.js** (v20+) and **npm**

### Step-by-Step Instructions

1. **Navigate to the application folder**:
   ```powershell
   cd "D:\Progects 3.2\FTBS\FTBMS\application"
   ```

2. **Ensure MySQL is running** on `127.0.0.1:3306` with database `film_ticket_bms`.

3. **Database Migration & Seeding**:
   ```powershell
   php artisan migrate --seed
   ```

4. **Build Frontend Assets**:
   ```powershell
   npm run build
   ```

5. **Start the Laravel Development Server**:
   ```powershell
   php artisan serve
   ```
   Open your browser at: **`http://localhost:8000`**

---

## 🧪 Automated Test Suite (11/11 Passed)

To verify the acceptance criteria and concurrency logic:
```powershell
php artisan test --filter=BookingSystemTest
```

**Verified Scenarios**:
1. `test_customer_can_browse_movies_and_view_catalog`
2. `test_customer_can_hold_seats_successfully`
3. `test_concurrent_reservation_conflict_prevents_duplicate_holds`
4. `test_expired_holds_are_safely_released`
5. `test_online_payment_success_confirms_booking_and_issues_qr_tickets`
6. `test_online_payment_failure_does_not_issue_tickets`
7. `test_counter_staff_can_record_cash_payment_and_calculate_change`
8. `test_counter_staff_cannot_record_sale_for_unassigned_branch`
9. `test_ticket_validation_and_duplicate_scan_prevention`
10. `test_booking_cancellation_releases_seats_and_records_refund`
11. `test_admin_sales_reporting_reconciles_amounts`

---

## 📂 Architecture Overview

```
FTBMS/
└── application/
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Customer/ (MovieCatalogController, BookingConfirmationController)
    │   │   │   └── Admin/ (ReportController)
    │   │   └── Middleware/ (RoleMiddleware)
    │   ├── Livewire/
    │   │   ├── Customer/ (SeatSelection, Checkout)
    │   │   └── Staff/ (CounterBooking, TicketScanner)
    │   ├── Models/ (18 Eloquent models: Cinema, Screen, Seat, ShowSeat, Booking, Ticket, etc.)
    │   ├── Payments/ (PaymentGatewayInterface, MockPaymentGateway, PaymentResult)
    │   ├── Policies/ (CinemaPolicy, ShowtimePolicy)
    │   └── Services/
    │       ├── SeatReservationService.php (Pessimistic lock & 7-min hold)
    │       ├── BookingService.php (Authoritative pricing & lifecycle)
    │       ├── PaymentService.php (Online mock gateway workflow)
    │       ├── CashPaymentService.php (POS cash drawer & change)
    │       └── TicketService.php (Pure SVG QR generation & atomic validation)
    ├── database/
    │   ├── migrations/ (6 cleanly sequenced migrations)
    │   └── seeders/ (DatabaseSeeder with realistic Sri Lankan cinemas & schedules)
    └── resources/
        └── views/
            ├── customer/ (Movie catalog, details, booking confirmation with QR)
            ├── livewire/ (Interactive seat map, checkout, POS, scanner)
            └── admin/ (Sales reports, occupancy progress, audit trail)
```
