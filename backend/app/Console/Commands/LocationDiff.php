<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocationDataset;
use App\Radar\Locations\LocationDiffService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class LocationDiff extends Command
{
    protected $signature = 'radar:locations:diff {old} {new} {--json} {--output=}';
    protected $description = 'Compare two location dataset versions deterministically.';

    public function handle(LocationDiffService $service): int
    {
        $old = LocationDataset::query()->where('version', $this->argument('old'))->firstOrFail();
        $new = LocationDataset::query()->where('version', $this->argument('new'))->firstOrFail();
        $result = $service->compare($old, $new);
        $json = json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        if ($this->option('output')) Storage::disk('local')->put((string) $this->option('output'), $json);
        if ($this->option('json')) $this->line($json); else $this->table(['metric', 'count'], collect($result)->filter(fn ($value): bool => is_array($value) || is_int($value))->map(fn ($value, $key): array => [$key, is_array($value) ? count($value) : $value])->all());
        return self::SUCCESS;
    }
}