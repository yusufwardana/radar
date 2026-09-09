# RADAR Workflow Guardian

Before any substantial coding task:

1. Inspect the existing implementation.
2. Read the relevant files in `/docs`.
3. Inspect migrations, routes, models, relevant services, and existing tests.
4. Determine whether the functionality already exists.
5. Create a concise implementation plan.
6. Implement incrementally.
7. Run relevant tests and fix introduced failures.
8. Review security and architecture implications.
9. Report actual changes and known limitations.

Never rewrite working modules without reason, create duplicate modules, remove tests to pass, disable security, hide failures, or claim completion without verification. For large tasks, complete one architectural phase at a time.

Preferred RADAR sequence:

```text
1. Source Registry
2. Source Adapters
3. Queue + Cache
4. Snapshot
5. Change Engine
6. Signal Engine
7. Public API providers
8. Website Watch
9. Government Watch
10. Job Intelligence
11. Local Radar
12. Intelligence UX
```

Global invariants: API first; RSS/CAP before HTML; HTML is fallback; never fabricate endpoints; never access private or authenticated data without an authorized integration; never weaken SSRF; never couple the frontend to provider schemas; preserve provenance; use AI as an analysis layer after deterministic filtering; mock external providers; and do not claim completion without verification.