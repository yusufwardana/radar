<?php

declare(strict_types=1);

namespace App\Radar\Forecast;

use App\Models\Location;
use App\Radar\Providers\Bmkg\BmkgForecastAdapter;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class ForecastService
{
    private const CACHE_TTL_SECONDS = 14400;

    public function __construct(private readonly BmkgForecastAdapter $adapter) {}

    /** @return array<string,mixed> */
    public function get(Location $location): array
    {
        $mapping = $location->providerMappings()
            ->where('provider', 'BMKG')
            ->where('provider_level', 'ADM4')
            ->where('is_active', true)
            ->first();
        if ($mapping === null) {
            throw new RuntimeException('LOCATION_MISSING_BMKG_MAPPING');
        }

        $adm4 = (string) $mapping->provider_code;
        $key = 'radar:forecast:bmkg:'.$adm4;
        $cached = Cache::get($key);
        if (is_array($cached) && isset($cached['forecast'], $cached['cache_expires_at']) && is_array($cached['forecast'])) {
            return [...$cached['forecast'], 'cached' => true, 'cache_expires_at' => $cached['cache_expires_at']];
        }

        $lock = Cache::lock('radar:forecast:lock:'.$adm4, 30);
        if (! $lock->get()) {
            throw new RuntimeException('FORECAST_REFRESH_IN_PROGRESS');
        }
        try {
            $forecast = $this->adapter->fetch($adm4);
            $expiresAt = now()->addSeconds(self::CACHE_TTL_SECONDS)->toISOString();
            Cache::put($key, ['forecast' => $forecast, 'cache_expires_at' => $expiresAt], self::CACHE_TTL_SECONDS);

            return [...$forecast, 'cached' => false, 'cache_expires_at' => $expiresAt];
        } finally {
            $lock->release();
        }
    }
}
