<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\FetchSourceJob;
use App\Models\Change;
use App\Models\FetchLog;
use App\Models\Signal;
use App\Models\SignalEvent;
use App\Models\Snapshot;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RadarIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_ingestion_is_idempotent_and_exposes_provenance_and_timeline(): void
    {
        $this->artisan('radar:demo:ingest')->assertSuccessful();
        $this->artisan('radar:demo:ingest')->assertSuccessful();

        $this->assertDatabaseCount('snapshots', 1);
        $this->assertDatabaseCount('changes', 1);
        $this->assertDatabaseCount('signals', 1);
        $this->assertDatabaseCount('signal_sources', 1);
        $this->assertDatabaseCount('signal_events', 1);

        $response = $this->getJson('/api/v1/signals');
        $response->assertOk()->assertJsonPath('data.0.title', 'Demo public event');
        $response->assertJsonPath('data.0.sources.0.slug', 'demo-json-source');
        $response->assertJsonPath('data.0.timeline.0.type', 'FIRST_DETECTED');
    }

    public function test_changed_fixture_creates_a_new_snapshot_change_and_timeline_event_on_same_signal(): void
    {
        $this->artisan('radar:demo:ingest')->assertSuccessful();
        $this->artisan('radar:demo:ingest', ['--fixture-version' => 2])->assertSuccessful();

        $this->assertDatabaseCount('snapshots', 2);
        $this->assertDatabaseHas('changes', ['change_type' => 'DATE_CHANGED']);
        $this->assertDatabaseCount('signals', 1);
        $this->assertDatabaseCount('signal_events', 2);
        $this->assertDatabaseHas('signal_events', ['event_type' => 'CONTENT_CHANGED']);
    }

    public function test_http_failures_are_logged_and_update_source_health_without_creating_intelligence(): void
    {
        Http::fake(['https://fixture.test/*' => Http::response(['error' => 'broken'], 500)]);
        $source = Source::create([
            'name' => 'HTTP Failure Fixture', 'slug' => 'http-failure', 'provider' => 'Test',
            'source_type' => SourceType::API, 'endpoint' => 'https://fixture.test/feed',
            'status' => SourceStatus::ACTIVE, 'metadata' => [],
        ]);

        try {
            FetchSourceJob::dispatchSync($source->id);
        } catch (\Throwable) {
            // The queue job must fail/retry; the FetchLog and health assertions are the contract.
        }

        $this->assertDatabaseHas('fetch_logs', ['source_id' => $source->id, 'status' => 'FAILED']);
        $this->assertDatabaseHas('sources', ['id' => $source->id, 'failure_count' => 1]);
        $this->assertDatabaseCount('snapshots', 0);
        $this->assertDatabaseCount('signals', 0);
    }

    public function test_rate_limited_http_source_is_distinguished_from_other_failures(): void
    {
        Http::fake(['https://fixture.test/*' => Http::response('', 429, ['Retry-After' => '30'])]);
        $source = Source::create([
            'name' => 'HTTP Rate Fixture', 'slug' => 'http-rate', 'provider' => 'Test',
            'source_type' => SourceType::API, 'endpoint' => 'https://fixture.test/feed',
            'status' => SourceStatus::ACTIVE, 'metadata' => [],
        ]);

        try {
            FetchSourceJob::dispatchSync($source->id);
        } catch (\Throwable) {
        }

        $this->assertDatabaseHas('fetch_logs', ['source_id' => $source->id, 'status' => 'RATE_LIMITED', 'error_code' => 'UPSTREAM_RATE_LIMITED']);
    }
}