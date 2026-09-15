<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Signal;
use App\Models\Source;
use App\Radar\Sources\SourceStatus;
use App\Radar\Sources\SourceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BmkgApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedBmkgSignals(): void
    {
        $source = Source::create(['name' => 'BMKG Fixture', 'slug' => 'bmkg-fixture', 'provider' => 'BMKG', 'source_type' => SourceType::API, 'status' => SourceStatus::ACTIVE, 'metadata' => []]);
        $earthquake = Signal::create(['fingerprint' => 'eq-api-test', 'title' => 'BMKG earthquake M5.2', 'summary' => 'Felt event', 'type' => 'NEW', 'priority' => 'HIGH', 'latitude' => -8.2, 'longitude' => 120.3, 'first_detected_at' => now(), 'last_updated_at' => now(), 'confidence_score' => 95, 'importance_score' => 80, 'source_count' => 1, 'status' => 'active', 'metadata' => ['provider' => 'BMKG', 'magnitude' => 5.2, 'latitude' => -8.2, 'longitude' => 120.3, 'depth_km' => 10, 'region' => 'Ruteng', 'event_time' => '2026-09-09T10:21:38+00:00', 'felt_description' => 'III', 'source_url' => 'https://bmkg.test/earthquake']]);
        $warning = Signal::create(['fingerprint' => 'warning-api-test', 'title' => 'Heavy rain warning', 'summary' => 'Warning area', 'type' => 'ALERT', 'priority' => 'HIGH', 'first_detected_at' => now(), 'last_updated_at' => now(), 'confidence_score' => 95, 'importance_score' => 75, 'source_count' => 1, 'status' => 'active', 'metadata' => ['provider' => 'BMKG', 'severity' => 'severe', 'urgency' => 'immediate', 'certainty' => 'likely', 'effective' => now()->toISOString(), 'expires' => now()->addHour()->toISOString(), 'geometry' => ['type' => 'Polygon', 'coordinates' => [[[120, -8], [121, -8], [121, -7], [120, -8]]]], 'source_url' => 'https://bmkg.test/warning']]);
        $earthquake->sources()->attach($source->id); $warning->sources()->attach($source->id);
    }

    public function test_bmkg_endpoints_filter_and_expose_normalized_fields(): void
    {
        $this->seedBmkgSignals();
        $this->getJson('/api/v1/earthquakes?min_magnitude=5&bbox=119,-9,121,-7')->assertOk()->assertJsonPath('data.0.magnitude', 5.2)->assertJsonPath('data.0.region', 'Ruteng');
        $this->getJson('/api/v1/weather/alerts?active=true')->assertOk()->assertJsonPath('data.0.severity', 'severe');
        $this->getJson('/api/v1/signals/map?bbox=119,-9,121,-7&layers=earthquake,weather-alert')->assertOk()->assertJsonCount(2, 'data');
    }
}