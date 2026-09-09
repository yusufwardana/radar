# RADAR Signal Engine

## Purpose

A **Signal** is RADAR’s primary intelligence entity: an attributable, deduplicated representation of a meaningful public observation or change. It is not a raw provider response, fetch log, or snapshot.

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

## Pipeline

```text
RAW ITEM
   ↓
NORMALIZE
   ↓
HASH
   ↓
COMPARE
   ↓
DIFF
   ↓
CLASSIFY
   ↓
DEDUPLICATE
   ↓
CLUSTER
   ↓
SCORE
   ↓
SIGNAL
```

Each stage preserves identifiers and evidence. Fetching a raw item alone never guarantees Signal creation.

## Signal Types

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

## Priority

```text
LOW
MEDIUM
HIGH
CRITICAL
```

Priority is an operational classification for display, queues, and notification policy. It uses documented rules such as authoritative alert severity, deadline proximity, or importance thresholds; it does not replace either score below.

## Signal Fields

```text
id
title
summary
type
category
priority
location_id
latitude
longitude
first_detected_at
last_updated_at
confidence_score
importance_score
source_count
status
metadata
created_at
updated_at
```

`metadata` is structured, versionable, and secret-free. Related Signal Source records preserve provider, URL, snapshot, and attribution evidence.

## Confidence Score

**Confidence Score** is how strongly available evidence supports a Signal, ranging from **0–100**. Factors may include authority, source count, source agreement, freshness, structured-source availability, and historical source reliability. It is evidence-oriented and must not change solely due to a user’s interests.

## Importance Score

**Importance Score** is how significant or relevant a Signal is to RADAR or a user context, ranging from **0–100**. Factors may include user interests, location, category, keyword match, deadline proximity, magnitude, change significance, and recency.

**Never combine Confidence Score and Importance Score into one score.** Store their calculation inputs and versioned logic independently.

## Snapshot Logic

A snapshot is an evidence-bearing observation of a source page or normalized item at a time. Minimum fields:

```text
source_id
source_page_id
content_hash
normalized_hash
title
text_content
metadata
http_status
fetched_at
```

`content_hash` represents captured eligible content. `normalized_hash` removes documented, non-meaningful variation only. Snapshot writes must be idempotent. Retention must avoid storing raw HTML forever while retaining hashes and audit evidence needed for comparison.

## Change Types

The Change Engine compares eligible current and prior state and emits explicit records:

```text
TEXT_CHANGED
DATE_CHANGED
PRICE_CHANGED
DOCUMENT_ADDED
DOCUMENT_REMOVED
LINK_ADDED
LINK_REMOVED
STATUS_CHANGED
NEW_ITEM
REMOVED_ITEM
SIGNIFICANT_CHANGE
```

`SIGNIFICANT_CHANGE` requires documented source/category rules; cosmetic edits must not trigger it.

## Deduplication

Apply stages in order:

1. external ID;
2. canonical URL;
3. exact fingerprint;
4. normalized title;
5. deterministic similarity;
6. semantic similarity if needed.

Never merge records merely because of broad keyword overlap. Retain matching method, threshold, candidate identifiers, decision, and decision version.

## Clustering

Clustering groups distinct source records that describe one event or topic. Multiple sources should usually yield one Signal with multiple Signal Sources. It is broader than deduplication and may use time, location, entities, topic, canonical links, and authority. Clusters must support later splits or merges without losing history.

## Signal Timeline

A Signal maintains ordered developments with references to sources, snapshots, changes, or Signal Sources:

```text
09:14 First detected
09:31 Second source discovered
10:04 Official source discovered
10:45 Information changed
12:17 New document added
```

Timeline entries identify sourced facts, deterministic processing, or AI-assisted enrichments.

## AI Rules

AI may assist classification, summarization, entity extraction, semantic deduplication, clustering, and importance scoring. AI **MUST NOT run on every fetch**.

```text
FETCH
 ↓
NORMALIZE
 ↓
HASH
 ↓
CHANGE?
 ├── NO → STOP
 └── YES
       ↓
    RULE ENGINE
       ↓
    AI IF REQUIRED
```

Record input-evidence references, model/prompt or rule version where feasible, timestamps, and uncertainty. AI must not invent source facts, hide provenance, or be the sole basis for a CRITICAL public alert without an explicit reviewed policy.

Stable statuses may include active, updated, resolved, archived, and suppressed, but must be enums with documented transitions. Suppression preserves evidence and reason. See [DATA_SOURCES.md](DATA_SOURCES.md), [ARCHITECTURE.md](ARCHITECTURE.md), and [CODING_STANDARDS.md](CODING_STANDARDS.md).