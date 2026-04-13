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
- [ ] XML-RPC disabled if not needed
- [ ] REST user enumeration blocked (`/wp-json/wp/v2/users`)
- [ ] Author enumeration blocked (`/?author=<id>`)
- [ ] Server packages and dependencies updated
- [ ] Monitoring and log rotation active

## 5) WordPress Surface Reduction
- [ ] WordPress generator tag removed
- [ ] Public user archives reviewed or disabled
- [ ] Unneeded REST namespaces/routes restricted
- [ ] Admin access protected (MFA and IP restrictions where possible)

## 6) Continuous Verification
- [ ] Monthly TLS and header checks
- [ ] Automated secret scanning in CI
- [ ] Backup restore test done
