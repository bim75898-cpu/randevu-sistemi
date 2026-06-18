# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | ✅ Active support  |

## Reporting a Vulnerability

We take the security of this project seriously. If you discover a security vulnerability, please report it privately before disclosing it publicly.

**Do not** report security vulnerabilities via public GitHub issues.

Instead, send a detailed report to the repository owner via:

- **GitHub Security Advisory**: Use the "Report a vulnerability" button under the Security tab of this repository.

### What to include

- Description of the vulnerability
- Steps to reproduce
- Affected versions
- Potential impact
- Any suggested fix (if available)

### Response Timeline

- **24 hours**: Initial acknowledgment
- **7 days**: Status update with fix plan
- **30 days**: Target resolution time for critical issues

## Security Features

This project implements the following security measures:

- **CSRF Protection**: Per-session tokens with double-submit cookie pattern on all forms
- **Rate Limiting**: 30 requests per 60 seconds per IP+endpoint
- **Brute Force Protection**: 5 failed login attempts → 1-hour IP block
- **Honeypot Fields**: Invisible anti-bot fields on public forms
- **Input Sanitization**: Strip tags, trim, type casting on all user input
- **Security Headers**: X-Frame-Options, Content-Security-Policy, HSTS, X-Content-Type-Options
- **Session Security**: IP and user-agent binding, HTTPS-only cookies
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Prevention**: Output encoding via `htmlspecialchars()`
- **API Security**: Bearer token auth, request body size limit (1MB), input validation

## Best Practices for Deployment

1. Change the default admin password (`admin` / `admin123`) immediately
2. Regenerate the API key in Settings → API
3. Enable HTTPS in production
4. Keep PHP and MySQL updated
5. Regularly review security logs in the admin panel
6. Configure daily backups in Settings → Backup
7. Set appropriate file permissions (755 for directories, 644 for files)
