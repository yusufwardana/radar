<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocationDataset;
use App\Models\LocationImportBatch;
use App\Models\Location;
use Illuminate\Console\Command;

class LocationReconcile extends Command
{
    protected $signature = 'radar:locations:reconcile {dataset}';
    protected $description = 'Compare community source claims with parsed and imported counts without forcing a match.';

    public function handle(): int
    {
        $dataset = LocationDataset::query()->where('version', $this->argument('dataset'))->firstOrFail();
        $query = Location::query()->where('location_dataset_id', $dataset->id);
        $actual = ['regency' => (clone $query)->where('level', 'REGENCY')->count(), 'city' => (clone $query)->where('level', 'CITY')->count(), 'district' => (clone $query)->where('level', 'DISTRICT')->count(), 'village' => (clone $query)->where('level', 'VILLAGE')->count(), 'kelurahan' => (clone $query)->where('level', 'KELURAHAN')->count(), 'adm4_unresolved' => (clone $query)->where('level', 'ADM4')->count(), 'total' => (clone $query)->count()];
        $claimed = ['regency' => 416, 'city' => 98, 'district' => 7285, 'kelurahan' => 8496, 'village' => 75266];
        $this->table(['metric', 'claimed community README', 'actual imported'], collect($claimed)->map(fn ($value, $key): array => [$key, $value, $actual[$key] ?? 0])->all());
        $this->info('Reconciliation: MISMATCH or UNKNOWN unless all source semantics and counts reconcile.');
        return self::SUCCESS;
    }
}