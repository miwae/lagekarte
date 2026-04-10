# Security Policy

## Scope
This project must be operated with encrypted transport and strict secret handling.

## Reporting a Vulnerability
Please report vulnerabilities privately to the project maintainer.
Do not open public issues with exploit details.

## Baseline Requirements
- HTTPS only in production, with redirect from HTTP to HTTPS.
- HSTS enabled after HTTPS is stable.
- Security headers enabled (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy).
- No secrets committed to git.
- Keep dependencies and server packages updated.

## Secret Management
- Use .env for local development only.
- Never commit .env, private keys, or certificates.
- Rotate secrets immediately if leakage is suspected.

## Incident Response
1. Contain: disable exposed credentials and isolate affected systems.
2. Eradicate: patch root cause and remove persistence.
3. Recover: restore from trusted state and verify integrity.
4. Review: document timeline and add preventive controls.
