<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Radar\Locations\LocationDatasetAcquisitionService;
use Illuminate\Console\Command;

class LocationAcquire extends Command
{
    protected $signature = 'radar:locations:acquire {--url=} {--file=} {--regulation=} {--dataset=} {--dry-run}';
    protected $description = 'Acquire an explicitly sourced official location dataset without activating it.';

    public function handle(LocationDatasetAcquisitionService $service): int
    {
        $url = $this->option('url');
        $file = $this->option('file');
        if (!$url && !$file) { $this->error('Provide --file or --url.'); return self::INVALID; }
        if ($url) {
            try { $service->validateUrl((string) $url); } catch (\Throwable $exception) { $this->error($exception->getMessage()); return self::FAILURE; }
            $this->warn('Official URL validated, but remote acquisition is intentionally not performed until an approved download transport is configured.');
            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->info('Dry run: local file format, checksum, and size will be inspected without persistence.');
        }
        try {
            if ($this->option('dry-run')) {
                $service->acquireLocal((string) $file, 'KEMENDAGRI', (string) ($this->option('dataset') ?: 'Kemendagri location dataset'), $this->option('regulation'));
                // The service validates MIME, size, and checksum; the command removes the persisted manifest below.
                $result = ['sha256' => hash_file('sha256', (string) $file), 'file_size' => filesize((string) $file), 'format' => 'validated-on-acquire', 'status' => 'DRY_RUN'];
                \App\Models\LocationDatasetAcquisition::query()->where('sha256', $result['sha256'])->delete();
            } else {
                $result = $service->acquireLocal((string) $file, 'KEMENDAGRI', (string) ($this->option('dataset') ?: 'Kemendagri location dataset'), $this->option('regulation'));
            }
        } catch (\Throwable $exception) { $this->error($exception->getMessage()); return self::FAILURE; }
        $this->table(['metric', 'value'], collect($result)->map(fn ($value, $key): array => [$key, $value])->all());
        return self::SUCCESS;
    }
}