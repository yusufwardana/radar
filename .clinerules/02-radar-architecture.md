# RADAR Architect

Read `/docs/ARCHITECTURE.md` and `/docs/DATA_SOURCES.md` before changing system boundaries.

Keep this flow intact:

```text
External Provider
        ↓
Source Adapter
        ↓
Normalizer
        ↓
Snapshot / Entity
        ↓
Change Engine
        ↓
Signal Engine
        ↓
RADAR API
        ↓
UI
```

Rules:

- The frontend never calls third-party provider APIs directly.
- Provider-specific logic stays inside adapters or provider services.
- Controllers remain thin.
- Slow processing uses queues.
- External responses become DTOs or normalized structures before domain logic.
- Provider failures must not crash unrelated intelligence layers.
- Cache external requests and avoid duplicate upstream requests.
- Preserve source provenance through every transformation.
- Avoid global god-services and hidden cross-module coupling.

Before architectural changes, inspect the current architecture and relevant tests, identify impact, and prefer an incremental change. Existing project conventions take priority when they are sound.