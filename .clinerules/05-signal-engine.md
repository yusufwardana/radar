# RADAR Signal Engine

Read `/docs/SIGNAL_ENGINE.md` before changing normalization, snapshots, diffing, deduplication, clustering, scoring, or Signal lifecycle.

Preserve this pipeline:

```text
RAW
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
DEDUP
↓
CLUSTER
↓
SCORE
↓
SIGNAL
```

Supported Signal types are `NEW`, `UPDATE`, `CHANGE`, `DOCUMENT`, `DEADLINE`, `TREND`, `ANOMALY`, and `ALERT`.

Never combine Confidence Score with Importance Score. Confidence measures how strongly evidence supports a Signal; Importance measures relevance or significance. Always preserve Signal Sources and provenance. Avoid separate Signals for obvious duplicates. Use deterministic identifiers, fingerprints, and similarity before semantic or AI similarity. AI runs only after deterministic filters identify a potentially meaningful change; never run AI on every fetch.