# Security CI Setup

This repository includes an automated security workflow:
- Secret scan with Gitleaks
- HTTPS redirect and security header checks

Workflow file:
- .github/workflows/security-checks.yml

## Required repository variable
Set this repository variable in GitHub:
- Name: LAGEKARTE_URL
- Value example: https://lagekarte.example.org/lagekarte2/

Path in GitHub:
- Settings -> Secrets and variables -> Actions -> Variables -> New repository variable

## What the workflow validates
- Secret leaks in git history and current code
- HTTP redirects to HTTPS (301 or 308)
- Required response headers:
  - Content-Security-Policy
  - X-Content-Type-Options
  - Referrer-Policy
  - Strict-Transport-Security (for HTTPS targets)

## Notes
- If LAGEKARTE_URL is not set, the URL check job is skipped with an info message.
- Adjust required headers in the workflow if your deployment has a different policy.
