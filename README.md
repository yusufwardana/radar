# RADAR

Live Public Intelligence & Change Monitoring.

```text
SOURCE → CHANGE → SIGNAL → INTELLIGENCE
```

## Services

- `backend/` — Laravel 12 application brain, API, persistence, orchestration, and Signal Engine boundary.
- `frontend/` — Next.js + TypeScript command-center UI. It calls only the RADAR API.
- `workers/browser/` — Node.js + Playwright extraction worker. It extracts permitted public page content only; it does not score, deduplicate, cluster, or alert.
- `docker/` — optional PostgreSQL, Redis, and browser-worker development services.
- `src/Radar/` — preserved deterministic PHP domain core used through backend Composer autoloading.

## Local verification

```text
php tests/run.php
cd backend && composer install && php artisan about && php artisan route:list
cd frontend && npm run lint && npm run build
cd workers/browser && npm install && npm run build
```

Copy environment examples before booting services. No provider endpoints or demo intelligence are seeded by this foundation.

## Optional infrastructure

```text
docker compose -f docker/docker-compose.yml up -d
```

Use PostgreSQL and Redis credentials from the local environment examples. Never commit real secrets.