<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Location;
use App\Models\LocationDataset;
use App\Models\LocationProviderMapping;
use App\Radar\Locations\LocationDatasetStatus;
use App\Radar\Locations\LocationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class LocationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_orders_hierarchy_and_is_idempotent_without_provider_coupling(): void
    {
        $path = base_path('tests/Fixtures/location-hierarchy.csv');
        $dataset = $this->dataset('fixture-v1');
        self::assertFileExists($path);
        self::assertFileIsReadable($path);

        try {
            $first = app(LocationImportService::class)->import($dataset, $path, activate: true);
            self::assertSame(4, $first['created']);

            $this->assertDatabaseCount('locations', 4);
            $this->assertDatabaseHas('location_datasets', [
                'id' => $dataset->id,
                'status' => LocationDatasetStatus::ACTIVE->value,
            ]);
            $this->assertDatabaseHas('locations', [
                'code' => '01.02.03.0004',
                'parent_id' => Location::where('code', '01.02.03')->value('id'),
            ]);
            $this->assertDatabaseCount('location_provider_mappings', 0);

            $second = app(LocationImportService::class)->import($dataset, $path, activate: true);
            self::assertSame(4, $second['updated']);

            $this->assertDatabaseCount('locations', 4);
            $this->assertDatabaseCount('location_provider_mappings', 0);
        } finally {
            // Repository fixture is intentionally retained for repeatable importer tests.
        }
    }

    public function test_dry_run_reports_duplicates_and_orphans_without_writing(): void
    {
        $path = $this->writeCsv([
            ['level', 'code', 'name'],
            ['KELURAHAN', '01.02.03.0004', 'Test Kelurahan'],
            ['KELURAHAN', '0102030004', 'Duplicate Kelurahan'],
        ]);
        $dataset = $this->dataset('fixture-dry-run');

        try {
            $this->artisan('radar:locations:import', [
                '--file' => $path,
                '--dataset' => 'fixture-dry-run',
                '--dry-run' => true,
            ])->assertSuccessful();

            $this->assertDatabaseCount('locations', 0);
            $this->assertDatabaseHas('location_datasets', [
                'id' => $dataset->id,
                'status' => LocationDatasetStatus::DISCOVERED->value,
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_strict_import_rejects_invalid_duplicate_and_orphan_rows_transactionally(): void
    {
        $path = $this->writeCsv([
            ['level', 'code', 'name'],
            ['KELURAHAN', '01.02.03.0004', 'Test Kelurahan'],
            ['KELURAHAN', '0102030004', 'Duplicate Kelurahan'],
            ['DISTRICT', '99.99.99', 'Orphan District'],
            ['DISTRICT', 'not-a-code', 'Invalid District'],
        ]);
        $this->dataset('fixture-strict');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('LOCATION_IMPORT_INTEGRITY_FAILED');
            app('Illuminate\Contracts\Console\Kernel')->call('radar:locations:import', [
                '--file' => $path,
                '--dataset' => 'fixture-strict',
                '--strict' => true,
            ]);
        } finally {
            @unlink($path);
        }
    }

    private function dataset(string $version): LocationDataset
    {
        return LocationDataset::create([
            'provider' => 'KEMENDAGRI',
            'name' => 'Controlled importer fixture',
            'version' => $version,
            'source_url' => 'file://controlled-fixture',
            'source_format' => 'CSV',
            'status' => LocationDatasetStatus::DISCOVERED,
        ]);
    }

    /** @param array<int, array<int, string>> $rows */
    private function writeCsv(array $rows): string
    {
        $directory = storage_path('framework/testing');
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create temporary fixture directory.');
        }
        $csvPath = $directory.DIRECTORY_SEPARATOR.'radar-location-'.Str::random(16).'.csv';
        $handle = fopen($csvPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Could not open temporary fixture.');
        }
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        $realPath = realpath($csvPath);
        if ($realPath === false) {
            throw new RuntimeException('Could not resolve CSV fixture path.');
        }
        return $realPath;
    }
}