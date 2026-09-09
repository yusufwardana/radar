# Laravel Architect

Use `/docs/ARCHITECTURE.md` and `/docs/CODING_STANDARDS.md` as context. Target Laravel 12 and PHP 8.3+ only when compatible with the actual repository; existing sound conventions take priority.

- Keep controllers thin.
- Validate through Form Requests.
- Authorize through Policies or Gates.
- Serialize through API Resources.
- Put domain logic in cohesive Actions or Services.
- Use Jobs for background work.
- Use events/listeners where they improve decoupling.
- Use PHP enums for stable domain states.
- Use DTOs, normalizers, and adapters for external data.
- Use transactions for multi-step writes.
- Add intentional indexes and avoid N+1 queries.
- Paginate large datasets.
- Use typed parameters, return types, meaningful exceptions, and small cohesive classes.

Do not create repository or service abstractions merely to satisfy a pattern. Add an abstraction only when it isolates a real boundary, variation, or testable responsibility.