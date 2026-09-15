<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Radar\Ingestion\HttpTransport;
use App\Radar\Providers\Bmkg\BmkgEarthquakeAdapter;
use App\Radar\Providers\Bmkg\BmkgWeatherWarningAdapter;
use Illuminate\Support\Facades\Http;
use Radar\Domain\SourceDefinition;
use Tests\TestCase;

class BmkgAdapterTest extends TestCase
{
    public function test_normalizes_bmkg_latest_earthquake(): void
    {
        Http::fake(['https://bmkg.test/*' => Http::response(['Infogempa' => ['gempa' => [
            'DateTime' => '2026-09-09T10:21:38+00:00', 'Coordinates' => '-8.15,120.51', 'Magnitude' => '4.2', 'Kedalaman' => '3 km', 'Wilayah' => 'Ruteng', 'Potensi' => 'Tidak berpotensi tsunami',
        ]]], 200, ['Content-Type' => 'application/json'])]);
        $adapter = new BmkgEarthquakeAdapter(new SourceDefinition('bmkg', 'BMKG', 'BMKG', 'API', 'https://bmkg.test/autogempa.json'), app(HttpTransport::class));
        $item = $adapter->normalize($adapter->fetch())[0];
        $this->assertSame('Ruteng', $item->metadata['region']);
        $this->assertSame('MEDIUM', $item->metadata['priority']);
        $this->assertSame(-8.15, $item->metadata['latitude']);
    }

    public function test_normalizes_cap_warning_and_geojson_polygon(): void
    {
        Http::fake([
            'https://bmkg.test/rss' => Http::response('<rss><channel><item><title>Warning</title><link>https://bmkg.test/cap.xml</link><description>Area</description></item></channel></rss>', 200, ['Content-Type' => 'application/xml']),
            'https://bmkg.test/cap.xml' => Http::response('<alert><identifier>CAP-1</identifier><sent>2026-09-09T10:00:00Z</sent><info><headline>Warning headline</headline><description>Heavy rain</description><severity>Severe</severity><urgency>Immediate</urgency><certainty>Likely</certainty><effective>2026-09-09T10:00:00Z</effective><expires>2026-09-09T12:00:00Z</expires><area><areaDesc>Area A</areaDesc><polygon>-6.1,106.1 -6.2,106.1 -6.2,106.2 -6.1,106.1</polygon></area></info></alert>', 200, ['Content-Type' => 'application/xml']),
        ]);
        $adapter = new BmkgWeatherWarningAdapter(new SourceDefinition('bmkg-warning', 'BMKG Warning', 'BMKG', 'RSS', 'https://bmkg.test/rss'), app(HttpTransport::class));
        $item = $adapter->normalize($adapter->fetch())[0];
        $this->assertSame('CAP-1', $item->metadata['identifier']);
        $this->assertSame('HIGH', $item->metadata['priority']);
        $this->assertSame('HIGH', $item->metadata['priority']);
    }
}