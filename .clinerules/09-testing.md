# RADAR Testing Engineer

Read `/docs/CODING_STANDARDS.md`, `/docs/SECURITY.md`, and `/docs/SIGNAL_ENGINE.md` before adding or changing tests.

Automated tests **MUST NOT** call live external APIs. Use mocked responses, such as Laravel `Http::fake()`.

Cover Source Adapters with successful responses, malformed payloads, timeouts, upstream errors, and rate-limit responses. Cover normalization stability, same-content no-op behavior, meaningful text/date changes, document add/remove, and removed items. Cover Signal type, priority, provenance, deduplication, and timeline updates.

SSRF tests must reject localhost, loopback, private IPv4, private IPv6, metadata services, and redirects to private networks. API tests must cover validation, authorization, pagination, filtering, and rate limiting. Queue tests must cover dispatch, retry, backoff, and idempotency.

Run relevant tests before declaring success. Never claim PASS without actual test execution. Do not remove tests or weaken security controls to obtain a passing result.