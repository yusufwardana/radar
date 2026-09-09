# RADAR Architecture Reference

## Scope and Status

This is RADAR’s target conceptual architecture. It does not claim the recommended framework, queues, providers, or integrations exist. Inspect and preserve the existing project stack where practical before implementation.

```text
                    RADAR UI
                       │
                       ▼
                  RADAR API
                       │
          ┌────────────┼────────────┐
          ▼            ▼            ▼
        Redis      PostgreSQL    Realtime
                       │
                       ▼
               INGESTION ENGINE
                       │
        ┌──────────────┼──────────────┐
        ▼              ▼              ▼
       API          RSS / CAP       HTML
    ADAPTERS         ADAPTERS       WATCH
        │              │              │
        └──────────────┼──────────────┘
                       ▼
                  NORMALIZER
                       ▼
                 SNAPSHOT ENGINE
                       ▼
                  CHANGE ENGINE
                       ▼
                DEDUPLICATION
                       ▼
                  CLUSTERING
                       ▼
                  SIGNAL ENGINE
                       ▼
          ┌────────────┼────────────┐
          ▼            ▼            ▼
         MAP          FEED         ALERT
```

## Layers

- **RADAR UI:** feeds, maps, search, dashboards, saved views, and alerts. It calls RADAR APIs only.
- **RADAR API:** validates, authorizes, filters, paginates, and serializes. Controllers do not contain provider parsing or domain rules.
- **Redis:** recommended cache, queue transport, rate limiting, locks, and short-lived upstream-response storage; never the system of record.
- **PostgreSQL:** preferred record of sources, normalized items, snapshots, changes, Signal sources, Signals, and audit-relevant state.
- **Realtime:** optional authorized delivery of completed Signal updates.
- **Ingestion Engine:** schedules work, applies source configuration and health controls, invokes adapters, and persists outcomes.
- **Adapters:** isolate provider transport, parsing, pagination, cursors, and error mapping. HTML is a controlled fallback.
- **Normalizer:** converts external payloads to stable internal DTOs before domain logic.
- **Snapshot Engine:** persists hashes, normalized content, metadata, fetch status, and observation time.
- **Change Engine:** compares snapshots and creates explicit change candidates.
- **Deduplication:** removes duplicate representations of the same item.
- **Clustering:** groups distinct source records describing one event.
- **Signal Engine:** creates, updates, scores, and timelines attributable Signals.
- **Map/Feed/Alert:** serve bounded map data, paginated feeds, and preference-matched notifications.

## Mandatory Architectural Rules

1. Frontend never calls third-party providers directly.
2. External providers must use Source Adapters.
3. Heavy operations use queues.
4. External API responses must be normalized before entering domain logic.
5. Signals always preserve source provenance.
6. AI must not run on every fetch.
7. Browser automation is a fallback.
8. Cache upstream responses.
9. Avoid duplicate upstream requests.
10. Keep provider-specific logic outside controllers.

## Recommended Stack

The following is recommended, not verified as the current project stack:

| Area | Recommendation |
|---|---|
| Backend | Laravel 12, PHP 8.3+ |
| Database | PostgreSQL preferred |
| Cache | Redis |
| Queues | Laravel Queue, Laravel Horizon |
| Scheduler | Laravel Scheduler |
| Frontend option A | Next.js + TypeScript + MapLibre |
| Frontend option B | Laravel + Inertia + Vue 3 + TypeScript + Tailwind |
| Browser worker | Node.js + Playwright |

## Suggested Folder Architecture

```text
app/
└── Radar/
    ├── Contracts/
    ├── Sources/
    ├── Adapters/
    ├── DTOs/
    ├── Normalizers/
    ├── Ingestion/
    ├── Snapshots/
    ├── Changes/
    ├── Signals/
    ├── Deduplication/
    ├── Clustering/
    ├── Scoring/
    └── Alerts/
```

## Queue Architecture

```text
radar-discovery
radar-fetch-high
radar-fetch
radar-parse
radar-diff
radar-signal
radar-ai
radar-notification
radar-maintenance
```

Retries should distinguish transient failures from permanent configuration or policy failures. Set connection, request, job, and worker timeouts. Use bounded exponential backoff with jitter and honor `Retry-After`. Jobs need stable idempotency keys so retries do not duplicate snapshots, Signals, or notifications. A circuit breaker must reduce or pause repeated failures, then use controlled probes. Track source health through success/failure times, counts, status, reason, and latency where available.

## Map Architecture

Map queries use bounding boxes and enabled layers. Never load all map records at once.

Conceptual, not implemented endpoint:

```text
GET /api/v1/signals/map
?bbox=...
&layers=government,jobs,earthquake
```

Validate bounding boxes, maximum area, allowed layers, authorization, result caps, and indexes. Return marker/cluster fields only; request full Signal details separately.

See [DATA_SOURCES.md](DATA_SOURCES.md), [SIGNAL_ENGINE.md](SIGNAL_ENGINE.md), [SECURITY.md](SECURITY.md), and [CODING_STANDARDS.md](CODING_STANDARDS.md).