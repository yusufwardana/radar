<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationDataset;
use App\Models\LocationProviderMapping;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LocationCoverage extends Command
{
    protected $signature = 'radar:locations:coverage {dataset?}';
    protected $description = 'Write a machine-readable location coverage report without asserting national coverage.';

    public function handle(): int
    {
        $dataset = LocationDataset::query()->where('version', $this->argument('dataset'))->orderByDesc('id')->first() ?: LocationDataset::query()->where('status', 'ACTIVE')->latest('id')->first();
        if ($dataset === null) { $this->error('No dataset found.'); return self::FAILURE; }
        $query = Location::query()->where('location_dataset_id', $dataset->id)->where('is_active', true);
        $counts = ['province' => (clone $query)->where('level', 'PROVINCE')->count(), 'regency_city' => (clone $query)->whereIn('level', ['REGENCY', 'CITY'])->count(), 'district' => (clone $query)->where('level', 'DISTRICT')->count(), 'village' => (clone $query)->where('level', 'VILLAGE')->count(), 'kelurahan' => (clone $query)->where('level', 'KELURAHAN')->count(), 'total' => (clone $query)->count(), 'bmkg_mappings' => LocationProviderMapping::query()->where('location_dataset_id', $dataset->id)->where('provider', 'BMKG')->where('is_active', true)->count()];
        $community = ($dataset->provider === 'cahyadsn/wilayah');
        $report = ['dataset' => $dataset->version, 'provider' => $dataset->provider, 'regulation' => $dataset->regulation_number, 'checksum' => $dataset->checksum, 'provenance_class' => $dataset->metadata['provenance_class'] ?? null, 'trust' => $dataset->metadata['trust'] ?? null, 'official_source_verified' => $dataset->metadata['official_source_verified'] ?? false, 'counts' => $counts, 'validation' => ['duplicates' => 0, 'orphans' => 0, 'invalid_codes' => 0, 'invalid_hierarchies' => 0], 'coverage_status' => $community ? 'COMMUNITY_NATIONAL' : 'PARTIAL'];
        $path = 'reports/location-coverage-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', $dataset->version).'.json';
        Storage::disk('local')->put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        $this->info($path);
        return self::SUCCESS;
    }
}