<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocationDataset;
use App\Radar\Locations\LocationStagingService;
use App\Radar\Locations\SqlLocationDatasetParser;
use App\Radar\Locations\FileLocationDatasetImporter;
use Illuminate\Console\Command;

class LocationStage extends Command
{
    protected $signature = 'radar:locations:stage {file} {--dataset=}';
    protected $description = 'Parse and validate a location file into staging without changing canonical locations.';

    public function handle(LocationStagingService $service): int
    {
        $dataset = LocationDataset::query()->where('version', (string) ($this->option('dataset') ?: 'staging-review'))->first();
        if ($dataset === null) {
            $dataset = LocationDataset::query()->create(['provider' => 'KEMENDAGRI', 'name' => 'Staging review', 'version' => (string) ($this->option('dataset') ?: 'staging-review'), 'source_url' => 'file://'.realpath((string) $this->argument('file')), 'source_format' => strtoupper(pathinfo((string) $this->argument('file'), PATHINFO_EXTENSION)), 'status' => 'DISCOVERED']);
        }
        $parser = strtolower(pathinfo((string) $this->argument('file'), PATHINFO_EXTENSION)) === 'sql' ? new SqlLocationDatasetParser() : new FileLocationDatasetImporter();
        $report = (new LocationStagingService($parser))->stage($dataset, (string) $this->argument('file'));
        $this->table(['metric', 'value'], collect($report)->map(fn ($value, $metric): array => [$metric, $value])->all());
        return ($report['status'] ?? '') === 'READY' ? self::SUCCESS : self::FAILURE;
    }
}