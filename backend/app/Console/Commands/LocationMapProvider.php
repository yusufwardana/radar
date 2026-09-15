<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationProviderMapping;
use Illuminate\Console\Command;

class LocationMapProvider extends Command
{
    protected $signature = 'radar:locations:map-provider {provider=BMKG} {--dry-run}';
    protected $description = 'Create explicit provider mappings for compatible active ADM4 locations.';

    public function handle(): int
    {
        $provider = strtoupper((string) $this->argument('provider'));
        if ($provider !== 'BMKG') { $this->error('Only the verified BMKG mapping is supported.'); return self::FAILURE; }
        $created = 0;
        $eligible = 0;
        Location::query()->where('is_active', true)->where('status', 'ACTIVE')->whereIn('level', ['VILLAGE', 'KELURAHAN', 'ADM4'])->where('adm4_code', 'like', '__.__.__.____')->cursor()->each(function (Location $location) use (&$created, &$eligible): void {
            $eligible++;
            if ($this->option('dry-run')) return;
            $mapping = LocationProviderMapping::query()->firstOrCreate(['provider' => 'BMKG', 'provider_code' => $location->adm4_code], ['location_id' => $location->id, 'provider_level' => 'ADM4', 'location_dataset_id' => $location->location_dataset_id, 'is_active' => true]);
            if ($mapping->wasRecentlyCreated) $created++;
        });
        $this->info("BMKG eligible ADM4: {$eligible}; mappings created: {$created}");
        return self::SUCCESS;
    }
}