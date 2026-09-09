# RADAR Data Sources and Ingestion Policy

## Purpose

RADAR collects permitted public information responsibly, normalizes it, preserves attribution, and produces evidence for the central flow:

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

## Source Priority

Use sources in this order whenever an appropriate permitted option exists:

1. Official API
2. Official Open Data
3. RSS
4. Atom
5. CAP
6. Sitemap
7. Public HTML
8. Browser-rendered public HTML

If an official API exists, use it. If RSS, Atom, or CAP exists, do not scrape corresponding HTML unnecessarily. Browser automation is used only when public content cannot reasonably be obtained through HTTP and all policy controls pass.

## Source Types

```text
API
OPEN_DATA
RSS
ATOM
CAP
SITEMAP
HTML
BROWSER
```

## Source Statuses

```text
ACTIVE
PAUSED
DEGRADED
FAILED
DISABLED
```

- **ACTIVE:** eligible for scheduled collection.
- **PAUSED:** intentionally not collected.
- **DEGRADED:** controlled collection continues despite impaired reliability.
- **FAILED:** cannot collect until corrected.
- **DISABLED:** prohibited, removed, unsafe, not permitted, or retired.

## Minimum Source Metadata

```text
name
slug
provider
source_type
base_url
endpoint
authentication_type
poll_interval
rate_limit
location_scope
categories
terms_url
attribution
robots_status
crawl_allowed
status
last_fetch_at
last_success_at
last_failure_at
failure_count
```

`endpoint` can be absent only for non-endpoint sources such as an explicitly configured public page. Never place secrets in publicly readable metadata.

## Source Adapter Architecture

All external-provider behavior belongs behind a Source Adapter. It owns request construction, authentication, cursoring, pagination, parsing, provider-error mapping, and normalization. Raw provider payloads must not enter general domain services.

Conceptual interface:

```php
interface SourceAdapter
{
    public function fetch(): mixed;

    public function normalize(mixed $payload): array;

    public function source(): string;
}
```

The implementation may improve this interface with DTOs, cursors, pagination, source configuration, metadata, retry context, typed fetch results, and error objects while preserving the boundary.

Adapters must apply rate limits and cache policy, retain stable IDs and canonical URLs when published, preserve provenance, classify provider errors, use mocked-response tests, and keep provider logic out of controllers and generic Signal services.

## Initial Planned Indonesian Public Sources

The following are planned categories only. They are **not verified integrations**, and this document declares no actual provider endpoint.

| Provider | Type | Module | Status |
|---|---|---|---|
| BMKG Weather | API | Weather | Planned |
| BMKG Weather Alert | CAP/API | Alerts | Planned |
| BMKG Earthquake | API | Earthquake | Planned |
| Government Sources | API/RSS/HTML | Government | Planned |
| Job Sources | API/RSS/HTML | Jobs | Planned |

Planned categories also include Indonesian government APIs, government RSS, public open-data portals, permitted job sources, and public event feeds.

**Do not fabricate provider endpoints.** During implementation, verify each endpoint, authentication method, license, rate limit, attribution rule, and permitted-use condition from official provider documentation or published terms. Record verification in the source registry or implementation documentation.

## Collection Controls

- Use conditional requests (`ETag`, `If-None-Match`, `Last-Modified`, `If-Modified-Since`) where available.
- Cache upstream responses according to source configuration.
- Use source-level locks to prevent concurrent duplicate fetches.
- Honor rate limits and `Retry-After`.
- Stop or disable sources that are prohibited, unsafe, or repeatedly failing.
- Apply URL, DNS, redirect, response-size, and content-type controls from [SECURITY.md](SECURITY.md).

## Configurable Caching Policy Examples

Defaults are planning guidance only; each remains configurable per source and cannot override stricter provider constraints.

| Category | Example polling/cache interval |
|---|---|
| Weather | 30–60 minutes |
| Weather Alerts | 2–5 minutes |
| Earthquake | 1–5 minutes |
| Government | 30–60 minutes |
| Jobs | 15–60 minutes |
| Documents | 30–60 minutes |

## Attribution Requirements

Every provider should support:

```text
provider
source_url
license
attribution_text
terms_url
```

Display or expose required attribution in UI and API outputs where terms require it. Summaries must link to public evidence and must not imply RADAR is the original publisher.

## Lifecycle

Register and verify a source; implement and mock-test its adapter; validate normalization, attribution, caching, rate limits, and security; enable controlled collection; observe health; then pause, degrade, fail, or disable when warranted. See [SIGNAL_ENGINE.md](SIGNAL_ENGINE.md) and [CODING_STANDARDS.md](CODING_STANDARDS.md).