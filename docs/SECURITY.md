# RADAR Security Policy

## Scope

This is mandatory policy for RADAR application code, APIs, source ingestion, and Website Watch. No feature requirement overrides it.

## SSRF Protection

User-submitted URLs and externally controlled redirect destinations must never access:

- localhost;
- `127.0.0.0/8`;
- `::1`;
- RFC1918 ranges;
- private IPv6 ranges;
- link-local addresses;
- cloud metadata endpoints;
- Docker internal services;
- internal hostnames; or
- internal network services.

These controls apply to Website Watch and every HTTP path where URLs are user-supplied, provider-controlled, or dynamically discovered.

### Requirements

1. Allow only HTTP and HTTPS.
2. Validate URL syntax, hostname form, port policy, and canonical representation.
3. Resolve DNS and validate **every resolved IP** against deny rules.
4. Pin or otherwise validate the approved connection destination against DNS rebinding.
5. Revalidate every redirect target.
6. Enforce a small configured redirect limit and reject loops.
7. Validate final destination after redirects.
8. Enforce connection and total request timeouts.
9. Stream with a response-size limit.
10. Validate allowed content types before parsing or storing.
11. Reject non-standard schemes, local file access, proxy tunneling, and protocol smuggling.
12. Record safe structured audit outcomes.

Deny rules must cover IPv4/IPv6 loopback, unspecified, link-local, private, unique-local IPv6, relevant internal/carrier ranges, and cloud metadata IPs and hostnames. Keep this as a reviewed, testable component—not scattered string checks.

### URL Canonicalization

Parse URLs with a standards-compliant parser. Lowercase scheme and DNS hostname, remove fragments, normalize default ports, and reject malformed percent encoding, control characters, URL credentials, and ambiguous host encodings. Do not use string-prefix checks. Canonicalization is insufficient alone: resolved IPs, every redirect, and final connection destinations must still pass SSRF policy.

## Responsible Collection

RADAR must not implement:

- authentication bypass;
- CAPTCHA bypass;
- credential guessing;
- session stealing;
- private-page crawling;
- anti-bot bypass;
- access-control bypass; or
- stealth crawling intended to evade blocking.

If automated crawling is prohibited, disable the HTML source and seek an official API, RSS, Atom, CAP, or open-data source. Browser automation is only a permitted public-content fallback and never a bypass mechanism. Honor terms, rate limits, attribution, and relevant robots guidance; see [DATA_SOURCES.md](DATA_SOURCES.md).

## Application Security

Require authentication, authorization, policies, CSRF protection, XSS protection, SQL injection protection, rate limiting, secrets protection, audit logs, and secure HTTP configuration including TLS, secure cookies, security headers, and safe production errors.

Never expose API tokens, database passwords, Redis credentials, internal URLs, provider secrets, private configuration, or stack traces.

## API Security

Protect `/api/v1/*` using endpoint-specific authentication and authorization, route/identity rate limits, validation, request-size limits, pagination caps, and consistent errors.

Require ownership or granted-access authorization for private resources including:

- My Radars;
- Alerts;
- Saved Signals; and
- Website Watches.

Public data endpoints may use separate quotas but must not permit unbounded expensive queries, bulk export, internal identifiers, or source secrets.

## Logging

Use structured logs with correlation IDs, safe source/URL identifiers, outcome codes, durations, and retry state. Do not log secrets, access tokens, credentials, or sensitive request headers. Redact query parameters and bodies that can contain tokens, personal data, or private configuration. Restrict audit-log access and define retention.

## Verification

Automated SSRF tests must deny localhost, loopback IPv4, private IPv4, private IPv6, metadata endpoints, and redirects to private IPs. Test authorization, validation, rate limits, and redirect handling whenever relevant code changes. See [CODING_STANDARDS.md](CODING_STANDARDS.md).