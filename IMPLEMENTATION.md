# RADAR Native PostgreSQL + Redis Completion Report

## Final runtime classification

## Phase 2.1 location registry status

The repository now has a provider-neutral, versioned location registry path. The official Ditjen Bina Administrasi Kewilayahan homepage confirms **Kepmendagri 300.2.2-2138 Tahun 2025**, “Pemberian dan Pemutakhiran Kode, Data Wilayah Administrasi Pemerintahan, dan Pulau”: https://ditjenbinaadwil.kemendagri.go.id/ . The page fetched during this phase did not expose a reproducible CSV, XLSX, JSON, or API download URL. No national official file was therefore imported, and national coverage is **not claimed**.

The existing BMKG documentation reference, Kepmendagri 100.1.1-6117 Tahun 2022, is retained as historical/provider documentation only; it is not silently treated as identical to the 2025 Kemendagri publication. Kepmendagri 300.2.2-2430 Tahun 2025 was not independently verified from an official machine-readable source during this phase.

Import workflow:

```text
official file supplied explicitly → streamed CSV/JSON parser → AdministrativeCode → local duplicate/orphan validation → transaction → optional activation → audit
```

Use `php artisan radar:locations:import --file=/absolute/path/file.csv --dataset=version --dry-run --strict` before activation. The importer stores SHA-256 checksum, source row, source code, and dataset provenance; it does not auto-create BMKG mappings. Provider mappings are explicit records and should be created only after provider compatibility is verified. Active ADM4 locations remain compatible with `radar:forecast:bmkg:{adm4}` and the existing four-hour Redis cache/lock flow.

Future update runbook: discover the official publication, download through an approved operator workflow, checksum, dry-run, audit, diff against the active version, import without activation, verify, activate transactionally, audit again, then perform a small multi-province forecast smoke test. Removed locations are retained inactive rather than deleted. Changed codes are audit candidates; no fuzzy remapping is performed.

## Phase 2.2 acquisition and controlled import status

The official source was checked again on 2026-09-10. The Ditjen Bina Administrasi Kewilayahan homepage confirms Kepmendagri 300.2.2-2138 Tahun 2025, but no reproducible official CSV/XLSX/JSON/API download URL was exposed in the fetched page. Kemendagri's main site could not be fetched by the research client. No official national file was acquired, so coverage remains `PARTIAL` and no national totals are asserted.

Implemented operator commands:

```text
php artisan radar:locations:acquire --file=/absolute/path/file.csv --regulation="Kepmendagri 300.2.2-2138 Tahun 2025"
php artisan radar:locations:acquire --url=https://approved.kemendagri.go.id/file.csv
php artisan radar:locations:profile /absolute/path/file.csv
php artisan radar:locations:stage /absolute/path/file.csv --dataset=review-v1
php artisan radar:locations:import --file=/absolute/path/file.csv --dataset=review-v1 --strict --activate
php artisan radar:locations:diff old-version new-version --json
php artisan radar:locations:activate old-version
php artisan radar:locations:map-provider BMKG
php artisan radar:locations:coverage
```

Acquisition records store provider, regulation, source URL, format, MIME-checked file identity, SHA-256, size, local non-public storage path, and status. The official URL validator allows only configured `kemendagri.go.id` hosts and HTTP(S); remote acquisition is deliberately not enabled until a verified official download URL and transport policy are configured. CSV/JSON/XLSX extension/MIME compatibility and file-size limits are checked. Import staging persists batch status, row-level source identity, normalized code/name, validation status, and errors. A failed staging batch blocks canonical import. Activation and reactivation are transactional; old locations are retained with lifecycle state rather than deleted.

Provider mapping is explicit and separate from canonical import. Forecast readiness now requires an active canonical Kelurahan plus an active BMKG ADM4 mapping. This preserves RADAR-owned identity and the existing Redis forecast-key contract.

```text
PostgreSQL server          VERIFIED
PostgreSQL 18.6            VERIFIED
radar database             VERIFIED
radar_test database        VERIFIED
PHP pdo_pgsql/pgsql        VERIFIED
PostgreSQL migrations      VERIFIED
PostgreSQL tests           VERIFIED
Redis-compatible server    VERIFIED
Redis cache                VERIFIED
Redis locks                VERIFIED
Redis queue                VERIFIED
Laravel queue worker       VERIFIED
RuntimeProbeJob            VERIFIED
FetchSourceJob             VERIFIED
Browser Worker             VERIFIED
Redis → Browser ingestion  VERIFIED
PostgreSQL persistence     VERIFIED
Laravel API                VERIFIED
Next.js frontend build     VERIFIED
```

