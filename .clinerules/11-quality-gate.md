# RADAR Quality and Anti-Slop Gate

Use this rule with `/docs/PRODUCT.md`, `/docs/ARCHITECTURE.md`, `/docs/SECURITY.md`, and `/docs/CODING_STANDARDS.md`. It adapts anti-slop quality practices to RADAR without duplicating the upstream project’s UI-specific rules.

## Intentionality

- Every significant implementation or product decision must have a clear reason tied to the requested outcome, RADAR’s product flow, or an explicit technical constraint.
- Do not add generic abstractions, screens, fields, notifications, dashboards, dependencies, or copy merely because they are common AI-generated patterns.
- Prefer the smallest coherent change that strengthens an existing RADAR module.

## Evidence Over Claims

- Never fabricate provider integrations, API endpoints, source facts, statistics, testimonials, metrics, availability, or implementation status.
- Public intelligence must preserve source provenance and distinguish observed facts from derived classifications, summaries, and AI-assisted analysis.
- If evidence is unavailable, mark the result as unknown, planned, or unverified rather than filling the gap with plausible text.

## Functional Completeness

- Do not present a control, route, action, filter, alert, map interaction, or status as working unless it is implemented and verified.
- Account for relevant loading, empty, error, permission, timeout, stale-data, unavailable-source, and retry states.
- If a requested behavior is intentionally deferred, expose the limitation in the implementation report rather than hiding it behind a placeholder.

## User-Facing Quality

- Avoid generic AI copy, unsupported superlatives, empty marketing language, and unexplained technical jargon.
- User-facing summaries must be specific, attributable, and proportionate to the evidence.
- Interfaces should be readable, keyboard-usable, responsive, and resilient where UI code exists. Do not impose visual styles or invent a design system without repository evidence or explicit product direction.

## Delivery Gate

Before reporting a substantial task as complete, check all applicable items:

- [ ] The change has a documented purpose and belongs to RADAR’s product scope.
- [ ] The source, meaningful change, Signal impact, user value, and module ownership are understood.
- [ ] No provider endpoint, integration, claim, or source fact was fabricated.
- [ ] Provenance, authorization, validation, security, and failure handling are preserved.
- [ ] Unchanged and duplicate inputs do not create avoidable Signals or notifications.
- [ ] Relevant tests, formatting, static checks, migrations, and routes were run or their absence is reported.
- [ ] Interactive behavior and important states were exercised where applicable.
- [ ] Actual modified files and known limitations are reported.

Any unchecked applicable item is a delivery failure, not a reason to weaken the requirement. Fix it or report the work as incomplete. This gate complements, and does not replace, the verification requirements in `10-workflow.md` and the testing requirements in `09-testing.md`.