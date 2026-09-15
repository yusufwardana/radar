<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationProviderMapping;
use Illuminate\Console\Command;

class LocationStats extends Command
{
    protected $signature = 'radar:locations:stats';
    protected $description = 'Report location registry counts without claiming national coverage.';

    public function handle(): int
    {
        $this->table(['metric', 'count'], collect(['active_dataset' => optional(\App\Models\LocationDataset::query()->where('status', 'ACTIVE')->latest('id')->first())->version, 'province' => Location::query()->where('level', 'PROVINCE')->where('is_active', true)->count(), 'regency_city' => Location::query()->whereIn('level', ['REGENCY', 'CITY'])->where('is_active', true)->count(), 'district' => Location::query()->where('level', 'DISTRICT')->where('is_active', true)->count(), 'village' => Location::query()->where('level', 'VILLAGE')->where('is_active', true)->count(), 'kelurahan' => Location::query()->where('level', 'KELURAHAN')->where('is_active', true)->count(), 'adm4' => Location::query()->where('level', 'ADM4')->where('is_active', true)->count(), 'total' => Location::query()->where('is_active', true)->count(), 'bmkg_mappings' => LocationProviderMapping::query()->where('provider', 'BMKG')->where('is_active', true)->count(), 'forecast_ready' => Location::query()->where('is_active', true)->whereIn('level', ['VILLAGE', 'KELURAHAN', 'ADM4'])->whereNotNull('adm4_code')->whereHas('providerMappings', fn ($q) => $q->where('provider', 'BMKG')->where('provider_level', 'ADM4')->where('is_active', true))->count(), 'inactive' => Location::query()->where('is_active', false)->count(), 'orphan' => Location::query()->where('is_active', true)->whereNotNull('parent_id')->whereDoesntHave('parent')->count()])->map(fn ($value, $metric): array => [$metric, $value])->all());
        return self::SUCCESS;
    }
}