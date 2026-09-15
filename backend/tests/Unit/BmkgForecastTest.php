<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Ingestion\HttpTransport;
use App\Radar\Providers\Bmkg\BmkgForecastAdapter;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BmkgForecastTest extends TestCase
{
    public function test_normalizes_documented_forecast_shape(): void
    {
        $payload = [
            'lokasi' => ['adm4' => '31.71.03.1001', 'desa' => 'Kemayoran', 'timezone' => 'Asia/Jakarta'],
            'data' => [[
                'cuaca' => [[[
                    'utc_datetime' => '2026-09-10 00:00:00',
                    'local_datetime' => '2026-09-10 07:00:00',
                    't' => 27,
                    'hu' => 75,
                    'weather_desc' => 'Cerah',
                    'weather_desc_en' => 'Sunny',
                    'ws' => 1.6,
                    'wd' => 'NE',
                    'tcc' => 34,
                    'vs_text' => '< 6 km',
                    'analysis_date' => '2026-09-09T12:00:00',
                ]]],
            ]],
        ];
        Http::fake(['https://api.bmkg.go.id/*' => Http::response($payload, 200, ['Content-Type' => 'application/json'])]);

        $forecast = (new BmkgForecastAdapter(app(HttpTransport::class)))->fetch('31.71.03.1001');

        $this->assertSame('31.71.03.1001', $forecast['location']['adm4']);
        $this->assertSame(27, $forecast['forecast'][0]['temperature_c']);
        $this->assertSame('Cerah', $forecast['forecast'][0]['weather_description']);
    }
}