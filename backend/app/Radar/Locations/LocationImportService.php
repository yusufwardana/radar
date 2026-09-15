<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use App\Models\Location;
use App\Models\LocationDataset;
use App\Radar\Locations\LocationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class LocationImportService
{
    public function __construct(private readonly LocationDatasetImporter $importer) {}

    /** @return array<string,int|bool|string> */
    public function import(LocationDataset $dataset, string $source, bool $dryRun = false, bool $strict = false, bool $activate = false): array
    {
        $report = ['dataset' => $dataset->version, 'processed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0, 'orphans' => 0, 'invalid_hierarchies' => 0, 'created' => 0, 'updated' => 0, 'unchanged' => 0, 'warnings' => 0, 'activated' => false];
        if (!$dryRun) return $this->streamImport($dataset, $source, $strict, $activate, $report);
        $records = [];
        $codes = [];
        $levelsByCode = [];
        $namesByCode = [];
        foreach ($this->importer->records($source) as $row) {
            $report['processed']++;
            $normalized = $this->normalize($row);
            try {
                $code = AdministrativeCode::forLevel((string) ($normalized['code'] ?? ''), LocationLevel::from((string) ($normalized['level_value'] ?? '')));
                $level = LocationLevel::from((string) ($normalized['level_value'] ?? ''));
                if (isset($codes[$code->digits])) {
                    $report['duplicates']++;
                    if ($levelsByCode[$code->digits] !== $level->value || $namesByCode[$code->digits] !== $normalized['name']) {
                        $report['invalid_hierarchies'] = ($report['invalid_hierarchies'] ?? 0) + 1;
                    }
                    continue;
                }
                $codes[$code->digits] = true;
                $levelsByCode[$code->digits] = $level->value;
                $namesByCode[$code->digits] = $normalized['name'];
                $normalized['code'] = $code->display;
                $normalized['source_code'] = $normalized['source_code'] ?: $code->display;
                $normalized['adm1_code'] = substr($code->display, 0, 2);
                $normalized['adm2_code'] = strlen($code->digits) >= 4 ? substr($code->display, 0, 5) : null;
                $normalized['adm3_code'] = strlen($code->digits) >= 6 ? substr($code->display, 0, 8) : null;
                $normalized['adm4_code'] = $code->isAdm4() ? $code->display : null;
                $records[] = $normalized;
                $report['valid']++;
            } catch (\Throwable) { $report['invalid']++; }
        }

        $knownCodes = $codes;
        foreach ($records as $record) {
            $parent = AdministrativeCode::parse($record['code'])->parent();
            if ($parent !== null && !isset($knownCodes[$parent->digits])) $report['orphans']++;
        }
        if ($strict && ($report['invalid'] || $report['duplicates'] || $report['orphans'] || ($report['invalid_hierarchies'] ?? 0))) throw new RuntimeException('LOCATION_IMPORT_INTEGRITY_FAILED');
        return $report;
    }

    /** @param array<string,int|bool|string> $report */
    private function streamImport(LocationDataset $dataset, string $source, bool $strict, bool $activate, array $report): array
    {
        $codes = []; $importedCodes = []; $batch = [];
        DB::transaction(function () use (&$report, &$codes, &$importedCodes, &$batch, $dataset, $source, $strict, $activate): void {
            foreach ($this->importer->records($source) as $row) {
                $report['processed']++;
                try {
                    $normalized = $this->normalize($row);
                    $level = LocationLevel::from((string) $normalized['level_value']);
                    $code = AdministrativeCode::forLevel((string) $normalized['code'], $level);
                    if (isset($codes[$code->digits])) { $report['duplicates']++; continue; }
                    $codes[$code->digits] = true;
                    $normalized['code'] = $code->display;
                    $normalized['source_code'] = $normalized['source_code'] ?: $code->display;
                    $normalized['adm1_code'] = substr($code->display, 0, 2);
                    $normalized['adm2_code'] = strlen($code->digits) >= 4 ? substr($code->display, 0, 5) : null;
                    $normalized['adm3_code'] = strlen($code->digits) >= 6 ? substr($code->display, 0, 8) : null;
                    $normalized['adm4_code'] = $code->isAdm4() ? $code->display : null;
                    $normalized['location_dataset_id'] = $dataset->id;
                    $normalized['status'] = LocationStatus::ACTIVE->value;
                    $normalized['level'] = $level->value;
                    $normalized['metadata'] = json_encode($normalized['metadata'], JSON_THROW_ON_ERROR);
                    unset($normalized['level_value']);
                    $batch[] = $normalized;
                    $importedCodes[] = $code->display;
                    $report['valid']++;
                    if (count($batch) >= 500) $this->flushChunk($batch, $report);
                } catch (\Throwable) { $report['invalid']++; }
            }
            if ($batch !== []) $this->flushChunk($batch, $report);
            if ($strict && ($report['invalid'] || $report['duplicates'])) throw new RuntimeException('LOCATION_IMPORT_INTEGRITY_FAILED');
            $dataset->update(['status' => $activate ? LocationDatasetStatus::ACTIVE : LocationDatasetStatus::IMPORTED, 'checksum' => is_file($source) ? hash_file('sha256', $source) : $dataset->checksum, 'imported_at' => now()]);
            if ($activate) LocationDataset::query()->whereKeyNot($dataset->id)->where('status', LocationDatasetStatus::ACTIVE)->update(['status' => LocationDatasetStatus::SUPERSEDED]);
        });
        $report['activated'] = $activate;
        return $report;
    }

    /** @param array<int,array<string,mixed>> $batch */
    private function flushChunk(array &$batch, array &$report): void
    {
        $codes = array_column($batch, 'code');
        $existing = array_fill_keys(Location::query()->whereIn('code', $codes)->pluck('code')->all(), true);
        Location::query()->upsert($batch, ['code'], ['location_dataset_id', 'level', 'source_code', 'source_row', 'adm1_code', 'adm2_code', 'adm3_code', 'adm4_code', 'name', 'normalized_name', 'type', 'is_active', 'status', 'metadata', 'updated_at']);
        $report['updated'] += count(array_intersect($codes, array_keys($existing)));
        $report['created'] += count($codes) - count(array_intersect($codes, array_keys($existing)));
        foreach (Location::query()->whereIn('code', $codes)->cursor() as $location) {
            $parent = AdministrativeCode::parse($location->code)->parent();
            if ($parent !== null) $location->update(['parent_id' => Location::query()->where('code', $parent->display)->value('id')]);
        }
        $batch = [];
    }

    private function normalize(array $row): array
    {
        $level = strtoupper(trim((string) ($row['level'] ?? $row['type'] ?? 'KELURAHAN')));
        $level = match ($level) { 'DESA' => 'VILLAGE', 'KABUPATEN' => 'REGENCY', 'KOTA', 'KOTA ADMINISTRASI' => 'CITY', default => $level };
        $name = trim((string) ($row['name'] ?? $row['nama'] ?? ''));
        return ['level_value' => $level, 'code' => trim((string) ($row['code'] ?? $row['kode'] ?? '')), 'source_code' => trim((string) ($row['source_code'] ?? '')), 'source_row' => $row['source_row'] ?? null, 'name' => $name, 'normalized_name' => Str::of($name)->lower()->squish()->replaceMatches('/[^\pL\pN ]/u', '')->toString(), 'type' => $row['type'] ?? null, 'is_active' => true, 'metadata' => ['imported' => true]];
    }
}