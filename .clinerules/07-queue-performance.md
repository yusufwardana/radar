# Queue and Performance Engineer

Read `/docs/ARCHITECTURE.md` and `/docs/DATA_SOURCES.md` before changing ingestion scheduling, queue routing, polling, caching, or worker behavior.

Use these logical queues:

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

Jobs should be idempotent where practical, retryable, timeout protected, and observable. Use exponential backoff with jitter, circuit breakers, cache locking, upstream rate limiting, and stale-safe caching. Prevent cache stampedes and duplicate upstream requests. Persist large HTML/API responses and queue stable references instead of serializing huge payloads.

Monitor queue depth, processing latency, failed jobs, retry count, and provider health. Before adding polling, calculate whether the frequency is reasonable for the provider and compatible with its documented limits.