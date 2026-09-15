<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use App\Models\LocationDatasetAcquisition;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

final class LocationDatasetAcquisitionService
{
    public function __construct(private readonly OfficialDatasetUrlValidator $urlValidator) {}

    /** @return array<string,mixed> */
    public function acquireLocal(string $file, string $provider, string $datasetName, ?string $regulation = null, ?string $sourceUrl = null): array
    {
        if (!is_file($file) || !is_readable($file)) throw new RuntimeException('LOCATION_DATASET_FILE_UNREADABLE');
        $size = filesize($file);
        $max = (int) config('radar.locations.max_file_bytes', 250 * 1024 * 1024);
        if ($size === false || $size > $max) throw new RuntimeException('LOCATION_DATASET_FILE_TOO_LARGE');
        $format = $this->detectFormat($file);
        $sha256 = hash_file('sha256', $file);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $stored = 'location-datasets/'.strtolower(preg_replace('/[^a-z0-9]+/i', '-', $provider.'-'.($regulation ?: 'dataset')).'-'.substr($sha256, 0, 16)).'.'.$extension;
        Storage::disk('local')->put($stored, fopen($file, 'rb'));
        $sourceUrl = $sourceUrl ?: 'file://'.$file;
        $acquisition = LocationDatasetAcquisition::query()->updateOrCreate(['provider' => $provider, 'sha256' => $sha256], ['regulation_number' => $regulation, 'dataset_name' => $datasetName, 'source_url' => $sourceUrl, 'source_format' => $format, 'file_size' => $size, 'status' => LocationAcquisitionStatus::VERIFIED, 'stored_path' => $stored, 'retrieved_at' => now()]);
        return ['id' => $acquisition->id, 'sha256' => $sha256, 'file_size' => $size, 'format' => $format, 'stored_path' => $stored, 'status' => $acquisition->status->value];
    }

    public function validateUrl(string $url): string { return $this->urlValidator->validate($url); }

    /** @return array<string,string> */
    public function validateCommunitySource(string $repository, string $commit, string $filePath): array
    {
        if ($repository !== 'https://github.com/cahyadsn/wilayah' || $filePath !== 'db/wilayah.sql' || preg_match('/^[0-9a-f]{40}$/', $commit) !== 1) {
            throw new InvalidArgumentException('LOCATION_COMMUNITY_SOURCE_NOT_ALLOWED');
        }
        return ['repository' => $repository, 'commit_sha' => $commit, 'file_path' => $filePath, 'raw_url' => 'https://raw.githubusercontent.com/cahyadsn/wilayah/'.$commit.'/'.$filePath];
    }

    /** @return array<string,mixed> */
    public function acquireCommunity(string $repository, string $commit, string $filePath, string $file): array
    {
        $source = $this->validateCommunitySource($repository, $commit, $filePath);
        $result = $this->acquireLocal($file, 'cahyadsn/wilayah', 'Community Indonesian administrative regions', 'Kepmendagri 300.2.2-2430 Tahun 2025', $source['raw_url']);
        $acquisition = LocationDatasetAcquisition::query()->findOrFail($result['id']);
        $acquisition->update(['notes' => ['provenance_class' => 'THIRD_PARTY_DERIVED', 'trust' => 'COMMUNITY', 'repository' => $repository, 'commit_sha' => $commit, 'file_path' => $filePath, 'license' => 'MIT', 'upstream_claim' => 'Kepmendagri 300.2.2-2430 Tahun 2025', 'official_source_verified' => false, 'sql_header_claim' => 'Kepmendagri 300.2.2-2138 Tahun 2025']]);
        return [...$result, 'repository' => $repository, 'commit_sha' => $commit, 'file_path' => $filePath, 'provenance_class' => 'THIRD_PARTY_DERIVED', 'official_source_verified' => false];
    }

    private function detectFormat(string $file): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file) ?: '';
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $formats = match (true) {
            $extension === 'csv' && in_array($mime, ['text/plain', 'text/csv', 'application/csv'], true) => 'CSV',
            $extension === 'json' && str_contains($mime, 'json') => 'JSON',
            $extension === 'sql' && in_array($mime, ['text/plain', 'application/sql', 'text/x-sql'], true) => 'SQL',
            $extension === 'xlsx' && in_array($mime, ['application/zip', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true) => 'XLSX',
            default => null,
        };
        if ($formats === null) throw new InvalidArgumentException('LOCATION_DATASET_FORMAT_MISMATCH');
        return $formats;
    }
}