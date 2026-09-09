# RADAR Coding Standards

## Purpose

These rules apply to every RADAR developer and coding agent. Follow existing project conventions when compatible with this document. Never claim unverified integrations, routes, framework versions, or feature completion.

## Before Coding

1. Inspect existing implementation.
2. Read `/docs`.
3. Check migrations.
4. Check routes.
5. Check existing services.
6. Check tests.
7. Avoid duplicate implementation.

Also inspect manifests, configuration, deployment constraints, and framework conventions before adding dependencies or adopting recommendations in [ARCHITECTURE.md](ARCHITECTURE.md).

## Laravel Standards

When implemented with Laravel:

- keep controllers thin;
- use Form Requests;
- use Policies;
- use API Resources;
- use services/actions for domain logic;
- use Jobs for slow operations;
- use events/listeners when appropriate;
- use enums for stable domain states;
- use DTOs for external provider payloads;
- use transactions for multi-write business operations; and
- prefer dependency injection over static coupling.

Provider parsing, HTTP behavior, and error mapping belong in Source Adapters, never controllers.

## PHP Standards

- Use strict typing where practical.
- Use typed properties and return types.
- Keep methods small and focused.
- Handle expected exceptions explicitly.
- Use meaningful domain-specific names.
- Avoid hidden side effects, unclear boolean flags, and unnecessary global/static coupling.

## External Providers

- Never call providers directly from the frontend.
- Never hard-code provider secrets.
- Never fabricate API endpoints.
- Adapter tests use mocked responses.
- Provider changes remain isolated behind adapters and normalized DTOs.
- Preserve provider, source URL, license, attribution text, and terms URL when applicable.
- Follow [DATA_SOURCES.md](DATA_SOURCES.md) source priority, rate limits, and cache policy.
- Apply all [SECURITY.md](SECURITY.md) external-request and Website Watch controls.

## Database

- Review indexes for every query path, especially source, timestamp, status, location, and foreign-key filters.
- Avoid N+1 queries.
- Paginate large queries.
- Use chunking/cursors for bulk processing.
- Define retention for high-volume tables.
- Avoid storing raw HTML forever.

High-volume candidates:

```text
fetch_logs
snapshots
signal_events
```

Schema changes must include appropriate foreign keys, nullability, uniqueness, indexes, and migration-safe deployment planning. Retention must preserve hashes, provenance, and audit evidence required by the Signal Engine.

## Queue Rules

Jobs should be idempotent where practical, retryable, timeout protected, and observable. Use backoff. Do not put huge payloads in queue messages; persist them and queue stable identifiers. Use locks and unique keys to prevent duplicate upstream work. Separate permanent failures from transient failures and follow queue routing in [ARCHITECTURE.md](ARCHITECTURE.md).

## Testing

Automated tests **MUST NOT** depend on live external APIs. Use mocked HTTP, for example:

```php
Http::fake()
```

Test adapters, normalization, diff, deduplication, Signal generation, API filters, authorization, rate limiting, queue behavior, and SSRF protection.

SSRF tests must include:

- localhost;
- `127.0.0.1`;
- private IPv4;
- private IPv6;
- metadata endpoint; and
- redirect to private IP.

Assert provenance, idempotency, and unchanged-snapshot no-op behavior, not only happy paths. Test deterministic and AI-assisted Signal behavior separately; see [SIGNAL_ENGINE.md](SIGNAL_ENGINE.md).

## Definition of Completion

A task is not complete merely because code compiles. Before reporting completion:

1. run relevant tests;
2. run formatting/static checks if configured;
3. inspect errors;
4. verify migrations;
5. verify routes;
6. report actual files modified; and
7. report known limitations.

Never claim a feature is implemented unless verified. If no runnable test, formatter, static-analysis, migration, or route command exists, state that exact limitation and report inspections performed.

## Documentation Discipline

Update architecture documentation whenever implementation changes a stable domain contract, source policy, security boundary, queue topology, or Signal lifecycle. Keep terminology consistent with:

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```