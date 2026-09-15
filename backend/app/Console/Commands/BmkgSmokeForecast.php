<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Radar\Forecast\ForecastService;
use Illuminate\Console\Command;

class BmkgSmokeForecast extends Command
{
    protected $signature = 'radar:bmkg:test-forecast {adm4}';
    protected $description = 'Fetch and summarize BMKG on-demand forecast data for an ADM4 code.';

    public function handle(ForecastService $service): int
    {
        $location = Location::query()->where('adm4_code', (string) $this->argument('adm4'))->first();
        if (!$location) { $this->error('Location ADM4 not found in RADAR registry.'); return self::FAILURE; }
        $forecast = $service->get($location);
        $this->info('BMKG Forecast: OK');
        $this->line('Location: '.$location->name.' ('.$location->adm4_code.')');
        $this->line('Provider: '.$forecast['attribution']);
        $this->line('Points: '.count($forecast['forecast']).' Cached: '.($forecast['cached'] ? 'yes' : 'no'));
        foreach (array_slice($forecast['forecast'], 0, 3) as $point) $this->line(($point['local_datetime'] ?? $point['utc_datetime']).' | '.$point['temperature_c'].'°C | '.$point['weather_description']);
        return self::SUCCESS;
    }
}