## PostgreSQL

Detected and verified:

```text
Service: postgresql-x64-18
Version: PostgreSQL 18.6
Port: 5432
Role: radar_user
```

Created and verified database ownership:

```text
radar       owner radar_user
radar_test  owner radar_user
```

The local password was used only for setup and is stored in ignored `backend/.env`; it is not present in `.env.example`, source code, or this report.

## PHP PostgreSQL support

Enabled in the active PHP 8.3.32 configuration:

```text
pdo_pgsql
pgsql
```

## PostgreSQL migrations and schema

Executed successfully against `radar`:

```text
php artisan migrate:fresh --seed --force
```

The PostgreSQL schema contains 17 tables, including:

```text
sources
source_pages
fetch_logs
snapshots
changes
signals
signal_sources
signal_events
failed_jobs
```

Verified PostgreSQL details include `timestamptz` timestamps, JSON columns, foreign keys, cascade/null actions, indexes, Signal fingerprint uniqueness, Snapshot identity uniqueness, Change transition uniqueness, and the SignalSource composite primary key.

## PostgreSQL test database

Added `backend/phpunit.pgsql.xml`, targeting:

```text
DB_CONNECTION=pgsql
DB_DATABASE=radar_test
DB_USERNAME=radar_user
```

Executed:

```text
php vendor/bin/phpunit --configuration phpunit.pgsql.xml --testdox
```

Result:

```text
11 tests
36 assertions
OK
```

## Redis-compatible runtime

Installed and running:

```text
Memurai Developer 4.1.2
```

```text
SERVICE_NAME: Memurai
STATE: RUNNING
127.0.0.1:6379 LISTENING
```

Laravel uses `predis/predis 3.6.0` with:

