<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use App\Models\LocationDataset;
use App\Models\LocationImportBatch;
use App\Models\LocationStagingRow;
use Illuminate\Support\Str;

final class LocationStagingService
{
    public function __construct(private readonly LocationDatasetImporter $importer) {}

    /** @return array<string,int|string> */
    public function stage(LocationDataset $dataset, string $source): array
    {
        $started = microtime(true);
        $batch = LocationImportBatch::query()->create(['location_dataset_id' => $dataset->id, 'status' => LocationImportBatchStatus::PARSING, 'started_at' => now()]);
        $codes = [];
        $processed = $valid = $invalid = $duplicates = $orphans = 0;
        $records = [];
        foreach ($this->importer->records($source) as $row) {
            $processed++;
            $level = strtoupper(trim((string) ($row['level'] ?? $row['type'] ?? '')));
            $level = match ($level) { 'DESA' => 'VILLAGE', 'KABUPATEN' => 'REGENCY', 'KOTA', 'KOTA ADMINISTRASI' => 'CITY', default => $level };
            $rawCode = trim((string) ($row['code'] ?? $row['kode'] ?? ''));
            $name = trim((string) ($row['name'] ?? $row['nama'] ?? ''));
            $errors = [];
            try { $code = AdministrativeCode::forLevel($rawCode, LocationLevel::from($level)); } catch (\Throwable $exception) { $code = null; $errors[] = $exception->getMessage(); }
            if ($code !== null && isset($codes[$code->digits])) { $duplicates++; $errors[] = 'DUPLICATE_CODE'; }
            if ($code !== null) { $codes[$code->digits] = true; $records[] = ['code' => $code, 'level' => $level]; }
            if ($errors === []) { $valid++; } else { $invalid++; }
            LocationStagingRow::query()->create(['batch_id' => $batch->id, 'row_number' => (int) ($row['source_row'] ?? $processed + 1), 'source_code' => $rawCode, 'source_name' => $name, 'source_level' => $level, 'normalized_code' => $code?->display, 'normalized_name' => Str::of($name)->lower()->squish()->toString(), 'validation_status' => $errors === [] ? 'VALID' : 'INVALID', 'validation_errors' => $errors]);
        }
        foreach ($records as $record) { if ($record['code']->parent() !== null && !isset($codes[$record['code']->parent()->digits])) $orphans++; }
        $status = ($invalid || $duplicates || $orphans) ? LocationImportBatchStatus::FAILED : LocationImportBatchStatus::READY;
        $batch->update(['status' => $status, 'completed_at' => now(), 'processed' => $processed, 'valid' => $valid, 'invalid' => $invalid, 'duplicates' => $duplicates, 'orphans' => $orphans, 'duration_ms' => (int) round((microtime(true) - $started) * 1000)]);
        return ['batch_id' => $batch->id, 'status' => $status->value, 'processed' => $processed, 'valid' => $valid, 'invalid' => $invalid, 'duplicates' => $duplicates, 'orphans' => $orphans];
    }
}