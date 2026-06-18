# Randevu Sistemi — Appointment Management System

A full-featured, modern appointment scheduling and management system built with PHP, MySQL, and TailwindCSS. Designed for XAMPP environments with enterprise-grade security, multi-branch support, customer loyalty, and a comprehensive admin panel.

## Features

### Appointment Management
- Online booking with real-time availability
- Recurring appointments (daily, weekly, monthly)
- Multi-branch support with per-branch services, employees, and schedules
- Break time management with employee overrides
- QR code check-in system
- Appointment history with cancel/reschedule tokens

### Customer Experience
- Loyalty points (1 point per 50 TL spent)
- Token-based appointment management (cancel, reschedule, payment)
- Email & SMS notifications (PHPMailer, SmsService)
- Post-appointment mood tracking (emoji-based review)
- Customer-facing package purchase and usage
- PWA support (offline-capable service worker + manifest)

### Admin Panel
- Standalone page architecture (no routed SPA)
- Dashboard with statistics, mood chart (Chart.js), and appointment heatmap
- Full CRUD: services, employees, customers, branches, working hours, break times
- Package management with multi-service session bundles
- Bulk messaging to customers
- Settings panel with 8 sub-tab categories (general, SMTP, SMS, payment, API, backup, cache, error handling)
- API test console with auto-generated code examples (cURL, PHP, JS, Python)
- Review management filtered by mood score

### Security
- CSRF protection (per-session tokens + double-submit cookie pattern)
- Rate limiting (30 req/min per IP + endpoint)
- Brute force lockout (5 failed attempts → 1-hour IP block)
- Honeypot fields on public forms
- Input sanitization (strip tags, trim, type casting)
- Security headers (X-Frame-Options, CSP, HSTS, X-Content-Type-Options)
- Session IP & user-agent binding
- IP blocking system (manual + automatic)
- SQL injection prevention (prepared statements throughout)
- Activity logging for security events

### REST API
- Bearer token authentication (Authorization header only)
- Full CRUD for appointments, services, employees, customers
- Pagination, filtering, sorting
- Rate limiting & brute force protection
- Input validation with descriptive errors
- Security headers on all responses

### Technical Features
- **Settings stored in database** — fully editable from admin UI
- **Multi-language** — Turkish (native) + English via `_t()` helper
- **PDF generation** — FPDF with Turkish character transliteration
- **Daily backup** — automatic `mysqldump` with admin controls
- **Cache abstraction** — auto-detects Redis → Memcache → File
- **Error handling** — custom handler with file log + optional Sentry
- **Auto-migration** — database tables and columns created on first load

## Requirements

- XAMPP (Apache + PHP 8.0+ + MySQL 5.7+)
- `mysqldump` in PATH (for backups)
- Optional: Redis or Memcache server

## Installation

1. Clone or copy the project to `C:\xampp\htdocs\randv`
2. Open `http://localhost/randv/` in your browser
3. The database and tables are created automatically on first load
4. Default admin credentials: `admin` / `admin123`

## Configuration

All configuration is managed through the admin panel under **Settings** (8 sub-tabs):

| Tab | Key Settings |
|-----|-------------|
| General | Site name, timezone, date format, appointment duration |
| SMTP | Mail server, port, credentials, encryption |
| SMS | SMS provider, API key, sender name |
| Payment | Iyzico sandbox/live API credentials |
| API | Bearer token for REST API access |
| Backup | Auto-backup toggle, frequency, retention, path |
| Cache | Cache driver (auto/redis/memcache/file), TTL |
| Error | Error log level, Sentry DSN |

## Directory Structure

```
├── admin/              # Admin panel pages (standalone PHP files)
│   ├── pages/          # Shared layout (head, footer)
│   ├── _init.php       # Bootstrap (DB, auth, language)
│   ├── dashboard.php   # Stats, mood chart, heatmap
│   ├── appointments.php
│   ├── services.php
│   ├── employees.php
│   ├── customers.php
│   ├── branches.php
│   ├── packages.php
│   ├── hours.php       # Working hours
│   ├── breaks.php      # Break times
│   ├── series.php      # Recurring appointments
│   ├── bulk.php        # Bulk messaging
│   ├── reviews.php     # Mood tracker reviews
│   ├── settings.php    # 8 sub-tab settings
│   ├── api-test.php    # API test console
│   └── login.php
├── api/                # REST API
│   ├── index.php       # Router + handlers
│   └── ApiAuth.php     # Bearer auth + rate limiting
├── config/             # Core configuration
│   ├── database.php    # DB connection + auto-migration
│   └── security.php    # Security class (CSRF, rate limit, etc.)
├── customer/           # Customer-facing pages
│   ├── history.php     # Appointment history + packages
│   ├── cancel.php      # Cancel/reschedule
│   ├── pay.php         # Payment (Iyzico)
│   └── payment_callback.php
├── lib/                # Libraries
│   ├── Settings.php    # Key-value settings from DB
│   ├── MailService.php # Email via PHPMailer
│   ├── SmsService.php  # SMS abstraction
│   ├── PaymentService.php
│   ├── PdfService.php  # FPDF wrapper
│   ├── CalendarService.php
│   ├── CacheService.php # Redis/Memcache/File
│   ├── BackupService.php
│   ├── ErrorHandler.php
│   ├── Language.php    # Multi-language engine
│   ├── lang/           # Language files (tr.php, en.php)
│   └── fpdf.php        # FPDF library
├── pwa/                # Progressive Web App
│   ├── manifest.json
│   ├── service-worker.js
│   ├── offline.html
│   └── icons/
├── index.php           # Public booking page
├── process.php         # Booking form handler
├── packages.php        # Public package listing
├── purchase_package.php # Package purchase handler
├── checkin.php         # QR check-in page
├── review.php          # Post-appointment review
├── cron.php            # Scheduled tasks (backup, reminders)
└── pdf.php             # PDF output
```

## API Usage

```bash
# List appointments (paginated)
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://localhost/randv/api/?entity=appointments&page=1&limit=10"

# Create a service
curl -X POST -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"name":"Haircut","duration":30,"price":200}' \
  "https://localhost/randv/api/?entity=services"
```

## License

MIT
