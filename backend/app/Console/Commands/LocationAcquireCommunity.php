<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Radar\Locations\LocationDatasetAcquisitionService;
use Illuminate\Console\Command;

class LocationAcquireCommunity extends Command
{
    protected $signature = 'radar:locations:acquire-community {--file=} {--commit=} {--repository=https://github.com/cahyadsn/wilayah} {--path=db/wilayah.sql} {--dry-run}';
    protected $description = 'Acquire a pinned cahyadsn/wilayah SQL file as third-party derived data.';

    public function handle(LocationDatasetAcquisitionService $service): int
    {
        $file = (string) $this->option('file'); $commit = (string) $this->option('commit');
        if ($file === '' || $commit === '') { $this->error('Provide --file and a 40-character --commit.'); return self::INVALID; }
        try { $result = $service->acquireCommunity((string) $this->option('repository'), $commit, (string) $this->option('path'), $file); }
        catch (\Throwable $exception) { $this->error($exception->getMessage()); return self::FAILURE; }
        if ($this->option('dry-run')) $this->warn('The file was validated and acquired; no canonical dataset was imported or activated.');
        $this->table(['metric', 'value'], collect($result)->map(fn ($value, $key): array => [$key, is_array($value) ? json_encode($value) : $value])->all());
        return self::SUCCESS;
    }
}