```text
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Redis cache and locks

Verified with `php artisan radar:runtime:check`:

```text
Database      OK pgsql
Cache         OK redis
Lock          OK redis
Browser Worker OK health ok
```

The lock probe verified acquisition, overlapping rejection, release, and reacquisition.

## Redis queue

`RuntimeProbeJob` was dispatched to Redis and consumed by a real worker. `FetchSourceJob` was also dispatched to `radar-fetch` and completed.

Worker command:

```text
php artisan queue:work redis --queue=radar-fetch,radar-browser,radar-notification,radar-maintenance --tries=3 --timeout=90
```

## Browser ingestion through Redis

The controlled browser source was dispatched with:

```text
php artisan radar:demo:browser dynamic --queue
```

The `radar-browser` worker completed:

```text
Redis → FetchSourceJob → BrowserWorkerClient → Node + Playwright → PostgreSQL
```

Real extraction returned:

```text
Title: RADAR Dynamic Fixture
Links: 1
Documents: 1
```

## Demo ingestion and idempotency

Executed against PostgreSQL:

```text
php artisan radar:demo:ingest
php artisan radar:demo:ingest
php artisan radar:demo:ingest --fixture-version=2
```

Unchanged content remained idempotent; the changed fixture created a new Snapshot/change and updated the existing Signal timeline.

## Scheduler

Verified:

```text
php artisan schedule:list
php artisan schedule:run
```

Registered task:

```text
* * * * * php artisan radar:sources:dispatch
```

## Failed jobs and source health

The PostgreSQL `failed_jobs` table exists and `php artisan queue:failed` returned no failed jobs at final verification. Source health tracking remains implemented for failures, recovery, degraded/failed thresholds, and paused/disabled protection.

## API and frontend

Verified PostgreSQL-backed API routes:

```text
GET /api/v1/signals
GET /api/v1/signals/{signal}
GET /api/v1/sources
GET /api/v1/sources/{source}
```

Frontend verification:

```text
npm run lint   PASS
npm run build  PASS
```

## Regression results

Existing deterministic core:

```text
7 passed, 0 failed
```

PostgreSQL Laravel suite:

```text
11 tests passed
36 assertions passed
```

Browser worker:

```text
4 tests passed
npm run build passed
```

Composer validation passed.

## Horizon

Horizon was not installed. Native Laravel Redis workers are operational and remain the documented local worker path.

## Known limitations

- The supplied local password is weak and should be replaced before deployment.
- No live public providers were added.
- No BMKG, government, jobs, Website Watch, notifications, AI, or map provider layers were added.
- Long-running workers should be managed by a Windows process supervisor.

## BMKG Phase 1 status

Official BMKG documentation was verified from:

```text
https://data.bmkg.go.id/
https://data.bmkg.go.id/gempabumi/
https://data.bmkg.go.id/peringatan-dini-cuaca/
```

Verified official structured sources:

```text
https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json
https://data.bmkg.go.id/DataMKG/TEWS/gempaterkini.json
https://data.bmkg.go.id/DataMKG/TEWS/gempadirasakan.json
https://www.bmkg.go.id/alerts/nowcast/id
https://www.bmkg.go.id/alerts/nowcast/en
https://www.bmkg.go.id/alerts/nowcast/id/{kode_detail_cap}_alert.xml
https://www.bmkg.go.id/alerts/nowcast/en/{kode_detail_cap}_alert.xml
```

BMKG documentation confirms 60 requests/minute/IP for earthquake and warning data, and requires BMKG attribution.

Added provider adapters:

- `BmkgEarthquakeAdapter` for latest, M5+, and felt JSON feeds;
- `BmkgWeatherWarningAdapter` for RSS → CAP XML;
- deterministic earthquake identity and priority;
- CAP identifier identity;
- CAP severity/urgency/certainty priority mapping;
- GeoJSON-compatible warning polygon normalization;
- BMKG attribution and source metadata preservation.

Registered paused sources:

```text
bmkg-earthquake-latest
bmkg-earthquake-m5
bmkg-earthquake-felt
bmkg-weather-warning
```

Smoke commands:

```text
php artisan radar:bmkg:test-earthquake
php artisan radar:bmkg:test-warning
php artisan radar:bmkg:register
```

Live smoke results:

```text
BMKG Earthquake: OK
BMKG Weather Warning: OK
```

No live BMKG data was persisted automatically. Sources remain `PAUSED` until explicitly activated.

## BMKG Live Productization

### Enabled sources

```text
bmkg-earthquake-latest: ACTIVE
```

Still paused:

```text
bmkg-earthquake-m5
bmkg-earthquake-felt
bmkg-weather-warning
```

### Real earthquake queue verification

Executed:

```text
php artisan radar:source enable bmkg-earthquake-latest
php artisan radar:sources:dispatch
php artisan queue:work redis --queue=radar-fetch --tries=1 --timeout=90 --stop-when-empty
```

Real PostgreSQL result for the BMKG earthquake source:

```text
FetchLogs       1 SUCCESS / HTTP 200
Snapshots       1
Changes         1
Signals         1
SignalSources   1
SignalEvents    1
Source health   ACTIVE / success
```

The persisted event was returned by the live BMKG feed. No fixture was used for this source.

### API resources and filters

Added:

```text
GET /api/v1/earthquakes
GET /api/v1/earthquakes/{signal}
GET /api/v1/weather/alerts
GET /api/v1/weather/alerts/{signal}
GET /api/v1/signals/map
```

Filters include magnitude bounds, priority, felt status, severity, urgency, certainty, active expiry filtering, date ranges, pagination, and bounded `bbox` queries.

Map output supports:

```text
earthquake
weather-alert
```

### Frontend

Added:

```text
/earthquakes
/weather-alerts
```

Both pages read only Laravel API resources and handle API failure/empty states. BMKG attribution is exposed in API payloads and displayed in the product pages.

The MapLibre shell requests the bounded RADAR map API and renders restrained earthquake point markers. Weather polygon rendering remains prepared by the API geometry contract but is not yet added to the frontend layer.

### Tests

PostgreSQL suite:

```text
14 tests passed
49 assertions passed
```

Standard Laravel suite:

```text
14 tests passed
49 assertions passed
```

Coverage includes BMKG adapters, filters, active warnings, bbox queries, map output, queue dispatch, ingestion idempotency, transport failures, and provenance.

### Live API verification

Verified HTTP 200 responses from:

```text
GET /api/v1/earthquakes
GET /api/v1/signals/map?bbox=95,-11,141,6&layers=earthquake,weather-alert
```

The map response contained the persisted BMKG earthquake point.

### Weather warning status

The official weather-warning smoke test is verified, but `bmkg-weather-warning` remains paused. No live weather warning has been persisted through the Redis queue path in this phase, so no live warning persistence is claimed.

### Current limitations

- Weather warning queue persistence is pending explicit activation.
- Weather polygon MapLibre rendering is not yet implemented; normalized geometry and map API output are available.
- Nationwide weather forecast and `adm4` location support remain deferred.
- No other public provider was added.

## Exact next phase

## BMKG CAP Warning → Redis → PostgreSQL → Signal → Expiry → MapLibre

The CAP warning path is now verified.

Enabled sources during verification:

```text
bmkg-earthquake-latest: ACTIVE
bmkg-weather-warning: ACTIVE
```

Still paused:

```text
bmkg-earthquake-m5
bmkg-earthquake-felt
```

The weather warning source was dispatched through Redis and consumed by the real `radar-fetch` worker.

PostgreSQL results for the BMKG warning source:

```text
Snapshots: 3
Changes: 3
Source status: ACTIVE
Last success: recorded
Failure count: 0
```

Overall BMKG persistence after earthquake and warning ingestion:

```text
BMKG Signals: 4
BMKG SignalSources: 4
BMKG SignalEvents: 4
```

The warning API returned zero results for `active=true` at verification time because the currently persisted warnings had expired. This was treated as a valid empty active state, not as a failure.

The map API returned persisted warning geometry:

```text
GET /api/v1/signals/map?bbox=95,-11,141,6&layers=earthquake,weather-alert
HTTP 200
```

MapLibre now renders:

- earthquake point markers;
- weather-warning polygon fills when geometry exists;
- warning popups with headline, severity, effective, and expiry.

Frontend routes are available:

```text
/earthquakes
/weather-alerts
```

Final verification:

```text
Laravel tests: 14 passed, 49 assertions
PostgreSQL suite: 14 passed, 49 assertions
Deterministic core: 7 passed, 0 failed
Frontend lint: PASS
Frontend build: PASS
Browser worker tests: 4 passed
Browser worker build: PASS
```

## Current limitations

- Weather alerts are currently expired, so the active-alert list is empty until BMKG publishes a currently valid warning.
- No nationwide forecast or `adm4` location ingestion was added.
- No notification delivery was added.
- No additional public provider was added.

## Exact next phase

Keep BMKG earthquake and warning sources under observed source health, add warning revision/expiry timeline tests for additional CAP variants, and defer nationwide forecast until location `adm4` support is ready.

## Phase 2 — Location Registry + BMKG Forecast

## Phase 2.1 — Indonesia Location Registry Expansion

The official source research for this phase found:

- BMKG's official forecast documentation: https://data.bmkg.go.id/prakiraan-cuaca/;
- BMKG identifies ADM4 codes and cites Kepmendagri 100.1.1-6117 Tahun 2022;
- Ditjen Bina Administrasi Kewilayahan's official site announces Kepmendagri 300.2.2-2138 Tahun 2025, concerning updated administrative codes and data: https://ditjenbinaadwil.kemendagri.go.id/.

The fetched official government pages did not expose a reproducible national CSV/XLSX/JSON download in this environment. No third-party dataset was imported and national coverage is not claimed. The verified Kemayoran sample remains partial coverage.

The registry now has versioned `location_datasets` metadata, explicit `LocationDatasetImporter` and file importer boundaries, checksum support for supplied CSV/JSON files, canonical `AdministrativeCode` validation, dataset provenance on locations, `location_provider_mappings`, deterministic `forecast_ready`, duplicate/orphan checks, transactional import, strict mode, audit/stats commands, and bounded ranked search filters.

Controlled importer coverage is included in `backend/tests/Feature/LocationImportTest.php` with a repository-local hierarchy fixture. It verifies parent ordering, ADM4/BMKG mapping, duplicate detection, dry-run non-persistence, strict integrity failure, and idempotent re-import. Imported rows derive ADM1/ADM2/ADM3/ADM4 fields from the canonical code and retain CSV source-row provenance.

Import runbook:

```text
obtain and verify official CSV/XLSX/JSON
php artisan radar:locations:import --file=... --dataset=... --dry-run --strict
php artisan radar:locations:import --file=... --dataset=... --strict
php artisan radar:locations:audit
php artisan radar:locations:stats
GET /api/v1/locations?q=...&level=KELURAHAN&forecast_ready=1
```

Only a supplied local CSV/JSON file is accepted by the generic importer; it does not fetch arbitrary URLs. PDF remains a controlled conversion input rather than a production scraper. Dataset activation supersedes the prior active dataset in one transaction, while old location rows and dataset metadata are retained.

Forecast compatibility remains BMKG ADM4-only, on-demand, Redis-cached for four hours, and keyed by `radar:forecast:bmkg:{adm4}`. Forecast data does not create Signals.

### Official source verified

BMKG forecast documentation was verified from:

```text
https://data.bmkg.go.id/prakiraan-cuaca/
```

Verified endpoint:

```text
https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={kode_wilayah_tingkat_iv}
```

Verified behavior:

- JSON format;
- three-day horizon;
- three-hour forecast interval;
- eight forecast points per day;
- twice-daily updates;
- 60 requests/minute/IP;
- ADM4 string location key;
- BMKG attribution required.

### Location registry

Added `locations` with:

- hierarchy parent support;
- enum location levels;
- deterministic administrative codes;
- ADM1/ADM2/ADM3/ADM4 fields;
- normalized search name;
- coordinates/timezone metadata;
- active status and indexes.

Added verified development sample:

```text
Kemayoran
ADM4: 31.71.03.1001
Province: DKI Jakarta
Regency: Kota Adm. Jakarta Pusat
District: Kemayoran
Timezone: Asia/Jakarta
```

Command:

```text
php artisan radar:locations:import --dry-run
php artisan radar:locations:import
```

This is explicitly a verified development sample. National location coverage is not claimed.

### Location API

Added:

```text
GET /api/v1/locations
GET /api/v1/locations/{location}
GET /api/v1/locations/{location}/forecast
```

Location search supports query and level filtering with bounded pagination.

### Forecast adapter/service

Added:

- `BmkgForecastAdapter`;
- `ForecastService`;
- Redis cache key `radar:forecast:bmkg:{adm4}`;
- per-location Redis lock;
- four-hour cache TTL aligned with BMKG’s twice-daily update cadence;
- normalized ordered forecast points;
- BMKG attribution and cache metadata.

Forecast is contextual data and does not create Signals for ordinary temperature, humidity, or weather-description changes.

### Live forecast verification

Executed twice:

```text
php artisan radar:bmkg:test-forecast 31.71.03.1001
```

Result:

```text
BMKG Forecast: OK
Location: Kemayoran (31.71.03.1001)
Points: 22
Provider: BMKG
Cached: yes
```

The forecast response contained real BMKG points including temperature, humidity, weather description, wind, cloud cover, visibility, local/UTC time, and analysis date.

The repeated request used Redis cache rather than issuing another BMKG request.

### Phase 2 frontend

Added:

```text
/weather
```

The page provides:

- verified location selection state;
- forecast loading/error/empty behavior through server rendering;
- three-hour forecast cards;
- temperature, weather, humidity, and wind display;
- BMKG attribution;
- cached/fresh state.

### Phase 2 tests

Added offline forecast normalization coverage.

Final backend results:

```text
Laravel suite:       15 passed, 52 assertions
PostgreSQL suite:    15 passed, 52 assertions
Deterministic core:  7 passed, 0 failed
```

Frontend:

```text
npm run lint: PASS
npm run build: PASS
```

### Phase 2 limitations

- Only one verified ADM4 development sample is imported.
- National administrative coverage is not claimed.
- No nationwide forecast polling exists.
- Forecast data is cache-backed and on-demand only.
- No historical forecast analytics or forecast-derived Signal rules were added.
- No PostGIS or nationwide ADM4 polygons were added.

## Exact next phase

Obtain and verify an authoritative machine-readable Indonesian administrative dataset, expand the location importer with dry-run validation and hierarchy checks, then add conservative configurable significant-weather rules only if product requirements justify forecast-derived Signals.