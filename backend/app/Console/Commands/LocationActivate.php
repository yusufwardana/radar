<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationDataset;
use App\Radar\Locations\LocationDatasetStatus;
use App\Radar\Locations\LocationStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LocationActivate extends Command
{
    protected $signature = 'radar:locations:activate {dataset}';
    protected $description = 'Activate an already imported location dataset transactionally, including rollback.';

    public function handle(): int
    {
        $dataset = LocationDataset::query()->where('version', $this->argument('dataset'))->firstOrFail();
        if ($dataset->status === LocationDatasetStatus::FAILED) { $this->error('Cannot activate a failed dataset.'); return self::FAILURE; }
        $count = Location::query()->where('location_dataset_id', $dataset->id)->count();
        if ($count === 0) { $this->error('Dataset has no imported locations.'); return self::FAILURE; }
        DB::transaction(function () use ($dataset): void {
            LocationDataset::query()->where('status', LocationDatasetStatus::ACTIVE)->whereKeyNot($dataset->id)->update(['status' => LocationDatasetStatus::SUPERSEDED]);
            Location::query()->where('location_dataset_id', $dataset->id)->update(['is_active' => true, 'status' => LocationStatus::ACTIVE]);
            Location::query()->where('location_dataset_id', '!=', $dataset->id)->update(['is_active' => false, 'status' => LocationStatus::SUPERSEDED]);
            $dataset->update(['status' => LocationDatasetStatus::ACTIVE]);
        });
        $this->info("Activated dataset {$dataset->version}.");
        return self::SUCCESS;
    }
}