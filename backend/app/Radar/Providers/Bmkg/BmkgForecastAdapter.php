<?php

declare(strict_types=1);

namespace App\Radar\Providers\Bmkg;

use App\Radar\Ingestion\HttpTransport;
use App\Radar\Locations\AdministrativeCode;
use App\Radar\Locations\LocationLevel;
use InvalidArgumentException;

final class BmkgForecastAdapter
{
    public function __construct(private readonly HttpTransport $transport) {}

    /** @return array<string,mixed> */
    public function fetch(string $adm4): array
    {
        $code = AdministrativeCode::forLevel($adm4, LocationLevel::ADM4)->display;
        $payload = $this->transport->get('https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4='.rawurlencode($code), 'application/json')->payload;
        if (! is_array($payload) || ! isset($payload['lokasi'], $payload['data']) || ! is_array($payload['lokasi']) || ! is_array($payload['data'])) {
            throw new InvalidArgumentException('BMKG forecast schema is invalid.');
        }

        return $this->normalize($payload, $code);
    }

    /** @return array<string,mixed> */
    public function normalize(array $payload, string $adm4): array
    {
        $location = $payload['lokasi'];
        $points = [];
        foreach ($payload['data'] as $day) {
            foreach (($day['cuaca'] ?? []) as $point) {
                foreach ((is_array($point) && isset($point[0]) ? $point : [$point]) as $item) {
                    if (! is_array($item) || empty($item['utc_datetime'])) {
                        continue;
                    }
                    $points[] = ['utc_datetime' => $item['utc_datetime'], 'local_datetime' => $item['local_datetime'] ?? null, 'temperature_c' => $item['t'] ?? null, 'humidity_percent' => $item['hu'] ?? null, 'weather_description' => $item['weather_desc'] ?? null, 'weather_description_en' => $item['weather_desc_en'] ?? null, 'wind_speed_kmh' => $item['ws'] ?? null, 'wind_direction' => $item['wd'] ?? null, 'cloud_cover_percent' => $item['tcc'] ?? null, 'visibility' => $item['vs_text'] ?? null, 'analysis_date' => $item['analysis_date'] ?? null];
                }
            }
        }
        usort($points, fn (array $a, array $b): int => strcmp((string) $a['utc_datetime'], (string) $b['utc_datetime']));

        return ['location' => ['adm4' => $location['adm4'] ?? $adm4, 'name' => $location['desa'] ?? null, 'province' => $location['provinsi'] ?? null, 'regency' => $location['kotkab'] ?? null, 'district' => $location['kecamatan'] ?? null, 'latitude' => $location['lat'] ?? null, 'longitude' => $location['lon'] ?? null, 'timezone' => $location['timezone'] ?? null], 'provider' => 'BMKG', 'attribution' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)', 'analysis_date' => $points[0]['analysis_date'] ?? null, 'forecast' => $points];
    }
}
