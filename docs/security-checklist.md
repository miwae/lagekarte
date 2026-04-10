# Lagekarte Security Checklist

## 1) Transport Security
- [ ] Valid TLS certificate installed
- [ ] HTTP permanently redirects to HTTPS (301)
- [ ] HSTS enabled after HTTPS verification
- [ ] No mixed-content warnings in browser

## 2) HTTP Response Hardening
- [ ] Strict-Transport-Security
- [ ] Content-Security-Policy
- [ ] X-Content-Type-Options: nosniff
- [ ] X-Frame-Options or frame-ancestors in CSP
- [ ] Referrer-Policy
- [ ] Permissions-Policy

## 3) Data and Secrets
- [ ] .env is local only and never committed
- [ ] API keys are rotated and stored securely
- [ ] Principle of least privilege for all credentials

## 4) Server Hardening
- [ ] Directory listing disabled
- [ ] Access to hidden files blocked
- [ ] Server packages and dependencies updated
- [ ] Monitoring and log rotation active

## 5) Continuous Verification
- [ ] Monthly TLS and header checks
- [ ] Automated secret scanning in CI
- [ ] Backup restore test done
