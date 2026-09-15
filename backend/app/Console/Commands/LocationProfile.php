<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Radar\Locations\FileLocationDatasetImporter;
use App\Radar\Locations\SqlLocationDatasetParser;
use Illuminate\Console\Command;

class LocationProfile extends Command
{
    protected $signature = 'radar:locations:profile {file}';
    protected $description = 'Profile an official location file without importing it.';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $rows = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'sql' ? (new SqlLocationDatasetParser())->records($path) : app(FileLocationDatasetImporter::class)->records($path);
        $first = null; $count = 0; $codes = [];
        foreach ($rows as $row) { $first ??= $row; $count++; $code = (string) ($row['code'] ?? $row['kode'] ?? ''); if ($code !== '') $codes[$code] = true; }
        $this->table(['metric', 'value'], [['rows', $count], ['unique_code_values', count($codes)], ['columns', $first ? implode(', ', array_keys($first)) : '']]);
        return self::SUCCESS;
    }
}