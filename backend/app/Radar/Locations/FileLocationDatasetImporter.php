<?php

declare(strict_types=1);

namespace App\Radar\Locations;

use RuntimeException;

final class FileLocationDatasetImporter implements LocationDatasetImporter
{
    public function records(string $source): iterable
    {
        if (!is_file($source) || !is_readable($source)) throw new RuntimeException('LOCATION_SOURCE_UNREADABLE');
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'json'], true)) throw new RuntimeException('LOCATION_SOURCE_FORMAT_UNSUPPORTED');
        if (filesize($source) > 100 * 1024 * 1024) throw new RuntimeException('LOCATION_SOURCE_TOO_LARGE');

        if ($extension === 'json') {
            $payload = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) throw new RuntimeException('LOCATION_SOURCE_INVALID_JSON');
            foreach ($payload as $row) if (is_array($row)) yield $row;
            return;
        }

        $handle = fopen($source, 'rb');
        if ($handle === false) throw new RuntimeException('LOCATION_SOURCE_UNREADABLE');
        try {
            $headers = fgetcsv($handle);
            if (!is_array($headers) || $headers === []) throw new RuntimeException('LOCATION_SOURCE_MISSING_HEADERS');
            $headers = array_map(static fn ($header): string => trim((string) $header), $headers);
            $rowNumber = 1;
            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (count($values) !== count($headers)) throw new RuntimeException('LOCATION_SOURCE_MALFORMED_ROW_'.$rowNumber);
                yield array_combine($headers, $values) + ['source_row' => $rowNumber];
            }
        } finally { fclose($handle); }
    }
}