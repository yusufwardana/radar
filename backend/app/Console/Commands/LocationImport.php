<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\LocationDataset;
use App\Radar\Locations\FileLocationDatasetImporter;
use App\Radar\Locations\LocationImportService;
use App\Radar\Locations\LocationDatasetStatus;
use App\Models\LocationProviderMapping;
use App\Radar\Locations\LocationLevel;
use App\Radar\Locations\LocationStagingService;
use App\Radar\Locations\SqlLocationDatasetParser;
use Illuminate\Console\Command;

class LocationImport extends Command
{
    protected $signature = 'radar:locations:import {--source=} {--file=} {--dataset=} {--provider=} {--commit=} {--community} {--dry-run} {--activate} {--strict}';
    protected $description = 'Import an explicitly supplied, auditable location dataset.';

    public function handle(): int
    {
        $source = $this->option('file') ?: $this->option('source');
        if (!$source) {
            $dataset = LocationDataset::query()->firstOrCreate(['provider' => 'BMKG', 'version' => 'documented-kemayoran-sample'], ['name' => 'BMKG documented ADM4 sample', 'regulation_number' => 'Kepmendagri 100.1.1-6117 Tahun 2022', 'source_url' => 'https://data.bmkg.go.id/prakiraan-cuaca/', 'source_format' => 'JSON', 'status' => LocationDatasetStatus::VALIDATED, 'metadata' => ['coverage' => 'partial', 'canonical_national_dataset' => false]]);
            $location = Location::query()->updateOrCreate(['code' => '31.71.03.1001'], ['location_dataset_id' => $dataset->id, 'level' => LocationLevel::KELURAHAN, 'code' => '31.71.03.1001', 'source_code' => '31.71.03.1001', 'adm1_code' => '31', 'adm2_code' => '31.71', 'adm3_code' => '31.71.03', 'adm4_code' => '31.71.03.1001', 'name' => 'Kemayoran', 'normalized_name' => 'kemayoran', 'type' => 'KELURAHAN', 'province_name' => 'DKI Jakarta', 'regency_name' => 'Kota Adm. Jakarta Pusat', 'district_name' => 'Kemayoran', 'latitude' => -6.1647214778, 'longitude' => 106.8453837867, 'timezone' => 'Asia/Jakarta', 'metadata' => ['source' => 'BMKG documented ADM4 example', 'sample' => true], 'status' => 'ACTIVE', 'is_active' => true]);
            LocationProviderMapping::query()->updateOrCreate(['provider' => 'BMKG', 'provider_code' => '31.71.03.1001'], ['location_id' => $location->id, 'provider_level' => 'ADM4', 'location_dataset_id' => $dataset->id, 'is_active' => true]);
            $this->info('Upserted verified Kemayoran sample. National coverage is not claimed.');
            return self::SUCCESS;
        }
        $isCommunity = (bool) $this->option('community');
        $provider = (string) ($this->option('provider') ?: ($isCommunity ? 'cahyadsn/wilayah' : 'KEMENDAGRI'));
        $version = (string) ($this->option('dataset') ?: ($isCommunity ? 'cahyadsn-wilayah-'.$this->option('commit') : 'unversioned-review'));
        $dataset = LocationDataset::query()->firstOrCreate(['provider' => $provider, 'version' => $version], ['name' => $isCommunity ? 'cahyadsn/wilayah community-derived registry' : 'Unverified supplied dataset', 'regulation_number' => $isCommunity ? 'Kepmendagri 300.2.2-2430 Tahun 2025 (upstream claim; unverified)' : null, 'source_url' => $isCommunity && $this->option('commit') ? 'https://raw.githubusercontent.com/cahyadsn/wilayah/'.((string) $this->option('commit')).'/db/wilayah.sql' : 'file://'.realpath($source), 'source_format' => strtoupper(pathinfo($source, PATHINFO_EXTENSION)), 'status' => LocationDatasetStatus::DISCOVERED, 'metadata' => $isCommunity ? ['trust' => 'COMMUNITY', 'provenance_class' => 'THIRD_PARTY_DERIVED', 'official_source_verified' => false, 'repository' => 'https://github.com/cahyadsn/wilayah', 'commit_sha' => $this->option('commit'), 'source_file' => 'db/wilayah.sql', 'license' => 'MIT', 'upstream_claim' => 'Kepmendagri 300.2.2-2430 Tahun 2025', 'sql_header_claim' => 'Kepmendagri 300.2.2-2138 Tahun 2025'] : null]);
        if ($isCommunity) $dataset->update(['name' => 'cahyadsn/wilayah community-derived registry', 'regulation_number' => 'Kepmendagri 300.2.2-2430 Tahun 2025 (upstream claim; unverified)', 'source_url' => 'https://raw.githubusercontent.com/cahyadsn/wilayah/'.((string) $this->option('commit')).'/db/wilayah.sql', 'source_format' => 'SQL', 'metadata' => ['trust' => 'COMMUNITY', 'provenance_class' => 'THIRD_PARTY_DERIVED', 'official_source_verified' => false, 'repository' => 'https://github.com/cahyadsn/wilayah', 'commit_sha' => $this->option('commit'), 'source_file' => 'db/wilayah.sql', 'license' => 'MIT', 'upstream_claim' => 'Kepmendagri 300.2.2-2430 Tahun 2025', 'sql_header_claim' => 'Kepmendagri 300.2.2-2138 Tahun 2025']]);
        if (!$this->option('dry-run')) {
            $parser = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'sql' ? new SqlLocationDatasetParser() : app(FileLocationDatasetImporter::class);
            $staging = (new LocationStagingService($parser))->stage($dataset, $source);
            if (($staging['status'] ?? '') !== 'READY') {
                $this->error('LOCATION_STAGING_INTEGRITY_FAILED');
                $this->table(['metric', 'value'], collect($staging)->map(fn ($value, $metric): array => [$metric, $value])->all());
                if ($this->option('strict')) throw new \RuntimeException('LOCATION_IMPORT_INTEGRITY_FAILED');
                return self::FAILURE;
            }
        }
        $parser = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'sql' ? new SqlLocationDatasetParser() : app(FileLocationDatasetImporter::class);
        $report = (new LocationImportService($parser))->import($dataset, $source, (bool) $this->option('dry-run'), (bool) $this->option('strict'), (bool) $this->option('activate'));
        $this->table(['metric', 'value'], collect($report)->map(fn ($value, $metric): array => [$metric, $value])->all());
        return $this->option('strict') && (($report['invalid'] ?? 0) || ($report['duplicates'] ?? 0) || ($report['orphans'] ?? 0)) ? self::FAILURE : self::SUCCESS;
    }
}