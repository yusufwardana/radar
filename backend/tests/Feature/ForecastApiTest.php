<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ForecastApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_uses_active_bmkg_mapping_and_returns_cached_response(): void
    {
        $location = Location::create(['level' => 'KELURAHAN', 'code' => '31.71.03.1001', 'adm1_code' => '31', 'adm2_code' => '31.71', 'adm3_code' => '31.71.03', 'adm4_code' => '31.71.03.1001', 'name' => 'Kemayoran', 'normalized_name' => 'kemayoran', 'is_active' => true, 'status' => 'ACTIVE']);
        LocationProviderMapping::create(['location_id' => $location->id, 'provider' => 'BMKG', 'provider_code' => '31.71.03.1001', 'provider_level' => 'ADM4', 'is_active' => true]);
        Http::fake(['https://api.bmkg.go.id/*' => Http::response(['lokasi' => ['adm4' => '31.71.03.1001', 'desa' => 'Kemayoran'], 'data' => [['cuaca' => [[['utc_datetime' => '2026-09-10 00:00:00', 't' => 27, 'weather_desc' => 'Cerah']]]]]], 200, ['Content-Type' => 'application/json'])]);

        $this->getJson('/api/v1/locations/'.$location->id.'/forecast')->assertOk()->assertJsonPath('provider', 'BMKG')->assertJsonPath('forecast.0.temperature_c', 27)->assertJsonPath('cached', false);
        $this->getJson('/api/v1/locations/'.$location->id.'/forecast')->assertOk()->assertJsonPath('cached', true);
        Http::assertSentCount(1);
    }

    public function test_forecast_requires_active_bmkg_mapping(): void
    {
        $location = Location::create(['level' => 'KELURAHAN', 'code' => '31.71.03.1002', 'name' => 'Test', 'normalized_name' => 'test', 'is_active' => true, 'status' => 'ACTIVE']);
        $this->getJson('/api/v1/locations/'.$location->id.'/forecast')->assertStatus(422)->assertSee('LOCATION_NOT_FORECAST_READY');
    }
}
