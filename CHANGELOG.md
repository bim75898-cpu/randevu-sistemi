# Changelog

All notable changes to this project are documented in this file.

## [1.0.0] — 2026-06-18

### Added
- Initial release of the appointment management system
- Online booking with real-time availability
- Multi-branch support (services, employees, schedules per branch)
- Admin panel with standalone page architecture
- Customer management with loyalty points (1 point per 50 TL)
- Email notifications via PHPMailer
- SMS notifications with provider abstraction
- Iyzico payment integration (sandbox/live)
- PDF invoice generation (FPDF with Turkish character support)
- PWA support (manifest, service worker, offline page)
- REST API with bearer token authentication
- Token-based appointment cancel/reschedule
- QR check-in system
- Post-appointment mood tracking (emoji-based reviews)
- Appointment heatmap dashboard
- Package/bundle system (multi-service session packs)
- Recurring appointments (daily, weekly, monthly)
- Break time management with employee overrides
- Bulk messaging to customers
- Automatic daily database backup
- Multi-language support (Turkish + English)
- Cache abstraction layer (Redis, Memcache, File)
- Custom error handler with Sentry integration
- Security test suite (16+ attack scenarios)
- API test console with code examples (cURL, PHP, JS, Python)
- Security features: CSRF, rate limiting, brute force protection, honeypot, IP blocking, session binding, security headers
