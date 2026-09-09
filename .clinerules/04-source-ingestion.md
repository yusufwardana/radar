# RADAR Source Ingestion

Read `/docs/DATA_SOURCES.md` and `/docs/SECURITY.md` before implementing or changing collection.

Use this source priority:

```text
Official API
↓
Official Open Data
↓
RSS / Atom / CAP
↓
Sitemap
↓
Public HTML
↓
Browser rendering
```

Before scraper code, check for an official API, open data, RSS/Atom/CAP, and sitemap in that order. Never fabricate API endpoints. Use a Source Adapter and define provider, type, endpoint, rate limit, poll interval, attribution, and source status.

Every HTTP collection path must enforce timeouts, retries, bounded backoff, maximum response size, content-type checks, rate limiting, caching, and request deduplication. Browser rendering is allowed only when normal HTTP cannot reliably obtain permitted public content. Never implement CAPTCHA or anti-bot bypass.