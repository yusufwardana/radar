# RADAR Product Definition

## Product Name

**RADAR**

## Product Category

**Live Public Intelligence & Change Monitoring Platform**

## Core Question

> **What changed, what matters, and what should I know now?**

## Product Vision

RADAR reduces the need to manually monitor many public websites, official feeds, open-data portals, and public documents. It collects permitted public information, normalizes provider-specific records, preserves evidence of observations, detects meaningful changes, and transforms public data into relevant Signals.

RADAR is positioned as **Live Public Intelligence & Change Monitoring**. Its central product flow is:

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

Its complete operational pipeline is:

```text
SOURCE → FETCH → NORMALIZE → SNAPSHOT → CHANGE → DEDUP → SIGNAL → INTELLIGENCE
```

## Product Principles

### Source First
Use authoritative, permitted public sources and preserve provider identity, source URL, collection time, attribution, and applicable terms.

### Change First
Prioritize new information and meaningful changes. An unchanged fetch normally must not create a Signal.

### Signal, Not Noise
Reduce duplicate items, repeated notices, cosmetic edits, and low-value volume before presenting information to users.

### Explainable Intelligence
Every Signal must be traceable to public evidence. Distinguish source facts, deterministic rules, and AI-assisted inference. Keep confidence and importance separate.

### API First
Prefer official APIs and open-data endpoints, then RSS/Atom/CAP, then sitemaps and public HTML. Browser automation is a last resort.

### Responsible Collection
Collect only permitted public information. Respect terms, robots guidance where applicable, rate limits, attribution, and access boundaries. Never evade access controls.

### Location Awareness
Signals may be national, regional, local, point-based, or non-geographic. Preserve stated geographic accuracy and scope rather than inventing precision.

### Evidence Preservation
Retain sufficient metadata, hashes, snapshots, and source links to explain how a Signal was produced, subject to documented retention rules.

## Core Modules

### Local Radar
Location-centered public information filtered by selected areas, enabled modules, categories, and interests.

### Government Watch
Government announcements, regulations, documents, deadlines, service information, and updates from verified public sources.

### Job Intelligence
Public job listings, requirements, skill trends, salary changes where publicly available, repost detection, and market analytics, subject to source terms.

### Website Watch
Meaningful-change monitoring for explicitly permitted public URLs, subject to strict SSRF, responsible-collection, rate-limit, and evidence controls.

### Weather Intelligence
Weather observations and forecasts from trusted official providers, normalized into consistent temporal and geographic records.

### Public Alerts
Weather alerts and other structured public alerts, including CAP where published.

### Earthquake Intelligence
Earthquake observations and related public warnings from trusted official sources, retaining published time, magnitude, location, and updates.

### Document Intelligence
Detection of newly published public PDFs, DOCX, XLSX, CSV, and similar documents, with metadata, provenance, and meaningful-change evidence.

### Event Intelligence
Permitted public event notices and feeds normalized for timing, location, category, and updates.

## Signal Concept

A **Signal** is RADAR’s central intelligence object: an attributable, deduplicated representation of a meaningful public observation or change. It is not merely a fetched page or feed item. A Signal may be supported by multiple sources and has an update timeline.

```text
NEW
UPDATE
CHANGE
DOCUMENT
DEADLINE
TREND
ANOMALY
ALERT
```

See [SIGNAL_ENGINE.md](SIGNAL_ENGINE.md) for lifecycle rules.

## Target Users

- residents;
- job seekers;
- communities;
- SMEs;
- journalists;
- researchers; and
- organizations.

## Non-Goals

RADAR is **not**:

- a generic web scraper;
- a vulnerability scanner;
- a credential crawler;
- an attack platform;
- a private-data collection tool;
- an internet-wide crawler;
- an OSINT attack toolkit; or
- a search engine for the entire web.

## Product Positioning

RADAR is **Live Public Intelligence & Change Monitoring**: responsible, attributable transformation of public-source observations into relevant Signals.

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

Source policy is in [DATA_SOURCES.md](DATA_SOURCES.md), architecture in [ARCHITECTURE.md](ARCHITECTURE.md), and safeguards in [SECURITY.md](SECURITY.md).